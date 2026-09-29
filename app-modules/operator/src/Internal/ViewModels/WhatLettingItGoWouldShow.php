<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What stopping seeding one download would cost, flattened for a template.
 *
 * An offer throughout: nothing here describes something that has happened,
 * and every field is empty where the stack has not offered yet.
 */
final readonly class WhatLettingItGoWouldShow
{
    /**
     * @param HowTheReadingWent $went           whether the offer came back, and what stood in the way where it did not
     * @param bool              $namesADownload whether the screen was opened on a download at all
     * @param bool              $isWorking      whether the stack is still working out what it would cost
     * @param bool              $hasEnded       whether the stack has no outcome for the asking any more
     * @param ?ADownloadAsShown $download       the download, where it stands, its ratio and what removing it costs
     * @param string            $goes           what goes with it, in the stack's words, or empty
     */
    public function __construct(
        public HowTheReadingWent $went,
        public bool $namesADownload,
        public bool $isWorking,
        public bool $hasEnded,
        public ?ADownloadAsShown $download,
        public string $goes,
    ) {}
}
