<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/** The endpoint a distributor gave for one stack, with the public key the phone keeps for it, or why there is none. */
final readonly class AnEndpointGiven
{
    private function __construct(private ?AnEndpoint $endpoint, private ?AWakingKey $key, private WhyNoEndpoint $why) {}

    public static function given(AnEndpoint $endpoint, AWakingKey $key): self
    {
        return new self($endpoint, $key, WhyNoEndpoint::NotHere);
    }

    public static function not(WhyNoEndpoint $why): self
    {
        return new self(null, null, $why);
    }

    /**
     * @template TGiven of object
     * @template TNot of object
     *
     * @param  Closure(AnEndpoint, AWakingKey): TGiven $given
     * @param  Closure(WhyNoEndpoint): TNot            $not
     * @return TGiven|TNot
     */
    public function either(Closure $given, Closure $not): object
    {
        return $this->endpoint instanceof AnEndpoint && $this->key instanceof AWakingKey ? $given($this->endpoint, $this->key) : $not($this->why);
    }
}
