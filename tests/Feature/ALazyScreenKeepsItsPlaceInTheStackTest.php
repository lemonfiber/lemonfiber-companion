<?php

declare(strict_types=1);

it('takes a stack to mount cold only where the last frame with a sentinel was not a stack\'s', function (): void {
    $source = (string) file_get_contents(base_path('vendor/nativephp/mobile/resources/xcode/NativePHP/NativeRender/NativeElementBridge.swift'));

    expect($source)->toContain('newRootType == "native_root_stack" && lastChromeRootType != "native_root_stack"')
        ->and($source)->toContain('lastChromeRootType = newRootType')
        ->and($source)->not->toContain('newRootType == "native_root_stack" && prevRootType != "native_root_stack"');
});
