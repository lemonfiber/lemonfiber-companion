<?php

declare(strict_types=1);

use Modules\Kernel\Api\ACopyRunning;
use Modules\Kernel\Api\Findings;
use Modules\Kernel\Api\HowTheCopyMoves;
use Modules\Kernel\Api\HowTheCopyRunningWasRead;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Overall;
use Modules\Kernel\Api\Report;
use Modules\Kernel\Api\ScopeOfACopy;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\WhatAStreamSaid;
use Modules\Kernel\Api\WhatIsBeingCopied;
use Modules\Kernel\Api\WhatIsCopied;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Presenters\HowTheCopyRunningReads;
use Modules\Operator\Internal\Screens\HowThisStackIs;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AStackThatCopies;
use Tests\Support\Fakes\AStackThatWasAsked;
use Tests\Support\TheHealthScreenOfTheLoft;
use Tests\Support\WhatTheDeviceWouldDraw;

// A stack's home saying, in one row that leads to Backups, that a backup or
// restore runs, from what the stack's stream says alone.

/** The moment the home screens here are drawn at, as the listening clock holds it. Named for this file (`G10`). */
const WHEN_THE_HOME_IS_DRAWN = 1_790_000_000;

/**
 * A copy of the whole stack, a quarter written, moving as a case says.
 *
 * @return WhatAStreamSaid<WhatIsBeingCopied>
 */
function aCopyTheStreamSays(HowTheCopyMoves $moves, WhatIsCopied $what = WhatIsCopied::Backup): WhatAStreamSaid
{
    return WhatAStreamSaid::said(WhatIsBeingCopied::running(new ACopyRunning($what, ScopeOfACopy::theWholeStack(), Instant::atEpochSeconds(WHEN_THE_HOME_IS_DRAWN - 600), 536_870_912, 2_147_483_648, 'sonarr database', $moves)));
}

/**
 * A stack whose stream says these in turn, and whose read nobody should take.
 *
 * @param WhatAStreamSaid<WhatIsBeingCopied> ...$said
 */
function aStackWhoseStreamSays(WhatAStreamSaid ...$said): AStackThatCopies
{
    return AStackThatCopies::holdingOpen(HowTheCopyRunningWasRead::said(WhatIsBeingCopied::nothing()), ...$said);
}

function theHomeOfAStackCopying(AStackThatCopies $running, ?AKeychainInMemory $keychain = null): HowThisStackIs
{
    return TheHealthScreenOfTheLoft::theHealthScreen(AStackThatWasAsked::saying(Report::of(Overall::Healthy, Findings::of())), $keychain, running: $running);
}

/** @param array<string, string> $with */
function aLineOfTheHome(string $key, array $with = []): string
{
    $said = __($key, $with);

    return is_string($said) ? $said : $key;
}

/**
 * What a reader hears for the row headed by these words, or nothing where no row is.
 *
 * @param array<mixed> $node
 */
function whatAReaderHearsForTheRow(array $node, string $headline): mixed
{
    if (data_get($node, 'props.headline') === $headline) {
        return data_get($node, 'props.a11y_label');
    }

    foreach ($node as $child) {
        $heard = is_array($child) ? whatAReaderHearsForTheRow($child, $headline) : null;

        if ($heard !== null) {
            return $heard;
        }
    }

    return null;
}

function theWholeStackBeingCopied(WhatIsCopied $what = WhatIsCopied::Backup): string
{
    return aLineOfTheHome($what->saidOnTheScreen(), ['scope' => aLineOfTheHome('stacks.copy.scope.whole_stack')]);
}

it('draws nothing of a copy before the stream says one runs, and never reads it', function (): void {
    $running = aStackWhoseStreamSays();
    $screen = theHomeOfAStackCopying($running);
    $drawn = WhatTheDeviceWouldDraw::onTheThirdFrame($screen)->said();
    $screen->followTheCopy();

    expect($drawn)->not->toContain(theWholeStackBeingCopied(), aLineOfTheHome(HowTheCopyRunningReads::CANNOT_TELL), aLineOfTheHome(HowTheCopyRunningReads::NONE))
        ->and(WhatTheDeviceWouldDraw::onTheThirdFrame($screen)->said())->not->toContain(theWholeStackBeingCopied())
        ->and($running->read())->toBe(0)
        ->and($running->asked())->toBe(1);
});

