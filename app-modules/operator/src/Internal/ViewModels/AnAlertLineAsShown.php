<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use Modules\Operator\Internal\Presenters\AgoAsShown;

/** One line about a back-end: the key it is said by, the core's own words that fill it, and how long ago it was. */
final readonly class AnAlertLineAsShown
{
    public function __construct(
        public string $said,
        public string $filling,
        public AgoAsShown $ago,
    ) {}
}
