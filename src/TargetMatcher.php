<?php

declare(strict_types=1);

namespace Contenir\Preview\Laminas\Mvc;

use Contenir\Preview\InvalidPreview;
use Contenir\Preview\PreviewConfig;
use Contenir\Preview\Resolver\PreviewTarget;
use Laminas\Http\Request;
use Laminas\Router\Exception\ExceptionInterface as RouterException;
use Laminas\Router\Http\Literal;
use Laminas\Router\RouteStackInterface;

use function is_string;

use const PHP_INT_MAX;

/**
 * Sets the request up so the application routes it to the page a resolver names, as it would a
 * visit, and every route listener (the site's own among them) runs as it would then. A path, or a
 * named route assembled to one, becomes the request's address, so the page sees its own URL; the
 * router must have a page there. A handler, which no route reaches, gets a route of its own at the
 * preview endpoint for this request only, dispatching it with its parameters.
 */
final readonly class TargetMatcher
{
    /** The name of the route a handler is dispatched through */
    public const string HANDLER_ROUTE = 'contenir-preview';

    /**
     * @throws InvalidPreview When the site has no page there
     */
    public function prepare(PreviewTarget $target, RouteStackInterface $router, Request $request): void
    {
        if (null !== $target->handler) {
            $router->addRoute(
                self::HANDLER_ROUTE,
                Literal::factory([
                    'route'    => PreviewConfig::ENDPOINT,
                    'defaults' => [...$target->params, 'controller' => $target->handler],
                ]),
                PHP_INT_MAX,
            );

            return;
        }

        $request->getUri()->setPath($this->path($target, $router, $request));
        if (null === $router->match($request)) {
            throw InvalidPreview::notPreviewable('The site has no page at this record\'s address.');
        }
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
