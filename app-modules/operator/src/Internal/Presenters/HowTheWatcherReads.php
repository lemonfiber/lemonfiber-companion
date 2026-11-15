<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Design\View\Tone;
use Modules\Kernel\Api\AHeartbeat;
use Modules\Kernel\Api\AMonitor;
use Modules\Kernel\Api\AMonitorNotSetUp;
use Modules\Kernel\Api\Daemons;
use Modules\Kernel\Api\HowAMonitorStands;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WhatAMonitorCovers;
use Modules\Kernel\Api\WhatTheWatcherSees;
use Modules\Kernel\Api\WhichWayItLooks;
use Modules\Operator\Internal\AsText;
use Modules\Operator\Internal\ViewModels\AMonitorAsShown;
use Modules\Operator\Internal\ViewModels\AThingUncoveredAsShown;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\TheWatcherAsShown;

/**
 * Turns what the outside watcher sees into what the watcher screen draws, each monitor beside what the stack says of the service it covers.
 */
final readonly class HowTheWatcherReads
{
    /** This device no longer holds a session for that stack. */
    public function signedOut(): TheWatcherAsShown
    {
        return $this->nothing(HowTheReadingWent::theSessionEnded());
    }

    /** It did not answer, and this is what the operator met. */
    public function met(Obstacle $why): TheWatcherAsShown
    {
        return $this->nothing(HowTheReadingWent::somethingStopped($why));
    }

    /** The stack said what its watcher sees; `$services` is what it says of its own services, where it has been read. */
    public function this(WhatTheWatcherSees $sees, Instant $now, ?Daemons $services): TheWatcherAsShown
    {
        $monitors = [];

        foreach ($sees as $monitor) {
            $monitors[] = $this->monitor($monitor, $now, $services);
        }

        $uncovered = [];

        foreach ($sees->uncovered() as $thing) {
            $uncovered[] = self::thing($thing);
        }

        return new TheWatcherAsShown(HowTheReadingWent::itCameBack(), $sees->watcher(), $sees->blindBecause(), AgoAsShown::since($sees->readAt(), $now), $monitors, $uncovered);
    }

    private function nothing(HowTheReadingWent $went): TheWatcherAsShown
    {
        return new TheWatcherAsShown($went, '', '', AgoAsShown::saysNothing(), [], []);
    }

    private function monitor(AMonitor $monitor, Instant $now, ?Daemons $services): AMonitorAsShown
    {
        $stackSaid = '';
        $disagree = false;

        foreach ($services ?? [] as $service) {
            if ($monitor->coversTheService($service->id())) {
                $stackSaid = $service->runs()->saidOnTheScreen();
                $disagree = $monitor->stands()->disagreesWith($service->runs());
            }
        }

        $covers = $monitor->covers(
            thing: static fn(WhatAMonitorCovers $thing): AThingUncoveredAsShown => self::thing($thing),
            nothing: static fn(): AThingUncoveredAsShown => new AThingUncoveredAsShown('', ''),
        );
        $missing = $monitor->heartbeat(
            waits: static fn(AHeartbeat $heartbeat): AgoAsShown => $heartbeat->missingFrom()->either(
                at: static fn(Instant $from): AgoAsShown => $from->isBefore($now) ? AgoAsShown::since($from, $now) : AgoAsShown::saysNothing(),
                unsaid: static fn(): AgoAsShown => AgoAsShown::undated(),
            ),
            none: static fn(): AgoAsShown => AgoAsShown::saysNothing(),
        );

        $notSetUp = $monitor->setUp(
            instead: static fn(AMonitorNotSetUp $why): AMonitorNotSetUp => $why,
            asMeant: static fn(): AMonitorNotSetUp => AMonitorNotSetUp::said('', '', ''),
        );

        return new AMonitorAsShown(
            name: $monitor->name(),
            kindSaid: $monitor->kind()->saidOnTheScreen(),
            stateSaid: $monitor->stands()->saidOnTheScreen(),
            tone: $this->tone($monitor->stands()),
            since: AgoAsShown::since($monitor->since(), $now),
            switchedOff: ! $monitor->isActive(),
            covers: $covers,
            pathSaid: $monitor->reached(
                by: static fn(WhichWayItLooks $path): AsText => AsText::of($path->saidOnTheScreen()),
                unsaid: static fn(): AsText => AsText::nothing(),
            )->said,
            missing: $missing,
            neverCame: $missing->said === AgoAsShown::undated()->said,
            wanted: $notSetUp->wanted(),
            watches: $notSetUp->watches(),
            why: $notSetUp->why(),
            stackSaid: $stackSaid,
            disagree: $disagree,
        );
    }

    private static function thing(WhatAMonitorCovers $thing): AThingUncoveredAsShown
    {
        return new AThingUncoveredAsShown($thing->kind()->saidOnTheScreen(), $thing->name());
    }

    private function tone(HowAMonitorStands $stands): string
    {
        $tone = match ($stands) {
            HowAMonitorStands::Up => Tone::Fine,
            HowAMonitorStands::Down => Tone::Trouble,
            HowAMonitorStands::Pending, HowAMonitorStands::NotReporting => Tone::Unknown,
            HowAMonitorStands::Paused, HowAMonitorStands::ExpectedDown => Tone::Quiet,
        };

        return $tone->value;
    }
}
