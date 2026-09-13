<?php

declare(strict_types=1);

namespace Tests\Support;

use function app;
use function array_map;
use function array_search;
use function basename;
use function glob;

use const GLOB_ONLYDIR;

use Illuminate\Contracts\Translation\Translator;

use function is_array;
use function is_string;
use function mb_strlen;
use function mb_substr;

use Modules\Device\Internal\Words;
use RuntimeException;

use function sprintf;
use function str_replace;

/**
 * One locale's translations for one module, read off disk.
 *
 * Every rule about text a person reads needs the same two things — which
 * locales exist, and what one of them says — and each one that fetched them for
 * itself had to name the `lang/` layout again. Two copies of "where the
 * catalogues are" disagree the day one moves, and the test that disagrees is
 * the one that quietly stops checking.
 *
 * Read rather than resolved through the translator: a rule about a missing key
 * cannot ask the thing whose job is to hide a missing key. Laravel looks one
 * up, misses, falls back, misses again and renders the key — so asking it would
 * turn every absence into a plausible-looking string.
 */
final readonly class Catalogue
{
    /**
     * Every locale the application ships, by the directory it lives in.
     *
     * Read off disk rather than listed here, so adding a language is adding a
     * directory rather than adding a directory and remembering this.
     *
     * @return list<string>
     */
    public static function locales(): array
    {
        $directories = glob(Tree::at('lang/*'), GLOB_ONLYDIR);
        $found = array_map(basename(...), $directories === false ? [] : $directories);

        // Refused rather than answered empty, the way `Coverage` refuses a
        // report that is not there. Four rules loop over this and each asks
        // whether something is missing *in every language* — so a list with no
        // languages in it finds nothing missing, four times, and says so with a
        // tick. Parity also wants two of them; one language is a catalogue that
        // agrees with itself.
        if ($found === []) {
            throw new RuntimeException(sprintf(
                'No language was found under %s. A rule asking what is missing in every '
                . 'language finds nothing missing when there are none, which is the same '
                . 'answer as a catalogue that is complete.',
                Tree::at('lang'),
            ));
        }

        return $found;
    }

    /**
     * One group's keys and sentences, flattened to dotted form.
     *
     * Flattened because a catalogue may nest — a group per enum reads better
     * than a prefix repeated on every row — and a rule asking whether a case
     * has a word should not have to know which of the two shapes was used.
     *
     * @return array<string, string>
     */
    public static function of(string $locale, string $group): array
    {
        /** @var mixed $rows */
        $rows = require Tree::at(sprintf('lang/%s/%s.php', $locale, str_replace('.', '/', $group)));

        return self::flatten($group, $rows);
    }

    /**
     * Everything one locale says, every group flattened under its own name.
     *
     * The keys read as the translator sees them — `health.conclusion.fail` —
     * because a rule comparing two locales has to report the key somebody would
     * search for, and half a key sends them to the wrong file.
     *
     * @return array<string, string>
     */
    public static function all(string $locale): array
    {
        $said = [];
        $under = Tree::at(sprintf('lang/%s', $locale));

        foreach (Tree::filesUnder($under, '.php') as $file) {
            $said = [...$said, ...self::of($locale, self::groupOf($under, $file))];
        }

        return $said;
    }

    /**
     * The keys whose sentence a key before them already used.
     *
     * Two rows saying the same thing is how a distinction the type system keeps
     * is lost at the last step: the cases stay apart, and the operator — who
     * meets the sentence and not the case — is told the same thing twice. It
     * reports the later key against the earlier one, so the message names the
     * row to rewrite rather than the pair.
     *
     * @param array<string, string> $words
     *
     * @return list<string>
     */
    public static function saidTwice(string $locale, array $words): array
    {
        $collisions = [];
        $said = [];

        foreach ($words as $key => $word) {
            $seen = array_search($word, $said, strict: true);

            if ($seen !== false) {
                $collisions[] = sprintf('%s: %s reads the same as %s', $locale, $key, $seen);
            }

            $said[$key] = $word;
        }

        return $collisions;
    }

    /**
     * The application's own catalogue reader.
     *
     * Here rather than in either contract test, because two test files may not
     * declare the same helper (`G10`) and building it differently in each is
     * how two tests end up asserting about two different catalogues.
     *
     * The real translator rather than a fake: what those contracts assert is
     * that a notification and a platform dialog carry the wording this
     * repository ships, which a fake would answer for.
     */
    public static function words(): Words
    {
        return new Words(app(Translator::class));
    }

    /**
     * The group name a catalogue file answers to, path and all.
     *
     * `basename()` was what this used, and it is wrong for any file below the
     * locale directory: `lang/en/onboarding/pairing.php` came back as
     * `pairing`, {@see self::of()} required `lang/en/pairing.php`, and the
     * missing file took down every rule that reads the catalogue — with
     * `Failed opening required`, from inside a rule that is about something
     * else entirely.
     *
     * It is not hypothetical and it is not only about a subdirectory somebody
     * might add: the `L2` fixture plants `lang/en/Fixtures/planted.php`, so the
     * whole of the Guards run had every catalogue rule erroring rather than
     * failing, and each of them matched its fixture on the name alone. A rule
     * that errors for the wrong reason still looks red, which is how this stayed
     * invisible.
     *
     * Laravel's own translator resolves a nested file as a dotted group, so
     * this is also what it actually answers to.
     */
    private static function groupOf(string $under, string $file): string
    {
        $relative = str_replace(sprintf('%s/', $under), '', $file);

        return str_replace('/', '.', mb_substr($relative, 0, -mb_strlen('.php')));
    }

    /** @return array<string, string> */
    private static function flatten(string $prefix, mixed $rows): array
    {
        if (is_string($rows)) {
            return [$prefix => $rows];
        }

        if (! is_array($rows)) {
            return [];
        }

        $found = [];

        foreach ($rows as $key => $nested) {
            $under = $prefix === '' ? (string) $key : sprintf('%s.%s', $prefix, $key);

            $found = [...$found, ...self::flatten($under, $nested)];
        }

        return $found;
    }
}
