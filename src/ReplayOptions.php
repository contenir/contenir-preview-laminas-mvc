<?php

declare(strict_types=1);

namespace Contenir\Preview\Laminas\Mvc;

use function is_array;

/**
 * Where accepted tokens are remembered (`contenir_preview.replay`): a PSR-16 cache service the
 * site's servers share, or a directory of files, the system's temporary directory by default.
 */
final readonly class ReplayOptions
{
    private function __construct(
        public ?string $cache,
        public ?string $directory,
    ) {}

    /**
     * @throws InvalidConfiguration
     */
    public static function from(mixed $replay): self
    {
        if (! is_array($replay)) {
            throw new InvalidConfiguration('contenir_preview.replay must be an array.');
        }

        return new self(
            Setting::optionalText($replay, 'cache', 'contenir_preview.replay.cache'),
            Setting::optionalText($replay, 'directory', 'contenir_preview.replay.directory'),
        );
    }
}
