<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * An update's account, as the template draws it: from and to, what stops, the new version's install, and where it did not hold, what putting the old one back came to.
 */
final readonly class APluginUpdateAsShown
{
    /**
     * @param bool                    $isAReading     whether nothing has happened yet: the yes is still to come
     * @param string                  $from           the version it moves from
     * @param string                  $to             the version it moves to
     * @param list<string>            $interrupts     every service that stops for it, named before any does
     * @param APluginInstallAsShown   $install        the new version's own account
     * @param HowPuttingARunBackWent  $wentBack       what putting the installed version's changes back came to, or would
     * @param string                  $headline       the catalogue key for how it ended, or empty on a reading
     * @param string                  $stopped        what stopped the new version before its proofs were asked, or empty
     * @param string                  $restoredSaid   the catalogue key for what putting the old version back came to, or empty where nothing was
     * @param string                  $restoredVersion the version put back, or empty
     */
    public function __construct(
        public bool $isAReading,
        public string $from,
        public string $to,
        public array $interrupts,
        public APluginInstallAsShown $install,
        public HowPuttingARunBackWent $wentBack,
        public string $headline,
        public string $stopped,
        public string $restoredSaid,
        public string $restoredVersion,
    ) {}
}
