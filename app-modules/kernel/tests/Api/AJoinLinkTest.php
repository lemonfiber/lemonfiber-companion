<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function http_build_query;
use function it;

use Modules\Kernel\Api\AClaim;
use Modules\Kernel\Api\AJoinLink;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\JoinLinkCannotBeUsed;
use Modules\Kernel\Api\MustNotLeaveThisProcess;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhyAJoinLinkCannotBeUsed;

use const PHP_QUERY_RFC3986;

use function print_r;
use function serialize;
use function sprintf;
use function str_repeat;

use Tests\Support\Fakes\FrozenClock;
use Tests\Support\TheWordCarriedOut;

use function unserialize;

/** The moment every link in this file is opened at. */
const OPENED_AT = 1_000;

/** The house every link in this file names. */
const THE_HOUSE_IT_NAMES = '7f3c9a1e5b2d4086a9c1e3f5b7d90246';

/**
 * A link's parameters, with whatever a test needs to change about them.
 *
 * @return array<string, string>
 */
function whatTheLinkCarries(): array
{
    return [
        'address' => 'https://192.168.1.42:8443',
        'fingerprint' => str_repeat('a', 64),
        'stack' => THE_HOUSE_IT_NAMES,
        'expires' => '2000',
        'name' => 'Robin Ash',
    ];
}

/** @param array<string, string> $carries */
function aLinkCarrying(array $carries): string
{
    return sprintf('lemonfiber://join?%s', http_build_query($carries, encoding_type: PHP_QUERY_RFC3986));
}

function openedNow(): FrozenClock
{
    return FrozenClock::at(Instant::atEpochSeconds(OPENED_AT));
}

function whyItIsRefused(string $handed): WhyAJoinLinkCannotBeUsed
{
    try {
        AJoinLink::read($handed, openedNow());
    } catch (JoinLinkCannotBeUsed $refused) {
        return $refused->why();
    }

    throw JoinLinkCannotBeUsed::because(WhyAJoinLinkCannotBeUsed::NotAJoinLink, 'it was read');
}

it('reads the house, its certificate and the name the person signs in as, decoded', function (): void {
    $link = AJoinLink::read(aLinkCarrying(whatTheLinkCarries()), openedNow());
    $house = $link->house(StackName::of('Home'));

    expect($link->stack()->is(StackId::saidBy(THE_HOUSE_IT_NAMES)))->toBeTrue()
        ->and($house->id()->is($link->stack()))->toBeTrue()
        ->and($house->name()->shown())->toBe('Home')
        ->and($house->at()->forTheClient())->toBe('https://192.168.1.42:8443')
        ->and($house->presents()->is(Fingerprint::of(str_repeat('a', 64))))->toBeTrue()
        ->and($link->name()->forTheExchange())->toBe('Robin Ash')
        ->and($link->leadsTo(claiming: static fn(): TheWordCarriedOut => new TheWordCarriedOut('claiming'), signingIn: static fn(): TheWordCarriedOut => new TheWordCarriedOut('signing in'))->said)->toBe('signing in');
});

it('reads a house named by a machine whose own name carries capitals, as the core writes it', function (): void {
    $link = AJoinLink::read(aLinkCarrying([...whatTheLinkCarries(), 'address' => 'https://Wessels-MacBook-Pro.local:8443']), openedNow());

    expect($link->house(StackName::of('Home'))->at()->forTheClient())->toBe('https://wessels-macbook-pro.local:8443');
});

it('carries the claim it was written with, for the one exchange that claims the invitation, and never lets it out of the process', function (): void {
    $claim = AJoinLink::read(aLinkCarrying([...whatTheLinkCarries(), 'claim' => 'a-token-of-enough-random-bits']), openedNow())
        ->leadsTo(claiming: static fn(AClaim $carried): AClaim => $carried, signingIn: static fn(): TheWordCarriedOut => new TheWordCarriedOut('no claim'));

    expect($claim instanceof AClaim ? $claim->forTheExchange() : $claim->said)->toBe('a-token-of-enough-random-bits')
        ->and(print_r($claim, return: true))->not->toContain('a-token-of-enough-random-bits')
        ->and(static fn(): string => serialize($claim))->toThrow(MustNotLeaveThisProcess::class, 'A claim may not be serialised')
        ->and(static fn(): mixed => unserialize('O:25:"Modules\\Kernel\\Api\\AClaim":0:{}'))->toThrow(MustNotLeaveThisProcess::class);
});

