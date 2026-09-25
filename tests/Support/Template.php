<?php

declare(strict_types=1);

namespace Tests\Support;

use function array_map;
use function array_merge;
use function explode;
use function file_get_contents;
use function implode;
use function is_file;
use function is_string;
use function preg_match_all;
use function preg_replace;
use function preg_split;
use function sprintf;
use function str_contains;
use function str_replace;
use function substr_count;
use function trim;

/**
 * One Blade template, read as text.
 *
 * A `.blade.php` file is not a class, so nothing that reflects over namespaces
 * can see it — which is why none of the architecture rules reach these files
 * and why this suite exists at all. The extraction below is regex over source,
 * with the limits that implies: it reads attributes that are written out and
 * cannot follow one assembled at runtime. Those it drops rather than guesses,
 * and each drop is named where it happens.
 */
final readonly class Template
{
    /**
     * Stands in for an echo while a class list is being split.
     *
     * Not the null byte, which `trim()` strips by default — an echo at the end
     * of a class list would lose its marker and `bg-{{ $tone }}` would come
     * back as the unknown utility `bg-`.
     */
    private const string RUNTIME = "\u{1}";

    public function __construct(
        public string $path,
        public string $source,
    ) {}

    /**
     * Every Blade template in the repository.
     *
     * Both roots, even though W3 refuses one of them: a screen put in the root
     * should be reported by both rules rather than by whichever runs first.
     *
     * @return list<self>
     */
    public static function all(): array
    {
        $found = [];

        $roots = array_merge(
            [Tree::at('resources/views')],
            array_map(
                static fn(Module $module): string => sprintf('%s/resources/views', $module->path),
                Module::all(),
            ),
        );

        foreach ($roots as $root) {
            foreach (Tree::filesUnder($root, '.blade.php') as $path) {
                $source = file_get_contents($path);

                if (is_string($source)) {
                    $found[] = new self(str_replace(sprintf('%s/', Tree::root()), '', $path), $source);
                }
            }
        }

        return $found;
    }

    /**
     * The value of every `class="…"` attribute, with runtime pieces removed.
     *
     * A token holding an echo goes entirely, rather than having the echo
     * stripped out of it: `bg-{{ $tone }}` with the expression deleted is
     * `bg-`, which the parser would report as an unknown utility and which
     * nobody wrote. The rest of the string is still checked, so a typo beside a
     * dynamic class is still found.
     *
     * A bound attribute — `:class` or `native:class` — is not read at all,
     * because its value is PHP rather than a class list.
     *
     * @return list<string>
     */
    public function classStrings(): array
    {
        preg_match_all('/(?<![:\w-])class\s*=\s*(["\'])(.*?)\1/s', $this->source, $found);

        return array_map(
            $this->withoutRuntimePieces(...),
            $found[2],
        );
    }

    /**
     * Tags written with the `native:` prefix, in kebab form.
     *
     * @return list<string>
     */
    public function nativeTags(): array
    {
        preg_match_all('/<native:([a-z][a-z0-9-]*)/i', $this->source, $found);

        return $found[1];
    }

    /**
     * Tags written without the prefix.
     *
     * The precompiler accepts these — it builds a bare-tag allowlist from the
     * element registry — so `<column>` renders exactly as `<native:column>`
     * does. What it cannot do is tell a reader which of the two a tag is, and
     * an HTML-looking `<button>` in a file that compiles to native widgets is
     * the sort of thing that gets edited as though it were HTML.
     *
     * @return list<string>
     */
    public function bareTags(): array
    {
        preg_match_all('/<(?!\/|!|\?|native:|x-)([a-z][a-z0-9-]*)/', $this->source, $found);

        return $found[1];
    }

    /**
     * Every Blade component the template stands in.
     *
     * A third kind of tag, and the reason `bareTags()` steps over `x-`: a
     * component is neither an element the collector knows nor HTML written by
     * mistake. It compiles to whatever its own template compiles to, which is
     * why {@see EveryScreenCompilesToPhpThatParsesTest} reads those too — and
     * why what is asserted here is only that the name resolves to something.
     *
     * @return list<string>
     */
    public function componentTags(): array
    {
        preg_match_all('/<x-([a-z][a-z0-9.:-]*)/i', $this->source, $found);

        return $found[1];
    }

    /**
     * Every element written in the template, with its attributes and line.
     *
     * @return list<array{tag: string, attributes: string, line: int}>
     */
    public function elements(): array
    {
        preg_match_all(
            '/<(?!x-)(?:native:)?([a-z][a-z0-9-]*)((?:[^>"\']|"[^"]*"|\'[^\']*\')*?)(\/?)>/i',
            $this->source,
            $found,
            PREG_OFFSET_CAPTURE,
        );

        $elements = [];

        foreach ($found[1] as $index => $tag) {
            $elements[] = [
                'tag' => $tag[0],
                'attributes' => $found[2][$index][0],
                'line' => $this->lineAt($tag[1]),
            ];
        }

        return $elements;
    }

    /**
     * Text sitting between tags, which is text a person reads.
     *
     * @return list<string>
     */
    public function proseNodes(): array
    {
        $withoutTags = preg_replace('/<[^>]*>/s', "\n", $this->source);
        // A directive's arguments may call a method, so one level of nested
        // parentheses is balanced: `@forelse ($this->rehearsal()->wouldStart as $s)`
        // would otherwise leave `->wouldStart as $s)` behind to be read as prose.
        $withoutDirectives = preg_replace('/@[a-z]+(\s*\((?:[^()]|\([^()]*\))*\))?/i', "\n", (string) $withoutTags);
        $withoutEchoes = preg_replace('/\{\{.*?\}\}|\{!!.*?!!\}/s', "\n", (string) $withoutDirectives);
        $withoutComments = preg_replace('/\{\{--.*?--\}\}/s', "\n", (string) $withoutEchoes);

        $found = [];

        $lines = preg_split('/\n/', (string) $withoutComments);

        foreach ($lines === false ? [] : $lines as $line) {
            $text = trim($line);

            if ($text !== '') {
                $found[] = $text;
            }
        }

        return $found;
    }

    /** The line a byte offset falls on. */
    public function lineAt(int $offset): int
    {
        return substr_count($this->source, "\n", 0, $offset) + 1;
    }

    /** Whether the template names this attribute anywhere on the given tag. */
    public function describe(string $tag, int $line): string
    {
        return sprintf('%s:%d <%s>', $this->path, $line, $tag);
    }
    /**
     * Whether this template is a component rather than a screen.
     *
     * The two are different kinds and the rules about them differ. A screen
     * owes the operator a control and a way off it; a heading owes neither,
     * and a floor applied to one because it was written in Blade like the
     * other is a rule failing on a file it was never about.
     *
     * By directory, because that is what decides it: `internachi/modular`
     * resolves `x-operator::heading` to `components/heading.blade.php` and
     * nowhere else.
     */
    public function isAComponent(): bool
    {
        return str_contains($this->path, '/components/');
    }

    /**
     * This template read together with the chrome it stands in.
     *
     * What reaches the operator is the composed screen, not the file. A screen
     * whose only control, or whose way back, lives in the chrome every screen
     * shares has both; a rule reading the file alone concludes it has neither,
     * which is how a correct refactor turns a true rule into a false one.
     *
     * One level, because that is what the repository has. A component standing
     * in another would need this to recurse, and a rule refusing a cycle would
     * have to come first.
     */
    public function composed(): self
    {
        return new self($this->path, $this->composedSource());
    }

    /**
     * This template's source, and the source of the chrome it stands in.
     */
    public function composedSource(): string
    {
        $sources = [$this->source];

        foreach ($this->componentTags() as $tag) {
            $stood = $this->componentNamed($tag);

            if ($stood instanceof self) {
                $sources[] = $stood->source;
            }
        }

        return implode("\n", $sources);
    }

    /**
     * A class list with every token that held a runtime expression removed.
     *
     * The expression collapses to a single marker before the string is split,
     * because an echo contains spaces of its own: splitting first turns
     * `bg-{{ $tone }}` into three tokens and leaves `$tone` looking like a
     * class somebody wrote. Collapsing first keeps the whole thing as one
     * token, which is then dropped along with the `bg-` it was attached to.
     */
    private function withoutRuntimePieces(string $value): string
    {
        $marked = preg_replace('/\{\{.*?\}\}|\{!!.*?!!\}/s', self::RUNTIME, $value);
        $kept = [];

        $tokens = preg_split('/\s+/', trim((string) $marked), -1, PREG_SPLIT_NO_EMPTY);

        foreach ($tokens === false ? [] : $tokens as $token) {
            if (str_contains($token, self::RUNTIME) || str_contains($token, '@')) {
                continue;
            }

            $kept[] = $token;
        }

        return implode(' ', $kept);
    }

    /**
     * The template behind a component tag, or `null` if the tag names none.
     *
     * `null` rather than a raise: a tag naming no component is `F3`'s to
     * report, and a second rule failing for the same reason names the same
     * defect twice in different words.
     */
    private function componentNamed(string $tag): ?self
    {
        [$module, $name] = str_contains($tag, '::')
            ? explode('::', $tag, 2)
            : ['operator', $tag];

        $path = sprintf('app-modules/%s/resources/views/components/%s.blade.php', $module, str_replace('.', '/', $name));
        $at = Tree::at($path);

        if (! is_file($at)) {
            return null;
        }

        return new self($path, (string) file_get_contents($at));
    }
}
