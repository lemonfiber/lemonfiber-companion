<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function afterEach;
use function expect;
use function it;
use function json_encode;

use Lemonfiber\Sdk\Contract\Api;
use Modules\Kernel\Api\ABundle;
use Modules\Kernel\Api\ABundleAsked;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ASettingToReveal;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowManyLines;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SettingsToReveal;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhatFilenamesShow;
use Modules\Sdk\Api\Bundlers;
use Modules\Sdk\Api\PinnedClients;
use RuntimeException;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;

use function sprintf;
use function str_repeat;

use Tests\Support\Fakes\SequencedEntropy;
use Tests\Support\WhatABundleSays;
use Tests\Support\WhatTheContractAccepts;

/**
 * What the adapter puts on the wire, which the contract cannot ask about: a
 * fake dials nothing, so the path, the arguments and the key are checked here,
 * and so is which refusal is the stack's answer and which is an obstacle.
 */
afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack a bundle is asked of. */
function theStackTheBundlerAsks(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/**
 * What a stack answers a bundle it took on with.
 *
 * @return array<string, mixed>
 */
function whatAStackGatheringABundleSends(): array
{
    return ['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'support', 'job' => 'a-bundle']];
}

/** The request asking for that bundle sent. */
function whatAskingForABundleSent(ABundleAsked $asked): PendingRequest
{
    MockClient::destroyGlobal();
    $mock = MockClient::global([MockResponse::make((string) json_encode(whatAStackGatheringABundleSends()), 202)]);

    new Bundlers(new PinnedClients(), SequencedEntropy::counting())->ask(theStackTheBundlerAsks(), Session::of('a-session-not-a-secret'), $asked);

    $sent = $mock->getLastPendingRequest();

    if (! $sent instanceof PendingRequest) {
        throw new RuntimeException('The adapter sent nothing, so this case read nothing.');
    }

    return $sent;
}

/** What asking after a bundle came to, when the stack answers with this. */
function whatBecameOfTheBundleAnswered(MockResponse $answered): string
{
    MockClient::destroyGlobal();
    MockClient::global([$answered]);

    return new Bundlers(new PinnedClients(), SequencedEntropy::counting())
        ->whatBecameOf(theStackTheBundlerAsks(), Session::of('a-session-not-a-secret'), Job::named('a-bundle'))
        ->either(
            stillRunning: static fn(): WhatTheBundlerSaid => new WhatTheBundlerSaid('still running'),
            done: static fn(ABundle $bundle): WhatTheBundlerSaid => new WhatTheBundlerSaid(WhatABundleSays::of($bundle)),
            refused: static fn(string $said): WhatTheBundlerSaid => new WhatTheBundlerSaid(sprintf('refused: %s', $said)),
            ended: static fn(): WhatTheBundlerSaid => new WhatTheBundlerSaid('ended'),
            met: static fn(Obstacle $why): WhatTheBundlerSaid => new WhatTheBundlerSaid($why->name),
        )->said;
}

/** One line carried out of an `either()` arm. */
final readonly class WhatTheBundlerSaid
{
    public function __construct(public string $said) {}
}

it('describes a bundle at the support action, saying every choice and writing nothing', function (): void {
    $sent = whatAskingForABundleSent(ABundleAsked::described(HowManyLines::of(50), WhatFilenamesShow::Replaced, SettingsToReveal::none()));

    expect($sent->getUrl())->toEndWith('/api/actions/support')
        ->and($sent->body()?->all())->toBe(['write' => false, 'logs' => 50, 'filenames' => false, 'reveal' => [], 'confirm' => false]);
});

it('writes the bundle described, with the settings agreed to and the yes that goes with them', function (): void {
    $asked = ABundleAsked::described(
        HowManyLines::of(1000),
        WhatFilenamesShow::Shown,
        SettingsToReveal::none()->with(ASettingToReveal::named('SONARR_URL'))->with(ASettingToReveal::named('RADARR_URL')),
    )->written();

    expect(whatAskingForABundleSent($asked)->body()?->all())
        ->toBe(['write' => true, 'logs' => 1000, 'filenames' => true, 'reveal' => ['SONARR_URL', 'RADARR_URL'], 'confirm' => true]);
});

it('names the attempt in the header, because asking for a bundle can write one', function (): void {
    expect(whatAskingForABundleSent(ABundleAsked::described(HowManyLines::of(50), WhatFilenamesShow::Replaced, SettingsToReveal::none()))->headers()->get(Api::IDEMPOTENCY_HEADER))
        ->toBe(SequencedEntropy::counting()->nonce()->shown());
});

it('asks after a bundle at the handle it was answered with', function (): void {
    MockClient::destroyGlobal();
    $mock = MockClient::global([MockResponse::make('{"error":"no such job"}', 404)]);

    new Bundlers(new PinnedClients(), SequencedEntropy::counting())->whatBecameOf(theStackTheBundlerAsks(), Session::of('a-session-not-a-secret'), Job::named('a-bundle'));

    expect($mock->getLastPendingRequest()?->getUrl())->toEndWith('/api/jobs/a-bundle');
});

it('carries a bundle the stack refused in its own words, from an error or from prose', function (int $status, string $body): void {
    expect(whatBecameOfTheBundleAnswered(MockResponse::make($body, $status)))
        ->toBe(sprintf('refused: %s', WhatABundleSays::A_LEAK));
})->with([
    'an error envelope' => [500, (string) json_encode(['api_version' => 1, 'kind' => 'error', 'data' => [
        'code' => 'BUNDLE_LEAK',
        'severity' => 'critical',
        'state' => 'guided',
        'summary' => WhatABundleSays::A_LEAK,
        'meaning' => 'Nothing has been written.',
        'remedies' => [['action' => 'Report which file this names']],
        'detail' => 'services.txt line 3 — nothing was written',
    ]])],
    'a sentence' => [400, WhatABundleSays::A_LEAK],
]);

it('reads a refused session, an account that may not ask, and a refusal with no words as obstacles', function (int $status, string $body, Obstacle $why): void {
    expect(whatBecameOfTheBundleAnswered(MockResponse::make($body, $status)))->toBe($why->name);
})->with([
    'a refused session' => [401, 'This needs the run token.', Obstacle::CredentialWasRefused],
    'an account that may not ask' => [403, 'Only the operator can ask for this.', Obstacle::NotForThisAccount],
    'nothing said' => [500, '', Obstacle::StackDidNotAnswer],
    'markup from something in between' => [502, '<html>bad gateway</html>', Obstacle::StackDidNotAnswer],
]);

it('stands in for a stack with payloads the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('JobEnvelope', whatAStackGatheringABundleSends()))->toBe([]);
});
