<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AnEditReverted;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\ConnectionsReverted;
use Modules\Kernel\Api\EditsReverted;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowTheResetIsGoing;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackIsUnidentified;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheReset;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\PuttingTheConfigurationBack;
use Modules\Operator\Internal\ViewModels\ADiffLineAsShown;
use Modules\Operator\Internal\ViewModels\AnEditAsShown;
use Modules\Operator\Internal\ViewModels\ARefusalAsShown;
use Modules\Operator\Internal\ViewModels\AResetAsShown;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AStackThatResets;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatAResetSays;
use Tests\Support\WhatTheDeviceWouldDraw;

// Putting the configuration back: the preview first, labelled as one and
// worded as what would happen; the yes, sent only after a preview that would
// revert something; and the stack's report of what went back, and which
// connections went with it.
//
// Here rather than in the operator module's own tests because a screen
// renders, and rendering needs the application.

/**
 * One line of the catalogue, as text.
 *
 * @param array<string, string> $with
 */
function aLineOfPuttingItAllBack(string $key, array $with = []): string
{
    $said = __($key, $with);

    return is_string($said) ? $said : $key;
}

/** The machine whose configuration goes back. */
function theStackWhoseConfigurationGoesBack(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** The screen, with a keychain holding whatever a case says. */
function thePuttingItAllBackScreen(
    AStackThatResets $resetting,
    ?AKeychainInMemory $keychain = null,
    bool $signedIn = true,
): PuttingTheConfigurationBack {
    $stack = theStackWhoseConfigurationGoesBack();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new PuttingTheConfigurationBack($resetting, $keychain, StacksInMemory::holding($stack));
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

/** A stack that previews the usual reset, and answers the yes with `$after`. */
function aStackPreviewingTheReset(?HowTheResetIsGoing $after = null): AStackThatResets
{
    return AStackThatResets::previewing(HowTheResetIsGoing::done(WhatAResetSays::previewed()), $after ?? HowTheResetIsGoing::stillRunning());
}

/** A preview that would revert nothing at all. */
function aPreviewRevertingNothing(): TheReset
{
    return TheReset::previewed(EditsReverted::these(), ConnectionsReverted::these());
}

/**
 * Every field of a reading, as drawn, in one comparable row.
 *
 * @return array<string, mixed>
 */
function everythingTheResetShows(AResetAsShown $shown): array
{
    return [
        'cameBack' => $shown->went->cameBack(),
        'isSignedIn' => $shown->went->isSignedIn,
        'met' => $shown->went->met,
        'isWorking' => $shown->isWorking,
        'hasEnded' => $shown->hasEnded,
        'refused' => $shown->refused instanceof ARefusalAsShown ? [$shown->refused->said, $shown->refused->meaning, $shown->refused->named] : null,
        'isReported' => $shown->isReported,
        'changesNothing' => $shown->changesNothing,
        'mayBeAgreedTo' => $shown->mayBeAgreedTo,
        'words' => [
            $shown->words->heading,
            $shown->words->files,
            $shown->words->noFile,
            $shown->words->noLine,
            $shown->words->connections,
            $shown->words->noConnection,
            $shown->words->nothing,
            $shown->words->working,
            $shown->words->ended,
            $shown->words->refusal,
        ],
        'edits' => array_map(
            static fn(AnEditAsShown $edit): array => [$edit->path, array_map(static fn(ADiffLineAsShown $line): array => [$line->said, $line->line], $edit->lines)],
            $shown->edits,
        ),
        'connections' => $shown->connections,
    ];
}

/**
 * The words of a preview, followed before the yes.
 *
 * @return list<string>
 */
function theWordsOfAPreview(): array
{
    return [
        'stacks.reset.a_preview',
        'stacks.reset.would_revert_files',
        'stacks.reset.would_revert_no_file',
        'stacks.reset.differs_in_no_line',
        'stacks.reset.would_revert_connections',
        'stacks.reset.would_revert_no_connection',
        'stacks.reset.would_change_nothing',
        'stacks.reset.asking',
        'stacks.reset.no_preview',
        'stacks.reset.refused_preview',
    ];
}

/**
 * The words of a report the stack says it carried out, followed after the yes.
 *
 * @return list<string>
 */
function theWordsOfAResetCarriedOut(): array
{
    return [
        'stacks.reset.put_back',
        'stacks.reset.reverted_files',
        'stacks.reset.reverted_no_file',
        'stacks.reset.differed_in_no_line',
        'stacks.reset.reverted_connections',
        'stacks.reset.reverted_no_connection',
        'stacks.reset.changed_nothing',
        'stacks.reset.putting_back',
        'stacks.reset.no_outcome',
        'stacks.reset.refused',
    ];
}

/**
 * A reading with no report in it, changed where a case says.
 *
 * @param  array<mixed> $changed
 * @return array<mixed>
 */
function nothingReportedOfTheReset(bool $afterTheYes, array $changed): array
{
    return [
        'cameBack' => true,
        'isSignedIn' => true,
        'met' => '',
        'isWorking' => false,
        'hasEnded' => false,
        'refused' => null,
        'isReported' => false,
        'changesNothing' => false,
        'mayBeAgreedTo' => false,
        'words' => $afterTheYes ? theWordsOfAResetCarriedOut() : theWordsOfAPreview(),
        'edits' => [],
        'connections' => [],
        ...$changed,
    ];
}

/**
 * The usual reset's files and connection, as drawn.
 *
 * @return array<string, mixed>
 */
function theUsualResetAsDrawn(): array
{
    return [
        'edits' => [
            ['compose.yaml', [['stacks.reset.theirs', 'image: sonarr:4.0.1'], ['stacks.reset.lemonfibers', 'image: sonarr:4.0.0']]],
            ['env/sonarr.env', []],
        ],
        'connections' => ['sonarr → qbittorrent'],
    ];
}

it('previews first, labelled as one, with every file\'s lines and every connection, worded as what would happen', function (): void {
    $resetting = aStackPreviewingTheReset();
    $screen = thePuttingItAllBackScreen($resetting);
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect(everythingTheResetShows($screen->answer()))->toBe(nothingReportedOfTheReset(afterTheYes: false, changed: [
        'isReported' => true,
        'mayBeAgreedTo' => true,
        ...theUsualResetAsDrawn(),
    ]))
        ->and($resetting->previewsAskedFor())->toBe(1)
        ->and($resetting->followed())->toBe([AStackThatResets::THE_PREVIEW])
        ->and($resetting->agreed())->toBe([])
        ->and($screen->wasAgreedTo())->toBeFalse()
        ->and($drawn->said())->toContain(__('stacks.reset.heading'))
        ->and($drawn->said())->toContain(__('stacks.reset.a_preview'))
        ->and($drawn->said())->toContain(__('stacks.reset.would_revert_files'))
        ->and($drawn->said())->toContain('compose.yaml')
        ->and($drawn->said())->toContain(__('stacks.reset.theirs', ['line' => 'image: sonarr:4.0.1']))
        ->and($drawn->said())->toContain(__('stacks.reset.lemonfibers', ['line' => 'image: sonarr:4.0.0']))
        ->and($drawn->said())->toContain('env/sonarr.env')
        ->and($drawn->said())->toContain(__('stacks.reset.differs_in_no_line'))
        ->and($drawn->said())->toContain(__('stacks.reset.would_revert_connections'))
        ->and($drawn->said())->toContain('sonarr → qbittorrent')
        ->and($drawn->said())->not->toContain(__('stacks.reset.put_back'))
        ->and($drawn->said())->not->toContain(__('stacks.reset.reverted_files'))
        ->and($drawn->offers())->toContain(__('stacks.reset.put_them_back'))
        ->and($drawn->offers())->toContain(__('health.ask_again'))
        ->and($drawn->offers())->not->toContain(__('stacks.reset.see_the_settings'));
});

it('marks the operator\'s line with a minus and lemonfiber\'s with a plus, in both languages', function (string $locale): void {
    app()->setLocale($locale);

    expect(aLineOfPuttingItAllBack('stacks.reset.theirs', ['line' => 'TZ=UTC']))->toBe('- TZ=UTC')
        ->and(aLineOfPuttingItAllBack('stacks.reset.lemonfibers', ['line' => 'TZ=UTC']))->toBe('+ TZ=UTC');
})->with(['en', 'nl']);

it('says a preview reverting no file, or no connection, says so', function (TheReset $previewed, string $said, string $notSaid): void {
    $screen = thePuttingItAllBackScreen(AStackThatResets::previewing(HowTheResetIsGoing::done($previewed), HowTheResetIsGoing::stillRunning()));
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->said())->toContain(__($said))
        ->and($drawn->said())->not->toContain(__($notSaid))
        ->and($drawn->offers())->toContain(__('stacks.reset.put_them_back'));
})->with([
    'no file' => [TheReset::previewed(EditsReverted::these(), ConnectionsReverted::these('sonarr → qbittorrent')), 'stacks.reset.would_revert_no_file', 'stacks.reset.would_revert_no_connection'],
    'no connection' => [TheReset::previewed(EditsReverted::these(AnEditReverted::at('compose.yaml', '')), ConnectionsReverted::these()), 'stacks.reset.would_revert_no_connection', 'stacks.reset.would_revert_no_file'],
]);

it('says plainly that nothing would change, and offers nothing to agree to', function (): void {
    $resetting = AStackThatResets::previewing(HowTheResetIsGoing::done(aPreviewRevertingNothing()), HowTheResetIsGoing::stillRunning());
    $screen = thePuttingItAllBackScreen($resetting);
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect(everythingTheResetShows($screen->answer()))->toBe(nothingReportedOfTheReset(afterTheYes: false, changed: ['isReported' => true, 'changesNothing' => true]))
        ->and($drawn->said())->toContain(__('stacks.reset.a_preview'))
        ->and($drawn->said())->toContain(__('stacks.reset.would_change_nothing'))
        ->and($drawn->said())->not->toContain(__('stacks.reset.would_revert_files'))
        ->and($drawn->offers())->not->toContain(__('stacks.reset.put_them_back'));

    $screen->agree();

    expect($resetting->agreed())->toBe([])
        ->and($screen->wasAgreedTo())->toBeFalse();
});

it('says the stack is working out the preview while that runs', function (): void {
    $resetting = AStackThatResets::previewing(HowTheResetIsGoing::stillRunning(), HowTheResetIsGoing::stillRunning());
    $screen = thePuttingItAllBackScreen($resetting);
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect(everythingTheResetShows($screen->answer()))->toBe(nothingReportedOfTheReset(afterTheYes: false, changed: ['isWorking' => true]))
        ->and($screen->isWorking())->toBeTrue()
        ->and($drawn)->toContain(__('stacks.reset.asking'))
        ->and($drawn)->not->toContain(__('stacks.reset.a_preview'));
});

it('asks after the same preview on its cadence while it runs, and starts no second one', function (): void {
    $resetting = AStackThatResets::previewing(HowTheResetIsGoing::stillRunning(), HowTheResetIsGoing::stillRunning());
    $screen = thePuttingItAllBackScreen($resetting);
    $screen->answer();
    $screen->whileItRuns();
    $screen->answer();

    expect($resetting->previewsAskedFor())->toBe(1)
        ->and($resetting->followed())->toBe([AStackThatResets::THE_PREVIEW, AStackThatResets::THE_PREVIEW]);
});

it('does not ask on its cadence while it is only showing a finished preview', function (): void {
    $resetting = aStackPreviewingTheReset();
    $screen = thePuttingItAllBackScreen($resetting);
    $screen->answer();
    $screen->whileItRuns();
    $screen->answer();

    expect($resetting->previewsAskedFor())->toBe(1)
        ->and($resetting->followed())->toBe([AStackThatResets::THE_PREVIEW]);
});

it('asks for a fresh preview when asked again after a finished one', function (): void {
    $resetting = aStackPreviewingTheReset();
    $screen = thePuttingItAllBackScreen($resetting);
    $screen->answer();
    $screen->again();
    $screen->agree();
    $screen->answer();

    expect($resetting->agreed())->toBe([])
        ->and($resetting->previewsAskedFor())->toBe(2)
        ->and($screen->answer()->isReported)->toBeTrue();
});

it('says a preview the stack no longer has a job for is not known, and a preview it refused in its words', function (HowTheResetIsGoing $preview, array $changed, string $said): void {
    $screen = thePuttingItAllBackScreen(AStackThatResets::previewing($preview, HowTheResetIsGoing::stillRunning()));
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect(everythingTheResetShows($screen->answer()))->toBe(nothingReportedOfTheReset(afterTheYes: false, changed: $changed))
        ->and($drawn->said())->toContain(__($said))
        ->and($drawn->offers())->not->toContain(__('stacks.reset.put_them_back'));
})->with([
    'ended' => [HowTheResetIsGoing::ended(), ['hasEnded' => true], 'stacks.reset.no_preview'],
    'refused' => [
        HowTheResetIsGoing::refused(ARefusalInItsWords::said('The recorded quality choice could not be read', '', WhatTheRefusalNamed::as('quality.json'))),
        ['refused' => ['The recorded quality choice could not be read', '', 'quality.json']],
        'stacks.reset.refused_preview',
    ],
]);

it('draws the stack\'s words for a refusal, and what it named only where it named anything', function (WhatTheRefusalNamed $named, bool $drawsTheName): void {
    $screen = thePuttingItAllBackScreen(AStackThatResets::previewing(HowTheResetIsGoing::refused(ARefusalInItsWords::said('A stack file could not be written', '', $named)), HowTheResetIsGoing::stillRunning()));
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($drawn)->toContain('A stack file could not be written')
        ->and(in_array(__('stacks.refusal.named', ['named' => 'compose.yaml']), $drawn, strict: true))->toBe($drawsTheName);
})->with([
    'named' => [WhatTheRefusalNamed::as('compose.yaml'), true],
    'naming nothing' => [WhatTheRefusalNamed::nothing(), false],
]);

it('offers nothing to agree to where the stack could not be asked, and lets go of a session it refused', function (Obstacle $why, bool $stillSignedIn): void {
    $keychain = AKeychainInMemory::working();
    $resetting = AStackThatResets::met($why);
    $screen = thePuttingItAllBackScreen($resetting, $keychain);
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($screen->answer()->went->cameBack())->toBeFalse()
        ->and($screen->answer()->went->isSignedIn)->toBe($stillSignedIn)
        ->and($keychain->isHolding(theStackWhoseConfigurationGoesBack()->id()))->toBe($stillSignedIn)
        ->and($drawn->offers())->not->toContain(__('stacks.reset.put_them_back'))
        ->and($drawn->said())->not->toContain(__('stacks.reset.heading'));

    $screen->agree();

    expect($resetting->agreed())->toBe([]);
})->with([
    'not answering' => [Obstacle::StackDidNotAnswer, true],
    'a refused session' => [Obstacle::CredentialWasRefused, false],
]);

it('lets go of a session refused while asking after the preview', function (): void {
    $keychain = AKeychainInMemory::working();
    $screen = thePuttingItAllBackScreen(AStackThatResets::previewing(HowTheResetIsGoing::met(Obstacle::CredentialWasRefused), HowTheResetIsGoing::stillRunning()), $keychain);

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($keychain->isHolding(theStackWhoseConfigurationGoesBack()->id()))->toBeFalse();
});

it('asks for a session rather than a preview where this device holds none', function (): void {
    $resetting = aStackPreviewingTheReset();
    $screen = thePuttingItAllBackScreen($resetting, signedIn: false);

    expect(everythingTheResetShows($screen->answer()))->toBe(nothingReportedOfTheReset(afterTheYes: false, changed: ['cameBack' => false, 'isSignedIn' => false]))
        ->and($resetting->previewsAskedFor())->toBe(0)
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->toContain(__('connection.sign_in'));
});

it('sends the yes only after the preview, and says it is putting the configuration back', function (): void {
    $resetting = aStackPreviewingTheReset();
    $screen = thePuttingItAllBackScreen($resetting);
    $screen->answer();
    $screen->agree();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($resetting->agreed())->toHaveCount(1)
        ->and($screen->wasAgreedTo())->toBeTrue()
        ->and($screen->isWorking())->toBeTrue()
        ->and(everythingTheResetShows($screen->answer()))->toBe(nothingReportedOfTheReset(afterTheYes: true, changed: ['isWorking' => true]))
        ->and($drawn->said())->toContain(__('stacks.reset.putting_back'))
        ->and($drawn->said())->not->toContain(__('stacks.reset.a_preview'))
        ->and($drawn->offers())->toContain(__('stacks.reset.see_the_settings'))
        ->and($resetting->followed())->toBe([AStackThatResets::THE_PREVIEW]);
});

it('sends no yes before a preview has been read', function (): void {
    $resetting = aStackPreviewingTheReset();
    $screen = thePuttingItAllBackScreen($resetting);
    $screen->agree();

    expect($resetting->agreed())->toBe([])
        ->and($screen->wasAgreedTo())->toBeFalse();
});

it('reports what went back and which connections went with it, in the past tense, from the stack\'s own word', function (): void {
    $resetting = aStackPreviewingTheReset(HowTheResetIsGoing::done(WhatAResetSays::carriedOut()));
    $screen = thePuttingItAllBackScreen($resetting);
    $screen->answer();
    $screen->agree();
    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect(everythingTheResetShows($screen->answer()))->toBe(nothingReportedOfTheReset(afterTheYes: true, changed: [
        'isReported' => true,
        ...theUsualResetAsDrawn(),
    ]))
        ->and($resetting->followed())->toBe([AStackThatResets::THE_PREVIEW, AStackThatResets::THE_RESET])
        ->and($drawn->said())->toContain(__('stacks.reset.put_back'))
        ->and($drawn->said())->toContain(__('stacks.reset.reverted_files'))
        ->and($drawn->said())->toContain(__('stacks.reset.differed_in_no_line'))
        ->and($drawn->said())->toContain(__('stacks.reset.reverted_connections'))
        ->and($drawn->said())->toContain('sonarr → qbittorrent')
        ->and($drawn->said())->not->toContain(__('stacks.reset.a_preview'))
        ->and($drawn->offers())->not->toContain(__('stacks.reset.put_them_back'))
        ->and($drawn->offers())->toContain(__('stacks.reset.see_the_settings'));

    $screen->whileItRuns();
    $screen->answer();

    expect($resetting->followed())->toHaveCount(2);
});

it('says a reset carried out that reverted nothing changed nothing', function (): void {
    $screen = thePuttingItAllBackScreen(aStackPreviewingTheReset(HowTheResetIsGoing::done(TheReset::carriedOut(EditsReverted::these(), ConnectionsReverted::these()))));
    $screen->answer();
    $screen->agree();
    $screen->whileItRuns();

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('stacks.reset.changed_nothing'));
});

