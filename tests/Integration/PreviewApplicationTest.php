<?php

declare(strict_types=1);

namespace Contenir\Preview\Laminas\Mvc\Tests\Integration;

use Closure;
use Contenir\Preview\Container\ClockFactory;
use Contenir\Preview\Laminas\Mvc\Module;
use Contenir\Preview\Laminas\Mvc\TargetMatcher;
use Contenir\Preview\Laminas\Mvc\Tests\TestAsset\FrozenClock;
use Contenir\Preview\Laminas\Mvc\Tests\TestAsset\PageCache;
use Contenir\Preview\Laminas\Mvc\Tests\TestAsset\Previews;
use Contenir\Preview\Laminas\Mvc\Tests\TestAsset\Site\DraftResolver;
use Contenir\Preview\Laminas\Mvc\Tests\TestAsset\Site\Module as SiteModule;
use Contenir\Preview\Laminas\Mvc\Tests\Trait\TempDirectoryTrait;
use Contenir\Preview\PreviewMarker;
use Contenir\Preview\PreviewRecord;
use Laminas\Http\PhpEnvironment\Request;
use Laminas\Http\Response;
use Laminas\Mvc\Application;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\Module as RouterModule;
use Laminas\Stdlib\Parameters;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SensitiveParameter;

use function str_contains;

/**
 * A Laminas MVC application with the module, answering the CMS's previews from start to finish:
 * routed by path, dispatched to a handler, refused, left alone when not configured, and kept out
 * of the site's page cache.
 */
#[Group('integration')]
final class PreviewApplicationTest extends TestCase
{
    use TempDirectoryTrait;

    /**
     * @return array<string, array{Closure(): PageCache}>
     */
    public static function pageCaches(): array
    {
        return [
            'a cache listening for pagecache.disable' => [PageCache::listeningForDisable(...)],
            'a cache reading Cache-Control'           => [PageCache::readingCacheControl(...)],
        ];
    }

    /**
     * @return array<string, array{PreviewRecord, string}>
     */
    public static function previewsAndTheirRoutes(): array
    {
        return [
            'a page at its path'      => [
                new PreviewRecord('site.page.home', ['resource_id' => '101'], ['slug' => 'spa-rituals']),
                'page',
            ],
            'a draft through a route' => [
                new PreviewRecord('site.page.draft', [], ['title' => 'A draft']),
                TargetMatcher::HANDLER_ROUTE,
            ],
        ];
    }

    /**
     * @param class-string $module
     */
    private static function moduleOf(string $module): string
    {
        return (new ReflectionClass($module))->getNamespaceName();
    }

    #[Test]
    public function answersNothingWhenPreviewsAreNotConfigured(): void
    {
        $response = $this->preview($this->application([]), Previews::token(new FrozenClock()));

        $this->assertSame([404, false], [$response->getStatusCode(), $response->getHeaders()->has('X-Robots-Tag')]);
    }

    #[Test]
    public function dispatchesADraftToItsHandler(): void
    {
        $clock = new FrozenClock();
        $draft = new PreviewRecord('site.page.draft', [], ['title' => 'A page not saved yet']);

        $response = $this->preview($this->configured(), Previews::token($clock, $draft));

        $this->assertSame([200, true], [
            $response->getStatusCode(),
            str_contains($response->getBody(), '<h1>A page not saved yet</h1>'),
        ]);
    }

    #[Test]
    public function leavesAnOrdinaryVisitAlone(): void
    {
        $application = $this->configured();
        $request     = $this->request($application);
        $request->setUri('https://www.example.test/spa-rituals');

        $application->run();
        $response = $this->response($application);

        $this->assertSame(
            [200, true, false, false],
            [
                $response->getStatusCode(),
                str_contains($response->getBody(), '<h1>Stored title</h1>'),
                str_contains($response->getBody(), PreviewMarker::HTML),
                $response->getHeaders()->has('X-Robots-Tag'),
            ],
        );
    }

    #[Test]
    #[DataProvider('previewsAndTheirRoutes')]
    public function letsTheSitesOwnRouteListenersSeeThePreviewsRoute(PreviewRecord $record, string $route): void
    {
        $application = $this->configured();
        $seen        = [];
        $application->getEventManager()->attach(
            MvcEvent::EVENT_ROUTE,
            static function (MvcEvent $event) use (&$seen): void {
                $seen[] = $event->getRouteMatch()?->getMatchedRouteName();
            },
            -100,
        );

        $this->preview($application, Previews::token(new FrozenClock(), $record));

        $this->assertSame([$route], $seen);
    }

    /**
     * @param Closure(): PageCache $cache
     */
    #[Test]
    #[DataProvider('pageCaches')]
    public function previewsInPlaceOfAPageTheCacheHolds(Closure $cache): void
    {
        $application                   = $this->configured();
        $cached                        = $this->cachedBy($application, $cache());
        $cached->pages['/spa-rituals'] = '<html><body><h1>Cached title</h1></body></html>';

        $response = $this->preview($application, Previews::token(new FrozenClock()));

        $this->assertSame([true, false], [
            str_contains($response->getBody(), '<h1>Edited title</h1>'),
            str_contains($response->getBody(), '<h1>Cached title</h1>'),
        ]);
    }

