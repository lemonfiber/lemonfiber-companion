<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function is_array;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\CapabilitiesEnvelope;
use Modules\Kernel\Api\Ability;
use Modules\Kernel\Api\Availability;
use Modules\Kernel\Api\Capabilities;
use Modules\Kernel\Api\Declared;
use Modules\Kernel\Api\StackId;
use Modules\Sdk\Api\Fields\CapabilitiesField;
use Modules\Sdk\Internal\Wire;

/**
 * The `capabilities` envelope, as what one stack declares it can do.
 *
 * Written the way {@see Repertoires} is: a static fold with no state, reading
 * through the field names and refusing rather than salvaging.
 *
 * **A path this app has never heard of is read like any other.** The stack may
 * be newer than this app and declare requests nothing here asks for; those
 * lines are carried and never asked about, rather than the answer being
 * refused for holding them. What is refused is a state that is none of the
 * three the contract names, because a button drawn from a word nobody can
 * read is a guess about whether it works.
 */
final readonly class Abilities
{
    /**
     * What this stack declares, line by line.
     *
     * @param Envelope<mixed> $envelope the `capabilities` envelope, as the client returned it
     */
    public static function in(Envelope $envelope, StackId $stack): Capabilities
    {
        $declared = [];

        foreach (self::listed(self::payload(Wire::checked($envelope))) as $path => $said) {
            $declared[] = Declared::of(Ability::of((string) $path), self::availability($said, (string) $path));
        }

        return Capabilities::of($stack, ...$declared);
    }

    /**
     * The payload, as it actually arrived.
     *
     * `mixed` deliberately, for {@see Rosters::payload()}'s reason: the
     * generated envelope asserts its shape without checking it.
     *
     * @param Envelope<mixed> $envelope
     */
    private static function payload(Envelope $envelope): mixed
    {
        return CapabilitiesEnvelope::in($envelope)->data;
    }

    /**
     * Each path and what was said of it, as it arrived.
     *
     * @return array<mixed>
     */
    private static function listed(mixed $data): array
    {
        if (! is_array($data) || ! array_key_exists(CapabilitiesField::Capabilities->value, $data)) {
            throw CapabilitiesAreUnreadable::missing(CapabilitiesField::Capabilities);
        }

        $listed = $data[CapabilitiesField::Capabilities->value];

        if (! is_array($listed)) {
            throw CapabilitiesAreUnreadable::missing(CapabilitiesField::Capabilities);
        }

        return $listed;
    }

    /** What the stack said of one path, in one of the contract's three words. */
    private static function availability(mixed $said, string $path): Availability
    {
        $availability = is_string($said) ? Availability::tryFrom($said) : null;

        return $availability ?? throw CapabilitiesAreUnreadable::state($path);
    }
}
