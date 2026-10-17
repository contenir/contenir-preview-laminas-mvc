<?php

declare(strict_types=1);

namespace Contenir\Preview\Laminas\Mvc\Tests\Integration;

use Contenir\Preview\Laminas\Mvc\Factory\ClockFactory;
use Contenir\Preview\Laminas\Mvc\Module;
use Contenir\Preview\Laminas\Mvc\PreviewMarker;
use Contenir\Preview\Laminas\Mvc\Tests\TestAsset\FrozenClock;
use Contenir\Preview\Laminas\Mvc\Tests\TestAsset\Previews;
use Contenir\Preview\Laminas\Mvc\Tests\TestAsset\Site\DraftResolver;
use Contenir\Preview\Laminas\Mvc\Tests\TestAsset\Site\Module as SiteModule;
use Contenir\Preview\Laminas\Mvc\Tests\Trait\TempDirectoryTrait;
use Contenir\Preview\PreviewRecord;
use Laminas\Http\PhpEnvironment\Request;
use Laminas\Http\Response;
use Laminas\Mvc\Application;
use Laminas\Router\Module as RouterModule;
use Laminas\Stdlib\Parameters;
use Override;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SensitiveParameter;

use function str_contains;

/**
 * A Laminas MVC application with the module, answering the CMS's previews from start to finish:
 * routed by path, dispatched to a handler, refused, and left alone when not configured.
 */
#[Group('integration')]
final class PreviewApplicationTest extends TestCase
{
    use TempDirectoryTrait;

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
}
