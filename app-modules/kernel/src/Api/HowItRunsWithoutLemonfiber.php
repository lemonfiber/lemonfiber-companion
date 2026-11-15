<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * How a stack runs without lemonfiber: whose stack it is, where its project and `.env` are, the words that bring it up, how any action runs without a question, and what those words leave out.
 */
final readonly class HowItRunsWithoutLemonfiber
{
    private function __construct(
        private WhoseStackItIs $whose,
        private string $project,
        private string $env,
        private TheCommandLine $plainUp,
        private string $nonInteractive,
        private ThePartsTheWordsLeaveOut $leftOut,
    ) {}

    /**
     * What the stack said: whose it is, the directory its Compose project is written to, the `.env` beside it, the `docker compose` words that bring it up, and the words any action runs with without a question being put.
     */
    public static function said(WhoseStackItIs $whose, string $project, string $env, TheCommandLine $plainUp, string $nonInteractive, ThePartsTheWordsLeaveOut $leftOut): self
    {
        return new self($whose, $project, $env, $plainUp, $nonInteractive, $leftOut);
    }

    /** Whose stack it is. */
    public function whose(): WhoseStackItIs
    {
        return $this->whose;
    }

    /** The directory the Compose project is written to. */
    public function project(): string
    {
        return $this->project;
    }

    /** The `.env` file beside it. */
    public function env(): string
    {
        return $this->env;
    }

    /** The `docker compose` words that bring the stack up with no lemonfiber running. */
    public function plainUp(): TheCommandLine
    {
        return $this->plainUp;
    }

    /** The words any action is run with without a question being put, as the command line takes them. */
    public function nonInteractive(): string
    {
        return $this->nonInteractive;
    }

    /** Every part those words alone do not bring up as lemonfiber does. */
    public function leftOut(): ThePartsTheWordsLeaveOut
    {
        return $this->leftOut;
    }
}
