<?php

declare(strict_types=1);

use Modules\Connection\Internal\TheSettingsAsKept;
use Modules\Health\Internal\TheSummaryAsKept;
use Modules\Kernel\Api\AFillAgreed;
use Modules\Kernel\Api\AgreedTo;
use Modules\Kernel\Api\AMoveAgreed;
use Modules\Kernel\Api\AnAffectedItem;
use Modules\Kernel\Api\AnInvitationAgreed;
use Modules\Kernel\Api\AnUninstallAgreed;
use Modules\Kernel\Api\APairingCode;
use Modules\Kernel\Api\APairingLine;
use Modules\Kernel\Api\APluginInstallAgreed;
use Modules\Kernel\Api\APluginRemovalAgreed;
use Modules\Kernel\Api\APluginUpdateAgreed;
use Modules\Kernel\Api\ARemovalAgreed;
use Modules\Kernel\Api\AResetAgreed;
use Modules\Kernel\Api\ARunAgreedTo;
use Modules\Kernel\Api\Confirmed;
use Modules\Kernel\Api\Credential;
use Modules\Kernel\Api\Decided;
use Modules\Kernel\Api\HostingAgreed;
use Modules\Kernel\Api\IdempotencyKey;
use Modules\Kernel\Api\Offer;
use Modules\Kernel\Api\Pairing;
use Modules\Kernel\Api\Repair;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\TakingAnUpdate;
use Modules\Kernel\Api\TheHealthSummary;
use Modules\Kernel\Api\Unsealed;
use Modules\Kernel\Api\WhatWasDecided;
use Modules\News\Internal\TheNewsAsKept;
use Modules\Requests\Internal\TheRequestsAsKept;
use Modules\Seal\Api\EncrypterSeal;
use Modules\Services\Internal\TheListingAsKept;
use Modules\Updates\Internal\TheUpkeepAsKept;
use Modules\Watching\Internal\TheirLanguagesAsKept;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use Tests\Support\Imports;
use Tests\Support\OurCode;
use Tests\Support\Tree;

// A13 — what the phone keeps is put in the clear only by a writer declared for
// it, and no writer is handed what the phone may not keep.
//
// Everything kept is sealed, and a value is sealed only once it is an
// `Unsealed`. So the one way anything reaches the phone's storage is a class
// that makes one, and those classes are few and named here: each owner's
// `*AsKept`, which writes what it keeps in a shape it can read back. The seal
// makes one too, handing back what it opened, and writes nothing.
//
// A writer is the other half. One handed a session, an offer or the key of a
// command not yet received would be one line from writing it down, and the
// phone would keep a credential beside the readings or retry an agreement on
// the next launch. So every type a writer is handed is walked down to the
// types it holds, its docblocks included, since a list a value holds is typed
// there and nowhere else.

/**
 * The writers, each the one class its owner writes what it keeps with.
 *
 * @var list<class-string>
 */
const THE_WRITERS_OF_WHAT_IS_KEPT = [
    TheSettingsAsKept::class,
    TheSummaryAsKept::class,
    TheNewsAsKept::class,
    TheUpkeepAsKept::class,
    TheListingAsKept::class,
    TheRequestsAsKept::class,
    TheirLanguagesAsKept::class,
];

/** The one other class that makes a value to seal: the seal, handing back what it opened. */
const THE_SEAL_THAT_OPENS = EncrypterSeal::class;

/**
 * What the phone may not keep: a repair offer, an agreement or a confirmation,
 * a decision about a request and the reason given for it, the key of a command
 * the stack may not have received, a credential, a session, and pairing
 * material.
 *
 * @var list<class-string>
 */
const WHAT_THE_PHONE_MAY_NOT_KEEP = [
    Offer::class,
    Repair::class,
    Confirmed::class,
    AgreedTo::class,
    ARunAgreedTo::class,
    AMoveAgreed::class,
    AnInvitationAgreed::class,
    AnUninstallAgreed::class,
    APluginInstallAgreed::class,
    APluginUpdateAgreed::class,
    APluginRemovalAgreed::class,
    ARemovalAgreed::class,
    AResetAgreed::class,
    AFillAgreed::class,
    HostingAgreed::class,
    TakingAnUpdate::class,
    Decided::class,
    WhatWasDecided::class,
    IdempotencyKey::class,
    Credential::class,
    Session::class,
    Pairing::class,
    APairingCode::class,
    APairingLine::class,
];

/** A `@param` or `@var` tag, and the rest of its line, which begins with the type it writes. */
const A_TYPE_IN_A_DOCBLOCK = '/@(?:param|var)\s+([^\n]*)/';

/** A name a docblock type is made of, which may be a class. */
const A_NAME_IN_A_TYPE = '/\\\\?[A-Z][A-Za-z0-9_]*(?:\\\\[A-Za-z0-9_]+)*/';

