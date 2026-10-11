<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One title a member's search found, as the core listed it.
 *
 * The id and the kind together name it to the core again, when it is asked for
 * and when its poster is read. Nothing here says where its artwork lives: the
 * core serves that itself.
 */
final readonly class AFoundTitle
{
    private function __construct(
        private string $id,
        private KindOfTitle $kind,
        private string $title,
        private WhenItCameOut $year,
        private WhereAFoundTitleStands $stands,
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

        return new self($id, $kind, $title, $year, $stands);
    }

    /** The id the search listed it under. */
    public function id(): string
    {
        return $this->id;
    }

    /** Whether it is a film or a series. */
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
}
