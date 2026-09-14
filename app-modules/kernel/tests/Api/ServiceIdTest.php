<?php

declare(strict_types=1);

use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\ServiceIsUnnamed;

it('N2-R10 — carries the name exactly as the stack spells it', function (): void {
    // Never title-cased for display: a service called `gluetun` shown as
    // *Gluetun* is a name that works nowhere else — not in a log read, not in a
    // terminal, not against a finding.
    expect(ServiceId::called('gluetun')->named())->toBe('gluetun');
});

it('keeps the name, less the whitespace around it', function (): void {
    expect(ServiceId::called("  sonarr\n")->named())->toBe('sonarr');
});

it('refuses a service named as nothing at all', function (): void {
    // A window over no service is the whole machine talking at once, which is
    // what a terminal is for.
    expect(fn(): ServiceId => ServiceId::called('   '))
        ->toThrow(ServiceIsUnnamed::class, 'nothing at all');
});

it('says whether two names are the same service', function (): void {
    // The comparison lives on the type so a screen matching a log window
    // against the finding that sent it there cannot answer it differently from
    // the next screen.
    expect(ServiceId::called('gluetun')->isTheSameAs(ServiceId::called('gluetun')))->toBeTrue()
        ->and(ServiceId::called('gluetun')->isTheSameAs(ServiceId::called('sonarr')))->toBeFalse();
});

it('the trim happens before the comparison, not after', function (): void {
    expect(ServiceId::called(' gluetun ')->isTheSameAs(ServiceId::called('gluetun')))->toBeTrue();
});
