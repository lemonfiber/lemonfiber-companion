<?php

declare(strict_types=1);

use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\UI\Elements\OutlinedTextInput;

it('tells each platform what a field holds, for its password manager', function (string $renderer, string ...$says): void {
    foreach ($says as $line) {
        expect((string) file_get_contents(base_path($renderer)))->toContain($line);
    }
})->with([
    'iOS' => [
        'vendor/nativephp/mobile-ui/resources/ios/NativeUITextInputCore.swift',
        '.textContentType(resolveContentType(p.getString("content_type")))',
        'case "username":     return .username',
        'case "password":     return .password',
        'case "new-password": return .newPassword',
    ],
    'Android' => [
        'vendor/nativephp/mobile-ui/resources/android/TextInputShared.kt',
        'contentType  = p.getString("content_type"),',
        '"username" -> ContentType.Username',
        '"password" -> ContentType.Password',
        '"new-password" -> ContentType.NewPassword',
        'return semantics { contentType = type }',
    ],
    'Android, outlined' => ['vendor/nativephp/mobile-ui/resources/android/OutlinedTextInputRenderer.kt', '.nuiContentType(props.contentType)'],
    'Android, filled' => ['vendor/nativephp/mobile-ui/resources/android/FilledTextInputRenderer.kt', '.nuiContentType(props.contentType)'],
    'Android, bare' => ['vendor/nativephp/mobile-ui/resources/android/BareTextInputRenderer.kt', '.nuiContentType(props.contentType)'],
]);

it('carries what a field holds to the platform, written either way, and nothing where it was not said', function (array $attributes, ?string $carried): void {
    $field = new OutlinedTextInput();
    $field->applyAttributes($attributes);

    $props = $field->toArray(new CallbackRegistry())['props'] ?? [];

    expect(is_array($props) ? ($props['content_type'] ?? null) : $props)->toBe($carried);
})->with([
    'a new password' => [['content-type' => 'new-password'], 'new-password'],
    'a name to sign in with' => [['contentType' => 'username'], 'username'],
    'nothing said' => [[], null],
]);
