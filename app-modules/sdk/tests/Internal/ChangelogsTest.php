<?php

declare(strict_types=1);

use Modules\Kernel\Api\Release;
use Modules\Kernel\Api\Releases;
use Modules\Sdk\Api\ChangelogIsUnreadable;
use Modules\Sdk\Internal\Changelogs;

/**
 * One release the record lists, with this case's field changed.
 *
 * @param  array<mixed> $differently
 * @return array<mixed>
 */
function oneReleaseTheRecordHolds(array $differently = []): array
{
    return ['version' => '4.1.0', 'user_facing' => true, ...$differently];
}

/**
 * A `changelog` block, with whatever this case is about changed.
 *
 * @param  array<mixed> $differently
 * @return array<mixed>
 */
function aChangelogSaying(array $differently = []): array
{
    return ['state' => 'current', 'releases' => [], 'requirements' => [], ...$differently];
}

/**
 * The versions a history holds, each marked standing or withdrawn.
 *
 * @return list<string>
 */
function whatTheHistorySays(Releases $history): array
{
    $said = [];

    foreach ($history as $release) {
        $said[] = sprintf('%s:%s', $release->version(), $release->wasWithdrawn() ? 'withdrawn' : 'standing');
    }

    return $said;
}

it('finds the block under `changelog`', function (): void {
    expect(Changelogs::in(['changelog' => aChangelogSaying()]))->toBe(aChangelogSaying());
});

it('refuses a payload with no changelog', function (): void {
    expect(fn(): array => Changelogs::in(['state' => 'current']))
        ->toThrow(ChangelogIsUnreadable::class, 'changelog');
});

it('refuses a changelog that is not a shape', function (): void {
    expect(fn(): array => Changelogs::in(['changelog' => 'nothing to report']))
        ->toThrow(ChangelogIsUnreadable::class, 'changelog');
});

it('reads every release the record holds, in its order, withdrawn ones included', function (): void {
    // History rather than offers: a withdrawn release is marked, not dropped,
    // and the order is the record's, newest first.
    $history = Changelogs::history(aChangelogSaying(['releases' => [
        oneReleaseTheRecordHolds(['version' => '4.1.0']),
        oneReleaseTheRecordHolds(['version' => '4.0.17', 'withdrawn' => '2026-09-01']),
        oneReleaseTheRecordHolds(['version' => '4.0.16', 'withdrawn' => null]),
    ]]));

    expect(whatTheHistorySays($history))->toBe(['4.1.0:standing', '4.0.17:withdrawn', '4.0.16:standing']);
});

it('reads a release as withdrawn from the field being there at all', function (): void {
    // The wire says *when* it was taken back. A date this side cannot parse is
    // still a stack saying the release was withdrawn.
    $history = Changelogs::history(aChangelogSaying(['releases' => [
        oneReleaseTheRecordHolds(['withdrawn' => 'not a date anybody can read']),
    ]]));

    expect(whatTheHistorySays($history))->toBe(['4.1.0:withdrawn']);
});

it('reads whether the household would notice, as the record said it', function (): void {
    $noticed = [];

    foreach (Changelogs::history(aChangelogSaying(['releases' => [
        oneReleaseTheRecordHolds(['user_facing' => true]),
        oneReleaseTheRecordHolds(['version' => '4.0.16', 'user_facing' => false]),
    ]])) as $release) {
        $noticed[] = $release->theHouseholdWouldNotice();
    }

    expect($noticed)->toBe([true, false]);
});

it('refuses a changelog with no releases', function (): void {
    expect(fn(): Releases => Changelogs::history(['state' => 'current']))
        ->toThrow(ChangelogIsUnreadable::class, 'releases');
});

it('refuses releases that are not a list', function (): void {
    expect(fn(): Releases => Changelogs::history(aChangelogSaying(['releases' => 'two'])))
        ->toThrow(ChangelogIsUnreadable::class, 'releases');
});

it('names a release by where it sat rather than by a name it has not got', function (): void {
    expect(fn(): Releases => Changelogs::history(aChangelogSaying([
        'releases' => [oneReleaseTheRecordHolds(), 'a string where a release belongs'],
    ])))->toThrow(ChangelogIsUnreadable::class, 'Release 2');
});

