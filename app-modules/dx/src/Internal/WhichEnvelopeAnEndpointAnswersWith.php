<?php

declare(strict_types=1);

namespace Modules\Dx\Internal;

use function mb_strlen;

use Modules\Dx\Adapters\TheInstalledPackage;

use function preg_match;
use function preg_match_all;
use function preg_quote;
use function sprintf;
use function str_starts_with;

/**
 * Which envelope one endpoint of the wire answers with, read off the SDK.
 *
 * A stand-in has to answer a request with the *right* shape, and which shape is
 * right is a property of the path that was asked for. A hand-written table is refused
 * of that written by hand for the reason it refuses every hand-written fixture:
 * the day lemonfiber moves an endpoint or changes what one answers with, a
 * table says nothing and every screen built on it keeps rendering.
 *
 * So the table is read. `Lemonfiber\Sdk\Contract\Api` is where the paths are
 * declared, and its own docblocks say what each one answers with — the SDK
 * wrote them for a human and they turn out to be machine-readable, which is a
 * happy accident worth using rather than a contract to rely on blindly. Where a
 * docblock says nothing this answers nothing, and the caller has a sentence for
 * that.
 *
 * **Two spellings, because the SDK uses two.** Most endpoints name the reading
 * type outright — `{@see \Lemonfiber\Sdk\Generated\StatusEnvelope}` — and a few
 * name only the kind in prose, as `/api/services` names `` `status` ``. Both
 * are followed, the class first, because a class is unambiguous and a
 * backticked word in a paragraph is a guess until the contract confirms it
 * names a real kind.
 */
