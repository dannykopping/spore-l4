<?php
namespace Infomaniac\Spore\Annotation;

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

    const ALL_VERBS = 'get|post|put|patch|delete';

    private static $defaults = array(
        self::URI      => 'uri',
        self::VERBS    => 'verbs',
        self::BASE     => 'base',
        self::TEMPLATE => 'template',
        self::RENDER   => 'render',
    );

    /**
     * Create native Laravel routes from parsed controllers' annotations
     *
     * @param Router $router
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
     * @return array
     */
    private static function addAnnotatedRouteByMethod(MethodElement $method)
    {
        $definition = new AnnotatedDefinition($method);

        // if the definition has no local URI, ignore it
        if (!$definition->getLocalPath()) {
            return null;
        }

        $fullPath        = $definition->getFullPath();
        $verbs           = null;
        $verbsAnnotation = $method->getAnnotation(self::getAnnotationIdentifier(self::VERBS));
        if ($verbsAnnotation) {
            $verbs = $verbsAnnotation->getValue();
            if (empty($verbs)) {
                $verbs = self::ALL_VERBS;
            } else {
                $verbs = strtolower(implode('|', explode(',', $verbs)));
            }
        }

        // create a new instance of the class if one does not exist already
        $class    = $definition->getClass();
        $instance = $class->getInstance();
        $instance = $instance ? $instance : $class->getReflectionObject()->newInstance();

        return array(
            $verbs,
            $fullPath,
            array(
                'after'      => 'bob',
                'definition' => $definition,
                $method->getReflectionObject()->getClosure($instance)
            )
        );
    }

    public static function getAnnotationIdentifier($type)
    {
        $default = isset(self::$defaults[$type]) ? self::$defaults[$type] : null;
        return Config::get('spore.annotations.' . $type, $default);
    }
}