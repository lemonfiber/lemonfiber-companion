<?php

declare(strict_types=1);

use Modules\Kernel\Api\ACertificateName;
use Modules\Kernel\Api\AskingHowItIsServed;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowItIsServed;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\TheCertificateItPresents;
use Modules\Kernel\Api\TheNamesItCovers;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\Servings;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatSaysHowItIsServed;
use Tests\Support\TheHealthScreenOfTheLoft;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;

// The AskingHowItIsServed contract, run against the adapter and against the fake.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** What both implementations answer with: encrypted on the network under a name, presenting a cut subject and one name of three. */
function theSameServing(): HowItIsServed
{
    return HowItIsServed::presenting(
        TheCertificateItPresents::said(
            Fingerprint::of(str_repeat('d', Fingerprint::CHARACTERS)),
            ACertificateName::said('CN=loft\x07', cut: true),
            ACertificateName::said('CN=loft\x07', cut: false),
            selfSigned: true,
            names: TheNamesItCovers::these(2, ACertificateName::said('loft.home.arpa', cut: false)),
            from: Instant::atEpochSeconds(1_700_000_000),
            until: Instant::atEpochSeconds(1_900_000_000),
        ),
        8443,
        network: true,
        named: 'loft.home.arpa',
        unrecorded: 'read-only',
    );
}

/**
 * The payload a stack sends for that serving, with the fields a test changes and those it leaves out.
 *
 * @param  array<mixed>         $changed
 * @param  array<mixed>         $certificate
 * @return array<string, mixed>
 */
function whatAStackSaysOfServing(array $changed = [], array $certificate = [], string ...$leftOut): array
{
    $data = [
        'port' => 8443,
        'encrypted' => true,
        'network' => true,
        'named' => 'loft.home.arpa',
        'unrecorded' => 'read-only',
        'certificate' => [
            'fingerprint' => str_repeat('d', Fingerprint::CHARACTERS),
            'subject' => 'CN=loft\x07',
            'subject_cut' => true,
            'issuer' => 'CN=loft\x07',
            'issuer_cut' => false,
            'self_signed' => true,
            'names' => ['loft.home.arpa'],
            'names_more' => 2,
            'valid_from' => 1_700_000_000,
            'valid_until' => 1_900_000_000,
            ...$certificate,
        ],
        ...$changed,
    ];

    foreach ($leftOut as $field) {
        unset($data[$field]);
    }

    return ['api_version' => 1, 'kind' => 'served', 'data' => $data];
}

/**
 * Both ways of asking, each set up to produce the same answer.
 *
 * @return array<string, Closure(): AskingHowItIsServed>
 */
function everyWayOfAskingHowItIsServed(MockResponse $answered, ?Obstacle $why = null): array
{
    return [
        'the fake' => static fn(): AskingHowItIsServed => $why instanceof Obstacle
            ? AStackThatSaysHowItIsServed::met($why)
            : AStackThatSaysHowItIsServed::with(theSameServing()),
        'the adapter' => static function () use ($answered): AskingHowItIsServed {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new Servings(new PinnedClients());
        },
    ];
}

/** Everything one way of asking comes away with, folded to text. */
function everythingTheStackSaysOfServing(AskingHowItIsServed $asking): string
{
    return $asking->served(TheHealthScreenOfTheLoft::theStackBeingLookedAt(), Session::of('a-session-not-a-secret'))->either(
        said: static fn(HowItIsServed $served): TheWordCarriedOut => new TheWordCarriedOut(sprintf(
            '%d %s %s "%s" "%s"; %s',
            $served->port(),
            var_export($served->isEncrypted(), return: true),
            var_export($served->isOnTheNetwork(), return: true),
            $served->named(),
            $served->unrecorded(),
            $served->whatItPresents(
                presenting: static fn(TheCertificateItPresents $certificate): TheWordCarriedOut => new TheWordCarriedOut(everythingACertificateSays($certificate)),
                plainly: static fn(): TheWordCarriedOut => new TheWordCarriedOut('no certificate'),
            )->said,
        )),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->value),
    )->said;
}

