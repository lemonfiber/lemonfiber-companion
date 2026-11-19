<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Which ways of downloading a stack is set up for.
 */
final readonly class TheWaysOfDownloading
{
    private function __construct(private bool $usenet, private bool $torrent) {}

    /** Set up for Usenet, for torrents, for both or for neither. */
    public static function of(bool $usenet, bool $torrent): self
    {
        return new self($usenet, $torrent);
    }

    /** Whether it downloads over Usenet. */
    public function usenet(): bool
    {
        return $this->usenet;
    }

    /** Whether it downloads torrents. */
    public function torrent(): bool
    {
        return $this->torrent;
    }
}
