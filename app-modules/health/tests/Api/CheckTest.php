<?php

declare(strict_types=1);

namespace Modules\Health\Tests\Api;

use function expect;
use function it;

use Modules\Health\Api\Check;
use Modules\Health\Api\CheckIsUnnamed;

it('carries the identifier the server sent', function (): void {
    expect(Check::of('vpn.egress-match')->shown())->toBe('vpn.egress-match');
});

it('is shown without the whitespace around it', function (): void {
    expect(Check::of("  vpn.egress-match\n")->shown())->toBe('vpn.egress-match');
});

it('refuses a check with no identifier', function (): void {
    expect(fn(): Check => Check::of(''))->toThrow(CheckIsUnnamed::class);
});

it('refuses an identifier that is only whitespace', function (): void {
    expect(fn(): Check => Check::of('  '))->toThrow(CheckIsUnnamed::class);
});

it('says what an unnamed check costs', function (): void {
    expect(fn(): Check => Check::of(''))
        ->toThrow(CheckIsUnnamed::class, 'nothing can recognise it between runs');
});

it('is the same check when the identifier is the same', function (): void {
    expect(Check::of('vpn.egress-match')->is(Check::of('vpn.egress-match')))->toBeTrue();
});

it('is a different check when the identifier differs', function (): void {
    expect(Check::of('vpn.egress-match')->is(Check::of('storage.one-filesystem')))->toBeFalse();
});
