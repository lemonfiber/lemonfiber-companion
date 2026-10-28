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
     * @param WhereTheWorkStands $stands           where the work stands, and whether the stack could be asked at all
     * @param bool              $namesADownload whether the screen was opened on a download at all
     * @param ?ADownloadAsShown $download       the download, where it stands, its ratio and what removing it costs
     * @param string            $goes           what goes with it, in the stack's words, or empty
     */
    public function __construct(
        public WhereTheWorkStands $stands,
        public bool $namesADownload,
        public ?ADownloadAsShown $download,
        public string $goes,
    ) {}
}
