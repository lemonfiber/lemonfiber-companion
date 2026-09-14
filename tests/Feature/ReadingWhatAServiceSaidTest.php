<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowManyLines;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Said;
use Modules\Kernel\Api\Scrollback;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\ServiceIsUnnamed;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackIsNotConfigured;
use Modules\Kernel\Api\StackIsUnidentified;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Stream;
use Modules\Operator\Internal\AStacksScreen;
use Modules\Operator\Internal\Screens\WhatThisServiceSaid;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AServiceThatSpoke;
use Tests\Support\Fakes\StacksInMemory;

// N2-R10 — logs offered as a bounded, searchable read that names the service
// and states the view is a window rather than the whole.
//
// Here rather than in the operator module's own tests because a screen renders,
// and rendering needs the application — `view()` and `__()` are not there in a
// module suite.

/** The machine whose service is read. */
function theStackWhoseServiceIsRead(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** The service the screen is opened on. */
function theServiceOnTheScreen(): ServiceId
{
    return ServiceId::called('gluetun');
}

/** Three lines, one of which is worth noticing, filling a bound of three. */
function aWindowWorthReading(): Scrollback
{
    $service = theServiceOnTheScreen();

    return Scrollback::of(
        $service,
        HowManyLines::of(3),
        Said::at('2026-09-14T04:00:00Z', 'tunnel up', $service, Stream::Stdout),
        Said::whenever('connection timed out', $service, Stream::Stderr),
        Said::at('2026-09-14T04:00:02Z', 'retrying', $service, Stream::Stdout),
    );
}

/**
 * The screen, with a stack it knows and a keychain holding whatever a test says.
 *
 * Named for this file, since the root suites share one namespace (`G10`).
 */
function theLogScreen(
    AServiceThatSpoke $saying,
    ?string $named = null,
    ?string $service = null,
    bool $signedIn = true,
    ?AKeychainInMemory $keychain = null,
): WhatThisServiceSaid {
    $stack = theStackWhoseServiceIsRead();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'));
    }

    $screen = new WhatThisServiceSaid($saying, $keychain, StacksInMemory::holding($stack));
    $screen->setParams([
        'stack' => $named ?? $stack->id()->stored(),
        'service' => $service ?? theServiceOnTheScreen()->named(),
    ]);

    return $screen;
}

/** The screen with something typed into the search box. */
function typedIntoTheSearch(WhatThisServiceSaid $screen, string $said): WhatThisServiceSaid
{
    $screen->__syncProperty('looking', $said);

    return $screen;
}

/** Every line a screen is showing, folded so an order can be compared. */
function everyLineOnTheScreen(WhatThisServiceSaid $screen): string
{
    $rows = [];

    foreach ($screen->lines() as $line) {
        $rows[] = sprintf('%s/%s', $line->streamSaid, $line->line);
    }

    return implode(' | ', $rows);
}

it('N2-R10 — shows the lines, oldest first, with the mouth each came out of', function (): void {
    $screen = theLogScreen(AServiceThatSpoke::saying(aWindowWorthReading()));

    expect(everyLineOnTheScreen($screen))->toBe(sprintf(
        '%s/tunnel up | %s/connection timed out | %s/retrying',
        Stream::Stdout->saidOnTheScreen(),
        Stream::Stderr->saidOnTheScreen(),
        Stream::Stdout->saidOnTheScreen(),
    ))->and($screen->met())->toBe('')
        ->and($screen->isSignedIn())->toBeTrue()
        // A read that worked leaves no obstacle and so nothing to do about one.
        // Asserted beside `met()` because the two are written together and only
        // one of them was read back, which is how a remedy for nothing survives.
        ->and($screen->remedy())->toBe('');
});

