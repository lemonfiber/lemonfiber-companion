<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function expect;
use function it;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\AgainstThePins;
use Modules\Kernel\Api\HowItEnded;
use Modules\Kernel\Api\HowToUndoIt;
use Modules\Kernel\Api\Release;
use Modules\Kernel\Api\Upkeep;
use Modules\Sdk\Api\ChangelogIsUnreadable;
use Modules\Sdk\Api\Standings;
use Modules\Sdk\Api\UpkeepIsUnreadable;

use function sprintf;

use Tests\Support\WhatTheContractAccepts;

/**
 * What an `update` envelope this side cannot read does to the reader.
 *
 * Built by hand rather than fetched, for {@see aRosterSaying()}'s reason: what
 * is tested here is what happens when the wire says something the contract does
 * not allow, which a client honouring the contract could not produce.
 *
 * The payload a stack really sends is asserted one layer up, in
 * `tests/Contract/KeepingCurrentContractTest.php`, and held to the generated
 * types so that it stays one. This file is the other half — every way the
 * reading can fail, and the refusal each produces.
 *
 * **One rule is under all of it: this side refuses rather than invents.** Every
 * case below has a reassuring direction to default in — nothing waiting, nothing
 * running, nothing changing, nothing applied — and each of those is an answer
 * somebody would stop worrying on.
 *
 * The release records' own refusals are {@see \Modules\Sdk\Internal\Changelogs}'s,
 * and asserted in its suite; this one asserts that they reach the adapter.
 *
 * @param array<mixed> $data
 *
 * @return Envelope<mixed>
 */
function anUpkeepSaying(array $data): Envelope
{
    return new Envelope(1, 'update', $data);
}

/**
 * What a stack sends, with whatever this case is about changed.
 *
 * The changelog is a separate argument because it carries a `state` of its own
 * beside the top-level one, and the two answer different questions: the
 * top-level one says whether any service would move, and the changelog's says
 * whether the release record matches the running build. The defaults below
 * disagree on purpose — no update available, notes still pending — which is
 * the realistic reading a stack sends after it was just updated.
 *
 * **Every field the contract requires is here, including the four no reader
 * touches.** A fixture short of one is a sample of a payload no stack sends,
 * and a reader tested only against it has been tested against nothing — so
 * `confirmed`, `in_flight`, `stack_edits` and the changelog's `requirements`
 * are carried whether or not anything reads them. What this app does not read
 * and why is the register beside this one's subject; what a stack sends is
 * this one's.
 *
 * @param  array<mixed> $changelog
 * @param  array<mixed> $differently
 * @return array<mixed>
 */
function whatAStackSaysAboutItsUpkeep(array $changelog = [], array $differently = []): array
{
    return [
        'state' => 'current',
        'applied' => [],
        'changes' => [],
        'confirmed' => false,
        'in_flight' => [],
        'stack_edits' => [],
        'changelog' => [
            'state' => 'pending',
            'releases' => [],
            'requirements' => [],
            ...$changelog,
        ],
        ...$differently,
    ];
}

/**
 * One release the changelog lists, with whatever this case is about changed.
 *
 * @param  array<mixed>  $differently
 * @return array<mixed>
 */
function aReleaseSaying(array $differently = []): array
{
    return ['version' => '4.1.0', 'user_facing' => true, ...$differently];
}

/**
 * The release a stack says it is running, which is a wider shape than a listed
 * one: the contract puts the grouped notes on the release in use only.
 *
 * @param  array<mixed>  $differently
 * @return array<mixed>
 */
function aReleaseInUseSaying(array $differently = []): array
{
    return [
        'version' => '4.0.15',
        'user_facing' => true,
        'tag' => 'v4.0.15',
        'groups' => [],
        ...$differently,
    ];
}

/**
 * One answer carried out of an `either()` arm, which hands back objects.
 *
 * {@see Upkeep::inUse()} returns an object so that a caller cannot fold its two
 * arms into a nullable string — which would lose the difference between a stack
 * that has not looked and one running nothing.
 */
final readonly class WhatTheStackTurnedOutToBeOn
{
    public function __construct(public string $version) {}
}

/** What the stack said it is on, as a word a test can compare. */
function whatItIsOn(Upkeep $upkeep): string
{
    return $upkeep->inUse(
        named: static fn(Release $inUse): WhatTheStackTurnedOutToBeOn
            => new WhatTheStackTurnedOutToBeOn($inUse->version()),
        unstated: static fn(): WhatTheStackTurnedOutToBeOn
            => new WhatTheStackTurnedOutToBeOn('unstated'),
    )->version;
}

/**
 * The reading, for a case that expects one.
 *
 * @param  array<mixed>  $data
 */
