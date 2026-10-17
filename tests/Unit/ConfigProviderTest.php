<?php

declare(strict_types=1);

namespace Contenir\Preview\Laminas\Mvc\Tests\Unit;

use Contenir\Preview\Laminas\Mvc\ConfigProvider;
use Contenir\Preview\Laminas\Mvc\Module;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_keys;

/**
 * What the module registers; the services themselves are built in the integration tests.
 */
#[Group('unit')]
final class ConfigProviderTest extends TestCase
{
    #[Test]
    public function namesTheHelperAndThePluginPreview(): void
    {
        $config = (new ConfigProvider())();

        $this->assertSame(
            [['preview'], ['preview']],
            [array_keys($config['view_helpers']['aliases']), array_keys($config['controller_plugins']['aliases'])],
        );
    }

    #[Test]
    public function registersTheServicesWithTheServiceManager(): void
    {
        $provider = new ConfigProvider();

        $this->assertSame($provider->dependencies(), $provider()['service_manager']);
    }

    #[Test]
    public function servesTheProvidersConfigurationAsTheModules(): void
    {
        $this->assertSame((new ConfigProvider())(), (new Module())->getConfig());
    }
}
