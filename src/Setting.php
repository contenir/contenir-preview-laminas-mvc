<?php

declare(strict_types=1);

namespace Contenir\Preview\Laminas\Mvc;

use function is_string;
use function sprintf;

/**
 * One text setting from the `contenir_preview` configuration, checked.
 *
 * @internal
 */
final class Setting
{
    /**
     * @param array<array-key, mixed> $options
     * @return non-empty-string|null
     * @throws InvalidConfiguration
     */
    public static function optionalText(array $options, string $key, string $name): ?string
    {
        /** @var mixed $value */
        $value = $options[$key] ?? null;
        if (null !== $value && (! is_string($value) || '' === $value)) {
            throw new InvalidConfiguration(sprintf('%s must be a non-empty string.', $name));
        }

        return $value;
    }

    /**
     * @param array<array-key, mixed> $options
     * @throws InvalidConfiguration
     */
    public static function text(array $options, string $key, string $name): string
    {
        return (
            self::optionalText($options, $key, $name)
                ?? throw new InvalidConfiguration(sprintf('%s must be a non-empty string.', $name))
        );
    }
}
