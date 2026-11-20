<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Modules\Kernel\Api\AnAddressToHand;
use Modules\Kernel\Api\OpeningAPage;
use Modules\Kernel\Api\WhetherThePageOpened;

/**
 * A phone that opens pages, or would not, and remembers which it was asked for.
 */
final class PagesThatOpen implements OpeningAPage
{
    /** @var list<string> every page asked for, in order */
    private array $asked = [];

    public function __construct(private readonly bool $opens = true) {}

    /** A phone that would open nothing. */
    public static function wouldNot(): self
    {
        return new self(opens: false);
    }

    public function open(AnAddressToHand $page): WhetherThePageOpened
    {
        $this->asked[] = $page->url();

        return $this->opens ? WhetherThePageOpened::opened() : WhetherThePageOpened::wouldNot();
    }

    /** @return list<string> every page asked for, in order */
    public function pagesOpened(): array
    {
        return $this->asked;
    }
}
