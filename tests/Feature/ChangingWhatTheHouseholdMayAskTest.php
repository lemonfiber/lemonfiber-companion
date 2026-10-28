<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AMember;
use Modules\Kernel\Api\AnAllowanceChange;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\ARequestLimit;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowTheHouseholdAsks;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Sentence;
use Modules\Kernel\Api\Sentences;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SomebodyInTheHousehold;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheAllowanceAsChanged;
use Modules\Kernel\Api\TheMembers;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Kernel\Api\WhetherItWasRehearsed;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\WhatTheHouseholdIsAllowed;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AMemberWhoIsOwed;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatChoosesWhatIsAllowed;
use Tests\Support\Fakes\AStackThatInvites;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheDeviceWouldDraw;

function theStackWhoseAllowanceChanges(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('d', Nonce::SHORTEST))),
        StackName::of('The cellar'),
        Address::of('https://192.168.1.91'),
        Fingerprint::of(str_repeat('d', Fingerprint::CHARACTERS)),
    );
}

const WHAT_THE_REHEARSAL_SAYS = 'alex waits for you, and is counted against 5 requests every 7 days — rehearsed, and nothing was written';

const WHAT_THE_CHANGE_SAYS = 'alex waits for you, and is counted against 5 requests every 7 days';

/**
 * @return WhatBecameOfTheWork<TheAllowanceAsChanged>
 */
function anAllowanceReported(WhetherItWasRehearsed $did, string $said): WhatBecameOfTheWork
{
    return WhatBecameOfTheWork::answered(TheAllowanceAsChanged::said($did, Sentences::of(Sentence::of($said))));
}

/**
 * @param WhatBecameOfTheWork<TheAllowanceAsChanged>|null $rehearsal
 * @param WhatBecameOfTheWork<TheAllowanceAsChanged>|null $change
 */
function aStackThatChangesWhatIsAllowed(?WhatBecameOfTheWork $rehearsal = null, ?WhatBecameOfTheWork $change = null): AStackThatChoosesWhatIsAllowed
{
    return AStackThatChoosesWhatIsAllowed::answering(
        $rehearsal ?? anAllowanceReported(WhetherItWasRehearsed::Rehearsed, WHAT_THE_REHEARSAL_SAYS),
        $change ?? anAllowanceReported(WhetherItWasRehearsed::CarriedOut, WHAT_THE_CHANGE_SAYS),
    );
}

function theAllowanceChangeScreen(AStackThatChoosesWhatIsAllowed $choosing, ?AStackThatInvites $inviting = null, ?AMemberWhoIsOwed $owing = null, bool $signedIn = true): WhatTheHouseholdIsAllowed
{
    $stack = theStackWhoseAllowanceChanges();
    $keychain = AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new WhatTheHouseholdIsAllowed(
        $owing ?? AMemberWhoIsOwed::owed(Sentences::of(Sentence::of('Everything the household asks for arrives unseen.'))),
        $choosing,
        $inviting ?? AStackThatInvites::answering()->holding(TheMembers::of(AMember::joined('alex'), AMember::joined('robin'))),
        $keychain,
        AroundThePhone::holding(StacksInMemory::holding($stack)),
        new AppsSettingsThatOpen(),
        AroundThePhone::listening(),
    );
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

function aRehearsedLimitForAlex(AStackThatChoosesWhatIsAllowed $choosing): WhatTheHouseholdIsAllowed
{
    $screen = theAllowanceChangeScreen($choosing);
    $screen->forSomebody('alex');
    $screen->policyIs(HowTheHouseholdAsks::EverythingWaits->value);
    $screen->requests = '5';
    $screen->days = '7';
    $screen->rehearse();
    $screen->whileItRuns();
    $screen->howItIsGoing();

    return $screen;
}

function everythingAChangeAsks(AnAllowanceChange $change): string
{
    return sprintf(
        'for=%s policy=%s limit=%s',
        $change->for()->either(
            everybody: static fn(): TheWordCarriedOut => new TheWordCarriedOut('everybody'),
            somebody: static fn(SomebodyInTheHousehold $who): TheWordCarriedOut => new TheWordCarriedOut($who->name()),
        )->said,
        $change->policy(
            chosen: static fn(HowTheHouseholdAsks $policy): TheWordCarriedOut => new TheWordCarriedOut($policy->value),
            asItIs: static fn(): TheWordCarriedOut => new TheWordCarriedOut('as it is'),
        )->said,
        $change->limit(
            chosen: static fn(ARequestLimit $limit): TheWordCarriedOut => new TheWordCarriedOut(sprintf('%d/%d', $limit->requests(), $limit->days())),
            asItIs: static fn(): TheWordCarriedOut => new TheWordCarriedOut('as it is'),
        )->said,
    );
}

it('offers the whole household or one member, every policy, and a limit, and asks nothing yet', function (): void {
    $choosing = aStackThatChangesWhatIsAllowed();
    $inviting = AStackThatInvites::answering()->holding(TheMembers::of(AMember::joined('alex'), AMember::joined('robin')));
    $screen = theAllowanceChangeScreen($choosing, $inviting);
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->said())->toContain(__('stacks.allowance_change.title'), __('stacks.allowance_change.left_as_it_is'), 'Everything the household asks for arrives unseen.')
        ->and($drawn->offers())->toContain(
            __('stacks.allowance_change.everybody'),
            __('stacks.allowance_change.one_member'),
            __(HowTheHouseholdAsks::Trusted->saidOnTheScreen()),
            __(HowTheHouseholdAsks::WithinALimit->saidOnTheScreen()),
            __(HowTheHouseholdAsks::EverythingWaits->saidOnTheScreen()),
            __('stacks.allowance_change.rehearse'),
        )
        ->and($drawn->offers())->not->toContain(__('stacks.allowance_change.apply'), 'alex')
        ->and($choosing->rehearsed())->toBe([])
        ->and($inviting->readings())->toBe(0);

    $screen->forAMember();
    $members = WhatTheDeviceWouldDraw::by($screen);

    expect($members->offers())->toContain('alex', 'robin')
        ->and($inviting->readings())->toBe(1);

    $screen->again();
    WhatTheDeviceWouldDraw::by($screen);

    expect($inviting->readings())->toBe(1);
});

