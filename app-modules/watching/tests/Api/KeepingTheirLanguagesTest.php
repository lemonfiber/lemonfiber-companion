<?php

declare(strict_types=1);

use Modules\Kernel\Api\HearIn;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Noted;
use Modules\Kernel\Api\ReadIn;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\TheirLanguages;
use Modules\Kernel\Api\Whose;
use Modules\Watching\Api\KeepingTheirLanguages;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\ASealInMemory;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\ReadingsInMemory;
use Tests\Support\TheWordCarriedOut;

/** A stack a member chooses languages on. Named for this file. */
function aStackLanguagesAreChosenOn(string $seed = 'l'): StackId
{
    return StackId::of(Nonce::of(str_repeat($seed, Nonce::SHORTEST)));
}

/** Whether a choice was kept, as a word. Named for this file. */
function whetherTheChoiceWasKept(Noted $noted): string
{
    return $noted->either(
        down: static fn(Instant $at): TheWordCarriedOut => new TheWordCarriedOut(sprintf('kept at %d', $at->epochSeconds())),
        notKept: static fn(): TheWordCarriedOut => new TheWordCarriedOut('not kept'),
    )->said;
}

/** Keeping languages over this seal and store, for whoever this keychain holds signed in. Named for this file. */
function languagesKeptOver(ASealInMemory $seal, ReadingsInMemory $store, ?AKeychainInMemory $signedIn = null): KeepingTheirLanguages
{
    return new KeepingTheirLanguages($seal, $store, FrozenClock::at(Instant::atEpochSeconds(7)), $signedIn ?? AKeychainInMemory::working());
}

it('hands back what the member chose on a stack, and the title as it comes where they chose nothing', function (): void {
    $keeping = languagesKeptOver(ASealInMemory::working(), ReadingsInMemory::empty());
    $chosen = TheirLanguages::of(HearIn::Dutch, ReadIn::English);

    $before = $keeping->of(aStackLanguagesAreChosenOn(), Whose::member('ada'));
    $noted = $keeping->choose(aStackLanguagesAreChosenOn(), Whose::member('ada'), $chosen);

    expect(whetherTheChoiceWasKept($noted))->toBe('kept at 7')
        ->and($before)->toEqual(TheirLanguages::asTheTitleComes())
        ->and($keeping->of(aStackLanguagesAreChosenOn(), Whose::member('ada')))->toEqual($chosen)
        ->and($keeping->of(aStackLanguagesAreChosenOn('m'), Whose::member('ada')))->toEqual(TheirLanguages::asTheTitleComes())
        ->and($keeping->of(aStackLanguagesAreChosenOn(), Whose::member('sam')))->toEqual(TheirLanguages::asTheTitleComes());
});

it('keeps nothing where the seal or the store will not', function (ASealInMemory $seal, ReadingsInMemory $store): void {
    $noted = languagesKeptOver($seal, $store)->choose(aStackLanguagesAreChosenOn(), Whose::member('ada'), TheirLanguages::of(HearIn::Dutch, ReadIn::English));

    expect(whetherTheChoiceWasKept($noted))->toBe('not kept');
})->with([
    'a seal with no secure storage' => [ASealInMemory::withNoSecureStorage(), ReadingsInMemory::empty()],
    'a store out of reach' => [ASealInMemory::working(), ReadingsInMemory::unreachable()],
]);

it('lets go of a choice that does not open, or that a later build wrote, and plays the title as it comes', function (string $how): void {
    $seal = ASealInMemory::working();
    $store = ReadingsInMemory::empty();
    $keeping = languagesKeptOver($seal, $store);
    $keeping->choose(aStackLanguagesAreChosenOn(), Whose::member('ada'), TheirLanguages::of(HearIn::Dutch, ReadIn::English));
    $how === 'its keys went' ? $seal->losesItsKeys() : $store->holdsOneALaterBuildWrote($seal->stack(aStackLanguagesAreChosenOn()));

    expect($keeping->of(aStackLanguagesAreChosenOn(), Whose::member('ada')))->toEqual(TheirLanguages::asTheTitleComes())
        ->and($store->newest($seal->stack(aStackLanguagesAreChosenOn()))->holdsARow())->toBeFalse();
})->with(['its keys went', 'a later build wrote it']);

it('cannot write a choice for a member whose identifier is not valid text', function (): void {
    expect(whetherTheChoiceWasKept(languagesKeptOver(ASealInMemory::working(), ReadingsInMemory::empty())->choose(aStackLanguagesAreChosenOn(), Whose::member("\xB1\x31"), TheirLanguages::asTheTitleComes())))->toBe('not kept');
});

it('changes one language at a time for whoever is signed in to the stack, and nothing where nobody is', function (): void {
    $signedIn = AKeychainInMemory::working();
    $signedIn->keep(aStackLanguagesAreChosenOn(), Session::of('a-session'), Whose::member('ada'));
    $keeping = languagesKeptOver(ASealInMemory::working(), ReadingsInMemory::empty(), $signedIn);

    $keeping->hearIn(aStackLanguagesAreChosenOn(), HearIn::English);
    $keeping->readIn(aStackLanguagesAreChosenOn(), ReadIn::Dutch);
    $nobody = $keeping->hearIn(aStackLanguagesAreChosenOn('m'), HearIn::Dutch);

    expect($keeping->chosenOn(aStackLanguagesAreChosenOn()))->toEqual(TheirLanguages::of(HearIn::English, ReadIn::Dutch))
        ->and($keeping->of(aStackLanguagesAreChosenOn(), Whose::member('ada')))->toEqual(TheirLanguages::of(HearIn::English, ReadIn::Dutch))
        ->and(whetherTheChoiceWasKept($nobody))->toBe('not kept')
        ->and($keeping->chosenOn(aStackLanguagesAreChosenOn('m')))->toEqual(TheirLanguages::asTheTitleComes());
});
