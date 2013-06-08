<?php
namespace Infomaniac\Spore\Illuminate\Routing;

use DocBlock\Parser;
use Exception;
use Illuminate\Container\Container;
use Illuminate\Routing\Router as BaseRouter;
use Illuminate\Support\Facades\Config;
use Infomaniac\Spore\Annotation\RouteParser;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Request;

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

    public function __construct(Container $container = null)
    {
        $this->parser = new Parser();

        return parent::__construct($container);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Routing\Route
     */
    protected function findRoute(Request $request)
    {
        $this->addAnnotatedRoutesToStack();

        return parent::findRoute($request);
    }

    /**
     * @param $instance
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
            list($method, $pattern, $action) = $route;

            // create the native route
            $this->createRoute($method, $pattern, $action);
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
}