<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One step of a recipe that ran, and what it came to.
 */
final readonly class AStepThatRan
{
    private function __construct(
        private string $step,
        private string $to,
        private WhatAStepCameTo $came,
        private string $why,
    ) {}

    /**
     * The step by its id, where it calls by the manifest's name, what it came to, and why where the stack says.
     *
     * A blank step or destination is refused.
     */
    public static function reported(string $step, string $to, WhatAStepCameTo $came, string $why): self
    {
        foreach (['step' => $step, 'to' => $to] as $field => $said) {
            if (trim($said) === '') {
                throw PluginSaysNothing::about($field);
            }
        }

        return new self(trim($step), trim($to), $came, trim($why));
    }

    /** The step's id. */
    public function step(): string
    {
        return $this->step;
    }

    /** Where it calls, by the name the manifest gives it. */
    public function to(): string
    {
        return $this->to;
    }

    /** What it came to. */
    public function came(): WhatAStepCameTo
    {
        return $this->came;
    }

    /** Why it came to that, in the stack's words; empty where the stack says nothing. */
    public function why(): string
    {
        return $this->why;
    }
}
