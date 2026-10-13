<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function expect;
use function it;
use function iterator_to_array;
use function json_encode;

use Lemonfiber\Sdk\Events\ServerEvent;
use Modules\Sdk\Api\AlertsHeard;
use Tests\Support\Fakes\NotesKeptInMemory;

// `alert` is stood in for and not judged: the case is a payload that is not one, which no stack sends.
it('skips an alert whose payload is not one, and notes only why', function (): void {
    $notes = new NotesKeptInMemory();
    $event = new ServerEvent('alert', (string) json_encode(['api_version' => 1, 'kind' => 'alert', 'data' => 'nothing']));

    expect(iterator_to_array(AlertsHeard::in($notes, $event), preserve_keys: false))->toBe([])
        ->and($notes->noted())->toBe(['alert: skipped one this app cannot read (Modules\Sdk\Api\AlertIsUnreadable)']);
});
