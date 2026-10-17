<?php

declare(strict_types=1);

namespace Contenir\Preview\Laminas\Mvc\Tests\Unit;

use Contenir\Preview\Laminas\Mvc\PreviewListener;
use Contenir\Preview\Laminas\Mvc\PreviewMarker;
use Contenir\Preview\Laminas\Mvc\TargetMatcher;
use Contenir\Preview\Laminas\Mvc\Tests\TestAsset\FrozenClock;
use Contenir\Preview\Laminas\Mvc\Tests\TestAsset\Previews;
use Contenir\Preview\PreviewContext;
use Contenir\Preview\PreviewEntry;
use Contenir\Preview\PreviewResponse;
use Contenir\Preview\Replay\InMemoryReplayGuard;
use Contenir\Preview\Resolver\PathResolver;
use Contenir\Preview\Resolver\ResolverRegistry;
use Contenir\Preview\Token\TokenVerifier;
use Laminas\EventManager\EventManager;
use Laminas\EventManager\Test\EventListenerIntrospectionTrait;
use Laminas\Http\Header\HeaderInterface;
use Laminas\Http\PhpEnvironment\Request as EnvironmentRequest;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\RouteMatch;
use Laminas\Router\RouteStackInterface;
use Laminas\Stdlib\Parameters;
use Laminas\Stdlib\Request as PlainRequest;
use Laminas\Stdlib\Response as PlainResponse;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_keys;
use function iterator_to_array;
use function str_contains;

/**
 * A post to the preview endpoint opened before the router runs, and a preview's response marked
 * as it leaves.
 */
#[Group('unit')]
final class PreviewListenerTest extends TestCase
{
    use EventListenerIntrospectionTrait;

    #[Test]
    public function addsThePreviewHeadersInPlaceOfThePagesOwn(): void
    {
        $event = $this->finished('<html><body>Spa Rituals</body></html>');
        $event->getResponse()->getHeaders()->addHeaderLine('Content-Language', 'en');
        $event->getResponse()->getHeaders()->addHeaderLine('content-security-policy', "frame-ancestors 'none'");

        $this->listener($this->previewing())->onFinish($event);

        $lines = [];
        /** @var HeaderInterface $header */
        foreach ($this->response($event)->getHeaders() as $header) {
            $lines[] = $header->toString();
        }
        $this->assertSame(
            [
                'Content-Language: en',
                'Cache-Control: no-store, private',
                'X-Robots-Tag: noindex, nofollow',
                'Referrer-Policy: no-referrer',
                'Content-Security-Policy: frame-ancestors ' . Previews::ISSUER . ';',
            ],
            $lines,
        );
    }

    #[Test]
    public function answersAMissingTokenAsMalformed(): void
    {
        $event = $this->posted(new Parameters(['contenir_preview' => ['not', 'a', 'token']]));

        $response = $this->listener(new PreviewContext())->onRoute($event);

        $this->assertSame(400, $response?->getStatusCode());
    }

    #[Test]
    public function answersARefusalWithItsStatusAndAPageSayingWhy(): void
    {
        $event = $this->posted(new Parameters(['contenir_preview' => Previews::token(new FrozenClock())]));
        $event->getRequest()->setMethod(Request::METHOD_PUT);

        $response = $this->listener(new PreviewContext())->onRoute($event);

        $this->assertSame(
            [405, true, true, 'no-store, private', 'text/html; charset=utf-8', $response],
            [
                $response?->getStatusCode(),
                str_contains((string) $response?->getContent(), '<html'),
                null === $event->getRouteMatch(),
                $response?->getHeaders()->get('Cache-Control')->getFieldValue(),
                $response?->getHeaders()->get('Content-Type')->getFieldValue(),
                $event->getResponse(),
            ],
        );
    }

    #[Test]
    public function attachesBeforeTheRouterAndOnFinishing(): void
    {
        $events = new EventManager();

        $this->listener(new PreviewContext())->attach($events);

        $this->assertSame(
            [[100], [100]],
            [
                array_keys(iterator_to_array($this->getListenersForEvent(
                    MvcEvent::EVENT_ROUTE,
                    $events,
                    withPriority: true,
                ))),
                array_keys(iterator_to_array($this->getListenersForEvent(
                    MvcEvent::EVENT_FINISH,
                    $events,
                    withPriority: true,
                ))),
            ],
        );
    }

    #[Test]
    public function leavesAnAnswerThatIsNotHtmlUnmarked(): void
    {
        $event = $this->finished('{"body":"</body>"}');
        $event->getResponse()->getHeaders()->addHeaderLine('Content-Type', 'application/json');

        $this->listener($this->previewing())->onFinish($event);

        $this->assertSame(
            ['{"body":"</body>"}', 'noindex, nofollow'],
            [
                $this->response($event)->getContent(),
                $this->response($event)->getHeaders()->get('X-Robots-Tag')->getFieldValue(),
            ],
        );
    }

    #[Test]
    public function leavesAnotherAddressToTheRouter(): void
    {
        $event = $this->posted(new Parameters(['contenir_preview' => Previews::token(new FrozenClock())]));
        $event->getRequest()->setUri('https://www.example.test/api/resource/preview/other');

        $response = $this->listener(new PreviewContext())->onRoute($event);

        $this->assertSame([null, null, false], [$response, $event->getRouteMatch(), $event->propagationIsStopped()]);
    }

