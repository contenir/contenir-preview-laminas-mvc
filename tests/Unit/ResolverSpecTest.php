<?php

declare(strict_types=1);

namespace Contenir\Preview\Laminas\Mvc\Tests\Unit;

use Contenir\Preview\Laminas\Mvc\InvalidConfiguration;
use Contenir\Preview\Laminas\Mvc\ResolverSpec;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * A section's resolver as configuration gives it.
 */
#[Group('unit')]
final class ResolverSpecTest extends TestCase
{
    /**
     * @return array<string, array{mixed, string}>
     */
    public static function invalidSpecs(): array
    {
        $resolver = 'The resolver for site.page.home must be a path, a route or a service name.';
        $params   = 'The route parameters for site.page.home must map names to patterns.';

        return [
            'empty'                 => ['', $resolver],
            'a number'              => [42, $resolver],
            'a map without a route' => [['params' => []], $resolver],
            'a route not named'     => [['route' => ''], $resolver],
            'a route name not text' => [['route' => 42], $resolver],
            'parameters not a map'  => [['route' => 'news', 'params' => 'slug'], $params],
            'a numbered parameter'  => [['route' => 'news', 'params' => ['{slug}']], $params],
            'a pattern not text'    => [['route' => 'news', 'params' => ['slug' => 42]], $params],
        ];
    }

    #[Test]
    public function acceptsAPathOrAServiceName(): void
    {
        $this->assertSame('/{slug}', ResolverSpec::check('site.page.home', '/{slug}'));
    }

    #[Test]
    public function acceptsARouteWithItsParameters(): void
    {
        $this->assertSame(
            ['route' => 'news/article', 'params' => ['slug' => '{slug}']],
            ResolverSpec::check('site.page.home', ['route' => 'news/article', 'params' => ['slug' => '{slug}']]),
        );
    }

    #[Test]
    public function acceptsARouteWithoutParameters(): void
    {
        $this->assertSame(
            ['route' => 'home', 'params' => []],
            ResolverSpec::check('site.page.home', ['route' => 'home']),
        );
    }

    #[Test]
    public function mapsEverySection(): void
    {
        $this->assertSame(
            ['site.page.home' => '/{slug}', 'site.page.landing' => 'LandingResolver'],
            ResolverSpec::map(['site.page.home' => '/{slug}', 'site.page.landing' => 'LandingResolver']),
        );
    }

    #[Test]
    #[DataProvider('invalidSpecs')]
    public function refusesAnInvalidResolver(mixed $spec, string $reason): void
    {
        $this->expectException(InvalidConfiguration::class);
        $this->expectExceptionMessage($reason);

        ResolverSpec::check('site.page.home', $spec);
    }
}
