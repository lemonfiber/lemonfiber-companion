<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ABundleHasNoName;
use Modules\Kernel\Api\AWrittenBundle;

it('is fetched by the last segment of the path it was written to, and keeps the path', function (string $path, string $name): void {
    expect(AWrittenBundle::at($path)->name())->toBe($name)
        ->and(AWrittenBundle::at($path)->path())->toBe($path);
})->with([
    'a path' => ['/home/op/.config/lemonfiber/bundles/lemonfiber-support.tar.gz', 'lemonfiber-support.tar.gz'],
    'a name alone' => ['lemonfiber-support.tar.gz', 'lemonfiber-support.tar.gz'],
    'a name starting with a dot' => ['/bundles/.hidden', '.hidden'],
]);

it('refuses a path that ends in no file it could be fetched by', function (string $path): void {
    expect(static fn(): AWrittenBundle => AWrittenBundle::at($path))
        ->toThrow(ABundleHasNoName::class, 'A written support bundle has to be at a path ending in a file name');
})->with([
    'nothing' => [''],
    'a directory' => ['/home/op/bundles/'],
    'the directory itself' => ['/home/op/bundles/.'],
    'a step up' => ['/home/op/bundles/..'],
    'a bare dot' => ['.'],
    'a bare step up' => ['..'],
]);
