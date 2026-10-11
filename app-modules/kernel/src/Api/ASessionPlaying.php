<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function trim;

/**
 * One session the media server is playing: who is watching, what, on which device, and whether it is paused.
 *
 * An episode carries its series, and the season and episode where the media
 * server numbered them.
 */
final readonly class ASessionPlaying
{
    private function __construct(
        private string $member,
        private string $title,
        private string $device,
        private bool $paused,
        private string $series,
        private ?int $season,
        private ?int $episode,
    ) {}

    /** A session playing a title that belongs to no series. */
    public static function of(string $member, string $title, string $device, bool $paused): self
    {
        return new self(self::said($member, 'member'), self::said($title, 'title'), self::said($device, 'device'), $paused, '', null, null);
    }

    /** A session playing an episode of a series the media server did not number. */
    public static function ofAnEpisode(string $member, string $title, string $device, bool $paused, string $series): self
    {
        return new self(self::said($member, 'member'), self::said($title, 'title'), self::said($device, 'device'), $paused, self::said($series, 'series'), null, null);
    }

    /** A session playing an episode of a series, by its season and its number in it. */
    public static function ofANumberedEpisode(string $member, string $title, string $device, bool $paused, string $series, int $season, int $episode): self
    {
        return new self(self::said($member, 'member'), self::said($title, 'title'), self::said($device, 'device'), $paused, self::said($series, 'series'), $season, $episode);
    }

    public function member(): string
    {
        return $this->member;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function device(): string
    {
        return $this->device;
    }

    public function isPaused(): bool
    {
        return $this->paused;
    }

    /**
     * What is playing, by whether it is an episode and how far it is numbered.
     *
     * @template R of object
     *
     * @param Closure(string): R                   $alone    the title, belonging to no series
     * @param Closure(string, string): R           $inSeries the series and the episode's title, unnumbered
     * @param Closure(string, int, int, string): R $numbered the series, its season and episode, and the episode's title
     *
     * @return R
     */
    public function what(Closure $alone, Closure $inSeries, Closure $numbered): object
    {
        return match (true) {
            $this->series === '' => $alone($this->title),
            $this->season !== null && $this->episode !== null => $numbered($this->series, $this->season, $this->episode, $this->title),
            default => $inSeries($this->series, $this->title),
        };
    }

    private static function said(string $word, string $field): string
    {
        $said = trim($word);

        return $said === '' ? throw SessionPlayingSaysNothing::about($field) : $said;
    }
}
