<?php

declare(strict_types=1);

use Modules\Kernel\Api\AClientsHolding;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ADirectionHeld;
use Modules\Kernel\Api\ALineChange;
use Modules\Kernel\Api\ARateGiven;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowFast;
use Modules\Kernel\Api\HowTheLineIsShared;
use Modules\Kernel\Api\HowTheLineWasMeasured;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Remarks;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheHouseholdsHours;
use Modules\Kernel\Api\TheLineAsChanged;
use Modules\Kernel\Api\WhatACapDoes;
use Modules\Kernel\Api\WhatADownloadClientSaid;
use Modules\Kernel\Api\WhatALimitCameTo;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Kernel\Api\WhatTheLineCarries;
use Modules\Kernel\Api\WhatTheLineRunDid;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Kernel\Api\WhereALiftStands;
use Modules\Kernel\Api\WhereTheLineStands;
use Modules\Kernel\Api\WhetherItGoesThroughTheTunnel;
use Modules\Kernel\Api\WhichRate;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\HowTheLineIsSharedHere;
use Modules\Operator\Internal\ViewModels\AChoiceOfASettingAsShown;
use Modules\Operator\Internal\ViewModels\AClientHoldingAsShown;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatRationsItsLine;
use Tests\Support\Fakes\AStackThatSharesItsLine;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheDeviceWouldDraw;

// Changing how a machine shares its line, rehearsed before it is applied.
//
// Here rather than in the operator module's own tests because a screen renders,
// and rendering needs the application.

/**
 * One line of the catalogue, as text, for filling another.
 *
 * @param array<string, int|string> $with
 */
function aLineOfTheLineChange(string $key, array $with = []): string
{
    $said = __($key, $with);

    return is_string($said) ? $said : $key;
}

/**
 * The words of the chips drawn as chosen.
 *
 * @param  list<AChoiceOfASettingAsShown> $offered
 * @return list<string>
 */
function theChipsChosen(array $offered): array
{
    $chosen = [];

    foreach ($offered as $chip) {
        if ($chip->chosen) {
            $chosen[] = $chip->word;
        }
    }

    return $chosen;
}

/** The moment the change is read at. */
const THE_LINE_CHANGES_AT = 1_790_150_000;

/** The machine whose line is changed. */
function theStackWhoseLineIsChanged(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('f', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('a', Fingerprint::CHARACTERS)),
    );
}

/** The line as it stands: measured, with the household's hours. */
function theLineBeforeTheChange(): HowTheLineIsShared
{
    return HowTheLineIsShared::standing(WhereTheLineStands::Unlimited, 'Nothing holds the stack back', 'No limit on 100 Mbit/s', 'No limit on 20 Mbit/s', Remarks::of(), Remarks::of())
        ->measuredAt(WhatTheLineCarries::measured(12_500_000, 2_500_000, HowTheLineWasMeasured::Observed, Instant::atEpochSeconds(THE_LINE_CHANGES_AT - 60), WhetherItGoesThroughTheTunnel::Beside))
        ->keepingHours(TheHouseholdsHours::from('07:00', '23:00', ''));
}

/** The line a change would leave or left. */
function theLineAfterTheChange(): HowTheLineIsShared
{
    return HowTheLineIsShared::standing(WhereTheLineStands::Limited, 'The stack takes half the line', 'Half of 100 Mbit/s', 'A quarter of 20 Mbit/s', Remarks::of(), Remarks::of());
}

