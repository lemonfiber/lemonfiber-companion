<?php

declare(strict_types=1);

namespace Modules\Dx\Internal;

use function array_filter;

use const ARRAY_FILTER_USE_KEY;

use function count;
use function is_array;
use function is_string;

use Modules\Sdk\Api\WireField;

/**
 * What is new on a stand-in stack, as a reader can take it: every kind read,
 * each request under a number of its own, and each problem dated in digits.
 *
 * The `news-items` envelope's own declaration, corrected in the three places
 * the generated type leaves open. A synthesised onset is the word `onset`,
 * which `WhatIsListedAsNew` refuses as it would refuse any stack that wrote
 * one; a synthesised `unread` names a kind, so What's new would list nothing
 * of it; and every synthesised request carries the same number, so two rows
 * would be one item.
 */
final readonly class WhatIsNewAsItReads
{
    /**
     * The envelope, corrected.
     *
     * @param array<string, mixed> $envelope the envelope as synthesised from the declaration
     * @param string               $onset    when every problem began, in seconds since the epoch written in digits
     *
     * @return array<string, mixed>
     */
    public static function from(array $envelope, string $onset): array
    {
        $data = $envelope['data'];
        // One line: `data` is always an array.
        $news = is_array($data) ? $data : [];
        // `problems` and `requests` are required by the declaration, so the
        // keys are always there; what is not known to the analyser is that
        // each holds a list.
        $problems = $news[WireField::Problems->value];
        $requests = $news[WireField::Requests->value];
        $dated = [];
        $numbered = [];

        foreach (is_array($problems) ? $problems : [] as $problem) {
            $dated[] = [...self::namedKeysOf($problem), WireField::Onset->value => $onset];
        }

        foreach (is_array($requests) ? $requests : [] as $request) {
            $numbered[] = [...self::namedKeysOf($request), WireField::Number->value => count($numbered) + 1];
        }

        $news[WireField::Problems->value] = $dated;
        $news[WireField::Requests->value] = $numbered;
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
