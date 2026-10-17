<?php

declare(strict_types=1);

namespace Contenir\Preview\Laminas\Mvc;

use Contenir\Preview\InvalidPreview;
use Contenir\Preview\Resolver\PreviewTarget;
use Laminas\Http\Request;
use Laminas\Router\Exception\ExceptionInterface as RouterException;
use Laminas\Router\RouteMatch;
use Laminas\Router\RouteStackInterface;

use function is_string;

/**
 * Turns the page a resolver names into the route match the application dispatches. A path is
 * matched by the router as a visit to it would be; a named route is assembled and matched the same
 * way; a handler is dispatched as the controller it names, with its parameters, and no route.
 * Matching a path or route moves the request to it, so the page sees its own address.
 */
final readonly class TargetMatcher
{
    /**
     * @throws InvalidPreview When the site has no page there
     */
    public function match(PreviewTarget $target, RouteStackInterface $router, Request $request): RouteMatch
    {
        if (null !== $target->handler) {
            return new RouteMatch([...$target->params, 'controller' => $target->handler]);
        }

        $request->getUri()->setPath($this->path($target, $router, $request));

        return (
            $router->match($request) ?? throw InvalidPreview::notPreviewable(
                'The site has no page at this record\'s address.',
            )
        );
    }

    /**
     * @throws InvalidPreview
     */
    private function path(PreviewTarget $target, RouteStackInterface $router, Request $request): string
    {
        if (null !== $target->path) {
            return BaseUrl::of($request) . $target->path;
        }

        try {
            /** @var mixed $path */
            $path = $router->assemble($target->params, ['name' => $target->route]);
        } catch (RouterException) {
            throw InvalidPreview::notPreviewable('The site has no route for this record.');
        }

        return is_string($path)
            ? $path
            : throw InvalidPreview::notPreviewable('The site has no route for this record.');
    }
}
