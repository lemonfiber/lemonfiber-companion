<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What came back when a change was put to the stack.
 *
 * **Four ways this ends and the template branches on `went` first**, as every
 * other reading on this surface does — so a session that has gone shows the
 * sign-in prompt rather than a half-drawn proposal.
 *
 * `holds` and `wrote` are two questions rather than one, because the stances
 * answer them differently: a setting that already held the value answers yes
 * to the first and no to the second, and an operator told *written* about it
 * would go looking for a restart that never happened.
 */
final readonly class WhatAChangeTurnedOutToBe
{
    public function __construct(
        public HowTheReadingWent $went,
        public string $key,
        public string $fromSaid,
        public string $toSaid,
        public bool $holdsNothingYet,
        public string $costSaid,
        public string $stanceSaid,
        public bool $mustBeAgreedFirst,
        public bool $holdsWhatWasAsked,
        public bool $wroteSomething,
        public string $refusalSaid,
    ) {}
}
