<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use Modules\Operator\Internal\Presenters\AgoAsShown;

/** How many starts in a row the stack failed to come back after, and for how long, flattened for the restart section. */
final readonly class WhatFailingCameTo
{
    /**
     * @param string     $said   the key for how long it has failed, dated or not, or empty where it is not failing
     * @param int        $starts how many starts in a row, or nought where it is not failing
     * @param AgoAsShown $since  how long ago it began failing, where the stack said
     */
    public function __construct(
        public string $said,
        public int $starts,
        public AgoAsShown $since,
    ) {}
}
