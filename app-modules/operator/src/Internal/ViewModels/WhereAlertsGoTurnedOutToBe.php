<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** Where a machine's alerts go, as Where alerts go and a back-end's own page draw it. */
final readonly class WhereAlertsGoTurnedOutToBe
{
    /**
     * @param HowTheReadingWent           $went      whether the core answered, and what stopped it where not
     * @param bool                        $notSetUp  whether nothing is set up and nothing went unread
     * @param string                      $stateSaid the key for how delivery stands as a whole
     * @param string                      $stateTone the tone that standing is drawn in
     * @param list<AnAlertBackEndAsShown> $backEnds  every back-end in the core's order, or the one a page is about
     * @param array<string, string>       $unread    each back-end the core could not read, with the core's reason
     */
    public function __construct(
        public HowTheReadingWent $went,
        public bool $notSetUp,
        public string $stateSaid,
        public string $stateTone,
        public array $backEnds,
        public array $unread,
    ) {}
}
