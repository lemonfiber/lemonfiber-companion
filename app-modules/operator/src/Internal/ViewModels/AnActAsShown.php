<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One act a healing run took, as the queue screen draws it.
 */
final readonly class AnActAsShown
{
    /**
     * @param string $actSaid     the key for the act
     * @param string $outcomeSaid the key for what became of it
     * @param string $tone        the tone that outcome is drawn in, a `Modules\Design\View\Tone` value
     * @param string $insteadSaid the key for what was done in its place, or empty
     * @param string $why         why it was refused or fell back, in the stack's words, or empty
     */
    public function __construct(
        public string $actSaid,
        public string $outcomeSaid,
        public string $tone,
        public string $insteadSaid,
        public string $why,
    ) {}
}
