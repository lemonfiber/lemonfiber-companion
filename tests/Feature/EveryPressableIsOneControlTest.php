<?php

declare(strict_types=1);

// A pressable the markup gave a label is one control to a screen reader, and so
// is every button the package draws in its bars: it says its name once, it is a
// button, or a tab that is selected or not, it says whether it holds the focus,
// and a tap through the accessibility layer presses it. Nothing else on the
// screen is a control: a tap on nothing in particular is heard, not offered. The renderers are the
// package's, rewritten by `scripts/patch_nativephp.php`; nothing in CI compiles
// them, so what is held here is that the rewrite is in the source a build
// compiles, on the node that takes the press.

it('says a labelled pressable on Android as one control, on the node that takes the press', function (string $renderer, string ...$says): void {
    $source = (string) file_get_contents(base_path($renderer));

    foreach ($says as $line) {
        expect($source)->toContain($line);
    }
})->with([
    // Every node is drawn through `NodeView`, so every kind of pressable, a
    // button, a list row, a tappable area, is said here, and outside the
    // click region rather than inside it.
    'the click region of every node' => [
        'vendor/nativephp/mobile/resources/androidstudio/app/src/main/java/com/nativephp/mobile/ui/nativerender/NodeView.kt',
        <<<'KOTLIN'
        modifier = modifier
            .nodeSaidAsAControl(node)
            .nodeGestures(node, interactionSource)
KOTLIN,
    ],
    'the control itself' => [
        'vendor/nativephp/mobile/resources/androidstudio/app/src/main/java/com/nativephp/mobile/ui/nativerender/NodeModifiers.kt',
        'return onFocusChanged { holdsTheFocus = it.hasFocus }.clearAndSetSemantics {',
        'contentDescription = name',
        'this.role = role',
        'focused = focusedNow',
        'onPress()',
        'disabled()',
    ],
    'a node the markup gave a label and a press' => [
        'vendor/nativephp/mobile/resources/androidstudio/app/src/main/java/com/nativephp/mobile/ui/nativerender/NodeModifiers.kt',
        'fun Modifier.nodeSaidAsAControl(node: NativeUINode): Modifier {',
        'val label = node.props.getString("a11y_label", "")',
        'val press = node.props.getCallbackId("on_press").let { if (it != 0) it else node.onPress }',
        'return saidAsAControl(said, Role.Button, pressable) {',
        'NativeElementBridge.sendPressEvent(press, nodeId)',
    ],
    'a tab' => [
        'vendor/nativephp/mobile/resources/androidstudio/app/src/main/java/com/nativephp/mobile/ui/nativerender/NativeRootTabsRenderer.kt',
        <<<'KOTLIN'
                        NavigationBarItem(
                            modifier = Modifier.saidAsAControl(
                                tab.props.getString("badge_label", "").ifEmpty { label },
                                Role.Tab,
                                state = { selected = actualIdx == selection },
                            ) { tabTapped() },
                            selected = actualIdx == selection,
                            onClick = tabTapped,
KOTLIN,
        'import androidx.compose.ui.semantics.selected',
    ],
    'the button that opens the menu' => [
        'vendor/nativephp/mobile-ui/resources/android/NativeLayoutDrawerHost.kt',
        '.saidAsAControl(drawerNode.props.getString("a11y_label", "Open menu"), Role.Button) { scope.launch { drawerState.open() } }',
        'Icon(Icons.Filled.Menu, contentDescription = null)',
    ],
    'the back arrow over a pushed screen' => [
        'vendor/nativephp/mobile/resources/androidstudio/app/src/main/java/com/nativephp/mobile/ui/nativerender/NativeRootStackRenderer.kt',
        'IconButton(onClick = goBack, modifier = Modifier.saidAsAControl("Back", Role.Button, onPress = goBack)) {',
    ],
    'an action in the top bar' => [
        'vendor/nativephp/mobile/resources/androidstudio/app/src/main/java/com/nativephp/mobile/ui/nativerender/NativeRootStackRenderer.kt',
        'IconButton(onClick = pressed, modifier = Modifier.saidAsAControl(action.props.getString("label", ""), Role.Button, onPress = pressed)) {',
        'IconButton(onClick = { expanded = true }, modifier = Modifier.saidAsAControl(action.props.getString("label", ""), Role.Button) { expanded = true }) {',
    ],
    'the back arrow over a tab' => [
        'vendor/nativephp/mobile/resources/androidstudio/app/src/main/java/com/nativephp/mobile/ui/nativerender/NativeRootTabsRenderer.kt',
        'modifier = Modifier.saidAsAControl("Back", Role.Button) { NativeElementBridge.sendSystemBackEvent() },',
    ],
    'the button that closes the drawer' => [
        'vendor/nativephp/mobile/resources/androidstudio/app/src/main/java/com/nativephp/mobile/ui/nativerender/NativeNavRenderers.kt',
        'IconButton(onClick = { onCloseDrawer {} }, modifier = Modifier.saidAsAControl("Close drawer", Role.Button) { onCloseDrawer {} }) {',
    ],
    // One choice of a row: a radio button, chosen or not, named on the node a
    // finger chooses, and nothing under it said, Material's checkbox included.
    'a chip' => [
        'vendor/nativephp/mobile-ui/resources/android/ChipRenderer.kt',
        <<<'KOTLIN'
        val chipModifier = modifier.saidAsAControl(
            listOf(a11yLabel.ifEmpty { label }, a11yHint).filter { it.isNotEmpty() }.joinToString(". "),
            Role.RadioButton,
            pressable = !disabled,
            state = { selected = isSelected },
            onPress = chosen,
        )

        FilterChip(
            selected = isSelected,
            onClick = chosen,
KOTLIN,
        'modifier = chipModifier,',
    ],
]);

it('says a chip on iOS as one button, chosen by the system\'s own trait and not by a word after it', function (): void {
    $chip = (string) file_get_contents(base_path('vendor/nativephp/mobile-ui/resources/ios/NativeUIChipRenderer.swift'));

    expect($chip)->toContain('.accessibilityAddTraits(isSelected ? [.isButton, .isSelected] : .isButton)')
        ->and(str_contains($chip, '.accessibilityValue(isSelected ? "Selected" : "Not selected")'))->toBeFalse();
});

it('draws every chip through the one renderer that says it as one choice', function (): void {
    expect((string) file_get_contents(base_path('app-modules/design/resources/views/components/chip.blade.php')))
        ->toContain('<native:chip ')
        ->toContain('a11y-label="{{ $named }}"')
        ->toContain(':selected="$chosen"');
});

it('puts the keyboard away on a tap on nothing without offering the whole screen as a control', function (): void {
    $root = (string) file_get_contents(base_path('vendor/nativephp/mobile/resources/androidstudio/app/src/main/java/com/nativephp/mobile/ui/nativerender/NativeUIRenderer.kt'));

    expect($root)->toContain('detectTapGestures { focusManager.clearFocus() }')
        ->and(preg_match('/\.clickable\(\s*indication = null,\s*interactionSource = remember \{ MutableInteractionSource\(\) \}\s*\) \{\s*\/\/ Tap outside any input dismisses keyboard/', $root))->toBe(0);
});

it('says no glyph inside a bar button a second time', function (string $renderer, string $glyph): void {
    expect((string) file_get_contents(base_path($renderer)))->not->toMatch(sprintf('/name = "%s",\s*contentDescription = "/', $glyph));
})->with([
    'the back arrow over a pushed screen' => ['vendor/nativephp/mobile/resources/androidstudio/app/src/main/java/com/nativephp/mobile/ui/nativerender/NativeRootStackRenderer.kt', 'arrow_back'],
    'the back arrow over a tab' => ['vendor/nativephp/mobile/resources/androidstudio/app/src/main/java/com/nativephp/mobile/ui/nativerender/NativeRootTabsRenderer.kt', 'arrow_back'],
    'the button that closes the drawer' => ['vendor/nativephp/mobile/resources/androidstudio/app/src/main/java/com/nativephp/mobile/ui/nativerender/NativeNavRenderers.kt', 'close'],
]);

it('says a labelled pressable on iOS as one element, a button where something takes its press', function (): void {
    expect((string) file_get_contents(base_path('vendor/nativephp/mobile-ui/resources/ios/NativeUISimpleRenderers.swift')))
        ->toContain('.accessibilityElement(children: .combine)')
        ->toContain('.accessibilityLabel(label)')
        ->toContain('.accessibilityAddTraits(node.onPress != 0 ? .isButton : [])');
});

it('presses a control through the accessibility layer only where its press is not taken by a menu', function (): void {
    expect((string) file_get_contents(base_path('vendor/nativephp/mobile/resources/androidstudio/app/src/main/java/com/nativephp/mobile/ui/nativerender/NodeModifiers.kt')))
        ->toContain('if (label.isEmpty() || press == 0 || node.props.getBool("has_menu")) {');
});