function theUpkeepIn(array $data): Upkeep
{
    return Standings::in(anUpkeepSaying($data));
}

it('reads whether an update is available off the pins, not off the changelog', function (): void {
    // The payload carries two fields called `state`. The changelog's `pending`
    // says the running build's release has no notes yet; the top-level
    // `current` says no service would move. Read the other way round, this
    // stack would be offered an update it has not got.
    $justUpdated = theUpkeepIn(whatAStackSaysAboutItsUpkeep());
    $behind = theUpkeepIn(whatAStackSaysAboutItsUpkeep(['state' => 'current'], ['state' => 'updates-available']));

    expect($justUpdated->againstThePins())->toBe(AgainstThePins::Current)
        ->and($behind->againstThePins())->toBe(AgainstThePins::UpdatesAvailable);
});

it('N2-R15 — reads the release in use off the changelog', function (): void {
    $upkeep = theUpkeepIn(whatAStackSaysAboutItsUpkeep([
        'running' => aReleaseInUseSaying(),
    ]));

    expect(whatItIsOn($upkeep))->toBe('4.0.15');
});

it('N2-R15 — a stack that named no release in use is not a stack running nothing', function (): void {
    // Absent rather than empty. Not having looked and running nothing are
    // different answers, and a screen handed a blank version would show the
    // second when the stack said the first.
    expect(whatItIsOn(theUpkeepIn(whatAStackSaysAboutItsUpkeep())))->toBe('unstated')
        ->and(whatItIsOn(theUpkeepIn(whatAStackSaysAboutItsUpkeep(['running' => null]))))->toBe('unstated');
});

it('reads the releases the changelog lists as history, withdrawn ones included', function (): void {
    $upkeep = theUpkeepIn(whatAStackSaysAboutItsUpkeep([
        'releases' => [aReleaseSaying(), aReleaseSaying(['version' => '4.0.17', 'withdrawn' => '2026-09-01'])],
    ]));

    $versions = [];

    foreach ($upkeep->history() as $release) {
        $versions[] = $release->version();
    }

    expect($versions)->toBe(['4.1.0', '4.0.17'])
        // Listed releases are not an update: the pins said `current`.
        ->and($upkeep->hasSomethingToOffer())->toBeFalse();
});

it('N2-R18 — reads what became of each service the last update touched', function (): void {
    $upkeep = theUpkeepIn(whatAStackSaysAboutItsUpkeep(differently: ['applied' => [
        ['service' => 'jellyfin', 'ending' => 'not-reached', 'reversal' => 'restore'],
    ]]));

    $rows = [];

    foreach ($upkeep->howItWent() as $took) {
        $rows[] = $took;
    }

    expect($rows)->toHaveCount(1)
        ->and($rows[0]->service()->named())->toBe('jellyfin')
        ->and($rows[0]->ending())->toBe(HowItEnded::NotReached)
        ->and($rows[0]->undo())->toBe(HowToUndoIt::Restore);
});

it('N2-R17 — leaves out a change the stack has already refused', function (): void {
    // Not a service an update would change, so naming it in a confirmation
    // would have somebody agree to a service that was never going to move.
    $upkeep = theUpkeepIn(whatAStackSaysAboutItsUpkeep(differently: ['changes' => [
        ['service' => 'jellyfin', 'refused' => false, 'irreversible' => false],
        ['service' => 'sonarr', 'refused' => true, 'irreversible' => false],
    ]]));

    $named = [];

    foreach ($upkeep->changing() as $service) {
        $named[] = $service->named();
    }

    expect($named)->toBe(['jellyfin']);
});

it('N2-R22 — names the services a change cannot be put back for', function (): void {
    // Per change and not per release. An update can move three services and be
    // undoable for two of them, and a warning covering all three is one an
    // operator learns to scroll past.
    $upkeep = theUpkeepIn(whatAStackSaysAboutItsUpkeep(differently: ['changes' => [
        ['service' => 'jellyfin', 'refused' => false, 'irreversible' => false],
        ['service' => 'sonarr', 'refused' => false, 'irreversible' => true],
        ['service' => 'radarr', 'refused' => false, 'irreversible' => true],
    ]]));

    $named = [];

    foreach ($upkeep->cannotBePutBack() as $service) {
        $named[] = $service->named();
    }

    // The whole list still moves; only two of it is the part nothing reverses.
    expect($named)->toBe(['sonarr', 'radarr'])
        ->and($upkeep->changing()->count())->toBe(3);
});

