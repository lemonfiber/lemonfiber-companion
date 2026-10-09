<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * The sentence and the remedy the core wrote for the household with a refusal, or that it wrote none.
 *
 * Every refusal the core answers a member's session with carries a sentence
 * written for the household and a remedy a member can act on, and
 * a member is told those rather than this app's own lines, because the core
 * knows what it refused and this app only knows its code. **Carried as text
 * and never read**: nothing here is matched, split or looked up, so a
 * sentence the core rewords reaches the member as the core wrote it.
 *
 * Only an obstacle the core answered can carry one. One met before it
 * answered (no network, a certificate that was not the one paired) has no
 * words of the core's, and is said in this app's.
 */
final readonly class WhatTheHouseholdWasTold
{
    private function __construct(private string $sentence, private string $remedy) {}

    /** The core's sentence and remedy; a blank sentence is refused, and a blank remedy is none. */
    public static function said(string $sentence, string $remedy): self
    {
        $sentence = trim($sentence);

        if ($sentence === '') {
            throw RefusalSaysNothing::about('summary');
        }

        return new self($sentence, trim($remedy));
    }

    /** The core wrote nothing for the household, or never answered. */
    public static function nothing(): self
    {
        return new self('', '');
    }

    /** Whether the core wrote anything for the household. */
    public function wasSaid(): bool
    {
        return $this->sentence !== '';
    }

    /** The core's sentence, or empty where it wrote none. */
    public function sentence(): string
    {
        return $this->sentence;
    }

    /** The core's remedy, or empty where it gave none. */
    public function remedy(): string
    {
        return $this->remedy;
    }
}
