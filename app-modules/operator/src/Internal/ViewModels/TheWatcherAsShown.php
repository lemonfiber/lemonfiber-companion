<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use Modules\Operator\Internal\Presenters\AgoAsShown;

/**
 * What the outside watcher sees, as the watcher screen draws it.
 */
final readonly class TheWatcherAsShown
{
    /**
     * @param HowTheReadingWent            $went         whether the stack answered, and what stopped it where not
     * @param string                       $watcher      the plugin watching, or empty
     * @param string                       $blindBecause why the watcher does not answer, or empty where it does
     * @param AgoAsShown                   $read         how long ago the stack asked it
     * @param list<AMonitorAsShown>        $monitors     every monitor, in the stack's order
     * @param list<AThingUncoveredAsShown> $uncovered    what no active monitor covers
     */
    public function __construct(
        public HowTheReadingWent $went,
        public string $watcher,
        public string $blindBecause,
        public AgoAsShown $read,
        public array $monitors,
        public array $uncovered,
    ) {}
}
