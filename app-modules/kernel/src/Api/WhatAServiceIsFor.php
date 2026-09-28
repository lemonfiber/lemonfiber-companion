<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One service, as the catalogue declares it: what it does for the house, what
 * the house goes without while it is down, and how much that matters.
 *
 * **What it is without is required.** A name and a description say what a
 * service is; only the cost of going without it says whether its being down
 * matters, and an entry lacking it is an inventory line rather than a
 * judgement. So none of the three words may be blank.
 */
final readonly class WhatAServiceIsFor
{
    private function __construct(
        private ServiceId $service,
        private string $name,
        private string $describes,
        private string $withoutIt,
        private HowMuchItMatters $matters,
    ) {}

    /** One service as the stack declares it, every word required. */
    public static function declared(
        ServiceId $service,
        string $name,
        string $describes,
        string $withoutIt,
        HowMuchItMatters $matters,
    ): self {
        return new self(
            $service,
            self::said('name', $name),
            self::said('describes', $describes),
            self::said('without_it', $withoutIt),
            $matters,
        );
    }

    /** Which service this is, as the stack names it. */
    public function service(): ServiceId
    {
        return $this->service;
    }

    /** What it is called in front of an operator. */
    public function name(): string
    {
        return $this->name;
    }

    /** What it does for the house, in plain language. */
    public function describes(): string
    {
        return $this->describes;
    }

    /** What the house goes without while it is down. */
    public function withoutIt(): string
    {
        return $this->withoutIt;
    }

    /** How much its absence matters. */
    public function matters(): HowMuchItMatters
    {
        return $this->matters;
    }

    /** One word, less the space around it, or the refusal naming which it was. */
    private static function said(string $field, string $word): string
    {
        $shown = trim($word);

        if ($shown === '') {
            throw CatalogueSaysNothing::about($field);
        }

        return $shown;
    }
}
