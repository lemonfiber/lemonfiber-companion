<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use Modules\Operator\Internal\Presenters\AgoAsShown;

/**
 * One of the outside watcher's monitors, as the watcher screen draws it, beside what the stack says of what it covers.
 */
final readonly class AMonitorAsShown
{
    /**
     * @param string                       $name      the monitor's name
     * @param string                       $kindSaid  the key for how it looks
     * @param string                       $stateSaid the key for what it last said
     * @param string                       $tone      the tone that state is drawn in, a `Modules\Design\View\Tone` value
     * @param AgoAsShown                   $since     how long it has said so
     * @param bool                         $switchedOff whether it is switched off
     * @param AThingUncoveredAsShown       $covers    what it covers, where lemonfiber made it, or one named nothing
     * @param string                       $pathSaid  the key for which way it reaches it, or empty
     * @param AgoAsShown                   $missing   how long the heartbeat it waits for has been missing, or nothing
     * @param bool                         $neverCame whether that heartbeat has never arrived
     * @param string                       $wanted    what it was meant to watch, where it could not be set up, or empty
     * @param string                       $watches   what it watches instead, or empty
     * @param string                       $why       why, in the stack's words, or empty
     * @param string                       $stackSaid the key for what the stack says of the service it covers, or empty
     * @param bool                         $disagree  whether the two say the opposite of each other
     */
    public function __construct(
        public string $name,
        public string $kindSaid,
        public string $stateSaid,
        public string $tone,
        public AgoAsShown $since,
        public bool $switchedOff,
        public AThingUncoveredAsShown $covers,
        public string $pathSaid,
        public AgoAsShown $missing,
        public bool $neverCame,
        public string $wanted,
        public string $watches,
        public string $why,
        public string $stackSaid,
        public bool $disagree,
    ) {}
}
