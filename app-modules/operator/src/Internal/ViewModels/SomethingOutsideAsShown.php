<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * Something lemonfiber cannot take, or something a removal left, as the row that draws it.
 */
final readonly class SomethingOutsideAsShown
{
    /**
     * @param string $what      what it is
     * @param string $why       why it is not lemonfiber's, or what the machine said about it
     * @param string $byHand    how to remove it by hand on this platform
     * @param string $foundSaid the catalogue key for whether the survey found it, or empty where that is not the question
     */
    public function __construct(
        public string $what,
        public string $why,
        public string $byHand,
        public string $foundSaid,
    ) {}
}