/** One client keeping its download and overrunning its upload, one that did not answer. */
function aChangeToTheLineReported(WhatTheLineRunDid $did, ?WhereALiftStands $lift = null): TheLineAsChanged
{
    return TheLineAsChanged::said(
        theLineAfterTheChange(),
        $did,
        $lift ?? WhereALiftStands::none(),
        AClientsHolding::answered(
            'qbittorrent',
            WhatADownloadClientSaid::Fetching,
            ADirectionHeld::said(WhatALimitCameTo::Holding, ARateGiven::of(WhichRate::Asked, 6_250_000), ARateGiven::of(WhichRate::Accepted, 6_250_000)),
            ADirectionHeld::said(WhatALimitCameTo::Overrunning, ARateGiven::of(WhichRate::Asked, 625_000), ARateGiven::of(WhichRate::Accepted, 625_000), ARateGiven::of(WhichRate::Moving, 1_250_000)),
        ),
        AClientsHolding::silent('sabnzbd', WhatADownloadClientSaid::Unsaid, 'connection refused'),
    );
}

/**
 * A stack that rehearses as `$rehearsal` and, on a yes, says `$change`.
 *
 * @param WhatBecameOfTheWork<TheLineAsChanged>|null $rehearsal
 * @param WhatBecameOfTheWork<TheLineAsChanged>|null $change
 */
function aStackThatChangesItsLine(?WhatBecameOfTheWork $rehearsal = null, ?WhatBecameOfTheWork $change = null): AStackThatSharesItsLine
{
    return AStackThatSharesItsLine::answering(
        $rehearsal ?? WhatBecameOfTheWork::answered(aChangeToTheLineReported(WhatTheLineRunDid::Rehearsed)),
        $change ?? WhatBecameOfTheWork::answered(aChangeToTheLineReported(WhatTheLineRunDid::Applied)),
    );
}

/** The screen, with a stack it knows and a keychain holding whatever a case says. */
function theLineChangeScreen(
    AStackThatSharesItsLine $sharing,
    ?AStackThatRationsItsLine $rationing = null,
    ?AKeychainInMemory $keychain = null,
    bool $signedIn = true,
): HowTheLineIsSharedHere {
    $stack = theStackWhoseLineIsChanged();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new HowTheLineIsSharedHere(
        $sharing,
        $rationing ?? AStackThatRationsItsLine::with(theLineBeforeTheChange()),
        $keychain,
        AroundThePhone::holding(StacksInMemory::holding($stack)),
        FrozenClock::at(Instant::atEpochSeconds(THE_LINE_CHANGES_AT)),
        new AppsSettingsThatOpen(),
        AroundThePhone::listening(),
    );
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

/** The screen once a rehearsal of half the line has come back. */
function aRehearsedHalfOfTheLine(AStackThatSharesItsLine $sharing): HowTheLineIsSharedHere
{
    $screen = theLineChangeScreen($sharing);
    $screen->down = '50%';
    $screen->rehearse();
    $screen->whileItRuns();
    $screen->howItIsGoing();

    return $screen;
}

/** Every part of a change and its lift, as one line, to compare what was sent. */
function everyPartSentOfTheLine(ALineChange $change): string
{
    $parts = [];

    foreach ($change as $part) {
        $parts[] = sprintf('%s=%s', $part->part()->value, $part->text());
    }

    return sprintf('%s lift=%s', implode(' ', $parts), $change->lift()->either(
        for: static fn(int $minutes): TheWordCarriedOut => new TheWordCarriedOut((string) $minutes),
        none: static fn(): TheWordCarriedOut => new TheWordCarriedOut('none'),
    )->said);
}

it('opens on the line as it stands, each limit beside the figure it is set against, and asks nothing of the change', function (): void {
    $sharing = aStackThatChangesItsLine();
    $drawn = WhatTheDeviceWouldDraw::by(theLineChangeScreen($sharing));
    $down = HowFast::of(12_500_000);

    expect($drawn->said())->toContain(
        __('stacks.line.down', ['says' => 'No limit on 100 Mbit/s']),
        __('stacks.line.up', ['says' => 'No limit on 20 Mbit/s']),
        __('stacks.line.up_from', ['from' => '07:00', 'to' => '23:00']),
        __('stacks.line_change.left_blank'),
    )
        ->and(implode("\n", $drawn->said()))->toContain((string) $down->figure)
        ->and($drawn->offers())->toContain(__('stacks.line_change.rehearse'))
        ->and($drawn->offers())->not->toContain(__('stacks.line_change.apply'))
        ->and($sharing->rehearsed())->toBe([]);
});

it('says a change asking nothing, or a lift that is not whole minutes, on the screen rather than sending it', function (string $lift, string $said, string $down = ''): void {
    $sharing = aStackThatChangesItsLine();
    $screen = theLineChangeScreen($sharing);
    $screen->lift = $lift;
    $screen->down = $down;
    $screen->rehearse();

    expect($screen->howItIsGoing()->notAskable)->toBe($said)
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__($said))
        ->and($sharing->rehearsed())->toBe([]);
})->with([
    'nothing' => ['', 'stacks.line_change.nothing_asked'],
    'a lift in words' => ['an hour', 'stacks.line_change.lift.is_minutes'],
    'only spaces typed' => ['  ', 'stacks.line_change.nothing_asked', '   '],
]);

