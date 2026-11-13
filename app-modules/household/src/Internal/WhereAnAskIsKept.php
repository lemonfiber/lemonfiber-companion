<?php

declare(strict_types=1);

namespace Modules\Household\Internal;

use Modules\Kernel\Api\KindOfRequest;

/** Where in the house a member finds an ask no video shelf holds, as the line their page says it with: music or books, and nothing for a film or a series. */
final readonly class WhereAnAskIsKept
{
    /** The line for an album or an artist. */
    public const string IN_THE_MUSIC = 'household.your_ask.in_the_music';

    /** The line for a book, an author or a book series. */
    public const string IN_THE_BOOKS = 'household.your_ask.in_the_books';

    /** The line for this kind, or empty where a video shelf holds it or the core said no kind. */
    public static function forThe(KindOfRequest $kind): string
    {
        return match ($kind) {
            KindOfRequest::Album, KindOfRequest::Artist => self::IN_THE_MUSIC,
            KindOfRequest::Book, KindOfRequest::Author, KindOfRequest::BookSeries => self::IN_THE_BOOKS,
            KindOfRequest::Film, KindOfRequest::Series, KindOfRequest::Something => '',
        };
    }
}
