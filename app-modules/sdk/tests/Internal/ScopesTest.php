<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Internal;

use function expect;
use function it;

use Modules\Kernel\Api\KeepingSaysNothing;
use Modules\Kernel\Api\ScopeOfACopy;
use Modules\Kernel\Api\ServiceIsUnnamed;
use Modules\Sdk\Api\ScopeIsUnreadable;
use Modules\Sdk\Internal\Scopes;
use Tests\Support\WhatAScopeSays;

it('reads each of the three scopes, and what each names', function (mixed $scope, string $said): void {
    expect(WhatAScopeSays::of(Scopes::in(['scope' => $scope])))->toBe($said);
})->with([
    'the whole stack' => [['scope' => 'whole_stack'], 'whole'],
    'one service' => [['scope' => 'service', 'name' => 'sonarr'], 'service:sonarr'],
    'an existing setup' => [
        ['scope' => 'existing', 'project' => 'media', 'trees' => [
            ['host_path' => '/srv/arr', 'archive_path' => 'existing/0'],
            ['host_path' => '/srv/plex', 'archive_path' => 'existing/1'],
        ]],
        'existing:media:/srv/arr,/srv/plex',
    ],
    'an existing setup with no trees' => [['scope' => 'existing', 'project' => 'media', 'trees' => []], 'existing:media:'],
]);

it('refuses a scope it cannot read, naming the field, rather than reading it as the whole stack', function (array $data, string $named): void {
    expect(fn(): ScopeOfACopy => Scopes::in($data))->toThrow(ScopeIsUnreadable::class, $named);
})->with([
    'no scope' => [[], '`scope`'],
    'a scope that is not a table' => [['scope' => 'whole_stack'], '`scope`'],
    'no word' => [['scope' => []], '`scope`'],
    'a word that is not text' => [['scope' => ['scope' => 3]], '`scope`'],
    'a word this app has no case for' => [['scope' => ['scope' => 'everything']], '`scope`'],
    'a service with no name' => [['scope' => ['scope' => 'service']], '`name`'],
    'a service whose name is not text' => [['scope' => ['scope' => 'service', 'name' => 7]], '`name`'],
    'a setup with no project' => [['scope' => ['scope' => 'existing', 'trees' => []]], '`project`'],
    'a setup with no trees' => [['scope' => ['scope' => 'existing', 'project' => 'media']], '`trees`'],
    'trees that are not a list' => [['scope' => ['scope' => 'existing', 'project' => 'media', 'trees' => ['first' => []]]], '`trees`'],
    'trees that are a word' => [['scope' => ['scope' => 'existing', 'project' => 'media', 'trees' => 'all']], '`trees`'],
]);

it('refuses a tree it cannot read by its position, first or later', function (mixed $trees, string $position): void {
    expect(fn(): ScopeOfACopy => Scopes::in(['scope' => ['scope' => 'existing', 'project' => 'media', 'trees' => $trees]]))
        ->toThrow(ScopeIsUnreadable::class, $position);
})->with([
    'the first, not a table' => [['/srv/arr'], 'Tree 0 '],
    'a later one, with no host path' => [[['host_path' => '/srv/arr'], ['archive_path' => 'existing/1']], 'Tree 1 '],
    'a later one, whose host path is not text' => [[['host_path' => '/srv/arr'], ['host_path' => null]], 'Tree 1 '],
]);

it('leaves a blank name to the kernel to refuse', function (): void {
    expect(fn(): ScopeOfACopy => Scopes::in(['scope' => ['scope' => 'service', 'name' => '  ']]))->toThrow(ServiceIsUnnamed::class);
    expect(fn(): ScopeOfACopy => Scopes::in(['scope' => ['scope' => 'existing', 'project' => ' ', 'trees' => []]]))->toThrow(KeepingSaysNothing::class, '`project`');
    expect(fn(): ScopeOfACopy => Scopes::in(['scope' => ['scope' => 'existing', 'project' => 'media', 'trees' => [['host_path' => ' ']]]]))->toThrow(KeepingSaysNothing::class);
});
