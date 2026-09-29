<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal\Presenters;

use function expect;
use function it;

use Modules\Design\View\Tone;
use Modules\Kernel\Api\HowTheLineIsShared;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Remarks;
use Modules\Kernel\Api\WhereTheLineStands;
use Modules\Operator\Internal\Presenters\HowTheLineReads;

/**
 * Every place the line can stand has the glyph an operator reads it by.
 *
 * Written out case by case rather than sampled, so a place added to the enum
 * without a glyph fails here by name as well as at the match.
 */
it('draws each place the line can stand with its own tone', function (WhereTheLineStands $stands, Tone $tone): void {
    $line = new HowTheLineReads()->this(
        HowTheLineIsShared::standing($stands, 'What it means', 'No limit', 'No limit', Remarks::of(), Remarks::of()),
        Instant::atEpochSeconds(1_790_150_000),
    );

    expect($line->tone)->toBe($tone->value);
})->with([
    'unlimited' => [WhereTheLineStands::Unlimited, Tone::Fine],
    'limited' => [WhereTheLineStands::Limited, Tone::Fine],
    'held back while the house is up' => [WhereTheLineStands::ScheduledActive, Tone::Fine],
    'the stack\'s while the house sleeps' => [WhereTheLineStands::ScheduledQuiet, Tone::Fine],
    'limits lifted for now' => [WhereTheLineStands::Overridden, Tone::Fine],
    'close to the cap' => [WhereTheLineStands::CapWarning, Tone::Attention],
    'the cap reached' => [WhereTheLineStands::CapExceeded, Tone::Attention],
]);

it('reads a line nobody could ask about as unknown', function (): void {
    expect(new HowTheLineReads()->met(Obstacle::StackDidNotAnswer)->tone)->toBe(Tone::Unknown->value)
        ->and(new HowTheLineReads()->signedOut()->tone)->toBe(Tone::Unknown->value);
});
