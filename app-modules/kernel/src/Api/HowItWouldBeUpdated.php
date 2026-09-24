<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function trim;

/**
 * What would update the running copy: the exact command, or why there is none.
 *
 * The command is shown, never run from here. Where there is nothing exact to
 * type, the stack says why, and that sentence takes the command's place.
 */
final readonly class HowItWouldBeUpdated
{
    private function __construct(private string $command, private string $instead) {}

    /** Exactly what to type. */
    public static function byRunning(string $command): self
    {
        if (trim($command) === '') {
            throw ItselfSaysNothing::about('command');
        }

        return new self($command, '');
    }

    /** Why there is nothing exact to type. */
    public static function insteadBecause(string $why): self
    {
        if (trim($why) === '') {
            throw ItselfSaysNothing::about('instead');
        }

        return new self('', $why);
    }

    /** The stack said neither. */
    public static function notSaid(): self
    {
        return new self('', '');
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template TCommand of object
     * @template TInstead of object
     * @template TNeither of object
     *
     * @param Closure(string): TCommand $byRunning
     * @param Closure(string): TInstead $instead
     * @param Closure(): TNeither       $notSaid
     *
     * @return TCommand|TInstead|TNeither
     */
    public function either(Closure $byRunning, Closure $instead, Closure $notSaid): object
    {
        if ($this->command !== '') {
            return $byRunning($this->command);
        }

        return $this->instead !== '' ? $instead($this->instead) : $notSaid();
    }
}