it('offers only the whole household where the members could not be read, and says why', function (): void {
    $inviting = AStackThatInvites::answering()->readingAs(Obstacle::of(KindOfObstacle::StackDidNotAnswer));
    $screen = theAllowanceChangeScreen(aStackThatChangesWhatIsAllowed(), $inviting);
    $screen->forAMember();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->said())->toContain(__('stacks.allowance_change.members_unread'))
        ->and($drawn->offers())->toContain(__('stacks.allowance_change.everybody'))
        ->and($drawn->offers())->not->toContain('alex');
});

it('says a change naming nothing, or half a limit, or a limit in words, on the screen rather than sending it', function (string $requests, string $days, string $said): void {
    $choosing = aStackThatChangesWhatIsAllowed();
    $screen = theAllowanceChangeScreen($choosing);
    $screen->requests = $requests;
    $screen->days = $days;
    $screen->rehearse();

    expect($screen->howItIsGoing()->notAskable)->toBe($said)
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__($said))
        ->and($choosing->rehearsed())->toBe([]);
})->with([
    'nothing' => ['', '', 'stacks.allowance_change.nothing_asked'],
    'requests with no days' => ['5', '', 'stacks.allowance_change.limit.both'],
    'days with no requests' => ['', '7', 'stacks.allowance_change.limit.both'],
    'a count in words' => ['five', '7', 'stacks.allowance_change.limit.whole_numbers'],
]);

it('names only what was chosen, leaving the rest as it is', function (string $policy, string $requests, string $days, string $asked): void {
    $choosing = aStackThatChangesWhatIsAllowed();
    $screen = theAllowanceChangeScreen($choosing);
    $screen->policyIs($policy);
    $screen->requests = $requests;
    $screen->days = $days;
    $screen->rehearse();

    expect(array_map(everythingAChangeAsks(...), $choosing->rehearsed()))->toBe([$asked]);
})->with([
    'only a policy' => ['trusted', '', '', 'for=everybody policy=trusted limit=as it is'],
    'only a limit' => ['', ' 3 ', '30', 'for=everybody policy=as it is limit=3/30'],
    'both' => ['within-a-limit', '5', '7', 'for=everybody policy=within-a-limit limit=5/7'],
    'a word the stack does not offer' => ['unlimited', '5', '7', 'for=everybody policy=as it is limit=5/7'],
]);

