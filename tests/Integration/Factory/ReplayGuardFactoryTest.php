<?php

declare(strict_types=1);

namespace Contenir\Preview\Laminas\Mvc\Tests\Integration\Factory;

use Contenir\Preview\Laminas\Mvc\Factory\ClockFactory;
use Contenir\Preview\Laminas\Mvc\Factory\ReplayGuardFactory;
use Contenir\Preview\Laminas\Mvc\PreviewOptions;
use Contenir\Preview\Laminas\Mvc\Tests\TestAsset\ArrayContainer;
use Contenir\Preview\Laminas\Mvc\Tests\TestAsset\FrozenClock;
use Contenir\Preview\Laminas\Mvc\Tests\TestAsset\Previews;
use Contenir\Preview\Laminas\Mvc\Tests\Trait\TempDirectoryTrait;
use Contenir\Preview\Replay\ReplayGuardInterface;
use Override;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function bin2hex;
use function is_file;
use function random_bytes;
use function sys_get_temp_dir;
use function unlink;

/**
 * Accepted tokens remembered as files: in the configured directory, or the system's temporary one.
 */
#[Group('integration')]
final class ReplayGuardFactoryTest extends TestCase
{
    use TempDirectoryTrait;

    private string $tokenId;

    #[Test]
    public function remembersTokensInTheConfiguredDirectory(): void
    {
        $this->guard(['directory' => $this->directory])->claim($this->tokenId, 1_792_236_300);

        $this->assertFileExists("{$this->directory}/contenir-preview-{$this->tokenId}");
    }

    #[Test]
    public function remembersTokensInTheTemporaryDirectoryByDefault(): void
    {
        $this->guard([])->claim($this->tokenId, 1_792_236_300);

        $this->assertFileExists(sys_get_temp_dir() . "/contenir-preview-{$this->tokenId}");
    }

    #[Override]
    protected function setUp(): void
    {
        $this->setUpTempDirectory();
        $this->tokenId = bin2hex(random_bytes(16));
    }

    #[Override]
    protected function tearDown(): void
    {
        $this->tearDownTempDirectory();
        $default = sys_get_temp_dir() . "/contenir-preview-{$this->tokenId}";
        if (is_file($default)) {
            unlink($default);
        }
    }

    /**
     * @param array<string, string> $replay
     */
    private function guard(array $replay): ReplayGuardInterface
    {
        return (new ReplayGuardFactory())(new ArrayContainer([
            PreviewOptions::class => PreviewOptions::fromAppConfig(Previews::appConfig(['replay' => $replay])),
            ClockFactory::SERVICE => new FrozenClock(),
        ]));
    }
}
