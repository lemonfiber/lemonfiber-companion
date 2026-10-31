<?php

declare(strict_types=1);

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\AnInternalCertificate;
use Modules\Kernel\Api\TheCredentialsHeld;
use Modules\Kernel\Api\WhatAReplacementCameTo;
use Modules\Sdk\Api\CredentialsIsUnreadable;
use Modules\Sdk\Api\CredentialsKept;
use Tests\Support\WhatTheContractAccepts;

/** The SHA-256 of the internal certificate in force, as the stack lists it. */
const THE_CERTIFICATE_IN_FORCE = 'ab12000000000000000000000000000000000000000000000000000000000000';

/**
 * The media server's internal certificate as the credentials read lists it, with one change.
 *
 * @param  array<string, mixed> $with
 * @return array<string, mixed>
 */
function theInternalCertificateListed(array $with = []): array
{
    return [
        'name' => 'Media server internal certificate',
        'state' => 'active',
        'origin' => 'lemonfiber',
        'consumers' => ['the decline service', 'the request gate'],
        'from' => ['origin' => 'bundled'],
        'location' => '/srv/jellyfin/internal.p12',
        'setting' => AnInternalCertificate::SETTING,
        'fingerprint' => THE_CERTIFICATE_IN_FORCE,
        ...$with,
    ];
}

/**
 * A `credentials` payload holding these, answering a replacement with `$rotated` where one is given.
 *
 * @param  list<array<string, mixed>> $held
 * @param  array<mixed>|null           $rotated
 * @return array<string, mixed>
 */
function aPayloadAboutTheCertificate(array $held, ?array $rotated = null): array
{
    return [
        'rehearsed' => false,
        'held' => $held,
        'grants' => [],
        'protection' => ['summary' => 'Plain files', 'against' => [], 'not_against' => []],
        ...($rotated === null ? [] : ['rotated' => $rotated]),
    ];
}

/**
 * What the stack answers a reading of replacing the certificate with, with one change to its `rotated`.
 *
 * @param  array<string, mixed> $with
 * @return array<string, mixed>
 */
function aReplacementRead(array $with = []): array
{
    return [
        'credential' => 'Media server internal certificate',
        'consumers' => [],
        'offer' => 'an-offer',
        'settled' => ['settled' => 'rehearsed', 'detail' => 'the decline service and the request gate are handed the new SHA-256', 'location' => '/srv/jellyfin/internal.p12', 'afterwards' => []],
        ...$with,
    ];
}

/**
 * @param  array<string, mixed> $data
 * @return Envelope<mixed>
 */
function credentialsAboutTheCertificate(array $data): Envelope
{
    return new Envelope(1, 'credentials', $data);
}

/**
 * What a replacement answer says, as one line: where it settled, what was said, its offer and each holder.
 *
 * @param array<mixed> $rotated
 */
function theReplacementRead(array $rotated): string
{
    $came = CredentialsKept::afterAReplacement(credentialsAboutTheCertificate(aPayloadAboutTheCertificate([theInternalCertificateListed()], $rotated)));
    $replacement = $came->replacement();
    $holders = [];

    foreach ($replacement->holders() as $holder) {
        $holders[] = sprintf('%s=%s(%s)', $holder->holder(), $holder->reach()->value, $holder->detail());
    }

    return sprintf('%s|%s|%s|%s', $replacement->settledAt()->value, $replacement->said(), $replacement->agreement(), implode(',', $holders));
}

/** The certificate in force each credential carries, by setting, as lines. */
function theCertificatesInForce(TheCredentialsHeld $held): string
{
    $lines = [];

    foreach ($held as $credential) {
        $lines[] = sprintf('%s:%s', $credential->setting(), $credential->certificateInForce()->wasSaid() ? $credential->certificateInForce()->atAGlance() : '-');
    }

    return implode("\n", $lines);
}

it('stands in for a stack with a certificate and a replacement the contract would accept', function (array $rotated): void {
    expect(WhatTheContractAccepts::complaintsAbout('CredentialsEnvelope', ['api_version' => 1, 'kind' => 'credentials', 'data' => aPayloadAboutTheCertificate([theInternalCertificateListed()], $rotated)]))->toBe([]);
})->with([
    'a reading' => [aReplacementRead()],
    'replaced' => [aReplacementRead(['offer' => null, 'settled' => ['settled' => 'replaced', 'observed' => 'jellyfin serves the new certificate'], 'consumers' => [['consumer' => 'the decline service', 'reach' => ['reach' => 'updated']], ['consumer' => 'the request gate', 'reach' => ['reach' => 'pending', 'detail' => 'restart the request gate']]]])],
    'unknown' => [aReplacementRead(['offer' => null, 'settled' => ['settled' => 'unknown', 'known' => []]])],
]);



it('reads what replacing the certificate would do, under the offer a yes quotes, and a reading that names none', function (): void {
    expect(theReplacementRead(aReplacementRead()))->toBe('rehearsed|the decline service and the request gate are handed the new SHA-256|an-offer|')
        ->and(theReplacementRead(aReplacementRead(['offer' => null])))->toBe('rehearsed|the decline service and the request gate are handed the new SHA-256||');
});

