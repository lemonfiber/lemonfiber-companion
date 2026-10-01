<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AMode;
use Modules\Kernel\Api\AMove;
use Modules\Kernel\Api\APortMoved;
use Modules\Kernel\Api\ARecord;
use Modules\Kernel\Api\AServiceAdopted;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Stance;
use Modules\Kernel\Api\TheAdoption;
use Modules\Kernel\Api\TheImport;
use Modules\Kernel\Api\TheModes;
use Modules\Kernel\Api\ThePortsHeld;
use Modules\Kernel\Api\ThePortsMoved;
use Modules\Kernel\Api\TheRecords;
use Modules\Kernel\Api\TheReplacement;
use Modules\Kernel\Api\TheStandingBeside;
use Modules\Kernel\Api\TheSurvey;
use Modules\Kernel\Api\Unsupported;
use Modules\Kernel\Api\WhatAdoptingOneWouldDo;
use Modules\Kernel\Api\WhatAdoptingWouldDo;
use Modules\Kernel\Api\WhatBecameOfTheMove;
use Modules\Kernel\Api\WhatIsUnsupported;
use Modules\Kernel\Api\WhatLinkingCosts;
use Modules\Kernel\Api\WhatMayBeDone;
use Modules\Kernel\Api\WhatStandsHere;
use Modules\Kernel\Api\WhatWasNamed;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\WhatIsAlreadyOnThisMachine;
use Modules\Operator\Internal\ViewModels\ALineOfTheSurvey;
use Modules\Operator\Internal\ViewModels\AModeAsShown;
use Modules\Operator\Internal\ViewModels\AMoveAsShown;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackWithSomethingAlreadyOnIt;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;

// Moving in beside what is already on a machine: asking what a mode would
// come to, and agreeing to it.
//
// Here rather than in the operator module's own tests because a screen renders,
// and rendering needs the application.

