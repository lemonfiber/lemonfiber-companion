<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AnEventHeard;
use Modules\Kernel\Api\EventHasNoName;
use Modules\Kernel\Api\WhereAStreamResumes;
use Tests\Support\WhereItResumes;

it('hands the event it was given to the arm that resumes after one', function (): void {
    expect(WhereItResumes::said(WhereAStreamResumes::after(AnEventHeard::named('run-7'))))->toBe('after:run-7');
});

it('answers the other arm for a stream opened from the start', function (): void {
    expect(WhereItResumes::said(WhereAStreamResumes::fromTheStart()))->toBe('from-the-start');
});

it('keeps an event exactly as the stack wrote it', function (): void {
    expect(AnEventHeard::named(' run-7 ')->shown())->toBe(' run-7 ');
});

it('refuses an empty identifier, which names nothing to resume after', function (): void {
    AnEventHeard::named('');
})->throws(EventHasNoName::class, 'nothing to resume after');
