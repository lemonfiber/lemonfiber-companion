<?php

declare(strict_types=1);

use Lemonfiber\Sdk\Http\ActionRequest;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AFormatChoiceMade;
use Modules\Kernel\Api\AFormatInForce;
use Modules\Kernel\Api\AHeldChoice;
use Modules\Kernel\Api\APresetInForce;
use Modules\Kernel\Api\APresetToChoose;
use Modules\Kernel\Api\ChoosingQuality;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\ThePresetsInForce;
use Modules\Kernel\Api\TheQualityChosen;
use Modules\Kernel\Api\WhatBecameOfTheChoice;
use Modules\Kernel\Api\WhatMusicIsSetTo;
use Modules\Kernel\Api\WhatTheChoiceCameTo;
use Modules\Sdk\Api\Graders;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatChoosesQuality;
use Tests\Support\WhatTheContractAccepts;

// The ChoosingQuality contract, run against the adapter and against the fake.
//
// `G2`'s shape. What both must keep is that choosing and confirming are two
// calls, and that what came back is the stack's whole answer: every preset in
// force with what an hour of it costs, music in its own terms, and what became
// of the choice — a held one and a rehearsed one each told apart from one that
// was recorded.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The machine whose quality is chosen. */
function theMachineWhoseQualityIsChosen(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('q', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

function theSessionQualityIsChosenOn(): Session
{
    return Session::of('a-session-not-a-secret');
}

/**
 * One preset in force, as the wire carries it.
 *
 * @return array<string, mixed>
 */
function aPresetOnTheWire(string $scope = 'everything', string $preset = 'Balanced', bool $transcodes = false): array
{
    return [
        'scope' => $scope,
        'preset' => $preset,
        'means' => 'Looks right on a TV',
        'resolution' => '1080p, good encodes',
        'size_per_hour' => '~2 GB',
        'transcoding' => 'Plays directly on most devices',
        'needs_transcoding_here' => $transcodes,
    ];
}

/**
 * The music format in force, as the wire carries it.
 *
 * @return array<string, string>
 */
function aFormatOnTheWire(): array
{
    return [
        'scope' => 'music',
        'format' => 'Lossless',
        'means' => 'CD quality, nothing thrown away',
        'targets' => 'FLAC',
        'size_per_hour' => '~300 MB',
        'note' => 'Some players need it converted',
    ];
}

/**
 * What a stack sends about the quality in force.
 *
 * @param  list<array<string, mixed>>|null  $choices
 * @param  array<string, string>|null  $music
 * @return array<string, mixed>
 */
function whatAStackSaysOfItsQuality(
    ?array $choices = null,
    ?array $music = null,
    string $disposition = 'shown',
    bool $customised = false,
): array {
    return [
        'api_version' => 1,
        'kind' => 'quality',
        'data' => [
            'choices' => $choices ?? [aPresetOnTheWire(), aPresetOnTheWire('movies', 'Maximum', transcodes: true)],
            'music' => $music,
            'disposition' => $disposition,
            'customised' => $customised,
        ],
    ];
}

/**
 * A quality answer with one field of its payload replaced.
 *
 * @return array<string, mixed>
 */
function aQualityAnswerWith(string $field, mixed $value): array
{
    $answer = whatAStackSaysOfItsQuality();
    $data = $answer['data'];
    $answer['data'] = [...(is_array($data) ? $data : []), $field => $value];

    return $answer;
}

/**
 * A quality answer with one field of its payload left out.
 *
 * @return array<string, mixed>
 */
function aQualityAnswerWithout(string $field): array
{
    $data = whatAStackSaysOfItsQuality()['data'];

    return [
        'api_version' => 1,
        'kind' => 'quality',
        'data' => array_diff_key(is_array($data) ? $data : [], [$field => true]),
    ];
}

/**
 * What a stack sends about a format chosen for music.
 *
 * @param  array<mixed>|null  $outcome
 * @return array<string, mixed>
 */
function whatAStackSaysOfAFormatChosen(?array $outcome = ['state' => 'started'], string $disposition = 'recorded'): array
{
    return [
        'api_version' => 1,
        'kind' => 'music',
        'data' => ['choice' => aFormatOnTheWire(), 'disposition' => $disposition, 'outcome' => $outcome],
    ];
}

/**
 * A music answer's payload, for replacing one field of it.
 *
 * @return array<string, mixed>
 */
function aFormatChoiceData(): array
{
    return ['choice' => aFormatOnTheWire(), 'disposition' => 'recorded', 'outcome' => ['state' => 'started']];
}

/** The quality a fake stands in with, the same as the adapter's answer above. */
function theQualityTheFakeHolds(): TheQualityChosen
{
    return TheQualityChosen::reported(
        ThePresetsInForce::of(
            APresetInForce::reported('everything', 'Balanced', 'Looks right on a TV', '1080p, good encodes', '~2 GB', 'Plays directly on most devices', transcodesHere: false),
            APresetInForce::reported('movies', 'Maximum', 'Looks right on a TV', '1080p, good encodes', '~2 GB', 'Plays directly on most devices', transcodesHere: true),
        ),
        WhatMusicIsSetTo::unset(),
        WhatBecameOfTheChoice::Shown,
        customised: false,
    );
}

/** One answer carried out of an arm. */
final readonly class WhatTheQualityCameBackAs
{
    public function __construct(public string $said) {}
}

/** The quality in force, as one line. */
function theQualityAsText(TheQualityChosen $chosen): string
{
    $presets = [];

    foreach ($chosen->presets() as $preset) {
        $presets[] = implode('/', [
            $preset->scope(),
            $preset->preset(),
            $preset->means(),
            $preset->resolution(),
            $preset->sizePerHour(),
            $preset->transcoding(),
            $preset->transcodesHere() ? 'here' : 'direct',
        ]);
    }

    return sprintf(
        '%s|%s|%s|%s',
        implode(';', $presets),
        $chosen->music()->either(
            set: static fn(AFormatInForce $format): WhatTheQualityCameBackAs => new WhatTheQualityCameBackAs(theFormatAsText($format)),
            unset: static fn(): WhatTheQualityCameBackAs => new WhatTheQualityCameBackAs('no music'),
        )->said,
        $chosen->became()->value,
        $chosen->customised() ? 'edited' : 'as written',
    );
}

/** A music format, as one line. */
function theFormatAsText(AFormatInForce $format): string
{
    return implode('/', [$format->scope(), $format->format(), $format->means(), $format->targets(), $format->sizePerHour(), $format->note()]);
}

/** What reading the quality came to, as one line. */
function howTheQualityReadsAsText(ChoosingQuality $choosing): string
{
    return $choosing->inForceOn(theMachineWhoseQualityIsChosen(), theSessionQualityIsChosenOn())->either(
        found: static fn(TheQualityChosen $chosen): WhatTheQualityCameBackAs => new WhatTheQualityCameBackAs(theQualityAsText($chosen)),
        met: static fn(Obstacle $why): WhatTheQualityCameBackAs => new WhatTheQualityCameBackAs(sprintf('refused:%s', $why->value)),
    )->said;
}

/** What a choice came to, as one line. */
function howAChoiceCameBackAsText(WhatTheChoiceCameTo $came): string
{
    return $came->either(
        inForce: static fn(TheQualityChosen $chosen): WhatTheQualityCameBackAs => new WhatTheQualityCameBackAs(theQualityAsText($chosen)),
        forMusic: static fn(AFormatChoiceMade $made): WhatTheQualityCameBackAs => new WhatTheQualityCameBackAs(sprintf(
            'music:%s|%s|%s|%s',
            theFormatAsText($made->format()),
            $made->became()->value,
            $made->applied()->saidOnTheScreen(),
            $made->applied()->detail(),
        )),
        met: static fn(Obstacle $why): WhatTheQualityCameBackAs => new WhatTheQualityCameBackAs(sprintf('refused:%s', $why->value)),
    )->said;
}

/** The adapter, answering every request with the body given. */
function gradersAnswering(mixed $body, int $status = 200): Graders
{
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make($body, $status)]);

    return new Graders(new PinnedClients());
}

