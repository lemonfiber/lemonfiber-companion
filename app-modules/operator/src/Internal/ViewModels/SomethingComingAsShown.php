<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One download still coming down, as the row that draws it.
 */
final readonly class SomethingComingAsShown
{
    /**
     * @param string $name     what it is, as the client names it
     * @param int    $progress how far along, from none to a hundred
     */
    public function __construct(
        public string $name,
        public int $progress,
    ) {}
}
