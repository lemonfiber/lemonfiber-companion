<?php

declare(strict_types=1);

namespace Modules\Updates\Tests\Api;

use function expect;
use function implode;
use function it;

use Modules\Kernel\Api\AgainstThePins;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\HowServicesTookIt;
use Modules\Kernel\Api\HowTheNotesStand;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Noted;
use Modules\Kernel\Api\Release;
use Modules\Kernel\Api\Releases;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\SealedStack;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\Shape;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\TheStackEdits;
use Modules\Kernel\Api\Unsealed;
use Modules\Kernel\Api\Upkeep;
use Modules\Kernel\Api\WhatAReleaseDelivers;
use Modules\Updates\Api\KeepingTheLastUpkeep;
use Modules\Updates\Api\WhatWasKeptOfTheUpkeep;

use function sprintf;
use function str_repeat;

use Tests\Support\Fakes\ASealInMemory;
use Tests\Support\Fakes\ReadingsInMemory;
use Tests\Support\WhatIsKeptOfUpdates;

/** The moment a kept reading in these tests was read at. */
const WHEN_THE_UPKEEP_WAS_READ = 1_790_000_000;

/** The stack a reading is kept for. */
function theStackTheUpkeepIsKeptFor(): StackId
{
    return StackId::of(Nonce::of(str_repeat('c', Nonce::SHORTEST)));
}

/** A second stack, whose reading must never answer for the first's. */
function theOtherStackTheUpkeepIsKeptFor(): StackId
{
    return StackId::of(Nonce::of(str_repeat('d', Nonce::SHORTEST)));
}

/** A moment, counted in seconds from when the reading was read. */
function secondsAfterTheUpkeepWasRead(int $seconds): Instant
{
    return Instant::atEpochSeconds(WHEN_THE_UPKEEP_WAS_READ + $seconds);
}

/** One release as one phrase. */
function everythingTheReleaseSays(Release $release): string
{
    return sprintf(
        '%s(%s%s,%s)',
        $release->version(),
        $release->theHouseholdWouldNotice() ? 'noticed' : 'unnoticed',
        $release->wasWithdrawn() ? ',withdrawn' : '',
        $release->delivers()->either(
            said: static fn(string $prose): Code => Code::of($prose),
            saidNothing: static fn(): Code => Code::of('nothing said'),
        )->shown(),
    );
}

/** Everything a reading says, as one line, so two readings can be compared whole. */
function everythingTheUpkeepSays(Upkeep $upkeep): string
{
    $said = [$upkeep->againstThePins()->value, $upkeep->notes()->value];

    foreach ($upkeep->history() as $release) {
        $said[] = everythingTheReleaseSays($release);
    }

    foreach ([$upkeep->changing(), $upkeep->cannotBePutBack()] as $services) {
        $names = [];

        foreach ($services as $service) {
            $names[] = $service->named();
        }

        $said[] = sprintf('[%s]', implode(',', $names));
    }

    foreach ($upkeep->editsKept() as $edit) {
        $said[] = sprintf('edit(%s:%s)', $edit->path(), $edit->diff());
    }

    $said[] = $upkeep->inUse(
        named: static fn(Release $inUse): Code => Code::of(sprintf('on %s', everythingTheReleaseSays($inUse))),
        unstated: static fn(): Code => Code::of('on nothing named'),
    )->shown();
    $said[] = sprintf('%d took it', $upkeep->howItWent()->count());

    return implode(' | ', $said);
}

/** What the screen opens on, as one line: nothing, or the reading as of when. */
function whatTheUpdatesScreenOpensOn(WhatWasKeptOfTheUpkeep $kept): string
{
    return $kept->either(
        kept: static fn(Upkeep $upkeep, Instant $readAt): Code
            => Code::of(sprintf('as of %d: %s', $readAt->epochSeconds() - WHEN_THE_UPKEEP_WAS_READ, everythingTheUpkeepSays($upkeep))),
        nothing: static fn(): Code => Code::of('nothing'),
    )->shown();
}

/** Whether a keep was noted as written, as one word. */
function whetherTheUpkeepWasKept(Noted $noted): string
{
    return $noted->either(
        down: static fn(): Code => Code::of('kept'),
        notKept: static fn(): Code => Code::of('not kept'),
    )->shown();
}

