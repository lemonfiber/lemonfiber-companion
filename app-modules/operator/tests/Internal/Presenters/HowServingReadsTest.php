<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal\Presenters;

use function expect;
use function it;

use Modules\Kernel\Api\ACertificateName;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowItIsServed;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheCertificateItPresents;
use Modules\Kernel\Api\TheNamesItCovers;
use Modules\Operator\Internal\Presenters\HowServingReads;

use function str_repeat;

it('says nothing of how it is served where the reading did not come back', function (): void {
    foreach ([new HowServingReads()->signedOut(), new HowServingReads()->met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))] as $shown) {
        expect($shown->went->cameBack())->toBeFalse()
            ->and([$shown->port, $shown->encrypted, $shown->network, $shown->named, $shown->unrecorded])->toBe(['', '', '', '', ''])
            ->and($shown->certificate)->toBeNull();
    }
});

it('marks an issuer cut short only where the certificate was signed by another', function (bool $selfSigned, string $issuer, bool $issuerCut): void {
    $fingerprint = Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS));
    $certificate = TheCertificateItPresents::said(
        $fingerprint,
        ACertificateName::said('CN=loft', cut: false),
        ACertificateName::said('CN=House CA', cut: true),
        $selfSigned,
        TheNamesItCovers::these(0),
        Instant::atEpochSeconds(1_700_000_000),
        Instant::atEpochSeconds(1_900_000_000),
    );

    $shown = new HowServingReads()->this(HowItIsServed::presenting($certificate, 8443, network: false, named: '', unrecorded: ''), $fingerprint, Instant::atEpochSeconds(1_800_000_000))->certificate;

    expect($shown?->issuer)->toBe($issuer)
        ->and($shown?->issuerCut)->toBe($issuerCut);
})->with([
    'signed by another' => [false, 'CN=House CA', true],
    'signed by itself' => [true, '', false],
]);
