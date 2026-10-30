<?php

declare(strict_types=1);

use Lemonfiber\Companion\PHPStan\Rules\CompareInConstantTimeRule;
use Modules\Kernel\Api\ComparedInConstantTime;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\StackId;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;

// The analyser's judgement on one comparison, over the properties marked in the
// class it stands in, and the values that are marked.

/** The first comparison in an expression, parsed. */
function theComparisonIn(string $expression): BinaryOp
{
    $parsed = new ParserFactory()->createForNewestSupportedVersion()->parse(sprintf('<?php %s;', $expression));
    $found = new NodeFinder()->findFirstInstanceOf($parsed ?? [], BinaryOp::class);

    return $found instanceof BinaryOp ? $found : throw new RuntimeException('The fixture parsed to no comparison.');
}

it('refuses a marked value compared by identity, on either side and either way round', function (string $comparison): void {
    expect(CompareInConstantTimeRule::comparedByIdentity(theComparisonIn($comparison), ['token']))->toBe(['token']);
})->with([
    'equal' => ['$this->token === $other->token'],
    'not equal' => ['$this->token !== $other->token'],
    'against a literal' => ['\'a-token\' === $this->token'],
]);

it('lets a marked value through hash_equals, and an unmarked one through identity', function (string $comparison): void {
    expect(CompareInConstantTimeRule::comparedByIdentity(theComparisonIn($comparison), ['token']))->toBe([]);
})->with([
    'compared in constant time' => ['hash_equals($this->token, $other->token) === true'],
    'another value' => ['$this->name === $other->name'],
    'not a comparison of identity' => ['$this->token . $other->token'],
]);

it('marks the values a guess could be timed against', function (): void {
    $marked = static fn(ReflectionClass $class): array => array_values(array_map(
        static fn(ReflectionProperty $property): string => $property->getName(),
        array_filter($class->getProperties(), static fn(ReflectionProperty $property): bool => $property->getAttributes(ComparedInConstantTime::class) !== []),
    ));

    expect($marked(new ReflectionClass(Session::class)))->toBe(['token'])
        ->and($marked(new ReflectionClass(Fingerprint::class)))->toBe(['digest'])
        ->and($marked(new ReflectionClass(StackId::class)))->toBe(['id']);
});
