<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function expect;
use function it;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\HowCurrent;
use Modules\Kernel\Api\HowItEnded;
use Modules\Kernel\Api\HowToUndoIt;
use Modules\Kernel\Api\Upkeep;
use Modules\Kernel\Api\VersionInUse;
use Modules\Sdk\Api\Standings;
use Modules\Sdk\Api\UpkeepIsUnreadable;

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
 * **`N2-R14` is the rule under all of it.** Every case below has a reassuring
 * direction to default in — nothing waiting, nothing running, nothing changing,
 * nothing applied — and each of those is an answer somebody would stop worrying
 * on. A reader that filled one in would be inventing the stack's half of the
 * conversation.
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
 * The changelog is a separate argument because most cases are about it: the
 * triple `N2-R15` asks for and the releases `N2-R16` filters both live under
 * it, and the payload carries a second `state` at the top that means something
 * else entirely.
 *
 * @param  array<mixed> $changelog
 * @param  array<mixed> $differently
 * @return array<mixed>
 */
function whatAStackSaysAboutItsUpkeep(array $changelog = [], array $differently = []): array
{
    return [
        'state' => 'partial',
        'applied' => [],
        'changes' => [],
        'changelog' => [
            'state' => 'pending',
            'releases' => [],
            ...$changelog,
        ],
        ...$differently,
    ];
}

/**
 * One release, complete, with whatever this case is about changed.
 *
 * @param  array<mixed>  $differently
 * @return array<mixed>
 */
function aReleaseSaying(array $differently = []): array
{
    return ['version' => '4.1.0', 'user_facing' => true, ...$differently];
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
        named: static fn(VersionInUse $inUse): WhatTheStackTurnedOutToBeOn
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

it('N2-R15 — reads the triple off the changelog rather than off the payload', function (): void {
    // The payload carries two fields called `state`. The top-level one says how
    // the last applied update finished — `partial` here — and the triple this
    // requirement is about is under `changelog`. Both are words, so nothing but
    // the contract tells them apart, and a reader on the wrong one refuses
    // every stack that has an update waiting.
    expect(theUpkeepIn(whatAStackSaysAboutItsUpkeep())->how())->toBe(HowCurrent::Pending);
});

it('N2-R15 — reads the release in use off the changelog', function (): void {
    $upkeep = theUpkeepIn(whatAStackSaysAboutItsUpkeep([
        'running' => aReleaseSaying(['version' => '4.0.15']),
    ]));

    expect(whatItIsOn($upkeep))->toBe('4.0.15');
});

it('N2-R15 — a stack that named no release in use is not a stack running nothing', function (): void {
    // Absent rather than empty. Not having looked and running nothing are
    // different answers, and a screen handed a blank version would show the
    // second when the stack said the first.
    expect(whatItIsOn(theUpkeepIn(whatAStackSaysAboutItsUpkeep())))->toBe('unstated');
});

it('N2-R16 — reads a release as withdrawn from the field being there at all', function (): void {
    // The wire says *when* it was taken back. A date this side cannot parse is
    // still a stack saying the release was withdrawn, and treating it as
    // standing would be the unsafe reading of an unreadable field.
    $upkeep = theUpkeepIn(whatAStackSaysAboutItsUpkeep([
        'releases' => [aReleaseSaying(['withdrawn' => 'not a date anybody can read'])],
    ]));

    expect($upkeep->waiting()->isEmpty())->toBeTrue();
});

it('N2-R16 — a release the stack has not taken back still stands', function (): void {
    $upkeep = theUpkeepIn(whatAStackSaysAboutItsUpkeep([
        'releases' => [aReleaseSaying(['withdrawn' => null])],
    ]));

    expect($upkeep->waiting()->count())->toBe(1);
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
        ['service' => 'jellyfin', 'refused' => false],
        ['service' => 'sonarr', 'refused' => true],
    ]]));

    $named = [];

    foreach ($upkeep->changing() as $service) {
        $named[] = $service->named();
    }

    expect($named)->toBe(['jellyfin']);
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

it('refuses a release whose version is nothing but space', function (): void {
    // Blank as well as absent. A blank one would otherwise be refused by the
    // value's own constructor, which throws a different kind — one the adapter
    // does not catch, so it would reach the operator as a crash rather than as
    // an obstacle.
    expect(fn(): object => theUpkeepIn(whatAStackSaysAboutItsUpkeep([
        'releases' => [aReleaseSaying(['version' => '   '])],
    ])))->toThrow(UpkeepIsUnreadable::class, 'Release 1');
});

it('refuses a reading with no changelog', function (): void {
    expect(fn(): object => theUpkeepIn(['state' => 'partial', 'applied' => [], 'changes' => []]))
        ->toThrow(UpkeepIsUnreadable::class, 'changelog');
});

