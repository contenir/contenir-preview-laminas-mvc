<?php

declare(strict_types=1);

namespace Contenir\Preview\Laminas\Mvc;

use Contenir\Preview\InvalidPreview;
use Contenir\Preview\PreviewConfig;
use Contenir\Preview\PreviewContext;
use Contenir\Preview\PreviewEntry;
use Contenir\Preview\PreviewResponse;
use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Http\Header\HeaderInterface;
use Laminas\Http\Headers;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;
use Laminas\Stdlib\Parameters;
use Override;

use function is_string;
use function str_contains;
use function strcasecmp;

/**
 * Answers the CMS's previews in a Laminas MVC application (§4 to §6 of the protocol).
 *
 * Before the router runs, a post to /api/resource/preview is opened: its token verified, the page
 * that shows the record resolved, and the preview context begun. The request then becomes a plain
 * visit to that page, which the application routes and renders as it always would, while the
 * site's data layer lays the preview's values over the record. A refusal is answered at once with
 * the protocol's status and a short page saying why. As the response leaves, a preview gets the
 * protocol's headers and, unless configured away, a visible "Preview" badge.
 */
final class PreviewListener extends AbstractListenerAggregate
{
    /** Runs before the router (RouteListener listens at 1). */
    public const int PRIORITY = 100;

    public function __construct(
        private readonly PreviewEntry $entry,
        private readonly TargetMatcher $matcher,
        private readonly PreviewContext $context,
        private readonly PreviewResponse $response,
        private readonly bool $marker,
    ) {}

    /**
     * @param int $priority
     */
    #[Override]
    public function attach(EventManagerInterface $events, $priority = self::PRIORITY): void
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_ROUTE, $this->onRoute(...), $priority);
        $this->listeners[] = $events->attach(MvcEvent::EVENT_FINISH, $this->onFinish(...), $priority);
    }

    public function onFinish(MvcEvent $event): void
    {
        $response = $event->getResponse();
        if (! $this->context->isActive() || ! $response instanceof Response) {
            return;
        }

        $this->withPreviewHeaders($response);

        $type = $response->getHeaders()->get('Content-Type');
        if ($this->marker && (! $type instanceof HeaderInterface || str_contains($type->getFieldValue(), 'html'))) {
            $response->setContent(PreviewMarker::mark($response->getBody()));
        }
    }

    public function onRoute(MvcEvent $event): ?Response
    {
        $request = $event->getRequest();
        $router  = $event->getRouter();
        if (! $request instanceof Request || ! $this->isEndpoint($request)) {
            return null;
        }

        try {
            /** @var mixed $token */
            $token  = $request->getPost(PreviewEntry::FIELD);
            $target = $this->entry->open($request->getMethod(), is_string($token) ? $token : null);

            $request->setMethod(Request::METHOD_GET);
            $request->setPost(new Parameters());
            $match = $this->matcher->match($target, $router, $request);
        } catch (InvalidPreview $refusal) {
            return $this->refuse($event, $refusal);
        }

        $event->setRouteMatch($match);
        $event->stopPropagation(true);

        return null;
    }

    private function isEndpoint(Request $request): bool
    {
        return $request->getUri()->getPath() === BaseUrl::of($request) . PreviewConfig::ENDPOINT;
    }

    /**
     * @return list<HeaderInterface>
     */
    private function named(Headers $headers, string $name): array
    {
        $named = [];
        /** @var mixed $header */
        foreach ($headers as $header) {
            if (! $header instanceof HeaderInterface || 0 !== strcasecmp($header->getFieldName(), $name)) {
                continue;
            }
            $named[] = $header;
        }

        return $named;
    }

    private function refuse(MvcEvent $event, InvalidPreview $refusal): Response
    {
        $response = new Response();
        $response->setStatusCode($refusal->status);
        $response->setContent($this->response->refusal($refusal));
        $response->getHeaders()->addHeaderLine('Content-Type', 'text/html; charset=utf-8');
        $this->withPreviewHeaders($response);
        $event->setResponse($response);

        return $response;
    }

    private function withPreviewHeaders(Response $response): void
    {
        $headers = $response->getHeaders();
        foreach ($this->response->headers() as $name => $value) {
            foreach ($this->named($headers, $name) as $header) {
                $headers->removeHeader($header);
            }
            $headers->addHeaderLine($name, $value);
        }
    }
}
