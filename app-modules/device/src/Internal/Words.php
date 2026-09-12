<?php

declare(strict_types=1);

namespace Modules\Device\Internal;

use Illuminate\Contracts\Translation\Translator;

use function is_string;

/**
 * One line from the catalogue, narrowed to a sentence.
 *
 * Two adapters needed this and each had its own copy of it, three lines long,
 * with a docblock in one pointing at the other for the reasoning. Two copies of
 * a decision is one decision that will be revised in one place.
 *
 * **The narrowing is the decision.** A translator answers `string|array`,
 * because a key may name a whole group rather than a line, and everything that
 * takes one of these wants a sentence — a notification body, a sentence the
 * platform prints in its own dialog. Casting an array to a string turns a
 * mistyped key into a PHP notice on somebody's phone. Answering with the key
 * instead puts the mistyped key in front of whoever is looking at the screen,
 * where it is unmistakable and costs nobody a crash.
 *
 * **It takes the translator rather than calling `__()`.** `A4` is the rule and
 * the constructor is the reason: a class that reaches the container stops
 * telling the truth about what it needs, and a reader has to run it to find
 * out. It also makes the catalogue substitutable in a test without touching
 * global state, which `A6` cares about.
 */
final readonly class Words
{
    public function __construct(private Translator $catalogue) {}

    /**
     * The line under this key, or the key itself if it names a group.
     *
     * @param array<string, string> $with
     */
    public function for(string $key, array $with = []): string
    {
        $said = $this->catalogue->get($key, $with);

        return is_string($said) ? $said : $key;
    }
}
