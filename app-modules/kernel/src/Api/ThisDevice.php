<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function hash;
use function preg_match;

/**
 * The id this install goes by when it asks a stack for a grant to play.
 *
 * Drawn once per install and kept. The core opens the member's session for
 * the device a grant names, and asking again under the same id replaces that
 * session; an install that drew a new id each time would leave sessions behind
 * that only their lapse ends.
 *
 * Letters, digits and hyphens, eight to sixty-four of them, which is what the
 * core accepts. It is not a secret and says nothing about the device: it is
 * drawn from entropy rather than from the model, the owner or the platform.
 */
final readonly class ThisDevice
{
    /** What the core accepts as a device's id. */
    private const string AN_ID = '/\A[A-Za-z0-9-]{8,64}\z/';

    /** The hash a drawn id is written as: thirty-two hexadecimal digits from a nonce of any length. */
    private const string DRAWN_AS = 'xxh128';

    private function __construct(private string $id) {}

    /** The id as written, where it is one the core accepts. */
    public static function named(string $id): self
    {
        if (preg_match(self::AN_ID, $id) !== 1) {
            throw DeviceIdIsUnfit::forTheCore();
        }

        return new self($id);
    }

    /** A new id, drawn from entropy. */
    public static function drawnFrom(Nonce $nonce): self
    {
        return self::named(hash(self::DRAWN_AS, $nonce->shown()));
    }

    /** The id, as the core is told it and as it is kept. */
    public function shown(): string
    {
        return $this->id;
    }
}
