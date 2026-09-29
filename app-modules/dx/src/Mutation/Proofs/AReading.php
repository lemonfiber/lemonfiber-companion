<?php

declare(strict_types=1);

namespace Dx\Mutation\Proofs;

use function in_array;
use function is_array;
use function max;
use function mb_strtolower;

/**
 * Where a reading of a file's top level has got to, one token at a time.
 *
 * `depth` counts every bracket, brace and attribute still open, so a statement
 * is at the top level only while it is zero. `opener` is the token that opened
 * the statement being read, and `naming` says that the next bare name is the
 * one a declaration declares.
 */
final readonly class AReading
{
    /** What opens a bracket, a brace or an attribute. */
    private const array OPENING = ['{', '(', '[', T_ATTRIBUTE, T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES];

    /** What closes one. */
    private const array CLOSING = ['}', ')', ']'];

    /** @param list<string> $declares */
    public function __construct(
        public int $depth = 0,
        public bool $atStart = true,
        public int|string $opener = '',
        public bool $naming = false,
        public array $declares = [],
        public bool $refused = false,
    ) {}

    /**
     * The reading once this token is read.
     *
     * @param array{0: int, 1: string, 2: int}|string $token
     * @param list<int>                               $declaring what may open a statement
     * @param list<int>                               $naming    what names the next bare name
     */
    public function then(array|string $token, array $declaring, array $naming): self
    {
        $kind = is_array($token) ? $token[0] : $token;
        $opens = $this->depth === 0 && $this->atStart;

        if ($opens && ! in_array($kind, $declaring, strict: true)) {
            return $this->refusing();
        }

        $read = $opens ? new self(0, false, $kind, false, $this->declares) : $this;

        return $read->naming($kind, is_array($token) ? $token[1] : $token, $naming)->nesting($kind);
    }

    private function refusing(): self
    {
        return new self($this->depth, $this->atStart, $this->opener, $this->naming, $this->declares, refused: true);
    }

    /** @param list<int> $naming */
    private function naming(int|string $kind, string $text, array $naming): self
    {
        if ($this->depth !== 0) {
            return $this;
        }

        if ($this->naming && $kind === T_STRING) {
            return new self(0, $this->atStart, $this->opener, false, [...$this->declares, mb_strtolower($text)]);
        }

        $names = in_array($kind, $naming, strict: true) || ($kind === ',' && $this->opener === T_CONST);

        return new self(0, $this->atStart, $this->opener, $names || $this->naming, $this->declares);
    }

    private function nesting(int|string $kind): self
    {
        if (in_array($kind, self::OPENING, strict: true)) {
            return $kind === '{' && $this->depth === 0 && in_array($this->opener, [T_NAMESPACE, T_DECLARE], strict: true)
                ? $this->refusing()
                : new self($this->depth + 1, $this->atStart, $this->opener, $this->naming, $this->declares);
        }

        if (in_array($kind, self::CLOSING, strict: true)) {
            $depth = max(0, $this->depth - 1);

            return new self($depth, $depth === 0 && $this->closes($kind), $this->opener, $this->naming, $this->declares);
        }

        return $kind === ';' && $this->depth === 0 ? new self(0, true, $this->opener, false, $this->declares) : $this;
    }

    /** Whether closing this bracket at the top level ends the statement it is in. */
    private function closes(string $kind): bool
    {
        return ($kind === '}' && $this->opener !== T_USE) || ($kind === ']' && $this->opener === T_ATTRIBUTE);
    }
}
