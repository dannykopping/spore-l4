<?php

use Illuminate\Support\Facades\Response;

/**
 * @base                /secure
 * @secure
 */
class SecureController
{
    /**
     * @uri             /hello
     * @verbs           ANY
     *
     * @return string
     */
    public function hello()
    {
        return "secure world";
    }
}