<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * The household's one front door: where it stands, how it came to be, and what else they can reach.
 */
final readonly class TheFrontDoor
{
    private function __construct(
        private WhereTheFrontDoorStands $standing,
        private string $meaning,
        private HowTheDoorCameToBe $chosen,
        private WhereTheHouseholdBegins $begins,
        private TheServicesBeside $beside,
    ) {}

    /** What the stack said of its front door; a blank meaning is refused. */
    public static function reported(
        WhereTheFrontDoorStands $standing,
        string $meaning,
        HowTheDoorCameToBe $chosen,
        WhereTheHouseholdBegins $begins,
        TheServicesBeside $beside,
    ): self {
        if (trim($meaning) === '') {
            throw TheDoorSaysNothing::about('meaning');
        }

        return new self($standing, $meaning, $chosen, $begins, $beside);
    }

    /** Where the door stands. */
    public function standing(): WhereTheFrontDoorStands
    {
        return $this->standing;
    }

    /** What this comes to, in the stack's words. */
    public function meaning(): string
    {
        return $this->meaning;
    }

    /** Whether it was worked out, named, or named and refused. */
    public function chosen(): HowTheDoorCameToBe
    {
        return $this->chosen;
    }

    /** The service the household begins at, or nowhere. */
    public function begins(): WhereTheHouseholdBegins
    {
        return $this->begins;
    }

    /** Everything else they can reach. */
    public function beside(): TheServicesBeside
    {
        return $this->beside;
    }
}
