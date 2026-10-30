<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use Modules\News\Api\AnItem;

/**
 * One item of news, and how a row says it.
 *
 * A release is said by the app's own words around its version; a request by its
 * title, or by its kind where no service has a title for it yet; a problem by the
 * stack's own line for it.
 */
final readonly class WhatAnItemSays
{
    /**
     * @param ALineToSay|null $line  the line the row's headline is said in, or null where the stack's own words are the headline
     * @param string|null     $words the stack's own words, where they are the headline
     */
    private function __construct(public AnItem $item, public ?ALineToSay $line, public ?string $words = null) {}

    /** A release, said with its version. */
    public static function ofARelease(AnItem $item, string $version): self
    {
        return new self($item, new ALineToSay('news.update_headline', ['version' => $version]));
    }

    /** A request, said by its title, or as a request where it has none yet. */
    public static function ofARequest(AnItem $item, string $title): self
    {
        return $title === '' ? new self($item, new ALineToSay('news.kind_one.request', [])) : new self($item, null, $title);
    }

    /** A problem, said in the stack's own line. */
    public static function ofAProblem(AnItem $item, string $summary): self
    {
        return new self($item, null, $summary);
    }
}
