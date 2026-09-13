<?php

declare(strict_types=1);

namespace Tests\Support;

use function array_filter;
use function array_values;
use function implode;
use function in_array;

use Modules\Kernel\Api\IdempotencyKey;
use Modules\Kernel\Api\Outcome;
use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionType;
use ReflectionUnionType;

use function sprintf;
use function str_starts_with;

/**
 * The published surface of every module, reflected.
 *
 * Pest's `arch()` expectations cannot see a signature — they read imports and
 * names. Every rule about what an `Api` method may take or answer therefore
 * asks PHP directly, the same way NoGlobalStateTest does.
 *
 * These helpers return nothing while the modules are empty, which is honest:
 * the rules apply the moment there is something to apply them to, and the
 * planted-violation runs in the commit history are what prove they bite.
 */
final readonly class ApiSurface
{
    // Named by class rather than as a string now that the kernel publishes
    // them. A string here is a reference nothing checks: renaming `Outcome`
    // would leave M1 comparing against a class that no longer exists, and a
    // comparison that never matches reads exactly like a rule that holds.

    /** The one type a refusal crosses a module boundary as (C1). */
    public const string OUTCOME = Outcome::class;

    /** The one type that makes a command safe to retry (M3). */
    public const string IDEMPOTENCY_KEY = IdempotencyKey::class;

    /**
     * Magic methods PHP dictates the return type of.
     *
     * Not a convenience list: each of these must answer an array by the
     * language's own rules, so a rule about published signatures reported on
     * them would be a rule about PHP rather than about this codebase.
     */
    private const array SHAPED_BY_PHP = ['__debugInfo', '__serialize', '__unserialize', '__sleep'];

    /**
     * Everything a class names, against where it names it.
     *
     * Methods and properties both. A rule that reads only methods is a rule a
     * field walks past — and a promoted property is found by the constructor
     * while a plain typed one is not, so the two shapes have to be asked for
     * separately.
     *
     * Every method, not only the published ones: a private helper handed a type
     * is a type the class can reach, whoever may call it.
     *
     * @param ReflectionClass<object> $class
     *
     * @return list<array{string, list<string>}>
     */
    public static function namedBy(ReflectionClass $class): array
    {
        $named = [];

        foreach ($class->getMethods() as $method) {
            $types = self::namesIn($method->getReturnType());

            foreach ($method->getParameters() as $parameter) {
                $types = [...$types, ...self::namesIn($parameter->getType())];
            }

            $named[] = [self::describe($method), $types];
        }

        foreach ($class->getProperties() as $property) {
            $named[] = [
                sprintf('%s::$%s', $class->getName(), $property->getName()),
                self::namesIn($property->getType()),
            ];
        }

        return $named;
    }

    /**
     * Everything a class *answers with*, against where it answers it.
     *
     * Return types and readable public properties, and no parameters. The
     * distinction is the whole of some rules: `Repair::offered()` takes an
     * `Effects` and that is how one is built, while a method *answering* an
     * `Effects` is a clause handed out on its own. Merging the two makes the
     * constructor look like the leak.
     *
     * @param ReflectionClass<object> $class
     *
     * @return list<array{string, list<string>}>
     */
    public static function answeredBy(ReflectionClass $class): array
    {
        $answers = [];

        foreach (self::publicMethodsOf($class) as $method) {
            $answers[] = [self::describe($method), self::namesIn($method->getReturnType())];
        }

        foreach ($class->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $answers[] = [
                sprintf('%s::$%s', $class->getName(), $property->getName()),
                self::namesIn($property->getType()),
            ];
        }

        return $answers;
    }

    /**
     * A class by name, as the helpers here will take it.
     *
     * `new ReflectionClass(Repair::class)` is a `ReflectionClass<Repair>`, and
     * `ReflectionClass`'s template is not covariant — so the analyser refuses it
     * everywhere a `ReflectionClass<object>` is wanted. Written once here rather
     * than beside each rule that reflects over a named class.
     *
     * @param class-string $name
     *
     * @return ReflectionClass<object>
     */
    public static function reflect(string $name): ReflectionClass
    {
        return new ReflectionClass($name);
    }

    /**
     * Every published class under `Modules\<Name>\Api\<segments>`.
     *
     * @return list<ReflectionClass<object>>
     */
    public static function classesIn(string ...$segments): array
    {
        $found = [];

        foreach (Module::all() as $module) {
            $prefix = sprintf('%s\\%s\\', $module->namespace, implode('\\', ['Api', ...$segments]));

            foreach ($module->classNames() as $name) {
                if (str_starts_with($name, $prefix)) {
                    $found[] = new ReflectionClass($name);
                }
            }
        }

        return $found;
    }

    /**
     * The public methods a class declares itself, constructor excluded.
     *
     * Inherited methods belong to whatever declared them and are that class's
     * problem; the constructor is judged by its own rules (M3).
     *
     * An enum's `cases()`, `from()` and `tryFrom()` are excluded too, and that
     * is the reason `isInternal()` is asked rather than a list of names being
     * kept here. PHP declares those three on every backed enum with signatures
     * nobody chose: `cases()` answers an array, which D1 refuses, and
     * `tryFrom()` answers null, which C2 refuses. Reported, they would make D4
     * — a closed set is an enum — impossible to obey, and the cure for each
     * would be to stop using the language's own accessor. They carry no design
     * decision, so there is nothing for these rules to find in them.
     *
     * @param ReflectionClass<object> $class
     *
     * @return list<ReflectionMethod>
     */
    public static function publicMethodsOf(ReflectionClass $class): array
    {
        return array_values(array_filter(
            $class->getMethods(ReflectionMethod::IS_PUBLIC),
            static fn(ReflectionMethod $method): bool => $method->getDeclaringClass()->getName() === $class->getName()
                && ! $method->isConstructor()
                && ! $method->isInternal()
                && ! in_array($method->getName(), self::SHAPED_BY_PHP, strict: true),
        ));
    }

    /**
     * Whether a method is a named constructor — static, answering its own type.
     *
     * This is the one place a primitive may cross into a module (D2). A value
     * object is built from a string exactly once, in a method named for what
     * the string meant, and every call site after that holds the type.
     */
    public static function isNamedConstructor(ReflectionMethod $method): bool
    {
        if (! $method->isStatic()) {
            return false;
        }

        $returns = $method->getReturnType();

        if (! $returns instanceof ReflectionNamedType) {
            return false;
        }

        $name = $returns->getName();

        return in_array($name, ['self', 'static', $method->getDeclaringClass()->getName()], strict: true);
    }

    /**
     * Every named type in a type, with a union flattened into its members.
     *
     * @return list<string>
     */
    public static function namesIn(?ReflectionType $type): array
    {
        if ($type instanceof ReflectionNamedType) {
            return [$type->getName()];
        }

        // An intersection is read as well as a union. It was not, and the gap
        // was the usual shape: `A&B` answered an empty list, so a rule asking
        // "does this signature mention X" quietly said no for every
        // intersection it met. A rule whose mechanism is narrower than its
        // sentence is worse than no rule, because it reports a pass.
        if (! $type instanceof ReflectionUnionType && ! $type instanceof ReflectionIntersectionType) {
            return [];
        }

        $found = [];

        foreach ($type->getTypes() as $member) {
            if ($member instanceof ReflectionNamedType) {
                $found[] = $member->getName();
            }
        }

        return $found;
    }

    /** Where a method is, in the form a failure message should name it. */
    public static function describe(ReflectionMethod $method): string
    {
        return sprintf('%s::%s()', $method->getDeclaringClass()->getName(), $method->getName());
    }
}
