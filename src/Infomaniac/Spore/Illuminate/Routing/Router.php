<?php
namespace Infomaniac\Spore\Illuminate\Routing;

use DocBlock\Parser;
use Exception;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router as BaseRouter;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use Infomaniac\Spore\Annotation\AnnotatedDefinition;
use Infomaniac\Spore\Annotation\RouteParser;
use Infomaniac\Spore\Exception\SecurityException;
use ReflectionClass;
use ReflectionMethod;

/**
 * Class Router
 */
class Router extends BaseRouter
{
    /**
     * @var array
     */
    private $controllers;
    /**
     * @var Parser
     */
    private $parser;

    /**
     * Mappings between Route and AnnotatedDefinition
     *
     * @var array
     */
    private $definitionMap;

    public function __construct(Container $container = null)
    {
        $this->parser        = new Parser();
        $this->definitionMap = array();

        $router = parent::__construct($container);

        // define before & after filters for pre- and post-processing
        $this->filter('beforeAnnotatedRoute', array($this, 'beforeFilter'));
        $this->filter('afterAnnotatedRoute', array($this, 'afterFilter'));

        return $router;
    }

    public function beforeFilter(Route $route, \Illuminate\Http\Request $request)
    {
        $definition = $this->getDefinitionForRoute($route);
        if (!$definition) {
            return null;
        }

        /**
         * Check authorization roles
         * If accessControl filter does not exist, ignore authorization check
         */
        $accessControlFilter = $this->getFilter('accessControl');
        if (!empty($accessControlFilter)) {
            $roles = RouteParser::getAnnotationValue(RouteParser::AUTH, $definition);
            if (count($roles)) {
                $authorized = call_user_func_array($accessControlFilter, [$roles]);

                if (!$authorized) {
                    throw new SecurityException(SecurityException::AUTHENTICATION_FAILURE);
                }
            }
        }

        // check for HTTPS-only requirements (with @secure annotation)
        $allowed = $this->isOnlyHttpsAllowed($definition, $request);
        if (!$allowed) {
            throw new SecurityException(SecurityException::HTTPS_ONLY);
        }
    }

    public function afterFilter(Route $route, \Illuminate\Http\Request $request, \Illuminate\Http\Response $response)
    {
        $definition = $this->getDefinitionForRoute($route);
        if (!$definition) {
            return null;
        }

        // check for view options
        $view = $this->getTemplate($definition, $request, $response);
        if ($view) {
            $response->setContent($view);
        }
    }

    protected function createRoute($method, $pattern, $action, AnnotatedDefinition $definition = null)
    {
        $route = parent::createRoute($method, $pattern, $action);
        $this->addDefinitionMapping($route, $definition);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return Route
     */
    protected function findRoute(\Symfony\Component\HttpFoundation\Request $request)
    {
        $this->addAnnotatedRoutesToStack();

        return parent::findRoute($request);
    }

    /**
     * @param $instance
     *
     * @return void
     */
    public function addController($instance)
    {
        if (!$instance) {
            return;
        }

        if (!is_object($instance)) {
            // if this is not an object, assume it is a class name
            $class = $instance;
            if (!class_exists($class)) {
                return;
            }

            // create a new instance
            $class    = new ReflectionClass($class);
            $instance = $class->newInstance();
        }

        if (!is_array($this->controllers)) {
            $this->controllers = array();
        }

        if (!in_array($instance, $this->controllers, true)) {
            $this->controllers[] = $instance;
        }
    }

    /**
     * @return Parser
     */
    public function getParser()
    {
        return $this->parser;
    }

    /**
     * Create native Laravel routes from parsed controllers' annotations
     */
    private function addAnnotatedRoutesToStack()
    {
        if (!$this->parser) {
            return;
        }

        // parse the controllers before attempting to find the route
        $this->parser->setMethodFilter($this->getAccessFilter());
        $this->parser->analyze($this->controllers);

        // get the routes' data from the route parser and create the native routes
        $routes = RouteParser::createRoutesData($this);
        if (!count($routes)) {
            return null;
        }

        foreach ($routes as $route) {
            list($method, $pattern, $action, $definition) = $route;

            // create the native route
            $this->createRoute($method, $pattern, $action, $definition);
        }
    }

    /**
     * Return a value determining which access modifiers should be considered
     *
     * @return array|int|null
     * @throws \Exception
     */
    private function getAccessFilter()
    {
        $access     = Config::get('spore.access');
        $acceptable = array('public', 'protected', 'private');

        if (!count($access)) {
            $access = array('public');
        }

        $invalid = array_diff(array_merge($access, $acceptable), $acceptable);
        if (!empty($invalid)) {
            throw new Exception(sprintf('The following access options are invalid: %s', implode(', ', $invalid)));
        }

        $filters = array();
        foreach ($access as $modifier) {
            $filter = null;
            switch ($modifier) {
                case 'public':
                    $filter = ReflectionMethod::IS_PUBLIC;
                    break;
                case 'protected':
                    $filter = ReflectionMethod::IS_PROTECTED;
                    break;
                case 'private':
                    $filter = ReflectionMethod::IS_PRIVATE;
                    break;
            }

            if (empty($filter)) {
                continue;
            }

            $filters |= $filter;
        }

        return $filters;
    }

    private function addDefinitionMapping(Route $route, AnnotatedDefinition $definition = null)
    {
        if (!$this->definitionMap) {
            $this->definitionMap = array();
        }

        if (!$route || !$definition) {
            return null;
        }

        $this->definitionMap[spl_object_hash($route)] = $definition;
    }

    private function getDefinitionForRoute(Route $route)
    {
        if (!$route) {
            return null;
        }

        $index = spl_object_hash($route);
        return isset($this->definitionMap[$index]) ? $this->definitionMap[$index] : null;
    }

    private function isOnlyHttpsAllowed(
        AnnotatedDefinition $definition,
        \Symfony\Component\HttpFoundation\Request $request
    ) {
        // if no definition is passed, stay on the safe side and prevent access
        // as this may have been an internal bug and it shouldn't compromise security
        if (!$definition) {
            return false;
        }

        $secure = RouteParser::getAnnotationValue(RouteParser::SECURE, $definition);
        if (!$secure) {
            return true;
        }

        return $secure && $request->isSecure();
    }

    private function getTemplate(
        AnnotatedDefinition $definition,
        \Symfony\Component\HttpFoundation\Request $request,
        \Illuminate\Http\Response $response
    ) {
        if (!$definition) {
            return null;
        }

        $data = $response->getOriginalContent();

        $template   = RouteParser::getAnnotationValue(RouteParser::TEMPLATE, $definition);
        $returnJSON = RouteParser::getAnnotationValue(RouteParser::JSON, $definition);

        if ($template) {
            $renderMode = RouteParser::getAnnotationValue(RouteParser::RENDER, $definition);
            $isAjax     = $request->isXmlHttpRequest();

            // if browser request or set to always render
            if (($renderMode == 'browser' && !$isAjax) || $renderMode == 'always') {
                $response->headers->set('Content-Type', 'text/html; charset=UTF-8');
                return View::make($template)->with('data', $data);
            }
        }

        if ($returnJSON !== false) {
            $response->headers->set('Content-Type', 'application/json; charset=UTF-8');
            return json_encode($data);
        }
    }
}