it('refuses a changelog that is not a shape', function (): void {
    expect(fn(): object => theUpkeepIn([
        'state' => 'partial',
        'applied' => [],
        'changes' => [],
        'changelog' => 'nothing to report',
    ]))->toThrow(UpkeepIsUnreadable::class, 'changelog');
});

it('refuses a changelog that never said where the stack stands', function (): void {
    // Built without the field rather than built and then unset, so the shape
    // this case is about is the one the reader is handed.
    expect(fn(): object => theUpkeepIn([
        'state' => 'partial',
        'applied' => [],
        'changes' => [],
        'changelog' => ['releases' => []],
    ]))->toThrow(UpkeepIsUnreadable::class, 'state');
});

it('refuses a standing that is not a word', function (): void {
    expect(fn(): object => theUpkeepIn(whatAStackSaysAboutItsUpkeep(['state' => 3])))
        ->toThrow(UpkeepIsUnreadable::class, 'state');
});

it('refuses a standing this app has no case for', function (): void {
    // A word this side does not know is not a state it can show, and rendering
    // the word itself would put lemonfiber's vocabulary on a screen in place of
    // a sentence somebody wrote.
    expect(fn(): object => theUpkeepIn(whatAStackSaysAboutItsUpkeep(['state' => 'nearly'])))
        ->toThrow(UpkeepIsUnreadable::class, 'nearly');
});

it('refuses a changelog with no releases', function (): void {
    expect(fn(): object => theUpkeepIn([
        'state' => 'partial',
        'applied' => [],
        'changes' => [],
        'changelog' => ['state' => 'pending'],
    ]))->toThrow(UpkeepIsUnreadable::class, 'releases');
});

it('refuses releases that are not a list', function (): void {
    expect(fn(): object => theUpkeepIn(whatAStackSaysAboutItsUpkeep(['releases' => 'two'])))
        ->toThrow(UpkeepIsUnreadable::class, 'releases');
});

it('names a release by where it sat rather than by a name it has not got', function (): void {
    expect(fn(): object => theUpkeepIn(whatAStackSaysAboutItsUpkeep([
        'releases' => [aReleaseSaying(), 'a string where a release belongs'],
    ])))->toThrow(UpkeepIsUnreadable::class, 'Release 2');
});

it('refuses a release with no version to call it by', function (): void {
    expect(fn(): object => theUpkeepIn(whatAStackSaysAboutItsUpkeep([
        'releases' => [['user_facing' => true]],
    ])))
        ->toThrow(UpkeepIsUnreadable::class, 'Release 1');
});

it('refuses a version that is not a word', function (): void {
    expect(fn(): object => theUpkeepIn(whatAStackSaysAboutItsUpkeep([
        'releases' => [aReleaseSaying(['version' => 410])],
    ])))->toThrow(UpkeepIsUnreadable::class, 'Release 1');
});

it('refuses a release that never said whether anybody would notice', function (): void {
    // `N2-R14` at its sharpest. The reassuring default is *nobody will notice*,
    // which is the answer that quietly turns a decision into a chore — so the
    // absent case is the stack's to explain rather than this side's to fill in.
    expect(fn(): object => theUpkeepIn(whatAStackSaysAboutItsUpkeep([
        'releases' => [['version' => '4.1.0']],
    ])))
        ->toThrow(UpkeepIsUnreadable::class, 'Release 1');
});

it('refuses a would-be-noticed that is not a yes or a no', function (): void {
    expect(fn(): object => theUpkeepIn(whatAStackSaysAboutItsUpkeep([
        'releases' => [aReleaseSaying(['user_facing' => 'yes'])],
    ])))->toThrow(UpkeepIsUnreadable::class, 'Release 1');
});

it('refuses a running release it cannot read', function (): void {
    expect(fn(): object => theUpkeepIn(whatAStackSaysAboutItsUpkeep([
        'running' => aReleaseSaying(['user_facing' => 'yes']),
    ])))->toThrow(UpkeepIsUnreadable::class, 'Release 1');
});

it('reads a running field that is not a shape as no release named', function (): void {
    // Not a refusal. The contract allows null here, and a stack that has not
    // determined what it is on is the ordinary case rather than a payload gone
    // wrong.
    expect(whatItIsOn(theUpkeepIn(whatAStackSaysAboutItsUpkeep(['running' => null]))))->toBe('unstated');
});

it('refuses a reading with nothing about what an update would change', function (): void {
    expect(fn(): object => theUpkeepIn([
        'state' => 'partial',
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
        'changes' => [['service' => 'jellyfin', 'refused' => false], 'a string'],
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
        ['service' => 'sonarr', 'refused' => true],
        ['service' => 'jellyfin', 'refused' => false],
        ['service' => 'radarr', 'refused' => false],
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
        ['service' => 'sonarr', 'refused' => true],
        ['service' => 'jellyfin', 'refused' => false],
        ['refused' => false],
    ]])))->toThrow(UpkeepIsUnreadable::class, 'Change 3');
});
