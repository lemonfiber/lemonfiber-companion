<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use function array_key_exists;

use Modules\Kernel\Api\ADistributor;
use Modules\Kernel\Api\AnEndpoint;
use Modules\Kernel\Api\AnEndpointGiven;
use Modules\Kernel\Api\AWakingKey;
use Modules\Kernel\Api\ReachingADistributor;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\TheDistributors;
use Modules\Kernel\Api\WhatThisPhoneWakesThrough;
use Modules\Kernel\Api\WhyNoEndpoint;

use function sprintf;
use function str_repeat;

/**
 * The UnifiedPush distributors on a phone, in memory, for a test to arrange and read.
 *
 * Held to `ReachingADistributorContractTest` beside the adapter: an endpoint
 * is given for each stack registered, a stack let go of has none, and a phone
 * that cannot be woken this way gives none at all.
 */
final class DistributorsOnAPhone implements ReachingADistributor
{
    /** The address each endpoint this phone is given begins with. */
    public const string PUSHED_AT = 'https://push.example/up/';

    /** @var array<string, string> each stack's endpoint, by stack */
    private array $endpoints = [];

    /** How many times every registration was let go of at once. */
    private int $everyLetGo = 0;

    private function __construct(private readonly ?TheDistributors $installed, private readonly ?WhyNoEndpoint $refusing) {}

    /** A phone with these distributors installed, ntfy alone where none is named. */
    public static function with(ADistributor ...$installed): self
    {
        return new self(TheDistributors::of(...($installed === [] ? [self::ntfy()] : $installed)), null);
    }

    /** An Android phone with no distributor installed. */
    public static function withNone(): self
    {
        return new self(TheDistributors::of(), WhyNoEndpoint::Refused);
    }

    /** An iPhone, or an Android too old to keep the key. */
    public static function notHere(): self
    {
        return new self(null, WhyNoEndpoint::NotHere);
    }

    /** A phone with ntfy installed that gives no endpoint, for this reason. */
    public static function givingNone(WhyNoEndpoint $why): self
    {
        return new self(TheDistributors::of(self::ntfy()), $why);
    }

    public static function ntfy(): ADistributor
    {
        return ADistributor::of('io.heckel.ntfy', 'ntfy');
    }

    public static function nextPush(): ADistributor
    {
        return ADistributor::of('org.unifiedpush.distributor.nextpush', 'NextPush');
    }

    /** The public key this phone makes for every stack. */
    public static function key(): AWakingKey
    {
        return AWakingKey::of(sprintf("\x04%s", str_repeat("\x01", AWakingKey::BYTES - 1)));
    }

    public function distributors(): WhatThisPhoneWakesThrough
    {
        return $this->installed instanceof TheDistributors ? WhatThisPhoneWakesThrough::installed($this->installed) : WhatThisPhoneWakesThrough::notHere();
    }

    public function endpointFor(StackId $stack, ADistributor $distributor): AnEndpointGiven
    {
        if ($this->refusing instanceof WhyNoEndpoint) {
            return AnEndpointGiven::not($this->refusing);
        }

        $this->endpoints[$stack->stored()] = sprintf('%s%s/%s', self::PUSHED_AT, $distributor->package(), $stack->stored());

        return $this->endpointNowFor($stack);
    }

    public function endpointNowFor(StackId $stack): AnEndpointGiven
    {
        if (! array_key_exists($stack->stored(), $this->endpoints)) {
            return AnEndpointGiven::not($this->refusing ?? WhyNoEndpoint::Dropped);
        }

        return AnEndpointGiven::given(AnEndpoint::of($this->endpoints[$stack->stored()]), self::key());
    }

    public function letGo(StackId $stack): bool
    {
        unset($this->endpoints[$stack->stored()]);

        return true;
    }

    public function letGoOfEvery(): bool
    {
        $this->endpoints = [];
        ++$this->everyLetGo;

        return true;
    }

    /** Whether the distributor holds a registration for the stack. */
    public function holdsARegistrationFor(StackId $stack): bool
    {
        return array_key_exists($stack->stored(), $this->endpoints);
    }

    /** The distributor gives the stack another endpoint: for a test to arrange. */
    public function movesTheEndpointOf(StackId $stack, string $endpoint): self
    {
        $this->endpoints[$stack->stored()] = $endpoint;

        return $this;
    }

    /** How many times every registration was let go of at once. */
    public function everyLetGoCount(): int
    {
        return $this->everyLetGo;
    }
}