it('rehearses for the member chosen, follows the work, and draws the stack\'s words labelled as a rehearsal', function (): void {
    $choosing = aStackThatChangesWhatIsAllowed();
    $screen = theAllowanceChangeScreen($choosing);
    $screen->forSomebody('alex');
    $screen->policyIs(HowTheHouseholdAsks::EverythingWaits->value);
    $screen->requests = '5';
    $screen->days = '7';
    $screen->rehearse();

    expect($screen->howItIsGoing()->stands->isWorking())->toBeTrue()
        ->and($screen->awaitsAnOutcome())->toBeTrue()
        ->and(array_map(everythingAChangeAsks(...), $choosing->rehearsed()))->toBe(['for=alex policy=everything-waits limit=5/7']);

    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($screen->awaitsAnOutcome())->toBeFalse()
        ->and(array_map(static fn(Job $job): string => $job->shown(), $choosing->followed()))->toBe([AStackThatChoosesWhatIsAllowed::THE_REHEARSAL])
        ->and($drawn->said())->toContain(__('stacks.allowance_change.did.rehearsed'), WHAT_THE_REHEARSAL_SAYS)
        ->and($drawn->offers())->toContain(__('stacks.allowance_change.apply'))
        ->and($choosing->carriedOut())->toBe([]);
});

it('makes the change the rehearsal was asked with, not what is chosen when the yes is tapped, and reads the household again', function (): void {
    $choosing = aStackThatChangesWhatIsAllowed();
    $screen = aRehearsedLimitForAlex($choosing);
    $screen->answer();
    $screen->forEverybody();
    $screen->policyIs(HowTheHouseholdAsks::Trusted->value);
    $screen->apply();

    expect(array_map(everythingAChangeAsks(...), $choosing->carriedOut()))->toBe(['for=alex policy=everything-waits limit=5/7'])
        ->and($screen->howItIsGoing()->stands->isWorking())->toBeTrue();

    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->said())->toContain(__('stacks.allowance_change.did.carried_out'), WHAT_THE_CHANGE_SAYS)
        ->and($drawn->offers())->not->toContain(__('stacks.allowance_change.apply'))
        ->and($screen->answered)->not->toBeNull();
});

it('sends no yes where nothing was rehearsed, or where the last answer was not a rehearsal', function (): void {
    $choosing = aStackThatChangesWhatIsAllowed();
    theAllowanceChangeScreen($choosing)->apply();

    $made = aStackThatChangesWhatIsAllowed(rehearsal: anAllowanceReported(WhetherItWasRehearsed::CarriedOut, WHAT_THE_CHANGE_SAYS));
    $screen = aRehearsedLimitForAlex($made);
    $screen->apply();

    expect($choosing->carriedOut())->toBe([])
        ->and($made->carriedOut())->toBe([])
        ->and($screen->howItIsGoing()->change?->mayBeApplied)->toBeFalse();
});

it('draws a refusal as the stack\'s own sentence and remedy, and offers starting again', function (): void {
    $why = ARefusalInItsWords::said('nobody called sam is in the household', '', WhatTheRefusalNamed::nothing(), 'Choose one of: alex, robin');
    $screen = aRehearsedLimitForAlex(aStackThatChangesWhatIsAllowed(rehearsal: WhatBecameOfTheWork::refused($why)));
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->said())->toContain(
        __('stacks.allowance_change.refused'),
        'nobody called sam is in the household',
        __('stacks.refusal.remedy', ['remedy' => 'Choose one of: alex, robin']),
    )
        ->and($drawn->offers())->toContain(__('stacks.allowance_change.start_again'));

    $screen->startAgain();

    expect($screen->howItIsGoing()->refused)->toBeNull()
        ->and($screen->who)->toBe('alex');
});

it('says so where the stack has no outcome for the work any more', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(aRehearsedLimitForAlex(aStackThatChangesWhatIsAllowed(rehearsal: WhatBecameOfTheWork::ended())));

    expect($drawn->said())->toContain(__('stacks.allowance_change.no_outcome'));
});

it('says what stood in the way of asking, and lets go of a session the stack refused', function (): void {
    $screen = aRehearsedLimitForAlex(AStackThatChoosesWhatIsAllowed::sayingAtOnce(WhatBecameOfTheWork::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))));

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__(KindOfObstacle::StackDidNotAnswer->said()));

    $refused = aRehearsedLimitForAlex(AStackThatChoosesWhatIsAllowed::sayingAtOnce(WhatBecameOfTheWork::met(Obstacle::of(KindOfObstacle::CredentialWasRefused))));

    expect($refused->howItIsGoing()->stands->went->isSignedIn)->toBeFalse();
});

it('asks nothing and offers signing in where no session is held', function (): void {
    $choosing = aStackThatChangesWhatIsAllowed();
    $screen = theAllowanceChangeScreen($choosing, signedIn: false);
    $screen->policyIs(HowTheHouseholdAsks::Trusted->value);
    $screen->rehearse();

    expect($screen->howItIsGoing()->stands->went->isSignedIn)->toBeFalse()
        ->and($screen->whoMayBeChosen()->membersRead)->toBeFalse()
        ->and($choosing->rehearsed())->toBe([]);
});
