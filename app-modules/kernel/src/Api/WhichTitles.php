<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function sprintf;

/**
 * What a member's search looks among: films and series, or one unit of music or books.
 *
 * Films and series are searched together and name no kind, as the core's
 * search takes them. Music and books are always searched for one unit, which
 * is what the core's curator answers for.
 */
final readonly class WhichTitles
{
    /** What films and series together are called when a member chooses them. */
    public const string FILMS_AND_SERIES = 'films-and-series';

    /** @param array{KindOfTitle, UnitOfTitle}|null $of */
    private function __construct(private ?array $of) {}

    /** Films and series, which is what a search looks among until a member chooses otherwise. */
    public static function filmsAndSeries(): self
    {
        return new self(null);
    }

    /** One unit of music or books. */
    public static function of(KindOfTitle $kind, UnitOfTitle $unit): self
    {
        return new self([$kind, $unit]);
    }

    /**
     * Say what happens either way, and get back what you built.
     *
     * @template TFilmsAndSeries of object
     * @template TOf of object
     *
     * @param  Closure(): TFilmsAndSeries             $filmsAndSeries
     * @param  Closure(KindOfTitle, UnitOfTitle): TOf $of
     * @return TFilmsAndSeries|TOf
     */
    public function either(Closure $filmsAndSeries, Closure $of): object
    {
        return $this->of === null ? $filmsAndSeries() : $of(...$this->of);
    }

    /** What it is called when a member chooses it: its kind's word, and its unit's beside it. */
    public function word(): string
    {
        return $this->of === null ? self::FILMS_AND_SERIES : sprintf('%s/%s', $this->kindWord(), $this->unitWord());
    }

    /** What its unit is called when a member chooses that unit, empty for films and series, which have none. */
    public function unitWord(): string
    {
        return $this->of === null ? '' : $this->of[1]->value;
    }

    /** What its kind is called when a member chooses that kind. */
    public function kindWord(): string
    {
        return $this->of === null ? self::FILMS_AND_SERIES : $this->of[0]->value;
    }

    /** Whether this looks among the same titles as that. */
    public function is(self $other): bool
    {
        return $this->of === $other->of;
    }
}
