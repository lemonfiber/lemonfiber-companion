<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\AMonthlyCap;
use Modules\Kernel\Api\HowTheLineIsShared;
use Modules\Kernel\Api\HowTheLineWasMeasured;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\LineSaysNothing;
use Modules\Kernel\Api\Remark;
use Modules\Kernel\Api\Remarks;
use Modules\Kernel\Api\WhatACapDoes;
use Modules\Kernel\Api\WhatTheLineCarries;
use Modules\Kernel\Api\WhereTheLineStands;
use Modules\Kernel\Api\WhetherItGoesThroughTheTunnel;

use function sprintf;

/** One line carried out of an arm. */
final readonly class WhatTheLineWasSaidToBe
{
    public function __construct(public string $said) {}
}

/** A limited line and nothing else known about it. */
function aLimitedLine(): HowTheLineIsShared
{
    return HowTheLineIsShared::standing(WhereTheLineStands::Limited, 'The stack takes a share', 'Down: half of 100 Mbit/s', 'Up: a quarter of 20 Mbit/s', Remarks::of('Measured at night'), Remarks::of('Plex streams'));
}

/** Every optional arm of a reading, as one line. */
function everyArmOf(HowTheLineIsShared $line): string
{
    return sprintf(
        '%s|%s|%s|%s',
        $line->capacity(static fn(WhatTheLineCarries $c): WhatTheLineWasSaidToBe => new WhatTheLineWasSaidToBe(sprintf('%d/%d', $c->down(), $c->up())), static fn(): WhatTheLineWasSaidToBe => new WhatTheLineWasSaidToBe('unmeasured'))->said,
        $line->cap(static fn(AMonthlyCap $c): WhatTheLineWasSaidToBe => new WhatTheLineWasSaidToBe(sprintf('%d', $c->monthly())), static fn(): WhatTheLineWasSaidToBe => new WhatTheLineWasSaidToBe('uncapped'))->said,
        $line->spentCap(static fn(string $doing): WhatTheLineWasSaidToBe => new WhatTheLineWasSaidToBe($doing), static fn(): WhatTheLineWasSaidToBe => new WhatTheLineWasSaidToBe('unspent'))->said,
        $line->uploadCost(static fn(string $costs): WhatTheLineWasSaidToBe => new WhatTheLineWasSaidToBe($costs), static fn(): WhatTheLineWasSaidToBe => new WhatTheLineWasSaidToBe('no upload limit'))->said,
    );
}

it('carries where the line stands, what it means, each direction\'s sentence and what surrounds them', function (): void {
    $line = aLimitedLine();

    expect($line->stands())->toBe(WhereTheLineStands::Limited)
        ->and($line->means())->toBe('The stack takes a share')
        ->and($line->downSays())->toBe('Down: half of 100 Mbit/s')
        ->and($line->upSays())->toBe('Up: a quarter of 20 Mbit/s')
        ->and(iterator_to_array($line->cautions(), preserve_keys: false))->toBe(['Measured at night'])
        ->and(iterator_to_array($line->untouched(), preserve_keys: false))->toBe(['Plex streams']);
});

it('N10-R7 — knows nothing it was not told: unmeasured, uncapped, nothing spent, no upload cost', function (): void {
    expect(everyArmOf(aLimitedLine()))->toBe('unmeasured|uncapped|unspent|no upload limit');
});

it('carries each thing it is told, and keeps the rest', function (): void {
    $line = aLimitedLine()
        ->measuredAt(WhatTheLineCarries::measured(12, 3, HowTheLineWasMeasured::Declared, Instant::atEpochSeconds(1), WhetherItGoesThroughTheTunnel::Beside))
        ->cappedAt(AMonthlyCap::of(0, WhatACapDoes::Pause))
        ->withASpentCapDoing(Remark::said('Fetching has stopped until the 1st', 'acting'))
        ->withUploadCosting(Remark::said('Seeding back at a quarter slows the ratio', 'ratio'));

    expect(everyArmOf($line))->toBe('12/3|0|Fetching has stopped until the 1st|Seeding back at a quarter slows the ratio')
        ->and($line->stands())->toBe(WhereTheLineStands::Limited)
        ->and($line->means())->toBe('The stack takes a share');
});

it('refuses a reading with a blank where a sentence belongs, naming which', function (string $field): void {
    $words = ['means' => 'The stack takes a share', 'down' => 'Down', 'up' => 'Up', $field => ' '];

    expect(fn(): HowTheLineIsShared => HowTheLineIsShared::standing(WhereTheLineStands::Unlimited, $words['means'], $words['down'], $words['up'], Remarks::of(), Remarks::of()))
        ->toThrow(LineSaysNothing::class, sprintf('`%s`', $field));
})->with(['means', 'down', 'up']);
