<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What asking a stack which app to watch on produced, flattened for a template.
 *
 * The three straining sentences are empty together where nothing strains
 * playback on this machine, and the template branches on the first.
 */
final readonly class TheAdviceTurnedOutToBe
{
    /**
     * @param list<ADeviceAsShown>  $devices            every device, in the stack's order
     * @param string                $onlyAtHome         where every device works, said once, or empty where nothing came back
     * @param string                $nothingIsInstalled what this will not do for them, or empty where nothing came back
     * @param string                $strainingPreset    the preset that strains playback here, or empty
     * @param string                $strainingCaution   what that preset asks of this machine, or empty
     * @param string                $strainingInstead   what makes it stop, or empty
     * @param list<ATroubleAsShown> $troubles           what to do when it does not work
     */
    public function __construct(
        public HowTheReadingWent $went,
        public array $devices,
        public string $onlyAtHome,
        public string $nothingIsInstalled,
        public string $strainingPreset,
        public string $strainingCaution,
        public string $strainingInstead,
        public array $troubles,
    ) {}
}
