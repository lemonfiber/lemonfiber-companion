<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use function in_array;

use Modules\Kernel\Api\AWayToBeTold;
use Modules\Kernel\Api\ChoosingHowTheyAreTold;
use Modules\Kernel\Api\HowBeingToldCameBack;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WaysChosen;
use Modules\Kernel\Api\WaysToBeTold;
use Override;

/** A house that offers ways to be told, answers a choice as the core does, and keeps every choice it was sent. */
final class AHouseThatTellsWhenItsReady implements ChoosingHowTheyAreTold
{
    /** @var list<list<string>> */
    public array $chosen = [];

    public int $readings = 0;

    /** @param list<AWayToBeTold> $ways */
    private function __construct(
        private array $ways,
        private readonly bool $phone,
        private readonly ?Obstacle $readRefused,
        private readonly ?Obstacle $choiceRefused,
    ) {}

    public static function offering(WaysToBeTold $offered): self
    {
        $ways = [];

        foreach ($offered as $way) {
            $ways[] = $way;
        }

        return new self($ways, $offered->wakesThisPhone(), readRefused: null, choiceRefused: null);
    }

    public static function offeringNothing(): self
    {
        return new self([], phone: false, readRefused: null, choiceRefused: null);
    }

    public static function refusing(Obstacle $why): self
    {
        return new self([], phone: false, readRefused: $why, choiceRefused: null);
    }

    /** The same house, refusing every choice for this reason. */
    public function refusingChoices(Obstacle $why): self
    {
        return new self($this->ways, $this->phone, $this->readRefused, $why);
    }

    #[Override]
    public function offered(Stack $stack, Session $session): HowBeingToldCameBack
    {
        $this->readings++;

        return $this->readRefused instanceof Obstacle
            ? HowBeingToldCameBack::refused($this->readRefused)
            : HowBeingToldCameBack::told($this->offeredNow());
    }

    #[Override]
    public function choose(Stack $stack, Session $session, WaysChosen $ways): HowBeingToldCameBack
    {
        $ids = [];

        foreach ($ways as $id) {
            $ids[] = $id;
        }

        $this->chosen[] = $ids;

        if ($this->choiceRefused instanceof Obstacle) {
            return HowBeingToldCameBack::refused($this->choiceRefused);
        }

        $answered = [];

        foreach ($this->ways as $way) {
            $answered[] = AWayToBeTold::offered($way->id(), $way->name(), receiving: in_array($way->id(), $ids, strict: true));
        }

        $this->ways = $answered;

        return HowBeingToldCameBack::told($this->offeredNow());
    }

    private function offeredNow(): WaysToBeTold
    {
        return $this->phone ? WaysToBeTold::wakingThisPhone(...$this->ways) : WaysToBeTold::notWakingThisPhone(...$this->ways);
    }
}
