<?php

declare(strict_types=1);

namespace Modules\Services\Tests\Api;

use function expect;
use function implode;
use function it;

use Modules\Kernel\Api\Awaiting;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Daemon;
use Modules\Kernel\Api\Daemons;
use Modules\Kernel\Api\Disturbances;
use Modules\Kernel\Api\HowAServiceRuns;
use Modules\Kernel\Api\HowMuchItMatters;
use Modules\Kernel\Api\HowTheStackIsRunning;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Noted;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\SealedStack;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Shape;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Unsealed;
use Modules\Kernel\Api\WhatItTakesAway;
use Modules\Kernel\Api\WhatLeansOnIt;
use Modules\Kernel\Api\WhatToDoWithIt;
use Modules\Services\Api\KeepingWhatItRuns;
use Modules\Services\Api\WhatWasKeptOfWhatItRuns;

use function sprintf;
use function str_repeat;

use Tests\Support\Fakes\ASealInMemory;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\ReadingsInMemory;
use Tests\Support\WhatIsKeptOfServices;

/** The moment a kept listing in these tests was read at. */
const WHEN_THE_LISTING_WAS_READ = 1_790_000_000;

/** The stack a listing is kept for. */
function theStackTheListingIsKeptFor(): StackId
{
    return StackId::of(Nonce::of(str_repeat('c', Nonce::SHORTEST)));
}

/** A second stack, whose listing must never answer for the first's. */
function theOtherStackTheListingIsKeptFor(): StackId
{
    return StackId::of(Nonce::of(str_repeat('d', Nonce::SHORTEST)));
}

/** A phone that seals, its clock this many seconds after the listing was read. */
function aPhoneKeepingListings(int $seconds = 0): WhatIsKeptOfServices
{
    return WhatIsKeptOfServices::onAPhoneThatSealsAt(Instant::atEpochSeconds(WHEN_THE_LISTING_WAS_READ + $seconds));
}

/** What a verb takes away, as one word. */
function whatItTakesAwaySaid(Disturbances $disturbs, WhatToDoWithIt $doing): string
{
    return $disturbs->forThe(
        $doing,
        said: static fn(WhatItTakesAway $takes): Code => $takes->either(
            bounded: static fn(int $seconds): Code => Code::of(sprintf('%ds', $seconds)),
            openEnded: static fn(Awaiting $awaiting): Code => Code::of(sprintf('until %s', $awaiting->value)),
        ),
        unreported: static fn(): Code => Code::of('unreported'),
    )->shown();
}

/** One service as one phrase. */
function everythingTheServiceSays(Daemon $daemon): string
{
    $leaning = [];

    foreach ($daemon->whatLeansOnIt() as $id) {
        $leaning[] = $id->named();
    }

    $forms = [];

    foreach ($daemon->whatBroughtItIn() as $form) {
        $forms[] = $form->named();
    }

    return sprintf(
        '%s(%s,%s,%s,[%s],%s,[%s])',
        $daemon->name(),
        $daemon->id()->named(),
        $daemon->runs()->value,
        $daemon->matters()->value,
        implode(';', $leaning),
        $daemon->exit(said: static fn(int $code): Code => Code::of(sprintf('exit %d', $code)), unstated: static fn(): Code => Code::of('no exit'))->shown(),
        implode(';', $forms),
    );
}

/** Everything a listing says, as one line, so two listings can be compared whole. */
function everythingTheListingSays(Daemons $daemons): string
{
    $said = [$daemons->running()->value];

    foreach ([WhatToDoWithIt::Start, WhatToDoWithIt::Stop, WhatToDoWithIt::Restart] as $doing) {
        $said[] = sprintf('%s %s', $doing->value, whatItTakesAwaySaid($daemons->disturbs(), $doing));
    }

    foreach ($daemons as $daemon) {
        $said[] = everythingTheServiceSays($daemon);
    }

    $active = [];

    foreach ($daemons->active() as $form) {
        $active[] = $form->named();
    }

    $said[] = sprintf('asked [%s]', implode(';', $active));

    foreach ($daemons->leftOut() as $left) {
        $asked = [];

        foreach ($left->askedBy() as $form) {
            $asked[] = $form->named();
        }

        $said[] = sprintf('left out %s(%s,%s,[%s])', $left->name(), $left->id()->named(), $left->needs()->value, implode(';', $asked));
    }

    return implode(' | ', $said);
}

