<?php

declare(strict_types=1);

namespace Contenir\Preview\Laminas\Mvc\Tests\Unit\Factory;

use Contenir\Preview\Clock\SystemClock;
use Contenir\Preview\Laminas\Mvc\Factory\Service;
use Contenir\Preview\Laminas\Mvc\InvalidConfiguration;
use Contenir\Preview\Laminas\Mvc\Tests\TestAsset\ArrayContainer;
use Contenir\Preview\PreviewContext;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

/**
 * A service from the container, checked to be the type its user needs.
 */
#[Group('unit')]
final class ServiceTest extends TestCase
{
    #[Test]
    public function findsAServiceByAnotherName(): void
    {
        $clock = new SystemClock();

        $this->assertSame($clock, Service::get(
            new ArrayContainer(['AppClock' => $clock]),
            ClockInterface::class,
            'AppClock',
        ));
    }

    #[Test]
    public function findsAServiceByItsType(): void
    {
        $context = new PreviewContext();

        $this->assertSame($context, Service::get(new ArrayContainer([
            PreviewContext::class => $context,
        ]), PreviewContext::class));
    }

    #[Test]
    public function refusesAServiceOfAnotherType(): void
    {
        $this->expectException(InvalidConfiguration::class);
        $this->expectExceptionMessage('The service AppClock is not a Psr\Clock\ClockInterface.');

        Service::get(new ArrayContainer(['AppClock' => new PreviewContext()]), ClockInterface::class, 'AppClock');
    }
}