it('words a report the stack did not carry out as a preview even after the yes, and offers no second yes', function (): void {
    $screen = thePuttingItAllBackScreen(aStackPreviewingTheReset(HowTheResetIsGoing::done(WhatAResetSays::previewed())));
    $screen->answer();
    $screen->agree();
    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($screen->answer()->words->heading)->toBe('stacks.reset.a_preview')
        ->and($screen->answer()->mayBeAgreedTo)->toBeFalse()
        ->and($drawn->said())->toContain(__('stacks.reset.a_preview'))
        ->and($drawn->said())->not->toContain(__('stacks.reset.put_back'))
        ->and($drawn->offers())->not->toContain(__('stacks.reset.put_them_back'));
});

it('says a reset the stack has no outcome for may have been done, and a refused one in its words', function (HowTheResetIsGoing $after, array $changed, string $said): void {
    $screen = thePuttingItAllBackScreen(aStackPreviewingTheReset($after));
    $screen->answer();
    $screen->agree();
    $screen->whileItRuns();

    expect(everythingTheResetShows($screen->answer()))->toBe(nothingReportedOfTheReset(afterTheYes: true, changed: $changed))
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__($said));
})->with([
    'ended' => [HowTheResetIsGoing::ended(), ['hasEnded' => true], 'stacks.reset.no_outcome'],
    'refused' => [
        HowTheResetIsGoing::refused(ARefusalInItsWords::said('A stack file could not be written', '', WhatTheRefusalNamed::as('compose.yaml'))),
        ['refused' => ['A stack file could not be written', '', 'compose.yaml']],
        'stacks.reset.refused',
    ],
]);

