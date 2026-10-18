<?php

declare(strict_types=1);

namespace Contenir\Preview\Laminas\Mvc\Tests\Unit;

use Contenir\Preview\InvalidPreview;
use Contenir\Preview\Laminas\Mvc\TargetMatcher;
use Contenir\Preview\Resolver\PreviewTarget;
use Laminas\Http\PhpEnvironment\Request as EnvironmentRequest;
use Laminas\Http\Request;
use Laminas\Router\Exception\RuntimeException as RouterException;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\TreeRouteStack;
use Laminas\Router\RouteMatch;
use Laminas\Router\RouteStackInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The request set up so the application routes it to the page a resolver names, as it would a
 * visit.
 */
#[Group('unit')]
final class TargetMatcherTest extends TestCase
{
    #[Test]
    public function givesAHandlerARouteOfItsOwnAtThePreviewEndpoint(): void
    {
        $router = new TreeRouteStack();
        $router->addRoute('everything', Literal::factory([
            'route'    => '/api/resource/preview',
            'defaults' => ['controller' => 'SiteController'],
        ]));
        $request = $this->request();

        (new TargetMatcher())->prepare(
            PreviewTarget::handler('DraftController', ['id' => '7', 'controller' => 'Other']),
            $router,
            $request,
        );
        $match = $router->match($request);

        static::assertSame(
            [TargetMatcher::HANDLER_ROUTE, ['id' => '7', 'controller' => 'DraftController'], '/api/resource/preview'],
            [$match?->getMatchedRouteName(), $match?->getParams(), $request->getUri()->getPath()],
        );
    }

    #[Test]
    public function movesTheRequestToANamedRoutesAddress(): void
    {
        $router = $this->createMock(RouteStackInterface::class);
        $router->method('assemble')
            ->with(['slug' => 'spa-rituals'], ['name' => 'news/article'])
            ->willReturn('/news/spa-rituals');
        $router->method('match')->willReturn(new RouteMatch([]));
        $request = $this->request();

        (new TargetMatcher())->prepare(
            PreviewTarget::route('news/article', ['slug' => 'spa-rituals']),
            $router,
            $request,
        );

        static::assertSame('/news/spa-rituals', $request->getUri()->getPath());
    }

    #[Test]
    public function movesTheRequestToAPath(): void
    {
        $router = $this->createStub(RouteStackInterface::class);
        $router->method('match')->willReturn(new RouteMatch([]));
        $request = $this->request();

        (new TargetMatcher())->prepare(PreviewTarget::path('/spa-rituals'), $router, $request);

        static::assertSame('/spa-rituals', $request->getUri()->getPath());
    }

    #[Test]
    public function movesTheRequestToAPathBelowTheApplicationsSubdirectory(): void
    {
        $router = $this->createStub(RouteStackInterface::class);
        $router->method('match')->willReturn(new RouteMatch([]));
        $request = new EnvironmentRequest();
        $request->setBaseUrl('/site');

        (new TargetMatcher())->prepare(PreviewTarget::path('/spa-rituals'), $router, $request);

        static::assertSame('/site/spa-rituals', $request->getUri()->getPath());
    }

    #[Test]
    public function refusesAPathTheSiteHasNoPageAt(): void
    {
        $router = $this->createStub(RouteStackInterface::class);
        $router->method('match')->willReturn(null);

        $this->assertRefused(
            404,
            'The site has no page at this record\'s address.',
            fn() => (new TargetMatcher())->prepare(PreviewTarget::path('/gone'), $router, $this->request()),
        );
    }

    #[Test]
    public function refusesARouteAssembledAsSomethingButAPath(): void
    {
        $router = $this->createStub(RouteStackInterface::class);
        $router->method('assemble')->willReturn(null);

        $this->assertRefused(
            404,
            'The site has no route for this record.',
            fn() => (new TargetMatcher())->prepare(PreviewTarget::route('news'), $router, $this->request()),
        );
    }

    #[Test]
    public function refusesARouteTheRouterCannotAssemble(): void
    {
        $router = $this->createStub(RouteStackInterface::class);
        $router->method('assemble')->willThrowException(new RouterException('Route with name "news" not found'));

        $this->assertRefused(
            404,
            'The site has no route for this record.',
            fn() => (new TargetMatcher())->prepare(PreviewTarget::route('news'), $router, $this->request()),
        );
    }

    /**
     * @param callable(): mixed $prepare
     */
    private function assertRefused(int $status, string $reason, callable $prepare): void
    {
        try {
            $prepare();
            static::fail('The target was prepared.');
        } catch (InvalidPreview $refusal) {
            static::assertSame([$status, $reason], [$refusal->status, $refusal->getMessage()]);
        }
    }

    private function request(): Request
    {
        $request = new Request();
        $request->setUri('https://www.example.test/api/resource/preview');

        return $request;
    }
}
