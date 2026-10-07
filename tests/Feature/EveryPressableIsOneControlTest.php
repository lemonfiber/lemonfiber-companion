<?php

declare(strict_types=1);

// A pressable the markup gave a label is one control to a screen reader: it
// says its label once, it is a button, or a tab that is selected or not, and a
// tap through the accessibility layer presses it. The renderers are the
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
        'fun Modifier.nodeSaidAsAControl(node: NativeUINode): Modifier {',
        'val label = node.props.getString("a11y_label", "")',
        'val press = node.props.getCallbackId("on_press").let { if (it != 0) it else node.onPress }',
        'return clearAndSetSemantics {',
        'contentDescription = said',
        'role = Role.Button',
        'NativeElementBridge.sendPressEvent(press, nodeId)',
        'disabled()',
    ],
    'a tab' => [
        'vendor/nativephp/mobile/resources/androidstudio/app/src/main/java/com/nativephp/mobile/ui/nativerender/NativeRootTabsRenderer.kt',
        <<<'KOTLIN'
                        NavigationBarItem(
                            modifier = Modifier.clearAndSetSemantics {
                                contentDescription = tab.props.getString("badge_label", "").ifEmpty { label }
                                role = Role.Tab
                                selected = actualIdx == selection
                                onClick { tabTapped(); true }
                            },
                            selected = actualIdx == selection,
                            onClick = tabTapped,
KOTLIN,
        'import androidx.compose.ui.semantics.selected',
    ],
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
