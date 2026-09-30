<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use Closure;

use function trim;

/**
 * Which versions a stack runs, and what its running release changed, as the stack said it.
 *
 * lemonfiber, the stack it operates and the container engine under both, each
 * by the version it reports, with the release notes the copy carries beside
 * them.
 *
 * **The engine may not have answered.** The stack asks it and says nothing
 * where it could not, which is held as an empty version rather than a guess.
 *
 * **The running release comes in two arms**, because a stack that has not named
 * one and a stack running nothing are different answers, and a screen handed a
 * null would draw the second.
 */
final readonly class WhatRunsHere
{
    /** @param list<AGroupOfChanges> $changes */
    private function __construct(
        private string $lemonfiber,
        private string $stack,
        private string $engine,
        private HowTheNotesStand $notes,
        private ?Release $running,
        private array $changes,
    ) {}

    /**
     * What the stack reported, naming the release it runs and what its notes list.
     *
     * A blank lemonfiber or stack version is refused, because both are always
     * known to the copy answering; a blank engine is the engine not answering.
     */
    public static function reported(
        string $lemonfiber,
        string $stack,
        string $engine,
        HowTheNotesStand $notes,
        Release $running,
        AGroupOfChanges ...$changes,
    ): self {
        return self::checked($lemonfiber, $stack, $engine, $notes, $running, array_values($changes));
    }

    /** What the stack reported, naming no release it runs. */
    public static function namingNoRelease(string $lemonfiber, string $stack, string $engine, HowTheNotesStand $notes): self
    {
        return self::checked($lemonfiber, $stack, $engine, $notes, null, []);
    }

    /** The version of lemonfiber answering. */
    public function lemonfiber(): string
    {
        return $this->lemonfiber;
    }

    /** The version of the stack it operates. */
    public function stack(): string
    {
        return $this->stack;
    }

    /** What the container engine reports, or empty where it could not be asked. */
    public function engine(): string
    {
        return $this->engine;
    }

    /** Whether the notes describe the copy answering. */
    public function notes(): HowTheNotesStand
    {
        return $this->notes;
    }

    /**
     * The release that is running and what its notes list, or that the stack named none.
     *
     * @template TNamed of object
     * @template TNotNamed of object
     *
     * @param Closure(Release, list<AGroupOfChanges>): TNamed $named
     * @param Closure(): TNotNamed $notNamed
     *
     * @return TNamed|TNotNamed
     */
    public function running(Closure $named, Closure $notNamed): object
    {
        return $this->running instanceof Release
            ? $named($this->running, $this->changes)
            : $notNamed();
    }

    /** @param list<AGroupOfChanges> $changes */
    private static function checked(
        string $lemonfiber,
        string $stack,
        string $engine,
        HowTheNotesStand $notes,
        ?Release $running,
        array $changes,
    ): self {
        if (trim($lemonfiber) === '') {
            throw VersionIsBlank::ofWhatRuns('lemonfiber');
        }

        if (trim($stack) === '') {
            throw VersionIsBlank::ofWhatRuns('stack');
        }

        return new self($lemonfiber, $stack, trim($engine), $notes, $running, $changes);
    }
}
