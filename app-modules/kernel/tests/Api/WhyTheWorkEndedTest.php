<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_map;
use function array_unique;
use function array_values;
use function basename;
use function class_exists;
use function expect;
use function glob;
use function in_array;
use function is_object;
use function it;

use LogicException;

use function method_exists;

use Modules\Kernel\Api\WhyTheWorkEnded;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

/**
 * Every outcome that can end before it finished, by its class name.
 *
 * @return list<class-string>
 */
function everyOutcomeThatEnds(): array
{
    $classes = [];

    $files = glob(sprintf('%s/../../src/Api/*.php', __DIR__));

    foreach ($files === false ? [] : $files as $file) {
        $class = sprintf('Modules\\Kernel\\Api\\%s', basename($file, '.php'));

        if (class_exists($class) && method_exists($class, 'noOutcome')) {
            $classes[] = $class;
        }
    }

    return $classes;
}

/** What the class's own way of saying the work ended builds, given why, or given nothing where the stack said nothing. */
function endedAs(string $class, ?WhyTheWorkEnded $why): object
{
    $ended = $why instanceof WhyTheWorkEnded
        ? new ReflectionMethod($class, 'ended')->invoke(null, $why)
        : new ReflectionMethod($class, 'noOutcome')->invoke(null);

    if (! is_object($ended)) {
        throw new LogicException(sprintf('%s::ended built no outcome', $class));
    }

    return $ended;
}

/**
 * The words the ended arm of every way of reading an outcome is handed.
 *
 * @return list<string>
 */
function whatEachReadingIsHanded(object $outcome): array
{
    $handed = [];

    foreach (new ReflectionClass($outcome)->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        $names = array_map(static fn(ReflectionParameter $parameter): string => $parameter->getName(), $method->getParameters());

        if ($method->isStatic() || ! in_array('ended', $names, strict: true)) {
            continue;
        }

        $arms = [];

        foreach ($names as $name) {
            $arms[$name] = $name === 'ended'
                ? static fn(?WhyTheWorkEnded $why): TheWordCarriedOut => new TheWordCarriedOut($why->value ?? 'unsaid')
                : static fn(): TheWordCarriedOut => new TheWordCarriedOut('another arm');
        }

        /** @var TheWordCarriedOut $word */
        $word = $method->invokeArgs($outcome, $arms);
        $handed[] = $word->said;
    }

    return $handed;
}

it('finds every outcome that can end before it finished', function (): void {
    expect(everyOutcomeThatEnds())->toHaveCount(21);
});

it('hands every ended arm why the work ended, or nothing where the stack did not say', function (string $class): void {
    expect(array_values(array_unique(whatEachReadingIsHanded(endedAs($class, WhyTheWorkEnded::LetGo)))))->toBe(['let-go'])
        ->and(array_values(array_unique(whatEachReadingIsHanded(endedAs($class, WhyTheWorkEnded::Released)))))->toBe(['released'])
        ->and(array_values(array_unique(whatEachReadingIsHanded(endedAs($class, null)))))->toBe(['unsaid']);
})->with(everyOutcomeThatEnds(...));
