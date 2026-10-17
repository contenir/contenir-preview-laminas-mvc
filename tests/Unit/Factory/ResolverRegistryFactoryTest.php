<?php

declare(strict_types=1);

namespace Contenir\Preview\Laminas\Mvc\Tests\Unit\Factory;

use Contenir\Preview\Laminas\Mvc\Factory\ResolverRegistryFactory;
use Contenir\Preview\Laminas\Mvc\InvalidConfiguration;
use Contenir\Preview\Laminas\Mvc\PreviewOptions;
use Contenir\Preview\Laminas\Mvc\Tests\TestAsset\ArrayContainer;
use Contenir\Preview\Laminas\Mvc\Tests\TestAsset\Previews;
use Contenir\Preview\PreviewContext;
use Contenir\Preview\Resolver\PreviewResolverInterface;
use Contenir\Preview\Resolver\PreviewTarget;
use Contenir\Preview\Resolver\ResolverRegistry;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Each section's resolver built from configuration.
 */
#[Group('unit')]
final class ResolverRegistryFactoryTest extends TestCase
{
    #[Test]
    public function buildsAPathResolverFromAPattern(): void
    {
        $target = $this->registry(['site.page.home' => '/pages/{slug}'])->resolve(Previews::record());

        $this->assertSame('/pages/spa-rituals', $target->path);
    }

    #[Test]
    public function buildsARouteResolverFromARoute(): void
    {
        $target = $this->registry(['site.page.home' => ['route' => 'page', 'params' => ['slug' => '{slug}']]])
            ->resolve(Previews::record());

        $this->assertSame(['page', ['slug' => 'spa-rituals']], [$target->route, $target->params]);
    }

    #[Test]
    public function refusesAServiceThatIsNotAResolver(): void
    {
        $this->expectException(InvalidConfiguration::class);
        $this->expectExceptionMessage(
            'The service LandingResolver is not a Contenir\Preview\Resolver\PreviewResolverInterface.',
        );

        $this->registry(['site.page.home' => 'LandingResolver'], ['LandingResolver' => new PreviewContext()]);
    }

    #[Test]
    public function usesAResolverService(): void
    {
        $resolver = $this->createStub(PreviewResolverInterface::class);
        $resolver->method('resolve')->willReturn(PreviewTarget::handler('LandingController'));

        $target = $this->registry(['site.page.home' => 'LandingResolver'], ['LandingResolver' => $resolver])
            ->resolve(Previews::record());

        $this->assertSame('LandingController', $target->handler);
    }

    /**
     * @param array<string, mixed> $resolvers
     * @param array<string, mixed> $services
     */
    private function registry(array $resolvers, array $services = []): ResolverRegistry
    {
        return (new ResolverRegistryFactory())(new ArrayContainer([
            PreviewOptions::class => PreviewOptions::fromAppConfig(Previews::appConfig(['resolvers' => $resolvers])),
            ...$services,
        ]));
    }
}
