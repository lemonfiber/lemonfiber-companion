<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * What a title of music or books is within its kind, as the core's search names it.
 *
 * The wire's words, held to the contract by `EveryWireValueIsACaseTest`. A
 * film or a series carries none.
 */
enum UnitOfTitle: string
{
    /** One album. */
    case Album = 'album';

    /** Everything by one artist. */
    case Artist = 'artist';

    /** One book. */
    case Book = 'book';

    /** Everything by one author. */
    case Author = 'author';

    /** A series of books. */
    case Series = 'series';
    private const string SAID_TO_THE_MEMBER = 'household.unit.%s';

    /** The catalogue key for what a member calls it. */
    public function saidToTheMember(): string
    {
        return sprintf(self::SAID_TO_THE_MEMBER, $this->value);
    }
}
