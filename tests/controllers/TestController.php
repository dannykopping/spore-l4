<?php

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
}