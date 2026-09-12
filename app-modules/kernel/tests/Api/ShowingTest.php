<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function get_class_methods;
use function it;

use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Findings;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Reading;
use Modules\Kernel\Api\Showing;

use function sprintf;

const WHEN_IT_WAS_READ = 1_757_000_000;

/**
 * Which arm answered, as a word.
 *
 * Named for this file rather than `fold`, because a module's test files share
 * one namespace and two of the same name are a fatal the moment both load (G10).
 */
function whatIsOnTheFrame(Showing $showing): string
{
    return $showing->either(
        waiting: static fn(): Code => Code::of('a spinner'),
        holding: static fn(Reading $reading): Code => $reading->either(
            live: static fn(object $value): Code => Code::of(sprintf('live:%s', $value::class)),
            retained: static fn(object $value, Instant $at): Code => Code::of(sprintf(
                'retained:%s:%d',
                $value::class,
                $at->epochSeconds(),
            )),
        ),
    )->shown();
}

it('N1-R28 — a progress indicator answers only where the app holds nothing', function (): void {
    expect(whatIsOnTheFrame(Showing::waiting()))->toBe('a spinner');
});

it('N1-R28 — a retained reading is shown rather than covered by a spinner', function (): void {
    // The common failure and the one the requirement names. A screen that has a
    // number from yesterday and paints a spinner over it while refreshing is not
    // being cautious — it throws away the only thing it had, and the operator
    // watches an empty screen for as long as a stack takes to answer.
    //
    // ADR-0019 publishes the frame before the read is issued, so the value is
    // there at paint time: the spinner is a decision, not a consequence.
    $remembered = Reading::retained(Findings::none(), Instant::atEpochSeconds(WHEN_IT_WAS_READ));

    expect(whatIsOnTheFrame(Showing::holding($remembered)))->toBe(sprintf('retained:%s:%d', Findings::class, WHEN_IT_WAS_READ));
});

it('N1-R28 — a live reading is shown as itself', function (): void {
    expect(whatIsOnTheFrame(Showing::holding(Reading::live(Findings::none()))))->toBe(sprintf('live:%s', Findings::class));
});

it('has no way to reach the reading without saying what happens when there is none', function (): void {
    // `Reading`'s own reason, one level out: a check-then-get pair puts the
    // check where it can be forgotten, and the forgotten one here is a spinner
    // painted over a value somebody already had.
    expect(get_class_methods(Showing::class))->toBe(['waiting', 'holding', 'either']);
});
