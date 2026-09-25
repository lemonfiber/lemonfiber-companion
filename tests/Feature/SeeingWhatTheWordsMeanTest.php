<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AWord;
use Modules\Kernel\Api\AWordInUse;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackIsUnidentified;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheGlossary;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\WhatTheWordsMean;
use Modules\Operator\Internal\ViewModels\AWordAsShown;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AStackThatExplainsItsWords;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;

// What lemonfiber's words mean, as the stack's glossary explains them.
//
// Here rather than in the operator module's own tests because a screen renders,
// and rendering needs the application.

/** The machine this screen is about. */
function theStackWhoseWordsAreRead(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** Three words: one with a longer gloss, one with other names, one with neither. */
function aFewWords(): TheGlossary
{
    return TheGlossary::of(
        AWord::explained('pin', 'The version a service is held at', 'A service runs the version it is pinned to until an update moves the pin.'),
        AWord::explained('seeding', 'Sharing a finished download', '', 'sharing', 'uploading'),
        AWord::explained('stack', 'Everything lemonfiber runs on a machine', ''),
    );
}

/** The screen, with a stack it knows and a keychain holding whatever a test says. Named for this file (`G10`). */
function theWordsScreen(
    AStackThatExplainsItsWords $explaining,
    ?AKeychainInMemory $keychain = null,
    bool $signedIn = true,
): WhatTheWordsMean {
    $stack = theStackWhoseWordsAreRead();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new WhatTheWordsMean($explaining, $keychain, StacksInMemory::holding($stack));
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

it('draws every word with its short gloss and what else it is called, and the longer gloss closed', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theWordsScreen(AStackThatExplainsItsWords::with(aFewWords())))->said();

    expect($drawn)->toContain('pin')
        ->and($drawn)->toContain('The version a service is held at')
        ->and($drawn)->toContain(__('stacks.words.also_called', ['names' => 'sharing, uploading']))
        ->and($drawn)->toContain('Everything lemonfiber runs on a machine')
        ->and($drawn)->not->toContain('A service runs the version it is pinned to until an update moves the pin.');
});

it('offers the longer gloss only where there is one, and opens and closes it', function (): void {
    $screen = theWordsScreen(AStackThatExplainsItsWords::with(aFewWords()));

    expect(WhatTheDeviceWouldDraw::by($screen)->offers())->toBe([__('stacks.words.more', ['word' => 'pin']), __('health.ask_again')]);

    $screen->toggle('0');

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain('A service runs the version it is pinned to until an update moves the pin.')
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->toBe([__('stacks.words.less', ['word' => 'pin']), __('health.ask_again')]);

    $screen->toggle('0');

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->not->toContain('A service runs the version it is pinned to until an update moves the pin.');
});

it('opening a word with no more to say, or a place that is not drawn, opens nothing', function (): void {
    $screen = theWordsScreen(AStackThatExplainsItsWords::with(aFewWords()));

    $screen->toggle('1');
    $screen->toggle('9');

    expect($screen->answer()->words[1]->isOpen)->toBeFalse()
        ->and($screen->open)->toBe('seeding');
});

it('finds a word by name or by what else it is called, and keeps an open word open while searching', function (): void {
    $screen = theWordsScreen(AStackThatExplainsItsWords::with(aFewWords()));
    $screen->toggle('0');

    $screen->looking = 'uploading';
    $found = $screen->answer();

    expect(array_map(static fn(AWordAsShown $word): string => $word->word, $found->words))->toBe(['seeding'])
        ->and($found->isSearching)->toBeTrue();

    $screen->looking = 'PIN';

    expect($screen->answer()->words[0]->isOpen)->toBeTrue();
});

it('a search that finds nothing is not a glossary with nothing in it', function (): void {
    $screen = theWordsScreen(AStackThatExplainsItsWords::with(aFewWords()));
    $screen->looking = 'nothing like it';

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('stacks.words.nothing_matched'))
        ->and(WhatTheDeviceWouldDraw::by(theWordsScreen(AStackThatExplainsItsWords::with(TheGlossary::of())))->said())->toContain(__('stacks.words.none'));
});