    #[Test]
    public function leavesARequestThatIsNotHttpAlone(): void
    {
        $event = new MvcEvent();
        $event->setRequest(new PlainRequest());
        $event->setRouter($this->createStub(RouteStackInterface::class));

        $this->assertNull($this->listener(new PreviewContext())->onRoute($event));
    }

    #[Test]
    public function leavesAResponseThatIsNotHttpAlone(): void
    {
        $event    = new MvcEvent();
        $response = new PlainResponse();
        $response->setContent('<body></body>');
        $event->setResponse($response);

        $this->listener($this->previewing())->onFinish($event);

        $this->assertSame('<body></body>', $response->getContent());
    }

    #[Test]
    public function leavesAVisitAlone(): void
    {
        $event = $this->finished('<html><body>Spa Rituals</body></html>');

        $this->listener(new PreviewContext())->onFinish($event);

        $this->assertSame(
            ['<html><body>Spa Rituals</body></html>', false],
            [$this->response($event)->getContent(), $this->response($event)->getHeaders()->has('X-Robots-Tag')],
        );
    }

    #[Test]
    public function leavesTheBadgeOffWhenConfiguredTo(): void
    {
        $event = $this->finished('<html><body>Spa Rituals</body></html>');

        $this->listener($this->previewing(), marker: false)->onFinish($event);

        $this->assertSame('<html><body>Spa Rituals</body></html>', $this->response($event)->getContent());
    }

    #[Test]
    public function marksAnHtmlPage(): void
    {
        $event = $this->finished('<html><body>Spa Rituals</body></html>');
        $event->getResponse()->getHeaders()->addHeaderLine('Content-Type', 'text/html; charset=utf-8');

        $this->listener($this->previewing())->onFinish($event);

        $this->assertSame(
            '<html><body>Spa Rituals' . PreviewMarker::HTML . '</body></html>',
            $this->response($event)->getContent(),
        );
    }

    #[Test]
    public function marksAPageThatDoesNotSayWhatItIs(): void
    {
        $event = $this->finished('<html><body>Spa Rituals</body></html>');

        $this->listener($this->previewing())->onFinish($event);

        $this->assertSame(
            '<html><body>Spa Rituals' . PreviewMarker::HTML . '</body></html>',
            $this->response($event)->getContent(),
        );
    }

    #[Test]
    public function opensAPostedPreviewAsAVisitToItsPage(): void
    {
        $context = new PreviewContext();
        $event   = $this->posted(new Parameters(['contenir_preview' => Previews::token(new FrozenClock())]));
        $request = $event->getRequest();

        $response = $this->listener($context)->onRoute($event);

        $this->assertSame(
            [null, 'Page', true, 'GET', [], '/spa-rituals', true],
            [
                $response,
                $event->getRouteMatch()?->getParam('controller'),
                $event->propagationIsStopped(),
                $request->getMethod(),
                $request->getPost()->toArray(),
                $request->getUri()->getPath(),
                $context->isActive(),
            ],
        );
    }

    #[Test]
    public function opensAPreviewBelowTheApplicationsSubdirectory(): void
    {
        $request = new EnvironmentRequest();
        $request->setBaseUrl('/site');
        $request->setUri('https://www.example.test/site/api/resource/preview');
        $request->setMethod(Request::METHOD_POST);
        $request->setPost(new Parameters(['contenir_preview' => Previews::token(new FrozenClock())]));
        $event = $this->routed($request);

        $this->listener(new PreviewContext())->onRoute($event);

        $this->assertSame('/site/spa-rituals', $request->getUri()->getPath());
    }

    private function finished(string $content): MvcEvent
    {
        $response = new Response();
        $response->setContent($content);
        $event = new MvcEvent();
        $event->setResponse($response);

        return $event;
    }

    private function listener(PreviewContext $context, bool $marker = true): PreviewListener
    {
        $clock = new FrozenClock();

        return new PreviewListener(
            new PreviewEntry(
                new TokenVerifier(Previews::config(), $clock, new InMemoryReplayGuard($clock)),
                new ResolverRegistry(['site.page.home' => new PathResolver()]),
                $context,
            ),
            new TargetMatcher(),
            $context,
            new PreviewResponse(Previews::config()),
            $marker,
        );
    }

    private function posted(Parameters $post): MvcEvent
    {
        $request = new Request();
        $request->setUri('https://www.example.test/api/resource/preview');
        $request->setMethod(Request::METHOD_POST);
        $request->setPost($post);

        return $this->routed($request);
    }

    private function previewing(): PreviewContext
    {
        $context = new PreviewContext();
        $context->begin(Previews::request());

        return $context;
    }

    private function response(MvcEvent $event): Response
    {
        $response = $event->getResponse();
        $this->assertInstanceOf(Response::class, $response);

        return $response;
    }

    private function routed(Request $request): MvcEvent
    {
        $router = $this->createStub(RouteStackInterface::class);
        $router->method('match')->willReturn(new RouteMatch(['controller' => 'Page']));
        $event = new MvcEvent();
        $event->setRequest($request);
        $event->setRouter($router);

        return $event;
    }
}
