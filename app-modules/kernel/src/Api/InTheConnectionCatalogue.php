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
 * end. The enums that name those outcomes hand their value here rather than
 * each spelling the group and the suffix: four spellings of one convention are
 * four places a renamed group has to be found.
 */
final readonly class InTheConnectionCatalogue
{
    /** The line that says what happened. */
    private const string SAID = 'connection.%s';

    /** The line that says what to do about it. */
    private const string REMEDY = 'connection.%s_action';

    private function __construct(private string $stem) {}

    /** The pair of lines a stem names. */
    public static function under(string $stem): self
    {
        return new self($stem);
    }

    /** The key for what happened. */
    public function said(): string
    {
        return sprintf(self::SAID, $this->stem);
    }

    /** The key for what to do about it. */
    public function remedy(): string
    {
        return sprintf(self::REMEDY, $this->stem);
    }
}