/** The machine being moved into. */
function theStackBeingMovedInto(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** A survey offering adopting, importing, one mode no act carries out, and replacing. */
function aSurveyWithModesToChoose(): TheSurvey
{
    return TheSurvey::reported(
        looked: true,
        standing: WhatStandsHere::of(),
        conflicts: ThePortsHeld::of(),
        unsupported: WhatIsUnsupported::none(),
        beside: ThePortsMoved::of(),
        linking: WhatLinkingCosts::nothing(),
        choices: WhatMayBeDone::offered(TheModes::of(
            AMode::offered('adopt', 'Manages what is here', disturbs: false, preselected: true),
            AMode::offered('import', 'Copies its records across', disturbs: false, preselected: false),
            AMode::offered('side-by-side', 'Something this app has no act for', disturbs: false, preselected: false),
            AMode::offered('replace', 'Stops the old one', disturbs: true, preselected: false),
        ), WhatAdoptingWouldDo::of(), WhatIsUnsupported::none()),
    );
}

/** The screen, with the stack answering each act with these in turn. Named for this file (`G10`). */
function theScreenForMovingIn(
    AStackWithSomethingAlreadyOnIt $movingIn,
    ?AKeychainInMemory $keychain = null,
    bool $signedIn = true,
): WhatIsAlreadyOnThisMachine {
    $stack = theStackBeingMovedInto();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new WhatIsAlreadyOnThisMachine($movingIn, $keychain, AroundThePhone::holding(StacksInMemory::holding($stack)), new AppsSettingsThatOpen());
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

/** A stack whose survey offers those modes, answering each act with the work and then with these. */
function aStackAnsweringTheMove(WhatBecameOfTheMove ...$then): AStackWithSomethingAlreadyOnIt
{
    return AStackWithSomethingAlreadyOnIt::with(aSurveyWithModesToChoose())->moving(WhatBecameOfTheMove::underway(Job::named('j-1')), ...$then);
}

/** What adopting would come to: one service a newer version opens and wants a copy of first, and one it does not. */
function anAdoptionAt(Stance $stance, string $backedUp = ''): AMove
{
    return AMove::at($stance, TheAdoption::of(
        'media',
        WhatWasNamed::of('back_up', '/srv/sonarr'),
        $backedUp,
        AServiceAdopted::said(WhatAdoptingOneWouldDo::said('sonarr', 'Its database is upgraded', backupFirst: true, refused: false), '3.0', '4.0', 'newer'),
        AServiceAdopted::said(WhatAdoptingOneWouldDo::said('radarr', 'Nothing changes', backupFirst: false, refused: false), '5.0', '5.0', 'same'),
    ));
}

/** What importing came to, or would come to, with what it could not carry. */
function anImportAt(Stance $stance, ARecord ...$carried): AMove
{
    return AMove::at($stance, TheImport::of(
        'media',
        TheRecords::of(...$carried),
        TheRecords::of(ARecord::of('sonarr', 'quality profiles', 'HD-1080p')),
        WhatIsUnsupported::these(Unsupported::of('radarr', 'its indexers could not be read')),
    ));
}

/** What replacing came to, or would come to. */
function aReplacementAt(Stance $stance): AMove
{
    return AMove::at($stance, TheReplacement::of(
        'media',
        WhatWasNamed::of('would_stop', 'sonarr'),
        WhatWasNamed::of('stopped', 'radarr'),
        WhatWasNamed::of('still_running', 'tautulli'),
    ));
}

/** The screen once the operator asked about a mode and the stack answered with the work and then with this. */
function theScreenAfterAsking(string $mode, AStackWithSomethingAlreadyOnIt $movingIn): WhatIsAlreadyOnThisMachine
{
    $screen = theScreenForMovingIn($movingIn);
    $screen->wouldMoveIn($mode);
    $screen->going = null;

    return $screen;
}

/**
 * The lines of a move, as the keys they are drawn with.
 *
 * @param  list<ALineOfTheSurvey> $lines
 * @return list<string>
 */
function theKeysOf(array $lines): array
{
    return array_map(static fn(ALineOfTheSurvey $line): string => $line->said, $lines);
}

/** The move the screen draws, which a test has arranged to be there. */
function theMoveDrawn(WhatIsAlreadyOnThisMachine $screen): AMoveAsShown
{
    return $screen->howTheMoveIsGoing()->move ?? throw new LogicException('no move was drawn');
}

it('offers asking about each mode an act carries out, and nothing for one that none does, asking the stack nothing', function (): void {
    $movingIn = aStackAnsweringTheMove();
    $screen = theScreenForMovingIn($movingIn);
    $drawn = WhatTheDeviceWouldDraw::by($screen)->offers();

    expect($drawn)->toBe([
        __('stacks.moving_in.ask.adopt'),
        __('stacks.moving_in.ask.import'),
        __('stacks.moving_in.ask.replace'),
        __('stacks.already_here.look_again'),
    ])->and(array_map(static fn(AModeAsShown $mode): string => $mode->askSaid, $screen->answer()->modes))
        ->toBe(['stacks.moving_in.ask.adopt', 'stacks.moving_in.ask.import', '', 'stacks.moving_in.ask.replace'])
        ->and($movingIn->acts())->toBe([])
        ->and($screen->howTheMoveIsGoing()->mode)->toBe('');
});

it('offers asking about standing beside where the survey offers it', function (): void {
    $survey = TheSurvey::reported(
        looked: true,
        standing: WhatStandsHere::of(),
        conflicts: ThePortsHeld::of(),
        unsupported: WhatIsUnsupported::none(),
        beside: ThePortsMoved::of(),
        linking: WhatLinkingCosts::nothing(),
        choices: WhatMayBeDone::offered(TheModes::of(AMode::offered('beside', 'Runs on other ports', disturbs: false, preselected: false)), WhatAdoptingWouldDo::of(), WhatIsUnsupported::none()),
    );

    expect(WhatTheDeviceWouldDraw::by(theScreenForMovingIn(AStackWithSomethingAlreadyOnIt::with($survey)))->offers())
        ->toContain(__('stacks.moving_in.ask.beside'));
});

it('asks what a mode would come to, says it is working while it runs, and offers no yes', function (): void {
    $movingIn = aStackAnsweringTheMove();
    $screen = theScreenForMovingIn($movingIn);
    $screen->wouldMoveIn('adopt');
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($movingIn->acts())->toBe(['would:adopt'])
        ->and($screen->following)->toBe('j-1')
        ->and($screen->about)->toBe('adopt')
        ->and($drawn->said())->toContain(__('stacks.moving_in.about', ['mode' => 'adopt']))
        ->and($drawn->said())->toContain(__('stacks.moving_in.working'))
        ->and($drawn->offers())->not->toContain(__('stacks.moving_in.agree.adopt'));
});

it('asks about nothing the survey did not list, nor about a mode no act carries out', function (): void {
    $movingIn = aStackAnsweringTheMove();
    $screen = theScreenForMovingIn($movingIn);
    $screen->wouldMoveIn('beside');
    $screen->wouldMoveIn('side-by-side');

    expect($movingIn->acts())->toBe([])
        ->and($screen->howTheMoveIsGoing()->mode)->toBe('');
});

it('says what it copies first before the yes, and sends the yes only beneath a pending answer', function (): void {
    $movingIn = aStackAnsweringTheMove(WhatBecameOfTheMove::answered(anAdoptionAt(Stance::Pending)), WhatBecameOfTheMove::underway(Job::named('j-2')));
    $screen = theScreenAfterAsking('adopt', $movingIn);
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();
    $move = theMoveDrawn($screen);

    $copied = array_search(__('stacks.moving_in.copies', ['path' => '/srv/sonarr']), $drawn, strict: true);
    $wanted = array_search(__('stacks.moving_in.copy_first', ['service' => 'sonarr', 'because' => 'Its database is upgraded']), $drawn, strict: true);
    $yes = array_search(__('stacks.moving_in.agree.adopt'), $drawn, strict: true);

    expect($move->stanceSaid)->toBe('stacks.moving_in.stance.pending')
        ->and(theKeysOf($move->copyFirst))->toBe(['stacks.moving_in.copy_first', 'stacks.moving_in.copies'])
        ->and($move->copyFirst[0]->with)->toBe(['service' => 'sonarr', 'existing' => '3.0', 'ours' => '4.0', 'verdict' => 'newer', 'because' => 'Its database is upgraded'])
        ->and(theKeysOf($move->lines))->toBe(['stacks.already_here.project', 'stacks.moving_in.upgrade', 'stacks.moving_in.upgrade'])
        ->and($move->agreeSaid)->toBe('stacks.moving_in.agree.adopt')
        ->and($drawn)->toContain(__('stacks.moving_in.before_you_agree'))
        ->and($drawn)->not->toContain(__('stacks.moving_in.stance.applied'))
        ->and(is_int($copied) && is_int($wanted) && is_int($yes) && $copied < $yes && $wanted < $yes)->toBeTrue()
        ->and($screen->staged)->not->toBeNull();

    $screen->moveIn();

    expect($movingIn->acts())->toBe(['would:adopt', 'after:j-1', 'move:adopt'])
        ->and($screen->staged)->toBeNull()
        ->and($screen->following)->toBe('j-2');
});

it('says so where nothing wants a copy first', function (): void {
    $move = AMove::at(Stance::Pending, TheStandingBeside::of(ThePortsMoved::of(APortMoved::of('sonarr', 8989, 8990)), ''));
    $screen = theScreenAfterAsking('replace', aStackAnsweringTheMove(WhatBecameOfTheMove::answered($move)));
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect(theMoveDrawn($screen)->copyFirst)->toBe([])
        ->and($drawn)->toContain(__('stacks.moving_in.nothing_copied_first'))
        ->and($drawn)->toContain(__('stacks.already_here.moved', ['service' => 'sonarr', 'from' => '8989', 'to' => '8990']))
        ->and(theMoveDrawn($screen)->agreeSaid)->toBe('stacks.moving_in.agree.beside')
        ->and(theKeysOf(theMoveDrawn($screen)->lines))->toBe(['stacks.already_here.moved']);
});

it('leads with what did not come across, before where the import stands and before what did', function (): void {
    $screen = theScreenAfterAsking('import', aStackAnsweringTheMove(WhatBecameOfTheMove::answered(anImportAt(Stance::Applied, ARecord::of('sonarr', 'root folders', '/tv')))));
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();
    $move = theMoveDrawn($screen);

    $heading = array_search(__('stacks.moving_in.left_behind'), $drawn, strict: true);
    $left = array_search(__('stacks.moving_in.not_carried', ['what' => 'radarr', 'because' => 'its indexers could not be read']), $drawn, strict: true);
    $stance = array_search(__('stacks.moving_in.stance.applied'), $drawn, strict: true);
    $carried = array_search(__('stacks.moving_in.carried', ['service' => 'sonarr', 'kind' => 'root folders', 'name' => '/tv']), $drawn, strict: true);

    expect($move->leftBehindSaid)->toBe('stacks.moving_in.left_behind')
        ->and(theKeysOf($move->leftBehind))->toBe(['stacks.moving_in.not_carried'])
        ->and(theKeysOf($move->lines))->toBe(['stacks.already_here.project', 'stacks.moving_in.carried'])
        ->and($move->agreeSaid)->toBe('')
        ->and(is_int($heading) && is_int($left) && is_int($stance) && is_int($carried) && $heading < $left && $left < $stance && $stance < $carried)->toBeTrue();
});

it('tells an import that carried nothing from one that has not run, and from one with nothing to carry', function (): void {
    $carriedNothing = theMoveDrawn(theScreenAfterAsking('import', aStackAnsweringTheMove(WhatBecameOfTheMove::answered(anImportAt(Stance::Applied)))));
    $notRun = theMoveDrawn(theScreenAfterAsking('import', aStackAnsweringTheMove(WhatBecameOfTheMove::answered(anImportAt(Stance::Pending)))));
    $nothingToCarry = theMoveDrawn(theScreenAfterAsking('import', aStackAnsweringTheMove(WhatBecameOfTheMove::answered(anImportAt(Stance::Unchanged)))));

    expect($carriedNothing->stanceSaid)->toBe('stacks.moving_in.import.carried_nothing')
        ->and(theKeysOf($carriedNothing->lines))->toBe(['stacks.already_here.project'])
        ->and($notRun->stanceSaid)->toBe('stacks.moving_in.import.not_run')
        ->and($notRun->leftBehindSaid)->toBe('stacks.moving_in.would_leave_behind')
        ->and(theKeysOf($notRun->lines))->toBe(['stacks.already_here.project', 'stacks.moving_in.would_carry'])
        ->and($notRun->agreeSaid)->toBe('stacks.moving_in.agree.import')
        ->and($nothingToCarry->stanceSaid)->toBe('stacks.moving_in.import.nothing_to_carry')
        ->and($nothingToCarry->leftBehindSaid)->toBe('stacks.moving_in.left_behind')
        ->and($carriedNothing->leftBehindSaid)->toBe('stacks.moving_in.left_behind')
        ->and($nothingToCarry->agreeSaid)->toBe('');
});

it('leads with nothing left behind for an import turned away, which carried nothing and was not asked to', function (): void {
    $blocked = AMove::blocked('There is no single setup here', TheImport::of('', TheRecords::of(), TheRecords::of(), WhatIsUnsupported::none()));
    $screen = theScreenAfterAsking('import', aStackAnsweringTheMove(WhatBecameOfTheMove::answered($blocked)));
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect(theMoveDrawn($screen)->leftBehindSaid)->toBe('')
        ->and(theMoveDrawn($screen)->stanceSaid)->toBe('stacks.moving_in.stance.blocked')
        ->and(theMoveDrawn($screen)->refusal)->toBe('There is no single setup here')
        ->and($drawn)->not->toContain(__('stacks.moving_in.left_behind'))
        ->and($drawn)->not->toContain(__('stacks.moving_in.nothing_left_behind'));
});

it('says nothing is left behind where an import leaves nothing', function (): void {
    $move = AMove::at(Stance::Applied, TheImport::of('', TheRecords::of(ARecord::of('sonarr', 'root folders', '/tv')), TheRecords::of(), WhatIsUnsupported::none()));
    $screen = theScreenAfterAsking('import', aStackAnsweringTheMove(WhatBecameOfTheMove::answered($move)));

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('stacks.moving_in.nothing_left_behind'))
        ->and(theKeysOf(theMoveDrawn($screen)->lines))->toBe(['stacks.moving_in.carried']);
});

it('draws each stance as given, and only applied as done', function (Stance $stance, string $said, bool $mayBeAgreed): void {
    $screen = theScreenAfterAsking('replace', aStackAnsweringTheMove(WhatBecameOfTheMove::answered($stance === Stance::Blocked
        ? AMove::blocked('Nothing here to stand in place of', TheReplacement::of('', WhatWasNamed::of('would_stop'), WhatWasNamed::of('stopped'), WhatWasNamed::of('still_running')))
        : aReplacementAt($stance))));
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect(theMoveDrawn($screen)->stanceSaid)->toBe($said)
        ->and($drawn->said())->toContain(__($said))
        ->and(theMoveDrawn($screen)->leftBehindSaid)->toBe('')
        ->and(in_array(__('stacks.moving_in.agree.replace'), $drawn->offers(), strict: true))->toBe($mayBeAgreed);
})->with([
    [Stance::Unchanged, 'stacks.moving_in.stance.unchanged', false],
    [Stance::Pending, 'stacks.moving_in.stance.pending', true],
    [Stance::Blocked, 'stacks.moving_in.stance.blocked', false],
    [Stance::Applied, 'stacks.moving_in.stance.applied', false],
]);

it('says what replacing would stop, or what it stopped and what would not stop', function (): void {
    $pending = theMoveDrawn(theScreenAfterAsking('replace', aStackAnsweringTheMove(WhatBecameOfTheMove::answered(aReplacementAt(Stance::Pending)))));
    $applied = theMoveDrawn(theScreenAfterAsking('replace', aStackAnsweringTheMove(WhatBecameOfTheMove::answered(aReplacementAt(Stance::Applied)))));

    expect(theKeysOf($pending->lines))->toBe(['stacks.already_here.project', 'stacks.moving_in.would_stop'])
        ->and($pending->lines[0]->with)->toBe(['project' => 'media'])
        ->and($pending->lines[1]->with)->toBe(['service' => 'sonarr'])
        ->and(theKeysOf($applied->lines))->toBe(['stacks.already_here.project', 'stacks.moving_in.stopped', 'stacks.moving_in.still_running'])
        ->and([$applied->lines[1]->with, $applied->lines[2]->with])->toBe([['service' => 'radarr'], ['service' => 'tautulli']]);
});

it('says where each service listens and where that is written, once standing beside is done', function (): void {
    $move = AMove::at(Stance::Applied, TheStandingBeside::of(ThePortsMoved::of(APortMoved::of('sonarr', 8989, 8990)), '/srv/beside.yml'));
    $shown = theMoveDrawn(theScreenAfterAsking('adopt', aStackAnsweringTheMove(WhatBecameOfTheMove::answered($move))));

    expect(theKeysOf($shown->lines))->toBe(['stacks.moving_in.listens', 'stacks.moving_in.written'])
        ->and($shown->lines[1]->with)->toBe(['path' => '/srv/beside.yml'])
        ->and($shown->copyFirst)->toBe([]);
});

it('says where the copy was written once adopting is done, and what it will not take over', function (): void {
    $refused = AMove::at(Stance::Applied, TheAdoption::of(
        '',
        WhatWasNamed::of('back_up', '/srv/sonarr'),
        '/srv/copy.tar',
        AServiceAdopted::said(WhatAdoptingOneWouldDo::said('lidarr', 'A later version wrote it', backupFirst: true, refused: true), '9.0', '2.0', 'older'),
    ));
    $shown = theMoveDrawn(theScreenAfterAsking('adopt', aStackAnsweringTheMove(WhatBecameOfTheMove::answered($refused))));

    expect(theKeysOf($shown->lines))->toBe(['stacks.moving_in.upgrade_refused', 'stacks.moving_in.backed_up'])
        ->and($shown->lines[1]->with)->toBe(['path' => '/srv/copy.tar'])
        ->and($shown->copyFirst)->toBe([])
        ->and($shown->agreeSaid)->toBe('');
});

it('shows a move turned away with the stack\'s reason, and not as something to try again', function (): void {
    $blocked = AMove::blocked('There is no single setup here', TheAdoption::of('', WhatWasNamed::of('back_up'), ''));
    $screen = theScreenAfterAsking('adopt', aStackAnsweringTheMove(WhatBecameOfTheMove::answered($blocked)));
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect(theMoveDrawn($screen)->refusal)->toBe('There is no single setup here')
        ->and($drawn->said())->toContain(__('stacks.moving_in.stance.blocked'))
        ->and($drawn->said())->toContain('There is no single setup here')
        ->and($drawn->said())->toContain(__('stacks.moving_in.nothing_listed'))
        ->and($drawn->offers())->not->toContain(__('health.ask_again'))
        ->and($drawn->offers())->not->toContain(__('stacks.moving_in.agree.adopt'))
        ->and($drawn->offers())->toContain(__('stacks.moving_in.leave_it'))
        ->and($screen->staged)->toBeNull();
});

it('shows a request the stack turned down in its own words, and not as something to try again', function (): void {
    $movingIn = AStackWithSomethingAlreadyOnIt::with(aSurveyWithModesToChoose())->moving(WhatBecameOfTheMove::refused('Nothing here to import from'));
    $screen = theScreenForMovingIn($movingIn);
    $screen->wouldMoveIn('import');
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($screen->howTheMoveIsGoing()->refusal)->toBe('Nothing here to import from')
        ->and($screen->following)->toBeNull()
        ->and($drawn->said())->toContain(__('stacks.moving_in.refused'))
        ->and($drawn->said())->toContain('Nothing here to import from')
        ->and($drawn->offers())->not->toContain(__('health.ask_again'))
        ->and($drawn->offers())->toContain(__('stacks.moving_in.leave_it'));
});

it('says the stack has no outcome for work it no longer knows, which is not a refusal', function (): void {
    $movingIn = aStackAnsweringTheMove();
    $screen = theScreenAfterAsking('adopt', $movingIn);
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->said())->toContain(__('stacks.moving_in.no_outcome'))
        ->and($screen->following)->toBeNull()
        ->and($screen->howTheMoveIsGoing()->hasEnded)->toBeTrue()
        ->and($drawn->offers())->toContain(__('stacks.moving_in.leave_it'))
        ->and($movingIn->acts())->toBe(['would:adopt', 'after:j-1']);
});

