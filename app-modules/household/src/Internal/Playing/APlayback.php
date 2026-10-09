<?php

declare(strict_types=1);

namespace Modules\Household\Internal\Playing;

use Modules\Kernel\Api\AGrant;
use Modules\Kernel\Api\ATitleToPlay;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HoldingId;
use Modules\Kernel\Api\HowFarIn;
use Modules\Kernel\Api\Location;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheirLanguages;
use Modules\Kernel\Api\Whose;

/**
 * One title on the player: whose it is, on which stack, what it is and where the core said it plays.
 *
 * Kept while it plays so that where the member is can be told to the core,
 * and so that a grant the door refused can be asked for again and the title
 * put back where it stopped. It holds no grant and no session: each is asked
 * for where it is used, so neither outlives the moment it is needed.
 */
final readonly class APlayback
{
    private function __construct(
        private Stack $stack,
        private Whose $whose,
        private HoldingId $page,
        private HoldingId $played,
        private string $named,
        private Location $location,
        private Fingerprint $door,
        private bool $wasGrantedAgain,
    ) {}

    /**
     * @param HoldingId $page   the title whose page Play was pressed on
     * @param HoldingId $played what plays: that title, or one of its episodes
     */
    public static function of(Stack $stack, Whose $whose, HoldingId $page, HoldingId $played, string $named, Location $location, Fingerprint $door): self
    {
        return new self($stack, $whose, $page, $played, $named, $location, $door, wasGrantedAgain: false);
    }

    /** The same playback, put back on the player under a grant asked for again. */
    public function grantedAgain(): self
    {
        return new self($this->stack, $this->whose, $this->page, $this->played, $this->named, $this->location, $this->door, wasGrantedAgain: true);
    }

    /** What the player is handed for it, under a grant, from a place, in the languages the member chose. */
    public function toPlay(AGrant $grant, HowFarIn $from, TheirLanguages $languages): ATitleToPlay
    {
        return ATitleToPlay::of($this->location, $this->door, $grant, $from, $this->named, $languages);
    }

    public function stack(): Stack
    {
        return $this->stack;
    }

    public function whose(): Whose
    {
        return $this->whose;
    }

    /** The title whose page Play was pressed on. */
    public function page(): HoldingId
    {
        return $this->page;
    }

    /** What plays: the title, or one of its episodes. */
    public function played(): HoldingId
    {
        return $this->played;
    }

    /** Whether the door already refused one grant for it, and a second was asked for. */
    public function wasGrantedAgain(): bool
    {
        return $this->wasGrantedAgain;
    }
}
