<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One way unrated material can go, as the chip that offers it.
 */
final readonly class AnUnratedChoiceAsShown
{
    /**
     * @param string $said   the catalogue key for the chip's words
     * @param string $word   what choosing it hands the screen: a case of what becomes of unrated material, or empty to leave it to the stack
     * @param bool   $chosen whether it is the one chosen now
     */
    public function __construct(
        public string $said,
        public string $word,
        public bool $chosen,
    ) {}
}