it('sends a lift typed with spaces around it as its minutes', function (): void {
    $sharing = aStackThatChangesItsLine();
    $screen = theLineChangeScreen($sharing);
    $screen->lift = ' 45 ';
    $screen->rehearse();

    expect(array_map(everyPartSentOfTheLine(...), $sharing->rehearsed()))->toBe([' lift=45']);
});

it('rehearses every part as typed, follows the work, and draws it labelled as a rehearsal with each client', function (): void {
    $sharing = aStackThatChangesItsLine();
    $screen = theLineChangeScreen($sharing);
    $screen->down = '50%';
    $screen->up = '25%';
    $screen->active = '07:00-23:00';
    $screen->line = '60MiB/6MiB';
    $screen->cap = '500GiB';
    $screen->exceededIs(WhatACapDoes::Throttle->value);
    $screen->liftFor('60');
    $screen->rehearse();

    expect($screen->howItIsGoing()->stands->isWorking())->toBeTrue()
        ->and($screen->awaitsAnOutcome())->toBeTrue()
        ->and(array_map(everyPartSentOfTheLine(...), $sharing->rehearsed()))->toBe(['down=50% up=25% active=07:00-23:00 line=60MiB/6MiB cap=500GiB exceeded=throttle lift=60']);

    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen);
    $overrun = HowFast::of(1_250_000);

    expect($screen->awaitsAnOutcome())->toBeFalse()
        ->and(array_map(static fn(Job $job): string => $job->shown(), $sharing->followed()))->toBe([AStackThatSharesItsLine::THE_REHEARSAL])
        ->and($drawn->said())->toContain(
            __(WhatTheLineRunDid::Rehearsed->saidOnTheScreen()),
            __('stacks.line.down', ['says' => 'Half of 100 Mbit/s']),
            'qbittorrent',
            __('stacks.line_change.direction.down', ['verdict' => aLineOfTheLineChange(WhatALimitCameTo::Holding->saidOnTheScreen())]),
            __('stacks.line_change.direction.up', ['verdict' => aLineOfTheLineChange(WhatALimitCameTo::Overrunning->saidOnTheScreen())]),
            __('stacks.line_change.missed'),
            __('stacks.line_change.rate.moving', ['figure' => $overrun->figure, 'unit' => aLineOfTheLineChange($overrun->said)]),
            __('stacks.line_change.silent', ['said' => 'connection refused']),
        )
        ->and($drawn->said())->toContain(__('stacks.pausing.said.fetching'))
        ->and(array_map(static fn(AClientHoldingAsShown $client): ?string => $client->pullingSaid, $screen->howItIsGoing()->change->clients ?? []))->toBe(['stacks.pausing.said.fetching', null])
        ->and($drawn->offers())->toContain(__('stacks.line_change.apply'))
        ->and($sharing->carriedOut())->toBe([]);
});

