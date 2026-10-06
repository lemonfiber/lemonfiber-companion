<?php

declare(strict_types=1);

namespace Modules\Household\Internal\ViewModels;

use function is_array;
use function max;
use function mb_strlen;

use Modules\Design\Api\TypeSize;

use function preg_split;

/**
 * How large a title is lettered on its poster.
 *
 * A poster with no artwork carries its title as its picture, so a short title
 * is set large and a long one steps down until it fits the tile. The step is
 * read off two lengths: the whole title, which decides how many lines it
 * takes, and its longest word, which has to fit one line, since a word broken
 * across two lines reads as two words.
 *
 * Each step names the brand's size it is set at, and the poster's template
 * writes that size out on one branch per step; what is decided here is where
 * one step ends and the next begins, and how many lines each may take before
 * the rest is cut. The poster's screen-reader label carries the whole title,
 * so a title cut short on the tile is never cut short for somebody listening.
 */
enum HowAPosterIsLettered: string
{
    /** One or a few short words, set at the brand's medium display size. */
    case Large = 'large';

    /** A title of a sentence's length, set at the body size. */
    case Middle = 'middle';

    /** Anything longer, set at the caption size. */
    case Small = 'small';

    /** The longest title lettered large. */
    public const int LARGE_AT_MOST = 18;

    /** The longest word a title lettered large may hold, so it fits one line. */
    public const int LARGE_WORD_AT_MOST = 6;

    /** The longest title lettered at the middle step. */
    public const int MIDDLE_AT_MOST = 56;

    /** The longest word a title at the middle step may hold, so it fits one line. */
    public const int MIDDLE_WORD_AT_MOST = 11;

    /** How many lines a title at each step may take, the most a 2:3 tile has room for at that size. */
    private const array LINES_AT_MOST = ['large' => 4, 'middle' => 7, 'small' => 8];

    /** How many lines a title at each step may take on Home's hero, the most its 16:9 tile has room for. */
    private const array LINES_ON_THE_HERO = ['large' => 2, 'middle' => 4, 'small' => 6];

    /** The step a title is lettered at. */
    public static function for(string $titled): self
    {
        $length = mb_strlen($titled);
        $longestWord = self::longestWordIn($titled);

        return match (true) {
            $length <= self::LARGE_AT_MOST && $longestWord <= self::LARGE_WORD_AT_MOST => self::Large,
            $length <= self::MIDDLE_AT_MOST && $longestWord <= self::MIDDLE_WORD_AT_MOST => self::Middle,
            default => self::Small,
        };
    }

    /** The brand's size a title at this step is set at. */
    public function setAt(): TypeSize
    {
        return match ($this) {
            self::Large => TypeSize::DisplayM,
            self::Middle => TypeSize::Body,
            self::Small => TypeSize::Caption,
        };
    }

    /**
     * The brand's size a title at this step is set at on Home's hero, a step
     * up from its poster: the hero is as wide as the screen.
     */
    public function setOnTheHero(): TypeSize
    {
        return match ($this) {
            self::Large => TypeSize::DisplayL,
            self::Middle => TypeSize::DisplayM,
            self::Small => TypeSize::Body,
        };
    }

    /** How many lines a title at this step may take on Home's hero before the rest is cut. */
    public function linesOnTheHero(): int
    {
        return self::LINES_ON_THE_HERO[$this->value];
    }

    /** How many lines a title at this step may take on the tile before the rest is cut. */
    public function linesAtMost(): int
    {
        return self::LINES_AT_MOST[$this->value];
    }

    /** The length of the longest run of characters between spaces. */
    private static function longestWordIn(string $titled): int
    {
        $longest = 0;
        $words = preg_split('/\s+/u', $titled);

        foreach (is_array($words) ? $words : [] as $word) {
            $longest = max($longest, mb_strlen($word));
        }

        return $longest;
    }
}
