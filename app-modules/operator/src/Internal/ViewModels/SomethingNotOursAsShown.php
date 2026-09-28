<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * Something beneath the data location that lemonfiber did not put there, as the row that draws it.
 */
final readonly class SomethingNotOursAsShown
{
    /**
     * @param string       $at    where it is, relative to the data location
     * @param int          $files how many files were found under it
     * @param ASizeAsShown $size  what they occupy
     */
    public function __construct(
        public string $at,
        public int $files,
        public ASizeAsShown $size,
    ) {}
}
