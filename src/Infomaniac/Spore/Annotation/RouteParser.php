<?php
namespace Infomaniac\Spore\Annotation;

use DocBlock\Element\AnnotationElement;
use DocBlock\Element\MethodElement;
use DocBlock\Parser;
use Illuminate\Support\Facades\Config;
use Infomaniac\Spore\Illuminate\Routing\Router;

/**
 * This class obtains the parsed controllers from the Router
 * and - in turn - creates native Laravel routes from the
 * annotated definitions in given classes
 *
 * Class RouteParser
 */
class RouteParser
{
    /**
     * @var     Router
     */
    private static $router;
    /**
     * @var     Parser
     */
    private static $parser;

    const URI      = 'uri';
    const VERBS    = 'verbs';
    const BASE     = 'base';
    const TEMPLATE = 'template';
    const RENDER   = 'render';
    const SECURE   = 'secure';
    const NAME     = 'name';
    const JSON     = 'json';
    const AUTH     = 'auth';

    const ALL_VERBS = 'get|post|put|patch|delete';

    private static $defaults = array(
        self::URI      => 'uri',
        self::VERBS    => 'verbs',
        self::BASE     => 'base',
        self::TEMPLATE => 'template',
        self::RENDER   => 'render',
        self::SECURE   => 'secure',
        self::NAME     => 'name',
        self::JSON     => 'json',
        self::AUTH     => 'auth',
    );

    /**
     * Create native Laravel routes from parsed controllers' annotations
     *
     * @param Router $router
     *
     * @return array|null
     */
    public static function createRoutesData(Router $router)
    {
        self::$router = $router;
        self::$parser = $router->getParser();

        $classes = self::$parser->getClasses();
        if (!count($classes)) {
            return null;
        }

        $routesData = array();
        foreach ($classes as $class) {
            $methods = $class->getMethods();
            if (!count($methods)) {
                continue;
            }

            foreach ($methods as $method) {
                // methods with no annotations will be ignored
                if (!count($method->getAnnotations())) {
                    continue;
                }

                $data = self::addAnnotatedRouteByMethod($method);
                if (!$data) {
                    continue;
                }

                $routesData[] = $data;
            }
        }

        return $routesData;
    }

    /**
     * @param MethodElement $method
     *
     * @return array
     */
    private static function addAnnotatedRouteByMethod(MethodElement $method)
    {
        $definition = new AnnotatedDefinition($method);

        $uri = self::getAnnotationValue(self::URI, $definition);
        if (!$uri) {
            return null;
        }

        $verbs = self::getAnnotationValue(self::VERBS, $definition);
        $name  = self::getAnnotationValue(self::NAME, $definition);

        // create a new instance of the class if one does not exist already
        $class    = $definition->getClass();
        $instance = $class->getInstance();
        $instance = $instance ? $instance : $class->getReflectionObject()->newInstance();

        return array(
            $verbs,
            $uri,
            array(
                'before' => 'beforeAnnotatedRoute',
                'after'  => 'afterAnnotatedRoute',
                'as'     => $name,
                $method->getReflectionObject()->getClosure($instance)
            ),
            $definition
        );
    }

    public static function getAnnotationValue($type, AnnotatedDefinition $definition, $inherit = true)
    {
        $identifier  = self::getAnnotationIdentifier($type);
        $classValue  = $definition->getClass()->getAnnotation($identifier);
        $methodValue = $definition->getMethod()->getAnnotation($identifier);
        $value       = null;

        if ($inherit && $classValue && !$methodValue) {
            // if inherit is true,
            // a class-level annotation exists and no method annotation was defined,
            // use the class-level value
            $value = $classValue;
        } else {
            // otherwise, use the value localized to the requested route callback method
            $value = $methodValue;
        }

        $callback = '';
        switch ($type) {
            case self::URI:
                $callback = 'getRouteURI';
                break;
            case self::VERBS:
                $callback = 'getVerbs';
                break;
            case self::SECURE:
                $callback = 'getSecure';
                break;
            case self::NAME:
                $callback = 'getRouteAlias';
                break;
            case self::TEMPLATE:
                $callback = 'getTemplate';
                break;
            case self::RENDER:
                $callback = 'getRenderMode';
                break;
            case self::JSON:
                $callback = 'getRenderJSON';
                break;
            case self::AUTH:
                $callback = 'getRoles';
                break;
            default:
                throw new \Exception('No handler for annotation type "' . $type . '"');
                break;
        }

        if (!method_exists(__CLASS__, $callback)) {
            return null;
        }

        return call_user_func_array(
            __CLASS__ . '::' . $callback,
            array($definition, $value)
        );
    }

    private static function getRouteURI(AnnotatedDefinition $definition, AnnotationElement $value = null)
    {
        // if the definition has no local URI, ignore it
        if (!$definition->getLocalPath()) {
            return null;
        }

        $fullPath = $definition->getFullPath();
        return $fullPath;
    }

    private static function getVerbs(AnnotatedDefinition $definition, AnnotationElement $value = null)
    {
        if ($value) {
            $verbs = $value->getValue();
            if (empty($verbs) || strtolower($verbs) == 'any') {
                $verbs = self::ALL_VERBS;
            } else {
                $verbs = strtolower(implode('|', explode(',', $verbs)));
            }
        } else {
            $verbs = self::ALL_VERBS;
        }

        return $verbs;
    }

    private static function getSecure(AnnotatedDefinition $definition, AnnotationElement $value = null)
    {
        // if a "@secure" annotation exists, assume it to be true
        // its absense will be construed as false

        if (!$value) {
            return false;
        }

        return true;
    }

    private static function getRouteAlias(AnnotatedDefinition $definition, AnnotationElement $value = null)
    {
        if (!$value) {
            return null;
        }

        return $value->getValue();
    }

    private static function getTemplate(AnnotatedDefinition $definition, AnnotationElement $value = null)
    {
        if (!$value) {
            return null;
        }

        return $value->getValue();
    }

    private static function getRenderMode(AnnotatedDefinition $definition, AnnotationElement $value = null)
    {
        if (!$value) {
            return null;
        }

        $renderMode = $value->getValue();
        $acceptable = array('always', 'browser', 'never');
        if (!in_array(strtolower($renderMode), $acceptable)) {
            throw new \Exception('Unacceptable value for @render option "' . $renderMode . '"');
        }

        return $renderMode;
    }

    private static function getRenderJSON(AnnotatedDefinition $definition, AnnotationElement $value = null)
    {
        if (!$value) {
            return null;
        }

        return (bool) $value->getValue() === false;
    }

    private static function getRoles(AnnotatedDefinition $definition, AnnotationElement $value = null)
    {
        if (!$value) {
            return null;
        }

        return explode(',', $value->getValue());
    }

    public static function getAnnotationIdentifier($type)
    {
        $default = isset(self::$defaults[$type]) ? self::$defaults[$type] : null;
        return Config::get('spore.annotations.' . $type, $default);
    }
}