it('asks after running work only while it runs', function (): void {
    $movingIn = aStackAnsweringTheMove(WhatBecameOfTheMove::underway(Job::named('j-1')), WhatBecameOfTheMove::answered(anAdoptionAt(Stance::Pending)));
    $screen = theScreenForMovingIn($movingIn);
    $screen->wouldMoveIn('adopt');
    $screen->whileItRuns();
    $screen->howTheMoveIsGoing();
    $screen->whileItRuns();
    $screen->howTheMoveIsGoing();

    expect($movingIn->acts())->toBe(['would:adopt', 'after:j-1', 'after:j-1']);

    $screen->whileItRuns();
    $screen->howTheMoveIsGoing();

    expect($movingIn->acts())->toBe(['would:adopt', 'after:j-1', 'after:j-1']);
});

it('reads what is on the machine again once a move has done something, and not before', function (): void {
    $movingIn = aStackAnsweringTheMove(WhatBecameOfTheMove::answered(anAdoptionAt(Stance::Pending)), WhatBecameOfTheMove::underway(Job::named('j-2')), WhatBecameOfTheMove::answered(anAdoptionAt(Stance::Applied, '/srv/copy.tar')));
    $screen = theScreenAfterAsking('adopt', $movingIn);
    $screen->howTheMoveIsGoing();
    $screen->answer();

    expect($movingIn->askings())->toBe(1);

    $screen->moveIn();
    $screen->going = null;
    $screen->howTheMoveIsGoing();
    $screen->answer();

    expect($movingIn->askings())->toBe(2)
        ->and(theMoveDrawn($screen)->stanceSaid)->toBe('stacks.moving_in.stance.applied')
        ->and($screen->staged)->toBeNull();
});

