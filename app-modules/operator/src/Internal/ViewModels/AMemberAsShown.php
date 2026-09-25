<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One member of the household, as the row that draws them.
 */
final readonly class AMemberAsShown
{
    /**
     * @param string $name         the name their account is held under
     * @param string $standingSaid the catalogue key for whether they have taken the account up
     */
    public function __construct(
        public string $name,
        public string $standingSaid,
    ) {}
}
