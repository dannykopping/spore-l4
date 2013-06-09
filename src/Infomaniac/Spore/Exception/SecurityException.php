<?php
namespace Infomaniac\Spore\Exception;

use Exception;

class SecurityException extends Exception
{
    const HTTPS_ONLY = 'Route is only accessible via HTTPS';
    const AUTHENTICATION_FAILURE = 'Authentication failure';
}