final readonly class WhichEnvelopeAnEndpointAnswersWith
{
    /** Where the endpoints are declared, under whatever root the SDK was installed at. */
    private const string DECLARED_IN = 'src/Contract/Api.php';

    /**
     * A docblock and the endpoint it introduces.
     *
     * The inner `(?:(?!\*\/).)*` is a tempered dot rather than a lazy one:
     * `.*?` still crosses a `*\/` when the pattern after it fails to match
     * nearby, so a lazy version silently hands back every docblock above an
     * endpoint joined together and the last one wins. That misread is what made
     * `/api/actions` appear to be documented by the class docblock.
     */
    private const string AN_ENDPOINT = '~/\*\*((?:(?!\*/).)*)\*/\s*public\sconst\sstring\s\w+_ENDPOINT\s=\s\'([^\']+)\';~s';

    /** A reading type named outright in a docblock. */
    private const string A_NAMED_TYPE = '~Generated\\\\(\w+Envelope)~';

    /** A clause of a docblock about a request that names none, or nothing. */
    private const string A_CLAUSE_NAMING_NONE = '~[^.;]*\\bnaming (?:none|nothing)\\b[^.;]*~';

    /** One sentence of a docblock that quotes a word, the word going in at `%s`. */
    private const string A_SENTENCE_QUOTING = '~[^.]*`%s`[^.]*~';

    /** A word in backticks, which is how the SDK writes a kind in prose. */
    private const string A_QUOTED_WORD = '~`([a-z][a-z_-]*)`~';

    /**
     * The envelope a path answers with, or nothing where the SDK does not say.
     *
     * By longest prefix rather than by equality, because two of the paths carry
     * a name in their last segment: an action is asked for at
     * `/api/actions/<name>` and the work it starts is asked about at
     * `/api/jobs/<name>`. Matching on equality would answer nothing for every
     * real request to either.
     *
     * Longest rather than first, so a path that is a prefix of another cannot
     * shadow it.
     *
     * `$asked` is the values the request's query carries. An endpoint that
     * answers with more than one envelope says in its docblock which request
     * gets which: a sentence quoting a value names that value's envelope, and a
     * clause about naming none or nothing names the envelope for a request
     * carrying no value. The first value with such a sentence decides; a
     * request with no values takes the clause about naming none where one
     * names an envelope; anything else gets the envelope the docblock names
     * first.
     */
    public static function at(string $path, string ...$asked): string
    {
        $longest = '';
        $said = '';

        foreach (self::everyOneDeclared() as $endpoint => $docblock) {
            if (str_starts_with($path, $endpoint) && mb_strlen($endpoint) > mb_strlen($longest)) {
                $longest = $endpoint;
                $said = $docblock;
            }
        }

        foreach ($asked as $value) {
            $envelope = self::namedIn(self::theSentenceQuoting($said, $value));

            if ($envelope !== '') {
                return $envelope;
            }
        }

        return $asked === [] ? self::theEnvelopeForNoValue($said) : self::namedIn($said);
    }

    /**
     * Every endpoint whose docblock says what it answers with, for a request carrying no value.
     *
     * Endpoints that say nothing are left out rather than guessed at. Three are
     * in that position and they are not one situation: `/api/events` is a
     * stream and not an envelope at all, and `/api/actions` and `/api/jobs`
     * both answer with a name for work that `Client::repair()` documents and
     * `Api` does not. A guess made here would be indistinguishable from a
     * reading, and the caller can tell the three apart.
     *
     * @return array<string, string> path => envelope class name
     */
    public static function everyOneNamed(): array
    {
        $answers = [];

        foreach (self::everyOneDeclared() as $endpoint => $docblock) {
            $answers[$endpoint] = self::theEnvelopeForNoValue($docblock);
        }

        return $answers;
    }

    /**
     * Every endpoint whose docblock names an envelope, with that docblock.
     *
     * @return array<string, string> path => docblock
     */
    private static function everyOneDeclared(): array
    {
        preg_match_all(self::AN_ENDPOINT, self::whatApiDeclares(), $found, PREG_SET_ORDER);

        $declared = [];

        foreach ($found as $one) {
            if (self::namedIn($one[1]) !== '') {
                $declared[$one[2]] = $one[1];
            }
        }

        return $declared;
    }

    /**
     * The envelope a request carrying no value gets: the one the clause about naming none names, or the first named.
     */
    private static function theEnvelopeForNoValue(string $docblock): string
    {
        $forNone = self::namedIn(self::theClauseNamingNone($docblock));

        return $forNone === '' ? self::namedIn($docblock) : $forNone;
    }

    /**
     * The clause of a docblock about a request that names none, or nothing where there is no such clause.
     */
    private static function theClauseNamingNone(string $docblock): string
    {
        return preg_match(self::A_CLAUSE_NAMING_NONE, $docblock, $clause) === 1 ? $clause[0] : '';
    }

    /**
     * The first sentence of a docblock that quotes a value, or nothing where none does.
     */
    private static function theSentenceQuoting(string $docblock, string $value): string
    {
        return preg_match(sprintf(self::A_SENTENCE_QUOTING, preg_quote($value, '~')), $docblock, $sentence) === 1
            ? $sentence[0]
            : '';
    }

    /**
     * The envelope one docblock names, by either of the two spellings.
     */
    private static function namedIn(string $said): string
    {
        if (preg_match(self::A_NAMED_TYPE, $said, $typed) === 1) {
            return $typed[1];
        }

        preg_match_all(self::A_QUOTED_WORD, $said, $quoted);

        return self::theFirstKindAmong($quoted[1]);
    }

    /**
     * The first backticked word the contract confirms is a kind.
     *
     * Confirmation is what makes this safe to run over prose. A docblock names
     * `` `form` `` and `` `api_version` `` as readily as it names `` `status`
     * ``, and only one of those is an envelope — so the contract decides, and a
     * paragraph that happens to quote a word gets no say.
     *
     * @param list<string> $words
     */
    private static function theFirstKindAmong(array $words): string
    {
        foreach ($words as $word) {
            $envelope = WhatTheContractDeclares::envelopeOfKind($word);

            if ($envelope !== '') {
                return $envelope;
            }
        }

        return '';
    }

    /**
     * What the SDK's endpoint declarations say.
     *
     * Through {@see TheInstalledPackage}, which is the one file in this module
     * allowed to open one — `B3`, and the reason it is worth obeying here is
     * that a missing package and a misread docblock are different failures
     * fixed in different places.
     */
    private static function whatApiDeclares(): string
    {
        return TheInstalledPackage::text(self::DECLARED_IN);
    }
}
