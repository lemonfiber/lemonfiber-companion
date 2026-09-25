<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One profile a start would leave out, flattened to a row. */
final readonly class AProfileLeftOutAsShown
{
    /**
     * @param string $profile   the profile, as the stack names it
     * @param string $needsSaid the catalogue key for what it would have needed
     */
    public function __construct(
        public string $profile,
        public string $needsSaid,
    ) {}
}
