<?php

declare(strict_types=1);

use Lemonfiber\Native\DistributorsListed;
use Lemonfiber\Native\EndpointAnswered;
use Lemonfiber\Native\Waking;
use Native\Mobile\Testing\FakeBridge;

// Being woken through a distributor, driven through the real bridge call.
//
// The bridge names are written out as literals, for `LinkTest`'s reason.

beforeEach(function (): void {
    FakeBridge::disable();
});

/** What was listed, as one line. */
function theDistributorsListed(DistributorsListed $listed): string
{
    return $listed->either(
        installed: static fn(array $installed): string => (string) json_encode($installed),
        notHere: static fn(): string => 'not here',
    );
}

/** What the bridge answered for an endpoint, as one line. */
function theEndpointAnswered(EndpointAnswered $answered): string
{
    return $answered->either(
        given: static fn(string $endpoint, string $key): string => sprintf('%s %s', $endpoint, $key),
        not: static fn(string $because): string => sprintf('not: %s', $because),
    );
}

it('lists each distributor installed by its package, with what it is called, and leaves out a row naming none', function (): void {
    FakeBridge::enable()->respondTo('Lemonfiber.Waking.Distributors', ['outcome' => 'listed', 'distributors' => [
        ['package' => 'io.heckel.ntfy', 'called' => 'ntfy'],
        ['package' => 'org.unifiedpush.distributor.nextpush'],
        ['package' => 'org.example.unnamed', 'called' => 7],
        ['called' => 'nameless'],
        'not a row',
    ]]);

    expect(theDistributorsListed(new Waking()->distributors()))->toBe('{"io.heckel.ntfy":"ntfy","org.unifiedpush.distributor.nextpush":"","org.example.unnamed":""}');
});

it('reads a phone that cannot be woken this way, a list that is not one, or no answer as not here', function (array $answered): void {
    FakeBridge::enable()->respondTo('Lemonfiber.Waking.Distributors', $answered);

    expect(theDistributorsListed(new Waking()->distributors()))->toBe('not here');
})->with([
    'not here' => [['outcome' => 'not_here']],
    'listed with no list' => [['outcome' => 'listed']],
    'a word it does not know' => [['outcome' => 'perhaps', 'distributors' => []]],
    'no answer' => [[]],
]);

it('registers a stack with a distributor, and reads the endpoint and the key as bytes', function (): void {
    $bridge = FakeBridge::enable()->respondTo('Lemonfiber.Waking.Register', ['outcome' => 'given', 'endpoint' => 'https://ntfy.sh/upAbC?up=1', 'p256dh' => 'a2V5']);

    expect(theEndpointAnswered(new Waking()->register('the-loft', 'io.heckel.ntfy')))->toBe('https://ntfy.sh/upAbC?up=1 key');

    $bridge->assertCalled('Lemonfiber.Waking.Register', static fn(array $params): bool => $params === ['stack' => 'the-loft', 'distributor' => 'io.heckel.ntfy']);
});

it('asks for the endpoint and lets go by the stack each is about', function (): void {
    $bridge = FakeBridge::enable()->respondTo('Lemonfiber.Waking.Endpoint', ['outcome' => 'given', 'endpoint' => 'https://ntfy.sh/up', 'p256dh' => 'a2V5'])->respondTo('Lemonfiber.Waking.LetGo', ['outcome' => 'let_go']);

    expect(theEndpointAnswered(new Waking()->endpoint('the-loft')))->toBe('https://ntfy.sh/up key')
        ->and(new Waking()->letGo('the-loft'))->toBeTrue();

    $bridge->assertCalled('Lemonfiber.Waking.Endpoint', static fn(array $params): bool => $params === ['stack' => 'the-loft'])
        ->assertCalled('Lemonfiber.Waking.LetGo', static fn(array $params): bool => $params === ['stack' => 'the-loft']);
});

it('reads no endpoint, no key, a key that is not base64, or a refusal as none given, with the bridge\'s word', function (array $answered, string $read): void {
    FakeBridge::enable()->respondTo('Lemonfiber.Waking.Endpoint', $answered);

    expect(theEndpointAnswered(new Waking()->endpoint('the-loft')))->toBe($read);
})->with([
    'refused' => [['outcome' => 'not_given', 'because' => 'dropped'], 'not: dropped'],
    'no key' => [['outcome' => 'given', 'endpoint' => 'https://ntfy.sh/up'], 'not: '],
    'an empty key' => [['outcome' => 'given', 'endpoint' => 'https://ntfy.sh/up', 'p256dh' => ''], 'not: '],
    'a key that is not base64' => [['outcome' => 'given', 'endpoint' => 'https://ntfy.sh/up', 'p256dh' => '!!'], 'not: '],
    'no endpoint' => [['outcome' => 'given', 'p256dh' => 'a2V5'], 'not: '],
]);

it('lets go of a stack and of every stack, and reads no answer as not let go of', function (): void {
    expect(new Waking()->letGo('the-loft'))->toBeFalse()
        ->and(new Waking()->letGoOfEvery())->toBeFalse();

    FakeBridge::enable()->respondTo('Lemonfiber.Waking.LetGo', ['outcome' => 'let_go'])->respondTo('Lemonfiber.Waking.LetGoOfEvery', ['outcome' => 'let_go']);

    expect(new Waking()->letGo('the-loft'))->toBeTrue()
        ->and(new Waking()->letGoOfEvery())->toBeTrue();
});

it('runs one stack\'s wake with the command each native half names', function (): void {
    $kotlin = (string) file_get_contents(sprintf('%s/resources/android/WakingRule.kt', dirname(__DIR__)));

    expect($kotlin)->toContain('"${WakeRule.COMMAND} --stack=$stack"');
});
