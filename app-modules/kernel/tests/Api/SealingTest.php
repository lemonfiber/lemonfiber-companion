<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\Sealing;
use Modules\Kernel\Api\WhyNothingIsSealed;

use function sprintf;

/** Which arm a sealing answered on, and what it carried, as one word. */
function whatSealingSaid(Sealing $sealing): string
{
    return $sealing->either(
        sealed: static fn(SealedPayload $payload): Code => Code::of(sprintf('sealed:%s', $payload->forTheStore())),
        refused: static fn(WhyNothingIsSealed $why): Code => Code::of(sprintf('refused:%s', $why->name)),
    )->shown();
}

it('hands the sealed arm the payload it was built with', function (): void {
    expect(whatSealingSaid(Sealing::sealed(SealedPayload::of('a-payload'))))->toBe('sealed:a-payload');
});

it('hands the refused arm which refusal it was', function (): void {
    expect(whatSealingSaid(Sealing::refused(WhyNothingIsSealed::NoSecureStorage)))->toBe('refused:NoSecureStorage')
        ->and(whatSealingSaid(Sealing::refused(WhyNothingIsSealed::KeyUnreadable)))->toBe('refused:KeyUnreadable');
});
