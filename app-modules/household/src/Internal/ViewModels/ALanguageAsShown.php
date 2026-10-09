<?php

declare(strict_types=1);

namespace Modules\Household\Internal\ViewModels;

/**
 * One language a member can choose, as the chip that offers it.
 */
final readonly class ALanguageAsShown
{
    /**
     * @param string $said   the catalogue key for the chip's words
     * @param string $word   what choosing it hands the screen: the word the choice is kept under
     * @param bool   $chosen whether it is the one chosen now
     */
    public function __construct(
        public string $said,
        public string $word,
        public bool $chosen,
    ) {}
}
