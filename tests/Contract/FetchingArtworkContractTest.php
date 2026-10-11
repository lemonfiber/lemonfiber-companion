<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AFoundTitle;
use Modules\Kernel\Api\Artwork;
use Modules\Kernel\Api\FetchingArtwork;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HoldingId;
use Modules\Kernel\Api\HowArtworkCame;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\KindOfTitle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhenItCameOut;
use Modules\Kernel\Api\WhereAFoundTitleStands;
use Modules\Kernel\Api\Whose;
use Modules\Sdk\Api\Pictures;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackWithPosters;
use Tests\Support\TheWordCarriedOut;

afterEach(function (): void {
    MockClient::destroyGlobal();
});

const A_POSTERS_BYTES = "\x89PNG\r\n\x1a\nnot-really-a-poster";

function aHouseWithPosters(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('q', Nonce::SHORTEST))),
        StackName::of('Home'),
        Address::of('https://192.168.1.46:8443'),
        Fingerprint::of(str_repeat('a', Fingerprint::CHARACTERS)),
    );
}

function aSessionPostersAreDrawnFor(): Session
{
    return Session::of('a-session-not-a-secret');
}

function aFoundTitleWithAPoster(): AFoundTitle
{
    return AFoundTitle::listed('438631', KindOfTitle::Film, 'Dune', WhenItCameOut::in(2021), WhereAFoundTitleStands::Askable);
}

/** The adapter, answered with these responses in order. */
function picturesAnswered(MockResponse ...$answered): Pictures
{
    MockClient::destroyGlobal();
    MockClient::global($answered);

    return new Pictures(new PinnedClients());
}

/**
 * Both ways of fetching a poster, each set up to answer the same.
 *
 * @return array<string, Closure(): FetchingArtwork>
 */
function everyWayOfFetchingAPoster(MockResponse $answered, HowArtworkCame $came): array
{
    return [
        'the fake' => static fn(): FetchingArtwork => AStackWithPosters::answering($came),
        'the adapter' => static fn(): FetchingArtwork => picturesAnswered($answered),
    ];
}

function howThePosterCame(HowArtworkCame $came): string
{
    return $came->either(
        arrived: static fn(Artwork $artwork): TheWordCarriedOut => new TheWordCarriedOut(sprintf('arrived %d bytes', strlen($artwork->bytes()))),
        refused: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->value),
    )->said;
}

it('fetches a held title\'s poster and backdrop and a found title\'s poster, byte for byte', function (): void {
    $png = static fn(): MockResponse => MockResponse::make(A_POSTERS_BYTES, 200, ['Content-Type' => 'image/png']);

    foreach (everyWayOfFetchingAPoster($png(), HowArtworkCame::arrived(Artwork::of(A_POSTERS_BYTES))) as $which => $make) {
        expect(howThePosterCame($make()->posterOfAHolding(aHouseWithPosters(), aSessionPostersAreDrawnFor(), Whose::member('sanne'), HoldingId::called('a1'))))
            ->toBe(sprintf('arrived %d bytes', strlen(A_POSTERS_BYTES)), $which);
    }

    foreach (everyWayOfFetchingAPoster($png(), HowArtworkCame::arrived(Artwork::of(A_POSTERS_BYTES))) as $which => $make) {
        expect(howThePosterCame($make()->backdropOfAHolding(aHouseWithPosters(), aSessionPostersAreDrawnFor(), Whose::member('sanne'), HoldingId::called('a1'))))
            ->toBe(sprintf('arrived %d bytes', strlen(A_POSTERS_BYTES)), $which);
    }

    foreach (everyWayOfFetchingAPoster($png(), HowArtworkCame::arrived(Artwork::of(A_POSTERS_BYTES))) as $which => $make) {
        expect(howThePosterCame($make()->posterOfAFoundTitle(aHouseWithPosters(), aSessionPostersAreDrawnFor(), aFoundTitleWithAPoster())))
            ->toBe(sprintf('arrived %d bytes', strlen(A_POSTERS_BYTES)), $which);
    }
});

it('asks a held title\'s pictures as the member, the household\'s defaults for the operator, and a found title\'s poster by its kind', function (): void {
    $png = static fn(): MockResponse => MockResponse::make(A_POSTERS_BYTES, 200, ['Content-Type' => 'image/png']);
    $asked = [];
    MockClient::destroyGlobal();
    $client = MockClient::global([$png(), $png(), $png(), $png()]);
    $pictures = new Pictures(new PinnedClients());

    $pictures->posterOfAHolding(aHouseWithPosters(), aSessionPostersAreDrawnFor(), Whose::member('sanne'), HoldingId::called('a1'));
    $pictures->posterOfAHolding(aHouseWithPosters(), aSessionPostersAreDrawnFor(), Whose::theOperator(), HoldingId::called('a1'));
    $pictures->backdropOfAHolding(aHouseWithPosters(), aSessionPostersAreDrawnFor(), Whose::member('sanne'), HoldingId::called('a1'));
    $pictures->posterOfAFoundTitle(aHouseWithPosters(), aSessionPostersAreDrawnFor(), aFoundTitleWithAPoster());

    foreach ($client->getRecordedResponses() as $response) {
        $request = $response->getPendingRequest();
        $asked[] = sprintf('%s?%s', parse_url($request->getUrl(), PHP_URL_PATH), http_build_query($request->query()->all()));
    }

    expect($asked)->toBe([
        '/api/held/a1/poster?member=sanne',
        '/api/held/a1/poster?defaults=true',
        '/api/held/a1/backdrop?member=sanne',
        '/api/askable/438631/poster?kind=movies',
    ]);
});

it('says a title with no poster, a refused session and a picture it cannot read as what stood in the way', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), KindOfObstacle::CredentialWasRefused],
        [MockResponse::make('not a picture', 200, ['Content-Type' => 'text/plain']), KindOfObstacle::AnswerCouldNotBeRead],
        [MockResponse::make('', 200, ['Content-Type' => 'image/png']), KindOfObstacle::AnswerCouldNotBeRead],
    ];

    foreach ($table as [$answered, $kind]) {
        foreach (everyWayOfFetchingAPoster($answered, HowArtworkCame::refused(Obstacle::of($kind))) as $which => $make) {
            expect(howThePosterCame($make()->posterOfAFoundTitle(aHouseWithPosters(), aSessionPostersAreDrawnFor(), aFoundTitleWithAPoster())))
                ->toBe($kind->value, sprintf('%s, %s', $which, $kind->value));
        }
    }
});