it('keeps a reading sealed, and hands it back whole on opening as of when it was read', function (): void {
    $kept = WhatIsKeptOfUpdates::onAPhoneThatSeals();

    expect(whetherTheUpkeepWasKept($kept->keeping->keep(theStackTheUpkeepIsKeptFor(), WhatIsKeptOfUpdates::aReadingWithEveryPart(), secondsAfterTheUpkeepWasRead(0))))->toBe('kept')
        ->and(whatTheUpdatesScreenOpensOn($kept->keeping->lastKept(theStackTheUpkeepIsKeptFor())))->toBe(
            'as of 0: updates-available | stale | 4.1.0(noticed,Adds series search.) | 4.0.16(unnoticed,withdrawn,nothing said)'
            . ' | [jellyfin,sonarr] | [sonarr] | edit(compose.yaml:- PUID=1001' . "\n" . '+ PUID=1000) | on 4.1.0(noticed,Adds series search.) | 0 took it',
        );
});

it('keeps a stack that names no release as one that names none', function (): void {
    $kept = WhatIsKeptOfUpdates::onAPhoneThatSeals();
    $kept->keeping->keep(theStackTheUpkeepIsKeptFor(), WhatIsKeptOfUpdates::aReadingOfAStackThatIsCurrent(), secondsAfterTheUpkeepWasRead(0));

    expect(whatTheUpdatesScreenOpensOn($kept->keeping->lastKept(theStackTheUpkeepIsKeptFor())))
        ->toBe('as of 0: current | current | [] | [] | on nothing named | 0 took it');
});

it('keeps nothing the store could read, and names the stack only by its keyed hash', function (): void {
    $kept = WhatIsKeptOfUpdates::onAPhoneThatSeals();
    $kept->keeping->keep(theStackTheUpkeepIsKeptFor(), WhatIsKeptOfUpdates::aReadingWithEveryPart(), secondsAfterTheUpkeepWasRead(0));

    $payload = $kept->store->newest($kept->seal->stack(theStackTheUpkeepIsKeptFor()))->either(
        found: static fn(SealedPayload $payload): Code => Code::of($payload->forTheStore()),
        none: static fn(): Code => Code::of('none'),
        unreadable: static fn(): Code => Code::of('unreadable'),
    )->shown();

    expect($payload)->toStartWith('sealed-by-')
        ->and($payload)->not->toContain('jellyfin')
        ->and($kept->store->newest(SealedStack::of(theStackTheUpkeepIsKeptFor()->stored()))->holdsARow())->toBeFalse();
});

it('keeps the newest reading of each stack, and each stack\'s apart', function (): void {
    $kept = WhatIsKeptOfUpdates::onAPhoneThatSeals();
    $kept->keeping->keep(theStackTheUpkeepIsKeptFor(), WhatIsKeptOfUpdates::aReadingWithEveryPart(), secondsAfterTheUpkeepWasRead(0));
    $kept->keeping->keep(theStackTheUpkeepIsKeptFor(), WhatIsKeptOfUpdates::aReadingOfAStackThatIsCurrent(), secondsAfterTheUpkeepWasRead(30));
    $kept->keeping->keep(theOtherStackTheUpkeepIsKeptFor(), WhatIsKeptOfUpdates::aReadingOfAStackThatIsCurrent(), secondsAfterTheUpkeepWasRead(60));

    expect(whatTheUpdatesScreenOpensOn($kept->keeping->lastKept(theStackTheUpkeepIsKeptFor())))->toStartWith('as of 30: current')
        ->and(whatTheUpdatesScreenOpensOn($kept->keeping->lastKept(theOtherStackTheUpkeepIsKeptFor())))->toStartWith('as of 60: current');
});

it('opens on nothing where nothing was kept', function (): void {
    expect(whatTheUpdatesScreenOpensOn(WhatIsKeptOfUpdates::onAPhoneThatSeals()->keeping->lastKept(theStackTheUpkeepIsKeptFor())))->toBe('nothing');
});

