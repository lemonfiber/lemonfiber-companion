<?php

declare(strict_types=1);

namespace Tests\Support;

use function dirname;
use function file_get_contents;
use function is_array;
use function is_file;
use function is_int;
use function is_string;
use function json_decode;

use const JSON_THROW_ON_ERROR;

use JsonException;
use RuntimeException;

use function sprintf;
use function str_replace;
use function str_starts_with;
use function trim;

/**
 * One tree the coverage report measures, and the bar it is held to.
 *
 * The trees come from `phpunit.xml`, which is the only statement of scope here
 * that cannot be narrowed without also narrowing what is measured — the same
 * answer {@see OurCode} gives every other gate. The floors come from the
 * manifest nearest that tree: a module's own for `app-modules/<name>/src`,
 * the plugin's for `bridge/src`, the root's for `bootstrap/Composition`.
 *
 * **One principle applied three times rather than three cases.** The rule was
 * "in the module's own manifest", and a module is something `Module::read()`
 * can build: a declared kind, which generates boundary rules, and a namespace
 * of `Modules\<Name>`. `bridge` is neither — it is `Lemonfiber\Native\` and a
 * path package — and `bootstrap/Composition` has no manifest at all. So the two
 * trees carrying 2,900 lines of shipped PHP sat inside the coverage floor and
 * outside every mutation bar, with nowhere to declare one.
 *
 * Nearest-above is what makes "every tree phpunit.xml measures is held to a
 * bar" a derivation rather than a list somebody maintains. A tree added
 * tomorrow is held the moment it is measured, and it is held by the manifest a
 * reader would look in first.
 */
