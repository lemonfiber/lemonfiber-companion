<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * Where a connection outcome's sentence and its remedy are in the catalogue.
 *
 * Every outcome of reaching a stack — an obstacle, a pairing, a sign-in, a
 * scanner that read nothing — is said by the line its stem names in the
 * `connection` group, and advised by the line beside it with `_action` on the
 * end; one met on the way to the stack is said to a member by the line under
 * the same stem in the household's group. The enums that name those outcomes
 * hand their value here rather than each spelling the group and the suffix:
 * four spellings of one convention are four places a renamed group has to be
 * found.
 */
final readonly class InTheConnectionCatalogue
{
    /** The line that says what happened, as the operator is told it. */
    private const string SAID = 'connection.%s';

    /** The line that says what to do about it, as the operator is told it. */
    private const string REMEDY = 'connection.%s_action';

    /** The line that says what happened, as a member is told it: in the household's words. */
    private const string SAID_TO_THE_HOUSEHOLD = 'household.out_of_reach.%s';

    /** The line that says what to do about it, as a member is told it. */
    private const string REMEDY_FOR_THE_HOUSEHOLD = 'household.out_of_reach.%s_action';

    private function __construct(private string $said, private string $remedy) {}

    /** The pair of lines a stem names. */
    public static function under(string $stem): self
    {
        return new self(sprintf(self::SAID, $stem), sprintf(self::REMEDY, $stem));
    }

    /**
     * The pair of lines a stem names on a member's screen.
     *
     * A member is told the same thing in the household's words, with no
     * machine, address or software in them, since a member has none of those
     * to look at.
     */
    public static function forTheHouseholdUnder(string $stem): self
    {
        return new self(sprintf(self::SAID_TO_THE_HOUSEHOLD, $stem), sprintf(self::REMEDY_FOR_THE_HOUSEHOLD, $stem));
    }

    /** The key for what happened. */
    public function said(): string
    {
        return $this->said;
    }

    /** The key for what to do about it. */
    public function remedy(): string
    {
        return $this->remedy;
    }
}
