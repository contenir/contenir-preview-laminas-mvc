<?php

declare(strict_types=1);

namespace Contenir\Preview\Laminas\Mvc\Tests\Unit\Factory;

use Contenir\Preview\Laminas\Mvc\Factory\ClockFactory;
use Contenir\Preview\Laminas\Mvc\Factory\ReplayGuardFactory;
use Contenir\Preview\Laminas\Mvc\PreviewOptions;
use Contenir\Preview\Laminas\Mvc\Tests\TestAsset\ArrayContainer;
use Contenir\Preview\Laminas\Mvc\Tests\TestAsset\FrozenClock;
use Contenir\Preview\Laminas\Mvc\Tests\TestAsset\Previews;
use Contenir\Preview\Replay\CacheReplayGuard;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;

/**
 * The replay guard over a shared cache; the file guard is tested with real files, as integration.
 */
#[Group('unit')]
final class ReplayGuardFactoryTest extends TestCase
{
    #[Test]
    public function remembersTokensInTheConfiguredCache(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->method('has')->willReturn(false);
        $cache->expects($this->once())->method('set')->willReturn(true);

        $guard = (new ReplayGuardFactory())(new ArrayContainer([
            PreviewOptions::class => PreviewOptions::fromAppConfig(Previews::appConfig(['replay' => [
                'cache' => 'PreviewCache',
            ]])),
            ClockFactory::SERVICE => new FrozenClock(),
            'PreviewCache'        => $cache,
        ]));

        $this->assertSame([CacheReplayGuard::class, true], [
            $guard::class,
            $guard->claim('0123456789abcdef0123456789abcdef', 1_792_236_300),
        ]);
    }
}