    #[Test]
    public function refusesATokenUsedTwice(): void
    {
        $token = Previews::token(new FrozenClock());
        $this->preview($this->configured(), $token);

        $response = $this->preview($this->configured(), $token);

        $this->assertSame([409, 'no-store, private'], [
            $response->getStatusCode(),
            $response->getHeaders()->get('Cache-Control')->getFieldValue(),
        ]);
    }

    #[Test]
    public function rendersThePageWithTheUnsavedValues(): void
    {
        $response = $this->preview($this->configured(), Previews::token(new FrozenClock()));

        $this->assertSame(
            [200, true, true, true, 'noindex, nofollow'],
            [
                $response->getStatusCode(),
                str_contains($response->getBody(), '<h1>Edited title</h1>'),
                str_contains($response->getBody(), '<p>Previewing</p>'),
                str_contains($response->getBody(), PreviewMarker::HTML . '</body>'),
                $response->getHeaders()->get('X-Robots-Tag')->getFieldValue(),
            ],
        );
    }

    /**
     * @param Closure(): PageCache $cache
     */
    #[Test]
    #[DataProvider('pageCaches')]
    public function servesAnOrdinaryVisitFromTheCache(Closure $cache): void
    {
        $application                   = $this->configured();
        $cached                        = $this->cachedBy($application, $cache());
        $cached->pages['/spa-rituals'] = '<html><body><h1>Cached title</h1></body></html>';

        $response = $this->visit($application, '/spa-rituals');

        $this->assertSame('<html><body><h1>Cached title</h1></body></html>', $response->getBody());
    }

    /**
     * @param Closure(): PageCache $cache
     */
    #[Test]
    #[DataProvider('pageCaches')]
    public function storesAnOrdinaryVisitInTheCache(Closure $cache): void
    {
        $application = $this->configured();
        $cached      = $this->cachedBy($application, $cache());

        $this->visit($application, '/spa-rituals');

        $this->assertStringContainsString('<h1>Stored title</h1>', $cached->pages['/spa-rituals'] ?? '');
    }

    /**
     * @param Closure(): PageCache $cache
     */
    #[Test]
    #[DataProvider('pageCaches')]
    public function storesNoPreviewInTheCache(Closure $cache): void
    {
        $application = $this->configured();
        $cached      = $this->cachedBy($application, $cache());

        $this->preview($application, Previews::token(new FrozenClock()));

        $this->assertSame([], $cached->pages);
    }

    #[Override]
    protected function setUp(): void
    {
        $this->setUpTempDirectory();
    }

    #[Override]
    protected function tearDown(): void
    {
        $this->tearDownTempDirectory();
    }

    /**
     * An application with the module and the test site, the given configuration, and the clock
     * stopped.
     *
     * @param array<string, mixed> $config
     */
    private function application(array $config): Application
    {
        $application = Application::init([
            'modules'                 => [
                self::moduleOf(RouterModule::class),
                self::moduleOf(Module::class),
                self::moduleOf(SiteModule::class),
            ],
            'module_listener_options' => ['use_laminas_loader' => false, 'extra_config' => $config],
            'service_manager'         => ['services' => [ClockFactory::SERVICE => new FrozenClock()]],
        ]);
        $application->getServiceManager()->get('SendResponseListener')->detach($application->getEventManager());

        return $application;
    }

    private function cachedBy(Application $application, PageCache $cache): PageCache
    {
        $cache->attach($application->getEventManager()->getSharedManager());

        return $cache;
    }

    private function configured(): Application
    {
        return $this->application(Previews::appConfig([
            'resolvers' => ['site.page.home' => '/{slug}', 'site.page.draft' => DraftResolver::class],
            'replay'    => ['directory' => $this->directory],
        ]));
    }

    private function preview(Application $application, #[SensitiveParameter] string $token): Response
    {
        $request = $this->request($application);
        $request->setUri('https://www.example.test/api/resource/preview');
        $request->setMethod(Request::METHOD_POST);
        $request->setPost(new Parameters(['contenir_preview' => $token]));

        $application->run();

        return $this->response($application);
    }

    private function request(Application $application): Request
    {
        $request = $application->getRequest();
        $this->assertInstanceOf(Request::class, $request);
        $request->setBaseUrl('');
        $request->setBasePath('');

        return $request;
    }

    private function response(Application $application): Response
    {
        $response = $application->getResponse();
        $this->assertInstanceOf(Response::class, $response);

        return $response;
    }

    private function visit(Application $application, string $path): Response
    {
        $this->request($application)->setUri("https://www.example.test{$path}");

        $application->run();

        return $this->response($application);
    }
}
