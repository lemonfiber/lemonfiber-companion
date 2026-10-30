<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\AClientsHolding;
use Modules\Kernel\Api\ADirectionHeld;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\HowFast;
use Modules\Kernel\Api\HowLong;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheLineAsChanged;
use Modules\Kernel\Api\WhatADownloadClientSaid;
use Modules\Kernel\Api\WhatTheLineRunDid;
use Modules\Kernel\Api\WhereALiftStands;
use Modules\Operator\Internal\AsText;
use Modules\Operator\Internal\ViewModels\AClientHoldingAsShown;
use Modules\Operator\Internal\ViewModels\ADirectionHeldAsShown;
use Modules\Operator\Internal\ViewModels\ALiftAsShown;
use Modules\Operator\Internal\ViewModels\ARateAsShown;
use Modules\Operator\Internal\ViewModels\TheLineChangeAsShown;
use Modules\Operator\Internal\ViewModels\TheLineChangeTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\WhereTheWorkStands;

use function sprintf;

/**
 * Where changing the line has got to, as the template draws it.
 */
final readonly class HowTheLineChangeReads
{
    /** The catalogue key, by unit, for a lift still in force. */
    private const string IN_FORCE = 'stacks.line_change.lift.in_force.%s';

    /** The catalogue key, by unit, for a lift that ran out. */
    private const string EXPIRED = 'stacks.line_change.lift.expired.%s';

    /** What a client's download is headed with. */
    private const string DOWN = 'stacks.line_change.direction.down';

    /** What a client's upload is headed with. */
    private const string UP = 'stacks.line_change.direction.up';

    /** Nothing has been asked yet. */
    public function notAsked(): TheLineChangeTurnedOutToBe
    {
        return new TheLineChangeTurnedOutToBe(WhereTheWorkStands::notAsked());
    }

    /** What was typed was not sent, and why. */
    public function notAskable(string $why): TheLineChangeTurnedOutToBe
    {
        return new TheLineChangeTurnedOutToBe(WhereTheWorkStands::answered(), notAskable: $why);
    }

    /** This phone holds no session for the stack. */
    public function signedOut(): TheLineChangeTurnedOutToBe
    {
        return new TheLineChangeTurnedOutToBe(WhereTheWorkStands::signedOut());
    }

    /** The stack is still at it. */
    public function running(): TheLineChangeTurnedOutToBe
    {
        return new TheLineChangeTurnedOutToBe(WhereTheWorkStands::running());
    }

    /** The stack has no outcome for it any more. */
    public function ended(): TheLineChangeTurnedOutToBe
    {
        return new TheLineChangeTurnedOutToBe(WhereTheWorkStands::ended());
    }

    /** The stack refused it, in its own words. */
    public function refused(ARefusalInItsWords $why): TheLineChangeTurnedOutToBe
    {
        return new TheLineChangeTurnedOutToBe(
            stands: WhereTheWorkStands::answered(),
            refused: new HowARefusalReads()->inItsWords($why),
        );
    }

    /** What stood in the way of asking. */
    public function met(Obstacle $why): TheLineChangeTurnedOutToBe
    {
        return new TheLineChangeTurnedOutToBe(WhereTheWorkStands::met($why));
    }

    /** What the run reported, read against the clock once. */
    public function answered(TheLineAsChanged $report, Instant $now): TheLineChangeTurnedOutToBe
    {
        $clients = [];

        foreach ($report as $client) {
            $clients[] = $this->client($client);
        }

        return new TheLineChangeTurnedOutToBe(
            stands: WhereTheWorkStands::answered(),
            change: new TheLineChangeAsShown(
                didSaid: $report->did()->saidOnTheScreen(),
                mayBeApplied: $report->did() === WhatTheLineRunDid::Rehearsed,
                line: new HowTheLineReads()->this($report->line(), $now),
                clients: $clients,
                lift: $this->lift($report->lift()),
            ),
        );
    }

    private function lift(WhereALiftStands $lift): ?ALiftAsShown
    {
        $shown = $lift->either(
            none: static fn(): AsText => AsText::nothing(),
            inForce: static fn(int $seconds): ALiftAsShown => self::lifted(self::IN_FORCE, $seconds, $lift->says()),
            expired: static fn(int $seconds): ALiftAsShown => self::lifted(self::EXPIRED, $seconds, $lift->says()),
        );

        return $shown instanceof ALiftAsShown ? $shown : null;
    }

    private static function lifted(string $saidIn, int $seconds, string $says): ALiftAsShown
    {
        $howLong = HowLong::ofSeconds($seconds);

        return new ALiftAsShown(
            said: sprintf($saidIn, $howLong->unit()->value),
            count: $howLong->howMany(),
            says: $says,
        );
    }

    private function client(AClientsHolding $client): AClientHoldingAsShown
    {
        $pulling = $client->pulling() === WhatADownloadClientSaid::Unsaid ? null : sprintf('stacks.pausing.said.%s', $client->pulling()->value);

        return $client->either(
            answered: fn(ADirectionHeld $down, ADirectionHeld $up): AClientHoldingAsShown => new AClientHoldingAsShown(
                name: $client->client(),
                pullingSaid: $pulling,
                directions: [$this->direction(self::DOWN, $down), $this->direction(self::UP, $up)],
                said: null,
            ),
            silent: static fn(string $said): AClientHoldingAsShown => new AClientHoldingAsShown(
                name: $client->client(),
                pullingSaid: $pulling,
                directions: [],
                said: $said,
            ),
        );
    }

    private function direction(string $said, ADirectionHeld $held): ADirectionHeldAsShown
    {
        return new ADirectionHeldAsShown(
            said: $said,
            verdictSaid: $held->verdict()->saidOnTheScreen(),
            miss: $held->verdict()->isAMiss(),
            rates: $this->rates($held),
        );
    }

    /**
     * Each rate a client gave, in what a line is sold in.
     *
     * @return list<ARateAsShown>
     */
    private function rates(ADirectionHeld $held): array
    {
        $rates = [];

        foreach ($held as $rate) {
            $fast = HowFast::of($rate->bytesASecond());
            $rates[] = new ARateAsShown(said: $rate->which()->saidOnTheScreen(), figure: $fast->figure, unit: $fast->said);
        }

        return $rates;
    }
}
