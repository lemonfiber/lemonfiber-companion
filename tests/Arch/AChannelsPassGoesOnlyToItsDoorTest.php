<?php

declare(strict_types=1);

use Tests\Support\Tree;

/**
 * Every native file a channel's addresses pass through, which carry the pass the door lets it through on.
 *
 * @var list<string>
 */
const WHERE_A_PASS_TRAVELS = [
    'bridge/resources/android/WhatToPlay.kt',
    'bridge/resources/android/PlayerFunctions.kt',
    'bridge/resources/android/PlayerSession.kt',
    'bridge/resources/android/PlaybackService.kt',
    'bridge/resources/android/PlayerActivity.kt',
    'bridge/resources/android/DoorDataSource.kt',
    'bridge/resources/ios/WhatToPlay.swift',
    'bridge/resources/ios/PlayerFunctions.swift',
    'bridge/resources/ios/PlayerScreen.swift',
    'bridge/resources/ios/DoorRelay.swift',
];

/** The words a log line would write an address, a pass or a grant with. */
const WHAT_A_LOG_LINE_MUST_NOT_NAME = ['asked', 'request', 'location', 'fallback', 'direct', 'transcoded', 'grant', 'parameters', 'address', 'url'];

/** The calls that write a log line on either platform. */
const WHAT_WRITES_A_LOG_LINE = ['Log.v(', 'Log.d(', 'Log.i(', 'Log.w(', 'Log.e(', 'NSLog(', 'print(', 'os_log(', 'Logger('];

/** The calls that would keep a value past the moment it plays, or hand it to another component. */
const WHAT_KEEPS_OR_HANDS_ON = ['putExtra', 'putString', 'SharedPreferences', 'UserDefaults', 'NSCache', 'URLCache.shared', 'savedInstanceState.put'];

/**
 * Each of those files, by its path, as it reads.
 *
 * @return array<string, string>
 */
function whereAPassTravels(): array
{
    $read = [];

    foreach (WHERE_A_PASS_TRAVELS as $path) {
        $read[$path] = (string) file_get_contents(Tree::at($path));
    }

    return $read;
}

it('writes no log line that names an address, a pass or a grant', function (): void {
    $found = [];
    $read = 0;

    foreach (whereAPassTravels() as $path => $source) {
        foreach (explode("\n", $source) as $at => $line) {
            $logs = array_filter(WHAT_WRITES_A_LOG_LINE, static fn(string $call): bool => str_contains($line, $call));
            $names = array_filter(WHAT_A_LOG_LINE_MUST_NOT_NAME, static fn(string $word): bool => str_contains(strtolower($line), $word));
            $read += $logs === [] ? 0 : 1;

            if ($logs !== [] && $names !== []) {
                $found[] = sprintf('%s:%d', $path, $at + 1);
            }
        }
    }

    expect($read)->toBeGreaterThan(0, 'no log line was found, so this rule proved nothing')
        ->and($found)->toBe([]);
});

it('keeps no address in an intent, a store or a cache', function (): void {
    $found = [];

    foreach (whereAPassTravels() as $path => $source) {
        foreach (WHAT_KEEPS_OR_HANDS_ON as $call) {
            if (str_contains($source, $call)) {
                $found[] = sprintf('%s uses %s', $path, $call);
            }
        }
    }

    $android = whereAPassTravels()['bridge/resources/android/DoorDataSource.kt'];
    $ios = whereAPassTravels()['bridge/resources/ios/DoorRelay.swift'];

    expect($found)->toBe([])
        ->and($android)->toContain('one.useCaches = false')
        ->and($ios)->toContain('configuration.urlCache = nil')
        ->and($ios)->toContain('URLSessionConfiguration.ephemeral');
});

it('fetches every byte at the door it was handed, over the pin, and a grant only where one came', function (): void {
    $android = whereAPassTravels()['bridge/resources/android/DoorDataSource.kt'];
    $ios = whereAPassTravels()['bridge/resources/ios/DoorRelay.swift'];

    $service = whereAPassTravels()['bridge/resources/android/PlaybackService.kt'];

    expect($service)->toContain('PinnedConnection(DoorTrust(request.door, request.pin))')
        ->and($ios)->toContain('self.trust = DoorTrust(door: asked.door, pin: asked.pin)')
        ->and($ios)->toContain('guard trust.admits(leaf: leaf, host: space.host, port: space.port) else {')
        ->and($android)->toContain('asked.door.admitted(address)')
        ->and($android)->toContain('one.sslSocketFactory = pinned.sockets')
        ->and($android)->toContain("if (asked.carriesAGrant) {\n            one.setRequestProperty(WhatToPlay.GRANT_HEADER, asked.grantHeaderValue)")
        ->and($ios)->toContain("if asked.carriesAGrant {\n            forwarded.setValue(asked.grantHeaderValue, forHTTPHeaderField: WhatToPlay.grantHeader)")
        ->and($ios)->toContain("if asked.carriesAGrant {\n            followed.setValue(asked.grantHeaderValue, forHTTPHeaderField: WhatToPlay.grantHeader)");
});

it('reads a channel apart from a title, so a title still refuses a grant in its address', function (): void {
    $android = whereAPassTravels()['bridge/resources/android/WhatToPlay.kt'];
    $ios = whereAPassTravels()['bridge/resources/ios/WhatToPlay.swift'];

    expect($android)->toContain('location.contains(grant) -> WhyNot.GRANT_IN_THE_ADDRESS')
        ->and($android)->toContain('public fun readChannel(parameters: Map<String, Any>): Read')
        ->and($ios)->toContain('guard !location.contains(grant) else {')
        ->and($ios)->toContain('public static func readChannel(_ parameters: [String: Any]) -> Read');
});