final readonly class MeasuredTree
{
    public function __construct(
        public string $path,
        public string $manifest,
        public ?Kind $kind,
        public ?int $coverageFloor,
        public ?int $mutationFloor,
        public ?string $whyCoverageIsZero,
        public ?string $whyMutationIsZero,
    ) {}

    /** @return list<self> */
    public static function all(): array
    {
        $found = [];

        foreach (OurCode::measuredTrees() as $tree) {
            $found[] = self::read($tree);
        }

        return $found;
    }

    /**
     * The PHP this tree holds, at any depth.
     *
     * A tree with none is measured and has nothing to mutate, which is not the
     * same as a tree whose mutants were all killed — the two print identically,
     * so whatever reads this has to say which it found.
     *
     * @return list<string>
     */
    public function sourceFiles(): array
    {
        return Tree::filesUnder(Tree::at($this->path), '.php');
    }

    /**
     * What a tree of this shape is usually covered to, where there is a shape.
     *
     * A hint for a failure message and never a default: the number has to be
     * typed into a manifest by somebody who looked at the tree. Where the
     * manifest declares no kind — the plugin's and the root's, neither of which
     * is a module — there is no convention to name, and the honest answer is
     * the empty one rather than a number borrowed from a module of some other
     * shape.
     */
    public function conventionalCoverage(): string
    {
        $kind = $this->kind;

        return $kind instanceof Kind
            ? sprintf(' (a %s is usually %d)', $kind->value, $kind->conventionalCoverageFloor())
            : '';
    }

    /**
     * The same for mutation, which is where the conventions actually differ.
     *
     * Two methods rather than one taking the name of a floor. One would dispatch
     * on a string and answer *something* for a string that is neither — the
     * mutation convention, silently, because a fallback has to pick one — and
     * the two conventions disagree for four kinds out of six.
     */
    public function conventionalMutation(): string
    {
        $kind = $this->kind;

        return $kind instanceof Kind
            ? sprintf(' (a %s is usually %d)', $kind->value, $kind->conventionalMutationFloor())
            : '';
    }

    /**
     * The manifest nearest a tree, as the repository writes it.
     *
     * At or above, and the first one wins. Starting at the tree itself matters
     * for a package whose manifest sits beside its source rather than above it,
     * and costs nothing where it does not: no tree measured here holds a
     * manifest of its own.
     *
     * The walk cannot run out — the repository root holds one — and it refuses
     * rather than answering anyway if it ever does, because an answer of "the
     * root" for a tree outside the root is how one manifest silently ends up
     * holding two trees.
     */
    private static function nearestManifestAbove(string $tree): string
    {
        $root = Tree::root();
        $at = Tree::at($tree);

        while (str_starts_with($at, $root)) {
            $manifest = sprintf('%s/composer.json', $at);

            if (is_file($manifest)) {
                return self::asTheRepositorySeesIt($manifest);
            }

            $at = dirname($at);
        }

        throw new RuntimeException(sprintf(
            '%s is measured by phpunit.xml and no manifest above it is inside %s. '
            . 'Every tree is held to the bar its nearest manifest declares, so a tree '
            . 'with no manifest above it is a tree nothing can hold.',
            $tree,
            $root,
        ));
    }

    /** A path below the repository root, as the repository writes it. */
    private static function asTheRepositorySeesIt(string $path): string
    {
        return trim(str_replace(Tree::root(), '', $path), '/');
    }

    /**
     * One declared floor, or null where the manifest declares none.
     *
     * Null rather than a default, and read without raising: an undeclared floor
     * is a finding for G7 to report by name, not an exception thrown while the
     * tree list is being built. Raising here would take down every rule that
     * reads a manifest, and the message would be about JSON rather than about
     * the tree nobody wrote a number for.
     */
    private static function floor(mixed $floors, string $which): ?int
    {
        if (! is_array($floors)) {
            return null;
        }

        $value = $floors[$which] ?? null;

        return is_int($value) ? $value : null;
    }

    /**
     * The argument a floor of zero carries, or null where it carries none.
     *
     * Beside the number rather than anywhere else, because the two are read
     * together or the second is not read at all. A floor of zero is a position
     * a tree's owner takes, and a position stated once for every tree of a kind
     * is one no single tree can be asked about — so this is where a manifest
     * says what holds its own decisions instead.
     *
     * Read without raising, for the reason {@see floor()} is.
     */
    private static function argument(mixed $floors, string $which): ?string
    {
        if (! is_array($floors)) {
            return null;
        }

        $said = $floors[sprintf('%s-is-zero-because', $which)] ?? null;

        return is_string($said) ? $said : null;
    }

    private static function read(string $tree): self
    {
        $manifest = self::nearestManifestAbove($tree);
        $lemonfiber = self::lemonfiberBlockIn($manifest);

        $kind = $lemonfiber['kind'] ?? null;
        $floors = $lemonfiber['floors'] ?? null;

        return new self(
            path: $tree,
            manifest: $manifest,
            // Lenient on purpose, unlike `Module::read()`, which refuses a
            // manifest declaring no kind because a kind is what generates that
            // module's boundary rules. Here it only names a convention in a
            // failure message, and two of the manifests that hold a tree are
            // not modules and have no kind to declare.
            kind: is_string($kind) ? Kind::tryFrom($kind) : null,
            coverageFloor: self::floor($floors, 'coverage'),
            mutationFloor: self::floor($floors, 'mutation'),
            whyCoverageIsZero: self::argument($floors, 'coverage'),
            whyMutationIsZero: self::argument($floors, 'mutation'),
        );
    }

    /**
     * What one manifest says under `extra.lemonfiber`, or nothing.
     *
     * @return array<array-key, mixed>
     */
    private static function lemonfiberBlockIn(string $manifest): array
    {
        $raw = file_get_contents(Tree::at($manifest));

        if (! is_string($raw)) {
            throw new RuntimeException(sprintf('%s could not be read', $manifest));
        }

        try {
            /** @var mixed $decoded */
            $decoded = json_decode($raw, associative: true, depth: 512, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException(sprintf('%s is not valid JSON', $manifest), 0, $e);
        }

        $extra = is_array($decoded) ? $decoded['extra'] ?? null : null;
        $lemonfiber = is_array($extra) ? $extra['lemonfiber'] ?? null : null;

        return is_array($lemonfiber) ? $lemonfiber : [];
    }
}
