<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

// NativePHP runs `storage:link` on every launch. The app serves no files to
// anybody, so there is nothing to link, and the command has nothing it can
// fail at before the first frame.

it('declares no link for the launch to make', function (): void {
    expect(config('filesystems.links'))->toBe([]);
});

/** What the launch's `storage:link` exits with. Through the facade because `$this->artisan()` needs Mockery, which this repository does not install. */
function whatLinkingAtLaunchExitsWith(): int
{
    return Artisan::call('storage:link');
}

it('runs the launch command without making anything', function (): void {
    expect(whatLinkingAtLaunchExitsWith())->toBe(0)
        ->and(file_exists(public_path('storage')))->toBeFalse();
});
