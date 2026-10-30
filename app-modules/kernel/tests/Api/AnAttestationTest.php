<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function implode;
use function it;

use Modules\Kernel\Api\AnAttestation;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

/** Which platform an attestation answered for, and what it carried there. */
function whatTheAttestationCarries(AnAttestation $attestation): string
{
    return $attestation->either(
        android: static fn(array $chain): TheWordCarriedOut => new TheWordCarriedOut(sprintf('android:%s', implode(',', $chain))),
        apple: static fn(string $object): TheWordCarriedOut => new TheWordCarriedOut(sprintf('apple:%s', $object)),
    )->said;
}

it('carries Android\'s chain whole and in order, the key\'s own certificate first, and Apple\'s object as written', function (): void {
    expect(whatTheAttestationCarries(AnAttestation::fromAndroid('leaf', 'intermediate', 'root')))->toBe('android:leaf,intermediate,root')
        ->and(whatTheAttestationCarries(AnAttestation::fromApple('an-attestation-object')))->toBe('apple:an-attestation-object');
});