it('reads where a replacement settled, in the stack\'s words, with how far it reached each holder', function (array $settled, string $read): void {
    $holders = [['consumer' => 'the decline service', 'reach' => ['reach' => 'updated']], ['consumer' => 'the request gate', 'reach' => ['reach' => 'failed', 'detail' => 'it did not answer']]];

    expect(theReplacementRead(aReplacementRead(['offer' => null, 'settled' => $settled, 'consumers' => $holders])))->toBe($read);
})->with([
    'replaced' => [['settled' => 'replaced', 'observed' => 'jellyfin serves it'], 'replaced|jellyfin serves it||the decline service=updated(),the request gate=failed(it did not answer)'],
    'replaced, unproven' => [['settled' => 'replaced-unproven', 'detail' => 'the old one is kept'], 'replaced-unproven|the old one is kept||the decline service=updated(),the request gate=failed(it did not answer)'],
    'unproven' => [['settled' => 'unproven', 'detail' => 'it could not be read'], 'unproven|it could not be read||the decline service=updated(),the request gate=failed(it did not answer)'],
    'refused' => [['settled' => 'refused', 'detail' => 'no'], 'refused|no||the decline service=updated(),the request gate=failed(it did not answer)'],
    'unknown' => [['settled' => 'unknown', 'known' => ['indexer.api_key']], 'unknown|||the decline service=updated(),the request gate=failed(it did not answer)'],
    'elsewhere' => [['settled' => 'elsewhere', 'detail' => 'from the provider'], 'elsewhere|from the provider||the decline service=updated(),the request gate=failed(it did not answer)'],
]);

it('reads every credential as it stands after a replacement', function (): void {
    $came = CredentialsKept::afterAReplacement(credentialsAboutTheCertificate(aPayloadAboutTheCertificate([theInternalCertificateListed()], aReplacementRead())));

    expect($came)->toBeInstanceOf(WhatAReplacementCameTo::class)
        ->and(theCertificatesInForce($came->held()))->toBe(sprintf('internal-certificate:%s', AnInternalCertificate::of(THE_CERTIFICATE_IN_FORCE)->atAGlance()->shown()));
});

it('refuses a replacement that is missing or not what the contract says', function (?array $rotated, string $said): void {
    expect(fn(): WhatAReplacementCameTo => CredentialsKept::afterAReplacement(credentialsAboutTheCertificate([...aPayloadAboutTheCertificate([]), 'rotated' => $rotated])))
        ->toThrow(CredentialsIsUnreadable::class, $said);
})->with([
    'no replacement' => [null, 'no readable `rotated`'],
    'no word of where it settled' => [aReplacementRead(['settled' => null]), '`rotated` in the credentials envelope has no readable `settled`'],
    'a settling with no word' => [aReplacementRead(['settled' => ['detail' => 'x']]), 'no readable `settled`'],
    'a word it has no case for' => [aReplacementRead(['settled' => ['settled' => 'half-done']]), 'says `settled` is `half-done`'],
    'a reading saying nothing' => [aReplacementRead(['settled' => ['settled' => 'rehearsed', 'detail' => ' ']]), 'no readable `detail`'],
    'a landed one observing nothing' => [aReplacementRead(['settled' => ['settled' => 'replaced']]), 'no readable `observed`'],
    'an offer that is not text' => [aReplacementRead(['offer' => 7]), 'no readable `offer`'],
    'no holders' => [aReplacementRead(['settled' => ['settled' => 'refused', 'detail' => 'no'], 'consumers' => null]), 'no readable `consumers`'],
    'a holder that is not one' => [aReplacementRead(['settled' => ['settled' => 'refused', 'detail' => 'no'], 'consumers' => ['the request gate']]), 'no readable `consumers`'],
    'a holder with no reach' => [aReplacementRead(['settled' => ['settled' => 'refused', 'detail' => 'no'], 'consumers' => [['consumer' => 'the request gate']]]), 'no readable `reach`'],
    'a holder reached a way it has no case for' => [aReplacementRead(['settled' => ['settled' => 'refused', 'detail' => 'no'], 'consumers' => [['consumer' => 'the request gate', 'reach' => ['reach' => 'nearly']]]]), 'says `reach` is `nearly`'],
    'a holder with no name' => [aReplacementRead(['settled' => ['settled' => 'refused', 'detail' => 'no'], 'consumers' => [['consumer' => ' ', 'reach' => ['reach' => 'updated']]]]), 'no readable `consumer`'],
    'a pending holder saying nothing of what is owed' => [aReplacementRead(['settled' => ['settled' => 'refused', 'detail' => 'no'], 'consumers' => [['consumer' => 'the request gate', 'reach' => ['reach' => 'pending']]]]), 'no readable `detail`'],
    'a pending holder with a blank detail' => [aReplacementRead(['settled' => ['settled' => 'refused', 'detail' => 'no'], 'consumers' => [['consumer' => 'the request gate', 'reach' => ['reach' => 'pending', 'detail' => ' ']]]]), 'no readable `detail`'],
]);
