<?php

declare(strict_types=1);

it('leaves what only a developer\'s checkout holds out of every build', function (): void {
    expect(config('nativephp.cleanup_exclude_files'))->toContain('.phpstan-cache', '.branch-backups', 'coverage');
});

it('keeps what the checkout\'s own settings leave out, once each', function (): void {
    $leftOut = config('nativephp.cleanup_exclude_files');

    expect($leftOut)->toContain('storage/logs/laravel.log')
        ->and($leftOut)->toHaveCount(count(array_unique(is_array($leftOut) ? $leftOut : [], SORT_REGULAR)));
});
