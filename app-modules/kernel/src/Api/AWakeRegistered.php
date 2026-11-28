<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What the phone keeps of a wake a stack holds: the name it lists, the endpoint by its fingerprint, and the WebPush key and secret it encrypts to.
 *
 * The whole address is not among it: it goes to the stack only in a registration.
 * Printed as its name alone.
 */
final readonly class AWakeRegistered
{
    public function __construct(
        public APushName $name,
        public AWakeFingerprint $fingerprint,
        public AWakingKey $key,
        public AWakingSecret $secret,
    ) {}

    /** @return array<string, string> */
    public function __debugInfo(): array
    {
        return ['name' => $this->name->shown()];
    }

    /** Whether the distributor still gives the endpoint the stack holds. */
    public function isAt(AnEndpoint $endpoint): bool
    {
        return $endpoint->fingerprint()->is($this->fingerprint);
    }

    /** The same, to register again at the endpoint the distributor gives now. */
    public function at(AnEndpoint $endpoint): AWakeToRegister
    {
        return new AWakeToRegister($this->name, $endpoint, $this->key, $this->secret);
    }
}
