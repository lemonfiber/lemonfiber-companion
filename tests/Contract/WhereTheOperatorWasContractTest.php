<?php

declare(strict_types=1);

use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\WhereTheOperatorWas;
use Modules\Kernel\Api\WhichTab;
use Modules\Vault\Api\PlatformWhereTheOperatorWas;
use Tests\Support\Fakes\APlatformStore;
use Tests\Support\Fakes\WhereTheOperatorWasInMemory;

// The WhereTheOperatorWas contract, run against the platform store and the fake.

/** A stack the operator was on. Named for this file. */
function aStackTheOperatorWasOn(string $seed): StackId
{
    return StackId::of(Nonce::of(str_repeat($seed, Nonce::SHORTEST)));
}

/** @return array<string, array{Closure(): WhereTheOperatorWas}> */
dataset('every place the operator was', [
    'the platform store' => [fn(): WhereTheOperatorWas => new PlatformWhereTheOperatorWas(APlatformStore::working())],
    'the fake' => [fn(): WhereTheOperatorWas => WhereTheOperatorWasInMemory::nowhere()],
]);

it('knows nowhere before the operator has been anywhere, and opens every stack on Health', function (WhereTheOperatorWas $was): void {
    expect($was->wasLastOn(aStackTheOperatorWasOn('a')))->toBeFalse()
        ->and($was->tabOf(aStackTheOperatorWasOn('a')))->toBe(WhichTab::Health);
})->with('every place the operator was');

it('keeps the stack last on view, and the tab last used on each stack', function (WhereTheOperatorWas $was): void {
    $kept = $was->wasOn(aStackTheOperatorWasOn('a'), WhichTab::Updates);
    $was->wasOn(aStackTheOperatorWasOn('b'), WhichTab::Services);
    $was->wasOn(aStackTheOperatorWasOn('b'), WhichTab::Repairs);

    expect($kept)->toBeTrue()
        ->and($was->wasLastOn(aStackTheOperatorWasOn('b')))->toBeTrue()
        ->and($was->wasLastOn(aStackTheOperatorWasOn('a')))->toBeFalse()
        ->and($was->tabOf(aStackTheOperatorWasOn('a')))->toBe(WhichTab::Updates)
        ->and($was->tabOf(aStackTheOperatorWasOn('b')))->toBe(WhichTab::Repairs);
})->with('every place the operator was');

it('lets go of one stack\'s part, last on view or not, and keeps the others', function (WhereTheOperatorWas $was): void {
    $was->wasOn(aStackTheOperatorWasOn('a'), WhichTab::Updates);
    $was->wasOn(aStackTheOperatorWasOn('b'), WhichTab::Services);

    $last = $was->forgetTheStack(aStackTheOperatorWasOn('b'))->howMany();
    $earlier = $was->forgetTheStack(aStackTheOperatorWasOn('a'))->howMany();

    expect([$last, $earlier])->toBe([1, 1])
        ->and($was->forgetTheStack(aStackTheOperatorWasOn('a'))->howMany())->toBe(0)
        ->and($was->wasLastOn(aStackTheOperatorWasOn('b')))->toBeFalse()
        ->and($was->keepsAnythingOf(aStackTheOperatorWasOn('a')))->toBeFalse()
        ->and($was->tabOf(aStackTheOperatorWasOn('b')))->toBe(WhichTab::Health);
})->with('every place the operator was');

it('lets go of everything, counting the stacks it held a tab for', function (WhereTheOperatorWas $was): void {
    $was->wasOn(aStackTheOperatorWasOn('a'), WhichTab::Updates);
    $was->wasOn(aStackTheOperatorWasOn('b'), WhichTab::Services);

    expect($was->forgetEverything()->howMany())->toBe(2)
        ->and($was->keepsAnythingOf(aStackTheOperatorWasOn('b')))->toBeFalse()
        ->and($was->wasLastOn(aStackTheOperatorWasOn('b')))->toBeFalse();
})->with('every place the operator was');

it('says a place it could not keep was not kept, and remembers none of it', function (WhereTheOperatorWas $refusing): void {
    expect($refusing->wasOn(aStackTheOperatorWasOn('a'), WhichTab::Updates))->toBeFalse()
        ->and($refusing->tabOf(aStackTheOperatorWasOn('a')))->toBe(WhichTab::Health);
})->with([
    'the platform store' => [fn(): WhereTheOperatorWas => new PlatformWhereTheOperatorWas(APlatformStore::refusing())],
    'the fake' => [fn(): WhereTheOperatorWas => WhereTheOperatorWasInMemory::refusing()],
]);

it('reads a record it did not write as nowhere', function (string $written): void {
    $was = new PlatformWhereTheOperatorWas(APlatformStore::working()->alreadyHolding('lemonfiber.where-left-off', $written));

    expect($was->wasLastOn(aStackTheOperatorWasOn('a')))->toBeFalse()
        ->and($was->tabOf(aStackTheOperatorWasOn('a')))->toBe(WhichTab::Health);
})->with([
    'not JSON' => ['not json'],
    'another shape' => [sprintf('{"shape":2,"last":"%s","tabs":{"%1$s":"updates"}}', str_repeat('a', Nonce::SHORTEST))],
    'parts that are not text' => ['{"shape":1,"last":7,"tabs":"updates"}'],
    'a tab this build does not draw' => [sprintf('{"shape":1,"last":"","tabs":{"%s":"elsewhere"}}', str_repeat('a', Nonce::SHORTEST))],
]);

it('says a store it cannot read may still keep something of a stack', function (): void {
    expect(new PlatformWhereTheOperatorWas(APlatformStore::refusing())->keepsAnythingOf(aStackTheOperatorWasOn('a')))->toBeTrue();
});

it('keeps no place whose record cannot be written down', function (): void {
    $was = new PlatformWhereTheOperatorWas(APlatformStore::working());

    expect($was->wasOn(StackId::rememberedAs("a stack with a broken byte \xB1\x31 in it"), WhichTab::Updates))->toBeFalse()
        ->and($was->forgetEverything()->howMany())->toBe(0);
});