it('refuses what is not a join link', function (string $handed): void {
    expect(whyItIsRefused($handed))->toBe(WhyAJoinLinkCannotBeUsed::NotAJoinLink);
})->with([
    'a web address' => [sprintf('https://join?%s', http_build_query(whatTheLinkCarries()))],
    'another of its links' => [sprintf('lemonfiber://pair?%s', http_build_query(whatTheLinkCarries()))],
    'pairing material' => ['{"address":"https://192.168.1.42:8443"}'],
    'a link with a path' => [sprintf('lemonfiber://join/more?%s', http_build_query(whatTheLinkCarries()))],
    'a link with a fragment' => [sprintf('%s#more', aLinkCarrying(whatTheLinkCarries()))],
    'a link with a port' => [sprintf('lemonfiber://join:80?%s', http_build_query(whatTheLinkCarries()))],
    'nothing' => [''],
]);

it('refuses a link without one of the parameters every link carries', function (string $missing): void {
    $carries = whatTheLinkCarries();
    unset($carries[$missing]);

    expect(whyItIsRefused(aLinkCarrying($carries)))->toBe(WhyAJoinLinkCannotBeUsed::WithoutAParameter);
})->with(['address', 'fingerprint', 'stack', 'expires', 'name']);

it('refuses a link with no parameters at all', function (): void {
    expect(whyItIsRefused('lemonfiber://join'))->toBe(WhyAJoinLinkCannotBeUsed::WithoutAParameter);
});

it('refuses a link with a parameter a join link does not carry', function (): void {
    expect(whyItIsRefused(aLinkCarrying([...whatTheLinkCarries(), 'operator' => 'yes'])))->toBe(WhyAJoinLinkCannotBeUsed::WithAParameterItDoesNotKnow);
});

it('refuses a link with a parameter it cannot read', function (string $handed): void {
    expect(whyItIsRefused($handed))->toBe(WhyAJoinLinkCannotBeUsed::WithAParameterItCannotRead);
})->with([
    'an unencrypted address' => [aLinkCarrying([...whatTheLinkCarries(), 'address' => 'http://192.168.1.42:8443'])],
    'an address that is not one' => [aLinkCarrying([...whatTheLinkCarries(), 'address' => 'nowhere'])],
    'an address naming one host and dialling another' => [aLinkCarrying([...whatTheLinkCarries(), 'address' => 'https://192.168.1.42:8443@evil.example'])],
    'an address with a backslash' => [aLinkCarrying([...whatTheLinkCarries(), 'address' => 'https://192.168.1.42\\@evil.example'])],
    'an address with an encoded name' => [aLinkCarrying([...whatTheLinkCarries(), 'address' => 'https://xn--lft-una.local'])],
    'an address with a trailing dot' => [aLinkCarrying([...whatTheLinkCarries(), 'address' => 'https://loft.local.'])],
    'a fingerprint that is not one' => [aLinkCarrying([...whatTheLinkCarries(), 'fingerprint' => 'abc'])],
    'a house no stack names itself' => [aLinkCarrying([...whatTheLinkCarries(), 'stack' => 'the-loft'])],
    'a blank name' => [aLinkCarrying([...whatTheLinkCarries(), 'name' => ' '])],
    'a moment that is not a number' => [aLinkCarrying([...whatTheLinkCarries(), 'expires' => 'soon'])],
    'a moment too long to be one' => [aLinkCarrying([...whatTheLinkCarries(), 'expires' => str_repeat('9', 19)])],
    'an empty claim' => [aLinkCarrying([...whatTheLinkCarries(), 'claim' => ''])],
    'a parameter written twice' => [sprintf('%s&name=Sam', aLinkCarrying(whatTheLinkCarries()))],
    'a parameter without a value' => [sprintf('%s&claim', aLinkCarrying(whatTheLinkCarries()))],
]);

it('refuses a link whose offer has lapsed, at the very moment it lapses too', function (string $expires): void {
    expect(whyItIsRefused(aLinkCarrying([...whatTheLinkCarries(), 'expires' => $expires])))->toBe(WhyAJoinLinkCannotBeUsed::Lapsed);
})->with(['999', '1000']);

it('reads a lapsed link as lapsed before it reads an address it cannot', function (): void {
    expect(whyItIsRefused(aLinkCarrying([...whatTheLinkCarries(), 'expires' => '10', 'address' => 'nowhere'])))->toBe(WhyAJoinLinkCannotBeUsed::Lapsed);
});
