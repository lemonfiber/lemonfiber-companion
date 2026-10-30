<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_map;

use Attribute;

use function expect;
use function it;

use Modules\Kernel\Api\ComparedInConstantTime;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\StackId;
use ReflectionClass;

it('marks a property and a constructor parameter, and nothing else', function (): void {
    $declared = new ReflectionClass(ComparedInConstantTime::class)->getAttributes(Attribute::class)[0]->newInstance();

    expect($declared->flags)->toBe(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER);
});

it('is made where it marks each secret, as the promoted parameter and as the property it becomes', function (ReflectionClass $marked): void {
    $made = [];

    foreach ([...$marked->getConstructor()?->getParameters() ?? [], ...$marked->getProperties()] as $where) {
        foreach ($where->getAttributes(ComparedInConstantTime::class) as $attribute) {
            $made[] = $attribute->newInstance();
        }
    }

    expect(array_map(static fn(ComparedInConstantTime $one): string => $one::class, $made))->toBe([ComparedInConstantTime::class, ComparedInConstantTime::class]);
})->with([
    'a session token' => [new ReflectionClass(Session::class)],
    'a certificate digest' => [new ReflectionClass(Fingerprint::class)],
    'a stack id' => [new ReflectionClass(StackId::class)],
]);
