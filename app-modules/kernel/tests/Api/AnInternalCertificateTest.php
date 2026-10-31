<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AnInternalCertificate;
use Modules\Kernel\Api\AtAGlance;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\FingerprintIsNotAFingerprint;
use Modules\Kernel\Api\TheCertificateInForce;

use function str_repeat;

/** A SHA-256 made of one repeated character. */
function aDigestOf(string $character): string
{
    return str_repeat($character, Fingerprint::CHARACTERS);
}

it('shows the certificate in the form a person compares at a glance, and nothing more', function (): void {
    expect(AnInternalCertificate::of(aDigestOf('a'))->atAGlance()->is(AtAGlance::of(Fingerprint::of(aDigestOf('a')))))->toBeTrue()
        ->and(AnInternalCertificate::of(aDigestOf('A'))->is(AnInternalCertificate::of(aDigestOf('a'))))->toBeTrue()
        ->and(AnInternalCertificate::of(aDigestOf('a'))->is(AnInternalCertificate::of(aDigestOf('b'))))->toBeFalse();
});

it('refuses a digest that is not a SHA-256', function (string $said): void {
    expect(fn(): AnInternalCertificate => AnInternalCertificate::of($said))->toThrow(FingerprintIsNotAFingerprint::class);
})->with(['too short' => ['ab12'], 'not hexadecimal' => [str_repeat('z', Fingerprint::CHARACTERS)]]);

it('is said or unsaid, and shows nothing where it is unsaid', function (): void {
    $certificate = AnInternalCertificate::of(aDigestOf('a'));

    expect([TheCertificateInForce::of($certificate)->wasSaid(), TheCertificateInForce::of($certificate)->atAGlance(), TheCertificateInForce::of($certificate)->is($certificate)])
        ->toBe([true, $certificate->atAGlance()->shown(), true])
        ->and(TheCertificateInForce::of($certificate)->is(AnInternalCertificate::of(aDigestOf('b'))))->toBeFalse()
        ->and([TheCertificateInForce::unsaid()->wasSaid(), TheCertificateInForce::unsaid()->atAGlance(), TheCertificateInForce::unsaid()->is($certificate)])->toBe([false, '', false]);
});
