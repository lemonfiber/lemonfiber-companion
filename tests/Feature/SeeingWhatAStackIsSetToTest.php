<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Cost;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ProposedChange;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Setting;
use Modules\Kernel\Api\Settings;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackIsUnidentified;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Stance;
use Modules\Kernel\Api\WhatASettingHolds;
use Modules\Kernel\Api\WhatItHoldsNow;
use Modules\Kernel\Api\WhereTheChangeStands;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\WhatThisStackIsSetTo;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AStackThatIsSet;
use Tests\Support\Fakes\AStackToldToChangeSomething;
use Tests\Support\Fakes\StacksInMemory;

// Everything a machine is set to, on one screen.
//
// The screen's whole claim is that what is drawn is what came back, so what is
// worth asserting is not that it renders — it is that it renders the listing
// unchanged: the stack's order, the stack's rows, and the stack's own note
// where a value was withheld.
//
// Here rather than in the operator module's own tests because a screen renders,
// and rendering needs the application — `view()` and `__()` are not there in a
// module suite.

/** The machine whose settings this screen is about. */
function theStackWhoseSettingsAreRead(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/**
 * Three settings, in an order no sort would produce.
 *
 * Deliberately not alphabetical and deliberately mixed: a listing of one kind
 * cannot catch a fold that treats a withheld value like a shown one, and a
 * listing already in order cannot catch a screen that sorts.
 */
function whatTheLoftIsSetTo(): Settings
{
    return Settings::of(
        Setting::called('LIBRARY_PATH', WhatASettingHolds::shown('/data/media')),
        Setting::called('API_KEY', WhatASettingHolds::withheld('set, not shown')),
        Setting::called('BIND', WhatASettingHolds::shown('lan')),
    );
}

/** The screen, with a stack it knows and a keychain holding whatever a test says. */
function theSettingsScreen(
    AStackThatIsSet $arranging,
    ?AStackToldToChangeSomething $adjusting = null,
    ?AKeychainInMemory $keychain = null,
    bool $signedIn = true,
): WhatThisStackIsSetTo {
    $stack = theStackWhoseSettingsAreRead();
    $keychain ??= AKeychainInMemory::working();
    $adjusting ??= AStackToldToChangeSomething::saying(aCheapChangeStaged());

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new WhatThisStackIsSetTo(
        $arranging,
        $adjusting,
        $keychain,
        StacksInMemory::holding($stack),
    );
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

/**
 * Type into the one field, the way the native bridge does.
 *
 * `__syncProperty` rather than a setter, because that is the door the
 * framework actually comes through — a test writing the property some other
 * way would pass against a screen the bridge cannot fill.
 */
function typedIntoTheField(WhatThisStackIsSetTo $screen, string $value): WhatThisStackIsSetTo
{
    $screen->__syncProperty('typed', $value);

    return $screen;
}

/** A cheap change the stack has staged and not written. */
function aCheapChangeStaged(): WhereTheChangeStands
{
    return WhereTheChangeStands::at(
        ProposedChange::of(
            'LIBRARY_PATH',
            '/data/films',
            WhatItHoldsNow::shown('/data/media'),
            Cost::Cheap,
        ),
        Stance::Pending,
    );
}

it('shows every setting the stack sent, in the order it sent them', function (): void {
    $screen = theSettingsScreen(AStackThatIsSet::to(whatTheLoftIsSetTo()));

    expect($screen->answer()->howMany())->toBe(3)
        ->and($screen->answer()->went->isSignedIn)->toBeTrue()
        ->and($screen->answer()->went->met)->toBe('');

    // The order is the stack's. Alphabetical would put API_KEY first, so this
    // fails the day somebody sorts on the way through.
    $rows = $screen->answer()->set;

    expect($rows[0]->key)->toBe('LIBRARY_PATH')
        ->and($rows[1]->key)->toBe('API_KEY')
        ->and($rows[2]->key)->toBe('BIND');
});

it('keeps a withheld value apart from a shown one', function (): void {
    // The assertion the whole fold exists for. Both arrive as strings, and a
    // screen that lost the difference would print a credential's note where a
    // value belongs — or a value where the note belongs, which is the one that
    // matters.
    $rows = theSettingsScreen(AStackThatIsSet::to(whatTheLoftIsSetTo()))->answer()->set;

    expect($rows[0]->withheld)->toBeFalse()
        ->and($rows[0]->said)->toBe('/data/media')
        ->and($rows[1]->withheld)->toBeTrue()
        ->and($rows[1]->said)->toBe('set, not shown');
});

it('says a stack holds nothing rather than drawing a blank', function (): void {
    // Not the same screen as a stack that could not be asked, and they arrive
    // as the same absence. The count is what the template branches on.
    $screen = theSettingsScreen(AStackThatIsSet::toNothing());

    expect($screen->answer()->howMany())->toBe(0)
        ->and($screen->answer()->set)->toBe([])
        ->and($screen->answer()->went->cameBack())->toBeTrue();
});

it('reports what stood in the way rather than an empty listing', function (): void {
    $screen = theSettingsScreen(AStackThatIsSet::met(Obstacle::StackDidNotAnswer));

    expect($screen->answer()->went->cameBack())->toBeFalse()
        ->and($screen->answer()->went->met)->toBe(Obstacle::StackDidNotAnswer->said())
        ->and($screen->answer()->went->remedy)->toBe(Obstacle::StackDidNotAnswer->remedy())
        ->and($screen->answer()->howMany())->toBe(0);
});

it('asks the stack it is on, once, however many rows are drawn', function (): void {
    $arranging = AStackThatIsSet::to(whatTheLoftIsSetTo());
    $screen = theSettingsScreen($arranging);

    $screen->answer();
    $screen->answer();

    expect($arranging->askings())->toBe(1)
        ->and($arranging->askedAbout()?->id()->is(theStackWhoseSettingsAreRead()->id()))->toBeTrue();
});

it('asks again when the operator asks it to', function (): void {
    // The held answer is dropped rather than re-read, so the next thing that
    // wants it does the asking — one path to the stack instead of two that can
    // disagree about what happened.
    $arranging = AStackThatIsSet::to(whatTheLoftIsSetTo());
    $screen = theSettingsScreen($arranging);

    $screen->answer();
    $screen->again();
    $screen->answer();

    expect($arranging->askings())->toBe(2);
});

it('shows the sign-in prompt rather than a listing when the session has gone', function (): void {
    $arranging = AStackThatIsSet::to(whatTheLoftIsSetTo());
    $screen = theSettingsScreen($arranging, signedIn: false);

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($screen->answer()->howMany())->toBe(0)
        // Never asked. A screen that reached the stack with no session would
        // spend a round trip to be told what it already knew.
        ->and($arranging->askings())->toBe(0);
});

it('reads a route parameter that is not text as naming no machine', function (): void {
    // The narrowing on the way out of the router, whose parameter array is
    // untyped. Anything that is not a string names no stack, which is the same
    // situation as a route with nothing in that segment. Every screen that
    // reads a stack out of the route makes this assertion, because every one
    // of them has the same branch — and a branch nothing drives is a branch
    // that can quietly become the other one.
    $screen = theSettingsScreen(AStackThatIsSet::to(whatTheLoftIsSetTo()));
    $screen->setParams(['stack' => 42]);

    expect(fn(): Stack => $screen->stack())->toThrow(StackIsUnidentified::class);
});

it('N3-R13 — lets the session go when the stack refuses the credential', function (): void {
    // The listing is a read, and a read refused on the credential is the same
    // signed-out device as one refused anywhere else: the identity was
    // removed, the password changed, or the stack was rebuilt. A session left
    // in the store is resumed on the next frame and refused again, and the
    // operator ends up at a sign-in prompt on a device that still believes it
    // is signed in.
    $keychain = AKeychainInMemory::working();
    $screen = theSettingsScreen(AStackThatIsSet::met(Obstacle::CredentialWasRefused), keychain: $keychain);

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($keychain->isHolding(theStackWhoseSettingsAreRead()->id()))->toBeFalse();
});

it('N3-R13 — keeps the session when the machine could not be reached', function (): void {
    // The other half, and the reason the release is a decision rather than a
    // reflex. A phone in flight mode has not lost its pairing, and forgetting
    // the session here would make somebody sign in again to read settings they
    // were entitled to read all along.
    $keychain = AKeychainInMemory::working();
    $screen = theSettingsScreen(AStackThatIsSet::met(Obstacle::DeviceHasNoNetwork), keychain: $keychain);

    expect($screen->answer()->went->isSignedIn)->toBeTrue()
        ->and($keychain->isHolding(theStackWhoseSettingsAreRead()->id()))->toBeTrue();
});

it('names where it goes and what it draws', function (): void {
    $screen = theSettingsScreen(AStackThatIsSet::to(whatTheLoftIsSetTo()));

    expect($screen->goes()->settings())->toContain('/settings')
        ->and($screen->render()->name())->toBe('operator::what-this-stack-is-set-to');
});

it('offers a change on a shown setting and on no withheld one', function (): void {
    // The requirement, read off the rows rather than off the template: the app
    // never offers to set or change a credential's value, and a withheld row
    // is the only thing here saying which settings those are.
    $rows = theSettingsScreen(AStackThatIsSet::to(whatTheLoftIsSetTo()))->answer()->set;

    expect($rows[0]->mayBeChanged)->toBeTrue()
        ->and($rows[1]->withheld)->toBeTrue()
        ->and($rows[1]->mayBeChanged)->toBeFalse()
        ->and($rows[2]->mayBeChanged)->toBeTrue();
});

it('refuses to open a withheld setting even when asked directly', function (): void {
    // The half that does not depend on markup. The template draws no control
    // beside a withheld row, and a screen whose guarantee lived only there
    // would be one blade edit from offering to set a credential.
    $screen = theSettingsScreen(AStackThatIsSet::to(whatTheLoftIsSetTo()));

    $screen->change('API_KEY');

    expect($screen->changing())->toBe('')
        ->and($screen->typed())->toBe('');
});

it('opens a shown setting seeded with what it holds', function (): void {
    $screen = theSettingsScreen(AStackThatIsSet::to(whatTheLoftIsSetTo()));

    $screen->change('LIBRARY_PATH');

    expect($screen->changing())->toBe('LIBRARY_PATH')
        ->and($screen->typed())->toBe('/data/media');
});

it('asks what a change would do without making it', function (): void {
    $adjusting = AStackToldToChangeSomething::saying(aCheapChangeStaged());
    $screen = theSettingsScreen(AStackThatIsSet::to(whatTheLoftIsSetTo()), $adjusting);

    $screen->change('LIBRARY_PATH');
    $screen->__syncProperty('typed', '/data/films');
    $screen->wouldBe('LIBRARY_PATH');

    // Rehearsed, never written. The two are one argument apart on the wire and
    // everything apart to the person whose stack it is.
    expect($adjusting->rehearsals())->toBe(1)
        ->and($adjusting->writes())->toBe(0)
        ->and($adjusting->askedFor()?->key)->toBe('LIBRARY_PATH')
        ->and($adjusting->askedFor()?->value)->toBe('/data/films');

    $proposal = $screen->proposal();

    expect($proposal?->toSaid)->toBe('/data/films')
        ->and($proposal?->fromSaid)->toBe('/data/media')
        ->and($proposal?->holdsNothingYet)->toBeFalse()
        ->and($proposal?->costSaid)->toBe(Cost::Cheap->saidOnTheScreen())
        ->and($proposal?->stanceSaid)->toBe(Stance::Pending->saidOnTheScreen())
        ->and($proposal?->wroteSomething)->toBeFalse();
});

it('writes only when the operator agrees, and asks the stack again after', function (): void {
    $arranging = AStackThatIsSet::to(whatTheLoftIsSetTo());
    $adjusting = AStackToldToChangeSomething::saying(WhereTheChangeStands::at(
        ProposedChange::of('LIBRARY_PATH', '/data/films', WhatItHoldsNow::shown('/data/media'), Cost::Cheap),
        Stance::Applied,
    ));
    $screen = theSettingsScreen($arranging, $adjusting);

    $screen->answer();
    $screen->change('LIBRARY_PATH');
    $screen->__syncProperty('typed', '/data/films');
    $screen->agree('LIBRARY_PATH');

    expect($adjusting->writes())->toBe(1)
        ->and($adjusting->rehearsals())->toBe(0)
        ->and($screen->proposal()?->wroteSomething)->toBeTrue();

    // The listing is dropped rather than patched: what the stack now holds is
    // the stack's to say, and a screen editing its own copy of the row would
    // show the operator what it believes rather than what is there.
    $screen->answer();

    expect($arranging->askings())->toBe(2);
});

it('tells a setting that already held the value apart from one that was written', function (): void {
    // The pair the stance enum exists for. Both mean the setting holds what
    // was asked for; only one of them wrote anything, and an operator told
    // "written" about the other goes looking for a restart that never happened.
    $unchanged = AStackToldToChangeSomething::saying(WhereTheChangeStands::at(
        ProposedChange::of('BIND', 'lan', WhatItHoldsNow::shown('lan'), Cost::Cheap),
        Stance::Unchanged,
    ));
    $screen = theSettingsScreen(AStackThatIsSet::to(whatTheLoftIsSetTo()), $unchanged);

    $screen->change('BIND');
    $screen->wouldBe('BIND');

    expect($screen->proposal()?->holdsWhatWasAsked)->toBeTrue()
        ->and($screen->proposal()?->wroteSomething)->toBeFalse();
});

it('carries the stack\'s own reason when a change was blocked', function (): void {
    $blocked = AStackToldToChangeSomething::saying(WhereTheChangeStands::blocked(
        ProposedChange::of('LIBRARY_PATH', '/nowhere', WhatItHoldsNow::shown('/data/media'), Cost::Consequential),
        'That path is not on a filesystem this machine can write to.',
    ));
    $screen = theSettingsScreen(AStackThatIsSet::to(whatTheLoftIsSetTo()), $blocked);

    $screen->change('LIBRARY_PATH');
    $screen->wouldBe('LIBRARY_PATH');

    expect($screen->proposal()?->stanceSaid)->toBe(Stance::Blocked->saidOnTheScreen())
        ->and($screen->proposal()?->refusalSaid)
        ->toBe('That path is not on a filesystem this machine can write to.')
        ->and($screen->proposal()?->mustBeAgreedFirst)->toBeTrue();
});

it('says a setting holds nothing yet rather than showing a blank', function (): void {
    $fresh = AStackToldToChangeSomething::saying(WhereTheChangeStands::at(
        ProposedChange::of('BIND', 'lan', WhatItHoldsNow::nothingYet(), Cost::Cheap),
        Stance::Pending,
    ));
    $screen = theSettingsScreen(AStackThatIsSet::to(whatTheLoftIsSetTo()), $fresh);

    $screen->change('BIND');
    $screen->wouldBe('BIND');

    expect($screen->proposal()?->holdsNothingYet)->toBeTrue()
        ->and($screen->proposal()?->fromSaid)->toBe('');
});

it('reports what stood in the way of a change rather than a silent nothing', function (): void {
    $screen = theSettingsScreen(
        AStackThatIsSet::to(whatTheLoftIsSetTo()),
        AStackToldToChangeSomething::met(Obstacle::StackDidNotAnswer),
    );

    $screen->change('LIBRARY_PATH');
    $screen->wouldBe('LIBRARY_PATH');

    expect($screen->proposal()?->went->cameBack())->toBeFalse()
        ->and($screen->proposal()?->went->met)->toBe(Obstacle::StackDidNotAnswer->said());
});

it('puts nothing to the stack when nothing is open', function (): void {
    $adjusting = AStackToldToChangeSomething::saying(aCheapChangeStaged());
    $screen = theSettingsScreen(AStackThatIsSet::to(whatTheLoftIsSetTo()), $adjusting);

    $screen->wouldBe('LIBRARY_PATH');

    expect($adjusting->rehearsals())->toBe(0)
        ->and($adjusting->writes())->toBe(0)
        ->and($screen->proposal()?->went->cameBack())->toBeTrue()
        ->and($screen->proposal()?->key)->toBe('');
});

it('closes an open setting without asking anything', function (): void {
    $adjusting = AStackToldToChangeSomething::saying(aCheapChangeStaged());
    $screen = theSettingsScreen(AStackThatIsSet::to(whatTheLoftIsSetTo()), $adjusting);

    $screen->change('LIBRARY_PATH');
    $screen->never();

    expect($screen->changing())->toBe('')
        ->and($screen->typed())->toBe('')
        ->and($screen->proposal())->toBeNull()
        ->and($adjusting->rehearsals())->toBe(0);
});

it('shows the sign-in prompt rather than a proposal when the session has gone', function (): void {
    $adjusting = AStackToldToChangeSomething::saying(aCheapChangeStaged());
    $screen = theSettingsScreen(
        AStackThatIsSet::to(whatTheLoftIsSetTo()),
        $adjusting,
        signedIn: false,
    );

    // Opened by hand, because the listing a signed-out screen draws has no
    // rows to tap — this is the state where a session went between the
    // reading and the tap.
    $screen->change('LIBRARY_PATH');

    expect($screen->changing())->toBe('');
});

it('acts on the row the control was drawn beside, not on whatever is open', function (): void {
    // The mistake a listing invites. Every control here sits inside the
    // listing and names its own row, so a tap arriving for a different
    // setting than the one being changed is a tap from a row that is not it —
    // and nothing is put to the stack.
    $adjusting = AStackToldToChangeSomething::saying(aCheapChangeStaged());
    $screen = theSettingsScreen(AStackThatIsSet::to(whatTheLoftIsSetTo()), $adjusting);

    $screen->change('LIBRARY_PATH');
    $screen->wouldBe('BIND');

    expect($adjusting->rehearsals())->toBe(0)
        ->and($screen->proposalFor('BIND'))->toBeNull();
});

it('shows a proposal only under the row it is about', function (): void {
    $screen = theSettingsScreen(AStackThatIsSet::to(whatTheLoftIsSetTo()));

    $screen->change('LIBRARY_PATH');
    $screen->wouldBe('LIBRARY_PATH');

    expect($screen->proposalFor('LIBRARY_PATH'))->not->toBeNull()
        ->and($screen->proposalFor('BIND'))->toBeNull()
        ->and($screen->proposalFor('API_KEY'))->toBeNull();
});

it('shows no proposal under any row when the reading did not come back', function (): void {
    $screen = theSettingsScreen(
        AStackThatIsSet::to(whatTheLoftIsSetTo()),
        AStackToldToChangeSomething::met(Obstacle::StackDidNotAnswer),
    );

    $screen->change('LIBRARY_PATH');
    $screen->wouldBe('LIBRARY_PATH');

    // The obstacle is reported by the screen's own branch rather than drawn
    // as a review under the row, so this returns nothing while
    // `proposal()` still carries what stood in the way.
    expect($screen->proposalFor('LIBRARY_PATH'))->toBeNull()
        ->and($screen->proposal()?->went->cameBack())->toBeFalse();
});

it('says nothing happened when the session went between the reading and the tap', function (): void {
    // The listing was drawn while signed in and the session has since gone.
    // The screen reports it as the reading ending rather than as the stack
    // refusing, because nothing was wrong with the stack.
    $keychain = AKeychainInMemory::working();
    $stack = theStackWhoseSettingsAreRead();
    $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());

    $adjusting = AStackToldToChangeSomething::saying(aCheapChangeStaged());
    $screen = theSettingsScreen(AStackThatIsSet::to(whatTheLoftIsSetTo()), $adjusting, $keychain);

    $screen->change('LIBRARY_PATH');
    $keychain->forget($stack->id());
    $screen->wouldBe('LIBRARY_PATH');

    expect($screen->proposal()?->went->isSignedIn)->toBeFalse()
        ->and($adjusting->rehearsals())->toBe(0);
});

it('closes whatever was open when a key the listing did not offer arrives', function (): void {
    // The half of the withheld-key refusal that a clean screen cannot show.
    // Asked from nothing open, closing is indistinguishable from never having
    // opened; asked with a shown setting already open and half typed into, a
    // screen that only declined to open the new one would leave the old one
    // open — and the operator's next agreement would be about a setting they
    // had stopped looking at.
    $screen = theSettingsScreen(AStackThatIsSet::to(whatTheLoftIsSetTo()));

    $screen->change('LIBRARY_PATH');
    typedIntoTheField($screen, '/data/films');
    $screen->change('API_KEY');

    expect($screen->changing())->toBe('')
        ->and($screen->typed())->toBe('');
});

it('offers no proposal for a setting when none has been asked for', function (): void {
    // The guard that a screen with a proposal in hand cannot exercise. Asked
    // before anything is proposed there is nothing to confuse it with; what
    // this pins is that *nothing proposed* answers nothing rather than
    // reaching past the check into a value that is not there.
    $screen = theSettingsScreen(AStackThatIsSet::to(whatTheLoftIsSetTo()));

    $screen->change('LIBRARY_PATH');

    expect($screen->proposalFor('LIBRARY_PATH'))->toBeNull()
        ->and($screen->proposal())->toBeNull();
});

it('hands the template what has been typed, so the field keeps it', function (): void {
    // The view data rather than the view name. A screen that rendered the right
    // template with nothing in it draws an empty field over what the operator
    // was halfway through typing, which is the one thing a change screen must
    // not do between two taps.
    $screen = theSettingsScreen(AStackThatIsSet::to(whatTheLoftIsSetTo()));

    $screen->change('LIBRARY_PATH');
    typedIntoTheField($screen, '/data/films');

    expect($screen->render()->getData())->toHaveKey('typed')
        ->and($screen->render()->getData()['typed'])->toBe('/data/films');
});

it('asks nothing of the stack for a setting that is not the open one', function (): void {
    // A tap on a control the template is not drawing, with a different setting
    // open. What must not happen is the screen asking the stack what setting
    // *that* one to the value typed into *this* one would come to — a question
    // about a pair the operator never put together.
    $adjusting = AStackToldToChangeSomething::saying(aCheapChangeStaged());
    $screen = theSettingsScreen(AStackThatIsSet::to(whatTheLoftIsSetTo()), $adjusting);

    $screen->change('LIBRARY_PATH');
    typedIntoTheField($screen, '/data/films');
    $screen->wouldBe('BIND');

    expect($adjusting->rehearsals())->toBe(0)
        ->and($adjusting->askedFor())->toBeNull()
        ->and($screen->proposal()?->key)->toBe('');
});

it('N3-R13 — lets the session go when a change is refused on the credential', function (): void {
    // The same release the listing does, on the other call this screen makes.
    // A read refused on the credential and a write refused on it are the same
    // signed-out device, and a screen that let go on one and not the other
    // would leave the operator signed in until they happened to read again.
    $keychain = AKeychainInMemory::working();
    $screen = theSettingsScreen(
        AStackThatIsSet::to(whatTheLoftIsSetTo()),
        AStackToldToChangeSomething::met(Obstacle::CredentialWasRefused),
        keychain: $keychain,
    );

    $screen->change('LIBRARY_PATH');
    typedIntoTheField($screen, '/data/films');
    $screen->wouldBe('LIBRARY_PATH');

    expect($keychain->isHolding(theStackWhoseSettingsAreRead()->id()))->toBeFalse();
});
