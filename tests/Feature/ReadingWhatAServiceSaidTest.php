<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowManyLines;
use Modules\Kernel\Api\HowSeriousALineIs;
use Modules\Kernel\Api\KindOfObstacle;
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
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\WhatThisServiceSaid;
use Modules\Stacks\Api\AStacksScreen;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AServiceThatSpoke;
use Tests\Support\Fakes\AZoneThatIsSet;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;

// Logs offered as a bounded, searchable read that names the service
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

/** A line, a banner of three lines holding no letters, a line, a lone rule, and a line. */
function aBannerAndThen(ServiceId $service): Scrollback
{
    return Scrollback::of(
        $service,
        HowManyLines::of(10),
        Said::at('2026-09-14T04:00:00Z', 'starting', $service, Stream::Stdout),
        Said::at('2026-09-14T04:00:00Z', '@@@==@@@', $service, Stream::Stdout),
        Said::at('2026-09-14T04:00:00Z', '', $service, Stream::Stdout),
        Said::at('2026-09-14T04:00:00Z', '@@==@@', $service, Stream::Stdout),
        Said::at('2026-09-14T04:00:01Z', 'ready', $service, Stream::Stdout),
        Said::at('2026-09-14T04:00:01Z', '=====', $service, Stream::Stdout),
        Said::at('2026-09-14T04:00:01Z', 'listening', $service, Stream::Stdout),
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
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new WhatThisServiceSaid(
        $saying,
        $keychain,
        AroundThePhone::holding(StacksInMemory::holding($stack)),
        AZoneThatIsSet::to('Europe/Amsterdam'),
        new AppsSettingsThatOpen(),
        AroundThePhone::listening(),
    );
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

    foreach ($screen->answer()->lines as $line) {
        $rows[] = sprintf('%s/%s', $line->streamSaid, $line->line);
    }

    return implode(' | ', $rows);
}

/**
 * Where each scroll view on a drawn log screen opens, outermost first.
 *
 * @return list<string>
 */
function whereTheLogScreenOpens(mixed $node): array
{
    if (! is_array($node)) {
        return [];
    }

    $anchor = data_get($node, 'props.scroll_anchor');
    $found = $node['type'] === 'scroll_view' ? [is_string($anchor) ? $anchor : 'top'] : [];
    $children = data_get($node, 'children');

    foreach (is_array($children) ? $children : [] as $child) {
        $found = [...$found, ...whereTheLogScreenOpens($child)];
    }

    return $found;
}

/** What the catalogue says for a key, as the text it is. */
function whatTheLogScreenCalls(string $key): string
{
    $said = __($key);

    return is_string($said) ? $said : '';
}

/**
 * Every name a drawn log screen gives a screen reader in place of what it shows.
 *
 * @return list<string>
 */
function everythingReadAloudOnTheLogScreen(mixed $node): array
{
    if (! is_array($node)) {
        return [];
    }

    $named = data_get($node, 'props.a11y_label');
    $found = is_string($named) ? [$named] : [];
    $children = data_get($node, 'children');

    foreach (is_array($children) ? $children : [] as $child) {
        $found = [...$found, ...everythingReadAloudOnTheLogScreen($child)];
    }

    return $found;
}

it('opens at the last line, where a service says why it stopped', function (): void {
    expect(whereTheLogScreenOpens(WhatTheDeviceWouldDraw::tree(
        theLogScreen(AServiceThatSpoke::saying(aWindowWorthReading())),
    )))->toBe(['bottom']);
});

it('shows the lines, oldest first, marking only those from the error stream', function (): void {
    $screen = theLogScreen(AServiceThatSpoke::saying(aWindowWorthReading()));

    expect(everyLineOnTheScreen($screen))->toBe(sprintf(
        '/tunnel up | %s/connection timed out | /retrying',
        Stream::Stderr->saidOnTheScreen(),
    ))->and($screen->answer()->went->met)->toBe('')
        ->and($screen->answer()->went->isSignedIn)->toBeTrue()
        // A read that worked leaves no obstacle and so nothing to do about one.
        // Asserted beside `met()` because the two are written together and only
        // one of them was read back, which is how a remedy for nothing survives.
        ->and($screen->answer()->went->remedy)->toBe('');
});

it('a line the service timed carries the time on the phone\'s clock, and one it did not says so', function (): void {
    // `hasAMoment` is a field of its own so a template never has to read an
    // empty `at` as *no moment*. That only holds if the two disagree somewhere:
    // a fold that set the flag from nothing, or set it the same way on both
    // arms, renders identically on every line that does carry a time — and the
    // lines without one are exactly where nobody looks.
    $rows = theLogScreen(AServiceThatSpoke::saying(aWindowWorthReading()))->answer()->lines;

    // Four in the morning UTC is six on a phone set to Amsterdam in September.
    expect($rows[0]->at)->toBe('06:00:00')
        ->and($rows[0]->atInFull)->toBe('2026-09-14T04:00:00Z')
        ->and($rows[0]->hasAMoment)->toBeTrue()
        // The service wrote this one without a time, so there is nothing to show
        // and the screen says which rather than showing a blank where a moment
        // goes.
        ->and($rows[1]->at)->toBe('')
        ->and($rows[1]->atInFull)->toBe('')
        ->and($rows[1]->hasAMoment)->toBeFalse()
        ->and($rows[2]->at)->toBe('06:00:02')
        ->and($rows[2]->hasAMoment)->toBeTrue();
});

it('draws the time on the phone\'s clock and reads out the moment as the service wrote it', function (): void {
    $service = theServiceOnTheScreen();
    $screen = theLogScreen(AServiceThatSpoke::saying(Scrollback::of(
        $service,
        HowManyLines::of(3),
        Said::at('2026-09-29T21:39:01.530691525Z', 'VPN provider name is not valid', $service, Stream::Stderr),
        Said::at('2026-09-29T21:39:02.000000001Z', 'shutting down', $service, Stream::Stdout),
        Said::at('not a moment', 'what now', $service, Stream::Stdout),
    )));
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();
    $noticed = whatTheLogScreenCalls('health.stream.stderr');

    expect($drawn)->toContain(
        sprintf('23:39:01 · %s', $noticed),
        '23:39:02',
        // A moment nobody can read is shown as it was written.
        'not a moment',
    );
    expect($drawn)->not->toContain(whatTheLogScreenCalls('health.stream.stdout'));
    expect(everythingReadAloudOnTheLogScreen(WhatTheDeviceWouldDraw::tree($screen)))->toContain(
        sprintf('2026-09-29T21:39:01.530691525Z · %s', $noticed),
        '2026-09-29T21:39:02.000000001Z',
        'not a moment',
    );
});

it('folds a run of lines holding no letters into one row that says how many', function (): void {
    $service = theServiceOnTheScreen();
    $screen = theLogScreen(AServiceThatSpoke::saying(aBannerAndThen($service)));
    $rows = $screen->answer()->lines;

    expect($rows)->toHaveCount(5)
        ->and($rows[0]->line)->toBe('starting')
        ->and($rows[1]->folded)->toBe(3)
        ->and($rows[1]->fold)->toBe(0)
        ->and($rows[1]->isOpen)->toBeFalse()
        ->and($rows[2]->line)->toBe('ready')
        // One such line alone is left as it is: a fold of one hides nothing.
        ->and($rows[3]->line)->toBe('=====')
        ->and($rows[3]->folded)->toBe(0)
        ->and($rows[4]->line)->toBe('listening');
    expect(WhatTheDeviceWouldDraw::by($screen)->offers())
        ->toContain(trans_choice('health.decorative_lines', 3));
    expect(trans_choice('health.decorative_lines', 3))->toBe('3 decorative lines');
});

it('opens a fold to show the lines it holds, and closes it again', function (): void {
    $service = theServiceOnTheScreen();
    $screen = theLogScreen(AServiceThatSpoke::saying(aBannerAndThen($service)));
    $screen->answer();

    $screen->unfold(0);
    $open = $screen->answer()->lines;

    expect($open)->toHaveCount(8)
        ->and($open[1]->folded)->toBe(3)
        ->and($open[1]->isOpen)->toBeTrue()
        ->and($open[2]->line)->toBe('@@@==@@@')
        ->and($open[3]->line)->toBe('')
        ->and($open[4]->line)->toBe('@@==@@');

    $screen->unfold(0);

    expect($screen->answer()->lines)->toHaveCount(5);
});

it('keeps each fold it opened, and forgets them all when asked again', function (): void {
    $service = theServiceOnTheScreen();
    $screen = theLogScreen(AServiceThatSpoke::saying(Scrollback::of(
        $service,
        HowManyLines::of(10),
        Said::whenever('@@@', $service, Stream::Stdout),
        Said::whenever('@@@', $service, Stream::Stdout),
        Said::whenever('between', $service, Stream::Stdout),
        Said::whenever('###', $service, Stream::Stdout),
        Said::whenever('###', $service, Stream::Stdout),
    )));
    $screen->answer();

    $screen->unfold(1);
    $rows = $screen->answer()->lines;

    expect($rows[0]->fold)->toBe(0)
        ->and($rows[0]->isOpen)->toBeFalse()
        ->and($rows[2]->fold)->toBe(1)
        ->and($rows[2]->isOpen)->toBeTrue()
        ->and($rows)->toHaveCount(5);

    $screen->unfold(0);

    expect($screen->unfolded)->toBe([1, 0]);

    expect($screen->readIn?->name())->toBe('Europe/Amsterdam');

    $screen->again();

    // Asking again reads the phone's zone afresh, with the lines.
    expect($screen->unfolded)->toBe([])
        ->and($screen->readIn)->toBeNull();
});

it('folds nothing while a search is on', function (): void {
    // The lines a search found are the ones somebody asked to see.
    $service = theServiceOnTheScreen();
    $rows = typedIntoTheSearch(theLogScreen(AServiceThatSpoke::saying(aBannerAndThen($service))), '@')->answer()->lines;

    expect($rows)->toHaveCount(2)
        ->and($rows[0]->line)->toBe('@@@==@@@')
        ->and($rows[1]->line)->toBe('@@==@@');
});

it('says above the lines the code the road here carried, from a finding or from how the service stopped', function (): void {
    $fromAFinding = theLogScreen(AServiceThatSpoke::saying(aWindowWorthReading()));
    $fromAFinding->setData(['reported' => 'VPN-2']);
    $fromTheService = theLogScreen(AServiceThatSpoke::saying(aWindowWorthReading()));
    $fromTheService->setData(['exited' => '1']);

    expect(WhatTheDeviceWouldDraw::by($fromAFinding)->said())->toContain(__('health.the_check_reported', ['code' => 'VPN-2']))
        ->and(WhatTheDeviceWouldDraw::by($fromTheService)->said())->toContain(__('health.it_stopped_with_exit_code', ['code' => '1']));
});

it('says no code where the road here carried none', function (): void {
    $screen = theLogScreen(AServiceThatSpoke::saying(aWindowWorthReading()));
    $screen->setData(['reported' => 42]);

    expect($screen->reported())->toBe('')
        ->and($screen->exited())->toBe('');
});

it('heads the lines with what the stack calls the service, where the road here carried it', function (): void {
    $named = theLogScreen(AServiceThatSpoke::saying(aWindowWorthReading()));
    $named->setData(['called' => 'Gluetun']);
    $unnamed = theLogScreen(AServiceThatSpoke::saying(aWindowWorthReading()));
    $unnamed->setData(['called' => 42]);

    // The route still names it by its id, which is what the logs are asked
    // for with; only the heading takes the name.
    expect($named->called())->toBe('Gluetun')
        ->and($named->service()->named())->toBe('gluetun')
        ->and($unnamed->called())->toBe('gluetun');
});

it('names the service it is about, from the route', function (): void {
    // From the route rather than held, because a screen holding the service it
    // was opened with, on a frame whose URI names another, would show one
    // service's lines under another's heading.
    expect(theLogScreen(AServiceThatSpoke::saying(aWindowWorthReading()))->service()->named())
        ->toBe('gluetun');
});

it('states the bound and whether the view stops at it', function (): void {
    $screen = theLogScreen(AServiceThatSpoke::saying(aWindowWorthReading()));

    expect($screen->answer()->bound)->toBe(3)
        ->and($screen->answer()->arrived)->toBe(3)
        ->and($screen->answer()->isAWindow)->toBeTrue();
});

it('a read the bound did not cut says so, and claims nothing more', function (): void {
    $service = theServiceOnTheScreen();
    $short = Scrollback::of(
        $service,
        HowManyLines::of(10),
        Said::whenever('tunnel up', $service, Stream::Stdout),
    );

    expect(theLogScreen(AServiceThatSpoke::saying($short))->answer()->isAWindow)->toBeFalse();
});

it('searching narrows what is shown and not what was read', function (): void {
    $screen = theLogScreen(AServiceThatSpoke::saying(aWindowWorthReading()));
    typedIntoTheSearch($screen, 'timed');

    expect(everyLineOnTheScreen($screen))
        ->toBe(sprintf('%s/connection timed out', Stream::Stderr->saidOnTheScreen()))
        ->and($screen->howMany())->toBe(1)
        // The claim about the edge survives the search, which is the whole
        // point: a search that covered twelve of two hundred lines must not
        // report that it covered everything the service ever said.
        ->and($screen->answer()->arrived)->toBe(3)
        ->and($screen->answer()->isAWindow)->toBeTrue()
        ->and($screen->answer()->isSearching)->toBeTrue();
});

it('narrowing does not ask the stack again', function (): void {
    // A screen that re-read per keystroke would open a connection per letter to
    // a machine on a home network — and would change what is being searched
    // underneath the person searching it.
    $saying = AServiceThatSpoke::saying(aWindowWorthReading());
    $screen = theLogScreen($saying);

    $screen->answer();
    typedIntoTheSearch($screen, 'timed');
    $screen->answer();
    typedIntoTheSearch($screen, 'retry');
    $screen->answer();

    expect($saying->askings())->toBe(1);
});

it('widening the search again shows the lines it had hidden', function (): void {
    // Every search runs against what arrived rather than against the last
    // search, so a backspace cannot leave lines hidden.
    $screen = theLogScreen(AServiceThatSpoke::saying(aWindowWorthReading()));

    typedIntoTheSearch($screen, 'timed');
    $screen->answer();
    typedIntoTheSearch($screen, '');

    expect($screen->howMany())->toBe(3)
        ->and($screen->answer()->isSearching)->toBeFalse();
});

it('an empty box is not a search', function (): void {
    $screen = theLogScreen(AServiceThatSpoke::saying(aWindowWorthReading()));
    typedIntoTheSearch($screen, '   ');

    expect($screen->answer()->isSearching)->toBeFalse()
        ->and($screen->howMany())->toBe(3)
        ->and($screen->looking())->toBe('   ');
});

it('asks for as much as a phone shows, and for this service', function (): void {
    $saying = AServiceThatSpoke::saying(aWindowWorthReading());
    theLogScreen($saying)->answer();

    expect($saying->askedFor()?->named())->toBe('gluetun')
        ->and($saying->askedWith()?->figure())->toBe(HowManyLines::ON_A_PHONE)
        ->and($saying->askedAbout()?->id()->stored())->toBe(theStackWhoseServiceIsRead()->id()->stored())
        ->and($saying->wasGivenASession())->toBeTrue();
});

it('a stack that could not be asked says which of the six it met', function (): void {
    $screen = theLogScreen(AServiceThatSpoke::met(Obstacle::of(KindOfObstacle::DeviceHasNoNetwork)));

    expect($screen->howMany())->toBe(0)
        // Meeting an obstacle is not losing the session: the device asked and
        // was answered. Reporting otherwise would put the sign-in screen in
        // front of an operator whose session works, and the sign-in branch comes
        // first in the template — so which of the six was met is never reached.
        ->and($screen->answer()->went->isSignedIn)->toBeTrue()
        ->and($screen->answer()->went->met)->toEqual(KindOfObstacle::DeviceHasNoNetwork->said())
        ->and($screen->answer()->went->remedy)->toEqual(KindOfObstacle::DeviceHasNoNetwork->remedy())
        // Nothing to be a window over, so no claim is made about an edge.
        ->and($screen->answer()->isAWindow)->toBeFalse()
        ->and($screen->answer()->bound)->toBe(0)
        // Nothing arrived, and nothing was being looked for. Both are counted
        // and rendered beside the rows, so a state with no rows that claimed
        // either would put a count over an empty screen.
        ->and($screen->answer()->arrived)->toBe(0)
        ->and($screen->answer()->isSearching)->toBeFalse();
});

it('a device with no session for that stack is not asked to wait for one', function (): void {
    $saying = AServiceThatSpoke::saying(aWindowWorthReading());
    $screen = theLogScreen($saying, signedIn: false);

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($screen->howMany())->toBe(0)
        // Nothing was met, because the app never got as far as asking — and a
        // remedy beside no obstacle would be an instruction about nothing.
        ->and($screen->answer()->went->met)->toBe('')
        ->and($screen->answer()->went->remedy)->toBe('')
        // Every claim a window makes is a claim about a read that happened.
        // This state is the one where none did, so each of them is the empty
        // answer rather than a number carried over from a state it is not in.
        ->and($screen->answer()->arrived)->toBe(0)
        ->and($screen->answer()->bound)->toBe(0)
        ->and($screen->answer()->isAWindow)->toBeFalse()
        ->and($screen->answer()->isSearching)->toBeFalse()
        ->and($saying->askings())->toBe(0);
});

it('a route naming a stack this device has forgotten is refused', function (): void {
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

it('the screen is registered under the route that reaches it', function (): void {
    $resolved = NativeRouter::resolve(AStacksScreen::Logs->forTheStacksService(
        theStackWhoseServiceIsRead()->id(),
        ServiceId::called('gluetun'),
    ));

    expect($resolved['class'] ?? null)->toBe(WhatThisServiceSaid::class);
});

it('the builder and the router agree about which service a path names', function (): void {
    // Not only that the pattern resolves, but that both identifiers survive it.
    // A builder that put them in the wrong segments would still resolve — each
    // pattern matches any single segment — and would open another machine's
    // service.
    $resolved = NativeRouter::resolve(AStacksScreen::Logs->forTheStacksService(
        StackId::rememberedAs('a-stack'),
        ServiceId::called('gluetun'),
    ));
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

it('asking again after an obstacle asks the stack again', function (): void {
    // The action an obstacle must not take away. Counted rather than asserted
    // by absence of an error, because a screen that kept its held window would
    // hand back the same lines and leave somebody tapping a button that changes
    // nothing.
    $saying = AServiceThatSpoke::met(Obstacle::of(KindOfObstacle::DeviceHasNoNetwork));
    $screen = theLogScreen($saying);

    $screen->answer();
    $screen->again();
    $screen->answer();

    expect($saying->askings())->toBe(2);
});

it('asking again forgets the window, so a search is not run against stale lines', function (): void {
    // The half no other screen here has. The window is held precisely so
    // searching does not re-ask; an *ask again* that kept it would be a button
    // that reports success and shows yesterday's tail.
    $saying = AServiceThatSpoke::saying(aWindowWorthReading());
    $screen = theLogScreen($saying);

    $screen->answer();
    $screen->again();
    $screen->answer();

    expect($saying->askings())->toBe(2);
});

it('a credential the stack refused signs this device out and lets the session go', function (): void {
    // This screen makes the same two moves its four siblings do, and it has to
    // make them itself: a fold cannot forget anything, and a session left in the
    // store is resumed on the next frame and refused again.
    $keychain = AKeychainInMemory::working();
    $screen = theLogScreen(AServiceThatSpoke::met(Obstacle::of(KindOfObstacle::CredentialWasRefused)), keychain: $keychain);

    expect($keychain->isHolding(theStackWhoseServiceIsRead()->id()))->toBeTrue();

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        // Nothing about a machine, because this is not about the machine — and
        // nothing already loaded, which is named separately. A window is
        // the thing this screen most obviously has to drop: an operator reading
        // a service's log under *this stack refused the pairing of this app* is
        // reading lines the stack has just said it will not answer for.
        ->and($screen->answer()->went->met)->toBe('')
        ->and($screen->answer()->went->remedy)->toBe('')
        ->and($screen->answer()->lines)->toBe([])
        ->and($screen->howMany())->toBe(0)
        ->and($screen->answer()->isAWindow)->toBeFalse()
        ->and($keychain->isHolding(theStackWhoseServiceIsRead()->id()))->toBeFalse();
});

it('an obstacle that is not a refused credential leaves the session alone', function (): void {
    // The other side of the same line, and the one that keeps this from being a
    // screen that signs somebody out whenever a machine is unreachable. A phone
    // in flight mode has not lost its pairing, and forgetting the session would
    // make somebody sign in again to read a log they were already entitled to.
    $keychain = AKeychainInMemory::working();
    $screen = theLogScreen(AServiceThatSpoke::met(Obstacle::of(KindOfObstacle::DeviceHasNoNetwork)), keychain: $keychain);

    expect($screen->answer()->went->isSignedIn)->toBeTrue()
        ->and($screen->answer()->went->met)->toEqual(KindOfObstacle::DeviceHasNoNetwork->said())
        ->and($keychain->isHolding(theStackWhoseServiceIsRead()->id()))->toBeTrue();
});

it('hands the view the state its markup reads by name', function (): void {
    // `native:model="looking"` expands to `:value="$looking"` — a bare variable
    // in the compiled view — and `NativeComponent::fromView()` fills a view's
    // data from a component's **public** properties. The search box's state is
    // `protected`, deliberately, so the view was handed nothing and `$looking`
    // was undefined on every frame.
    //
    // An undefined variable is a warning rather than a stop: the field drew
    // empty and nothing anywhere said why. `F15` is what saw it, by drawing
    // every screen the router serves — and this screen is only reached there
    // behind a session, so the fact is pinned here as well, where the session
    // is a line in a builder rather than a stand-in keychain.
    $screen = theLogScreen(AServiceThatSpoke::saying(aWindowWorthReading()));

    expect($screen->render()->getData())->toHaveKey('looking');
});

it('draws the list of stacks when the menu opens it, though its top bar names the service rather than the stack', function (): void {
    $screen = theLogScreen(AServiceThatSpoke::saying(aWindowWorthReading()));

    expect(WhatTheDeviceWouldDraw::inTheListOfStacks($screen)->said())->toBe([]);

    $screen->chooseAStack();

    expect(WhatTheDeviceWouldDraw::inTheListOfStacks($screen)->offers())
        ->toBe([theStackWhoseServiceIsRead()->name()->shown(), __('navigation.switcher.add')]);
});

/** A window where an error comes after two ordinary lines, and a warning and an info line after it. */
function aWindowWithAnErrorInIt(): Scrollback
{
    $service = theServiceOnTheScreen();

    return Scrollback::of(
        $service,
        HowManyLines::of(10),
        Said::whenever('starting', $service, Stream::Stdout)->declaring(HowSeriousALineIs::Info),
        Said::whenever('checking the settings', $service, Stream::Stdout),
        Said::whenever('VPN provider name is not valid', $service, Stream::Stderr)->declaring(HowSeriousALineIs::Error),
        Said::whenever('slow to answer', $service, Stream::Stdout)->declaring(HowSeriousALineIs::Warn),
        Said::whenever('gone', $service, Stream::Stderr)->declaring(HowSeriousALineIs::Fatal),
    );
}

it('gives an error, a fatal line and a warning the glyph of their tone, read aloud as their word', function (): void {
    $screen = theLogScreen(AServiceThatSpoke::saying(aWindowWithAnErrorInIt()));
    $rows = $screen->answer()->lines;

    expect($rows[0]->tone)->toBe('')
        ->and($rows[1]->tone)->toBe('')
        ->and($rows[2]->tone)->toBe('trouble')
        ->and($rows[2]->isAnError)->toBeTrue()
        ->and($rows[3]->tone)->toBe('attention')
        ->and($rows[3]->isAnError)->toBeFalse()
        ->and($rows[4]->tone)->toBe('trouble')
        ->and($rows[4]->isAnError)->toBeTrue();
    $readAloud = everythingReadAloudOnTheLogScreen(WhatTheDeviceWouldDraw::tree($screen));

    expect($readAloud)->toContain(
        whatTheLogScreenCalls('health.level.error'),
        whatTheLogScreenCalls('health.level.warn'),
        whatTheLogScreenCalls('health.level.fatal'),
    );
    expect($readAloud)->not->toContain(whatTheLogScreenCalls('health.level.info'));
});

it('offers the lines from the first error at the top, which starts them there and offers every line back', function (): void {
    $screen = theLogScreen(AServiceThatSpoke::saying(aWindowWithAnErrorInIt()));

    expect($screen->answer()->hasAnErrorFurtherDown)->toBeTrue()
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers()[0] ?? '')->toBe(whatTheLogScreenCalls('health.show_from_the_first_error'))
        ->and(whereTheLogScreenOpens(WhatTheDeviceWouldDraw::tree($screen)))->toBe(['bottom']);

    $screen->showFromTheFirstError();
    $rows = $screen->answer()->lines;

    // The lines start at the error; the window's claim about its edge is the
    // one it made before, because nothing arrived differently.
    expect($rows)->toHaveCount(3)
        ->and($rows[0]->line)->toBe('VPN provider name is not valid')
        ->and($screen->answer()->arrived)->toBe(5)
        ->and($screen->answer()->startsAtTheFirstError)->toBeTrue()
        ->and($screen->answer()->hasAnErrorFurtherDown)->toBeFalse()
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers()[0] ?? '')->toBe(whatTheLogScreenCalls('health.show_every_line'))
        ->and(whereTheLogScreenOpens(WhatTheDeviceWouldDraw::tree($screen)))->toBe(['top']);

    $screen->showEveryLine();

    expect($screen->answer()->lines)->toHaveCount(5);

    $screen->showFromTheFirstError();
    $screen->again();

    expect($screen->fromTheFirstError)->toBeFalse();
});

it('offers nothing to show from where no line declared an error, or where the first line is the error', function (): void {
    $service = theServiceOnTheScreen();
    $quiet = theLogScreen(AServiceThatSpoke::saying(aWindowWorthReading()));
    $atOnce = theLogScreen(AServiceThatSpoke::saying(Scrollback::of(
        $service,
        HowManyLines::of(10),
        Said::whenever('broken', $service, Stream::Stderr)->declaring(HowSeriousALineIs::Error),
        Said::whenever('still broken', $service, Stream::Stderr)->declaring(HowSeriousALineIs::Error),
    )));

    expect($quiet->answer()->hasAnErrorFurtherDown)->toBeFalse()
        ->and(WhatTheDeviceWouldDraw::by($quiet)->offers())->not->toContain(whatTheLogScreenCalls('health.show_from_the_first_error'))
        ->and($atOnce->answer()->hasAnErrorFurtherDown)->toBeFalse();

    // Asking where nothing comes before the first error leaves every line where it was.
    $atOnce->showFromTheFirstError();

    expect($atOnce->answer()->startsAtTheFirstError)->toBeFalse()
        ->and($atOnce->answer()->lines)->toHaveCount(2);
});
