<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** What the stack's rehearsal of starting something came to, service by service, with its estimate. */
final readonly class TheRehearsalAsRead
{
    /**
     * @param list<AServiceToStartAsShown> $wouldStart    the services starting it would name, in the stack's order, each as one it would bring up or one already running
     * @param list<AServiceLeftOutAsShown> $leftOut       the services it would leave out, each with what it would need
     * @param int                          $estimatedMib  what the stack estimates starting it would take, in mebibytes
     * @param list<string>                 $unestimated   the services that would start with no estimate of their own
     * @param bool                         $runningUnread whether the stack could not read which of them are already running
     */
    public function __construct(
        public array $wouldStart,
        public array $leftOut,
        public int $estimatedMib,
        public array $unestimated,
        public bool $runningUnread,
    ) {}
}
