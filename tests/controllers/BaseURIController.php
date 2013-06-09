<?php

use Illuminate\Support\Facades\Response;

/**
 * @base                /base-uri
 */
class BaseURIController
{
    /**
     * @uri             /hello
     * @verbs           ANY
     *
     * @return string
     */
    public function hello()
    {
        return "base world";
    }
}

/**
 * @base                /base-uri/{someParam}
 */
class BaseURIWithParamController
{
    /**
     * @uri             /hello
     * @verbs           ANY
     *
     * @param $someParam
     *
     * @return string
     */
    public function hello($someParam)
    {
        return "base $someParam world";
    }
}