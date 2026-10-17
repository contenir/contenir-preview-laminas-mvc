<?php

declare(strict_types=1);

namespace Contenir\Preview\Laminas\Mvc\Tests\Unit\Factory;

use Contenir\Preview\Clock\SystemClock;
use Contenir\Preview\Laminas\Mvc\Factory\ClockFactory;
use Contenir\Preview\Laminas\Mvc\PreviewOptions;
use Contenir\Preview\Laminas\Mvc\Tests\TestAsset\ArrayContainer;
use Contenir\Preview\Laminas\Mvc\Tests\TestAsset\FrozenClock;
use Contenir\Preview\Laminas\Mvc\Tests\TestAsset\Previews;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ClockFactoryTest extends TestCase
{
    #[Test]
    public function usesTheConfiguredClock(): void
    {
        $clock = new FrozenClock();

        $this->assertSame($clock, (new ClockFactory())(new ArrayContainer([
            PreviewOptions::class => PreviewOptions::fromAppConfig(Previews::appConfig(['clock' => 'AppClock'])),
            'AppClock'            => $clock,
        ])));
    }

    #[Test]
    public function usesTheSystemClockOtherwise(): void
    {
        $this->assertInstanceOf(SystemClock::class, (new ClockFactory())(new ArrayContainer([
            PreviewOptions::class => PreviewOptions::fromAppConfig(Previews::appConfig()),
        ])));
    }
}
