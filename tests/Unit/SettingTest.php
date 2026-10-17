<?php

declare(strict_types=1);

namespace Contenir\Preview\Laminas\Mvc\Tests\Unit;

use Contenir\Preview\Laminas\Mvc\InvalidConfiguration;
use Contenir\Preview\Laminas\Mvc\Setting;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * One text setting, required or optional.
 */
#[Group('unit')]
final class SettingTest extends TestCase
{
    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function invalidText(): array
    {
        return [
            'empty'    => [['issuer' => '']],
            'a number' => [['issuer' => 42]],
            'a list'   => [['issuer' => ['https://admin.example.test']]],
        ];
    }

    #[Test]
    public function leavesAnOptionalSettingOut(): void
    {
        $this->assertNull(Setting::optionalText([], 'clock', 'contenir_preview.clock'));
    }

    #[Test]
    public function readsText(): void
    {
        $this->assertSame('AppClock', Setting::optionalText(
            ['clock' => 'AppClock'],
            'clock',
            'contenir_preview.clock',
        ));
    }

    /**
     * @param array<string, mixed> $options
     */
    #[Test]
    #[DataProvider('invalidText')]
    public function refusesAnythingButText(array $options): void
    {
        $this->expectException(InvalidConfiguration::class);
        $this->expectExceptionMessage('contenir_preview.issuer must be a non-empty string.');

        Setting::optionalText($options, 'issuer', 'contenir_preview.issuer');
    }

    #[Test]
    public function requiresARequiredSetting(): void
    {
        $this->expectException(InvalidConfiguration::class);
        $this->expectExceptionMessage('contenir_preview.issuer must be a non-empty string.');

        Setting::text([], 'issuer', 'contenir_preview.issuer');
    }
}