it('makes the change the rehearsal was asked with, not what is in the fields when the yes is tapped', function (): void {
    $sharing = aStackThatChangesItsLine();
    $rationing = AStackThatRationsItsLine::with(theLineBeforeTheChange());
    $screen = theLineChangeScreen($sharing, $rationing);
    $screen->answer();
    $screen->down = '50%';
    $screen->rehearse();
    $screen->whileItRuns();
    $screen->howItIsGoing();
    $screen->down = '90%';
    $screen->apply();

    expect(array_map(everyPartSentOfTheLine(...), $sharing->carriedOut()))->toBe(['down=50% lift=none'])
        ->and($screen->howItIsGoing()->stands->isWorking())->toBeTrue();

    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->said())->toContain(__(WhatTheLineRunDid::Applied->saidOnTheScreen()))
        ->and($drawn->offers())->not->toContain(__('stacks.line_change.apply'))
        ->and($rationing->askings())->toBe(2);
});

it('sends no yes where nothing was rehearsed, or where the last answer was not a rehearsal', function (): void {
    $sharing = aStackThatChangesItsLine();
    $screen = theLineChangeScreen($sharing);
    $screen->apply();

    $applied = aRehearsedHalfOfTheLine(aStackThatChangesItsLine(rehearsal: WhatBecameOfTheWork::answered(aChangeToTheLineReported(WhatTheLineRunDid::Applied))));
    $applied->apply();

    expect($sharing->carriedOut())->toBe([])
        ->and($applied->howItIsGoing()->change?->mayBeApplied)->toBeFalse();
});

it('draws a refusal as the stack\'s own sentence and remedy, and offers starting again', function (): void {
    $why = ARefusalInItsWords::said('half could not be read as a download limit', '', WhatTheRefusalNamed::nothing(), 'Write a download limit as 50%, 2MiB or unlimited');
    $screen = aRehearsedHalfOfTheLine(aStackThatChangesItsLine(rehearsal: WhatBecameOfTheWork::refused($why)));
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->said())->toContain(
        __('stacks.line_change.refused'),
        'half could not be read as a download limit',
        __('stacks.refusal.remedy', ['remedy' => 'Write a download limit as 50%, 2MiB or unlimited']),
    )
        ->and($drawn->offers())->toContain(__('stacks.line_change.start_again'));

    $screen->startAgain();

    expect($screen->howItIsGoing()->refused)->toBeNull()
        ->and($screen->down)->toBe('50%');
});

it('says so where the stack has no outcome for the work any more', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(aRehearsedHalfOfTheLine(aStackThatChangesItsLine(rehearsal: WhatBecameOfTheWork::ended())));

    expect($drawn->said())->toContain(__('stacks.line_change.no_outcome'));
});

it('draws a lift in force and one that ran out, with the stack\'s words for it', function (WhereALiftStands $lift, string $key, int $count): void {
    $screen = aRehearsedHalfOfTheLine(aStackThatChangesItsLine(rehearsal: WhatBecameOfTheWork::answered(aChangeToTheLineReported(WhatTheLineRunDid::Rehearsed, $lift))));
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->said())->toContain(trans_choice($key, $count), 'No limits until 21:10');
})->with([
    'in force' => [WhereALiftStands::inForce(3_600, 'No limits until 21:10'), 'stacks.line_change.lift.in_force.hours', 1],
    'run out' => [WhereALiftStands::expired(300, 'No limits until 21:10'), 'stacks.line_change.lift.expired.minutes', 5],
]);

it('says the stack runs no download client where it runs none', function (): void {
    $none = TheLineAsChanged::said(theLineAfterTheChange(), WhatTheLineRunDid::Rehearsed, WhereALiftStands::none());
    $drawn = WhatTheDeviceWouldDraw::by(aRehearsedHalfOfTheLine(aStackThatChangesItsLine(rehearsal: WhatBecameOfTheWork::answered($none))));

    expect($drawn->said())->toContain(__('stacks.line_change.no_clients'));
});

