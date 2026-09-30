<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One value a setting can take, as the chip that offers it. */
final readonly class AChoiceOfASettingAsShown
{
    /**
     * @param string $said   the catalogue key for the chip's words
     * @param string $word   what choosing it hands the screen
     * @param bool   $chosen whether it is the one in force now
     * @param int    $count  the count the words are said for, where they have one
     */
    public function __construct(
        public string $said,
        public string $word,
        public bool $chosen,
        public int $count = 1,
    ) {}
}
