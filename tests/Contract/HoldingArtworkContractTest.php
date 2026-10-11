<?php

declare(strict_types=1);

use Bootstrap\Composition\ArtworkGoesWithTheSession;
use Lemonfiber\Native\Artwork as TheArtworkHeld;
use Modules\Device\Api\PlatformArtwork;
use Modules\Kernel\Api\Artwork;
use Modules\Kernel\Api\ArtworkKey;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\HoldingArtwork;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\WhichPicture;
use Modules\Kernel\Api\Whose;
use Native\Mobile\Testing\FakeBridge;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\ArtworkHeldInMemory;

// The HoldingArtwork contract, run against the adapter and against the fake.
//
// The adapter arm runs over a bridge scripted into `FakeBridge`, which goes
// through the function names from the manifest, the JSON out and the decoding
// of the answer. Each arm is scripted to answer the way the device would after
// the calls the test makes, so what is compared is the reading of each answer.

beforeEach(function (): void {
    FakeBridge::disable();
});

function theStackArtworkIsHeldFor(): StackId
{
    return StackId::of(Nonce::of(str_repeat('h', Nonce::SHORTEST)));
}

function aPostersKey(): ArtworkKey
{
    return ArtworkKey::of(theStackArtworkIsHeldFor(), WhichPicture::HeldPoster, 'a1');
}

/**
 * Both ways of holding artwork, each set up to answer as a device that holds, or one that refuses.
 *
 * @return array<string, Closure(): HoldingArtwork>
 */
function everyWayOfHoldingArtwork(bool $holding): array
{
    return [
        'the fake' => static fn(): HoldingArtwork => $holding ? ArtworkHeldInMemory::working() : ArtworkHeldInMemory::refusing(),
        'the adapter' => static function () use ($holding): HoldingArtwork {
            FakeBridge::disable();
            FakeBridge::enable()
                ->respondTo('Lemonfiber.Artwork.Hold', $holding ? ['outcome' => 'in-memory'] : ['outcome' => 'refused', 'because' => 'not-a-picture'])
                ->respondTo('Lemonfiber.Artwork.Holds', ['outcome' => 'in-memory', 'holds' => $holding])
                ->respondTo('Lemonfiber.Artwork.LetGo', ['outcome' => 'let-go']);

            return new PlatformArtwork(new TheArtworkHeld());
        },
    ];
}

it('holds a picture under its key and says it holds it', function (): void {
    foreach (everyWayOfHoldingArtwork(holding: true) as $which => $make) {
        $held = $make();

        expect($held->hold(aPostersKey(), theStackArtworkIsHeldFor(), Artwork::of('a picture')))->toBeTrue($which)
            ->and($held->holds(aPostersKey()))->toBeTrue($which)
            ->and($held->letGoOf(theStackArtworkIsHeldFor()))->toBeTrue($which);
    }
});

it('says a picture the device would not hold is not held', function (): void {
    foreach (everyWayOfHoldingArtwork(holding: false) as $which => $make) {
        $held = $make();

        expect($held->hold(aPostersKey(), theStackArtworkIsHeldFor(), Artwork::of('a picture')))->toBeFalse($which)
            ->and($held->holds(aPostersKey()))->toBeFalse($which);
    }
});

it('hands the device the key, the stack and the bytes, and nothing else', function (): void {
    $bridge = FakeBridge::enable()->respondTo('Lemonfiber.Artwork.Hold', ['outcome' => 'in-memory']);

    new PlatformArtwork(new TheArtworkHeld())->hold(aPostersKey(), theStackArtworkIsHeldFor(), Artwork::of('a picture'));

    $bridge->assertCalled('Lemonfiber.Artwork.Hold', static fn(array $sent): bool => $sent === [
        'key' => aPostersKey()->said(),
        'stack' => theStackArtworkIsHeldFor()->stored(),
        'bytes' => 'YSBwaWN0dXJl',
    ]);
});

it('names a picture by an opaque key that tells two pictures apart and says nothing of the title', function (): void {
    $key = aPostersKey()->said();

    expect($key)->toStartWith(ArtworkKey::SCHEME)
        ->and($key)->not->toContain('a1')
        ->and($key)->toMatch('/^lemonfiber-art:[0-9a-f]{64}$/')
        ->and(ArtworkKey::of(theStackArtworkIsHeldFor(), WhichPicture::AskablePoster, 'a1')->said())->not->toBe($key)
        ->and(ArtworkKey::of(theStackArtworkIsHeldFor(), WhichPicture::HeldPoster, 'a1')->said())->toBe($key);
});

it('lets go of a stack\'s pictures whenever its session is kept, forgotten or the stack removed, and of no other stack\'s', function (): void {
    $other = StackId::of(Nonce::of(str_repeat('o', Nonce::SHORTEST)));
    $held = ArtworkHeldInMemory::working();
    $store = new ArtworkGoesWithTheSession(AKeychainInMemory::working(), $held);
    $holdBoth = static function () use ($held, $other): void {
        $held->hold(aPostersKey(), theStackArtworkIsHeldFor(), Artwork::of('a picture'));
        $held->hold(ArtworkKey::of($other, WhichPicture::HeldPoster, 'a1'), $other, Artwork::of('a picture'));
    };

    $holdBoth();
    $store->keep(theStackArtworkIsHeldFor(), Session::of('a-session-not-a-secret'), Whose::member('robin'));
    $afterKeep = $held->howMany();
    $holdBoth();
    $store->forget(theStackArtworkIsHeldFor());
    $afterForget = $held->howMany();
    $holdBoth();
    $store->forgetTheStack(theStackArtworkIsHeldFor());

    expect([$afterKeep, $afterForget, $held->howMany()])->toBe([1, 1, 1])
        ->and($held->holds(aPostersKey()))->toBeFalse()
        ->and($store->resume(theStackArtworkIsHeldFor())->either(held: static fn(): Code => Code::of('held'), notHeld: static fn(): Code => Code::of('nothing'))->shown())->toBe('nothing');
});
