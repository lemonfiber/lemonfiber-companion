<?php

declare(strict_types=1);

namespace Modules\Health\Api;

use function trim;

/**
 * A stable identifier for the thing that was checked.
 *
 * `vpn.egress-match`, `storage.one-filesystem`. Stable is the word that
 * matters: it is what a finding is recognised by between runs, so that "this
 * is the same problem as yesterday" is a comparison rather than a guess at two
 * titles that happen to match.
 *
 * A type rather than a string because a check and a title are both strings and
 * both arrive on the same finding. Passing one where the other belongs
 * compiles, ships, and shows the operator `vpn.egress-match` where a sentence
 * should be.
 */
final readonly class Check
{
    private function __construct(private string $check) {}

    public static function of(string $check): self
    {
        $trimmed = trim($check);

        if ($trimmed === '') {
            throw CheckIsUnnamed::inAReport();
        }

        return new self($trimmed);
    }

    public function shown(): string
    {
        return $this->check;
    }

    public function is(self $other): bool
    {
        return $this->check === $other->check;
    }
}
