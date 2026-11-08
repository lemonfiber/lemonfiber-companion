<?php

declare(strict_types=1);

use Lemonfiber\Native\Waking;
use Modules\Device\Api\PlatformDistributors;
use Modules\Kernel\Api\ADistributor;
use Modules\Kernel\Api\AnEndpoint;
use Modules\Kernel\Api\AnEndpointGiven;
use Modules\Kernel\Api\AWakingKey;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\ReachingADistributor;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\TheDistributors;
use Modules\Kernel\Api\WhyNoEndpoint;
use Native\Mobile\Testing\FakeBridge;
use Tests\Support\Fakes\DistributorsOnAPhone;
use Tests\Support\TheWordCarriedOut;

// The ReachingADistributor contract, run against the adapter and against the fake.
//
// Every test of waking runs over `DistributorsOnAPhone`, so this file is the
// one place that says the bridge's answers come to the same thing: what is
// installed is listed by name, a stack registered is given an endpoint and a
// key, a stack let go of has none, and a phone that cannot be woken this way
// lists nothing and gives nothing.

afterEach(function (): void {
    FakeBridge::disable();
});

function aStackAPhoneIsWokenFor(): StackId
{
    return StackId::of(Nonce::of(str_repeat('d', Nonce::SHORTEST)));
}

/** What a distributor gave, folded to a word. */
function whatWasGiven(AnEndpointGiven $given): string
{
    return $given->either(
        given: static fn(AnEndpoint $endpoint, AWakingKey $key): TheWordCarriedOut => new TheWordCarriedOut(sprintf('given %d', strlen($key->bytes()))),
        not: static fn(WhyNoEndpoint $why): TheWordCarriedOut => new TheWordCarriedOut($why->value),
    )->said;
}

/** What is installed, folded to a word. */
function whatIsInstalled(ReachingADistributor $phone): string
{
    return $phone->distributors()->either(
        installed: static function (TheDistributors $installed): TheWordCarriedOut {
            $called = [];

            foreach ($installed as $distributor) {
                $called[] = $distributor->called();
            }

            return new TheWordCarriedOut(implode(',', $called));
        },
        notHere: static fn(): TheWordCarriedOut => new TheWordCarriedOut('not here'),
    )->said;
}

/** @return array<string, array{Closure(): ReachingADistributor, Closure(): ReachingADistributor}> a phone with ntfy, and an iPhone */
function everyPhoneThatWakesAndOneThatCannot(): array
{
    $key = sodium_bin2base64(DistributorsOnAPhone::key()->bytes(), SODIUM_BASE64_VARIANT_ORIGINAL);

    return [
        'the fake' => [
            static fn(): ReachingADistributor => DistributorsOnAPhone::with(),
            static fn(): ReachingADistributor => DistributorsOnAPhone::notHere(),
        ],
        'the adapter' => [
            static function () use ($key): ReachingADistributor {
                FakeBridge::disable();
                FakeBridge::enable()
                    ->respondTo('Lemonfiber.Waking.Distributors', ['outcome' => 'listed', 'distributors' => [['package' => 'io.heckel.ntfy', 'called' => 'ntfy']]])
                    ->respondTo('Lemonfiber.Waking.Register', ['outcome' => 'given', 'endpoint' => 'https://ntfy.sh/upAbC?up=1', 'p256dh' => $key])
                    ->respondTo('Lemonfiber.Waking.Endpoint', ['outcome' => 'not_given', 'because' => 'dropped'])
                    ->respondTo('Lemonfiber.Waking.LetGo', ['outcome' => 'let_go'])
                    ->respondTo('Lemonfiber.Waking.LetGoOfEvery', ['outcome' => 'let_go']);

                return new PlatformDistributors(new Waking());
            },
            static function (): ReachingADistributor {
                FakeBridge::disable();
                FakeBridge::enable()
                    ->respondTo('Lemonfiber.Waking.Distributors', ['outcome' => 'not_here'])
                    ->respondTo('Lemonfiber.Waking.Register', ['outcome' => 'not_given', 'because' => 'not_here'])
                    ->respondTo('Lemonfiber.Waking.Endpoint', ['outcome' => 'not_given', 'because' => 'not_here']);

                return new PlatformDistributors(new Waking());
            },
        ],
    ];
}

