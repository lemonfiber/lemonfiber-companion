<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One choice drawn as chips: what is chosen, and every chip offered. */
final readonly class TheChipsAsShown
{
    /**
     * @param string                          $said    the catalogue key saying what is chosen
     * @param list<AChoiceOfASettingAsShown>  $offered every chip, in order, the chosen one marked
     */
    public function __construct(
        public string $said,
        public array $offered,
    ) {}
}
