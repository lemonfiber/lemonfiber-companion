<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function expect;
use function it;
use function iterator_to_array;
use function json_encode;

use Lemonfiber\Sdk\Events\ServerEvent;
use Modules\Kernel\Api\AnAlertsName;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\WhatTheCoreDecided;
use Modules\Sdk\Api\AlertsHeard;

use function str_repeat;

use Tests\Support\Fakes\NotesKeptInMemory;

// `alert` is stood in for and not judged: the case is a payload that is not one, which no stack sends.
it('skips an alert whose payload is not one, and notes only why', function (): void {
    $notes = new NotesKeptInMemory();
    $event = new ServerEvent('alert', (string) json_encode(['api_version' => 1, 'kind' => 'alert', 'data' => 'nothing']));

    expect(iterator_to_array(AlertsHeard::in($notes, $event), preserve_keys: false))->toBe([])
        ->and($notes->noted())->toBe(['alert: skipped one this app cannot read (Modules\Sdk\Api\AlertIsUnreadable)']);
});

// `alert` is stood in for and not judged: the case is a name that is only spaces, which a stack holding to the contract does not send.
it('files an alert named only in spaces under its kind, as one the core gave no name', function (): void {
    $notes = new NotesKeptInMemory();
    $event = new ServerEvent('alert', (string) json_encode(['api_version' => 1, 'kind' => 'alert', 'data' => ['kind' => 'vpn.down', 'moment' => 'onset', 'id' => '  ']]));
    $stack = StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST)));
    $filed = [];

    foreach (AlertsHeard::in($notes, $event) as $alert) {
        $filed[] = $alert->filedUnder()->filedFor($stack);
    }

    expect($filed)->toBe([AnAlertsName::byItsKind(WhatTheCoreDecided::toSay('vpn.down'))->filedFor($stack)])
        ->and($notes->noted())->toBe([]);
});