it('says a copy the stream says runs in one row that leads on: of what, how far, and how it moves', function (): void {
    $screen = theHomeOfAStackCopying(aStackWhoseStreamSays(aCopyTheStreamSays(HowTheCopyMoves::working())));
    WhatTheDeviceWouldDraw::onTheThirdFrame($screen);
    $screen->followTheCopy();
    $soFar = aLineOfTheHome('stacks.copy.running.so_far', ['percent' => '25', 'moves' => aLineOfTheHome(HowTheCopyRunningReads::WORKING)]);

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(theWholeStackBeingCopied(), $soFar)
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->toContain(theWholeStackBeingCopied())
        ->and(whatAReaderHearsForTheRow(WhatTheDeviceWouldDraw::tree($screen), theWholeStackBeingCopied()))->toBe(sprintf('%s. %s. %s', theWholeStackBeingCopied(), $soFar, aLineOfTheHome('stacks.copy.running.see_it')));
});

it('says a restore and a stuck copy as the stream says each', function (): void {
    $restore = theHomeOfAStackCopying(aStackWhoseStreamSays(aCopyTheStreamSays(HowTheCopyMoves::working(), WhatIsCopied::Restore)));
    $stuck = theHomeOfAStackCopying(aStackWhoseStreamSays(aCopyTheStreamSays(HowTheCopyMoves::stalled(Instant::atEpochSeconds(WHEN_THE_HOME_IS_DRAWN - 420)))));

    foreach ([$restore, $stuck] as $screen) {
        WhatTheDeviceWouldDraw::onTheThirdFrame($screen);
        $screen->followTheCopy();
    }

    expect(WhatTheDeviceWouldDraw::by($restore)->said())->toContain(theWholeStackBeingCopied(WhatIsCopied::Restore))
        ->and(WhatTheDeviceWouldDraw::by($stuck)->said())->toContain(aLineOfTheHome('stacks.copy.running.so_far', ['percent' => '25', 'moves' => trans_choice('stacks.copy.running.stalled.minutes', 7)]))
        ->and($stuck->theCopyTheStreamSaysRuns()?->tone)->toBe('trouble');
});

it('takes the row away once the stream says the copy ended, or stops vouching for it', function (WhatAStreamSaid $after): void {
    $screen = theHomeOfAStackCopying(aStackWhoseStreamSays(aCopyTheStreamSays(HowTheCopyMoves::working()), $after));
    WhatTheDeviceWouldDraw::onTheThirdFrame($screen);
    $screen->followTheCopy();
    $screen->followTheCopy();

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->not->toContain(theWholeStackBeingCopied())
        ->and($screen->theCopyTheStreamSaysRuns())->toBeNull();
})->with([
    'none runs' => [WhatAStreamSaid::said(WhatIsBeingCopied::nothing())],
    'the stream closed' => [WhatAStreamSaid::closed()],
]);

it('never asks with a member\'s session, and draws nothing of a copy', function (): void {
    $keychain = AKeychainInMemory::working();
    $running = aStackWhoseStreamSays(aCopyTheStreamSays(HowTheCopyMoves::working()));
    $screen = theHomeOfAStackCopying($running, $keychain);
    $keychain->keep(TheHealthScreenOfTheLoft::theStackBeingLookedAt()->id(), Session::of('a-session-not-a-secret'), Whose::member('a7f3'));
    $screen->followTheCopy();
    $screen->theCopyTheStreamSaysRuns();
    $screen->followTheCopy();

    expect($running->read())->toBe(0)
        ->and($running->asked())->toBe(0)
        ->and($screen->theCopyTheStreamSaysRuns())->toBeNull();
});

it('lets go of the copy\'s stream when the screen stops', function (): void {
    $running = aStackWhoseStreamSays(aCopyTheStreamSays(HowTheCopyMoves::working()));
    $screen = theHomeOfAStackCopying($running);
    WhatTheDeviceWouldDraw::onTheThirdFrame($screen);
    $screen->followTheCopy();
    $screen->stop();

    expect($running->lettingsGo())->toBe(1);
});

it('says how a copy of nothing moves without a share of it', function (): void {
    $screen = theHomeOfAStackCopying(aStackWhoseStreamSays(WhatAStreamSaid::said(WhatIsBeingCopied::running(new ACopyRunning(WhatIsCopied::Backup, ScopeOfACopy::theWholeStack(), Instant::atEpochSeconds(WHEN_THE_HOME_IS_DRAWN - 60), 0, 0, 'sonarr database', HowTheCopyMoves::working())))));
    WhatTheDeviceWouldDraw::onTheThirdFrame($screen);
    $screen->followTheCopy();

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(theWholeStackBeingCopied(), aLineOfTheHome(HowTheCopyRunningReads::WORKING));
});
