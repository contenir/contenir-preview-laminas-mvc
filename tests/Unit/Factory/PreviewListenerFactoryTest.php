<?php

declare(strict_types=1);

namespace Contenir\Preview\Laminas\Mvc\Tests\Unit\Factory;

use Contenir\Preview\Laminas\Mvc\Factory\PreviewListenerFactory;
use Contenir\Preview\Laminas\Mvc\PreviewMarker;
use Contenir\Preview\Laminas\Mvc\PreviewOptions;
use Contenir\Preview\Laminas\Mvc\Tests\TestAsset\ArrayContainer;
use Contenir\Preview\Laminas\Mvc\Tests\TestAsset\FrozenClock;
use Contenir\Preview\Laminas\Mvc\Tests\TestAsset\Previews;
use Contenir\Preview\PreviewContext;
use Contenir\Preview\PreviewEntry;
use Contenir\Preview\PreviewResponse;
use Contenir\Preview\Replay\InMemoryReplayGuard;
use Contenir\Preview\Resolver\ResolverRegistry;
use Contenir\Preview\Token\TokenVerifier;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The listener over the configured services, with the badge as configured.
 */
#[Group('unit')]
final class PreviewListenerFactoryTest extends TestCase
{
    /**
     * @return array<string, array{bool, string}>
     */
    public static function markers(): array
    {
        return [
            'with the badge'    => [true, '<body>' . PreviewMarker::HTML . '</body>'],
            'without the badge' => [false, '<body></body>'],
        ];
    }

    #[Test]
    #[DataProvider('markers')]
    public function marksAPreviewAsConfigured(bool $marker, string $content): void
    {
        $context = new PreviewContext();
        $context->begin(Previews::request());
        $clock    = new FrozenClock();
        $response = new Response();
        $response->setContent('<body></body>');
        $event = new MvcEvent();
        $event->setResponse($response);

        (new PreviewListenerFactory())(new ArrayContainer([
            PreviewOptions::class  => PreviewOptions::fromAppConfig(Previews::appConfig(['marker' => $marker])),
            PreviewContext::class  => $context,
            PreviewResponse::class => new PreviewResponse(Previews::config()),
            PreviewEntry::class    => new PreviewEntry(
                new TokenVerifier(Previews::config(), $clock, new InMemoryReplayGuard($clock)),
                new ResolverRegistry([]),
                $context,
            ),
        ]))
            ->onFinish($event);

        $this->assertSame($content, $response->getContent());
    }
}
