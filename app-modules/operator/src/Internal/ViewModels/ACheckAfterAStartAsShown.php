<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use Modules\Operator\Internal\Presenters\AgoAsShown;

/** One run of the check after a start, flattened for the restart section; an empty `$whenSaid` where none is drawn. */
final readonly class ACheckAfterAStartAsShown
{
    /**
     * @param string                  $whenSaid       the key for when it ran: after the latest restart, or an earlier start; empty where there is none
     * @param AgoAsShown              $ago            how long ago it ran
     * @param string                  $endedSaid      the key for what it came to
     * @param list<string>            $notBack        each service that did not come back, by the stack's name for it
     * @param string                  $tunnelSaid     the key for whether the tunnel came back
     * @param string                  $tunnelWords    the stack's words for the tunnel check that failed, or empty
     * @param APortAfterAStartAsShown $port           what became of the forwarded port
     * @param bool                    $notEveryClient whether a client does not take the port yet
     * @param string                  $said           the sentence the run raised, or empty
     */
    public function __construct(
        public string $whenSaid,
        public AgoAsShown $ago,
        public string $endedSaid,
        public array $notBack,
        public string $tunnelSaid,
        public string $tunnelWords,
        public APortAfterAStartAsShown $port,
        public bool $notEveryClient,
        public string $said,
    ) {}
}
