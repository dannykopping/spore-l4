<?php

use Illuminate\Support\Facades\Response;

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
}