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
     * @param string                $says  the catalogue key for the row's headline, or empty where `words` is the headline
     * @param array<string, string> $with  what the key is said with
     * @param string                $words the stack's own words, where they are the headline
     */
    private function __construct(
        public AnItem $item,
        public string $says,
        public array $with,
        public string $words,
    ) {}

    /** A release, said with its version. */
    public static function ofARelease(AnItem $item, string $version): self
    {
        return new self($item, 'news.update_headline', ['version' => $version], '');
    }

    /** A request, said by its title, or as a request where it has none yet. */
    public static function ofARequest(AnItem $item, string $title): self
    {
        return $title === ''
            ? new self($item, 'news.kind_one.request', [], '')
            : new self($item, '', [], $title);
    }

    /** A problem, said in the stack's own line. */
    public static function ofAProblem(AnItem $item, string $summary): self
    {
        return new self($item, '', [], $summary);
    }
}
