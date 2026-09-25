<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowFarItGot;
use Modules\Kernel\Api\HowMuchOfItIsHere;
use Modules\Kernel\Api\HowSureTheTraceIs;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackIsUnidentified;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Stage;
use Modules\Kernel\Api\TheGlossary;
use Modules\Kernel\Api\TheMomentsInItsHistory;
use Modules\Kernel\Api\TheStagesItReached;
use Modules\Kernel\Api\WhatTheTraceFound;
use Modules\Kernel\Api\WhatToFollow;
use Modules\Kernel\Api\WhereItGotTo;
use Modules\Kernel\Api\WhereTheServicesDisagree;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\WhereThisGotTo;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AStackThatExplainsItsWords;
use Tests\Support\Fakes\AStackThatTraces;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\TracesToFollow;
use Tests\Support\WhatTheDeviceWouldDraw;

// Where one item got to.
//
// Here rather than in the operator module's own tests because a screen renders,
// and rendering needs the application.

/** The machine this screen is about. */
function theStackWhoseItemIsFollowed(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** A film that arrived, followed with certainty, with nothing else to say. */
function aFilmThatArrived(): WhereItGotTo
{
    return WhereItGotTo::followed('Dune', WhatTheTraceFound::traced(
        HowSureTheTraceIs::Certain,
        HowFarItGot::reached(Stage::Available, TheStagesItReached::of(), ''),
        TheMomentsInItsHistory::of(),
        WhereTheServicesDisagree::of(),
        HowMuchOfItIsHere::aWholeItem(),
    ));
}

/** The screen, following what the route names. Named for this file (`G10`). */
function theTraceScreen(AStackThatTraces $tracing, ?AKeychainInMemory $keychain = null, bool $signedIn = true, string $named = 'Severance'): WhereThisGotTo
{
    $stack = theStackWhoseItemIsFollowed();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new WhereThisGotTo($tracing, AStackThatExplainsItsWords::with(TheGlossary::of()), $keychain, StacksInMemory::holding($stack));
    $screen->setParams(['stack' => $stack->id()->stored(), 'service' => $named]);

    return $screen;
}

it('follows what the route names, and says how sure it is before anything else', function (): void {
    $tracing = AStackThatTraces::with(TracesToFollow::aSeriesStuckDownloading());
    $drawn = WhatTheDeviceWouldDraw::by(theTraceScreen($tracing))->said();

    expect($tracing->followed())->toBe('Severance')
        ->and($drawn)->toContain(__('health.trace.following', ['item' => 'Severance']))
        ->and(array_keys($drawn, __(HowSureTheTraceIs::Uncertain->saidOnTheScreen()), strict: true))->toBe([5])
        ->and(array_keys($drawn, __('health.trace.following', ['item' => 'Severance']), strict: true))->toBe([4])
        ->and(array_keys($drawn, __('health.trace.furthest', ['stage' => 'downloading']), strict: true))->toBe([6]);
});

it('draws the furthest stage as the stack\'s word with the sentence beside it, and why it stopped', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theTraceScreen(AStackThatTraces::with(TracesToFollow::aSeriesStuckDownloading())))->said();

    expect($drawn)->toContain(__('health.trace.furthest', ['stage' => 'downloading']))
        ->and($drawn)->toContain(__(Stage::Downloading->saidOnTheScreen()))
        ->and($drawn)->toContain(__('health.trace.stopped', ['why' => 'No peers have been seen for two days']));
});

it('shows a series season by season, with every outstanding episode at its stage', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theTraceScreen(AStackThatTraces::with(TracesToFollow::aSeriesStuckDownloading())))->said();

    expect($drawn)->toContain(__('health.trace.series_here', ['have' => 8, 'wanted' => 10]))
        ->and($drawn)->toContain(trans_choice('health.trace.nobody_asked_for', 1))
        ->and($drawn)->toContain(__('health.trace.season', ['season' => 1, 'have' => 8, 'wanted' => 9]))
        ->and($drawn)->toContain(__('health.trace.episode', ['number' => 9, 'title' => 'The We We Are', 'stage' => 'downloading']))
        ->and($drawn)->toContain(__('health.trace.episode', ['number' => 1, 'title' => 'Hello, Ms. Cobel', 'stage' => 'searching']));
});

it('shows the way it came, what was tried in order, and where the services disagree', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theTraceScreen(AStackThatTraces::with(TracesToFollow::aSeriesStuckDownloading())))->said();
    $failed = array_keys($drawn, __('health.trace.outcome.download-failed'), strict: true);

    expect($drawn)->toContain(__('health.trace.recorded_untimed', ['service' => 'sonarr']))
        ->and($drawn)->toContain(__('health.trace.recorded_at', ['service' => 'qbittorrent', 'at' => '2026-09-20T10:01:00Z']))
        ->and($failed)->toHaveCount(1)
        ->and(array_slice($drawn, $failed[0] ?? 0, 2))->toBe([__('health.trace.outcome.download-failed'), '2026-09-19T21:00:00Z'])
        ->and($drawn)->toContain('Jellyfin holds an episode no service is watching for');
});

