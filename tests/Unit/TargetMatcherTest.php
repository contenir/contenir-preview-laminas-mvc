<?php

declare(strict_types=1);

namespace Contenir\Preview\Laminas\Mvc\Tests\Unit;

use Contenir\Preview\InvalidPreview;
use Contenir\Preview\Laminas\Mvc\TargetMatcher;
use Contenir\Preview\Resolver\PreviewTarget;
use Laminas\Http\PhpEnvironment\Request as EnvironmentRequest;
use Laminas\Http\Request;
use Laminas\Router\Exception\RuntimeException as RouterException;
use Laminas\Router\RouteMatch;
use Laminas\Router\RouteStackInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The page a resolver names, as the route match the application dispatches.
 */
#[Group('unit')]
final class TargetMatcherTest extends TestCase
{
    #[Test]
    public function assemblesANamedRouteAndMatchesIt(): void
    {
        $match  = new RouteMatch(['controller' => 'Article']);
        $router = $this->createMock(RouteStackInterface::class);
        $router->method('assemble')
            ->with(['slug' => 'spa-rituals'], ['name' => 'news/article'])
            ->willReturn('/news/spa-rituals');
        $router->method('match')->willReturn($match);
        $request = $this->request();

        $matched = (new TargetMatcher())->match(
            PreviewTarget::route('news/article', ['slug' => 'spa-rituals']),
            $router,
            $request,
        );

        $this->assertSame([$match, '/news/spa-rituals'], [$matched, $request->getUri()->getPath()]);
    }

    #[Test]
    public function dispatchesAHandlerWithoutRouting(): void
    {
        $router = $this->createMock(RouteStackInterface::class);
        $router->expects($this->never())->method('match');
        $request = $this->request();

        $match = (new TargetMatcher())->match(
            PreviewTarget::handler('DraftController', ['id' => '7', 'controller' => 'Other']),
            $router,
            $request,
        );

        $this->assertSame(
            [['id' => '7', 'controller' => 'DraftController'], '/api/resource/preview'],
            [$match->getParams(), $request->getUri()->getPath()],
        );
    }

    #[Test]
    public function matchesAPathAsAVisitToIt(): void
    {
        $match  = new RouteMatch(['controller' => 'Page']);
        $router = $this->createStub(RouteStackInterface::class);
        $router->method('match')->willReturn($match);
        $request = $this->request();

        $matched = (new TargetMatcher())->match(PreviewTarget::path('/spa-rituals'), $router, $request);

        $this->assertSame([$match, '/spa-rituals'], [$matched, $request->getUri()->getPath()]);
    }

    #[Test]
    public function matchesAPathBelowTheApplicationsSubdirectory(): void
    {
        $router = $this->createStub(RouteStackInterface::class);
        $router->method('match')->willReturn(new RouteMatch([]));
        $request = new EnvironmentRequest();
        $request->setBaseUrl('/site');

        (new TargetMatcher())->match(PreviewTarget::path('/spa-rituals'), $router, $request);

        $this->assertSame('/site/spa-rituals', $request->getUri()->getPath());
    }

    #[Test]
    public function refusesAPathTheSiteHasNoPageAt(): void
    {
        $router = $this->createStub(RouteStackInterface::class);
        $router->method('match')->willReturn(null);

        $this->assertRefused(
            404,
            'The site has no page at this record\'s address.',
            fn() => (new TargetMatcher())->match(PreviewTarget::path('/gone'), $router, $this->request()),
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
            fn() => (new TargetMatcher())->match(PreviewTarget::route('news'), $router, $this->request()),
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
            fn() => (new TargetMatcher())->match(PreviewTarget::route('news'), $router, $this->request()),
        );
    }

    /**
     * @param callable(): mixed $match
     */
    private function assertRefused(int $status, string $reason, callable $match): void
    {
        try {
            $match();
            $this->fail('The target was matched.');
        } catch (InvalidPreview $refusal) {
            $this->assertSame([$status, $reason], [$refusal->status, $refusal->getMessage()]);
        }
    }

    private function request(): Request
    {
        $request = new Request();
        $request->setUri('https://www.example.test/api/resource/preview');

        return $request;
    }
}
