<?php

declare(strict_types=1);

it('waits for a boot of the runtime under way rather than failing a second one', function (): void {
    $source = (string) file_get_contents(base_path('vendor/nativephp/mobile/resources/xcode/Include/Bridge/PHP.c'));

    expect($source)->toContain("    if (!under_way && !persistent_initialized && !worker_thread_alive) {\n        g_persistent_boot_state = PERSISTENT_BOOT_IN_PROGRESS;\n    }")
        ->and($source)->toContain("    if (under_way) {\n        fprintf(stderr, \"persistent_php_boot: a boot is under way, waiting for it\\n\");\n        fflush(stderr);\n        return wait_for_persistent_boot(60) == 0 ? 0 : -3;\n    }");
});

it('puts the app in place for one caller at a time', function (): void {
    $source = (string) file_get_contents(base_path('vendor/nativephp/mobile/resources/xcode/NativePHP/AppUpdateManager.swift'));

    expect($source)->toContain('    private let ensuring = NSLock()')
        ->and($source)->toContain("        print(\"📦 AppUpdateManager.ensureAppExists() starting\")\n\n        ensuring.lock()\n        defer { ensuring.unlock() }");
});

it('boots the runtime from a wake that finds none, before it hears', function (): void {
    $wake = (string) file_get_contents(base_path('bridge/resources/ios/WakeFunctions.swift'));

    expect($wake)->toContain('if ColdWake.on(runtimeIsUp: PersistentPHPRuntime.shared.isBooted) == .bootsTheRuntimeFirst {')
        ->and($wake)->toMatch('/guard PersistentPHPRuntime\\.shared\\.boot\\(\\) else \\{.*ephemeralBoot\\(\\$0\\)/s');
});
