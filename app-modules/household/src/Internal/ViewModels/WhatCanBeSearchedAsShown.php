<?php

declare(strict_types=1);

namespace Modules\Household\Internal\ViewModels;

/**
 * What a member can search among, as the two rows of chips the Search tab offers it with, and how the tab says what it is for.
 */
final readonly class WhatCanBeSearchedAsShown
{
    /**
     * @param list<AChoiceAsShown> $kinds       every kind they can search, the one chosen now marked, or none where there is only one
     * @param list<AChoiceAsShown> $units       every unit of the chosen kind, the one chosen now marked, or none where it has only one or none
     * @param string               $explained   the catalogue key for what the tab is for, naming only what it offers
     * @param string               $placeholder the catalogue key for what the field suggests looking for
     */
    public function __construct(
        public array $kinds,
        public array $units,
        public string $explained,
        public string $placeholder,
    ) {}
}