it('sends no yes without a pending answer on the screen', function (): void {
    $movingIn = aStackAnsweringTheMove(WhatBecameOfTheMove::answered(anAdoptionAt(Stance::Unchanged)));
    $fresh = theScreenForMovingIn($movingIn);
    $fresh->moveIn();

    expect($movingIn->acts())->toBe([]);

    $screen = theScreenAfterAsking('adopt', $movingIn);
    $screen->howTheMoveIsGoing();
    $screen->moveIn();

    expect($movingIn->acts())->toBe(['would:adopt', 'after:j-1'])
        ->and($screen->staged)->toBeNull();

    $screen->staged = anAdoptionAt(Stance::Applied);
    $screen->moveIn();

    expect($movingIn->acts())->toBe(['would:adopt', 'after:j-1']);
});

it('lets go of an answer waiting on a yes once another mode is asked about', function (): void {
    $movingIn = aStackAnsweringTheMove(WhatBecameOfTheMove::answered(anAdoptionAt(Stance::Pending)), WhatBecameOfTheMove::underway(Job::named('j-2')));
    $screen = theScreenAfterAsking('adopt', $movingIn);
    $screen->howTheMoveIsGoing();

    expect($screen->staged)->not->toBeNull();

    $screen->wouldMoveIn('replace');
    $screen->moveIn();

    expect($screen->staged)->toBeNull()
        ->and($screen->about)->toBe('replace')
        ->and($screen->following)->toBe('j-2')
        ->and($movingIn->acts())->toBe(['would:adopt', 'after:j-1', 'would:replace']);
});