/** Choosing through the adapter, answered with the body given. */
function aChoiceAnsweredWith(mixed $body, ?APresetToChoose $asked = null): string
{
    return howAChoiceCameBackAsText(gradersAnswering($body)->choose(
        theMachineWhoseQualityIsChosen(),
        theSessionQualityIsChosenOn(),
        $asked ?? APresetToChoose::named('maximum', 'movies'),
    ));
}

/**
 * The body the adapter last sent, with the action it was sent to.
 *
 * @return array{string, mixed}
 */
function whatTheGradersSent(): array
{
    $sent = MockClient::getGlobal()?->getLastRequest();

    return $sent instanceof ActionRequest ? [$sent->resolveEndpoint(), $sent->body()->all()] : ['', null];
}

/** A held answer about the choice asked, which is what a confirmation is made from. */
function aChoiceHeldFor(APresetToChoose $asked): AHeldChoice
{
    return AHeldChoice::of($asked, TheQualityChosen::reported(
        ThePresetsInForce::of(),
        WhatMusicIsSetTo::unset(),
        WhatBecameOfTheChoice::Held,
        customised: false,
    ));
}

/**
 * Both ways of reading the quality, each set up to produce the same answer.
 *
 * @return array<string, Closure(): ChoosingQuality>
 */
function everyWayOfReadingTheQuality(): array
{
    return [
        'the fake' => static fn(): ChoosingQuality => AStackThatChoosesQuality::with(theQualityTheFakeHolds()),
        'the adapter' => static fn(): ChoosingQuality => gradersAnswering(whatAStackSaysOfItsQuality()),
    ];
}