it('says so where nothing was tried, the services agree, and a film has no seasons to count', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theTraceScreen(AStackThatTraces::with(aFilmThatArrived()), named: 'Dune'))->said();

    expect($drawn)->toContain(__('health.trace.nothing_tried'))
        ->and($drawn)->toContain(__('health.trace.agree'))
        ->and($drawn)->toContain(__('health.trace.no_stages'))
        ->and($drawn)->toContain(__(HowSureTheTraceIs::Certain->saidOnTheScreen()))
        ->and(implode("\n", $drawn))->not->toContain(__('health.trace.series_here', ['have' => 0, 'wanted' => 0]));
});

it('tells nobody asking for it apart from a trace that could not be read', function (): void {
    $nobody = WhatTheDeviceWouldDraw::by(theTraceScreen(AStackThatTraces::with(WhereItGotTo::nothingAskedFor('Severance'))))->said();
    $unread = theTraceScreen(AStackThatTraces::met(Obstacle::StackDidNotAnswer))->answer();

    expect($nobody)->toContain(__('health.trace.nothing_asked_for', ['item' => 'Severance']))
        ->and($unread->went->cameBack())->toBeFalse()
        ->and($unread->went->met)->toBe(Obstacle::StackDidNotAnswer->said())
        ->and($unread->item)->toBe('Severance');
});

it('follows what was typed instead, once asked to, and ignores a blank', function (): void {
    $tracing = AStackThatTraces::with(aFilmThatArrived());
    $screen = theTraceScreen($tracing);
    $screen->answer();

    $screen->looking = ' ';
    $screen->follow();
    $screen->answer();

    expect($tracing->followed())->toBe('Severance');

    $screen->looking = ' Dune ';
    $screen->follow();
    $screen->answer();

    expect($tracing->followed())->toBe('Dune')
        ->and($screen->looking)->toBe('')
        ->and($tracing->askings())->toBe(2);
});

it('asks nothing where nothing is named, and says what to do', function (): void {
    $tracing = AStackThatTraces::with(aFilmThatArrived());
    $drawn = WhatTheDeviceWouldDraw::by(theTraceScreen($tracing, named: ' '))->said();

    expect($drawn)->toContain(__('health.trace.nothing_named'))
        ->and($tracing->askings())->toBe(0);
});

it('asks once a frame, and again when asked', function (): void {
    $tracing = AStackThatTraces::with(aFilmThatArrived());
    $screen = theTraceScreen($tracing);

    $screen->answer();
    $screen->answer();
    $screen->again();
    $screen->answer();

    expect($tracing->askings())->toBe(2)
        ->and($tracing->wasGivenASession())->toBeTrue()
        ->and($tracing->askedAbout()?->id()->stored())->toBe(theStackWhoseItemIsFollowed()->id()->stored());
});

it('a session that has ended asks nothing, and a refused one is let go', function (): void {
    $tracing = AStackThatTraces::with(aFilmThatArrived());
    $keychain = AKeychainInMemory::working();

    expect(theTraceScreen($tracing, signedIn: false)->answer()->went->isSignedIn)->toBeFalse()
        ->and($tracing->askings())->toBe(0)
        ->and(theTraceScreen(AStackThatTraces::met(Obstacle::CredentialWasRefused), $keychain)->answer()->went->isSignedIn)->toBeFalse()
        ->and($keychain->isHolding(theStackWhoseItemIsFollowed()->id()))->toBeFalse();
});

it('refuses a route parameter that is not text, and reads a term that is not text as nothing named', function (): void {
    $screen = theTraceScreen(AStackThatTraces::with(aFilmThatArrived()));
    $screen->setParams(['stack' => 42, 'service' => 'Dune']);

    expect(fn(): Stack => $screen->stack())->toThrow(StackIsUnidentified::class);

    $screen->setParams(['stack' => theStackWhoseItemIsFollowed()->id()->stored(), 'service' => 7]);

    expect($screen->answer()->item)->toBe('');
});

it('is reached by a route carrying the title as it was drawn, and goes back to health', function (): void {
    $screen = theTraceScreen(AStackThatTraces::with(aFilmThatArrived()));

    expect(NativeRouter::resolve($screen->goes()->traceOf(WhatToFollow::called('AC/DC: Live'))))->toHaveKey('params.service', 'AC/DC: Live')
        ->and(NativeRouter::resolve($screen->goes()->health()))->not->toBeNull()
        ->and($screen->render()->name())->toBe('operator::where-this-got-to');
});