it('a search narrows what is shown without asking the machine again', function (): void {
    $explaining = AStackThatExplainsItsWords::with(aFewWords());
    $screen = theWordsScreen($explaining);

    $screen->answer();
    $screen->looking = 'seed';
    $screen->answer();

    expect($explaining->askings())->toBe(1)
        ->and($explaining->wasGivenASession())->toBeTrue()
        ->and($explaining->askedAbout()?->id()->stored())->toBe(theStackWhoseWordsAreRead()->id()->stored());
});

it('a glossary that could not be read is not an empty one', function (): void {
    $answer = theWordsScreen(AStackThatExplainsItsWords::met(Obstacle::StackDidNotAnswer))->answer();

    expect($answer->went->cameBack())->toBeFalse()
        ->and($answer->went->met)->toBe(Obstacle::StackDidNotAnswer->said())
        ->and($answer->words)->toBe([]);
});

it('a session that has ended asks nothing', function (): void {
    $explaining = AStackThatExplainsItsWords::with(aFewWords());
    $answer = theWordsScreen($explaining, signedIn: false)->answer();

    expect($answer->went->isSignedIn)->toBeFalse()
        ->and($explaining->askings())->toBe(0);
});

it('a credential the stack refused signs this device out and lets the session go', function (): void {
    $keychain = AKeychainInMemory::working();
    $screen = theWordsScreen(AStackThatExplainsItsWords::met(Obstacle::CredentialWasRefused), $keychain);

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($keychain->isHolding(theStackWhoseWordsAreRead()->id()))->toBeFalse();
});

it('asking again asks the machine again', function (): void {
    $explaining = AStackThatExplainsItsWords::with(aFewWords());
    $screen = theWordsScreen($explaining);

    $screen->answer();
    $screen->again();
    $screen->answer();

    expect($explaining->askings())->toBe(2);
});

it('refuses a route parameter that is not text', function (): void {
    $screen = theWordsScreen(AStackThatExplainsItsWords::met(Obstacle::DeviceHasNoNetwork));
    $screen->setParams(['stack' => 42]);

    expect(fn(): Stack => $screen->stack())->toThrow(StackIsUnidentified::class);
});

it('the way here and the way back are routes', function (): void {
    $screen = theWordsScreen(AStackThatExplainsItsWords::with(aFewWords()));

    expect(NativeRouter::resolve($screen->goes()->health()))->not->toBeNull()
        ->and(NativeRouter::resolve($screen->goes()->ofItself()->words()))->not->toBeNull();
});

it('renders its own view', function (): void {
    expect(theWordsScreen(AStackThatExplainsItsWords::with(aFewWords()))->render()->name())->toBe('operator::what-the-words-mean');
});

it('opens on the word another screen sent somebody to, by any name it goes by, and closes it on a tap', function (): void {
    $glossary = TheGlossary::of(
        AWord::explained('grab', 'Sending a release to the download client', 'The indexer found it; the client has it now.', 'snatch'),
        AWord::explained('pin', 'The version a service is held at', ''),
    );
    $screen = theWordsScreen(AStackThatExplainsItsWords::with($glossary));
    $screen->mount('snatch');

    expect($screen->looking)->toBe('snatch')
        ->and($screen->answer()->words)->toHaveCount(1)
        ->and($screen->answer()->words[0]->isOpen)->toBeTrue();

    $screen->toggle('0');

    expect($screen->answer()->words[0]->isOpen)->toBeFalse();
});

it('opens on no word where the route names none', function (): void {
    $screen = theWordsScreen(AStackThatExplainsItsWords::with(aFewWords()));
    $screen->mount(' ');

    expect([$screen->looking, $screen->open])->toBe(['', ''])
        ->and($screen->answer()->words)->toHaveCount(3);
});

it('the way to one word is a route to this screen', function (): void {
    $screen = theWordsScreen(AStackThatExplainsItsWords::with(aFewWords()));

    expect(NativeRouter::resolve($screen->goes()->ofItself()->wordAbout(AWordInUse::named('pin'))))->not->toBeNull();
});

it('carries a word with a space or a slash to the screen as it was drawn', function (): void {
    $screen = theWordsScreen(AStackThatExplainsItsWords::with(aFewWords()));

    foreach (['port forwarding', 'AC/DC', 'Dune: Part Two'] as $word) {
        expect(NativeRouter::resolve($screen->goes()->ofItself()->wordAbout(AWordInUse::named($word))))->toHaveKey('params.service', $word);
    }
});
