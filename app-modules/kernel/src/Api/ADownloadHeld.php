<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One completed download the client holds, by the name it and the stack's account both use.
 *
 * A name rather than a path or a hash: the stack finds the torrent by the name
 * its account of the disk printed, so the name is the one thing a phone can ask
 * about. Carried exactly as the account listed it.
 */
final readonly class ADownloadHeld
{
    private function __construct(private string $name) {}

    /** A download by the name the stack's account listed it under. */
    public static function named(string $name): self
    {
        if (trim($name) === '') {
            throw RoomSaysNothing::about('download');
        }

        return new self($name);
    }

    /** The name, for showing and for asking with. */
    public function name(): string
    {
        return $this->name;
    }
}
