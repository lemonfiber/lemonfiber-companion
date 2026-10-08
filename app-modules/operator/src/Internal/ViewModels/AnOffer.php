<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use Modules\Kernel\Api\WhetherItIsOffered;

/**
 * What one button says about whether the stack offers what it asks for.
 *
 * Folded once from what the stack declared, so every button says the same
 * thing for the same answer: whether it can be pressed, the sentence beside it
 * where there is one, and the road to the stack's updates where a newer
 * lemonfiber is what would provide it. Nothing is hidden: a button the stack
 * does not offer is drawn and explained.
 */
final readonly class AnOffer
{
    private function __construct(
        public bool $offers,
        public string $note,
        public string $updatesAt,
    ) {}

    /** What a button says for this answer, with where the stack's updates are. */
    public static function of(WhetherItIsOffered $whether, string $updatesAt): self
    {
        return new self(
            offers: $whether->offersAnAction(),
            note: match ($whether) {
                WhetherItIsOffered::Offered, WhetherItIsOffered::NotKnown => '',
                WhetherItIsOffered::NotSetUp => 'connection.not_set_up',
                WhetherItIsOffered::NotTheirs => 'connection.not_for_this_account',
                WhetherItIsOffered::NeedsANewerLemonfiber => 'connection.not_on_this_stack',
            },
            updatesAt: $whether->isProvidedByAnUpdate() ? $updatesAt : '',
        );
    }
}
