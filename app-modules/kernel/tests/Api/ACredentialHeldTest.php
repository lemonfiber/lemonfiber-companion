<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_keys;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\ACredentialHeld;
use Modules\Kernel\Api\CredentialSaysNothing;
use Modules\Kernel\Api\WhatUsesIt;
use Modules\Kernel\Api\WhereACredentialStands;
use Modules\Kernel\Api\WhoMadeACredential;

/** A credential with the name and advisory given here. */
function aCredentialSaying(string $name, string $advisory): ACredentialHeld
{
    return ACredentialHeld::described($name, WhereACredentialStands::Stale, WhoMadeACredential::Service, WhatUsesIt::of('sonarr'), $advisory);
}

it('keeps everything it was described with', function (): void {
    $consumers = WhatUsesIt::of('sonarr', 'bazarr');
    $credential = ACredentialHeld::described('Indexer API key', WhereACredentialStands::Invalid, WhoMadeACredential::Operator, $consumers, 'Refused on Tuesday');

    expect([$credential->name(), $credential->state(), $credential->origin(), $credential->consumers(), $credential->advisory()])
        ->toBe(['Indexer API key', WhereACredentialStands::Invalid, WhoMadeACredential::Operator, $consumers, 'Refused on Tuesday']);
});

it('takes an advisory that is empty', function (): void {
    expect(aCredentialSaying('Indexer API key', '')->advisory())->toBe('');
});

it('refuses a blank name, and an advisory that is blank rather than empty', function (): void {
    expect(fn(): ACredentialHeld => aCredentialSaying(' ', ''))->toThrow(CredentialSaysNothing::class, 'arrived with its `name` blank')
        ->and(fn(): ACredentialHeld => aCredentialSaying('Indexer API key', "\t"))->toThrow(CredentialSaysNothing::class, 'arrived with its `advisory` blank');
});

it('keeps what uses it in the stack\'s order, and nothing is an answer', function (): void {
    $consumers = WhatUsesIt::of(...['first' => 'sonarr', 'second' => 'bazarr']);

    expect(iterator_to_array($consumers, preserve_keys: false))->toBe(['sonarr', 'bazarr'])
        ->and(array_keys(iterator_to_array($consumers, preserve_keys: true)))->toBe([0, 1])
        ->and($consumers)->toHaveCount(2)
        ->and(WhatUsesIt::of())->toHaveCount(0);
});

it('refuses a consumer that says nothing', function (): void {
    expect(fn(): WhatUsesIt => WhatUsesIt::of('sonarr', ' '))->toThrow(CredentialSaysNothing::class, '`consumers`');
});

it('says each state and each origin under a key of its own', function (): void {
    expect(WhereACredentialStands::Rotating->saidOnTheScreen())->toBe('stacks.credentials.state.rotating')
        ->and(WhoMadeACredential::Lemonfiber->saidOnTheScreen())->toBe('stacks.credentials.origin.lemonfiber');
});