/** What the screen opens on, as one line: nothing, or the listing as of when, and how long ago. */
function whatTheServicesScreenOpensOn(WhatWasKeptOfWhatItRuns $kept): string
{
    return $kept->either(
        kept: static fn(Daemons $daemons, Instant $readAt, Instant $now): Code
            => Code::of(sprintf('as of %d, %d ago: %s', $readAt->epochSeconds() - WHEN_THE_LISTING_WAS_READ, $now->epochSeconds() - $readAt->epochSeconds(), everythingTheListingSays($daemons))),
        nothing: static fn(): Code => Code::of('nothing'),
    )->shown();
}

/** Whether a keep was noted as written, as one word. */
function whetherTheListingWasKept(Noted $noted): string
{
    return $noted->either(
        down: static fn(): Code => Code::of('kept'),
        notKept: static fn(): Code => Code::of('not kept'),
    )->shown();
}

it('keeps a listing sealed, read now, and hands it back whole with when it was read', function (): void {
    $kept = aPhoneKeepingListings();

    expect(whetherTheListingWasKept($kept->keeping->keep(theStackTheListingIsKeptFor(), WhatIsKeptOfServices::aListingWithEveryPart())))->toBe('kept');

    $kept->clock->moveTo(Instant::atEpochSeconds(WHEN_THE_LISTING_WAS_READ + 7_200));

    expect(whatTheServicesScreenOpensOn($kept->keeping->lastKept(theStackTheListingIsKeptFor())))->toBe(
        'as of 0, 7200 ago: degraded | start 30s | stop until downloads | restart 45s'
        . ' | Jellyfin(jellyfin,running,core,[sonarr],no exit,[watching])'
        . ' | Sonarr(sonarr,failed,important,[],exit 137,[])'
        . ' | SABnzbd(sabnzbd,absent,optional,[],no exit,[])'
        . ' | asked [watching] | left out SABnzbd(sabnzbd,usenet,[watching])',
    );
});

it('keeps a stack running nothing as one running nothing', function (): void {
    $kept = aPhoneKeepingListings();
    $kept->keeping->keep(theStackTheListingIsKeptFor(), WhatIsKeptOfServices::aListingOfNothing());

    expect(whatTheServicesScreenOpensOn($kept->keeping->lastKept(theStackTheListingIsKeptFor())))
        ->toBe('as of 0, 0 ago: inactive | start 1s | stop 1s | restart 1s | asked []');
});

it('keeps nothing the store could read, and names the stack only by its keyed hash', function (): void {
    $kept = aPhoneKeepingListings();
    $kept->keeping->keep(theStackTheListingIsKeptFor(), WhatIsKeptOfServices::aListingWithEveryPart());

    $payload = $kept->store->newest($kept->seal->stack(theStackTheListingIsKeptFor()))->either(
        found: static fn(SealedPayload $payload): Code => Code::of($payload->forTheStore()),
        none: static fn(): Code => Code::of('none'),
        unreadable: static fn(): Code => Code::of('unreadable'),
    )->shown();

    expect($payload)->toStartWith('sealed-by-')
        ->and($payload)->not->toContain('jellyfin')
        ->and($kept->store->newest(SealedStack::of(theStackTheListingIsKeptFor()->stored()))->holdsARow())->toBeFalse();
});

it('keeps the newest listing of each stack, and each stack\'s apart', function (): void {
    $kept = aPhoneKeepingListings();
    $kept->keeping->keep(theStackTheListingIsKeptFor(), WhatIsKeptOfServices::aListingWithEveryPart());
    $kept->clock->moveTo(Instant::atEpochSeconds(WHEN_THE_LISTING_WAS_READ + 30));
    $kept->keeping->keep(theStackTheListingIsKeptFor(), WhatIsKeptOfServices::aListingOfNothing());
    $kept->clock->moveTo(Instant::atEpochSeconds(WHEN_THE_LISTING_WAS_READ + 60));
    $kept->keeping->keep(theOtherStackTheListingIsKeptFor(), WhatIsKeptOfServices::aListingOfNothing());

    expect(whatTheServicesScreenOpensOn($kept->keeping->lastKept(theStackTheListingIsKeptFor())))->toStartWith('as of 30, 30 ago: inactive')
        ->and(whatTheServicesScreenOpensOn($kept->keeping->lastKept(theOtherStackTheListingIsKeptFor())))->toStartWith('as of 60, 0 ago: inactive');
});

it('opens on nothing where nothing was kept', function (): void {
    expect(whatTheServicesScreenOpensOn(aPhoneKeepingListings()->keeping->lastKept(theStackTheListingIsKeptFor())))->toBe('nothing');
});