foreach (everyWayOfReadingTheQuality() as $name => $build) {
    it(sprintf('%s answers with every preset in force, what it costs and whether it transcodes here', $name), function () use ($build): void {
        expect(howTheQualityReadsAsText($build()))->toBe(
            'everything/Balanced/Looks right on a TV/1080p, good encodes/~2 GB/Plays directly on most devices/direct;'
            . 'movies/Maximum/Looks right on a TV/1080p, good encodes/~2 GB/Plays directly on most devices/here'
            . '|no music|shown|as written',
        );
    });
}

it('the fake and the adapter both refuse rather than answer with nothing', function (): void {
    expect(howTheQualityReadsAsText(gradersAnswering(['nothing' => 'the contract knows'], 500)))->toBe('refused:no_answer')
        ->and(howTheQualityReadsAsText(AStackThatChoosesQuality::met(Obstacle::StackDidNotAnswer)))->toBe('refused:no_answer');
});

it('the fake keeps choosing apart from confirming', function (): void {
    $fake = AStackThatChoosesQuality::with(theQualityTheFakeHolds());
    $asked = APresetToChoose::named('maximum', 'movies');

    $fake->choose(theMachineWhoseQualityIsChosen(), theSessionQualityIsChosenOn(), $asked);

    expect([$fake->choices(), $fake->confirmations(), $fake->chosen(), $fake->confirmed()])->toBe([1, 0, $asked, null]);

    $fake->confirm(theMachineWhoseQualityIsChosen(), theSessionQualityIsChosenOn(), aChoiceHeldFor($asked));

    expect([$fake->choices(), $fake->confirmations(), $fake->confirmed()])->toBe([1, 1, $asked]);
});

it('reads music in its own terms, with its format and what that means', function (): void {
    $read = howTheQualityReadsAsText(gradersAnswering(whatAStackSaysOfItsQuality(music: aFormatOnTheWire())));

    expect($read)->toContain('|music/Lossless/CD quality, nothing thrown away/FLAC/~300 MB/Some players need it converted|');
});

it('reads music left out altogether as none set, as it reads music sent as nothing', function (): void {
    expect(howTheQualityReadsAsText(gradersAnswering(aQualityAnswerWithout('music'))))->toContain('|no music|');
});

it('reads a hand-edited configuration as edited, and every disposition as the stack wrote it', function (string $disposition): void {
    expect(howTheQualityReadsAsText(gradersAnswering(whatAStackSaysOfItsQuality(disposition: $disposition, customised: true))))
        ->toEndWith(sprintf('|%s|edited', $disposition));
})->with(['shown', 'recorded', 'rehearsed', 'held', 'reapplied', 'would-reapply']);

it('refuses a quality answer it cannot read rather than drawing part of one', function (string $field, mixed $value): void {
    expect(howTheQualityReadsAsText(gradersAnswering(aQualityAnswerWith($field, $value))))->toBe('refused:no_answer');
})->with([
    'a disposition the contract has not got' => ['disposition', 'mostly'],
    'a disposition that is not a word' => ['disposition', 3],
    'customised that is not yes or no' => ['customised', 'yes'],
    'choices that are not a list' => ['choices', 'Balanced'],
    'a choice that is not a table' => ['choices', ['Balanced']],
    'a choice with a blank preset' => ['choices', [aPresetOnTheWire(preset: ' ')]],
    'a choice whose transcoding here is not yes or no' => ['choices', [[...aPresetOnTheWire(), 'needs_transcoding_here' => 'no']]],
    'a choice with no resolution' => ['choices', [array_diff_key(aPresetOnTheWire(), ['resolution' => true])]],
    'music that is not a table' => ['music', 'Lossless'],
    'music with a blank format' => ['music', [...aFormatOnTheWire(), 'format' => '']],
]);

it('refuses a quality answer missing a field the contract requires', function (string $field): void {
    expect(howTheQualityReadsAsText(gradersAnswering(aQualityAnswerWithout($field))))->toBe('refused:no_answer');
})->with(['choices', 'disposition', 'customised']);