it('asks for a fresh preview when asked again after a yes that finished', function (): void {
    $resetting = aStackPreviewingTheReset(HowTheResetIsGoing::done(WhatAResetSays::carriedOut()));
    $screen = thePuttingItAllBackScreen($resetting);
    $screen->answer();
    $screen->agree();
    $screen->whileItRuns();
    $screen->answer();
    $screen->again();

    expect($screen->wasAgreedTo())->toBeFalse()
        ->and($screen->answer()->words->heading)->toBe('stacks.reset.a_preview')
        ->and($resetting->previewsAskedFor())->toBe(2)
        ->and($resetting->followed())->toBe([AStackThatResets::THE_PREVIEW, AStackThatResets::THE_RESET, AStackThatResets::THE_PREVIEW]);
});

it('asks after the same yes when asked again while it runs', function (): void {
    $resetting = aStackPreviewingTheReset();
    $screen = thePuttingItAllBackScreen($resetting);
    $screen->answer();
    $screen->agree();
    $screen->again();
    $screen->answer();

    expect($screen->wasAgreedTo())->toBeTrue()
        ->and($resetting->previewsAskedFor())->toBe(1)
        ->and($resetting->followed())->toBe([AStackThatResets::THE_PREVIEW, AStackThatResets::THE_RESET]);
});

