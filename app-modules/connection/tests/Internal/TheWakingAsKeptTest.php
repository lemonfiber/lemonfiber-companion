<?php

declare(strict_types=1);

namespace Modules\Connection\Tests\Internal;

use function expect;
use function it;

use Modules\Connection\Internal\TheWakingAsKept;
use Modules\Kernel\Api\ADistributor;
use Modules\Kernel\Api\APushName;
use Modules\Kernel\Api\AWakeFingerprint;
use Modules\Kernel\Api\AWakeRegistered;
use Modules\Kernel\Api\AWakingKey;
use Modules\Kernel\Api\AWakingSecret;
use Modules\Kernel\Api\HowThisPhoneIsWoken;
use Modules\Kernel\Api\Shape;
use Modules\Kernel\Api\Unsealed;

use function sprintf;
use function str_repeat;
use function strlen;

use Tests\Support\TheWordCarriedOut;

function aWakeKept(): AWakeRegistered
{
    return new AWakeRegistered(
        APushName::of('galaxy-a51-7f3a'),
        AWakeFingerprint::of('2fb15b007051cfa9'),
        AWakingKey::of(sprintf("\x04%s", str_repeat("\x01", 64))),
        AWakingSecret::of(str_repeat('s', 16)),
    );
}

/** How the phone is woken as read back, as one word. */
function wokenAsRead(HowThisPhoneIsWoken $woken): string
{
    return $woken->either(
        off: static fn(): TheWordCarriedOut => new TheWordCarriedOut('off'),
        on: static fn(ADistributor $through, AWakeRegistered $wake): TheWordCarriedOut => new TheWordCarriedOut(sprintf('on %s %s %s %d %s', $through->package(), $through->called(), $wake->fingerprint->shown(), strlen($wake->key->bytes()), $wake->secret->bytes())),
        owed: static fn(APushName $name): TheWordCarriedOut => new TheWordCarriedOut(sprintf('owed %s', $name->shown())),
    )->said;
}

it('reads back waking on, with everything it was registered with', function (): void {
    $written = TheWakingAsKept::written(HowThisPhoneIsWoken::on(ADistributor::of('io.heckel.ntfy', 'ntfy'), aWakeKept()));

    expect(wokenAsRead(TheWakingAsKept::read(Shape::One, $written)))->toBe(sprintf('on io.heckel.ntfy ntfy 2fb15b007051cfa9 65 %s', str_repeat('s', 16)));
});

it('reads back a removal owed, and off as nothing kept', function (): void {
    expect(wokenAsRead(TheWakingAsKept::read(Shape::One, TheWakingAsKept::written(HowThisPhoneIsWoken::owed(APushName::of('galaxy-a51-7f3a'))))))->toBe('owed galaxy-a51-7f3a')
        ->and(TheWakingAsKept::written(HowThisPhoneIsWoken::off())->inTheClear())->toBe('[]')
        ->and(wokenAsRead(TheWakingAsKept::read(Shape::One, TheWakingAsKept::written(HowThisPhoneIsWoken::off()))))->toBe('off');
});

it('reads waking it cannot make out whole as a removal owed where it has the name, and as off where it does not', function (string $written, string $read): void {
    expect(wokenAsRead(TheWakingAsKept::read(Shape::One, Unsealed::of($written))))->toBe($read);
})->with([
    'not json' => ['{', 'off'],
    'a name a stack would refuse' => ['{"push_name":"Galaxy A51"}', 'off'],
    'a name that is not text' => ['{"push_name":7}', 'off'],
    'a name with nothing else' => ['{"push_name":"galaxy-a51-7f3a"}', 'owed galaxy-a51-7f3a'],
    'what the distributor is called, missing' => ['{"push_name":"galaxy-a51-7f3a","distributor_package":"io.heckel.ntfy","push_fingerprint":"2fb15b007051cfa9","webpush_key":"BAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQE=","webpush_secret":"c3Nzc3Nzc3Nzc3Nzc3Nzcw=="}', sprintf('on io.heckel.ntfy io.heckel.ntfy 2fb15b007051cfa9 65 %s', str_repeat('s', 16))],
    'what the distributor is called, not text' => ['{"push_name":"galaxy-a51-7f3a","distributor_package":"io.heckel.ntfy","distributor_called":7,"push_fingerprint":"2fb15b007051cfa9","webpush_key":"BAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQE=","webpush_secret":"c3Nzc3Nzc3Nzc3Nzc3Nzcw=="}', sprintf('on io.heckel.ntfy io.heckel.ntfy 2fb15b007051cfa9 65 %s', str_repeat('s', 16))],
    'the whole address where the fingerprint goes' => ['{"push_name":"galaxy-a51-7f3a","distributor_package":"io.heckel.ntfy","distributor_called":"ntfy","push_endpoint":"https://ntfy.sh/up","webpush_key":"BAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQE=","webpush_secret":"c3Nzc3Nzc3Nzc3Nzc3Nzcw=="}', 'owed galaxy-a51-7f3a'],
    'a fingerprint that is not one' => ['{"push_name":"galaxy-a51-7f3a","distributor_package":"io.heckel.ntfy","distributor_called":"ntfy","push_fingerprint":"https://ntfy.sh/up","webpush_key":"BAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQE=","webpush_secret":"c3Nzc3Nzc3Nzc3Nzc3Nzcw=="}', 'owed galaxy-a51-7f3a'],
    'keys that do not read' => ['{"push_name":"galaxy-a51-7f3a","distributor_package":"io.heckel.ntfy","distributor_called":"ntfy","push_fingerprint":"2fb15b007051cfa9","webpush_key":"!!","webpush_secret":"c3M="}', 'owed galaxy-a51-7f3a'],
]);