it('leaves an answer where it is and moves nothing', function (): void {
    $movingIn = aStackAnsweringTheMove(WhatBecameOfTheMove::answered(anAdoptionAt(Stance::Pending)));
    $screen = theScreenAfterAsking('adopt', $movingIn);
    $screen->howTheMoveIsGoing();
    $screen->leaveIt();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect([$screen->about, $screen->following, $screen->staged])->toBe(['', null, null])
        ->and($screen->howTheMoveIsGoing()->move)->toBeNull()
        ->and($drawn->offers())->not->toContain(__('stacks.moving_in.agree.adopt'))
        ->and($movingIn->acts())->toBe(['would:adopt', 'after:j-1']);
});

it('an act the stack could not be reached for is an obstacle beside the survey, that can be asked again', function (): void {
    $movingIn = AStackWithSomethingAlreadyOnIt::with(aSurveyWithModesToChoose())->moving(WhatBecameOfTheMove::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)));
    $screen = theScreenForMovingIn($movingIn);
    $screen->wouldMoveIn('replace');
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($screen->howTheMoveIsGoing()->went->met)->toEqual(KindOfObstacle::StackDidNotAnswer->said())
        ->and($drawn->said())->toContain(__(KindOfObstacle::StackDidNotAnswer->said()))
        ->and($drawn->said())->toContain(__('stacks.already_here.modes'))
        ->and($drawn->offers())->toContain(__('health.ask_again'));

    $screen->again();

    expect($screen->howTheMoveIsGoing()->went->cameBack())->toBeTrue()
        ->and($movingIn->acts())->toBe(['would:replace']);
});

it('a credential the stack refused when moving in signs this device out and lets the session go', function (): void {
    $keychain = AKeychainInMemory::working();
    $screen = theScreenForMovingIn(AStackWithSomethingAlreadyOnIt::with(aSurveyWithModesToChoose())->moving(WhatBecameOfTheMove::met(Obstacle::of(KindOfObstacle::CredentialWasRefused))), keychain: $keychain);
    $screen->wouldMoveIn('adopt');

    expect($screen->howTheMoveIsGoing()->went->isSignedIn)->toBeFalse()
        ->and($keychain->isHolding(theStackBeingMovedInto()->id()))->toBeFalse();
});

it('a session that has ended asks the stack nothing about moving in', function (): void {
    $movingIn = aStackAnsweringTheMove();
    $keychain = AKeychainInMemory::working();
    $screen = theScreenForMovingIn($movingIn, keychain: $keychain);
    $screen->answer();
    $keychain->forget(theStackBeingMovedInto()->id());
    $screen->wouldMoveIn('adopt');

    expect($screen->howTheMoveIsGoing()->went->isSignedIn)->toBeFalse()
        ->and($screen->howTheMoveIsGoing()->mode)->toBe('adopt')
        ->and($movingIn->acts())->toBe([]);
});
