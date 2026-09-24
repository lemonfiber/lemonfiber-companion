<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One word of the glossary, flattened for a template.
 *
 * The longer gloss is drawn only where it is open, and the control that opens
 * it only where there is one.
 */
final readonly class AWordAsShown
{
    /**
     * @param string $word       the word itself
     * @param string $short      what it means, in a line
     * @param string $deep       what it means at length, or empty
     * @param string $alsoCalled what else it is called, joined, or empty
     * @param bool   $isOpen     whether the longer gloss is drawn
     */
    public function __construct(
        public string $word,
        public string $short,
        public string $deep,
        public string $alsoCalled,
        public bool $isOpen,
    ) {}
}
