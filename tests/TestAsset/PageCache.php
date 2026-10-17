<?php

declare(strict_types=1);

namespace Contenir\Preview\Laminas\Mvc\Tests\TestAsset;

use Contenir\Preview\Laminas\Mvc\PreviewListener;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\Http\Header\HeaderInterface;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\Application;
use Laminas\Mvc\MvcEvent;

use function array_key_exists;
use function str_contains;

/**
 * A site's page cache, in memory, keyed on a GET to a path the way a Laminas MVC page cache is:
 * after dispatch a stored page answers in place of the one rendered, and as the response leaves a
 * page rendered afresh is stored. It leaves a request alone when told to in one of the two ways a
 * page cache might listen for: the `pagecache.disable` event, or the request's Cache-Control.
 */
final class PageCache
{
    /** @var array<string, string> */
    public array $pages = [];

    private bool $disabled = false;

    private ?string $key = null;

    private function __construct(
        private readonly bool $listensForDisable,
    ) {}

    /**
     * A cache that stands aside when the application triggers `pagecache.disable`, and pays no
     * attention to Cache-Control.
     */
    public static function listeningForDisable(): self
    {
        return new self(listensForDisable: true);
    }

    /**
     * A cache that serves no stored page to a request with `no-cache` and stores nothing for one
     * with `no-store`, and knows nothing of `pagecache.disable`.
     */
    public static function readingCacheControl(): self
    {
        return new self(listensForDisable: false);
    }

    public function attach(SharedEventManagerInterface $events): void
    {
        $events->attach(Application::class, MvcEvent::EVENT_DISPATCH, $this->onDispatch(...), -100);
        $events->attach(Application::class, MvcEvent::EVENT_FINISH, $this->onFinish(...), 100);
        if ($this->listensForDisable) {
            $events->attach(Application::class, PreviewListener::CACHE_DISABLE_EVENT, $this->disable(...), 100);
        }
    }

    public function disable(): void
    {
        $this->disabled = true;
    }

    public function onDispatch(MvcEvent $event): ?Response
    {
        $request  = $event->getRequest();
        $response = $event->getResponse();
        if ($this->disabled || ! $request instanceof Request || ! $request->isGet()) {
            return null;
        }

        $key = $request->getUri()->getPath();
        if (
            ! $response instanceof Response
            || ! array_key_exists($key, $this->pages)
            || $this->directs($request, 'no-cache')
        ) {
            $this->key = $key;

            return null;
        }

        return $response->setContent($this->pages[$key]);
    }

    public function onFinish(MvcEvent $event): void
    {
        $request  = $event->getRequest();
        $response = $event->getResponse();
        if (
            $this->disabled
            || null === $this->key
            || ! $request instanceof Request
            || $this->directs($request, 'no-store')
            || ! $response instanceof Response
            || 200 !== $response->getStatusCode()
        ) {
            return;
        }

        $this->pages[$this->key] = $response->getBody();
    }

    private function directs(Request $request, string $directive): bool
    {
        $control = $request->getHeaders()->get('Cache-Control');

        return (
            ! $this->listensForDisable
                && $control instanceof HeaderInterface
                && str_contains($control->getFieldValue(), $directive)
        );
    }
}
