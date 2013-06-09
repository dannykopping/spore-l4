<?php

use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;

class TestController
{
    /**
     * @uri             /hello
     * @verbs           GET
     *
     * @return string
     */
    public function hello()
    {
        return "world";
    }

    /**
     * @uri             /unauthorized
     * @verbs           GET
     *
     * @return string
     */
    public function unauthorized()
    {
        return Response::make(null, 403);
    }

    /**
     * @uri             /hello/{name}
     * @verbs           GET
     *
     * @param $name
     *
     * @return string
     */
    public function helloWithParams($name)
    {
        return $name;
    }

    /**
     * @uri             /any-verb
     */
    public function anyVerb()
    {
        return null;
    }

    /**
     * @uri             /custom-verb
     * @verbs           MY-FANCY-VERB
     */
    public function customVerb()
    {
        return null;
    }

    /**
     * @uri             /named-route
     * @name            named
     * @verbs           GET
     *
     * @return string
     */
    public function namedRoute()
    {
        return URL::to('named');
    }

    /**
     * @uri                 /view-browser/{title}
     * @verbs               GET
     * @template            spore::test
     * @render              browser
     */
    public function getBrowserRenderView($title = null)
    {
        return $this->pageData($title);
    }

    /**
     * @uri                 /view-always/{title}
     * @verbs               GET
     * @template            spore::test
     * @render              always
     */
    public function getAlwaysRenderView($title = null)
    {
        return $this->pageData($title);
    }

    /**
     * @uri                 /view-never/{title}
     * @verbs               GET
     * @template            spore::test
     * @render              never
     */
    public function getNeverRenderView($title = null)
    {
        return $this->pageData($title);
    }

    private function pageData($title = null)
    {
        return array(
            'page' => array(
                'title' => $title
            )
        );
    }
}