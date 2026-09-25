<?php

declare(strict_types=1);

namespace Tests\Support;

use function array_key_exists;
use function array_keys;
use function array_unique;
use function array_values;

use PhpParser\Node\Expr\StaticCall;
use RuntimeException;

use function sort;
use function sprintf;
use function strpos;
use function substr;

/**
 * Which path on which envelope the readers actually reach for.
 *
 * A name is not a place, and {@see \Modules\Sdk\Api\WireField} is a flat set of
 * names. A rule that asks *has this app got a word for `state`* answers yes for
 * `error.state`, `status.services[].state`, `doctor.findings[].verdict.state`
 * and `update.changelog.state` alike, on the strength of one case written for
 * one of them. Twenty-nine names do that across ninety-nine of the two hundred
 * and twenty-three paths the contract declares here.
 *
 * The last of those four is the whole argument. `update.changelog.state` is the
 * field {@see WhatTheContractAccepts} exists because a reader misread — the
 * top-level `state` taken for the triple the contract puts under `changelog` —
 * and a register meant to catch exactly that could not see it, because a case
 * named for the `status` envelope's `state` had already answered for it.
 *
 * So the question here is *does anything read this path*, and the path is
 * recovered the way a reader builds it: the payload is seated at the envelope
 * it came out of, `$held = $said[WireField::X->value]` carries the path of
 * `$said` with `x` on the end, `foreach ($listed as $one)` steps into an entry,
 * and an argument handed to a helper arrives bound to what the call site had.
 * The answer is a set of paths — `UpdateEnvelope.changelog.state` — which is
 * something a contract path can be compared against and a name never was.
 *
 * **What it cannot seat, it says.** A reach whose base this could not place is
 * reported by {@see unseated()}, and the rule that asks treats it as fatal.
 * Dropping it would put a field that *is* read in front of somebody as a field
 * nothing reads, and the register's answer to that is a row explaining why it
 * is not read — a false decision, written down, that outlives everyone who
 * could have spotted it. A red suite naming the reader and the line is the
 * cheaper failure, and it is the argument {@see Tree} makes about a checker
 * that quietly finds nothing to check.
 *
 * @phpstan-import-type Reader from EveryReaderOfTheWire
 * @phpstan-import-type Readers from EveryReaderOfTheWire
 * @phpstan-import-type Answers from WhatAReaderNames
 * @phpstan-import-type Bindings from WhatAReaderNames
 * @phpstan-type Contexts array<string, array{key: string, bindings: Bindings}>
 * @phpstan-type Following array{contexts: Contexts, answers: Answers}
 */
