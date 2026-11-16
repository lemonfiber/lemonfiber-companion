<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use JsonSerializable;

/**
 * A channel's live stream, opened for one member: where its bytes are served at the household's door, and until when.
 *
 * Two places, either of which may be missing: the tuner's own stream and a
 * transcoded playlist of it, each a {@see Location} at the door as the core
 * stated it. The player decides which it can decode. Each
 * carries the pass the core issued for this stream and this member in its
 * address, which is how the door asks for it, so **the addresses are a
 * secret**. Like {@see AGrant}, this will not be serialised or printed, and it
 * is handed to the player and to nothing else.
 *
 * The pass lapses when {@see self::lapses()} says. Renewing keeps the same
 * pass good for longer, so the addresses never change while it plays.
 */
final readonly class ALiveStream implements JsonSerializable
{
    /** What a live stream is printed or encoded as. */
    private const string HIDDEN = '(a live stream, hidden)';

    /** Where one of the two is not served. */
    private const string NOWHERE = '';

    private function __construct(
        private string $stream,
        private string $direct,
        private string $transcoded,
        private Fingerprint $door,
        private Instant $lapses,
    ) {}

    /**
     * What a debugger prints.
     *
     * @return array<string, string>
     */
    public function __debugInfo(): array
    {
        return ['stream' => self::HIDDEN];
    }

    /** @return array<string, never> */
    public function __serialize(): array
    {
        throw MustNotLeaveThisProcess::aLiveStream();
    }

    /** @param array<string, never> $data */
    public function __unserialize(array $data): void
    {
        throw MustNotLeaveThisProcess::aLiveStream();
    }

    public function jsonSerialize(): string
    {
        return self::HIDDEN;
    }

    /**
     * The stream the core opened, by the id a renewal or a stop names, served at one or both addresses.
     *
     * @param string $direct     where the tuner's own stream is served, or empty
     * @param string $transcoded where a transcoded playlist of it is served, or empty
     */
    public static function opened(string $stream, string $direct, string $transcoded, Fingerprint $door, Instant $lapses): self
    {
        if ($stream === self::NOWHERE) {
            throw LiveStreamIsUnfit::unnamed();
        }

        if ($direct === self::NOWHERE && $transcoded === self::NOWHERE) {
            throw LiveStreamIsUnfit::servedNowhere();
        }

        return new self($stream, $direct, $transcoded, $door, $lapses);
    }

    /** The same stream, its passes good until a later moment. */
    public function renewedUntil(Instant $lapses): self
    {
        return new self($this->stream, $this->direct, $this->transcoded, $this->door, $lapses);
    }

    /** The id a renewal or a stop names it by. */
    public function stream(): string
    {
        return $this->stream;
    }

    /** Where the tuner's own stream is served, pass included, as the player is handed it; empty where it is not. */
    public function directForThePlayer(): string
    {
        return $this->direct;
    }

    /** Where a transcoded playlist is served, pass included, as the player is handed it; empty where it is not. */
    public function transcodedForThePlayer(): string
    {
        return $this->transcoded;
    }

    /** The certificate the door presents, which the player pins. */
    public function door(): Fingerprint
    {
        return $this->door;
    }

    /** When its passes lapse. */
    public function lapses(): Instant
    {
        return $this->lapses;
    }
}