it('N2-R22 — leaves out a change the stack has refused, however it is marked', function (): void {
    // Whether undoing would work is a question about something that is not
    // going to happen, and naming the service would put it in a warning about
    // an evening it takes no part in.
    $upkeep = theUpkeepIn(whatAStackSaysAboutItsUpkeep(differently: ['changes' => [
        ['service' => 'jellyfin', 'refused' => true, 'irreversible' => true],
        ['service' => 'sonarr', 'refused' => false, 'irreversible' => true],
    ]]));

    $named = [];

    foreach ($upkeep->cannotBePutBack() as $service) {
        $named[] = $service->named();
    }

    expect($named)->toBe(['sonarr']);
});

it('N2-R22 — says so where nothing an update does is permanent', function (): void {
    // The ordinary evening, and it has to read as one rather than as an
    // absence: a screen asking gets an answer either way.
    $upkeep = theUpkeepIn(whatAStackSaysAboutItsUpkeep(differently: ['changes' => [
        ['service' => 'jellyfin', 'refused' => false, 'irreversible' => false],
    ]]));

    expect($upkeep->cannotBePutBack()->isEmpty())->toBeTrue();
});

it('N2-R22 — refuses a change that never said whether it can be put back', function (): void {
    // Refused rather than defaulted. Reading an absent field as *this can be
    // undone* would drop the sentence the operator needs most, and drop it on
    // the one payload shape that failed to say.
    expect(fn(): object => theUpkeepIn(whatAStackSaysAboutItsUpkeep(differently: ['changes' => [
        ['service' => 'jellyfin', 'refused' => false],
    ]])))->toThrow(UpkeepIsUnreadable::class);
});

it('N2-R22 — refuses a change that answered with something other than yes or no', function (): void {
    expect(fn(): object => theUpkeepIn(whatAStackSaysAboutItsUpkeep(differently: ['changes' => [
        ['service' => 'jellyfin', 'refused' => false, 'irreversible' => 'maybe'],
    ]])))->toThrow(UpkeepIsUnreadable::class);
});

it('refuses a payload that is not a shape at all', function (): void {
    // A word where the whole reading belongs. Read as *nothing*, every question
    // below it would answer with the reassuring blank.
    expect(fn(): object => Standings::in(new Envelope(1, 'update', 'nothing to report')))
        ->toThrow(UpkeepIsUnreadable::class, 'data');
});

it('refuses a payload with nothing in it', function (): void {
    expect(fn(): object => Standings::in(anUpkeepSaying([])))
        ->toThrow(UpkeepIsUnreadable::class);
});

it('refuses a reading with no changelog, as a changelog it cannot read', function (): void {
    expect(fn(): object => theUpkeepIn(['state' => 'current', 'applied' => [], 'changes' => []]))
        ->toThrow(ChangelogIsUnreadable::class, 'changelog');
});

it('refuses a release the changelog lists and it cannot read', function (): void {
    expect(fn(): object => theUpkeepIn(whatAStackSaysAboutItsUpkeep([
        'releases' => [aReleaseSaying(['version' => '   '])],
    ])))->toThrow(ChangelogIsUnreadable::class, 'Release 1');
});

it('refuses a running release it cannot read', function (): void {
    expect(fn(): object => theUpkeepIn(whatAStackSaysAboutItsUpkeep([
        'running' => aReleaseSaying(['user_facing' => 'yes']),
    ])))->toThrow(ChangelogIsUnreadable::class, 'running release');
});

it('refuses a reading that never said where the services stand', function (): void {
    // Built without the field rather than built and then unset, so the shape
    // this case is about is the one the reader is handed. The changelog's
    // `state` is there and is not an answer to this.
    expect(fn(): object => theUpkeepIn([
        'applied' => [],
        'changes' => [],
        'changelog' => ['state' => 'pending', 'releases' => []],
    ]))->toThrow(UpkeepIsUnreadable::class, 'state');
});

it('refuses a standing that is not a word', function (): void {
    expect(fn(): object => theUpkeepIn(whatAStackSaysAboutItsUpkeep(differently: ['state' => 3])))
        ->toThrow(UpkeepIsUnreadable::class, 'state');
});

it('refuses a standing this app has no case for', function (): void {
    // `pending` is a word of the changelog's, and not one the pins are
    // described in. Read here, it is a stack speaking a vocabulary this side
    // does not know, and rendering the word itself would put lemonfiber's
    // vocabulary on a screen in place of a sentence somebody wrote.
    expect(fn(): object => theUpkeepIn(whatAStackSaysAboutItsUpkeep(differently: ['state' => 'pending'])))
        ->toThrow(UpkeepIsUnreadable::class, 'pending');
});

it('refuses a reading with nothing about what an update would change', function (): void {
    expect(fn(): object => theUpkeepIn([
        'state' => 'current',
        'applied' => [],
        'changelog' => ['state' => 'pending', 'releases' => []],
    ]))->toThrow(UpkeepIsUnreadable::class, 'changes');
});