it('keeps nothing where nothing can be sealed, or where the store will not keep it', function (): void {
    foreach ([
        'no secure storage' => new WhatIsKeptOfServices(ASealInMemory::withNoSecureStorage(), ReadingsInMemory::empty(), FrozenClock::at(Instant::atEpochSeconds(0))),
        'a store that will not answer' => new WhatIsKeptOfServices(ASealInMemory::working(), ReadingsInMemory::unreachable(), FrozenClock::at(Instant::atEpochSeconds(0))),
    ] as $which => $kept) {
        expect(whetherTheListingWasKept($kept->keeping->keep(theStackTheListingIsKeptFor(), WhatIsKeptOfServices::aListingWithEveryPart())))->toBe('not kept', $which)
            ->and($kept->store->forgetEverything()->howMany())->toBe(0, $which);
    }
});

it('keeps nothing where a stack\'s words are not text it can write', function (): void {
    $kept = aPhoneKeepingListings();
    $garbled = Daemons::of(
        HowTheStackIsRunning::Active,
        Disturbances::of(WhatItTakesAway::atMost(1), WhatItTakesAway::atMost(1), WhatItTakesAway::atMost(1)),
        Daemon::called("\xB1\x31", ServiceId::called('garbled'), HowAServiceRuns::Running, HowMuchItMatters::Core, WhatLeansOnIt::nothing()),
    );

    expect(whetherTheListingWasKept($kept->keeping->keep(theStackTheListingIsKeptFor(), $garbled)))->toBe('not kept')
        ->and($kept->store->forgetEverything()->howMany())->toBe(0);
});

it('lets go of a kept listing a later build wrote, and opens on nothing', function (): void {
    $kept = aPhoneKeepingListings();
    $kept->store->holdsOneALaterBuildWrote($kept->seal->stack(theStackTheListingIsKeptFor()));

    expect(whatTheServicesScreenOpensOn($kept->keeping->lastKept(theStackTheListingIsKeptFor())))->toBe('nothing')
        ->and($kept->store->forgetEverything()->howMany())->toBe(0);
});

it('lets go of a kept listing that does not open, and opens on nothing', function (): void {
    // Sealed by another phone's seal, which is what a payload sealed under a
    // key this one does not hold looks like from here.
    $kept = aPhoneKeepingListings();
    $elsewhere = ASealInMemory::working();
    $kept->seal->standing();

    $elsewhere->seal(Unsealed::of('{}'))->either(
        sealed: static fn(SealedPayload $payload): Noted => $kept->store->keep($kept->seal->stack(theStackTheListingIsKeptFor()), $payload, Shape::One, Instant::atEpochSeconds(WHEN_THE_LISTING_WAS_READ)),
        refused: static fn(): Noted => Noted::notKept(),
    );

    expect(whatTheServicesScreenOpensOn($kept->keeping->lastKept(theStackTheListingIsKeptFor())))->toBe('nothing')
        ->and($kept->store->forgetEverything()->howMany())->toBe(0);
});

