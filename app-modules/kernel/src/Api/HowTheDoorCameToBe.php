<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * How the front door came to be the one it is, with what the operator named where they named one.
 *
 * `named` is the door the operator named, on both arms that have one, and
 * `because` is why a named door was refused. Each is empty where its arm
 * carries nothing.
 */
final readonly class HowTheDoorCameToBe
{
    private function __construct(private HowTheDoorWasChosen $how, private string $named, private string $because) {}

    /** Worked out from what the stack declares. */
    public static function derived(): self
    {
        return new self(HowTheDoorWasChosen::Derived, '', '');
    }

    /** Named by the operator, by the id the stack declares it under. */
    public static function byTheOperator(string $door): self
    {
        if (trim($door) === '') {
            throw TheDoorSaysNothing::about('door');
        }

        return new self(HowTheDoorWasChosen::Named, $door, '');
    }

    /** Named by the operator and refused, with why. */
    public static function refused(string $named, string $because): self
    {
        foreach (['named' => $named, 'because' => $because] as $field => $said) {
            if (trim($said) === '') {
                throw TheDoorSaysNothing::about($field);
            }
        }

        return new self(HowTheDoorWasChosen::Refused, $named, $because);
    }

    /** Which of the three it was. */
    public function how(): HowTheDoorWasChosen
    {
        return $this->how;
    }

    /** What the operator named, as they wrote it, or empty where the door was worked out. */
    public function named(): string
    {
        return $this->named;
    }

    /** Why what the operator named is not the door, or empty. */
    public function because(): string
    {
        return $this->because;
    }
}
