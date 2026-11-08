<?php

declare(strict_types=1);

namespace Modules\Device\Api;

use Lemonfiber\Native\EndpointAnswered;
use Lemonfiber\Native\Waking;
use Modules\Kernel\Api\ADistributor;
use Modules\Kernel\Api\AnEndpoint;
use Modules\Kernel\Api\AnEndpointGiven;
use Modules\Kernel\Api\AWakeIsUnfit;
use Modules\Kernel\Api\AWakingKey;
use Modules\Kernel\Api\ReachingADistributor;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\TheDistributors;
use Modules\Kernel\Api\WhatThisPhoneWakesThrough;
use Modules\Kernel\Api\WhyNoEndpoint;

/**
 * The UnifiedPush distributors on this phone, through lemonfiber's bridge.
 *
 * Each stack is registered under its id. An endpoint or key the bridge hands
 * back that would not make a registration is read as none given.
 */
final readonly class PlatformDistributors implements ReachingADistributor
{
    public function __construct(private Waking $waking) {}

    public function distributors(): WhatThisPhoneWakesThrough
    {
        return $this->waking->distributors()->either(
            installed: static function (array $installed): WhatThisPhoneWakesThrough {
                $distributors = [];

                foreach ($installed as $package => $called) {
                    $distributors[] = ADistributor::of($package, $called);
                }

                return WhatThisPhoneWakesThrough::installed(TheDistributors::of(...$distributors));
            },
            notHere: static fn(): WhatThisPhoneWakesThrough => WhatThisPhoneWakesThrough::notHere(),
        );
    }

    public function endpointFor(StackId $stack, ADistributor $distributor): AnEndpointGiven
    {
        return $this->given($this->waking->register($stack->stored(), $distributor->package()), WhyNoEndpoint::Refused);
    }

    public function endpointNowFor(StackId $stack): AnEndpointGiven
    {
        return $this->given($this->waking->endpoint($stack->stored()), WhyNoEndpoint::Dropped);
    }

    public function letGo(StackId $stack): bool
    {
        return $this->waking->letGo($stack->stored());
    }

    public function letGoOfEvery(): bool
    {
        return $this->waking->letGoOfEvery();
    }

    private function given(EndpointAnswered $answered, WhyNoEndpoint $otherwise): AnEndpointGiven
    {
        return $answered->either(
            given: static function (string $endpoint, string $key) use ($otherwise): AnEndpointGiven {
                try {
                    return AnEndpointGiven::given(AnEndpoint::of($endpoint), AWakingKey::of($key));
                } catch (AWakeIsUnfit) {
                    return AnEndpointGiven::not($otherwise);
                }
            },
            not: static fn(string $because): AnEndpointGiven => AnEndpointGiven::not(WhyNoEndpoint::said($because, $otherwise)),
        );
    }
}
