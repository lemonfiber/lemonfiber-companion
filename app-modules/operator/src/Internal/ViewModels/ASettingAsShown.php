<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** A setting as it stands, and every value it can take. */
final readonly class ASettingAsShown
{
    /**
     * @param string                         $said    the catalogue key for the value in force
     * @param list<AChoiceOfASettingAsShown> $offered every value it can take, the one in force marked
     */
    public function __construct(
        public string $said,
        public array $offered,
    ) {}
}
