<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function sprintf;
use function trim;

/**
 * What one install or removal did to the machine, as the stack reported it.
 *
 * **The standing is the stack's, read after the act.** An install is reported
 * by where the command now stands — hosted, installed and unverified, stopped —
 * and never as the install having succeeded: a definition written to disk that
 * the manager is not running keeps none of the promises the command makes.
 *
 * **Every file is carried, in both directions.** What an install wrote and what
 * a removal took back are the same list on the wire, so {@see self::did()}
 * says which of the two each file was.
 *
 * **Two constructors, because only an install starts anything and says where
 * its words go.** A removal that claimed to have started a command, or named a
 * log for one it just took back, is unspellable here.
 *
 * **A rehearsal is carried as one.** The stack says so where nothing it lists
 * actually happened, and a screen that drew a rehearsed install as a real one
 * would tell an operator a guarantee is in force that nothing is keeping.
 */
final readonly class WhatTheHandoverDid
{
    private function __construct(
        private HandingOver $did,
        private string $name,
        private bool $rehearsed,
        private bool $started,
        private HowItIsHosted $standing,
        private ?string $output,
        private TheFilesTouched $touched,
    ) {}

    /** An install, what it started, and where the stack said the command's words are written. */
    public static function installing(
        string $name,
        bool $rehearsed,
        bool $started,
        HowItIsHosted $standing,
        string $output,
        TheFilesTouched $touched,
    ): self {
        return new self(
            did: HandingOver::Install,
            name: self::said($name, 'which command'),
            rehearsed: $rehearsed,
            started: $started,
            standing: $standing,
            output: self::said($output, sprintf('where %s writes its words', $name)),
            touched: $touched,
        );
    }

    /**
     * An install whose stack did not say where the command's words are written.
     *
     * A constructor of its own rather than a nullable path on the one above
     * (`C2`): *the stack did not say* is a sentence on a screen, and a blank path
     * would read as an answer.
     */
    public static function installingWithNowhereSaid(
        string $name,
        bool $rehearsed,
        bool $started,
        HowItIsHosted $standing,
        TheFilesTouched $touched,
    ): self {
        return new self(
            did: HandingOver::Install,
            name: self::said($name, 'which command'),
            rehearsed: $rehearsed,
            started: $started,
            standing: $standing,
            output: null,
            touched: $touched,
        );
    }

    /** A removal, and every file it took back. */
    public static function removing(
        string $name,
        bool $rehearsed,
        HowItIsHosted $standing,
        TheFilesTouched $touched,
    ): self {
        return new self(
            did: HandingOver::Remove,
            name: self::said($name, 'which command'),
            rehearsed: $rehearsed,
            started: false,
            standing: $standing,
            output: null,
            touched: $touched,
        );
    }

    /** Which of the two this was. */
    public function did(): HandingOver
    {
        return $this->did;
    }

    /** lemonfiber's name for the command it acted on. */
    public function name(): string
    {
        return $this->name;
    }

    /** Whether this was a rehearsal, in which case nothing it lists happened. */
    public function wasRehearsed(): bool
    {
        return $this->rehearsed;
    }

    /** Whether the install started the command. Never true of a removal. */
    public function started(): bool
    {
        return $this->started;
    }

    /** Where the command stands now, as the stack read it after the act. */
    public function standing(): HowItIsHosted
    {
        return $this->standing;
    }

    /** Every file it wrote or took back, in the stack's order. */
    public function touched(): TheFilesTouched
    {
        return $this->touched;
    }

    /**
     * Say where the command's words are written, or say that the stack did not.
     *
     * Two arms rather than a nullable getter, for {@see Unattended::missing()}'s
     * reason: a blank printed where a path belongs reads as an answer. A removal
     * takes the second arm, since nothing is left writing anywhere.
     *
     * @template TThere of object
     * @template TUnsaid of object
     *
     * @param  Closure(string): TThere  $there
     * @param  Closure(): TUnsaid  $unsaid
     * @return TThere|TUnsaid
     */
    public function writesTo(Closure $there, Closure $unsaid): object
    {
        return $this->output === null ? $unsaid() : $there($this->output);
    }

    /** One word, less the space around it, refused where nothing is left. */
    private static function said(string $word, string $what): string
    {
        $shown = trim($word);

        if ($shown === '') {
            throw HandoverSaysNothing::where($what);
        }

        return $shown;
    }
}
