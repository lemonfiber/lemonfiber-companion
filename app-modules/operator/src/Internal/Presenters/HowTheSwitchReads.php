<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheCommandLine;
use Modules\Kernel\Api\TheServicesNamed;
use Modules\Kernel\Api\TheSwitch;
use Modules\Kernel\Api\WhetherItWasRehearsed;
use Modules\Operator\Internal\AsText;
use Modules\Operator\Internal\ViewModels\TheSwitchAsShown;
use Modules\Operator\Internal\ViewModels\TheSwitchTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\WhereTheWorkStands;

/**
 * Where switching the active forms has got to, as the template draws it.
 */
final readonly class HowTheSwitchReads
{
    /** What a rehearsal is headed with. */
    private const string REHEARSED = 'stacks.switch.did.rehearsed';

    /** What a switch that was made is headed with. */
    private const string CARRIED_OUT = 'stacks.switch.did.carried_out';

    public function notAsked(): TheSwitchTurnedOutToBe
    {
        return new TheSwitchTurnedOutToBe(WhereTheWorkStands::notAsked());
    }

    public function notAskable(string $why): TheSwitchTurnedOutToBe
    {
        return new TheSwitchTurnedOutToBe(WhereTheWorkStands::answered(), notAskable: $why);
    }

    public function signedOut(): TheSwitchTurnedOutToBe
    {
        return new TheSwitchTurnedOutToBe(WhereTheWorkStands::signedOut());
    }

    public function running(): TheSwitchTurnedOutToBe
    {
        return new TheSwitchTurnedOutToBe(WhereTheWorkStands::running());
    }

    public function ended(): TheSwitchTurnedOutToBe
    {
        return new TheSwitchTurnedOutToBe(WhereTheWorkStands::ended());
    }

    public function refused(ARefusalInItsWords $why): TheSwitchTurnedOutToBe
    {
        return new TheSwitchTurnedOutToBe(
            stands: WhereTheWorkStands::answered(),
            refused: new HowARefusalReads()->inItsWords($why),
        );
    }

    public function met(Obstacle $why): TheSwitchTurnedOutToBe
    {
        return new TheSwitchTurnedOutToBe(WhereTheWorkStands::met($why));
    }

    /** What the run reported, headed by whether it was a rehearsal. */
    public function answered(TheSwitch $switch): TheSwitchTurnedOutToBe
    {
        $rehearsed = $switch->did() === WhetherItWasRehearsed::Rehearsed;

        return new TheSwitchTurnedOutToBe(
            stands: WhereTheWorkStands::answered(),
            change: new TheSwitchAsShown(
                didSaid: $rehearsed ? self::REHEARSED : self::CARRIED_OUT,
                mayBeApplied: $rehearsed,
                stopped: $this->names($switch->stopped()),
                started: $this->names($switch->started()),
                kept: $this->names($switch->kept()),
                stopCommand: $switch->stopCommand(
                    with: static fn(TheCommandLine $command): AsText => AsText::of($command->asTyped()),
                    none: static fn(): AsText => AsText::nothing(),
                )->said,
            ),
        );
    }

    /** @return list<string> */
    private function names(TheServicesNamed $named): array
    {
        $names = [];

        foreach ($named as $name) {
            $names[] = $name;
        }

        return $names;
    }
}
