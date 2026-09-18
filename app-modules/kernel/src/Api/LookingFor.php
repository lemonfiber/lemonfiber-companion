<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * What somebody typed into a search box, and whether that is a search at all.
 *
 * A log read is searchable, and the awkward half of
 * *searchable* is the empty box. A blank term selects every line, so a screen
 * treating it as a search would announce *200 of 200 lines match* the moment
 * somebody cleared the field — which reads as a result rather than as the
 * absence of one.
 *
 * So the question is asked once, here, rather than by every caller comparing
 * against `''`. {@see self::isSearching()} is the whole of it, and a screen
 * that has one of these cannot forget to ask.
 *
 * **Trimmed on the way in.** A term with a trailing space is what a phone
 * keyboard produces after a word, and matching on it would silently find
 * nothing — the worst answer available, because it looks exactly like a service
 * that never said the thing.
 */
final readonly class LookingFor
{
    private function __construct(private string $text) {}

    /** What somebody typed, which may turn out to be nothing. */
    public static function text(string $typed): self
    {
        return new self(trim($typed));
    }

    /** Nobody is looking for anything, which is the state a screen opens in. */
    public static function nothing(): self
    {
        return new self('');
    }

    /** Whether this narrows anything. */
    public function isSearching(): bool
    {
        return $this->text !== '';
    }

    /**
     * The term, for matching with and for showing back.
     *
     * Named for what a person did rather than for what the field holds, so it
     * cannot collide with the constructor above — and because the two answer
     * different questions: {@see self::text()} is handed whatever arrived, and
     * this is what is left of it.
     */
    public function typed(): string
    {
        return $this->text;
    }
}
