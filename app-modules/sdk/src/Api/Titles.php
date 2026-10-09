<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function is_array;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\TitleEnvelope;
use Modules\Kernel\Api\AnEpisode;
use Modules\Kernel\Api\ASeason;
use Modules\Kernel\Api\ATitle;
use Modules\Kernel\Api\Episodes;
use Modules\Kernel\Api\Genres;
use Modules\Kernel\Api\Holding;
use Modules\Kernel\Api\HoldingId;
use Modules\Kernel\Api\HowLongItRuns;
use Modules\Kernel\Api\Medium;
use Modules\Kernel\Api\NumberedAs;
use Modules\Kernel\Api\ReleaseIsNoDay;
use Modules\Kernel\Api\Seasons;
use Modules\Kernel\Api\WhatTheTitleIs;
use Modules\Kernel\Api\WhenItCameOut;
use Modules\Kernel\Api\WhenItWasReleased;
use Modules\Sdk\Api\Fields\TitleField;
use Modules\Sdk\Internal\Located;
use Modules\Sdk\Internal\Optional;
use Modules\Sdk\Internal\Required;
use Modules\Sdk\Internal\Wire;

use function preg_match;

/**
 * The `title` envelope, read into one title in full.
 *
 * {@see Holdings} one read over, and held to the same line: a part the core
 * does not state is unstated here, and a part stated as something else
 * refuses the whole title, because a title missing a season reads as a series
 * that has fewer than it has.
 *
 * **No title is the core's answer, not a failure.** The core answers a title
 * outside the member's limits as absent, as it does one the household does
 * not hold, so an envelope with no title is the absent answer.
 */
final readonly class Titles
{
    /** A calendar day as the core writes one, in its three parts. */
    private const string A_DAY = '/\A(\d{4})-(\d{2})-(\d{2})\z/';

    /**
     * @param Envelope<mixed> $envelope the `title` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): WhatTheTitleIs
    {
        $data = self::payload($envelope);

        if (! is_array($data)) {
            throw TitleIsUnreadable::missing(WireField::Data);
        }

        if (! array_key_exists(WireField::Title->value, $data) || $data[WireField::Title->value] === null) {
            return WhatTheTitleIs::absent();
        }

        $title = $data[WireField::Title->value];

        return is_array($title) ? WhatTheTitleIs::told(self::title($title)) : throw TitleIsUnreadable::missing(WireField::Title);
    }

    /**
     * The payload as it arrived, before anything about its shape is believed.
     *
     * @param Envelope<mixed> $envelope
     */
    private static function payload(Envelope $envelope): mixed
    {
        return TitleEnvelope::in(Wire::checked($envelope))->data;
    }

    /** @param array<mixed> $title */
    private static function title(array $title): ATitle
    {
        $refused = TitleIsUnreadable::missing(WireField::Title);

        return ATitle::of(
            self::holding($title),
            Optional::text($title, TitleField::Overview, TitleIsUnreadable::missing(TitleField::Overview)),
            self::runs($title, TitleIsUnreadable::missing(TitleField::Minutes)),
            self::genres($title),
            Optional::text($title, TitleField::Certificate, TitleIsUnreadable::missing(TitleField::Certificate)),
            self::released($title),
            Located::in($title, $refused),
            self::seasons($title),
        );
    }

    /** @param array<mixed> $title */
    private static function holding(array $title): Holding
    {
        $medium = Required::text($title, WireField::Medium, TitleIsUnreadable::missing(WireField::Medium));
        $year = Optional::number($title, WireField::Year, TitleIsUnreadable::missing(WireField::Year));

        return Holding::of(
            HoldingId::called(Required::text($title, WireField::Id, TitleIsUnreadable::missing(WireField::Id))),
            Required::text($title, WireField::Title, TitleIsUnreadable::missing(WireField::Title)),
            Medium::tryFrom($medium) ?? throw TitleIsUnreadable::missing(WireField::Medium),
            $year === null ? WhenItCameOut::unstated() : WhenItCameOut::in($year),
        );
    }

    /** @param array<mixed> $item */
    private static function runs(array $item, TitleIsUnreadable $refused): HowLongItRuns
    {
        $minutes = Optional::number($item, TitleField::Minutes, $refused);

        return $minutes === null ? HowLongItRuns::unstated() : HowLongItRuns::minutes($minutes);
    }

    /** @param array<mixed> $title */
    private static function genres(array $title): Genres
    {
        $refused = TitleIsUnreadable::missing(TitleField::Genres);
        $named = [];

        foreach (Required::rows($title, TitleField::Genres, $refused) as $genre) {
            $named[] = is_string($genre) ? $genre : throw $refused;
        }

        return Genres::of(...$named);
    }

    /** @param array<mixed> $title */
    private static function released(array $title): WhenItWasReleased
    {
        $said = Optional::text($title, TitleField::Released, TitleIsUnreadable::missing(TitleField::Released));

        if ($said === '') {
            return WhenItWasReleased::unstated();
        }

        if (preg_match(self::A_DAY, $said, $part) !== 1) {
            throw TitleIsUnreadable::missing(TitleField::Released);
        }

        try {
            return WhenItWasReleased::on((int) $part[1], (int) $part[2], (int) $part[3]);
        } catch (ReleaseIsNoDay $why) {
            throw TitleIsUnreadable::noDay($why);
        }
    }

    /** @param array<mixed> $title */
    private static function seasons(array $title): Seasons
    {
        $seasons = [];

        foreach (Required::rows($title, WireField::Seasons, TitleIsUnreadable::missing(WireField::Seasons)) as $at => $season) {
            $seasons[] = is_array($season) ? self::season($season, (int) $at) : throw TitleIsUnreadable::season((int) $at);
        }

        return Seasons::of(...$seasons);
    }

    /** @param array<mixed> $season */
    private static function season(array $season, int $at): ASeason
    {
        $refused = TitleIsUnreadable::season($at);
        $episodes = [];

        foreach (Required::rows($season, TitleField::Episodes, $refused) as $nth => $episode) {
            $episodes[] = is_array($episode) ? self::episode($episode, TitleIsUnreadable::episode($at, (int) $nth)) : throw TitleIsUnreadable::episode($at, (int) $nth);
        }

        return ASeason::of(Required::text($season, WireField::Name, $refused), Episodes::of(...$episodes));
    }

    /** @param array<mixed> $episode */
    private static function episode(array $episode, TitleIsUnreadable $refused): AnEpisode
    {
        return AnEpisode::of(
            HoldingId::called(Required::text($episode, WireField::Id, $refused)),
            Required::text($episode, WireField::Title, $refused),
            self::numbered($episode, $refused),
            self::runs($episode, $refused),
            Optional::text($episode, TitleField::Overview, $refused),
            Located::in($episode, $refused),
        );
    }

    /** @param array<mixed> $item */
    private static function numbered(array $item, TitleIsUnreadable $refused): NumberedAs
    {
        $number = Optional::number($item, WireField::Number, $refused);

        return $number === null ? NumberedAs::none() : NumberedAs::number($number);
    }
}
