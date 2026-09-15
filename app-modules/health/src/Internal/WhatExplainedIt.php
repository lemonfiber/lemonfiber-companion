<?php

declare(strict_types=1);

namespace Modules\Health\Internal;

use Modules\Kernel\Api\Check;

/**
 * The check that explains a finding, carried out of an `either()` arm as a name.
 *
 * {@see \Modules\Kernel\Api\Because::either()} answers with an object so that a
 * caller cannot read *nothing explains this* as *something does, and it is
 * blank*. That is right for the value and awkward for a query that has to ask
 * the same question of every finding twice — so it is asked once here and the
 * answer travels as a name.
 *
 * The empty string means nothing explains it. Safe only because this never
 * leaves the module: a check's name is never blank, and a caller outside would
 * have to be told that rule rather than being handed a type that holds it.
 */
final readonly class WhatExplainedIt
{
    private function __construct(public string $check) {}

    public static function theCheck(Check $check): self
    {
        return new self($check->shown());
    }

    /** Nothing explains this finding; it stands on its own. */
    public static function nothing(): self
    {
        return new self('');
    }

    public function isExplained(): bool
    {
        return $this->check !== '';
    }
}
