<?php

declare(strict_types=1);

namespace Modules\Connection\Tests\Api;

use function expect;
use function it;
use function json_encode;

use Modules\Connection\Api\FingerprintWasConfirmed;
use Modules\Connection\Api\WhatTheCodeSaysSoFar;
use Modules\Connection\Api\WhereTheCodeGot;
use Modules\Kernel\Api\AtAGlance;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowItWasRead;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Pairing;

use function sprintf;
use function str_repeat;

use Tests\Support\Fakes\FrozenClock;

/** The moment every code in this file is read at. */
const NOW = 1_000;

/** Something to hand a closure that has to answer with an object. */
final readonly class Answered
{
    public function __construct(public string $with) {}
}

/** A clock stopped at the moment above. */
function stopped(): Clock
{
    return FrozenClock::at(Instant::atEpochSeconds(NOW));
}

/** A digest made of one repeated character. */
function digestOf(string $character): string
{
    return str_repeat($character, Fingerprint::CHARACTERS);
}

/** Pairing material, with whatever a test needs to break about it. */
function aCode(mixed $at = 'https://192.168.1.42', mixed $digest = null, mixed $expires = 2_000): string
{
    return (string) json_encode([
        'address' => $at,
        'fingerprint' => $digest ?? digestOf('a'),
        'expires' => $expires,
    ]);
}

/** Material carrying the digest a test names, read at a moment it is still good. */
function parsed(string $digest): Pairing
{
    return Pairing::read(aCode(digest: $digest), HowItWasRead::Typed, stopped());
}

it('says nothing is wrong with a field nobody has typed in', function (): void {
    // The state a screen opens in, and the one it returns to every time somebody
    // clears the box to start over. "That code is not readable" is a true
    // sentence about an empty string and a hostile one to show.
    expect(WhatTheCodeSaysSoFar::read('', HowItWasRead::Typed, stopped())->got())
        ->toBe(WhereTheCodeGot::Waiting);
});

it('treats a field holding only spaces as one nobody has typed in', function (): void {
    // A phone's keyboard puts a space in on its own, and the operator who tapped
    // it has not made a mistake worth a sentence.
    expect(WhatTheCodeSaysSoFar::read('   ', HowItWasRead::Typed, stopped())->got())
        ->toBe(WhereTheCodeGot::Waiting);
});

it('refuses something that is not pairing material at all', function (): void {
    expect(WhatTheCodeSaysSoFar::read('not a code', HowItWasRead::Typed, stopped())->got())
        ->toBe(WhereTheCodeGot::Unreadable);
});

it('refuses material whose address has no scheme', function (): void {
    // The refusal comes from Address rather than from Pairing, and lands in the
    // same place: every step along this road raises the same family, and a
    // screen owes one sentence for all of them.
    expect(WhatTheCodeSaysSoFar::read(aCode(at: '192.168.1.42'), HowItWasRead::Typed, stopped())->got())
        ->toBe(WhereTheCodeGot::Unreadable);
});

it('refuses material whose fingerprint is the wrong length', function (): void {
    expect(WhatTheCodeSaysSoFar::read(aCode(digest: 'abc'), HowItWasRead::Typed, stopped())->got())
        ->toBe(WhereTheCodeGot::Unreadable);
});

it('tells an expired code apart from an unreadable one', function (): void {
    // N1-R49. The two remedies are opposite: checking the characters is wasted
    // effort on a code that was typed perfectly, and the only way forward is a
    // new code from the stack.
    expect(WhatTheCodeSaysSoFar::read(aCode(expires: NOW), HowItWasRead::Typed, stopped())->got())
        ->toBe(WhereTheCodeGot::Expired);
});

it('reads a scanned code by the same road as a typed one', function (): void {
    // Both routes carry the same payload and are held to the same rules — a code
    // read by camera is not more trusted than one read by a person.
    expect(WhatTheCodeSaysSoFar::read(aCode(), HowItWasRead::Scanned, stopped())->got())
        ->toBe(WhereTheCodeGot::Comparing);
});

it('shows the operator a form derived from the fingerprint it read', function (): void {
    // N1-R51 — short enough to check at a glance, and derived from the whole
    // fingerprint. The point of asserting the exact string is that it is the one
    // the stack's own screen has to be producing.
    $shown = WhatTheCodeSaysSoFar::comparing(parsed(digestOf('a')))->toCompare();

    expect($shown)->toBe(AtAGlance::of(Fingerprint::of(digestOf('a')))->shown());
});

it('has nothing to compare where the code did not parse', function (): void {
    // Deliberately empty rather than a placeholder: there is no fingerprint in a
    // code that did not parse, and a placeholder is a string somebody could
    // confirm.
    expect(WhatTheCodeSaysSoFar::waiting()->toCompare())->toBe('')
        ->and(WhatTheCodeSaysSoFar::unreadable()->toCompare())->toBe('')
        ->and(WhatTheCodeSaysSoFar::expired()->toCompare())->toBe('');
});

it('hands the confirmation and the material it is about across together', function (): void {
    // The mistake this shape removes: a caller holding a confirmation and
    // pairing something else with it. There is no way to receive one without
    // the other.
    $said = parsed(digestOf('b'));

    $answered = WhatTheCodeSaysSoFar::comparing($said)->confirmedByTheOperator(
        confirmed: static fn(Pairing $about, FingerprintWasConfirmed $by): Answered => new Answered(
            $about === $said && $by->covers(AtAGlance::of(Fingerprint::of(digestOf('b')))) ? 'both' : 'wrong',
        ),
        notYet: static fn(): Answered => new Answered('nothing'),
    );

    expect($answered->with)->toBe('both');
});

it('makes no confirmation about a code that never reached the comparison', function (): void {
    // N1-R50's "must not proceed on an unconfirmed fingerprint", from the side
    // that makes it hard to arrange: there was no form on the screen, so there
    // is nothing the operator could have compared.
    //
    // The confirming arm names what it was handed and uses neither, which is
    // the assertion: it must not be called at all, and a closure taking no
    // parameters would not prove that it was offered the right ones.
    $answered = WhatTheCodeSaysSoFar::unreadable()->confirmedByTheOperator(
        confirmed: static fn(Pairing $about, FingerprintWasConfirmed $by): Answered => new Answered(sprintf(
            'made one about %s, covered: %s',
            $about->at()->forTheClient(),
            $by->covers(AtAGlance::of($about->presenting())) ? 'yes' : 'no',
        )),
        notYet: static fn(): Answered => new Answered('nothing'),
    );

    expect($answered->with)->toBe('nothing');
});
