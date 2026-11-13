<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function sprintf;
use function trim;

/**
 * One title a member's search found, as the core listed it.
 *
 * The id, the kind and, for music and books, the unit together name it to the
 * core again, when it is asked for and when its poster is read. Nothing here
 * says where its artwork lives: the core serves that itself.
 */
final readonly class AFoundTitle
{
    private function __construct(
        private string $id,
        private KindOfTitle $kind,
        private string $title,
        private WhenItCameOut $year,
        private WhereAFoundTitleStands $stands,
        private WhichTitles $among,
        private string $by,
    ) {}

    /** A title the core listed, refused where its id or name is blank. */
    public static function listed(string $id, KindOfTitle $kind, string $title, WhenItCameOut $year, WhereAFoundTitleStands $stands): self
    {
        if (trim($id) === '') {
            throw FoundTitleSaysNothing::about('id');
        }

        if (trim($title) === '') {
            throw FoundTitleSaysNothing::about('title');
        }

        return new self($id, $kind, $title, $year, $stands, WhichTitles::filmsAndSeries(), '');
    }

    /** A title of music or books the core listed, with its unit and who made it where the core named somebody. */
    public static function curated(string $id, KindOfTitle $kind, UnitOfTitle $unit, string $title, string $by, WhenItCameOut $year, WhereAFoundTitleStands $stands): self
    {
        $listed = self::listed($id, $kind, $title, $year, $stands);

        return new self($listed->id, $kind, $listed->title, $year, $stands, WhichTitles::of($kind, $unit), trim($by));
    }

    /** The id the search listed it under. */
    public function id(): string
    {
        return $this->id;
    }

    /** Whether it is a film, a series, music or books. */
    public function kind(): KindOfTitle
    {
        return $this->kind;
    }

    /** Its name. */
    public function title(): string
    {
        return $this->title;
    }

    /** The year it came out, where the core knew one. */
    public function year(): WhenItCameOut
    {
        return $this->year;
    }

    /** Where it stands in the house. */
    public function stands(): WhereAFoundTitleStands
    {
        return $this->stands;
    }

    /** What it was found among: films and series, or its kind and unit of music or books. */
    public function among(): WhichTitles
    {
        return $this->among;
    }

    /**
     * Say who made it, where the core named somebody.
     *
     * @template TNamed of object
     * @template TUnnamed of object
     *
     * @param  Closure(string): TNamed $named
     * @param  Closure(): TUnnamed     $unnamed
     * @return TNamed|TUnnamed
     */
    public function by(Closure $named, Closure $unnamed): object
    {
        return $this->by === '' ? $unnamed() : $named($this->by);
    }

    /** The id, kind and unit it is named to the core by, together, which no other title shares. */
    public function named(): string
    {
        return sprintf('%s|%s|%s', $this->kind->value, $this->among->word(), $this->id);
    }
}
