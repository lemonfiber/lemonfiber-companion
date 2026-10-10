<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One app a device can be pointed at the stack with, and the code that points it.
 *
 * Every word is the stack's. A closed app may be named and is never the
 * recommended path, which is why whether it is open source is carried.
 */
final readonly class AClientToHandOver
{
    private function __construct(
        private string $device,
        private string $client,
        private bool $openSource,
        private string $code,
        private bool $deepLink,
    ) {}

    /** As the stack named it; a blank word it owes is refused. */
    public static function named(string $device, string $client, bool $openSource, string $code, bool $deepLink): self
    {
        foreach (['device' => $device, 'client' => $client, 'code' => $code] as $field => $said) {
            if (trim($said) === '') {
                throw HandoffSaysNothing::about($field);
            }
        }

        return new self($device, $client, $openSource, $code, $deepLink);
    }

    /** What somebody would call the device they are holding. */
    public function device(): string
    {
        return $this->device;
    }

    /** What to use on it. */
    public function client(): string
    {
        return $this->client;
    }

    /** Whether the app is open source. */
    public function isOpenSource(): bool
    {
        return $this->openSource;
    }

    /** The code for this app: a link that opens it at this server, or the address alone. */
    public function code(): string
    {
        return $this->code;
    }

    /** What a code of it carries, which is its code as the stack gave it. */
    public function carried(): string
    {
        return $this->code;
    }

    /** Whether the code is such a link rather than the address alone. */
    public function isALink(): bool
    {
        return $this->deepLink;
    }
}
