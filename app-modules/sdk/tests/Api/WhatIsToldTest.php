<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function expect;
use function implode;
use function it;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\WhatTheOperatorIsTold;
use Modules\Sdk\Api\AlertsAreUnreadable;
use Modules\Sdk\Api\WhatIsTold;

use function sprintf;

use Tests\Support\WhatTheContractAccepts;

/**
 * An `alerts` envelope holding whatever the case under test is about.
 *
 * @param array<mixed> $data
 *
 * @return Envelope<mixed>
 */
function alertsSaying(array $data): Envelope
{
    return new Envelope(1, 'alerts', $data);
}

/**
 * A setting with every part the reader insists on, and these exceptions.
 *
 * @param list<mixed> $exceptions
 *
 * @return array<string, mixed>
 */
function aQuietSetting(array $exceptions = []): array
{
    return ['preset' => 'quiet', 'means' => 'Only what needs you today', 'exceptions' => $exceptions, 'changed' => false, 'rehearsed' => false];
}

/** Everything a setting says, as one line. */
function everythingToldIn(WhatTheOperatorIsTold $told): string
{
    $exceptions = [];

    foreach ($told->exceptions() as $event) {
        $exceptions[] = sprintf('%s=%s', $event->kind(), $event->heard()->value);
    }

    return sprintf('%s/%s/[%s]', $told->preset(), $told->means(), implode(',', $exceptions));
}

it('N10-R8 — reads the preset, what it means and every exception, in the stack\'s order', function (): void {
    $told = WhatIsTold::in(alertsSaying(aQuietSetting([['kind' => 'update-available', 'wanted' => false], ['kind' => 'disk-low', 'wanted' => true]])));

    expect(everythingToldIn($told))->toBe('quiet/Only what needs you today/[update-available=silenced,disk-low=heard]');
});

it('reads a setting with nothing set apart', function (): void {
    expect(everythingToldIn(WhatIsTold::in(alertsSaying(aQuietSetting()))))->toBe('quiet/Only what needs you today/[]');
});

it('does not read what a call to change it came to', function (): void {
    // `changed` and `rehearsed` describe a call this app never makes, so what
    // they say changes nothing here.
    $asRehearsed = [...aQuietSetting(), 'changed' => true, 'rehearsed' => true];

    expect(everythingToldIn(WhatIsTold::in(alertsSaying($asRehearsed))))->toBe('quiet/Only what needs you today/[]');
});

it('refuses a setting missing its preset, what it means, or its exceptions', function (string $field): void {
    $setting = aQuietSetting();
    unset($setting[$field]);

    expect(fn(): WhatTheOperatorIsTold => WhatIsTold::in(alertsSaying($setting)))->toThrow(AlertsAreUnreadable::class, sprintf('`%s`', $field));
})->with(['preset', 'means', 'exceptions']);

it('refuses a preset or meaning that is blank or not text', function (mixed $said): void {
    expect(fn(): WhatTheOperatorIsTold => WhatIsTold::in(alertsSaying([...aQuietSetting(), 'means' => $said])))->toThrow(AlertsAreUnreadable::class, '`means`');
})->with([['  '], [null], [3]]);

it('refuses an envelope whose data is not a payload, or whose exceptions are not a list', function (): void {
    expect(fn(): WhatTheOperatorIsTold => WhatIsTold::in(new Envelope(1, 'alerts', 'nothing')))->toThrow(AlertsAreUnreadable::class, '`data`')
        ->and(fn(): WhatTheOperatorIsTold => WhatIsTold::in(alertsSaying([...aQuietSetting(), 'exceptions' => 'some'])))->toThrow(AlertsAreUnreadable::class, '`exceptions`');
});

it('refuses an exception that is not one, rather than dropping it', function (): void {
    expect(fn(): WhatTheOperatorIsTold => WhatIsTold::in(alertsSaying(aQuietSetting([['kind' => 'disk-low', 'wanted' => true], 'x']))))
        ->toThrow(AlertsAreUnreadable::class, 'Exception 1');
});

it('refuses an exception whose kind or answer is unreadable', function (array $row, string $field): void {
    expect(fn(): WhatTheOperatorIsTold => WhatIsTold::in(alertsSaying(aQuietSetting([$row]))))
        ->toThrow(AlertsAreUnreadable::class, sprintf('Exception 0 in the alerts envelope has no readable `%s`', $field));
})->with([
    [['wanted' => true], 'kind'],
    [['kind' => ' ', 'wanted' => true], 'kind'],
    [['kind' => 4, 'wanted' => true], 'kind'],
    [['kind' => 'disk-low'], 'wanted'],
    [['kind' => 'disk-low', 'wanted' => 'yes'], 'wanted'],
]);

it('refuses one kind set apart twice, by position', function (): void {
    expect(fn(): WhatTheOperatorIsTold => WhatIsTold::in(alertsSaying(aQuietSetting([['kind' => 'disk-low', 'wanted' => true], ['kind' => 'disk-low', 'wanted' => false]]))))
        ->toThrow(AlertsAreUnreadable::class, 'Exception 1 in the alerts envelope sets `disk-low` apart again');
});

it('judges the payload these cases are built on against the contract', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('AlertsEnvelope', ['api_version' => 1, 'kind' => 'alerts', 'data' => aQuietSetting([['kind' => 'disk-low', 'wanted' => true]])]))
        ->toBe([]);
});
