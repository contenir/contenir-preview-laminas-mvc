<?php

declare(strict_types=1);

namespace Contenir\Preview\Laminas\Mvc\Tests\Unit;

use Contenir\Preview\Laminas\Mvc\InvalidConfiguration;
use Contenir\Preview\Laminas\Mvc\ReplayOptions;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Where accepted tokens are remembered, as `contenir_preview.replay` says.
 */
#[Group('unit')]
final class ReplayOptionsTest extends TestCase
{
    #[Test]
    public function readsACacheAndADirectory(): void
    {
        $replay = ReplayOptions::from(['cache' => 'PreviewCache', 'directory' => '/var/preview']);

        $this->assertSame(['PreviewCache', '/var/preview'], [$replay->cache, $replay->directory]);
    }

    #[Test]
    public function refusesAnEmptyCacheName(): void
    {
        $this->expectException(InvalidConfiguration::class);
        $this->expectExceptionMessage('contenir_preview.replay.cache must be a non-empty string.');

        ReplayOptions::from(['cache' => '']);
    }

    #[Test]
    public function refusesAnEmptyDirectory(): void
    {
        $this->expectException(InvalidConfiguration::class);
        $this->expectExceptionMessage('contenir_preview.replay.directory must be a non-empty string.');

        ReplayOptions::from(['directory' => '']);
    }
}
