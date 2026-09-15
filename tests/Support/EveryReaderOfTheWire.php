<?php

declare(strict_types=1);

namespace Tests\Support;

use function array_key_exists;
use function array_values;
use function file_get_contents;
use function is_string;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;

use function sprintf;
use function str_contains;
use function str_replace;

/**
 * The methods that read a payload, as the parts of them worth reading.
 *
 * The corpus {@see WhatTheReadersRead} follows, found once and handed over as
 * plain values. Each method arrives with the three things a following asks of
 * it — what it assigns, what it reaches for, what it hands back — so that the
 * walk over it can be arithmetic rather than another tree traversal, and so
 * that the tree is parsed once for a rule that asks its question several times.
 *
 * Read as a tree rather than as text. A reader spells a field as
 * `WireField::X->value` and a text search for that would find the docblock
 * above it as readily as the subscript itself — and the difference between a
 * field being *mentioned* and being *read* is the whole of what this is for.
 *
 * @phpstan-type Reader array{method: Stmt\ClassMethod, imports: array<string, string>, file: string, assigns: list<Expr\Assign>, walks: list<Stmt\Foreach_>, reaches: list<Expr>, hands: list<Stmt\Return_>}
 * @phpstan-type Readers array<string, Reader>
 */
final readonly class EveryReaderOfTheWire
{
    /** Where the readers live. */
    public const string WHERE = 'app-modules/sdk/src';

    /**
     * Every method of every reader, by the class and method that declare it.
     *
     * @return Readers
     */
    public static function all(): array
    {
        $readers = [];

        foreach (Tree::filesUnder(Tree::at(self::WHERE), '.php') as $file) {
            foreach (self::in($file) as $key => $reader) {
                $readers[$key] = $reader;
            }
        }

        return $readers;
    }

    /**
     * Whether a node is one that reaches into a payload or into another reader.
     *
     * A call counts, because most of the reads a reader makes are in the helper
     * it hands the payload to rather than in the method that found it.
     */
    public static function isAReach(Node $node): bool
    {
        return $node instanceof ArrayDimFetch
            || $node instanceof FuncCall
            || $node instanceof StaticCall
            || $node instanceof MethodCall;
    }

    /**
     * Whether a reach is one that takes a field rather than one that leads on.
     *
     * A call is a reach because it is where a payload goes next, and following
     * it is what seats the helper at the other end — but the call itself reads
     * nothing. Counting one would put the payload's own root in the answer as
     * though it were a field, which is not a thing anybody can decide about.
     */
    public static function readsAField(Node $node): bool
    {
        return $node instanceof ArrayDimFetch
            || ($node instanceof FuncCall && TheWireNameAtASubscript::isAPresenceCheck($node));
    }

    /**
     * Where a reach is, exactly.
     *
     * By position in the file rather than by line, because a reader asks
     * whether a field arrived and then takes it on the same line — two reaches,
     * one line, and a rule that could not tell them apart would report the one
     * it placed as the one it did not.
     */
    public static function siteOf(Node $node): string
    {
        return sprintf('%d:%d', $node->getStartFilePos(), $node->getEndFilePos());
    }

    /** A file as the repository sees it, so a message reads the same on any machine. */
    public static function below(string $file): string
    {
        return str_replace(sprintf('%s/', Tree::root()), '', $file);
    }

    /**
     * A name written in a file, as the name it stands for.
     *
     * @param array<string, string> $imports
     */
    public static function resolve(string $written, array $imports): string
    {
        if (array_key_exists($written, $imports)) {
            return $imports[$written];
        }

        return str_contains($written, '\\') || ! array_key_exists('', $imports)
            ? $written
            : sprintf('%s\\%s', $imports[''], $written);
    }

    /**
     * Every method one file declares.
     *
     * @return Readers
     */
    private static function in(string $file): array
    {
        $statements = self::parse($file);
        $imports = self::importsIn($statements);
        $readers = [];

        /** @var list<ClassLike> $declared */
        $declared = new NodeFinder()->findInstanceOf($statements, ClassLike::class);

        foreach ($declared as $class) {
            if ($class->name === null) {
                continue;
            }

            foreach ($class->getMethods() as $method) {
                $key = sprintf('%s::%s', $class->name->toString(), $method->name->toString());
                $readers[$key] = self::readerOf($method, $imports, $file);
            }
        }

        return $readers;
    }

    /**
     * One method, with the parts of it a following reads.
     *
     * @param array<string, string> $imports
     *
     * @return Reader
     */
    private static function readerOf(ClassMethod $method, array $imports, string $file): array
    {
        $finder = new NodeFinder();

        /** @var list<Assign> $assigns */
        $assigns = $finder->findInstanceOf($method, Assign::class);

        /** @var list<Foreach_> $walks */
        $walks = $finder->findInstanceOf($method, Foreach_::class);

        /** @var list<Expr> $reaches */
        $reaches = $finder->find($method, self::isAReach(...));

        /** @var list<Return_> $hands */
        $hands = $finder->findInstanceOf($method, Return_::class);

        return [
            'method' => $method,
            'imports' => $imports,
            'file' => $file,
            'assigns' => $assigns,
            'walks' => $walks,
            'reaches' => $reaches,
            'hands' => $hands,
        ];
    }

    /**
     * One file, as statements.
     *
     * @return list<Stmt>
     */
    private static function parse(string $file): array
    {
        $source = file_get_contents($file);

        if (! is_string($source)) {
            return [];
        }

        return array_values(new ParserFactory()->createForNewestSupportedVersion()->parse($source) ?? []);
    }

    /**
     * What one file's short names stand for, including its own namespace.
     *
     * The namespace is filed under the empty name, which is what
     * {@see resolve()} falls back to for a class written without one: a reader
     * naming a sibling in its own directory writes the short name and nothing
     * else, and a resolver that gave up there would lose every helper.
     *
     * @param list<Stmt> $statements
     *
     * @return array<string, string>
     */
    private static function importsIn(array $statements): array
    {
        $imports = [];

        /** @var list<Use_> $uses */
        $uses = new NodeFinder()->findInstanceOf($statements, Use_::class);

        foreach ($uses as $use) {
            foreach ($use->uses as $single) {
                $imports[$single->getAlias()->toString()] = $single->name->toString();
            }
        }

        /** @var Namespace_|null $namespace */
        $namespace = new NodeFinder()->findFirstInstanceOf($statements, Namespace_::class);

        if ($namespace?->name !== null) {
            $imports[''] = $namespace->name->toString();
        }

        return $imports;
    }
}
