<?php

declare(strict_types=1);

namespace Tests\Support;

use function class_exists;
use function preg_match;

use ReflectionClass;

use function sprintf;
use function str_starts_with;

/**
 * Which envelope one of the SDK's own types hands over, read off its docblock.
 *
 * Most payloads reach a reader through `XEnvelope::in($envelope)->data`, where
 * the kind is written at the call. One does not: a log window is handed to
 * {@see \Modules\Sdk\Api\Lines} already holding its envelopes, and the only
 * statement about what is in it is the `@return list<Envelope<Data>>` on the
 * window, with a `@phpstan-import-type` above the class saying which envelope
 * `Data` came from.
 *
 * So that is what is read. The alternative was a list of the SDK's types kept
 * here by hand, which would be right until the SDK grew a second wrapper — and
 * the failure then is silent in the direction that matters: a reader nothing
 * follows reads fields the register is told nothing reads.
 *
 * It is the same notation {@see WhatTheContractDeclares} reads one file along,
 * and the same argument for reading it: what the generator writes is the only
 * statement about these shapes that is kept up to date.
 */
final readonly class WhatTheSdkHandsBack
{
    /** The namespace whose docblocks are worth asking. */
    private const string THE_SDK = 'Lemonfiber\\Sdk\\';

    /**
     * The kind of envelope a method of an SDK type answers with, if any.
     *
     * Answers with nothing for a method that hands back something else, which
     * is most of them: a window also answers with the service it is about and
     * how many lines were asked for, and neither is an envelope.
     */
    public static function from(?string $class, string $method): ?string
    {
        if ($class === null || ! str_starts_with($class, self::THE_SDK) || ! class_exists($class)) {
            return null;
        }

        $reflection = new ReflectionClass($class);

        if (! $reflection->hasMethod($method)) {
            return null;
        }

        return self::theKindNamedIn(
            (string) $reflection->getMethod($method)->getDocComment(),
            (string) $reflection->getDocComment(),
        );
    }

    /**
     * The generated envelope an `Envelope<…>` return type resolves to.
     *
     * The alias is followed rather than assumed: the SDK writes the payload
     * type as `Data` in every one of these, and a reading that took the alias
     * for the envelope would name a type that does not exist.
     *
     * The answer is held to being an envelope the contract declares, so a
     * docblock naming something else seats no payload at all rather than
     * seating one on a shape nobody has.
     */
    private static function theKindNamedIn(string $said, string $onTheClass): ?string
    {
        if (preg_match('/@return\s+(?:list<)?Envelope<(\w+)>/', $said, $handed) !== 1) {
            return null;
        }

        $from = [];

        if (preg_match(sprintf('/@phpstan-import-type\s+%s\s+from\s+(\w+)/', $handed[1]), $onTheClass, $from) !== 1) {
            return null;
        }

        return WhatTheContractDeclares::shapeOf($from[1]) === '' ? null : $from[1];
    }
}
