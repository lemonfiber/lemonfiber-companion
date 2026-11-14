<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Giving back one category of the room on offer, agreed to against the offer the operator was shown.
 */
final readonly class ARoomGivenBackAgreed
{
    private function __construct(private string $agreement, private WhatALineIsAbout $category) {}

    /** Agreed to that category of that offer; a category the offer does not hold is refused. */
    public static function to(TheRoomOnOffer $offer, WhatALineIsAbout $category): self
    {
        if (! $offer->holds($category)) {
            throw ARoomGivenBackWasNotOffered::as($category);
        }

        return new self($offer->agreement(), $category);
    }

    /** The name of the offer agreed to. */
    public function agreement(): string
    {
        return $this->agreement;
    }

    /** The one category agreed to. */
    public function category(): WhatALineIsAbout
    {
        return $this->category;
    }
}
