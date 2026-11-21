<?php

declare(strict_types=1);

namespace Modules\Dx\Internal;

use function array_filter;

use const ARRAY_FILTER_USE_KEY;

use function count;
use function is_array;
use function is_string;

use Modules\Sdk\Api\Fields\NewsItemsField;
use Modules\Sdk\Api\WireField;

/**
 * What is new on a stand-in stack, as a reader can take it: every kind read,
 * each request under a reference of its own, and each problem dated in digits.
 *
 * The `news-items` envelope's own declaration, corrected in the three places
 * the generated type leaves open. A synthesised onset is the word `onset`,
 * which `WhatIsListedAsNew` refuses as it would refuse any stack that wrote
 * one; a synthesised `unread` names a kind, so What's new would list nothing
 * of it; and every synthesised request carries the same reference, so two rows
 * would be one item.
 */
final readonly class WhatIsNewAsItReads
{
    /**
     * The envelope, corrected.
     *
     * @param array<string, mixed> $envelope the envelope as synthesised from the declaration
     * @param int                  $onset    when every problem began, in whole seconds since the epoch
     *
     * @return array<string, mixed>
     */
    public static function from(array $envelope, int $onset): array
    {
        $data = $envelope['data'];
        $news = is_array($data) ? $data : [];
        $problems = $news[WireField::Problems->value];
        $requests = $news[WireField::Requests->value];
        $dated = [];
        $referenced = [];

        foreach (is_array($problems) ? $problems : [] as $problem) {
            $dated[] = [...self::namedKeysOf($problem), WireField::Onset->value => $onset];
        }

        foreach (is_array($requests) ? $requests : [] as $request) {
            $referenced[] = [...self::namedKeysOf($request), NewsItemsField::Request->value => (string) (count($referenced) + 1)];
        }

        $news[WireField::Problems->value] = $dated;
        $news[WireField::Requests->value] = $referenced;
        $news[WireField::Unread->value] = [];
        $envelope['data'] = $news;

        return $envelope;
    }

    /**
     * An entry's named fields, which are all an entry carries.
     *
     * @return array<string, mixed>
     */
    private static function namedKeysOf(mixed $entry): array
    {
        return array_filter(is_array($entry) ? $entry : [], is_string(...), ARRAY_FILTER_USE_KEY);
    }
}
