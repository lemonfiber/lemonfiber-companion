<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ASettingHasNoName;
use Modules\Kernel\Api\ASettingToReveal;

it('names a setting without the spaces typed around it', function (): void {
    expect(ASettingToReveal::named('  SONARR_URL ')->name())->toBe('SONARR_URL');
});

it('refuses a setting with a blank name', function (string $blank): void {
    expect(static fn(): ASettingToReveal => ASettingToReveal::named($blank))
        ->toThrow(ASettingHasNoName::class, 'A blank name names no setting');
})->with(['nothing' => [''], 'spaces' => ['   ']]);

it('is the same setting where the names are the same', function (): void {
    expect(ASettingToReveal::named('SONARR_URL')->is(ASettingToReveal::named(' SONARR_URL')))->toBeTrue()
        ->and(ASettingToReveal::named('SONARR_URL')->is(ASettingToReveal::named('RADARR_URL')))->toBeFalse();
});