it('N2-R10 — a line the service timed carries the moment, and one it did not says so', function (): void {
    // `hasAMoment` is a field of its own so a template never has to read an
    // empty `at` as *no moment*. That only holds if the two disagree somewhere:
    // a fold that set the flag from nothing, or set it the same way on both
    // arms, renders identically on every line that does carry a time — and the
    // lines without one are exactly where nobody looks.
    $rows = theLogScreen(AServiceThatSpoke::saying(aWindowWorthReading()))->lines();

    expect($rows[0]->at)->toBe('2026-09-14T04:00:00Z')
        ->and($rows[0]->hasAMoment)->toBeTrue()
        // The service wrote this one without a time, so there is nothing to show
        // and the screen says which rather than showing a blank where a moment
        // goes.
        ->and($rows[1]->at)->toBe('')
        ->and($rows[1]->hasAMoment)->toBeFalse()
        ->and($rows[2]->at)->toBe('2026-09-14T04:00:02Z')
        ->and($rows[2]->hasAMoment)->toBeTrue();
});

it('N2-R10 — names the service it is about, from the route', function (): void {
    // From the route rather than held, because a screen holding the service it
    // was opened with, on a frame whose URI names another, would show one
    // service's lines under another's heading.
    expect(theLogScreen(AServiceThatSpoke::saying(aWindowWorthReading()))->service()->named())
        ->toBe('gluetun');
});

it('N2-R10 — states the bound and whether the view stops at it', function (): void {
    $screen = theLogScreen(AServiceThatSpoke::saying(aWindowWorthReading()));

    expect($screen->bound())->toBe(3)
        ->and($screen->howManyArrived())->toBe(3)
        ->and($screen->isAWindow())->toBeTrue();
});

it('N2-R10 — a read the bound did not cut says so, and claims nothing more', function (): void {
    $service = theServiceOnTheScreen();
    $short = Scrollback::of(
        $service,
        HowManyLines::of(10),
        Said::whenever('tunnel up', $service, Stream::Stdout),
    );

    expect(theLogScreen(AServiceThatSpoke::saying($short))->isAWindow())->toBeFalse();
});

it('N2-R10 — searching narrows what is shown and not what was read', function (): void {
    $screen = theLogScreen(AServiceThatSpoke::saying(aWindowWorthReading()));
    typedIntoTheSearch($screen, 'timed');

    expect(everyLineOnTheScreen($screen))
        ->toBe(sprintf('%s/connection timed out', Stream::Stderr->saidOnTheScreen()))
        ->and($screen->howMany())->toBe(1)
        // The claim about the edge survives the search, which is the whole
        // point: a search that covered twelve of two hundred lines must not
        // report that it covered everything the service ever said.
        ->and($screen->howManyArrived())->toBe(3)
        ->and($screen->isAWindow())->toBeTrue()
        ->and($screen->isSearching())->toBeTrue();
});

it('N1-R17 — narrowing does not ask the stack again', function (): void {
    // A screen that re-read per keystroke would open a connection per letter to
    // a machine on a home network — and would change what is being searched
    // underneath the person searching it.
    $saying = AServiceThatSpoke::saying(aWindowWorthReading());
    $screen = theLogScreen($saying);

    $screen->lines();
    typedIntoTheSearch($screen, 'timed');
    $screen->lines();
    typedIntoTheSearch($screen, 'retry');
    $screen->lines();

    expect($saying->askings())->toBe(1);
});

it('widening the search again shows the lines it had hidden', function (): void {
    // Every search runs against what arrived rather than against the last
    // search, so a backspace cannot leave lines hidden.
    $screen = theLogScreen(AServiceThatSpoke::saying(aWindowWorthReading()));

    typedIntoTheSearch($screen, 'timed');
    $screen->lines();
    typedIntoTheSearch($screen, '');

    expect($screen->howMany())->toBe(3)
        ->and($screen->isSearching())->toBeFalse();
});

it('an empty box is not a search', function (): void {
    $screen = theLogScreen(AServiceThatSpoke::saying(aWindowWorthReading()));
    typedIntoTheSearch($screen, '   ');

    expect($screen->isSearching())->toBeFalse()
        ->and($screen->howMany())->toBe(3)
        ->and($screen->looking())->toBe('   ');
});

