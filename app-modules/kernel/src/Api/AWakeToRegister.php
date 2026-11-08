<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What a stack is asked to keep to wake this phone: the name it lists, the endpoint, and the WebPush key and secret it encrypts to.
 *
 * Printed as its name alone.
 */
final readonly class AWakeToRegister
{
    public function __construct(
        public APushName $name,
        public AnEndpoint $endpoint,
        public AWakingKey $key,
        public AWakingSecret $secret,
    ) {}

    /** @return array<string, string> */
    public function __debugInfo(): array
    {
        return ['name' => $this->name->shown()];
    }

    /** The same, at the endpoint the distributor gives now. */
    public function at(AnEndpoint $endpoint): self
    {
        return new self($this->name, $endpoint, $this->key, $this->secret);
    }
}
