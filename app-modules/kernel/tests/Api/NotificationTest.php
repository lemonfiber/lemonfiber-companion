<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Notification;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\WhatTheCoreDecided;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

use function sprintf;

it('N4-R11 — is built from the core\'s decision and nothing else', function (): void {
    // The requirement has two halves and this is the one a type can keep: there
    // is exactly one way to make a notification, it takes a code the server
    // declared, and it takes no text. An app wanting to say something of its own
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

    expect($built)->toBe(['fromTheCore']);
});

it('N4-R10 — has nowhere to hold a credential, a name or a title', function (): void {
    // All three are values the requirement forbids, and the defence is that
    // there is no field for any of them. Pinned over the constructor rather
    // than argued in a comment: a fourth parameter taking a `string` is the
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
    ]);
});

it('N4-R15 — knows whether its stack is still configured', function (): void {
    $here = StackId::rememberedAs('the-loft');
    $gone = StackId::rememberedAs('the-shed');

    $notification = Notification::fromTheCore($gone, WhatTheCoreDecided::toSay('backup.finished'));

    expect($notification->concernsOneOf($here))->toBeFalse()
        ->and($notification->concernsOneOf($here, $gone))->toBeTrue();
});

it('N4-R15 — concerns nothing when the device has no stacks at all', function (): void {
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

it('N4-R20 — hands a locked device no stack to name', function (): void {
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

it('N4-R20 — the guarded arm is the only one a locked device can run', function (): void {
    $ran = Notification::fromTheCore(StackId::rememberedAs('the-loft'), WhatTheCoreDecided::toSay('backup.finished'))
        ->whileLocked()
        ->either(
            plain: fn(WhatTheCoreDecided $says, StackId $about): Code => Code::of(sprintf('plain %s %s', $says->shown(), $about->stored())),
            guarded: fn(WhatTheCoreDecided $says): Code => Code::of(sprintf('guarded %s', $says->shown())),
        );

    expect($ran->shown())->toBe('guarded backup.finished');
});

it('N4-R20 — locking one notification does not unlock the original', function (): void {
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
