<?php

declare(strict_types=1);

namespace Modules\Connection\Tests\Internal;

use function expect;
use function it;

use Modules\Connection\Internal\ThePhonesSettings;
use Modules\Connection\Internal\TheSettingsAsKept;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\HowLongReadingsAreKept;
use Modules\Kernel\Api\KeptFor;
use Modules\Kernel\Api\LockAfter;
use Modules\Kernel\Api\Shape;
use Modules\Kernel\Api\Unsealed;

/** How long readings are kept, as one word. */
function howLongTheSettingsKeepReadings(ThePhonesSettings $settings): string
{
    return $settings->readingsKept->either(
        days: static fn(int $days): Code => Code::of((string) $days),
        untilRemoved: static fn(): Code => Code::of('until removed'),
    )->shown();
}

it('reads every lock choice back in the shape it was written in', function (): void {
    foreach (LockAfter::cases() as $after) {
        $settings = ThePhonesSettings::standard()->lockingAfter($after);

        expect(TheSettingsAsKept::read(Shape::One, TheSettingsAsKept::written($settings))->lockAfter)->toBe($after);
    }
});

it('reads how long readings are kept back, a count or until removed', function (HowLongReadingsAreKept $kept, string $said): void {
    $settings = ThePhonesSettings::standard()->keepingReadingsFor($kept);

    expect(howLongTheSettingsKeepReadings(TheSettingsAsKept::read(Shape::One, TheSettingsAsKept::written($settings))))->toBe($said);
})->with([
    'a count offered by name' => [HowLongReadingsAreKept::for(KeptFor::NinetyDays), '90'],
    'a count typed' => [HowLongReadingsAreKept::days(45), '45'],
    'until removed' => [HowLongReadingsAreKept::untilRemoved(), 'until removed'],
]);

it('reads the lock it cannot make out as immediately', function (string $written): void {
    expect(TheSettingsAsKept::read(Shape::One, Unsealed::of($written))->lockAfter)->toBe(LockAfter::Immediately);
})->with(['', 'null', '[]', '{"lock_after":7}', '{"lock_after":"a fortnight"}', '{"lock_after":"one_hour"}']);

it('reads how long readings are kept as thirty days where it cannot make it out', function (string $written): void {
    expect(howLongTheSettingsKeepReadings(TheSettingsAsKept::read(Shape::One, Unsealed::of($written))))->toBe('30');
})->with([
    'not JSON' => ['not json'],
    'no setting' => ['{}'],
    'a count out of range' => ['{"keep_readings":400}'],
    'none at all' => ['{"keep_readings":0}'],
    'a count written as text' => ['{"keep_readings":"45"}'],
    'a word it does not know' => ['{"keep_readings":"forever"}'],
]);

it('carries one setting over where the other cannot be made out', function (): void {
    $read = TheSettingsAsKept::read(Shape::One, Unsealed::of('{"lock_after":"OneHour","keep_readings":"forever"}'));

    expect($read->lockAfter)->toBe(LockAfter::OneHour)
        ->and(howLongTheSettingsKeepReadings($read))->toBe('30');
});

it('reads a row an earlier build wrote with the lock alone', function (): void {
    $read = TheSettingsAsKept::read(Shape::One, Unsealed::of('{"lock_after":"FiveMinutes"}'));

    expect($read->lockAfter)->toBe(LockAfter::FiveMinutes)
        ->and(howLongTheSettingsKeepReadings($read))->toBe('30');
});
