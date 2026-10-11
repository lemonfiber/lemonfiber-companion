<?php

declare(strict_types=1);

namespace Modules\Household\Internal\ViewModels;

use Modules\Kernel\Api\AFoundTitle;

/**
 * One title on a page of a member's search, as a row: its name, its year, what it is, where it stands, and what its page is opened with.
 *
 * `line`, `kind` and `stands` are catalogue keys; `year` is empty where the core knew none.
 * `found` is the title itself, for the road to its page.
 */
final readonly class AFoundTitleAsShown
{
    /** @param array<string, string> $carries what the road hands the title's page, besides its id */
    public function __construct(
        public AFoundTitle $found,
        public string $titled,
        public string $line,
        public string $year,
        public string $kind,
        public string $stands,
        public array $carries,
    ) {}

    /** The same row, where it stands said by another catalogue key. */
    public function standing(string $stands): self
    {
        return new self($this->found, $this->titled, $this->line, $this->year, $this->kind, $stands, $this->carries);
    }
}