it('refuses a quality answer whose data is not a table', function (): void {
    expect(howTheQualityReadsAsText(gradersAnswering(['api_version' => 1, 'kind' => 'quality', 'data' => 'Balanced'])))
        ->toBe('refused:no_answer');
});

it('says the credential was refused when the stack refuses it', function (): void {
    expect(howTheQualityReadsAsText(gradersAnswering(['error' => 'no'], 401)))->toBe('refused:credential_refused');
});

it('chooses for everything without naming a kind, and without a yes', function (): void {
    aChoiceAnsweredWith(whatAStackSaysOfItsQuality(disposition: 'recorded'), APresetToChoose::named('balanced', ''));

    expect(whatTheGradersSent())->toBe(['/api/actions/quality-set', ['preset' => 'balanced', 'confirm' => false]]);
});

it('chooses for one kind of media by naming it, and without a yes', function (): void {
    aChoiceAnsweredWith(whatAStackSaysOfItsQuality(disposition: 'held'));

    expect(whatTheGradersSent())->toBe(['/api/actions/quality-set', ['preset' => 'maximum', 'media_type' => 'movies', 'confirm' => false]]);
});

it('confirms exactly the choice that was held, with the yes', function (): void {
    $asked = APresetToChoose::named('maximum', 'movies');

    gradersAnswering(whatAStackSaysOfItsQuality(disposition: 'recorded'))
        ->confirm(theMachineWhoseQualityIsChosen(), theSessionQualityIsChosenOn(), aChoiceHeldFor($asked));

    expect(whatTheGradersSent())->toBe(['/api/actions/quality-set', ['preset' => 'maximum', 'media_type' => 'movies', 'confirm' => true]]);
});

it('reads a choice answered with the quality in force, held or recorded', function (): void {
    expect(aChoiceAnsweredWith(whatAStackSaysOfItsQuality(disposition: 'held')))->toEndWith('|held|as written')
        ->and(aChoiceAnsweredWith(whatAStackSaysOfItsQuality(disposition: 'recorded')))->toEndWith('|recorded|as written');
});

it('reads a choice for music as what its service made of it', function (): void {
    expect(aChoiceAnsweredWith(whatAStackSaysOfAFormatChosen(), APresetToChoose::named('lossless', 'music')))
        ->toBe('music:music/Lossless/CD quality, nothing thrown away/FLAC/~300 MB/Some players need it converted|recorded|quality.asked.started|');
});

it('reads every way asking the music service can have gone', function (?array $outcome, string $said): void {
    expect(aChoiceAnsweredWith(whatAStackSaysOfAFormatChosen($outcome, 'rehearsed')))->toEndWith(sprintf('|rehearsed|%s', $said));
})->with([
    'nothing asked' => [null, 'quality.asked.not-asked|'],
    'not ready' => [['state' => 'not-started'], 'quality.asked.not-started|'],
    'refused' => [['state' => 'failed', 'detail' => 'Lidarr said no'], 'quality.asked.failed|Lidarr said no'],
]);

it('refuses a choice answer it cannot read rather than drawing part of one', function (mixed $body): void {
    expect(aChoiceAnsweredWith($body))->toBe('refused:no_answer');
})->with([
    'an outcome the contract has not got' => [whatAStackSaysOfAFormatChosen(['state' => 'mostly'])],
    'a failure with no reason' => [whatAStackSaysOfAFormatChosen(['state' => 'failed'])],
    'an outcome that is not a table' => [[...whatAStackSaysOfAFormatChosen(), 'data' => [...aFormatChoiceData(), 'outcome' => 'started']]],
    'a choice that is not a table' => [[...whatAStackSaysOfAFormatChosen(), 'data' => [...aFormatChoiceData(), 'choice' => 'Lossless']]],
    'a music answer whose data is not a table' => [['api_version' => 1, 'kind' => 'music', 'data' => 'Lossless']],
    'an answer of another kind' => [['api_version' => 1, 'kind' => 'config', 'data' => []]],
]);

it('says what stood in the way of a choice rather than that it was not made', function (): void {
    expect(howAChoiceCameBackAsText(gradersAnswering(['error' => 'down'], 503)->choose(
        theMachineWhoseQualityIsChosen(),
        theSessionQualityIsChosenOn(),
        APresetToChoose::named('maximum', ''),
    )))->toBe('refused:no_answer');
});

it('stands in for a stack with payloads the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('QualityEnvelope', whatAStackSaysOfItsQuality(music: aFormatOnTheWire())))->toBe([])
        ->and(WhatTheContractAccepts::complaintsAbout('MusicEnvelope', whatAStackSaysOfAFormatChosen(['state' => 'failed', 'detail' => 'no'])))->toBe([]);
});