/** What a certificate says, folded to text. */
function everythingACertificateSays(TheCertificateItPresents $certificate): string
{
    return sprintf(
        '%s; subject "%s" cut %s; issuer "%s" cut %s; self-signed %s; names %s and %d more; %d to %d',
        $certificate->fingerprint()->forComparingByEye(),
        $certificate->subject()->text(),
        var_export($certificate->subject()->isCut(), return: true),
        $certificate->issuer()->text(),
        var_export($certificate->issuer()->isCut(), return: true),
        var_export($certificate->isSelfSigned(), return: true),
        implode(',', array_map(static fn(ACertificateName $name): string => $name->isCut() ? sprintf('%s (cut)', $name->text()) : $name->text(), iterator_to_array($certificate->names(), preserve_keys: false))),
        $certificate->names()->more(),
        $certificate->validFrom()->epochSeconds(),
        $certificate->validUntil()->epochSeconds(),
    );
}

it('comes away with where it is served and every field of the certificate, as the stack wrote it out', function (): void {
    $answered = MockResponse::make((string) json_encode(whatAStackSaysOfServing()));
    $said = [];

    foreach (everyWayOfAskingHowItIsServed($answered) as $which => $make) {
        $said[$which] = everythingTheStackSaysOfServing($make());
    }

    expect($said['the adapter'])->toBe($said['the fake'])
        ->and($said['the fake'])->toBe(sprintf(
            '8443 true true "loft.home.arpa" "read-only"; %s; subject "CN=loft\x07" cut true; issuer "CN=loft\x07" cut false; self-signed true; names loft.home.arpa and 2 more; 1700000000 to 1900000000',
            str_repeat('d', Fingerprint::CHARACTERS),
        ));
});

it('reads a plain surface as presenting no certificate, and a name and a reason left out as none', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysOfServing(['encrypted' => false, 'network' => false, 'named' => null], [], 'certificate', 'unrecorded')))]);

    expect(everythingTheStackSaysOfServing(new Servings(new PinnedClients())))->toBe('8443 false false "" ""; no certificate');
});

it('tells a session that has ended from a stack that is not answering', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::of(KindOfObstacle::CredentialWasRefused)],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
        [MockResponse::make('not json at all'), Obstacle::of(KindOfObstacle::AnswerCouldNotBeRead)],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfAskingHowItIsServed($answered, $why) as $which => $make) {
            expect(everythingTheStackSaysOfServing($make()))->toBe($why->kind()->value, sprintf('%s / %s', $which, $why->kind()->value));
        }
    }
});

// `served` is stood in for and not judged: each case is a payload the contract does not describe, or a promise the stack breaks.
it('refuses what it cannot read, and a certificate text still holding a control character', function (array $changed, array $certificate): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysOfServing($changed, $certificate)))]);

    expect(everythingTheStackSaysOfServing(new Servings(new PinnedClients())))->toBe(KindOfObstacle::AnswerCouldNotBeRead->value);
})->with([
    'no port' => [['port' => null], []],
    'a port below nothing' => [['port' => -1], []],
    'no encrypted' => [['encrypted' => 'yes'], []],
    'no network' => [['network' => null], []],
    'a name that is not text' => [['named' => 7], []],
    'a reason that is not text' => [['unrecorded' => false], []],
    'encrypted with no certificate' => [['certificate' => null], []],
    'no fingerprint' => [[], ['fingerprint' => null]],
    'a fingerprint that is not one' => [[], ['fingerprint' => 'abc']],
    'no subject' => [[], ['subject' => null]],
    'no subject cut' => [[], ['subject_cut' => null]],
    'no issuer cut' => [[], ['issuer_cut' => 'no']],
    'no self signed' => [[], ['self_signed' => null]],
    'no names' => [[], ['names' => null]],
    'a covered name that is not text' => [[], ['names' => [7]]],
    'no names left out' => [[], ['names_more' => -1]],
    'no valid from' => [[], ['valid_from' => '2026-01-01T00:00:00Z']],
    'no valid until' => [[], ['valid_until' => null]],
    'an escape in the subject' => [[], ['subject' => "CN=loft\e[31m"]],
    'a C1 control in the issuer' => [[], ['issuer' => "CN=\u{009B}31m"]],
    'a line break in a name' => [[], ['names' => ["loft\nevil"]]],
]);

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('ServedEnvelope', whatAStackSaysOfServing()))->toBe([])
        ->and(WhatTheContractAccepts::complaintsAbout('ServedEnvelope', whatAStackSaysOfServing(['encrypted' => false, 'named' => null], [], 'certificate', 'unrecorded')))->toBe([]);
});
