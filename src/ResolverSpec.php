<?php

declare(strict_types=1);

namespace Contenir\Preview\Laminas\Mvc;

use function is_array;
use function is_string;
use function sprintf;

/**
 * One section's resolver as configuration gives it: a path pattern (`/{slug}`), a named route with
 * parameter patterns (`['route' => 'news/article', 'params' => ['slug' => '{slug}']]`), or the
 * name of a PreviewResolverInterface service.
 *
 * @internal
 */
final class ResolverSpec
{
    /**
     * @return string|array{route: string, params?: array<string, string>}
     * @throws InvalidConfiguration
     */
    public static function check(string $section, mixed $spec): string|array
    {
        if (is_string($spec) && '' !== $spec) {
            return $spec;
        }

        if (! is_array($spec) || ! is_string($spec['route'] ?? null) || '' === $spec['route']) {
            throw new InvalidConfiguration(sprintf(
                'The resolver for %s must be a path, a route or a service name.',
                $section,
            ));
        }

        /** @var mixed $params */
        $params = $spec['params'] ?? [];
        if (! is_array($params)) {
            throw new InvalidConfiguration(sprintf(
                'The route parameters for %s must map names to patterns.',
                $section,
            ));
        }

        $valid = [];
        /** @var mixed $pattern */
        foreach ($params as $name => $pattern) {
            if (! is_string($name) || ! is_string($pattern)) {
                throw new InvalidConfiguration(sprintf(
                    'The route parameters for %s must map names to patterns.',
                    $section,
                ));
            }
            $valid[$name] = $pattern;
        }

        return ['route' => $spec['route'], 'params' => $valid];
    }

    /**
     * Every section's resolver.
     *
     * @return array<string, string|array{route: string, params?: array<string, string>}>
     * @throws InvalidConfiguration
     */
    public static function map(mixed $resolvers): array
    {
        if (! is_array($resolvers)) {
            throw new InvalidConfiguration('contenir_preview.resolvers must map sections to resolvers.');
        }

        $valid = [];
        /** @var mixed $resolver */
        foreach ($resolvers as $section => $resolver) {
            if (! is_string($section)) {
                throw new InvalidConfiguration('contenir_preview.resolvers must map sections to resolvers.');
            }
            $valid[$section] = self::check($section, $resolver);
        }

        return $valid;
    }
}
