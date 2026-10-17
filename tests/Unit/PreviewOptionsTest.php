<?php

declare(strict_types=1);

namespace Contenir\Preview\Laminas\Mvc\Tests\Unit;

use Contenir\Preview\Laminas\Mvc\InvalidConfiguration;
use Contenir\Preview\Laminas\Mvc\PreviewOptions;
use Contenir\Preview\Laminas\Mvc\Tests\TestAsset\Previews;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The application's `contenir_preview` configuration, read and checked once.
 */
#[Group('unit')]
final class PreviewOptionsTest extends TestCase
{
    /**
     * @return array<string, array{array<array-key, mixed>, string}>
     */
    public static function invalidConfigurations(): array
    {
        $with = Previews::appConfig(...);

        return [
            'no configuration'          => [[], 'The application has no contenir_preview configuration.'],
            'configuration not a map'   => [
                ['contenir_preview' => 'yes'],
                'The application has no contenir_preview configuration.',
            ],
            'no issuer'                 => [
                $with(['issuer' => null]),
                'contenir_preview.issuer must be a non-empty string.',
            ],
            'empty audience'            => [
                $with(['audience' => '']),
                'contenir_preview.audience must be a non-empty string.',
            ],
            'no key to sign with'       => [
                $with(['sign_with' => null]),
                'contenir_preview.sign_with must be a non-empty string.',
            ],
            'no secrets'                => [
                $with(['secrets' => null]),
                'contenir_preview.secrets must map key ids to secrets.',
            ],
            'empty secrets'             => [
                $with(['secrets' => []]),
                'contenir_preview.secrets must map key ids to secrets.',
            ],
            'secrets without key ids'   => [
                $with(['secrets' => ['a-secret']]),
                'contenir_preview.secrets must map key ids to secrets.',
            ],
            'a numbered secret'         => [
                $with(['secrets' => [5 => 'a-secret']]),
                'contenir_preview.secrets must map key ids to secrets.',
            ],
            'a secret not text'         => [
                $with(['secrets' => ['2026-10' => 42]]),
                'contenir_preview.secrets must map key ids to secrets.',
            ],
            'replay not a map'          => [$with(['replay' => 'files']), 'contenir_preview.replay must be an array.'],
            'a clock not named'         => [
                $with(['clock' => 42]),
                'contenir_preview.clock must be a non-empty string.',
            ],
            'a marker not a flag'       => [
                $with(['marker' => 'yes']),
                'contenir_preview.marker must be true or false.',
            ],
            'resolvers not a map'       => [
                $with(['resolvers' => '/{slug}']),
                'contenir_preview.resolvers must map sections to resolvers.',
            ],
            'a resolver for no section' => [
                $with(['resolvers' => ['/{slug}']]),
                'contenir_preview.resolvers must map sections to resolvers.',
            ],
        ];
    }

    #[Test]
    public function defaultsToTheSystemClockTemporaryFilesAndTheMarker(): void
    {
        $options = PreviewOptions::fromAppConfig(Previews::appConfig());

        $this->assertSame(
            [[], null, null, null, true],
            [
                $options->resolvers,
                $options->replay->cache,
                $options->replay->directory,
                $options->clock,
                $options->marker,
            ],
        );
    }

    #[Test]
    public function readsTheSharedConfiguration(): void
    {
        $config = PreviewOptions::fromAppConfig(Previews::appConfig())->config;

        $this->assertSame(
            [Previews::ISSUER, Previews::AUDIENCE, ['2026-10' => Previews::SHARED], '2026-10'],
            [$config->issuer, $config->audience, $config->secrets, $config->signWith],
        );
    }

    #[Test]
    public function readsTheSitesChoices(): void
    {
        $options = PreviewOptions::fromAppConfig(Previews::appConfig([
            'resolvers' => ['site.page.home' => '/{slug}'],
            'replay'    => ['cache' => 'PreviewCache'],
            'clock'     => 'AppClock',
            'marker'    => false,
        ]));

        $this->assertSame(
            [['site.page.home' => '/{slug}'], 'PreviewCache', 'AppClock', false],
            [$options->resolvers, $options->replay->cache, $options->clock, $options->marker],
        );
    }

    /**
     * @param array<array-key, mixed> $appConfig
     */
    #[Test]
    #[DataProvider('invalidConfigurations')]
    public function refusesAnInvalidConfiguration(array $appConfig, string $reason): void
    {
        $this->expectException(InvalidConfiguration::class);
        $this->expectExceptionMessage($reason);

        PreviewOptions::fromAppConfig($appConfig);
    }
}
