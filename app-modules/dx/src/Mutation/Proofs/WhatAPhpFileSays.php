<?php

declare(strict_types=1);

namespace Dx\Mutation\Proofs;

use function array_any;
use function array_key_last;
use function explode;
use function implode;
use function in_array;
use function is_array;
use function mb_strtolower;
use function preg_match_all;
use function str_contains;
use function str_replace;
use function token_get_all;

/**
 * What one PHP file declares and what it names, read for a mutation proof.
 *
 * A proof narrows the tests a verdict reads to the ones that judge it, and the
 * support those tests use is found by name: a fake is in the proof of every
 * unit whose tests mention the fake's class. That is only sound for a file that
 * does nothing but declare, because a file that runs something when it is
 * loaded — a Pest bootstrap, a dataset, a helper that registers itself — acts on
 * tests that never name it. So a file is read for three things: whether it only
 * declares, the names it declares, and every name and string it mentions.
 *
 * A mention is read generously on purpose. Every identifier counts, and so does
 * every word in a string literal, with a hyphenated word also read joined up —
 * a container binding written as a string and a component written as a tag are
 * both names. Reading too much puts a file in a proof it did not need to be in,
 * which costs a shard run; reading too little would leave one out.
 */
final readonly class WhatAPhpFileSays
{
    /** What a name is spelled with once its namespace is taken off. */
    private const array NAME_TOKENS = [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED, T_NAME_RELATIVE];

    /** The tokens that carry text rather than code. */
    private const array TEXT_TOKENS = [T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE, T_INLINE_HTML];

    /**
     * @param list<string>        $declares every class, interface, trait, enum, function and constant it declares, lower-cased
     * @param array<string, true> $names    every name and word it mentions, lower-cased
     * @param string              $text     every string literal it holds, lower-cased
     */
    private function __construct(
        public bool $onlyDeclares,
        public array $declares,
        public array $names,
        public string $text,
    ) {}

    public static function of(string $source): self
    {
        $tokens = token_get_all($source);
        $read = self::mentioned($tokens);
        $topLevel = TheTopLevel::of($tokens);

        return new self(
            onlyDeclares: $topLevel->onlyDeclares,
            declares: $topLevel->declares,
            names: $read['names'],
            text: $read['text'],
        );
    }

    /**
     * Whether any of its string literals mentions one of these, ignoring case.
     *
     * @param list<string> $mentions
     */
    public function mentionsAnyOf(array $mentions): bool
    {
        return array_any($mentions, fn(string $mention): bool => str_contains($this->text, mb_strtolower($mention)));
    }

    /**
     * Every name the tokens mention, and the text of their string literals.
     *
     * @param  list<array{0: int, 1: string, 2: int}|string>        $tokens
     * @return array{names: array<string, true>, text: string}
     */
    private static function mentioned(array $tokens): array
    {
        $names = [];
        $text = [];

        foreach ($tokens as $token) {
            if (! is_array($token)) {
                continue;
            }

            $lower = mb_strtolower($token[1]);

            if (in_array($token[0], self::NAME_TOKENS, strict: true)) {
                $names[self::lastSegmentOf($lower)] = true;
            }

            if (in_array($token[0], self::TEXT_TOKENS, strict: true)) {
                $text[] = $lower;
                $names += self::wordsIn($lower);
            }
        }

        return ['names' => $names, 'text' => implode("\n", $text)];
    }

    private static function lastSegmentOf(string $name): string
    {
        $segments = explode('\\', $name);

        return $segments[array_key_last($segments)];
    }

    /**
     * Every word in some text, and every hyphenated run of words joined up.
     *
     * @return array<string, true>
     */
    private static function wordsIn(string $text): array
    {
        preg_match_all('/[a-z0-9_]+(?:-[a-z0-9_]+)*/u', $text, $found);

        $words = [];

        foreach ($found[0] as $run) {
            $words[str_replace('-', '', $run)] = true;

            foreach (explode('-', $run) as $word) {
                $words[$word] = true;
            }
        }

        return $words;
    }
}