/**
 * The types a docblock's `@param` and `@var` tags write, each read to the space that ends it.
 *
 * A type can hold spaces of its own, `array<string, AnItem>` among them, so it
 * ends at the first space outside its brackets rather than at the first space.
 *
 * @return list<string>
 */
function theTypesADocblockWrites(string $docblock): array
{
    preg_match_all(A_TYPE_IN_A_DOCBLOCK, $docblock, $tags);
    $types = [];

    foreach ($tags[1] as $line) {
        $depth = 0;
        $type = '';

        foreach (mb_str_split($line) as $character) {
            $depth += match ($character) {
                '<', '(', '{', '[' => 1,
                '>', ')', '}', ']' => -1,
                default => 0,
            };

            if ($depth === 0 && trim($character) === '') {
                break;
            }

            $type .= $character;
        }

        $types[] = $type;
    }

    return $types;
}

/** Whether a source file calls `Unsealed::of`, by the name PHP resolves it to. */
function putsAValueInTheClear(string $file): bool
{
    $source = file_get_contents($file);
    $statements = is_string($source) ? new ParserFactory()->createForNewestSupportedVersion()->parse($source) : null;
    $resolved = new NodeTraverser(new NameResolver())->traverse($statements ?? []);

    return array_any(
        new NodeFinder()->findInstanceOf($resolved, StaticCall::class),
        static fn(StaticCall $call): bool => $call->class instanceof Name
            && $call->class->toString() === Unsealed::class
            && $call->name instanceof Identifier
            && $call->name->toLowerString() === 'of',
    );
}

/**
 * Every class in the source trees that makes a value to seal.
 *
 * @return list<string>
 */
function everyClassThatPutsAValueInTheClear(): array
{
    $found = [];

    foreach (OurCode::sourceFiles() as $file) {
        if (putsAValueInTheClear($file)) {
            $found[] = Imports::declaredName($file);
        }
    }

    sort($found);

    return $found;
}

/**
 * The class names a declared type is made of.
 *
 * @return list<string>
 */
function theClassesIn(?ReflectionType $type): array
{
    $found = [];
    $named = $type instanceof ReflectionUnionType || $type instanceof ReflectionIntersectionType ? $type->getTypes() : [$type];

    foreach ($named as $one) {
        if ($one instanceof ReflectionNamedType && ! $one->isBuiltin()) {
            $found[] = $one->getName();
        }
    }

    return $found;
}

/**
 * The classes a docblock names in its `@param` and `@var` types, resolved as the file it sits in would resolve them.
 *
 * @param ReflectionClass<object> $class
 *
 * @return list<string>
 */
function theClassesADocblockNames(string $docblock, ReflectionClass $class): array
{
    $file = $class->getFileName();
    $imported = is_string($file) ? Imports::of($file) : [];
    $found = [];

    foreach (theTypesADocblockWrites($docblock) as $type) {
        preg_match_all(A_NAME_IN_A_TYPE, $type, $names);

        foreach ($names[0] as $name) {
            $found[] = resolvedAgainst($name, $imported, $class->getNamespaceName());
        }
    }

    return array_values(array_filter($found, static fn(string $name): bool => class_exists($name) || enum_exists($name) || interface_exists($name)));
}

/**
 * A name as the file would resolve it: written out, imported, or in its own namespace.
 *
 * @param list<string> $imported
 */
function resolvedAgainst(string $name, array $imported, string $namespace): string
{
    if (str_starts_with($name, '\\')) {
        return ltrim($name, '\\');
    }

    foreach ($imported as $whole) {
        if ($whole === $name || str_ends_with($whole, sprintf('\\%s', $name))) {
            return $whole;
        }
    }

    return sprintf('%s\\%s', $namespace, $name);
}

/**
 * The types a class holds: its properties' declared types, and what their docblocks and its constructor's name.
 *
 * @param class-string $class
 *
 * @return list<string>
 */
function theTypesAClassHolds(string $class): array
{
    $reflected = new ReflectionClass($class);
    $docblocks = [(string) $reflected->getConstructor()?->getDocComment()];
    $found = [];

    foreach ($reflected->getProperties() as $property) {
        $found = [...$found, ...theClassesIn($property->getType())];
        $docblocks[] = (string) $property->getDocComment();
    }

    foreach ($docblocks as $docblock) {
        $found = [...$found, ...theClassesADocblockNames($docblock, $reflected)];
    }

    return $found;
}

/**
 * Every type reachable from one, itself included, following only this repository's own classes.
 *
 * @return list<string>
 */
