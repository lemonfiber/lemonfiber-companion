<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * Which versions a stack runs and what its running release changed, flattened for a template.
 *
 * Every optional line is empty where there is nothing to say, and the template
 * branches on it rather than printing a blank.
 */
final readonly class TheVersionsTurnedOutToBe
{
    /**
     * @param string                       $lemonfiber      the version of lemonfiber, or empty where nothing answered
     * @param string                       $stack           the version of the stack, or empty where nothing answered
     * @param string                       $engine          what the container engine reports, or empty where it could not be asked
     * @param string                       $notesSaid       the catalogue key saying the notes are not shown and why, or empty where they are
     * @param string                       $notesMeanSaid   the catalogue key explaining that, or empty
     * @param string                       $release         the running release's version, where its notes are shown, or empty
     * @param string                       $noticedSaid     the catalogue key for whether the household would notice it, or empty
     * @param bool                         $withdrawn       whether the running release was taken back
     * @param list<ALineOfTheNotesAsShown> $changes         what the running release changed, each group's title before its changes
     */
    public function __construct(
        public HowTheReadingWent $went,
        public string $lemonfiber,
        public string $stack,
        public string $engine,
        public string $notesSaid,
        public string $notesMeanSaid,
        public string $release,
        public string $noticedSaid,
        public bool $withdrawn,
        public array $changes,
    ) {}
}
