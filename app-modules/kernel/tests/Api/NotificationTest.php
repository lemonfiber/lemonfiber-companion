<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AnAlert;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Notification;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\WhatTheCoreDecided;
use Modules\Kernel\Api\WhichWayItWent;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

use function sprintf;

it('is built from the core\'s decision and nothing else', function (): void {
    // The requirement has two halves and this is the one a type can keep: each
    // way to make a notification takes what the core declared — its decision,
    // or one alert it raised — and no text. An app wanting to say something of its own
    // has nowhere to put the sentence.
    //
    // The other half — not calling the platform's alert directly — is a
    // `disallowedStaticCalls` entry on `Dialog::alert()`, because a call this
    // type is not involved in is not a call this type can refuse.
    $built = [];

    foreach (new ReflectionClass(Notification::class)->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        if ($method->isStatic()) {
            $built[] = $method->getName();
        }
    }

    expect($built)->toBe(['fromTheCore', 'ofAnAlert']);
});

it('has nowhere to hold a credential, a name or a title', function (): void {
    // All three are values the requirement forbids, and the defence is that
    // there is no field for any of them. Pinned over the constructor rather
    // than argued in a comment: a parameter taking a `string` is the
    // change that would quietly undo this, and it fails here the moment it is
    // written.
    $carries = [];

    foreach (new ReflectionClass(Notification::class)->getConstructor()?->getParameters() ?? [] as $parameter) {
        $type = $parameter->getType();

        $carries[$parameter->getName()] = $type instanceof ReflectionNamedType ? $type->getName() : null;
    }

    expect($carries)->toBe([
        'about' => StackId::class,
        'says' => WhatTheCoreDecided::class,
        'guarded' => 'bool',
        'way' => WhichWayItWent::class,
        'filedUnder' => WhatTheCoreDecided::class,
    ]);
});

it('knows whether its stack is still configured', function (): void {
    $here = StackId::rememberedAs('the-loft');
    $gone = StackId::rememberedAs('the-shed');

    $notification = Notification::fromTheCore($gone, WhatTheCoreDecided::toSay('backup.finished'));

    expect($notification->concernsOneOf($here))->toBeFalse()
        ->and($notification->concernsOneOf($here, $gone))->toBeTrue();
});

it('concerns nothing when the device has no stacks at all', function (): void {
    // The boundary the loop gets wrong: an empty list is what a device has
    // immediately after the last stack is removed, which is exactly when a
    // notification about it is most likely to still be in flight.
    $notification = Notification::fromTheCore(StackId::rememberedAs('the-loft'), WhatTheCoreDecided::toSay('backup.finished'));

    expect($notification->concernsOneOf())->toBeFalse();
});

it('says what it is about when the device is unlocked', function (): void {
    $said = Notification::fromTheCore(StackId::rememberedAs('the-loft'), WhatTheCoreDecided::toSay('backup.finished'))->either(
        plain: fn(WhatTheCoreDecided $says, StackId $about): Code => Code::of(sprintf('%s on %s', $says->shown(), $about->stored())),
        guarded: fn(WhatTheCoreDecided $says): WhatTheCoreDecided => $says,
    );

    expect($said->shown())->toBe('backup.finished on the-loft');
});

it('hands a locked device no stack to name', function (): void {
    // The point of the whole design, and the reason `whileLocked()` returns a
    // different object rather than setting a flag somebody has to read: the
    // guarded arm is not passed the stack, so a lock-screen renderer cannot
    // name it, quote a reading from it or identify the service — not because it
    // was told not to, but because none of it arrived.
    //
    // Asserted over the signature as well as the behaviour. A second parameter
    // added to the guarded arm tomorrow would compile, pass every other test
    // here, and hand a lock screen the stack it must not name.
    $arms = [];

    foreach (new ReflectionMethod(Notification::class, 'either')->getParameters() as $parameter) {
        $arms[] = $parameter->getName();
    }

    expect($arms)->toBe(['plain', 'guarded']);

    $handed = null;

    Notification::fromTheCore(StackId::rememberedAs('the-loft'), WhatTheCoreDecided::toSay('backup.finished'))
        ->whileLocked()
        ->either(
            plain: fn(WhatTheCoreDecided $says, StackId $about): Code => Code::of(sprintf('%s%s', $says->shown(), $about->stored())),
            guarded: function (WhatTheCoreDecided $says) use (&$handed): WhatTheCoreDecided {
                $handed = $says->shown();

                return $says;
            },
        );

    expect($handed)->toBe('backup.finished');
});

it('the guarded arm is the only one a locked device can run', function (): void {
    $ran = Notification::fromTheCore(StackId::rememberedAs('the-loft'), WhatTheCoreDecided::toSay('backup.finished'))
        ->whileLocked()
        ->either(
            plain: fn(WhatTheCoreDecided $says, StackId $about): Code => Code::of(sprintf('plain %s %s', $says->shown(), $about->stored())),
            guarded: fn(WhatTheCoreDecided $says): Code => Code::of(sprintf('guarded %s', $says->shown())),
        );

    expect($ran->shown())->toBe('guarded backup.finished');
});

it('locking one notification does not unlock the original', function (): void {
    // `whileLocked()` returns a new object, and the mistake it exists to
    // prevent is a renderer that locks a notification and then, still holding
    // the same reference, renders the plain form somewhere else.
    $notification = Notification::fromTheCore(StackId::rememberedAs('the-loft'), WhatTheCoreDecided::toSay('backup.finished'));

    $notification->whileLocked();

    $ran = $notification->either(
        plain: fn(WhatTheCoreDecided $says, StackId $about): Code => Code::of(sprintf('plain %s %s', $says->shown(), $about->stored())),
        guarded: fn(WhatTheCoreDecided $says): Code => Code::of(sprintf('guarded %s', $says->shown())),
    );

    expect($ran->shown())->toBe('plain backup.finished the-loft');
});

it('files a notification of the core\'s decision under that decision, as an onset', function (): void {
    $notification = Notification::fromTheCore(StackId::rememberedAs('the-loft'), WhatTheCoreDecided::toSay('backup.finished'));

    expect($notification->filedUnder()->shown())->toBe('backup.finished')
        ->and($notification->way())->toBe(WhichWayItWent::Onset);
});

it('files a notification of an alert under the alert\'s name, which way it went, locked or not', function (WhichWayItWent $way): void {
    $notification = Notification::ofAnAlert(StackId::rememberedAs('the-loft'), AnAlert::raised(WhatTheCoreDecided::toSay('vpn.down'), $way, 'vpn.tunnel-2'));

    foreach ([$notification, $notification->whileLocked()] as $told) {
        expect($told->filedUnder()->shown())->toBe('vpn.tunnel-2')
            ->and($told->way())->toBe($way);
    }

    expect($notification->either(
        plain: static fn(WhatTheCoreDecided $says): WhatTheCoreDecided => $says,
        guarded: static fn(): WhatTheCoreDecided => WhatTheCoreDecided::toSay('locked'),
    )->shown())->toBe('vpn.down');
})->with(WhichWayItWent::cases());