it('says what stood in the way of a yes the stack refused, and asks for the preview afresh', function (): void {
    $resetting = AStackThatResets::previewingButRefusing(HowTheResetIsGoing::done(WhatAResetSays::previewed()), Obstacle::StackDidNotAnswer);
    $screen = thePuttingItAllBackScreen($resetting);
    $screen->answer();
    $screen->agree();

    expect(everythingTheResetShows($screen->answer()))->toBe(nothingReportedOfTheReset(afterTheYes: true, changed: ['cameBack' => false, 'met' => Obstacle::StackDidNotAnswer->said()]))
        ->and($screen->isWorking())->toBeFalse()
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__(Obstacle::StackDidNotAnswer->said()));

    $screen->again();

    expect($screen->wasAgreedTo())->toBeFalse()
        ->and($screen->answer()->isReported)->toBeTrue()
        ->and($resetting->previewsAskedFor())->toBe(2)
        ->and($resetting->followed())->toBe([AStackThatResets::THE_PREVIEW, AStackThatResets::THE_PREVIEW]);
});

it('lets go of a session refused while putting back or asking after', function (bool $whileAsking): void {
    $keychain = AKeychainInMemory::working();
    $resetting = $whileAsking
        ? aStackPreviewingTheReset(HowTheResetIsGoing::met(Obstacle::CredentialWasRefused))
        : AStackThatResets::previewingButRefusing(HowTheResetIsGoing::done(WhatAResetSays::previewed()), Obstacle::CredentialWasRefused);
    $screen = thePuttingItAllBackScreen($resetting, $keychain);
    $screen->answer();
    $screen->agree();

    if ($whileAsking) {
        $screen->whileItRuns();
    }

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($keychain->isHolding(theStackWhoseConfigurationGoesBack()->id()))->toBeFalse();
})->with(['putting back' => [false], 'asking after' => [true]]);

