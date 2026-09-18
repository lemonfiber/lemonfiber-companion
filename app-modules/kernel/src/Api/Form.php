<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One of the groupings a stack arranges its services into.
 *
 * A value object over the wire's string for {@see ServiceId}'s reason: the set
 * is declared by the machine at runtime, so an enum would be a closed list of
 * what this build had heard of and a form it had not heard of would arrive as a
 * `tryFrom` returning null — which reads as *this stack has no such form* and
 * means *this app does not recognise it*.
 *
 * **It exists because start, stop and restart are asked for by form.** A
 * form is the unit an operator reaches for when they want everything to do with
 * media to stop, and a screen that could only act service by service would make
 * that nineteen taps and a mistake.
 *
 * The name is the identifier, carried exactly as the stack spelled it and never
 * title-cased — the same argument {@see ServiceId} makes, and for the same
 * reason: it is what an action is asked for by.
 */
final readonly class Form
{
    private function __construct(private string $named) {}

    /** One of the forms, named as the stack names it. */
    public static function called(string $form): self
    {
        $named = trim($form);

        if ($named === '') {
            throw FormIsUnnamed::whereOneWasExpected();
        }

        return new self($named);
    }

    /** The name, for showing and for asking with — they are the same string. */
    public function named(): string
    {
        return $this->named;
    }

    /**
     * Whether this is the same form as another.
     *
     * Here rather than compared at call sites, because *the same form* is one
     * decision and a screen grouping services under headings must not answer it
     * differently from the one that acts on a heading.
     */
    public function isTheSameAs(self $other): bool
    {
        return $this->named === $other->named;
    }
}
