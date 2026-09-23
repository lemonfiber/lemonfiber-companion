<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One request lemonfiber makes on its own account: where it goes, what it
 * carries, what switches it off and what that costs.
 *
 * Every word is required, because each answers a question somebody deciding
 * whether to allow it is owed: *why*, *what exactly travels*, *how do I stop
 * it* and *what breaks if I do*. A cost left blank reads as *nothing*.
 *
 * **No destination is an answer.** A request with nowhere configured to reach
 * carries an empty list, and that is not the same as switched off — a request
 * can be allowed and have nowhere to go, or off with a destination still set.
 * The two are carried apart, and neither stands in for the other.
 */
final readonly class ARequestOfOurs
{
    private function __construct(
        private WhatLemonfiberAsksFor $asksFor,
        private WhereItGoes $destinations,
        private string $purpose,
        private string $sends,
        private WhetherItIsAllowed $allowed,
        private string $switch,
        private string $cost,
    ) {}

    /** One request, as the stack describes it. */
    public static function described(
        WhatLemonfiberAsksFor $asksFor,
        WhereItGoes $destinations,
        string $purpose,
        string $sends,
        WhetherItIsAllowed $allowed,
        string $switch,
        string $cost,
    ): self {
        return new self(
            $asksFor,
            $destinations,
            self::said('purpose', $purpose),
            self::said('sends', $sends),
            $allowed,
            self::said('switch', $switch),
            self::said('cost', $cost),
        );
    }

    /** Which of lemonfiber's requests this is. */
    public function asksFor(): WhatLemonfiberAsksFor
    {
        return $this->asksFor;
    }

    /** Where it goes as this machine is configured; nowhere where nothing is. */
    public function destinations(): WhereItGoes
    {
        return $this->destinations;
    }

    /** Why lemonfiber asks. */
    public function purpose(): string
    {
        return $this->purpose;
    }

    /** Exactly what travels in the request. */
    public function sends(): string
    {
        return $this->sends;
    }

    /** Whether this machine's settings let it go out. */
    public function allowed(): WhetherItIsAllowed
    {
        return $this->allowed;
    }

    /** The setting that switches it off. */
    public function switch(): string
    {
        return $this->switch;
    }

    /** What stops working once it is off. */
    public function cost(): string
    {
        return $this->cost;
    }

    /** A word, refused where it is blank. */
    private static function said(string $field, string $word): string
    {
        if (trim($word) === '') {
            throw RequestSaysNothing::about($field);
        }

        return $word;
    }
}