final readonly class WhatTheReadersRead
{
    /**
     * How many times the whole following is worked through before it is refused.
     *
     * Each pass carries an answer one call deeper, so the number needed is the
     * depth of the deepest chain of readers: fifteen, as these are written. The
     * loop stops as soon as a pass changes nothing, so this is a ceiling rather
     * than a count, and it sits far above the need on purpose — a ceiling the
     * work is nearly touching is one a single new helper turns into a silently
     * shorter answer.
     *
     * Reaching it raises rather than answering. A following that has not
     * settled is one still finding fields, and the fields it has not found yet
     * are the ones the register would be told nothing reads.
     */
    private const int THE_MOST_PASSES = 64;

    /**
     * Every path on an envelope that something in the SDK module reads.
     *
     * @return list<string>
     */
    public static function paths(): array
    {
        $read = array_keys(self::reading()['read']);

        sort($read);

        return $read;
    }

    /**
     * Every envelope a reader was seated on.
     *
     * The roots of the paths above. An envelope a reader opens and this does
     * not answer with is one the following stopped short of, which is the
     * difference between a rule that read the readers and a rule that read
     * nothing.
     *
     * @return list<string>
     */
    public static function envelopes(): array
    {
        $named = [];

        foreach (self::paths() as $path) {
            $named[] = self::theRootOf($path);
        }

        $named = array_values(array_unique($named));

        sort($named);

        return $named;
    }

    /**
     * Every wire name a reader reaches for that this could not place.
     *
     * Held against the reaches found in the sources rather than against the
     * walk's own account of itself, which is the point: a reach the walk never
     * arrived at is, from inside the walk, indistinguishable from one that is
     * not there.
     *
     * @return list<string>
     */
    public static function unseated(): array
    {
        $seated = self::reading()['seated'];
        $missed = [];

        foreach (EveryReaderOfTheWire::all() as $key => $reader) {
            foreach ($reader['reaches'] as $reach) {
                $named = TheWireNameAtASubscript::reachedForBy($reach);

                if ($named !== null && ! array_key_exists(EveryReaderOfTheWire::siteOf($reach), $seated)) {
                    $missed[] = sprintf(
                        '%s reaches for `%s` at %s:%d',
                        $key,
                        $named,
                        EveryReaderOfTheWire::below($reader['file']),
                        $reach->getStartLine(),
                    );
                }
            }
        }

        sort($missed);

        return $missed;
    }

    /**
     * Every path read, and every reach that was placed to read it.
     *
     * @return array{read: array<string, true>, seated: array<string, true>}
     */
    private static function reading(): array
    {
        $readers = EveryReaderOfTheWire::all();
        $following = self::followed($readers);
        $read = [];
        $seated = [];

        foreach ($following['contexts'] as $context) {
            $reader = $readers[$context['key']];
            $bindings = WhatAReaderNames::settled($reader, $context['bindings'], $readers, $following['answers']);

            foreach ($reader['reaches'] as $reach) {
                if (! EveryReaderOfTheWire::readsAField($reach)) {
                    continue;
                }

                foreach (WhereAnExpressionPoints::paths($reach, $bindings, $readers, $following['answers']) as $path) {
                    $read[$path] = true;
                    $seated[EveryReaderOfTheWire::siteOf($reach)] = true;
                }
            }
        }

        return ['read' => $read, 'seated' => $seated];
    }

    /**
     * Every reader, under every set of bindings it is reached with.
     *
     * A settling rather than a recursion. Each pass reads the answers the last
     * one worked out, which is what lets a call be looked up instead of
     * followed — so a reader that calls itself ends the walk by answering with
     * what it answered last time rather than by not ending it.
     *
     * A pass that changes nothing is the end of it. Contexts only ever arrive,
     * so the loop cannot oscillate; the ceiling is for the shape nobody has
     * written yet.
     *
     * @param Readers $readers
     *
     * @return Following
     */
    private static function followed(array $readers): array
    {
        $following = ['contexts' => self::whereAPayloadEnters($readers), 'answers' => []];

        for ($pass = 0; $pass < self::THE_MOST_PASSES; $pass++) {
            $grown = self::grown($following, $readers);

            if ($grown === $following) {
                return $following;
            }

            $following = $grown;
        }

        throw new RuntimeException(sprintf(
            'The reading of %s was still finding fields after %d passes, so what it has is short of '
            . 'what the readers read. Every field it has not reached yet is one the register would be '
            . 'told nothing reads, which is a row saying why a field that is read is not.',
            EveryReaderOfTheWire::WHERE,
            self::THE_MOST_PASSES,
        ));
    }

    /**
     * One pass: what every context so far answers, and where each of them calls.
     *
     * @param Following $following
     * @param Readers   $readers
     *
     * @return Following
     */
    private static function grown(array $following, array $readers): array
    {
        foreach ($following['contexts'] as $signature => $context) {
            $reader = $readers[$context['key']];
            $bindings = WhatAReaderNames::settled($reader, $context['bindings'], $readers, $following['answers']);

            $following['answers'][$signature] = WhatAReaderNames::handsBack(
                $reader,
                $bindings,
                $readers,
                $following['answers'],
            );

            $following['contexts'] = self::reachedFrom($following, $reader, $bindings, $readers);
        }

        return $following;
    }

    /**
     * The contexts one reader's calls lead to, beside the ones already found.
     *
     * @param Following $following
     * @param Reader    $reader
     * @param Bindings  $bindings
     * @param Readers   $readers
     *
     * @return Contexts
     */
    private static function reachedFrom(array $following, array $reader, array $bindings, array $readers): array
    {
        $contexts = $following['contexts'];

        foreach ($reader['reaches'] as $reach) {
            if (! $reach instanceof StaticCall) {
                continue;
            }

            [$key, $given] = WhatAReaderNames::theCallLeadsTo($reach, $bindings, $readers, $following['answers']);

            if (array_key_exists($key, $readers) && ! WhereAShapeHoldsItself::isEnteredAgain($given)) {
                $contexts[WhatAReaderNames::signature($key, $given)] = ['key' => $key, 'bindings' => $given];
            }
        }

        return $contexts;
    }

    /**
     * The methods a payload enters this app through.
     *
     * A method handed something of the SDK's, and nothing else. Every other
     * reader is reached from one of those through the calls it makes, and
     * seeding the rest as well would read a helper with nothing bound to its
     * parameters — which reports every subscript in it as unplaceable, a
     * complaint about the seeding rather than about the reader.
     *
     * @param Readers $readers
     *
     * @return Contexts
     */
    private static function whereAPayloadEnters(array $readers): array
    {
        $contexts = [];

        foreach ($readers as $key => $reader) {
            $handed = WhatAReaderNames::whatTheSdkHandsIn($reader);

            if ($handed === []) {
                continue;
            }

            $bindings = WhatAReaderNames::nothingBound($handed);
            $bindings['within'] = WhatAReaderNames::theClassIn($key);

            $contexts[WhatAReaderNames::signature($key, $bindings)] = ['key' => $key, 'bindings' => $bindings];
        }

        return $contexts;
    }

    /** The envelope a path is rooted at. */
    private static function theRootOf(string $path): string
    {
        $at = strpos($path, '.');

        return $at === false ? $path : substr($path, 0, $at);
    }
}
