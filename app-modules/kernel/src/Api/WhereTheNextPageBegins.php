<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * The place a page of a search said the next page begins at.
 *
 * Opaque to this app: it is handed back to the core as it came, and only the
 * core knows what it means.
 */
final readonly class WhereTheNextPageBegins
{
    private function __construct(private string $place) {}

    /** The place a page named, refused where it is blank. */
    public static function at(string $place): self
    {
        if (trim($place) === '') {
            throw FoundTitleSaysNothing::about('next');
        }

        return new self($place);
    }

    /** The place, as it is handed back. */
    public function said(): string
    {
        return $this->place;
    }
}