it('keeps nothing where nothing can be sealed, or where the store will not keep it', function (): void {
    foreach ([
        'no secure storage' => new WhatIsKeptOfUpdates(ASealInMemory::withNoSecureStorage(), ReadingsInMemory::empty()),
        'a store that will not answer' => new WhatIsKeptOfUpdates(ASealInMemory::working(), ReadingsInMemory::unreachable()),
    ] as $which => $kept) {
        expect(whetherTheUpkeepWasKept($kept->keeping->keep(theStackTheUpkeepIsKeptFor(), WhatIsKeptOfUpdates::aReadingWithEveryPart(), secondsAfterTheUpkeepWasRead(0))))->toBe('not kept', $which)
            ->and($kept->store->forgetEverything()->howMany())->toBe(0, $which);
    }
});

it('keeps nothing where a stack\'s words are not text it can write', function (): void {
    $kept = WhatIsKeptOfUpdates::onAPhoneThatSeals();
    $garbled = Upkeep::reported(
        AgainstThePins::Current,
        Releases::these(Release::called("\xB1\x31", noticeable: false, withdrawn: false, delivers: WhatAReleaseDelivers::saidNothing())),
        Services::none(),
        Services::none(),
        HowServicesTookIt::none(),
        HowTheNotesStand::Current,
        TheStackEdits::none(),
    );

    expect(whetherTheUpkeepWasKept($kept->keeping->keep(theStackTheUpkeepIsKeptFor(), $garbled, secondsAfterTheUpkeepWasRead(0))))->toBe('not kept')
        ->and($kept->store->forgetEverything()->howMany())->toBe(0);
});

it('lets go of a kept reading a later build wrote, and opens on nothing', function (): void {
    $kept = WhatIsKeptOfUpdates::onAPhoneThatSeals();
    $kept->store->holdsOneALaterBuildWrote($kept->seal->stack(theStackTheUpkeepIsKeptFor()));

    expect(whatTheUpdatesScreenOpensOn($kept->keeping->lastKept(theStackTheUpkeepIsKeptFor())))->toBe('nothing')
        ->and($kept->store->forgetEverything()->howMany())->toBe(0);
});

it('lets go of a kept reading that does not open, and opens on nothing', function (): void {
    // Sealed by another phone's seal, which is what a payload sealed under a
    // key this one does not hold looks like from here.
    $kept = WhatIsKeptOfUpdates::onAPhoneThatSeals();
    $elsewhere = ASealInMemory::working();
    $kept->seal->standing();

    $elsewhere->seal(Unsealed::of('{}'))->either(
        sealed: static fn(SealedPayload $payload): Noted => $kept->store->keep($kept->seal->stack(theStackTheUpkeepIsKeptFor()), $payload, Shape::One, secondsAfterTheUpkeepWasRead(0)),
        refused: static fn(): Noted => Noted::notKept(),
    );

    expect(whatTheUpdatesScreenOpensOn($kept->keeping->lastKept(theStackTheUpkeepIsKeptFor())))->toBe('nothing')
        ->and($kept->store->forgetEverything()->howMany())->toBe(0);
});

