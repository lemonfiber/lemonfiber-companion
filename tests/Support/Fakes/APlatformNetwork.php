<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use function json_decode;

use Native\Mobile\Network as Platform;
use RuntimeException;

use function sprintf;

use stdClass;

/**
 * The platform's network facade, standing in for a bridge that is not there.
 *
 * `Network::status()` reaches `nativephp_call`, which exists only inside the
 * handset runtime. Without a stand-in {@see \Modules\Device\Api\PlatformNetwork}
 * is a file nothing executes, which is the situation every adapter here is in
 * and why each has one of these. `G1` forbids mocking a type we do not own; a
 * subclass with the method written out fails to compile when the real class
 * changes, where a mock would drift in silence.
 *
 * Every answer is written as the JSON the bridge would hand back and decoded
 * the way the real facade decodes it, so a fixture cannot describe a shape the
 * platform could not produce — including the shapes that are missing a key or
 * have the wrong type in one, which are the ones the adapter exists to survive.
 */
final class APlatformNetwork extends Platform
{
    /** What `json_decode` is given for depth, matching the platform's default. */
    private const int DEPTH = 512;

    private function __construct(private readonly ?stdClass $said) {}

    /** A device on a network of some kind. */
    public static function connected(): self
    {
        return self::answering('{"connected":true,"type":"wifi","isExpensive":false}');
    }

    /** A device with no network at all. */
    public static function withNothingToReachOver(): self
    {
        return self::answering('{"connected":false,"type":"unknown","isExpensive":false}');
    }

    /** Whatever the bridge said, for the shapes that are not either of those. */
    public static function answering(string $json): self
    {
        $said = json_decode($json, associative: false, depth: self::DEPTH, flags: JSON_THROW_ON_ERROR);

        if (! $said instanceof stdClass) {
            throw new RuntimeException(sprintf('%s is not an answer this bridge could give', $json));
        }

        return new self($said);
    }

    /** No bridge, which is every run of this suite and every desktop build. */
    public static function withNoBridge(): self
    {
        return new self(null);
    }

    public function status(): ?object
    {
        return $this->said;
    }
}
