<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\AWordInUse;
use Modules\Kernel\Api\Explaining;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheGlossary;
use Modules\Kernel\Api\WhatWasFoundOfTheWords;
use Modules\Kernel\Api\WhatWasSaidOfOneWord;

/**
 * A stack where a test says which words it explains, and which remembers being asked.
 *
 * {@see AStackThatChecksItself}' sibling one endpoint along. Asked for one
 * word, it answers from the words a test says it explains alone, which are
 * none unless {@see alsoExplaining()} names some.
 *
 * Not `readonly`: what was asked is written when the asking happens.
 */
final class AStackThatExplainsItsWords implements Explaining
{
    /** The stack it was last asked about, or nothing where it never was. */
    private ?Stack $askedAbout = null;

    /** How many times, which is how a screen that polls is caught. */
    private int $askings = 0;

    /** Whether the session it was handed carried anything — for a test to ask. */
    private bool $carried = false;

    /**
     * Every word it was asked for alone, in the order it was asked.
     *
     * @var list<string>
     */
    private array $wordsAsked = [];

    /** What it explains when asked for one word, which the glossary does not list. */
    private TheGlossary $alone;

    /**
     * @param Closure(): WhatWasFoundOfTheWords $answer
     * @param Obstacle|null $metAlone what asking it for one word meets, or nothing where it answers
     */
    private function __construct(private readonly Closure $answer, private ?Obstacle $metAlone = null)
    {
        $this->alone = TheGlossary::of();
    }

    /** A stack explaining these words. */
    public static function with(TheGlossary $words): self
    {
        return new self(static fn(): WhatWasFoundOfTheWords => WhatWasFoundOfTheWords::found($words));
    }

    /** A stack the operator could not reach, for the reason given. */
    public static function met(Obstacle $why): self
    {
        return new self(static fn(): WhatWasFoundOfTheWords => WhatWasFoundOfTheWords::met($why), $why);
    }

    /** The same stack, explaining these words as well when asked for one of them alone. */
    public function alsoExplaining(TheGlossary $words): self
    {
        $this->alone = $words;

        return $this;
    }

    /** The same stack, meeting this when asked for one word, where its glossary answered. */
    public function meetingWhenAskedAlone(Obstacle $why): self
    {
        $this->metAlone = $why;

        return $this;
    }

    /**
     * Every word it was asked for alone, in order.
     *
     * @return list<string>
     */
    public function wordsAsked(): array
    {
        return $this->wordsAsked;
    }

    /** The stack it was last asked about, or nothing where it never was. */
    public function askedAbout(): ?Stack
    {
        return $this->askedAbout;
    }

    /** How many times it was asked, which catches a screen asking twice a frame. */
    public function askings(): int
    {
        return $this->askings;
    }

    /** Whether it was handed a session with something in it. */
    public function wasGivenASession(): bool
    {
        return $this->carried;
    }

    public function glossaryOn(Stack $stack, Session $session): WhatWasFoundOfTheWords
    {
        $this->askedAbout = $stack;
        $this->askings++;

        // The session is read and the value dropped, for
        // {@see AStackThatKeepsARecord}'s reason.
        $this->carried = $session->forTheHeader() !== '';

        return ($this->answer)();
    }

    public function wordOn(Stack $stack, Session $session, AWordInUse $word): WhatWasSaidOfOneWord
    {
        $this->askedAbout = $stack;
        $this->wordsAsked[] = $word->said();

        if ($this->metAlone instanceof Obstacle) {
            return WhatWasSaidOfOneWord::met($this->metAlone);
        }

        foreach ($this->alone->explaining($word) as $entry) {
            return WhatWasSaidOfOneWord::explained($entry);
        }

        return WhatWasSaidOfOneWord::unexplained();
    }
}
