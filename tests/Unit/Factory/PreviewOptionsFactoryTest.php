<?php

declare(strict_types=1);

namespace Contenir\Preview\Laminas\Mvc\Tests\Unit\Factory;

use Contenir\Preview\Laminas\Mvc\Factory\PreviewOptionsFactory;
use Contenir\Preview\Laminas\Mvc\InvalidConfiguration;
use Contenir\Preview\Laminas\Mvc\Tests\TestAsset\ArrayContainer;
use Contenir\Preview\Laminas\Mvc\Tests\TestAsset\Previews;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class PreviewOptionsFactoryTest extends TestCase
{
    #[Test]
    public function readsTheApplicationsConfiguration(): void
    {
        $options = (new PreviewOptionsFactory())(new ArrayContainer(['config' => Previews::appConfig()]));

        $this->assertSame(Previews::AUDIENCE, $options->config->audience);
    }

    #[Test]
    public function refusesAnApplicationWithoutConfiguration(): void
    {
        $this->expectException(InvalidConfiguration::class);
        $this->expectExceptionMessage('The application has no contenir_preview configuration.');

        (new PreviewOptionsFactory())(new ArrayContainer(['config' => null]));
    }
}