it('lets go of a kept reading that opens to something that is not a reading', function (string $written): void {
    $kept = WhatIsKeptOfUpdates::onAPhoneThatSeals()->holdsSealed($written, theStackTheUpkeepIsKeptFor(), secondsAfterTheUpkeepWasRead(0));

    expect(whatTheUpdatesScreenOpensOn($kept->keeping->lastKept(theStackTheUpkeepIsKeptFor())))->toBe('nothing')
        ->and($kept->store->forgetEverything()->howMany())->toBe(0);
})->with([
    'not written as fields at all' => ['not a reading'],
    'a word rather than fields' => ['"current"'],
    'a field left out' => ['{"pins":"current","history":[],"changing":[],"permanent":[],"notes":"current","edits":[]}'],
    'pins this build does not know' => ['{"pins":"ahead","history":[],"changing":[],"permanent":[],"notes":"current","edits":[],"running":[]}'],
    'notes this build does not know' => ['{"pins":"current","history":[],"changing":[],"permanent":[],"notes":"lost","edits":[],"running":[]}'],
    'pins that are not text' => ['{"pins":1,"history":[],"changing":[],"permanent":[],"notes":"current","edits":[],"running":[]}'],
    'a history that is not a list' => ['{"pins":"current","history":"none","changing":[],"permanent":[],"notes":"current","edits":[],"running":[]}'],
    'a release that is not fields' => ['{"pins":"current","history":["4.1.0"],"changing":[],"permanent":[],"notes":"current","edits":[],"running":[]}'],
    'a release with a blank version' => ['{"pins":"current","history":[{"version":" ","noticeable":true,"withdrawn":false,"delivers":[]}],"changing":[],"permanent":[],"notes":"current","edits":[],"running":[]}'],
    'a release noticed in a word' => ['{"pins":"current","history":[{"version":"4.1.0","noticeable":"yes","withdrawn":false,"delivers":[]}],"changing":[],"permanent":[],"notes":"current","edits":[],"running":[]}'],
    'what a release delivers, not in words' => ['{"pins":"current","history":[{"version":"4.1.0","noticeable":true,"withdrawn":false,"delivers":[7]}],"changing":[],"permanent":[],"notes":"current","edits":[],"running":[]}'],
    'a service that is not named' => ['{"pins":"current","history":[],"changing":[3],"permanent":[],"notes":"current","edits":[],"running":[]}'],
    'a service named blank' => ['{"pins":"current","history":[],"changing":[" "],"permanent":[],"notes":"current","edits":[],"running":[]}'],
    'an edit whose line carries no mark' => ['{"pins":"current","history":[],"changing":[],"permanent":[],"notes":"current","edits":[{"path":"compose.yaml","diff":"PUID=1000"}],"running":[]}'],
    'a release running that is not fields' => ['{"pins":"current","history":[],"changing":[],"permanent":[],"notes":"current","edits":[],"running":["4.1.0"]}'],
]);

it('lets go of the reading kept for one stack, and of no other\'s', function (): void {
    $kept = WhatIsKeptOfUpdates::onAPhoneThatSeals();
    $kept->keeping->keep(theStackTheUpkeepIsKeptFor(), WhatIsKeptOfUpdates::aReadingOfAStackThatIsCurrent(), secondsAfterTheUpkeepWasRead(0));
    $kept->keeping->keep(theOtherStackTheUpkeepIsKeptFor(), WhatIsKeptOfUpdates::aReadingOfAStackThatIsCurrent(), secondsAfterTheUpkeepWasRead(0));

    expect($kept->keeping->keepsAnythingOf(theStackTheUpkeepIsKeptFor()))->toBeTrue()
        ->and($kept->keeping->forgetTheStack(theStackTheUpkeepIsKeptFor())->howMany())->toBe(1)
        ->and($kept->keeping->keepsAnythingOf(theStackTheUpkeepIsKeptFor()))->toBeFalse()
        ->and($kept->keeping->keepsAnythingOf(theOtherStackTheUpkeepIsKeptFor()))->toBeTrue();
});

it('finds nothing, lets go of nothing, and says it may keep something where the seal\'s keys cannot be read', function (): void {
    $store = ReadingsInMemory::empty();
    new KeepingTheLastUpkeep(ASealInMemory::working(), $store)->keep(theStackTheUpkeepIsKeptFor(), WhatIsKeptOfUpdates::aReadingWithEveryPart(), secondsAfterTheUpkeepWasRead(0));

    $locked = new KeepingTheLastUpkeep(ASealInMemory::thatWillNotOpen(), $store);

    expect(whatTheUpdatesScreenOpensOn($locked->lastKept(theStackTheUpkeepIsKeptFor())))->toBe('nothing')
        ->and($locked->keepsAnythingOf(theStackTheUpkeepIsKeptFor()))->toBeTrue()
        ->and($store->forgetEverything()->howMany())->toBe(1);
});

it('says it keeps a reading of a stack it cannot read until that reading is let go of', function (): void {
    $kept = WhatIsKeptOfUpdates::onAPhoneThatSeals();
    $kept->store->holdsOneALaterBuildWrote($kept->seal->stack(theStackTheUpkeepIsKeptFor()));

    expect($kept->keeping->keepsAnythingOf(theStackTheUpkeepIsKeptFor()))->toBeTrue();
});
