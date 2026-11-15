<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;
use function str_replace;

/**
 * What kind of thing a request is for, as the core's `media` word says it.
 *
 * The core's household nouns: a film or a series for a video ask, and an album,
 * an artist, a book, an author or a book series for a music or book ask. Any
 * other word, or none, is something asked for.
 */
enum KindOfRequest: string
{
    case Film = 'film';
    case Series = 'series';
    case Album = 'album';
    case Artist = 'artist';
    case Book = 'book';
    case Author = 'author';
    case BookSeries = 'book series';
    case Something = 'something';

    private const string SAID_TO_THE_MEMBER = 'household.request_kind.%s';

    private const string ALWAYS_WAITS = 'household.always_waits.%s';

    /** The core's word as a catalogue key segment, which holds no space. */
    public function word(): string
    {
        return str_replace(' ', '_', $this->value);
    }

    /** Whether the core said what kind of thing it is. */
    public function wasSaid(): bool
    {
        return $this !== self::Something;
    }

    /** Whether it is a whole artist, author or series, which waits for the operator whatever the household's policy. */
    public function isWhole(): bool
    {
        return match ($this) {
            self::Artist, self::Author, self::BookSeries => true,
            self::Film, self::Series, self::Album, self::Book, self::Something => false,
        };
    }

    /** The catalogue key telling the operator a whole one always waits for them, or empty where it is not a whole one. */
    public function alwaysWaitsOnTheScreen(): string
    {
        return $this->isWhole() ? sprintf(self::ALWAYS_WAITS, $this->word()) : '';
    }

    /** The catalogue key for what a member calls it. */
    public function saidToTheMember(): string
    {
        return sprintf(self::SAID_TO_THE_MEMBER, $this->word());
    }
}