it('refuses changes that are not a list', function (): void {
    expect(fn(): object => theUpkeepIn(whatAStackSaysAboutItsUpkeep(differently: ['changes' => 'two'])))
        ->toThrow(UpkeepIsUnreadable::class, 'changes');
});

it('names a change by where it sat', function (): void {
    expect(fn(): object => theUpkeepIn(whatAStackSaysAboutItsUpkeep(differently: [
        'changes' => [['service' => 'jellyfin', 'refused' => false, 'irreversible' => false], 'a string'],
    ])))->toThrow(UpkeepIsUnreadable::class, 'Change 2');
});

it('refuses a change that never said whether the stack would make it', function (): void {
    // The reassuring default here is *it will happen*, which is the direction
    // that puts a service in a confirmation it was never going to move for.
    expect(fn(): object => theUpkeepIn(whatAStackSaysAboutItsUpkeep(differently: [
        'changes' => [['service' => 'jellyfin']],
    ])))->toThrow(UpkeepIsUnreadable::class, 'Change 1');
});

it('refuses a refusal that is not a yes or a no', function (): void {
    expect(fn(): object => theUpkeepIn(whatAStackSaysAboutItsUpkeep(differently: [
        'changes' => [['service' => 'jellyfin', 'refused' => 'no']],
    ])))->toThrow(UpkeepIsUnreadable::class, 'Change 1');
});

it('refuses a change with no service to name', function (): void {
    expect(fn(): object => theUpkeepIn(whatAStackSaysAboutItsUpkeep(differently: [
        'changes' => [['refused' => false]],
    ])))->toThrow(UpkeepIsUnreadable::class, 'Change 1');
});

it('refuses a service name that is not a word', function (): void {
    expect(fn(): object => theUpkeepIn(whatAStackSaysAboutItsUpkeep(differently: [
        'changes' => [['service' => 7, 'refused' => false]],
    ])))->toThrow(UpkeepIsUnreadable::class, 'Change 1');
});

it('N2-R17 — goes on reading after a change the stack refused', function (): void {
    // A refused change is skipped, not stopped at. A reader that broke out of
    // the loop would silently drop every service named after the first refusal,
    // and the confirmation would name fewer services than the update changes —
    // which is the same false promise from the other direction.
    $upkeep = theUpkeepIn(whatAStackSaysAboutItsUpkeep(differently: ['changes' => [
        ['service' => 'sonarr', 'refused' => true, 'irreversible' => false],
        ['service' => 'jellyfin', 'refused' => false, 'irreversible' => false],
        ['service' => 'radarr', 'refused' => false, 'irreversible' => false],
    ]]));

    $named = [];

    foreach ($upkeep->changing() as $service) {
        $named[] = $service->named();
    }

    expect($named)->toBe(['jellyfin', 'radarr']);
});

it('counts a refused change when it names where a later one sat', function (): void {
    // The position is what a refusal has instead of a name, so it has to count
    // the rows it skipped. Reported as row three here, which is where it is.
    expect(fn(): object => theUpkeepIn(whatAStackSaysAboutItsUpkeep(differently: ['changes' => [
        ['service' => 'sonarr', 'refused' => true, 'irreversible' => false],
        ['service' => 'jellyfin', 'refused' => false, 'irreversible' => false],
        ['refused' => false],
    ]])))->toThrow(UpkeepIsUnreadable::class, 'Change 3');
});

it('stands in for a stack with payloads the contract would accept', function (): void {
    // The payload every case here is built from, held to the generated types
    // rather than to the reader that reads it. A fixture is written by whoever
    // wrote the reader, so the two can agree about a field that is not there
    // and every assertion above pass against a machine nobody has run them
    // against.
    //
    // The two releases are separate entries because they are separate shapes:
    // the contract puts the grouped notes on the release in use only.
    $payloads = [
        'the reading' => whatAStackSaysAboutItsUpkeep(),
        'an update available' => whatAStackSaysAboutItsUpkeep(differently: ['state' => 'updates-available']),
        'a release the changelog lists' => whatAStackSaysAboutItsUpkeep(['releases' => [aReleaseSaying()]]),
        'the release in use' => whatAStackSaysAboutItsUpkeep(['running' => aReleaseInUseSaying()]),
    ];

    foreach ($payloads as $which => $payload) {
        expect(WhatTheContractAccepts::complaintsAbout('UpdateEnvelope', ['kind' => 'update', 'data' => $payload]))
            ->toBe([], sprintf(
                "The payload this suite stands in for a stack with is not one a stack would send: %s.\n",
                $which,
            ));
    }
});
