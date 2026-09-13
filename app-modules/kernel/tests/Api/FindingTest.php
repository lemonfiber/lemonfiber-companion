<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_map;
use function expect;
use function implode;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\Category;
use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\CheckSaidNothing;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Conclusion;
use Modules\Kernel\Api\Finding;
use Modules\Kernel\Api\FindingHasNoTitle;
use Modules\Kernel\Api\Remedies;
use Modules\Kernel\Api\Remedy;
use Modules\Kernel\Api\Severity;
use Modules\Kernel\Api\Standing;
use Modules\Kernel\Api\WhatTheCheckSaid;

use function sprintf;

function aFinding(string $title = 'Torrent traffic leaves through the tunnel'): Finding
{
    return Finding::of(
        Check::of('vpn.egress-match'),
        Category::Vpn,
        $title,
        Conclusion::Passed,
        WhatTheCheckSaid::nothingWrong(),
    );
}

it('carries every field a row shows', function (): void {
    $finding = aFinding();

    expect($finding->check()->shown())->toBe('vpn.egress-match')
        ->and($finding->category())->toBe(Category::Vpn)
        ->and($finding->title())->toBe('Torrent traffic leaves through the tunnel')
        ->and($finding->conclusion())->toBe(Conclusion::Passed);
});

it('trims the title before carrying it', function (): void {
    expect(aFinding("  Torrent traffic leaves through the tunnel \n")->title())
        ->toBe('Torrent traffic leaves through the tunnel');
});

it('refuses a finding with no title', function (): void {
    expect(fn(): Finding => aFinding(''))->toThrow(FindingHasNoTitle::class);
});

it('refuses a title that is only whitespace', function (): void {
    expect(fn(): Finding => aFinding('   '))->toThrow(FindingHasNoTitle::class);
});

it('names the check when it refuses, because that is what a report is searched by', function (): void {
    expect(fn(): Finding => aFinding(''))
        ->toThrow(FindingHasNoTitle::class, 'vpn.egress-match arrived with no title');
});

it('N2-R3 — a finding carries the code, the meaning and the remedy the core produced', function (): void {
    // All three arrived on the wire and all three were dropped: the doctor
    // envelope carries them on the verdict, and the reader took the outcome tag
    // and said so in as many words. A screen could show that a check failed and
    // nothing about what or what to do.
    $said = WhatTheCheckSaid::wentWrong(
        Code::of('VPN-EGRESS-MISMATCH'),
        'Traffic is leaving on your own address rather than the tunnel.',
        Remedies::of(Remedy::of('restart the tunnel')),
        Severity::Error,
        Standing::Remediable,
    );

    $shown = Finding::of(
        Check::of('vpn.egress-match'),
        Category::Vpn,
        'Egress does not match',
        Conclusion::Failed,
        $said,
    )->said()->either(
        nothingWrong: static fn(): Code => Code::of('nothing-wrong'),
        wentWrong: static fn(Code $code, string $meaning, Remedies $remedies): Code => Code::of(sprintf(
            '%s|%s|%s',
            $code->shown(),
            $meaning,
            implode(',', array_map(
                static fn(Remedy $remedy): string => $remedy->action(),
                iterator_to_array($remedies->likeliest(), preserve_keys: false),
            )),
        )),
        couldNotSay: static fn(string $reason, Remedies $remedies): Code => Code::of(
            sprintf('could-not-say|%s|%d', $reason, $remedies->count()),
        ),
    );

    expect($shown->shown())->toBe(
        'VPN-EGRESS-MISMATCH|Traffic is leaving on your own address rather than the tunnel.|restart the tunnel',
    );
});

it('N2-R3 — a check that passed carries none of it, and says so in its own arm', function (): void {
    // A type with three nullable fields would make a screen ask three questions
    // to find out which situation it is in, and the screen that asks two of them
    // renders a passing check as a failure with blank text.
    $shown = Finding::of(
        Check::of('vpn.egress-match'),
        Category::Vpn,
        'Egress matches',
        Conclusion::Passed,
        WhatTheCheckSaid::nothingWrong(),
    )->said()->either(
        nothingWrong: static fn(): Code => Code::of('nothing-wrong'),
        wentWrong: static fn(Code $code, string $meaning, Remedies $remedies): Code => Code::of(sprintf(
            '%s|%s|%d',
            $code->shown(),
            $meaning,
            $remedies->count(),
        )),
        couldNotSay: static fn(string $reason, Remedies $remedies): Code => Code::of(
            sprintf('could-not-say|%s|%d', $reason, $remedies->count()),
        ),
    );

    expect($shown->shown())->toBe('nothing-wrong');
});

it('N2-R3 — a failure that says nothing is refused rather than shown', function (): void {
    // A red row with no sentence is one the operator cannot act on and cannot
    // search for. A core producing one has a fault, and the fault should be
    // visible where the payload is read rather than on somebody's screen at the
    // moment they most need a sentence.
    expect(fn(): WhatTheCheckSaid => WhatTheCheckSaid::wentWrong(
        Code::of('VPN-EGRESS-MISMATCH'),
        '   ',
        Remedies::none(),
        Severity::Error,
        Standing::Guided,
    ))->toThrow(CheckSaidNothing::class);
});
