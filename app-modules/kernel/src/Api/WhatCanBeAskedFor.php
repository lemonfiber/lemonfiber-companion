<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;
use function in_array;

/**
 * What a member can search for and ask for, as the core lists it for them.
 *
 * Films and series are one choice, since a search looks among both at once.
 * Music and books are a choice for each unit the core lists, and a kind of
 * music or books listing no unit cannot be searched, so it offers nothing.
 */
final readonly class WhatCanBeAskedFor
{
    /** @param list<WhichTitles> $offered */
    private function __construct(private array $offered) {}

    /** Films and series alone, which is what is searched where the core's list could not be read. */
    public static function filmsAndSeriesAlone(): self
    {
        return new self([WhichTitles::filmsAndSeries()]);
    }

    /** Nothing yet, to be listed kind by kind in the core's order. */
    public static function listing(): self
    {
        return new self([]);
    }

    /** The same list, with this kind and the units the core listed under it after it. */
    public function and(KindOfTitle $kind, UnitOfTitle ...$units): self
    {
        $offered = $this->offered;

        foreach ($this->choicesOf($kind, array_values($units)) as $choice) {
            if (! in_array($choice->word(), $this->wordsOf($offered), strict: true)) {
                $offered[] = $choice;
            }
        }

        return new self($offered);
    }

    /**
     * The choice a word names, or the first of its kind, or the first offered.
     *
     * A word for a choice that is not offered names the first one that is, so
     * a choice made before the list changed never searches what the core no
     * longer lists.
     */
    public function named(string $word): WhichTitles
    {
        foreach ($this->offered as $choice) {
            if ($choice->word() === $word) {
                return $choice;
            }
        }

        foreach ($this->offered as $choice) {
            if ($choice->kindWord() === $word) {
                return $choice;
            }
        }

        return $this->offered === [] ? WhichTitles::filmsAndSeries() : $this->offered[0];
    }

    /**
     * The first choice of each kind, in the core's order.
     *
     * @return list<WhichTitles>
     */
    public function kinds(): array
    {
        $kinds = [];

        foreach ($this->offered as $choice) {
            $kinds[$choice->kindWord()] ??= $choice;
        }

        return array_values($kinds);
    }

    /**
     * Every choice of the same kind as this one, where its kind is divided into units.
     *
     * @return list<WhichTitles>
     */
    public function unitsBeside(WhichTitles $chosen): array
    {
        $units = [];

        foreach ($this->offered as $choice) {
            if ($choice->kindWord() === $chosen->kindWord() && $choice->unitWord() !== '') {
                $units[] = $choice;
            }
        }

        return $units;
    }

    /**
     * @param  list<UnitOfTitle> $units
     * @return list<WhichTitles>
     */
    private function choicesOf(KindOfTitle $kind, array $units): array
    {
        if ($kind === KindOfTitle::Film || $kind === KindOfTitle::Series) {
            return [WhichTitles::filmsAndSeries()];
        }

        $choices = [];

        foreach ($units as $unit) {
            $choices[] = WhichTitles::of($kind, $unit);
        }

        return $choices;
    }

    /**
     * @param  list<WhichTitles> $choices
     * @return list<string>
     */
    private function wordsOf(array $choices): array
    {
        $words = [];

        foreach ($choices as $choice) {
            $words[] = $choice->word();
        }

        return $words;
    }
}
