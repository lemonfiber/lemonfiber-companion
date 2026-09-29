<?php

declare(strict_types=1);

namespace Dx\Mutation\Proofs;

use function in_array;
use function is_array;
use function trim;

/**
 * What a PHP file does at the top level, outside every body it declares.
 *
 * A file only declares when every statement at the top level is a namespace, an
 * import, a `declare`, or the declaration of a class, interface, trait, enum,
 * function or constant — so loading it runs nothing. Anything else there, a
 * call or an assignment or text outside the PHP tags, is something the file does
 * to whatever loads it. A namespace written with braces is read as doing
 * something, because what is inside the braces is not read here.
 */
final readonly class TheTopLevel
{
    /** What may open a statement in a file that only declares. */
    private const array DECLARING = [
        T_NAMESPACE, T_USE, T_DECLARE, T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM,
        T_FUNCTION, T_CONST, T_FINAL, T_ABSTRACT, T_READONLY, T_ATTRIBUTE,
    ];

    /** What names the thing a declaration declares, in the token after it. */
    private const array NAMING = [T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM, T_FUNCTION, T_CONST];

    /** What a reading passes over. */
    private const array SILENT = [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT, T_OPEN_TAG, T_CLOSE_TAG];

    /** @param list<string> $declares every name declared at the top level, lower-cased */
    private function __construct(public bool $onlyDeclares, public array $declares) {}

    /** @param list<array{0: int, 1: string, 2: int}|string> $tokens */
    public static function of(array $tokens): self
    {
        $reading = new AReading();

        foreach ($tokens as $token) {
            $reading = self::isSilent($token) ? $reading : $reading->then($token, self::DECLARING, self::NAMING);

            if ($reading->refused) {
                return new self(onlyDeclares: false, declares: []);
            }
        }

        return new self(onlyDeclares: true, declares: $reading->declares);
    }

    /** @param array{0: int, 1: string, 2: int}|string $token */
    private static function isSilent(array|string $token): bool
    {
        return is_array($token)
            && (in_array($token[0], self::SILENT, strict: true) || ($token[0] === T_INLINE_HTML && trim($token[1]) === ''));
    }
}
