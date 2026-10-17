<?php

declare(strict_types=1);

namespace Contenir\Preview\Laminas\Mvc;

use Laminas\Http\PhpEnvironment\Request as EnvironmentRequest;
use Laminas\Http\Request;

/**
 * Where the application lives below its host, as the router sees it: the request's base URL for
 * an application in a subdirectory, and none otherwise.
 *
 * @internal
 */
final class BaseUrl
{
    public static function of(Request $request): string
    {
        return $request instanceof EnvironmentRequest ? $request->getBaseUrl() : '';
    }
}