it('lets go of a kept listing that opens to something that is not a listing', function (string $written): void {
    $kept = aPhoneKeepingListings()->holdsSealed($written, theStackTheListingIsKeptFor(), Instant::atEpochSeconds(WHEN_THE_LISTING_WAS_READ));

    expect(whatTheServicesScreenOpensOn($kept->keeping->lastKept(theStackTheListingIsKeptFor())))->toBe('nothing')
        ->and($kept->store->forgetEverything()->howMany())->toBe(0);
})->with([
    'not written as fields at all' => ['not a listing'],
    'a word rather than fields' => ['"active"'],
    'a field left out' => ['{"running":"active","disturbs":{"start":{"at_most":1},"stop":{"at_most":1},"restart":{"at_most":1}},"services":[],"active":[]}'],
    'running in a way this build does not know' => ['{"running":"asleep","disturbs":{"start":{"at_most":1},"stop":{"at_most":1},"restart":{"at_most":1}},"services":[],"active":[],"left_out":[]}'],
    'a verb with nothing said of it' => ['{"running":"active","disturbs":{"start":{"at_most":1},"stop":{"at_most":1}},"services":[],"active":[],"left_out":[]}'],
    'a length that is not a number' => ['{"running":"active","disturbs":{"start":{"at_most":"one"},"stop":{"at_most":1},"restart":{"at_most":1}},"services":[],"active":[],"left_out":[]}'],
    'a wait this build does not know' => ['{"running":"active","disturbs":{"start":{"lasts_until":"dawn"},"stop":{"at_most":1},"restart":{"at_most":1}},"services":[],"active":[],"left_out":[]}'],
    'a service that is not fields' => ['{"running":"active","disturbs":{"start":{"at_most":1},"stop":{"at_most":1},"restart":{"at_most":1}},"services":["jellyfin"],"active":[],"left_out":[]}'],
    'a service named blank' => ['{"running":"active","disturbs":{"start":{"at_most":1},"stop":{"at_most":1},"restart":{"at_most":1}},"services":[{"id":"jellyfin","name":" ","runs":"running","matters":"core","leaning":[],"exited":[],"brought_in_by":[]}],"active":[],"left_out":[]}'],
    'a service running in a way this build does not know' => ['{"running":"active","disturbs":{"start":{"at_most":1},"stop":{"at_most":1},"restart":{"at_most":1}},"services":[{"id":"jellyfin","name":"Jellyfin","runs":"flying","matters":"core","leaning":[],"exited":[],"brought_in_by":[]}],"active":[],"left_out":[]}'],
    'a service mattering in a way this build does not know' => ['{"running":"active","disturbs":{"start":{"at_most":1},"stop":{"at_most":1},"restart":{"at_most":1}},"services":[{"id":"jellyfin","name":"Jellyfin","runs":"running","matters":"vital","leaning":[],"exited":[],"brought_in_by":[]}],"active":[],"left_out":[]}'],
    'something leaning on it that is not named' => ['{"running":"active","disturbs":{"start":{"at_most":1},"stop":{"at_most":1},"restart":{"at_most":1}},"services":[{"id":"jellyfin","name":"Jellyfin","runs":"running","matters":"core","leaning":[3],"exited":[],"brought_in_by":[]}],"active":[],"left_out":[]}'],
    'an exit that is not a number' => ['{"running":"active","disturbs":{"start":{"at_most":1},"stop":{"at_most":1},"restart":{"at_most":1}},"services":[{"id":"jellyfin","name":"Jellyfin","runs":"failed","matters":"core","leaning":[],"exited":["137"],"brought_in_by":[]}],"active":[],"left_out":[]}'],
    'a form named blank' => ['{"running":"active","disturbs":{"start":{"at_most":1},"stop":{"at_most":1},"restart":{"at_most":1}},"services":[],"active":[" "],"left_out":[]}'],
    'a form that is not named' => ['{"running":"active","disturbs":{"start":{"at_most":1},"stop":{"at_most":1},"restart":{"at_most":1}},"services":[],"active":[1],"left_out":[]}'],
    'a service left out for a need this build does not know' => ['{"running":"active","disturbs":{"start":{"at_most":1},"stop":{"at_most":1},"restart":{"at_most":1}},"services":[],"active":[],"left_out":[{"id":"sabnzbd","name":"SABnzbd","needs":"carrier pigeon","asked_by":[]}]}'],
]);

it('lets go of the listing kept for one stack, and of no other\'s', function (): void {
    $kept = aPhoneKeepingListings();
    $kept->keeping->keep(theStackTheListingIsKeptFor(), WhatIsKeptOfServices::aListingOfNothing());
    $kept->keeping->keep(theOtherStackTheListingIsKeptFor(), WhatIsKeptOfServices::aListingOfNothing());

    expect($kept->keeping->keepsAnythingOf(theStackTheListingIsKeptFor()))->toBeTrue()
        ->and($kept->keeping->forgetTheStack(theStackTheListingIsKeptFor())->howMany())->toBe(1)
        ->and($kept->keeping->keepsAnythingOf(theStackTheListingIsKeptFor()))->toBeFalse()
        ->and($kept->keeping->keepsAnythingOf(theOtherStackTheListingIsKeptFor()))->toBeTrue();
});

it('finds nothing, lets go of nothing, and says it may keep something where the seal\'s keys cannot be read', function (): void {
    $store = ReadingsInMemory::empty();
    new KeepingWhatItRuns(ASealInMemory::working(), $store, FrozenClock::at(Instant::atEpochSeconds(0)))->keep(theStackTheListingIsKeptFor(), WhatIsKeptOfServices::aListingWithEveryPart());

    $locked = new KeepingWhatItRuns(ASealInMemory::thatWillNotOpen(), $store, FrozenClock::at(Instant::atEpochSeconds(0)));

    expect(whatTheServicesScreenOpensOn($locked->lastKept(theStackTheListingIsKeptFor())))->toBe('nothing')
        ->and($locked->keepsAnythingOf(theStackTheListingIsKeptFor()))->toBeTrue()
        ->and($store->forgetEverything()->howMany())->toBe(1);
});

it('says it keeps a listing of a stack it cannot read until that listing is let go of', function (): void {
    $kept = aPhoneKeepingListings();
    $kept->store->holdsOneALaterBuildWrote($kept->seal->stack(theStackTheListingIsKeptFor()));

    expect($kept->keeping->keepsAnythingOf(theStackTheListingIsKeptFor()))->toBeTrue();
});