foreach (everyPhoneThatWakesAndOneThatCannot() as $name => [$wakes, $cannot]) {
    it(sprintf('%s lists the distributors installed by what each is called', $name), function () use ($wakes): void {
        expect(whatIsInstalled($wakes()))->toBe('ntfy');
    });

    it(sprintf('%s gives a stack an endpoint and the key the phone made for it', $name), function () use ($wakes): void {
        expect(whatWasGiven($wakes()->endpointFor(aStackAPhoneIsWokenFor(), DistributorsOnAPhone::ntfy())))->toBe(sprintf('given %d', AWakingKey::BYTES));
    });

    it(sprintf('%s lets go of a stack and of every stack, and gives a stack it holds nothing for no endpoint', $name), function () use ($wakes): void {
        $phone = $wakes();

        expect($phone->letGo(aStackAPhoneIsWokenFor()))->toBeTrue()
            ->and($phone->letGoOfEvery())->toBeTrue()
            ->and(whatWasGiven($phone->endpointNowFor(aStackAPhoneIsWokenFor())))->toBe(WhyNoEndpoint::Dropped->value);
    });

    it(sprintf('%s lists nothing and gives nothing where the phone cannot be woken this way', $name), function () use ($cannot): void {
        $phone = $cannot();

        expect(whatIsInstalled($phone))->toBe('not here')
            ->and(whatWasGiven($phone->endpointFor(aStackAPhoneIsWokenFor(), DistributorsOnAPhone::ntfy())))->toBe(WhyNoEndpoint::NotHere->value)
            ->and(whatWasGiven($phone->endpointNowFor(aStackAPhoneIsWokenFor())))->toBe(WhyNoEndpoint::NotHere->value);
    });
}

// Beyond the contract: what the adapter asks the bridge, and what it will not take.

it('registers the stack by its id with the distributor by its package', function (): void {
    $bridge = FakeBridge::enable()->respondTo('Lemonfiber.Waking.Register', ['outcome' => 'not_given', 'because' => 'no_answer']);

    expect(whatWasGiven(new PlatformDistributors(new Waking())->endpointFor(aStackAPhoneIsWokenFor(), ADistributor::of('org.unifiedpush.distributor.nextpush', 'NextPush'))))->toBe(WhyNoEndpoint::NoAnswer->value);

    $bridge->assertCalled('Lemonfiber.Waking.Register');
});

it('reads an endpoint with a key that is not a P-256 point, or a reason it does not know, as none given', function (array $answered, string $read): void {
    FakeBridge::enable()->respondTo('Lemonfiber.Waking.Register', $answered);

    expect(whatWasGiven(new PlatformDistributors(new Waking())->endpointFor(aStackAPhoneIsWokenFor(), DistributorsOnAPhone::ntfy())))->toBe($read);
})->with([
    'a key too short' => [['outcome' => 'given', 'endpoint' => 'https://ntfy.sh/upAbC?up=1', 'p256dh' => sodium_bin2base64('short', SODIUM_BASE64_VARIANT_ORIGINAL)], WhyNoEndpoint::Refused->value],
    'a blank endpoint' => [['outcome' => 'given', 'endpoint' => ' ', 'p256dh' => sodium_bin2base64(DistributorsOnAPhone::key()->bytes(), SODIUM_BASE64_VARIANT_ORIGINAL)], WhyNoEndpoint::Refused->value],
    'a reason it does not know' => [['outcome' => 'not_given', 'because' => 'perhaps'], WhyNoEndpoint::Refused->value],
    'no bridge answer' => [[], WhyNoEndpoint::Refused->value],
]);