it('marks the chips chosen, and leaves a part as it is on its own chip', function (): void {
    $screen = theLineChangeScreen(aStackThatChangesItsLine());
    $screen->exceededIs(WhatACapDoes::Pause->value);
    $screen->liftFor('240');

    expect(theChipsChosen($screen->exceededChoice()->offered))->toBe(['pause'])
        ->and($screen->exceededChoice()->said)->toBe('stacks.line_change.exceeded.pause')
        ->and(theChipsChosen($screen->liftChoice()))->toBe(['240'])
        ->and(array_map(static fn(AChoiceOfASettingAsShown $chip): string => $chip->word, $screen->liftChoice()))->toBe(['15', '60', '240', ''])
        ->and($screen->lift)->toBe('240');

    $screen->exceededIs('explode');
    $screen->liftFor('7');

    expect(theChipsChosen($screen->exceededChoice()->offered))->toBe([''])
        ->and(theChipsChosen($screen->liftChoice()))->toBe([''])
        ->and($screen->lift)->toBe('');
});

it('a refused credential signs this device out and lets the session go', function (): void {
    $keychain = AKeychainInMemory::working();
    $screen = theLineChangeScreen(AStackThatSharesItsLine::sayingAtOnce(WhatBecameOfTheWork::met(Obstacle::of(KindOfObstacle::CredentialWasRefused))), keychain: $keychain);
    $screen->down = '50%';
    $screen->rehearse();

    expect($screen->howItIsGoing()->stands->went->isSignedIn)->toBeFalse()
        ->and($keychain->isHolding(theStackWhoseLineIsChanged()->id()))->toBeFalse();
});

it('a stack that is not answering leaves the session, and says what stood in the way', function (): void {
    $screen = theLineChangeScreen(AStackThatSharesItsLine::sayingAtOnce(WhatBecameOfTheWork::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))));
    $screen->down = '50%';
    $screen->rehearse();

    expect($screen->howItIsGoing()->stands->went->cameBack())->toBeFalse()
        ->and($screen->howItIsGoing()->stands->went->isSignedIn)->toBeTrue()
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->not->toBe([]);
});

it('a session that has ended asks nothing of the stack', function (): void {
    $sharing = aStackThatChangesItsLine();
    $screen = theLineChangeScreen($sharing, signedIn: false);
    $screen->down = '50%';
    $screen->rehearse();

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($screen->howItIsGoing()->stands->went->isSignedIn)->toBeFalse()
        ->and($sharing->rehearsed())->toBe([]);
});

it('draws what stood in the way of reading the line as it stands', function (): void {
    $screen = theLineChangeScreen(aStackThatChangesItsLine(), AStackThatRationsItsLine::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)));

    expect($screen->answer()->went->cameBack())->toBeFalse()
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->not->toContain(__('stacks.line_change.rehearse'));
});

it('asking again reads the line again, and follows the work on its own cadence', function (): void {
    $rationing = AStackThatRationsItsLine::with(theLineBeforeTheChange());
    $sharing = AStackThatSharesItsLine::answering(WhatBecameOfTheWork::underway(Job::named(AStackThatSharesItsLine::THE_REHEARSAL)), WhatBecameOfTheWork::ended());
    $screen = theLineChangeScreen($sharing, $rationing);
    $screen->answer();
    $screen->down = '50%';
    $screen->rehearse();
    $screen->again();
    $screen->answer();

    expect($rationing->askings())->toBe(2)
        ->and($sharing->followed())->toBe([])
        ->and($screen->awaitsAnOutcome())->toBeTrue();

    $screen->whileItRuns();
    $screen->howItIsGoing();

    expect(count($sharing->followed()))->toBe(1);
});

it('keeps a rehearsal drawn while the line is read again around it', function (): void {
    $screen = aRehearsedHalfOfTheLine(aStackThatChangesItsLine());
    $screen->whileItMoves();

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__(WhatTheLineRunDid::Rehearsed->saidOnTheScreen()));
});

it('renders its own view', function (): void {
    expect(theLineChangeScreen(aStackThatChangesItsLine())->render()->name())->toBe('operator::how-the-line-is-shared-here');
});
