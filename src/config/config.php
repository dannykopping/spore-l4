<?php
return array(
    'annotations' => array(

        /**
         * The path for the route
         */
        'uri'      => 'uri',
        /**
         * The HTTP verbs that will be accepted
         */
        'verbs'    => 'verbs',
        /**
         * The base path of a controller
         */
        'base'     => 'base',
        /**
         * The template to be used
         */
        'template' => 'template',
        /**
         * Whether to render the defined template
         */
        'render'   => 'render',
        /**
         * Whether to force HTTPS-only access
         * @link    http://laravel.com/docs/routing#basic-routing
         */
        'secure'   => 'secure',
        /**
         * Route alias
         * @link    http://laravel.com/docs/routing#named-routes
         */
        'name'   => 'name',
    ),
    /**
     * public, private and protected methods can be addressed
     * By default, Spore will only address public methods
     */
    'access'      => array(
        'public',
    )
);