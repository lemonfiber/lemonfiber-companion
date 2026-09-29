<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\KeyHeld;
use Modules\Kernel\Api\KeyMaterial;
use Modules\Kernel\Api\WhyNothingIsSealed;

use function sprintf;

/** Which of the three arms answered, and with what, as one word. */
function whatWasHeld(KeyHeld $held): string
{
    return $held->either(
        held: static fn(KeyMaterial $key): Code => Code::of(sprintf('held:%s', $key->bytes())),
        madeAfresh: static fn(KeyMaterial $key): Code => Code::of(sprintf('made:%s', $key->bytes())),
        refused: static fn(WhyNothingIsSealed $why): Code => Code::of(sprintf('refused:%s', $why->name)),
    )->shown();
}

it('hands the held arm the key that was there', function (): void {
    expect(whatWasHeld(KeyHeld::held(KeyMaterial::of('the-key-that-was-there-all-along'))))
        ->toBe('held:the-key-that-was-there-all-along');
});

it('hands the made-afresh arm the key made in its place, never the held arm', function (): void {
    // The two arms carry the same type and opposite news, so the arm is the
    // whole of the answer: a key made now read as held tells an owner that
    // what it kept still opens.
    expect(whatWasHeld(KeyHeld::madeAfresh(KeyMaterial::of('a-key-made-just-now-for-this-one'))))
        ->toBe('made:a-key-made-just-now-for-this-one');
});

it('hands the refused arm which refusal it was', function (): void {
    expect(whatWasHeld(KeyHeld::refused(WhyNothingIsSealed::NoSecureStorage)))->toBe('refused:NoSecureStorage')
        ->and(whatWasHeld(KeyHeld::refused(WhyNothingIsSealed::KeyUnreadable)))->toBe('refused:KeyUnreadable');
});
