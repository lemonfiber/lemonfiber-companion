<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * What stopping seeding one completed download would cost, read before anything is let go.
 *
 * The stack's offer: the download as its account names it — where it stands,
 * the ratio it has reached, what it occupies and what removing it costs — what
 * goes with it, and the name the offer goes by. Nothing has been let go when
 * this exists, and it is the only thing stopping seeding can be agreed against.
 *
 * **The agreement names this offer and no other.** The stack builds it from
 * everything an operator reads here, so a yes carrying it is a yes to exactly
 * this; an offer that has moved since, a ratio earned in the gap, is refused by
 * the stack rather than carried out.
 */
final readonly class WhatLettingItGoCosts
{
    private function __construct(
        private ADownloadOnDisk $download,
        private string $goes,
        private string $agreement,
    ) {}

    /**
     * The stack's offer to let one download go.
     *
     * What goes with it and the agreement are both required: an offer that
     * will not say what goes cannot say what it costs, and one that cannot be
     * named cannot be agreed to.
     */
    public static function offered(ADownloadOnDisk $download, string $goes, string $agreement): self
    {
        if (trim($goes) === '') {
            throw RoomSaysNothing::about('goes');
        }

        if (trim($agreement) === '') {
            throw RoomSaysNothing::about('agreement');
        }

        return new self($download, $goes, $agreement);
    }

    /** The download, where it stands and what removing it costs. */
    public function download(): ADownloadOnDisk
    {
        return $this->download;
    }

    /** What goes with it, in the stack's words. */
    public function goes(): string
    {
        return $this->goes;
    }

    /** What this offer is called, so a yes can name it. */
    public function agreement(): string
    {
        return $this->agreement;
    }
}
