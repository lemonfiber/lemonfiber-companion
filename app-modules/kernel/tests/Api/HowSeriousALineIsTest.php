<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\HowSeriousALineIs;

it('counts an error and a fatal line as errors, and nothing else', function (HowSeriousALineIs $level, bool $error, bool $warning): void {
    expect($level->isAnError())->toBe($error)
        ->and($level->isAWarning())->toBe($warning);
})->with([
    'trace' => [HowSeriousALineIs::Trace, false, false],
    'debug' => [HowSeriousALineIs::Debug, false, false],
    'info' => [HowSeriousALineIs::Info, false, false],
    'warn' => [HowSeriousALineIs::Warn, false, true],
    'error' => [HowSeriousALineIs::Error, true, false],
    'fatal' => [HowSeriousALineIs::Fatal, true, false],
]);

it('is called on a screen by a key under its own group', function (): void {
    expect(HowSeriousALineIs::Warn->saidOnTheScreen())->toBe('health.level.warn');
});
