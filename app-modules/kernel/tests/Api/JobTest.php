<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\JobHasNoName;

it('holds the name a stack gave its work, without the space around it', function (): void {
    expect(Job::named("  install-tdarr-7\n")->shown())->toBe('install-tdarr-7');
});

it('refuses an acknowledgement that names no work', function (string $named): void {
    expect(static fn(): Job => Job::named($named))->toThrow(JobHasNoName::class);
})->with([
    'nothing' => [''],
    'only space' => [" \t\n"],
]);

it('is the same work only under the same name', function (): void {
    expect(Job::named('install-tdarr-7')->is(Job::named(' install-tdarr-7 ')))->toBeTrue()
        ->and(Job::named('install-tdarr-7')->is(Job::named('install-tdarr-8')))->toBeFalse();
});
