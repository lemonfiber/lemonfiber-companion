<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * The address a distributor gave this phone to be woken at, for one stack.
 *
 * Whoever holds it can wake the phone, so it is said to nobody but the stack it is for.
 */
final readonly class AnEndpoint
{
    /** What a debugger sees instead of the address. */
    private const string HIDDEN = '(an endpoint, hidden)';

    private function __construct(private string $address) {}

    /** @return array<string, string> */
    public function __debugInfo(): array
    {
        return ['address' => self::HIDDEN];
    }

    /** The one place a string becomes an endpoint; a blank one is refused. */
    public static function of(string $address): self
    {
        if (trim($address) === '') {
            throw AWakeIsUnfit::anEndpoint();
        }

        return new self($address);
    }

    /** The address as the distributor wrote it. */
    public function address(): string
    {
        return $this->address;
    }

    /** What a stack names the address by once it keeps it. */
    public function fingerprint(): AWakeFingerprint
    {
        return AWakeFingerprint::ofTheAddress($this->address);
    }
}