it('N2-R10 — asks for as much as a phone shows, and for this service', function (): void {
    $saying = AServiceThatSpoke::saying(aWindowWorthReading());
    theLogScreen($saying)->lines();

    expect($saying->askedFor()?->named())->toBe('gluetun')
        ->and($saying->askedWith()?->figure())->toBe(HowManyLines::ON_A_PHONE)
        ->and($saying->askedAbout()?->id()->stored())->toBe(theStackWhoseServiceIsRead()->id()->stored())
        ->and($saying->wasGivenASession())->toBeTrue();
});

it('N1-R10 — a stack that could not be asked says which of the six it met', function (): void {
    $screen = theLogScreen(AServiceThatSpoke::met(Obstacle::DeviceHasNoNetwork));

    expect($screen->howMany())->toBe(0)
        // Meeting an obstacle is not losing the session: the device asked and
        // was answered. Reporting otherwise would put the sign-in screen in
        // front of an operator whose session works, and `N1-R44`'s branch comes
        // first in the template — so which of the six was met is never reached.
        ->and($screen->isSignedIn())->toBeTrue()
        ->and($screen->met())->toBe(Obstacle::DeviceHasNoNetwork->said())
        ->and($screen->remedy())->toBe(Obstacle::DeviceHasNoNetwork->remedy())
        // Nothing to be a window over, so no claim is made about an edge.
        ->and($screen->isAWindow())->toBeFalse()
        ->and($screen->bound())->toBe(0)
        // Nothing arrived, and nothing was being looked for. Both are counted
        // and rendered beside the rows, so a state with no rows that claimed
        // either would put a count over an empty screen.
        ->and($screen->howManyArrived())->toBe(0)
        ->and($screen->isSearching())->toBeFalse();
});

it('N1-R44 — a device with no session for that stack is not asked to wait for one', function (): void {
    $saying = AServiceThatSpoke::saying(aWindowWorthReading());
    $screen = theLogScreen($saying, signedIn: false);

    expect($screen->isSignedIn())->toBeFalse()
        ->and($screen->howMany())->toBe(0)
        // Nothing was met, because the app never got as far as asking — and a
        // remedy beside no obstacle would be an instruction about nothing.
        ->and($screen->met())->toBe('')
        ->and($screen->remedy())->toBe('')
        // Every claim a window makes is a claim about a read that happened.
        // This state is the one where none did, so each of them is the empty
        // answer rather than a number carried over from a state it is not in.
        ->and($screen->howManyArrived())->toBe(0)
        ->and($screen->bound())->toBe(0)
        ->and($screen->isAWindow())->toBeFalse()
        ->and($screen->isSearching())->toBeFalse()
        ->and($saying->askings())->toBe(0);
});

it('N1-R11 — a route naming a stack this device has forgotten is refused', function (): void {
    $screen = theLogScreen(
        AServiceThatSpoke::saying(aWindowWorthReading()),
        named: str_repeat('z', Nonce::SHORTEST),
    );

    expect(fn(): Stack => $screen->stack())->toThrow(StackIsNotConfigured::class);
});

it('refuses a route parameter that is not text', function (): void {
    $screen = theLogScreen(AServiceThatSpoke::saying(aWindowWorthReading()));
    $screen->setParams(['stack' => 42, 'service' => 'gluetun']);

    expect(fn(): Stack => $screen->stack())->toThrow(StackIsUnidentified::class);
});

it('refuses a route naming no service', function (): void {
    // A window over no service is the whole machine talking at once.
    $screen = theLogScreen(AServiceThatSpoke::saying(aWindowWorthReading()));
    $screen->setParams(['stack' => theStackWhoseServiceIsRead()->id()->stored(), 'service' => 42]);

    expect(fn(): ServiceId => $screen->service())->toThrow(ServiceIsUnnamed::class);
});

it('N2-R10 — the screen is registered under the route that reaches it', function (): void {
    $resolved = NativeRouter::resolve(AStacksScreen::Logs->forTheStacksService(
        theStackWhoseServiceIsRead()->id()->stored(),
        'gluetun',
    ));

    expect($resolved['class'] ?? null)->toBe(WhatThisServiceSaid::class);
});

