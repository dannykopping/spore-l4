<?php
namespace Infomaniac\Spore\Annotation;

use DocBlock\Element\MethodElement;

/**
 * Class AnnotatedDefinition
 */
class AnnotatedDefinition
{
    /**
     * @var \DocBlock\Element\MethodElement
     */
    private $definition;

    /**
     * @param MethodElement $definition
     */
    public function __construct(MethodElement $definition)
    {
        $this->definition = $definition;
    }

    /**
     * @return \DocBlock\Element\MethodElement|null
     */
    public function getMethod()
    {
        return $this->definition;
    }

    /**
     * @return \DocBlock\Element\ClassElement|null
     */
    public function getClass()
    {
        return empty($this->definition) ? null : $this->definition->getClass();
    }

    /**
     * Get the URI definition
     *
     * @return mixed|null
     */
    public function getLocalPath()
    {
        if (!$this->getMethod()) {
            return null;
        }

        $uriIdentifier = RouteParser::getAnnotationIdentifier(RouteParser::URI);

        $uriAnnotation = $this->getMethod()->getAnnotation($uriIdentifier);
        if (!$uriAnnotation) {
            return null;
        }

        if (!$uriAnnotation->getValue()) {
            return null;
        }

        return $this->sanitizePath($uriAnnotation->getValue());
    }

    /**
     * Get the full URI definition which includes the parent's base path and this definition's local path
     */
    public function getFullPath()
    {
        if (!$this->getClass()) {
            return null;
        }

        $baseIdentifier = RouteParser::getAnnotationIdentifier(RouteParser::BASE);

        $baseAnnotation = $this->getClass()->getAnnotation($baseIdentifier);
        $base           = null;
        if ($baseAnnotation) {
            $base = $this->sanitizePath($baseAnnotation->getValue());
        }

        $local = $this->getLocalPath();
        return $base . $local;
    }

    private function sanitizePath($path)
    {
        // if path is empty, return null - don't correct to /
        if (empty($path)) {
            return null;
        }

        // if there is a missing leading forward slash, add one
        if (substr($path, 0, 1) != '/') {
            $path = '/' . $path;
        }

        // conversely, if there is a trailing slash, zap it
        if (substr($path, strlen($path) - 1) == '/') {
            $path = substr($path, 0, strlen($path) - 1);
        }

        return $path;
    }
}