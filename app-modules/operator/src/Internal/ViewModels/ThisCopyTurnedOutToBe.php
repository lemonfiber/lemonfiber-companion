<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What asking a stack about its running copy of lemonfiber produced, flattened for a template.
 *
 * Every optional sentence is empty where the stack said nothing, and the
 * template branches on it rather than printing a blank.
 */
final readonly class ThisCopyTurnedOutToBe
{
    /**
     * @param string $running       the version running, or empty where nothing answered
     * @param string $installedSaid the catalogue key for how it was installed
     * @param string $owner         the tool that owns it, or empty
     * @param string $standsSaid    the catalogue key for where it stands
     * @param string $offered       the newest version released, or empty
     * @param string $changed       what that version says it changed, or empty
     * @param string $untold        why availability could not be told, or empty
     * @param string $command       exactly what to type to update, or empty
     * @param string $instead       why there is nothing exact to type, or empty
     * @param string $carries       what a release brings besides the program
     * @param string $afterwards    what updating leaves alone and needs afterwards
     */
    public function __construct(
        public HowTheReadingWent $went,
        public string $running,
        public string $installedSaid,
        public string $owner,
        public string $standsSaid,
        public string $offered,
        public string $changed,
        public string $untold,
        public string $command,
        public string $instead,
        public string $carries,
        public string $afterwards,
    ) {}
}