function everyTypeHeldBy(string $type): array
{
    $seen = [];
    $waiting = [$type];

    while ($waiting !== []) {
        $next = array_pop($waiting);

        if (in_array($next, $seen, strict: true)) {
            continue;
        }

        $seen[] = $next;

        if (str_starts_with($next, 'Modules\\') && (class_exists($next) || enum_exists($next))) {
            $waiting = [...$waiting, ...theTypesAClassHolds($next)];
        }
    }

    return $seen;
}

/**
 * Every class a writer's public methods take, each with where it is taken.
 *
 * @param class-string $writer
 *
 * @return list<array{string, string}> the method and parameter, and the class it is typed as
 */
function everythingAWriterIsHanded(string $writer): array
{
    $found = [];

    foreach (new ReflectionClass($writer)->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        foreach ($method->getParameters() as $parameter) {
            foreach (theClassesIn($parameter->getType()) as $handed) {
                $found[] = [sprintf('%s::%s() takes $%s as %s', $writer, $method->name, $parameter->name, $handed), $handed];
            }
        }
    }

    return $found;
}

/**
 * Every place a writer is handed something the phone may not keep.
 *
 * @param class-string $writer
 *
 * @return list<string>
 */
function whatAWriterCouldWriteDown(string $writer): array
{
    $found = [];

    foreach (everythingAWriterIsHanded($writer) as [$where, $handed]) {
        foreach (array_intersect(everyTypeHeldBy($handed), WHAT_THE_PHONE_MAY_NOT_KEEP) as $forbidden) {
            $found[] = $forbidden === $handed ? $where : sprintf('%s, which holds %s', $where, $forbidden);
        }
    }

    return $found;
}

it('finds the writers it names, each an owner\'s *AsKept, and the types each is handed', function (): void {
    // The floors. A writer named here that writes nothing is a stale entry; a
    // walk that never reads a docblock would find nothing a list holds, and
    // pass every writer whose inputs keep their values in one.
    $stale = array_values(array_diff(THE_WRITERS_OF_WHAT_IS_KEPT, everyClassThatPutsAValueInTheClear()));
    $misnamed = array_values(array_filter(THE_WRITERS_OF_WHAT_IS_KEPT, static fn(string $writer): bool => ! str_ends_with($writer, 'AsKept')));

    expect($stale)->toBe([], 'These are named as writers and make nothing to seal.')
        ->and($misnamed)->toBe([])
        ->and(everyTypeHeldBy(TheHealthSummary::class))->toContain(AnAffectedItem::class);
});

it('names every agreement the kernel declares as something the phone may not keep', function (): void {
    $unnamed = [];

    foreach (Tree::filesUnder(Tree::at('app-modules/kernel/src/Api'), '.php') as $file) {
        $declared = Imports::declaredName($file);

        if (preg_match('/Agreed(To)?$/', $declared) === 1 && ! in_array($declared, WHAT_THE_PHONE_MAY_NOT_KEEP, strict: true)) {
            $unnamed[] = $declared;
        }
    }

    expect($unnamed)->toBe([], 'These are agreements the phone may not keep, and nothing here says so.');
});

// A13 — what the phone keeps is put in the clear to be sealed only by its owner's writer, and no writer is handed an offer, an agreement, a command's idempotency key, a credential, a session or pairing material
it('only a declared writer puts a value in the clear to be kept', function (): void {
    $offenders = array_values(array_diff(everyClassThatPutsAValueInTheClear(), [...THE_WRITERS_OF_WHAT_IS_KEPT, THE_SEAL_THAT_OPENS]));

    expect($offenders)->toBe([], sprintf(
        "These make a value to seal and are not a writer of what the phone keeps:\n  %s\n\n"
        . 'What the phone keeps is written by its owner\'s *AsKept, in a shape it can read back, '
        . 'and each one is named in this test. A class that makes an Unsealed anywhere else is a '
        . 'second way into the phone\'s storage that no list of what is kept describes (A13).',
        implode("\n  ", $offenders),
    ));
});

// A13 — what the phone keeps is put in the clear to be sealed only by its owner's writer, and no writer is handed an offer, an agreement, a command's idempotency key, a credential, a session or pairing material
it('no writer is handed an offer, an agreement, a command\'s key, a credential, a session or pairing material', function (): void {
    $offenders = [];

    foreach (THE_WRITERS_OF_WHAT_IS_KEPT as $writer) {
        $offenders = [...$offenders, ...whatAWriterCouldWriteDown($writer)];
    }

    expect($offenders)->toBe([], sprintf(
        "These writers are handed something the phone may not keep:\n  %s\n\n"
        . 'A writer handed a session, an offer or the key of a command not yet received is one '
        . 'line from writing it down beside the readings. What is kept is what its owner decided '
        . 'to keep, and none of these is (A13).',
        implode("\n  ", $offenders),
    ));
});