it('refuses a release with no version to call it by', function (): void {
    expect(fn(): Releases => Changelogs::history(aChangelogSaying(['releases' => [['user_facing' => true]]])))
        ->toThrow(ChangelogIsUnreadable::class, 'Release 1');
});

it('refuses a version that is not a word', function (): void {
    expect(fn(): Releases => Changelogs::history(aChangelogSaying([
        'releases' => [oneReleaseTheRecordHolds(['version' => 410])],
    ])))->toThrow(ChangelogIsUnreadable::class, 'Release 1');
});

it('refuses a version that is nothing but space', function (): void {
    // Blank as well as absent. `Release::called()` would refuse it with its own
    // kind, which no adapter catches, so it would reach the operator as a crash.
    expect(fn(): Releases => Changelogs::history(aChangelogSaying([
        'releases' => [oneReleaseTheRecordHolds(['version' => '   '])],
    ])))->toThrow(ChangelogIsUnreadable::class, 'Release 1');
});

it('refuses a release that never said whether anybody would notice', function (): void {
    // The reassuring default is *nobody will notice*, so the absent case is the
    // stack's to explain rather than this side's to fill in.
    expect(fn(): Releases => Changelogs::history(aChangelogSaying(['releases' => [['version' => '4.1.0']]])))
        ->toThrow(ChangelogIsUnreadable::class, 'Release 1');
});

it('refuses a would-be-noticed that is not a yes or a no', function (): void {
    expect(fn(): Releases => Changelogs::history(aChangelogSaying([
        'releases' => [oneReleaseTheRecordHolds(['user_facing' => 'yes'])],
    ])))->toThrow(ChangelogIsUnreadable::class, 'Release 1');
});

it('reads what a release delivers in the stack\'s words, and silence as silence', function (): void {
    $said = [];

    foreach (Changelogs::history(aChangelogSaying(['releases' => [
        oneReleaseTheRecordHolds(['delivers' => 'Adds series search.']),
        oneReleaseTheRecordHolds(),
        // A number where prose belongs is a stack that has given no prose, and
        // refusing it would lose every other release in the list.
        oneReleaseTheRecordHolds(['delivers' => 7]),
    ]])) as $release) {
        $said[] = $release->delivers()->either(
            said: static fn(string $prose): WhatAChangelogRowSaid => new WhatAChangelogRowSaid($prose),
            saidNothing: static fn(): WhatAChangelogRowSaid => new WhatAChangelogRowSaid('silent'),
        )->said;
    }

    expect($said)->toBe(['Adds series search.', 'silent', 'silent']);
});

it('reads the release that is running', function (): void {
    $running = Changelogs::running(aChangelogSaying([
        'running' => oneReleaseTheRecordHolds(['version' => '4.0.15', 'withdrawn' => '2026-09-01']),
    ]));

    expect($running)->toBeInstanceOf(Release::class)
        ->and($running?->version())->toBe('4.0.15')
        ->and($running?->wasWithdrawn())->toBeTrue();
});

it('reads a running release that is absent or null as none named', function (): void {
    // Not a refusal. The contract allows both, and a stack that has not
    // determined what it is on is the ordinary case.
    expect(Changelogs::running(aChangelogSaying()))->toBeNull()
        ->and(Changelogs::running(aChangelogSaying(['running' => null])))->toBeNull();
});

it('refuses a running release that is not a shape', function (): void {
    expect(fn(): ?Release => Changelogs::running(aChangelogSaying(['running' => 'v4.0.15'])))
        ->toThrow(ChangelogIsUnreadable::class, 'running release');
});

it('refuses a running release it cannot read', function (): void {
    expect(fn(): ?Release => Changelogs::running(aChangelogSaying([
        'running' => oneReleaseTheRecordHolds(['user_facing' => 'yes']),
    ])))->toThrow(ChangelogIsUnreadable::class, 'running release');
});

/** One answer carried out of an `either()` arm, which hands back objects. */
final readonly class WhatAChangelogRowSaid
{
    public function __construct(public string $said) {}
}
