<?php

declare(strict_types=1);

namespace Contenir\Preview\Laminas\Mvc;

use Contenir\Preview\PreviewConfig;

use function array_is_list;
use function is_array;
use function is_bool;
use function is_string;

/**
 * The application's `contenir_preview` configuration, read once and checked:
 *
 * ```php
 * 'contenir_preview' => [
 *     'issuer'    => 'https://admin.example.com',
 *     'audience'  => 'https://www.example.com',
 *     'secrets'   => ['2026-10' => '…at least 32 bytes…'],
 *     'sign_with' => '2026-10',
 *     'resolvers' => [
 *         'site.page.home'        => '/{slug}',                                  // a path
 *         'library.news.articles' => ['route' => 'news/article', 'params' => ['slug' => '{slug}']],
 *         'site.page.landing'     => App\Preview\LandingResolver::class,         // a resolver service
 *     ],
 *     'replay'    => ['cache' => 'PreviewCache'],   // a PSR-16 service shared by servers; or
 *                                                   // ['directory' => '/var/app/preview'], default the temp dir
 *     'clock'     => null,                          // a PSR-20 service, default the system's
 *     'marker'    => true,                          // the visible "Preview" badge
 * ],
 * ```
 */
final readonly class PreviewOptions
{
    /**
     * @param array<string, string|array{route: string, params?: array<string, string>}> $resolvers
     */
    private function __construct(
        public PreviewConfig $config,
        public array $resolvers,
        public ReplayOptions $replay,
        public ?string $clock,
        public bool $marker,
    ) {}

    /**
     * @param array<array-key, mixed> $appConfig The application's merged configuration
     * @throws InvalidConfiguration
     */
    public static function fromAppConfig(array $appConfig): self
    {
        /** @var mixed $options */
        $options = $appConfig['contenir_preview'] ?? null;
        if (! is_array($options)) {
            throw new InvalidConfiguration('The application has no contenir_preview configuration.');
        }

        /** @var mixed $marker */
        $marker = $options['marker'] ?? true;

        return new self(
            new PreviewConfig(
                Setting::text($options, 'issuer', 'contenir_preview.issuer'),
                Setting::text($options, 'audience', 'contenir_preview.audience'),
                self::secrets($options['secrets'] ?? null),
                Setting::text($options, 'sign_with', 'contenir_preview.sign_with'),
            ),
            ResolverSpec::map($options['resolvers'] ?? []),
            ReplayOptions::from($options['replay'] ?? []),
            Setting::optionalText($options, 'clock', 'contenir_preview.clock'),
            is_bool($marker)
                ? $marker
                : throw new InvalidConfiguration('contenir_preview.marker must be true or false.'),
        );
    }

    /**
     * @return non-empty-array<string, string>
     * @throws InvalidConfiguration
     */
    private static function secrets(mixed $secrets): array
    {
        if (! is_array($secrets) || [] === $secrets || array_is_list($secrets)) {
            throw new InvalidConfiguration('contenir_preview.secrets must map key ids to secrets.');
        }

        $valid = [];
        /** @var mixed $secret */
        foreach ($secrets as $keyId => $secret) {
            if (! is_string($keyId) || ! is_string($secret)) {
                throw new InvalidConfiguration('contenir_preview.secrets must map key ids to secrets.');
            }
            $valid[$keyId] = $secret;
        }

        return $valid;
    }
}
