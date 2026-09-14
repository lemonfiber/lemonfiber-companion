<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What became of one service when an update was applied, flattened to a row.
 *
 * **Four endings stay four on the row.** `N2-R18` refuses a single *failed*, and
 * the pressure to flatten lives exactly here — a row is where somebody would
 * write *it worked* or *it did not*. What the row carries is the key the ending
 * names, so the sentence is the catalogue's and the distinction survives to the
 * screen.
 *
 * **Whether the way back brings the data with it travels as a flag, not as a
 * sentence.** `N2-R19` has a rollback and a restore be two offers, and the
 * difference an operator decides on is that one of them puts the evening's data
 * back too. A row handed a finished phrase could not lead on it.
 */
final readonly class WhatOneServiceTookItSays
{
    public function __construct(
        public string $service,
        public string $endingSaid,
        public string $undoSaid,
        public bool $arrived,
        public bool $undoCarriesTheDataWithIt,
    ) {}
}
