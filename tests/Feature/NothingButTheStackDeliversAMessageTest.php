<?php

declare(strict_types=1);

use Modules\Device\Api\PlatformNotifier;
use Modules\Kernel\Api\Notifier;
use Tests\Support\Manifests;
use Tests\Support\OurCode;
use Tests\Support\Tree;

// What delivers a message is the back-end the operator configured on the stack,
// and nothing this app brings with it.
//
// A companion holding a push identity of its own would be a hosted notification
// plane in all but name, so the absence is held at each place one would enter:
// a package that reaches a push service, the platform's push facade, the switch
// NativePHP's build reads to keep the push entitlement, the Firebase files its
// build copies in, and this app's own native manifest. And because the app
// subscribes to no back-end and raises nothing yet, nothing on it promises
// delivery while it is not running.

/**
 * Every PHP file the application ships, configuration included.
 *
 * @return list<string>
 */
function whatTheApplicationShips(): array
{
    return [...OurCode::sourceFiles(), ...Tree::filesUnder(Tree::at('config'), '.php')];
}

/**
 * Every file of the application that names one of these, with the one it names.
 *
 * @param list<string> $names
 * @param list<string> $leaving files that may name them
 * @return list<string>
 */
function whereTheApplicationNames(array $names, array $leaving = []): array
{
    $found = [];

    foreach (whatTheApplicationShips() as $file) {
        if (in_array($file, $leaving, strict: true)) {
            continue;
        }

        $said = (string) file_get_contents($file);

        foreach ($names as $name) {
            if (preg_match(sprintf('/\b%s\b/', preg_quote($name, '/')), $said) === 1) {
                $found[] = sprintf('%s names %s', str_replace(sprintf('%s/', Tree::root()), '', $file), $name);
            }
        }
    }

    return $found;
}

it('requires no package that reaches a push service', function (): void {
    expect(Manifests::reachingAPushService())->toBe([]);
});

it('names the platform\'s push facade and its enrolment nowhere in what it ships', function (): void {
    expect(whatTheApplicationShips())->not->toBe([])
        ->and(whereTheApplicationNames(['PushNotifications', 'PendingPushNotificationEnrollment']))->toBe([]);
});

it('leaves the switch NativePHP\'s build keeps a push entitlement by turned off, and holds no Firebase file for it to copy', function (): void {
    expect(config('nativephp.permissions.push_notifications'))->toBeFalsy()
        ->and(file_exists(Tree::at('google-services.json')))->toBeFalse()
        ->and(file_exists(Tree::at('GoogleService-Info.plist')))->toBeFalse();
});

it('declares no push dependency, background mode or registration in its own native manifest and code', function (): void {
    $manifest = (string) file_get_contents(Tree::at('bridge/nativephp.json'));
    $native = [...Tree::filesUnder(Tree::at('bridge/resources'), '.kt'), ...Tree::filesUnder(Tree::at('bridge/resources'), '.swift')];
    $registering = [];

    foreach ($native as $file) {
        $said = (string) file_get_contents($file);

        foreach (['FirebaseMessaging', 'registerForRemoteNotifications', 'didRegisterForRemoteNotificationsWithDeviceToken'] as $call) {
            if (str_contains($said, $call)) {
                $registering[] = sprintf('%s calls %s', basename($file), $call);
            }
        }
    }

    expect($native)->not->toBe([])
        ->and($manifest)->not->toContain('firebase')
        ->and($manifest)->not->toContain('remote-notification')
        ->and($manifest)->not->toContain('aps-environment')
        ->and($registering)->toBe([]);
});

it('takes a notifier nowhere but where it is answered, so nothing subscribes, asks to notify or promises delivery while the app is not running', function (): void {
    $answered = [
        Tree::at('app-modules/kernel/src/Api/Notifier.php'),
        Tree::at('app-modules/device/src/Api/PlatformNotifier.php'),
        Tree::at('bootstrap/Composition/CompositionRoot.php'),
    ];

    expect(interface_exists(Notifier::class) && class_exists(PlatformNotifier::class))->toBeTrue()
        ->and(whereTheApplicationNames(['Notifier'], leaving: $answered))->toBe([]);
});
