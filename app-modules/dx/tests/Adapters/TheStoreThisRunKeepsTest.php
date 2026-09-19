<?php

declare(strict_types=1);

namespace Modules\Dx\Tests\Adapters;

use function expect;
use function it;

use Lemonfiber\Native\WasRead;
use Lemonfiber\Native\WhenAValueMayBeRead;
use Lemonfiber\Native\Wrote;
use Modules\Dx\Adapters\TheStoreThisRunKeeps;

use function sprintf;

// The store, held to the four things `Keeps` promises.
//
// Its own suite rather than only the round-trips the stand-ins exercise. Three
// adapters write here — the keychain, the stack list and the verdicts — and
// each of them reads the store's refusals to decide what a screen says, so a
// store that answered one of them wrongly would be a stand-in teaching a
// development build the wrong lesson about a real device.

/** Which of the three arms a read came back as, as a word a test can compare. */
function whatItAnswered(WasRead $read): string
{
    return $read->either(
        found: static fn(string $value): Answered => Answered::saying(sprintf('found:%s', $value)),
        nothing: static fn(): Answered => Answered::saying('nothing'),
        refused: static fn(): Answered => Answered::saying('refused'),
    )->word;
}

/** Whether a write was taken, and at which accessibility. */
function whatItWroteAt(Wrote $wrote): string
{
    return $wrote->either(
        done: static fn(WhenAValueMayBeRead $when): Answered => Answered::saying($when->value),
        refused: static fn(): Answered => Answered::saying('refused'),
    )->word;
}

/** A word carried out of an `either()` arm, which may only build an object. */
final readonly class Answered
{
    private function __construct(public string $word) {}

    public static function saying(string $word): self
    {
        return new self(word: $word);
    }
}

it('answers with what was written under a key', function (): void {
    $store = new TheStoreThisRunKeeps();
    $store->keep('a-key', 'a value', WhenAValueMayBeRead::WhileUnlocked);

    expect(whatItAnswered($store->read('a-key')))->toBe('found:a value');
});

it('answers that it holds nothing under a key nobody wrote', function (): void {
    // Nothing, and never a refusal. This store is always reachable, and
    // answering *the store could not be asked* would put a screen in front of
    // somebody for a condition that cannot arise here.
    $store = new TheStoreThisRunKeeps();

    expect(whatItAnswered($store->read('never-written')))->toBe('nothing');
});

it('forgets a key it is asked to forget', function (): void {
    $store = new TheStoreThisRunKeeps();
    $store->keep('a-key', 'a value', WhenAValueMayBeRead::WhileUnlocked);
    $store->forget('a-key');

    expect(whatItAnswered($store->read('a-key')))->toBe('nothing');
});

it('answers back the accessibility it was asked for', function (): void {
    // Nothing here locks, so there is no narrower promise to report having
    // fallen back to — which makes answering with what was asked for the
    // honest reply rather than a convenient one. A real Android store answers
    // the wider word to a caller that asked for the narrower.
    $store = new TheStoreThisRunKeeps();

    expect(whatItWroteAt($store->keep('a-key', 'a value', WhenAValueMayBeRead::WhileUnlocked)))
        ->toBe(WhenAValueMayBeRead::WhileUnlocked->value)
        ->and(whatItWroteAt($store->keep('b-key', 'a value', WhenAValueMayBeRead::AfterFirstUnlock)))
        ->toBe(WhenAValueMayBeRead::AfterFirstUnlock->value);
});

it('says there is somewhere to keep a value', function (): void {
    // The answer that lets a run reach the screens past pairing. A stand-in
    // answering otherwise would put every one of them behind a refusal.
    expect(new TheStoreThisRunKeeps()->canBeAsked())->toBeTrue();
});

it('keeps one key apart from another', function (): void {
    $store = new TheStoreThisRunKeeps();
    $store->keep('one', 'first', WhenAValueMayBeRead::WhileUnlocked);
    $store->keep('two', 'second', WhenAValueMayBeRead::WhileUnlocked);

    expect(whatItAnswered($store->read('one')))->toBe('found:first')
        ->and(whatItAnswered($store->read('two')))->toBe('found:second');
});