it('the builder and the router agree about which service a path names', function (): void {
    // Not only that the pattern resolves, but that both identifiers survive it.
    // A builder that put them in the wrong segments would still resolve — each
    // pattern matches any single segment — and would open another machine's
    // service.
    $resolved = NativeRouter::resolve(AStacksScreen::Logs->forTheStacksService('a-stack', 'gluetun'));
    $params = is_array($resolved) && is_array($resolved['params'] ?? null) ? $resolved['params'] : [];

    expect($params['stack'] ?? null)->toBe('a-stack')
        ->and($params['service'] ?? null)->toBe('gluetun');
});

it('the way back to the machine is a route as well', function (): void {
    $screen = theLogScreen(AServiceThatSpoke::saying(aWindowWorthReading()));

    expect(NativeRouter::resolve($screen->goes()->health()))->not->toBeNull();
});

it('renders its own view', function (): void {
    expect(theLogScreen(AServiceThatSpoke::saying(aWindowWorthReading()))->render()->name())
        ->toBe('operator::what-this-service-said');
});

it('N1-R3 — asking again after an obstacle asks the stack again', function (): void {
    // The action an obstacle must not take away. Counted rather than asserted
    // by absence of an error, because a screen that kept its held window would
    // hand back the same lines and leave somebody tapping a button that changes
    // nothing.
    $saying = AServiceThatSpoke::met(Obstacle::DeviceHasNoNetwork);
    $screen = theLogScreen($saying);

    $screen->lines();
    $screen->again();
    $screen->lines();

    expect($saying->askings())->toBe(2);
});

it('asking again forgets the window, so a search is not run against stale lines', function (): void {
    // The half no other screen here has. The window is held precisely so
    // searching does not re-ask; an *ask again* that kept it would be a button
    // that reports success and shows yesterday's tail.
    $saying = AServiceThatSpoke::saying(aWindowWorthReading());
    $screen = theLogScreen($saying);

    $screen->lines();
    $screen->again();
    $screen->lines();

    expect($saying->askings())->toBe(2);
});

it('N3-R13 — a credential the stack refused signs this device out and lets the session go', function (): void {
    // This screen makes the same two moves its four siblings do, and it has to
    // make them itself: a fold cannot forget anything, and a session left in the
    // store is resumed on the next frame and refused again.
    $keychain = AKeychainInMemory::working();
    $screen = theLogScreen(AServiceThatSpoke::met(Obstacle::CredentialWasRefused), keychain: $keychain);

    expect($keychain->isHolding(theStackWhoseServiceIsRead()->id()))->toBeTrue();

    expect($screen->isSignedIn())->toBeFalse()
        // Nothing about a machine, because this is not about the machine — and
        // nothing already loaded, which `N3-R13` names separately. A window is
        // the thing this screen most obviously has to drop: an operator reading
        // a service's log under *this stack refused the pairing of this app* is
        // reading lines the stack has just said it will not answer for.
        ->and($screen->met())->toBe('')
        ->and($screen->remedy())->toBe('')
        ->and($screen->lines())->toBe([])
        ->and($screen->howMany())->toBe(0)
        ->and($screen->isAWindow())->toBeFalse()
        ->and($keychain->isHolding(theStackWhoseServiceIsRead()->id()))->toBeFalse();
});

it('N3-R13 — an obstacle that is not a refused credential leaves the session alone', function (): void {
    // The other side of the same line, and the one that keeps this from being a
    // screen that signs somebody out whenever a machine is unreachable. A phone
    // in flight mode has not lost its pairing, and forgetting the session would
    // make somebody sign in again to read a log they were already entitled to.
    $keychain = AKeychainInMemory::working();
    $screen = theLogScreen(AServiceThatSpoke::met(Obstacle::DeviceHasNoNetwork), keychain: $keychain);

    expect($screen->isSignedIn())->toBeTrue()
        ->and($screen->met())->toBe(Obstacle::DeviceHasNoNetwork->said())
        ->and($keychain->isHolding(theStackWhoseServiceIsRead()->id()))->toBeTrue();
});