it('asks for a session where it is gone by the time the yes is sent, or asked after', function (bool $afterTheYes): void {
    $keychain = AKeychainInMemory::working();
    $resetting = aStackPreviewingTheReset();
    $screen = thePuttingItAllBackScreen($resetting, $keychain);
    $screen->answer();

    if ($afterTheYes) {
        $screen->agree();
    }

    $keychain->forget(theStackWhoseConfigurationGoesBack()->id());

    if (! $afterTheYes) {
        $screen->agree();
    }

    $screen->whileItRuns();

    expect(everythingTheResetShows($screen->answer()))->toBe(nothingReportedOfTheReset(afterTheYes: true, changed: ['cameBack' => false, 'isSignedIn' => false]));
})->with(['before the yes' => [false], 'after it' => [true]]);

it('has nothing to report for a yes nobody gave', function (): void {
    $resetting = aStackPreviewingTheReset();
    $screen = thePuttingItAllBackScreen($resetting);

    expect(everythingTheResetShows($screen->done()))->toBe(nothingReportedOfTheReset(afterTheYes: true, changed: ['hasEnded' => true]))
        ->and($resetting->followed())->toBe([]);
});

it('names where it goes and what it draws', function (): void {
    $screen = thePuttingItAllBackScreen(aStackPreviewingTheReset());

    expect($screen->goes()->ofItself()->changing()->reset())->toEndWith('/reset')
        ->and($screen->render()->name())->toBe('operator::putting-the-configuration-back');
});

it('refuses a route parameter that is not text as naming a stack', function (): void {
    $screen = thePuttingItAllBackScreen(aStackPreviewingTheReset());
    $screen->setParams(['stack' => 42]);

    expect(fn(): Stack => $screen->stack())->toThrow(StackIsUnidentified::class);
});

it('asked again before anything was read, asks for the preview when the frame reads it, and only then', function (): void {
    $resetting = aStackPreviewingTheReset();
    $screen = thePuttingItAllBackScreen($resetting);

    $screen->again();

    expect($resetting->previewsAskedFor())->toBe(0);

    $screen->answer();

    expect($resetting->previewsAskedFor())->toBe(1);
});
