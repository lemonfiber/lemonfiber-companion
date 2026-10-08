<?php

declare(strict_types=1);

namespace Modules\Dx\Internal;

use function array_key_exists;
use function array_unique;
use function array_values;
use function count;
use function in_array;
use function mb_str_split;
use function mb_strlen;
use function mb_substr;

use Modules\Dx\Adapters\TheInstalledPackage;

use function preg_match;
use function preg_match_all;
use function preg_replace_callback;
use function sprintf;
use function str_starts_with;
use function trim;

/**
 * The shape a generated envelope declares, read as the notation it is written
 * in rather than as the payload it describes.
 *
 * The half of {@see WhatTheContractAccepts} that never looks at a payload. One
 * class knows what `array{a: 'x'|'y', b?: list<…>}` means and the other knows
 * what to do about a fixture that disagrees with it, and keeping them apart is
 * what stops a change to either being a change to both — the notation is the
 * SDK generator's, and what a suite does about a mismatch is this repository's.
 *
 * Nothing here is specific to a contract. It is a type-notation reader, which
 * is why none of its methods mention a stack, a fixture or a complaint.
 */
final readonly class WhatTheContractDeclares
{
    /**
     * How an entry of a list or a map is written into a path.
     *
     * One marker for both kinds, and public because the two sides of a path
     * comparison have to spell it the same way: {@see WhatTheReadersRead}
     * recovers the reader's half and this declares the contract's, and a marker
     * written out in each would be one edit away from two vocabularies that
     * never match.
     */
    public const string EACH = '[]';

    /**
     * Where inside the package the generated envelopes sit.
     *
     * Relative to the package root rather than absolute, because finding that
     * root is {@see TheInstalledPackage}'s job and this class's knowledge stops
     * at *which directory of it*.
     */
    private const string GENERATED = 'src/Generated';

    /**
     * The file under the generated tree that names every shape more than one
     * kind carries, which an envelope imports rather than repeats.
     */
    private const string SHARED = 'Shapes.php';

    /**
     * What a name stands for when spelling it out would never end.
     *
     * A shape that held itself would carry its own name inside its
     * declaration, and following that name would never end. The level that is
     * spelled out carries every field; below it the contract allows anything,
     * which is what `mixed` says, and the word the package writes itself where
     * one of its shapes would hold its own.
     */
    private const string ITSELF_AGAIN = 'mixed';

    /**
     * A quoted literal, or a name in a type: a word that starts in capitals and
     * is not a field, because a field is followed by its colon.
     */
    private const string A_NAME_OR_A_LITERAL = '/\'[^\']*\'|\b[A-Z]\w*\b(?!\??:)/';

    /**
     * The payload shape one envelope declares, as text, with every name spelled out.
     *
     * The `@phpstan-type Data` line and nothing else: every docblock in that
     * package quotes field names in prose, so anything wider reads an
     * explanation as a declaration.
     *
     * The package names a shape once and refers to it everywhere else — in
     * `Shapes` for one more than one kind carries, beside the envelope for one
     * only that kind carries — so the line says `Data StatusReport` and the
     * fields are two hops away. Every reader of this answer walks brackets, and
     * a name has none, so the names are replaced by what they stand for here,
     * once, rather than taught to each reader.
     */
    public static function shapeOf(string $envelope): string
    {
        $file = sprintf('%s.php', $envelope);

        return self::spelledOut(
            self::whatIsDeclared($file, '/@phpstan-type\sData\s(.*)/'),
            [...self::namedIn(self::SHARED), ...self::namedIn($file)],
        );
    }

    /**
     * The kind one envelope reads, as the word that kind is on the wire.
     *
     * Two hops, because the package keeps them apart: the envelope names a case
     * of the generated enum, and the enum holds the word. Read rather than
     * mapped, for the reason every other reading here is: a map written beside
     * this one is a second copy of the contract's answer, and the copy is what
     * goes on agreeing after the contract has moved.
     */
    public static function kindOf(string $envelope): string
    {
        $case = self::whatIsDeclared(
            sprintf('%s.php', $envelope),
            '/public\sconst\sKind\sKIND\s=\sKind::(\w+);/',
        );

        return $case === ''
            ? ''
            : self::whatIsDeclared('Kind.php', sprintf('/case %s = \'([^\']+)\';/', $case));
    }

    /**
     * The fields one `array{…}` declares, and whether each is required.
     *
     * A name the notation cannot write bare, such as one with a hyphen in it,
     * arrives quoted, and is read without its quotes: the wire's word is the
     * one inside them.
     *
     * @return array<string, array{0: bool, 1: string}>
     */
    public static function fieldsOf(string $type): array
    {
        $fields = [];

        foreach (self::split(self::inside($type, 'array{'), ',') as $part) {
            if (preg_match('/^\'?([\w-]+)\'?(\??):\s*(.*)$/s', $part, $said) !== 1) {
                continue;
            }

            $fields[$said[1]] = [$said[2] === TheNotation::MarksOptional->value, trim($said[3])];
        }

        return $fields;
    }

    /**
     * Every path a type declares, all the way down, in the order it declares them.
     *
     * A field is not `detail`, it is `findings[].verdict.remedies[].detail` —
     * and the difference is the whole of what a name-based reading gets wrong.
     * Nine envelopes declare two hundred and twenty-three of these between
     * them, and forty-one of the names are shared by two paths or more.
     *
     * Both bracket kinds become one marker, `[]`, for the reason
     * {@see WhatTheReadersRead} gives about a reader walking either with
     * `foreach`: what a path is compared against has to be written the way the
     * reader's side of it can be recovered.
     *
     * A union contributes every arm, because the contract writes a tagged shape
     * as one — `verdict` carries `reason` on two arms and `remedies` on two
     * others, and a reading that took the first arm would call the rest fields
     * nobody has.
     *
     * @return list<string>
     */
    public static function everyPathIn(string $type, string $under = ''): array
    {
        $found = [];

        foreach (self::alternatives($type) as $alternative) {
            $found = [...$found, ...self::pathsUnder($alternative, $under)];
        }

        return array_values(array_unique($found));
    }

    /**
     * What sits between a type's opening bracket and the one that closes it.
     *
     * The closing bracket is the last character of a well-formed type, which is
     * what {@see split()} guarantees about every part it hands back.
     */
    public static function inside(string $type, string $opens): string
    {
        return trim(mb_substr($type, mb_strlen($opens), -1));
    }

    /**
     * One type's alternatives, where it is a union.
     *
     * @return list<string>
     */
    public static function alternatives(string $type): array
    {
        return self::split($type, '|');
    }

    /**
     * A type split on a separator that is not inside a bracket.
     *
     * Depth counted over both kinds, because the contract nests them through
     * each other — `array<string, array{…}>` has a comma inside angle brackets
     * and another inside braces, and a split that read either would cut a field
     * in half and report the halves as fields nobody has.
     *
     * @return list<string>
     */
    public static function split(string $type, string $on): array
    {
        $parts = [];
        $held = '';
        $depth = 0;

        foreach (mb_str_split($type) as $character) {
            $depth += self::deeper($character);

            if ($character === $on && $depth === 0) {
                $parts[] = trim($held);
                $held = '';

                continue;
            }

            $held .= $character;
        }

        $parts[] = trim($held);

        return $parts;
    }

    /**
     * Every envelope the installed contract publishes, by class name.
     *
     * Read off the tree rather than from a list, for the reason the whole class
     * exists: a list is a thing that goes stale silently, and an envelope added
     * to the SDK that nothing here knows about is exactly the gap a stand-in is
     * written against.
     *
     * Through {@see TheInstalledPackage} rather than the suite's own directory
     * walker, because this runs on the device too and `tests/` is not bundled
     * there — a helper borrowed from the suite is a helper that is absent at
     * the moment it matters.
     *
     * @return list<string>
     */
    public static function everyEnvelope(): array
    {
        return TheInstalledPackage::namesUnder(self::GENERATED, 'Envelope');
    }

    /**
     * The envelope carrying one kind, or nothing where no envelope carries it.
     *
     * The other direction of {@see kindOf()}, and it exists because the SDK
     * writes both spellings: `Api` names the answer to some endpoints as a
     * class and to others as the kind in prose — `` `status` `` where
     * `/api/services` is concerned — so a reader of that file has to be able to
     * go either way.
     *
     * A linear walk over every envelope rather than a map built once. There are
     * under a hundred of them and this is asked a handful of times per run, so
     * the cost of the walk is below the cost of a cache being wrong about an
     * SDK that was swapped underneath it.
     */
    public static function envelopeOfKind(string $kind): string
    {
        foreach (self::everyEnvelope() as $envelope) {
            if (self::kindOf($envelope) === $kind) {
                return $envelope;
            }
        }

        return '';
    }

    /**
     * The words of a type that are names, spelled out with what they stand for.
     *
     * A quoted literal is matched too, and handed back as it was, so that a
     * capital inside one is never read as a name.
     *
     * @param  array<string, string>  $named
     */
    public static function spelledOut(string $type, array $named): string
    {
        return self::spelledOutBelow($type, $named, []);
    }

    /**
     * The one thing a pattern picks out of a file under the generated tree.
     *
     * An envelope this repository does not vendor answers with nothing rather
     * than raising, because the caller has a sentence for that and no sentence
     * for a raise coming out of an expectation.
     */
    private static function whatIsDeclared(string $file, string $pattern): string
    {
        preg_match($pattern, self::whatIsWrittenIn($file), $said);

        return array_key_exists(1, $said) ? trim($said[1]) : '';
    }

    /**
     * Every shape one file under the generated tree names, by its name.
     *
     * @return array<string, string>
     */
    private static function namedIn(string $file): array
    {
        preg_match_all('/@phpstan-type\s(\w+)\s(.*)/', self::whatIsWrittenIn($file), $said, PREG_SET_ORDER);
        $named = [];

        foreach ($said as [, $name, $type]) {
            $named[$name] = trim($type);
        }

        return $named;
    }

    /**
     * {@see spelledOut()}, knowing which names are already being spelled out above.
     *
     * @param  array<string, string>  $named
     * @param  list<string>  $within
     */
    private static function spelledOutBelow(string $type, array $named, array $within): string
    {
        return (string) preg_replace_callback(
            self::A_NAME_OR_A_LITERAL,
            static fn(array $word): string => self::whatItStandsFor($word[0], $named, $within),
            $type,
        );
    }

    /**
     * One word of a type, spelled out where it is a name this envelope knows.
     *
     * @param  array<string, string>  $named
     * @param  list<string>  $within
     */
    private static function whatItStandsFor(string $word, array $named, array $within): string
    {
        if (! array_key_exists($word, $named)) {
            return $word;
        }

        return in_array($word, $within, strict: true)
            ? self::ITSELF_AGAIN
            : self::spelledOutBelow($named[$word], $named, [...$within, $word]);
    }

    /**
     * What one file under the generated tree says.
     *
     * Through {@see TheInstalledPackage}, which is what knows where the
     * installed package sits and says why that has to be asked rather than
     * worked out.
     */
    private static function whatIsWrittenIn(string $file): string
    {
        return TheInstalledPackage::text(sprintf('%s/%s', self::GENERATED, $file));
    }

    /**
     * One arm of a type, as the paths it puts under a path.
     *
     * @return list<string>
     */
    private static function pathsUnder(string $type, string $under): array
    {
        // Split where the question changes. Above: *is this a collection*, and
        // both answers to that are a walk into whatever it holds under the same
        // `[]`. Below: *which fields*, which is a different reading of a
        // different notation.
        if (str_starts_with($type, 'list<') || str_starts_with($type, 'array<')) {
            return self::everyPathIn(self::whatItHolds($type), sprintf('%s%s', $under, self::EACH));
        }

        return str_starts_with($type, 'array{') ? self::theFieldPathsIn($type, $under) : [];
    }

    /**
     * What a collection holds, whichever of the two ways it was written.
     *
     * A `list<V>` says it once and an `array<K, V>` says it after the key, so
     * the value is the last part either way — which is the same reading
     * {@see WhatAStackWouldSay} makes, and for the same reason: asking *was a
     * key written* is a question with no consequence.
     */
    private static function whatItHolds(string $type): string
    {
        if (str_starts_with($type, 'list<')) {
            return self::inside($type, 'list<');
        }

        $parts = self::split(self::inside($type, 'array<'), ',');

        return $parts === [] ? '' : $parts[count($parts) - 1];
    }

    /**
     * Every path the fields of one `array{…}` put under a path.
     *
     * @return list<string>
     */
    private static function theFieldPathsIn(string $type, string $under): array
    {
        $found = [];

        foreach (self::fieldsOf($type) as $name => $field) {
            $path = $under === '' ? $name : sprintf('%s.%s', $under, $name);
            $found = [...$found, $path, ...self::everyPathIn($field[1], $path)];
        }

        return $found;
    }

    /**
     * What one character does to the depth, counting both kinds as one.
     *
     * Both kinds together rather than a count each, because the contract nests
     * them through each other and nothing here asks which bracket it is inside
     * — only whether it is inside one.
     */
    private static function deeper(string $character): int
    {
        return TheNotation::tryFrom($character)?->deeper() ?? 0;
    }
}
