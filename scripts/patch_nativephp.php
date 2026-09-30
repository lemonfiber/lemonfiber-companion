<?php

declare(strict_types=1);

/*
 * Every step of a build must be asked the same question about development
 * dependencies.
 *
 * NativePHP assembles a bundle in three steps. Both platform lanes decide
 * whether to install development dependencies from the build type — debug keeps
 * them, release does not — and the dump that follows is not asked at all: it
 * passes no flag, so it always keeps `autoload-dev`.
 *
 * That disagreement is a fatal on a release build. The install has dropped the
 * development packages and the dump writes an authoritative classmap that
 * expects them, so package discovery registers a provider the classmap was
 * built without:
 *
 *     Class "…\Collision\Adapters\Laravel\CollisionServiceProvider" not found
 *
 * `PHPBridge` then reports an empty response, so the first frame never renders
 * and the app returns to the launcher without saying anything. Every suite is
 * green when that happens, because no suite builds a bundle.
 *
 * So the dump is asked the same question the install was, from the same
 * variable, in the same method. A debug build keeps its development
 * dependencies through all three steps and a release build drops them through
 * all three — which is what lets a stand-in for a stack reach a handset while
 * being absent from anything shipped.
 *
 * **What the bundle removes is the other half of this, and it is not patched
 * here.** The cleanup drops `tests` at any depth, so an autoloader entry
 * pointing into it is a file that is gone by the time anything reads it. Only
 * `autoload-dev.files` is `require`d unconditionally at boot, so that is the
 * entry this application must not have — see {@see \Tests\Support\Rules},
 * which is a class for exactly that reason. A classmap entry naming a dropped
 * file is inert until something autoloads it, and on a device nothing does.
 *
 * Run from `post-install-cmd` and `post-update-cmd`, so it survives the next
 * `composer install` rather than being a thing somebody remembers. It refuses
 * to be a no-op: an edit that matches nothing is the failure this repository
 * keeps finding in its own rules, and a silent one here would mean the device
 * fatal came back with the patch still in the tree looking applied.
 */

/**
 * Every line this rewrites, and what it becomes.
 *
 * A list rather than a map keyed by file, so that two edits to one file stay
 * expressible. Each entry names its own file for the same reason the refusal
 * does: a patch that cannot say *which* line moved sends the reader to search
 * a package for it.
 */
const WHAT_THIS_REWRITES = [
    [
        // A bundle carrying development dependencies is a bigger bundle, and
        // the copy that assembles it passes no timeout of its own — so it takes
        // Laravel's default sixty seconds, which a debug build exceeds. Sixty
        // seconds is a budget rather than a correctness property, and what it
        // produces when it runs out is an rsync killed halfway: a partial tree,
        // a build that fails somewhere later, and nothing saying the copy is
        // what ended.
        'in' => '/../vendor/nativephp/mobile/src/Support/BundleFileManager.php',
        'ships' => <<<'SHIPS'
        $result = Process::run("rsync -a --copy-links {$excludeFlags} \"{$source}/\" \"{$destination}/\"");
SHIPS,
        'becomes' => <<<'BECOMES'
        $result = Process::timeout(600)->run("rsync -a --copy-links {$excludeFlags} \"{$source}/\" \"{$destination}/\"");
BECOMES,
    ],
    [
        'in' => '/../vendor/nativephp/mobile/src/Concerns/PreparesBuild.php',
        // The closing marker sits at column zero so that nothing is stripped:
        // PHP removes the marker's own indentation from every line of a
        // heredoc, and these lines have to arrive with the twelve, sixteen and
        // twenty spaces the package wrote them with or the match is a match
        // against text that exists nowhere.
        'ships' => <<<'SHIPS'
            $this->components->task('Optimizing autoloader', function () use ($tempDir) {
                $result = Process::path($tempDir)
                    ->timeout(60)
                    ->run('composer dump-autoload --optimize --classmap-authoritative');
SHIPS,
        'becomes' => <<<'BECOMES'
            $this->components->task('Optimizing autoloader', function () use ($tempDir, $excludeDevDependencies) {
                $result = Process::path($tempDir)
                    ->timeout(60)
                    ->run('composer dump-autoload --optimize --classmap-authoritative'.($excludeDevDependencies ? ' --no-dev' : ''));
BECOMES,
    ],
    [
        // An agent's git worktree inside the project is a second checkout of
        // this repository — a `vendor/` of its own, its own `app-modules`, its
        // own lockfile — and the bundler copies it. Measured on 2026-09-16: a
        // debug bundle came to 243 MB, of which 225 MB was one worktree under
        // `.claude/`, carried onto a handset and unpacked there.
        //
        // `.git` is already excluded at any depth and this is the same fact
        // wearing a different name: a directory a tool keeps its own state in,
        // which no build has a use for. It sits beside `.git` rather than in
        // `PROJECT` because a worktree can be nested anywhere, and a
        // project-root rule would miss one a directory deeper.
        //
        // The alternative was asking every agent to put its worktree somewhere
        // else, which is a convention — and a convention is what this
        // repository calls the thing that holds until somebody new arrives.
        'in' => '/../vendor/nativephp/mobile/src/Support/BundleExclusions.php',
        'ships' => <<<'SHIPS'
    public const ANY_DEPTH = [
        '.git',
SHIPS,
        'becomes' => <<<'BECOMES'
    public const ANY_DEPTH = [
        '.git',
        '.claude',
BECOMES,
    ],
    [
        // `bridge/` is a path repository, so composer symlinks it into
        // `vendor/lemonfiber/bridge` — and the bundler copies with
        // `rsync -a --copy-links`, which follows the link and takes everything
        // under it. That includes `.build`, where SwiftPM leaves its module
        // caches and object files: 231 MB measured on 2026-09-19, growing every
        // time `swift test` runs, and carried onto a handset.
        //
        // Size is the smaller half. A module cache holds absolute paths from
        // the machine that built it and objects compiled from this checkout,
        // and none of it is anything a device has a use for — so what ships is
        // a copy of somebody's working tree inside the app.
        //
        // It already stops builds rather than merely bloating them: a debug
        // build died with `rsync: .../.build/debug/ModuleCache/...: No space
        // left on device` and succeeded unchanged once the directory was gone.
        //
        // Sits beside `.git` and `.claude` for their reason: a directory a tool
        // keeps its own state in, which no build has a use for, and which can
        // appear at any depth rather than only at the project root.
        'in' => '/../vendor/nativephp/mobile/src/Support/BundleExclusions.php',
        'ships' => <<<'SHIPS'
        '.claude',
SHIPS,
        'becomes' => <<<'BECOMES'
        '.claude',
        '.build',
BECOMES,
    ],
    [
        // A text field sends every keystroke to PHP and takes back whatever value
        // PHP answers with, unless it equals the one value it sent last. Typing
        // faster than PHP answers makes the answer to an earlier keystroke arrive
        // after a later one was sent: "a" comes back while "ab" is out, differs
        // from it, and is taken as PHP setting the field — so the "b" is gone.
        // Measured on 2026-09-28 on a Galaxy A51: a pairing code typed at about
        // eight characters a second lost one character in every thirty, and the
        // code was refused as unreadable.
        //
        // So each field remembers everything it sent and PHP has not answered, and
        // a value matching any of it is an answer rather than a change. Only a
        // value this field never sent replaces what the person typed.
        'in' => '/../vendor/nativephp/mobile-ui/resources/android/TextInputShared.kt',
        'ships' => <<<'SHIPS'
/**
 * Outbound dispatch state machine.
SHIPS,
        'becomes' => <<<'BECOMES'
/**
 * What this field sent over the bridge that PHP has not answered yet, oldest
 * first. PHP answers in the order it was asked, so a server value matching one
 * of these is the answer to it — and to everything sent before it.
 */
internal class InFlight {
    private val waiting = ArrayDeque<String>()

    fun sent(value: String) {
        waiting.addLast(value)
        while (waiting.size > 64) waiting.removeFirst()
    }

    /** Whether [value] answers something this field sent; forgets it and everything older. */
    fun answers(value: String): Boolean {
        val at = waiting.indexOf(value)
        if (at < 0) return false
        repeat(at + 1) { waiting.removeFirst() }
        return true
    }

    fun forget() = waiting.clear()
}

/**
 * Outbound dispatch state machine.
BECOMES,
    ],
    [
        'in' => '/../vendor/nativephp/mobile-ui/resources/android/OutlinedTextInputRenderer.kt',
        'ships' => <<<'SHIPS'
        var lastSentValue by remember { mutableStateOf(props.serverValue) }
SHIPS,
        'becomes' => <<<'BECOMES'
        var lastSentValue by remember { mutableStateOf(props.serverValue) }
        val inFlight = remember { InFlight() }
BECOMES,
    ],
    [
        'in' => '/../vendor/nativephp/mobile-ui/resources/android/OutlinedTextInputRenderer.kt',
        'ships' => <<<'SHIPS'
            if (props.serverValue != lastSentValue) {
SHIPS,
        'becomes' => <<<'BECOMES'
            if (!inFlight.answers(props.serverValue) && props.serverValue != lastSentValue) {
BECOMES,
    ],
    [
        'in' => '/../vendor/nativephp/mobile-ui/resources/android/OutlinedTextInputRenderer.kt',
        'ships' => <<<'SHIPS'
                lastSentValue = props.serverValue
SHIPS,
        'becomes' => <<<'BECOMES'
                lastSentValue = props.serverValue
                inFlight.forget()
BECOMES,
    ],
    [
        'in' => '/../vendor/nativephp/mobile-ui/resources/android/OutlinedTextInputRenderer.kt',
        'ships' => <<<'SHIPS'
                setLastSent = { lastSentValue = it },
SHIPS,
        'becomes' => <<<'BECOMES'
                setLastSent = { lastSentValue = it; inFlight.sent(it) },
BECOMES,
    ],
    [
        'in' => '/../vendor/nativephp/mobile-ui/resources/android/FilledTextInputRenderer.kt',
        'ships' => <<<'SHIPS'
        var lastSentValue by remember { mutableStateOf(props.serverValue) }
SHIPS,
        'becomes' => <<<'BECOMES'
        var lastSentValue by remember { mutableStateOf(props.serverValue) }
        val inFlight = remember { InFlight() }
BECOMES,
    ],
    [
        'in' => '/../vendor/nativephp/mobile-ui/resources/android/FilledTextInputRenderer.kt',
        'ships' => <<<'SHIPS'
            if (props.serverValue != lastSentValue) {
SHIPS,
        'becomes' => <<<'BECOMES'
            if (!inFlight.answers(props.serverValue) && props.serverValue != lastSentValue) {
BECOMES,
    ],
    [
        'in' => '/../vendor/nativephp/mobile-ui/resources/android/FilledTextInputRenderer.kt',
        'ships' => <<<'SHIPS'
                lastSentValue = props.serverValue
SHIPS,
        'becomes' => <<<'BECOMES'
                lastSentValue = props.serverValue
                inFlight.forget()
BECOMES,
    ],
    [
        'in' => '/../vendor/nativephp/mobile-ui/resources/android/FilledTextInputRenderer.kt',
        'ships' => <<<'SHIPS'
                setLastSent = { lastSentValue = it },
SHIPS,
        'becomes' => <<<'BECOMES'
                setLastSent = { lastSentValue = it; inFlight.sent(it) },
BECOMES,
    ],
    [
        'in' => '/../vendor/nativephp/mobile-ui/resources/android/BareTextInputRenderer.kt',
        'ships' => <<<'SHIPS'
        var lastSentValue by remember { mutableStateOf(props.serverValue) }
SHIPS,
        'becomes' => <<<'BECOMES'
        var lastSentValue by remember { mutableStateOf(props.serverValue) }
        val inFlight = remember { InFlight() }
BECOMES,
    ],
    [
        'in' => '/../vendor/nativephp/mobile-ui/resources/android/BareTextInputRenderer.kt',
        'ships' => <<<'SHIPS'
            if (props.serverValue != lastSentValue) {
SHIPS,
        'becomes' => <<<'BECOMES'
            if (!inFlight.answers(props.serverValue) && props.serverValue != lastSentValue) {
BECOMES,
    ],
    [
        'in' => '/../vendor/nativephp/mobile-ui/resources/android/BareTextInputRenderer.kt',
        'ships' => <<<'SHIPS'
                lastSentValue = props.serverValue
SHIPS,
        'becomes' => <<<'BECOMES'
                lastSentValue = props.serverValue
                inFlight.forget()
BECOMES,
    ],
    [
        'in' => '/../vendor/nativephp/mobile-ui/resources/android/BareTextInputRenderer.kt',
        'ships' => <<<'SHIPS'
                    lastSentValue = newValue.text
SHIPS,
        'becomes' => <<<'BECOMES'
                    lastSentValue = newValue.text
                    inFlight.sent(newValue.text)
BECOMES,
    ],
    [
        // The theme store's `secondary` is the tonal button's fill, and
        // Material draws a selected tab's label in its own `secondary`. One key
        // cannot be both a quiet fill and a label that has to read on the bar,
        // so Material's is the surface's text colour, and the store's stays the
        // button's.
        'in' => '/../vendor/nativephp/mobile-ui/resources/android/NativeUITheme.kt',
        'ships' => <<<'SHIPS'
        secondary        = secondary,
        onSecondary      = onSecondary,
        tertiary         = accent,
SHIPS,
        'becomes' => <<<'BECOMES'
        secondary        = onSurface,
        onSecondary      = surface,
        tertiary         = accent,
BECOMES,
    ],
    [
        // Material's container colours back the bottom bar's selected-item
        // indicator and the tonal button. The package maps its surface family
        // onto the theme store and leaves these at Material's baseline, a
        // lavender no theme chose. Each is folded onto the store's token; the
        // secondary container, behind a selected tab and a tonal button, onto
        // the outline with the surface's text on it.
        'in' => '/../vendor/nativephp/mobile-ui/resources/android/NativeUITheme.kt',
        'ships' => <<<'SHIPS'
        inversePrimary          = primary,
    )
SHIPS,
        'becomes' => <<<'BECOMES'
        inversePrimary          = primary,
        primaryContainer        = primary,
        onPrimaryContainer      = onPrimary,
        secondaryContainer      = outline,
        onSecondaryContainer    = onSurface,
        tertiaryContainer       = accent,
        onTertiaryContainer     = onAccent,
        errorContainer          = destructive,
        onErrorContainer        = onDestructive,
        outlineVariant          = outlineVariant,
    )
BECOMES,
    ],
    [
        // Text with no colour of its own is painted black, in dark mode too,
        // where the ground is the theme's dark background. It takes the theme's
        // text colour instead, which follows the mode.
        'in' => '/../vendor/nativephp/mobile-ui/resources/android/TextRenderer.kt',
        'ships' => <<<'SHIPS'
import androidx.compose.ui.graphics.Color

SHIPS,
        'becomes' => <<<'BECOMES'
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.toArgb

BECOMES,
    ],
    [
        'in' => '/../vendor/nativephp/mobile-ui/resources/android/TextRenderer.kt',
        'ships' => <<<'SHIPS'
        val textArgb = if (darkColor != 0) darkColor else p.getColor("color", 0xFF000000.toInt())
SHIPS,
        'becomes' => <<<'BECOMES'
        val textArgb = if (darkColor != 0) darkColor else p.getColor("color", androidx.compose.material3.MaterialTheme.colorScheme.onBackground.toArgb())
BECOMES,
    ],
    [
        // The same default for text drawn as inline runs.
        'in' => '/../vendor/nativephp/mobile-ui/resources/android/TextRenderer.kt',
        'ships' => <<<'SHIPS'
        val ctx = LocalContext.current
        val annotated = buildAnnotatedString {
            appendTextRuns(node, RunCtx.Root, isDark, ctx)
        }
SHIPS,
        'becomes' => <<<'BECOMES'
        val ctx = LocalContext.current
        val themeTextArgb = androidx.compose.material3.MaterialTheme.colorScheme.onBackground.toArgb()
        val annotated = buildAnnotatedString {
            appendTextRuns(node, RunCtx.Root.copy(colorArgb = themeTextArgb), isDark, ctx)
        }
BECOMES,
    ],
    [
        // An icon with no colour of its own takes the theme's text colour on
        // its surface, for the reason text does.
        'in' => '/../vendor/nativephp/mobile-ui/resources/android/IconRenderer.kt',
        'ships' => <<<'SHIPS'
import androidx.compose.ui.graphics.Color

SHIPS,
        'becomes' => <<<'BECOMES'
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.toArgb

BECOMES,
    ],
    [
        'in' => '/../vendor/nativephp/mobile-ui/resources/android/IconRenderer.kt',
        'ships' => <<<'SHIPS'
        val lightArgb = p.getColor("color", 0xFF000000.toInt())
SHIPS,
        'becomes' => <<<'BECOMES'
        val lightArgb = p.getColor("color", androidx.compose.material3.MaterialTheme.colorScheme.onSurface.toArgb())
BECOMES,
    ],
    [
        // A divider with no colour of its own is a fixed light grey in both
        // modes. It takes the theme's hairline colour, which follows the mode.
        'in' => '/../vendor/nativephp/mobile-ui/resources/android/SimpleRenderers.kt',
        'ships' => <<<'SHIPS'
        val color = if (borderArgb != 0) argbToComposeColor(borderArgb) else Color(0xFFE0E0E0)
        HorizontalDivider(modifier = modifier, color = color)
SHIPS,
        'becomes' => <<<'BECOMES'
        val color = if (borderArgb != 0) argbToComposeColor(borderArgb) else androidx.compose.material3.MaterialTheme.colorScheme.outlineVariant
        HorizontalDivider(modifier = modifier, color = color)
BECOMES,
    ],
    [
        'in' => '/../vendor/nativephp/mobile-ui/resources/android/SimpleRenderers.kt',
        'ships' => <<<'SHIPS'
        val color = if (borderArgb != 0) argbToComposeColor(borderArgb) else Color(0xFFE0E0E0)
        val strokeWidth = node.style?.borderWidth ?: 1f
SHIPS,
        'becomes' => <<<'BECOMES'
        val color = if (borderArgb != 0) argbToComposeColor(borderArgb) else androidx.compose.material3.MaterialTheme.colorScheme.outlineVariant
        val strokeWidth = node.style?.borderWidth ?: 1f
BECOMES,
    ],
    [
        // The drawer names its ☰ and says whether the screen already has a
        // back button in the leading slot. Both are the screen's to say: the
        // label is text a person reads, so it comes from the translator, and
        // only the screen knows whether it was pushed.
        'in' => '/../vendor/nativephp/mobile-ui/src/Builders/Drawer.php',
        'ships' => <<<'SHIPS'
    public function getContent(): View|Element
SHIPS,
        'becomes' => <<<'BECOMES'
    private string $label = 'Open menu';

    private bool $besideBack = false;

    /** What a screen reader announces for the ☰ affordance. */
    public function label(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    /** The screen has a back button in the leading slot, so the ☰ sits beside it. */
    public function besideBack(bool $besideBack = true): self
    {
        $this->besideBack = $besideBack;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function isBesideBack(): bool
    {
        return $this->besideBack;
    }

    public function getContent(): View|Element
BECOMES,
    ],
    [
        'in' => '/../vendor/nativephp/mobile-ui/src/NativeUIServiceProvider.php',
        'ships' => <<<'SHIPS'
                'mode' => $builder->getMode(),
                'width' => $builder->getWidth(),
            ]);
SHIPS,
        'becomes' => <<<'BECOMES'
                'mode' => $builder->getMode(),
                'width' => $builder->getWidth(),
                'a11y-label' => $builder->getLabel(),
                'beside_back' => $builder->isBesideBack(),
            ]);
BECOMES,
    ],
    [
        'in' => '/../vendor/nativephp/mobile-ui/src/Elements/NativeDrawer.php',
        'ships' => <<<'SHIPS'
            $this->props['width'] = (int) $attrs['width'];
        }
SHIPS,
        'becomes' => <<<'BECOMES'
            $this->props['width'] = (int) $attrs['width'];
        }
        if (isset($attrs['beside_back'])) {
            $this->props['beside_back'] = (bool) $attrs['beside_back'];
        }
BECOMES,
    ],
    [
        // The host is told which screen it is drawn around, so a navigation
        // that keeps the drawer — one screen with a menu to the next — closes it.
        'in' => '/../vendor/nativephp/mobile-ui/resources/ios/NativeUIDrawerHost.swift',
        'ships' => <<<'SHIPS'
        return AnyView(NativeDrawerHost(drawerNode: drawerNode) { content })
SHIPS,
        'becomes' => <<<'BECOMES'
        let uri = root.props.getString("current_uri", default: "")
        return AnyView(NativeDrawerHost(drawerNode: drawerNode, uri: uri) { content })
BECOMES,
    ],
    [
        // The panel's colour is read from the theme store for the mode the
        // phone is in. The environment's theme is not set this far out, so it
        // answered the package's light fallback in dark mode too. The ☰ sits
        // past the system's back button: a 44-point glass circle from iOS 26,
        // a chevron and a word before it.
        'in' => '/../vendor/nativephp/mobile-ui/resources/ios/NativeUIDrawerHost.swift',
        'ships' => <<<'SHIPS'
    let drawerNode: NativeUINode?
    @ViewBuilder var content: Content

    @ObservedObject private var state = DrawerHostState.shared
    @Environment(\.accessibilityReduceMotion) private var reduceMotion
    @Environment(\.nativeUITheme) private var theme
SHIPS,
        'becomes' => <<<'BECOMES'
    let drawerNode: NativeUINode?
    var uri: String = ""
    @ViewBuilder var content: Content

    @ObservedObject private var state = DrawerHostState.shared
    @ObservedObject private var themes = NativeUITheme.shared
    @Environment(\.accessibilityReduceMotion) private var reduceMotion
    @Environment(\.colorScheme) private var colorScheme

    private var besideTheBack: CGFloat {
        if #available(iOS 26, *) { return 64 }
        return 100
    }
BECOMES,
    ],
    [
        // A level with a back button keeps the left-edge swipe for going back.
        'in' => '/../vendor/nativephp/mobile-ui/resources/ios/NativeUIDrawerHost.swift',
        'ships' => <<<'SHIPS'
                // Left-edge detector for swipe-to-open (both modes), when closed.
                if !state.isOpen {
SHIPS,
        'becomes' => <<<'BECOMES'
                // Left-edge detector for swipe-to-open (both modes), when closed.
                if !state.isOpen && !drawerNode.props.getBool("beside_back") {
BECOMES,
    ],
    [
        'in' => '/../vendor/nativephp/mobile-ui/resources/ios/NativeUIDrawerHost.swift',
        'ships' => <<<'SHIPS'
                    .accessibilityLabel("Open menu")
                    .padding(.leading, 12)
SHIPS,
        'becomes' => <<<'BECOMES'
                    .accessibilityLabel(drawerNode.props.getString("a11y_label", default: "Open menu"))
                    .padding(.leading, drawerNode.props.getBool("beside_back") ? besideTheBack : 12)
BECOMES,
    ],
    [
        'in' => '/../vendor/nativephp/mobile-ui/resources/ios/NativeUIDrawerHost.swift',
        'ships' => <<<'SHIPS'
        .onChange(of: drawerNode.id) { _ in
            if dragOffset != 0 { dragOffset = 0 }
        }
SHIPS,
        'becomes' => <<<'BECOMES'
        .onChange(of: drawerNode.id) { _ in
            if dragOffset != 0 { dragOffset = 0 }
        }
        .onChange(of: uri) { _ in
            if state.isOpen { animateClosed() }
        }
BECOMES,
    ],
    [
        // A tap inside the panel is a choice made, so the panel closes. A drag
        // is a scroll and leaves it open.
        'in' => '/../vendor/nativephp/mobile-ui/resources/ios/NativeUIDrawerHost.swift',
        'ships' => <<<'SHIPS'
        .background(theme.background)
    }
SHIPS,
        'becomes' => <<<'BECOMES'
        .background(themes.resolve(for: colorScheme).background)
        .simultaneousGesture(TapGesture().onEnded { animateClosed() })
    }
BECOMES,
    ],
    [
        'in' => '/../vendor/nativephp/mobile-ui/resources/android/NativeUIChromeInit.kt',
        'ships' => <<<'SHIPS'
        NativeLayoutDrawerHost(drawerNode = drawerNode, content = content)
SHIPS,
        'becomes' => <<<'BECOMES'
        NativeLayoutDrawerHost(drawerNode = drawerNode, uri = root.props.getString("current_uri", ""), content = content)
BECOMES,
    ],
    [
        'in' => '/../vendor/nativephp/mobile-ui/resources/android/NativeLayoutDrawerHost.kt',
        'ships' => <<<'SHIPS'
import kotlinx.coroutines.launch
SHIPS,
        'becomes' => <<<'BECOMES'
import kotlinx.coroutines.launch
import androidx.compose.foundation.gestures.awaitEachGesture
import androidx.compose.foundation.gestures.awaitFirstDown
import androidx.compose.ui.input.pointer.PointerEventPass
import androidx.compose.ui.input.pointer.pointerInput
BECOMES,
    ],
    [
        'in' => '/../vendor/nativephp/mobile-ui/resources/android/NativeLayoutDrawerHost.kt',
        'ships' => <<<'SHIPS'
    drawerNode: NativeUINode?,
    content: @Composable () -> Unit,
SHIPS,
        'becomes' => <<<'BECOMES'
    drawerNode: NativeUINode?,
    uri: String = "",
    content: @Composable () -> Unit,
BECOMES,
    ],
    [
        // Closed when the screen under it changes, and by a tap inside the
        // panel — a tap is a choice made, and a drag past the touch slop is a
        // scroll that leaves it open.
        'in' => '/../vendor/nativephp/mobile-ui/resources/android/NativeLayoutDrawerHost.kt',
        'ships' => <<<'SHIPS'
    val scope = rememberCoroutineScope()

    val sheetModifier = if (widthDp > 0) Modifier.width(widthDp.dp) else Modifier
SHIPS,
        'becomes' => <<<'BECOMES'
    val scope = rememberCoroutineScope()

    androidx.compose.runtime.LaunchedEffect(uri) {
        if (drawerState.isOpen) drawerState.close()
    }

    val sheetModifier = (if (widthDp > 0) Modifier.width(widthDp.dp) else Modifier)
        .pointerInput(drawerState) {
            awaitEachGesture {
                val down = awaitFirstDown(requireUnconsumed = false, pass = PointerEventPass.Initial)
                var dragged = false
                while (true) {
                    val change = awaitPointerEvent(PointerEventPass.Initial).changes.firstOrNull { it.id == down.id } ?: break
                    if ((change.position - down.position).getDistance() > viewConfiguration.touchSlop) dragged = true
                    if (!change.pressed) {
                        if (!dragged) scope.launch { drawerState.close() }
                        break
                    }
                }
            }
        }
BECOMES,
    ],
    [
        'in' => '/../vendor/nativephp/mobile-ui/resources/android/NativeLayoutDrawerHost.kt',
        'ships' => <<<'SHIPS'
                    Icon(Icons.Filled.Menu, contentDescription = "Open menu")
SHIPS,
        'becomes' => <<<'BECOMES'
                    Icon(Icons.Filled.Menu, contentDescription = drawerNode.props.getString("a11y_label", "Open menu"))
BECOMES,
    ],
    [
        // Back closes an open drawer before it leaves the screen. Composed
        // after the drawer, so it is registered after the screen's own back
        // handlers and is asked first.
        'in' => '/../vendor/nativephp/mobile-ui/resources/android/NativeLayoutDrawerHost.kt',
        'ships' => <<<'SHIPS'
            content = wrappedContent
        )
    }
}
SHIPS,
        'becomes' => <<<'BECOMES'
            content = wrappedContent
        )
    }

    androidx.activity.compose.BackHandler(enabled = drawerState.isOpen) {
        scope.launch { drawerState.close() }
    }
}
BECOMES,
    ],
    [
        // A tab carries its own name. The tab bar is laid out to the bottom
        // edge and padded above the system navigation bar, and on a handset
        // with three-button navigation the accessibility layer clips the
        // window above where that padding ends. Each tab's label then falls
        // outside the clip and is reported with no bounds at all, so the tab a
        // screen reader focuses has no name of its own and nothing on it can
        // be found by its label. The name goes on the tab, whose bounds start
        // at the top of the bar, and the label beside the icon is left to the
        // eye rather than said a second time.
        'in' => '/../vendor/nativephp/mobile/resources/androidstudio/app/src/main/java/com/nativephp/mobile/ui/nativerender/NativeRootTabsRenderer.kt',
        'ships' => <<<'SHIPS'
import androidx.compose.ui.platform.LocalLayoutDirection

SHIPS,
        'becomes' => <<<'BECOMES'
import androidx.compose.ui.platform.LocalLayoutDirection
import androidx.compose.ui.semantics.clearAndSetSemantics
import androidx.compose.ui.semantics.contentDescription
import androidx.compose.ui.semantics.semantics

BECOMES,
    ],
    [
        'in' => '/../vendor/nativephp/mobile/resources/androidstudio/app/src/main/java/com/nativephp/mobile/ui/nativerender/NativeRootTabsRenderer.kt',
        'ships' => <<<'SHIPS'
                        NavigationBarItem(
                            selected = actualIdx == selection,
SHIPS,
        'becomes' => <<<'BECOMES'
                        NavigationBarItem(
                            modifier = Modifier.semantics { contentDescription = label },
                            selected = actualIdx == selection,
BECOMES,
    ],
    [
        'in' => '/../vendor/nativephp/mobile/resources/androidstudio/app/src/main/java/com/nativephp/mobile/ui/nativerender/NativeRootTabsRenderer.kt',
        'ships' => <<<'SHIPS'
                            label = { Text(label, fontFamily = chromeFontFamily) },
SHIPS,
        'becomes' => <<<'BECOMES'
                            label = { Text(label, fontFamily = chromeFontFamily, modifier = Modifier.clearAndSetSemantics {}) },
BECOMES,
    ],
    [
        // What a bridge call carries stays out of the device log. The bridge
        // writes every call's parameters and result to logcat at INFO, in
        // debug and release builds alike, and this app's storage calls carry
        // the session token, the stack's address and its pinned fingerprint.
        // Anything that can read the log reads them. The function's name is
        // still logged; what it was handed and what it handed back are not.
        'in' => '/../vendor/nativephp/mobile/resources/androidstudio/app/src/main/cpp/bridge_jni.cpp',
        'ships' => <<<'SHIPS'
    if (parametersJSON) {
        LOGI("📦 BridgeJNI: Parameters JSON: %s", parametersJSON);
    } else {
        LOGI("📦 BridgeJNI: Parameters JSON: NULL");
    }
SHIPS,
        'becomes' => <<<'BECOMES'
    // What a call carries is not logged: this app's calls carry its session.
BECOMES,
    ],
    [
        'in' => '/../vendor/nativephp/mobile/resources/androidstudio/app/src/main/cpp/bridge_jni.cpp',
        'ships' => <<<'SHIPS'
    LOGI("📤 BridgeJNI: Result JSON: %s", resultStr);
SHIPS,
        'becomes' => <<<'BECOMES'
    // What a call returned is not logged, for the reason its parameters are not.
BECOMES,
    ],
    [
        // The same on iOS, where the router prints every call's parameters.
        'in' => '/../vendor/nativephp/mobile/resources/xcode/NativePHP/Bridge/BridgeRouter.swift',
        'ships' => <<<'SHIPS'
    print("🚀 NativePHPCall('\(functionName)') with parameters: \(parameters)")
SHIPS,
        'becomes' => <<<'BECOMES'
    print("🚀 NativePHPCall('\(functionName)')")
BECOMES,
    ],
];

/**
 * What to do when a line this patch rewrites is not where it was.
 *
 * One literal rather than several joined, because a join between two literals
 * is three mutants — drop either, swap them — and nothing asserts this sentence
 * word for word.
 */
const WHEN_THE_LINE_HAS_MOVED = "patch_nativephp: the line this patch rewrites is not in %s.\n\nEither the package fixed it, in which case delete that entry — and if it was the last one, this script and the two `composer.json` hooks that call it — or it moved, in which case a build is fatalling on a device again and nothing said so. Do not ignore this.\n";

/**
 * What to do when the file a patch rewrites is not there at all.
 *
 * A separate sentence from the one above, because the two send a reader to
 * different places: that one says to open the file and look for a line, and
 * this one is about a file there is nothing to open. Told apart here rather
 * than at the reader, who would otherwise go looking for a line in a path that
 * does not exist.
 *
 * One literal for the reason the one above is one literal.
 */
const WHEN_THE_FILE_IS_NOT_THERE = "patch_nativephp: %s is not there.\n\nThe package no longer ships the file this patch rewrites. Either it was renamed, in which case point that entry at the new path, or it is gone, in which case delete the entry — and if it was the last one, this script and the two `composer.json` hooks that call it. Skipping it quietly leaves a build fatalling on a device with nothing having said so. Do not ignore this.\n";

/**
 * What to do when the native project this tree builds from does not carry a line the package does.
 *
 * Its own sentence, because the remedy is not the other two's. The project
 * is a copy `native:install` took of the package, so a line missing from it
 * means the copy no longer matches the package this tree installed, and the
 * way out is to copy it again rather than to edit this script.
 *
 * One literal for the reason the ones above are one literal.
 */
const WHEN_THE_INSTALLED_COPY_DIFFERS = "patch_nativephp: %s does not carry what the package ships.\n\nThat file belongs to the native project `native:install` copied out of the package, and the copy no longer matches the package this tree installed. Run `php artisan native:install --force` to copy it again from the patched package. Building from it as it is leaves the device on code this patch never reached. Do not ignore this.\n";

/**
 * The package directory the bundle leaves out, so its copy of this tree has none.
 *
 * A build copies this tree without it, as `BundleExclusions::VENDOR_PATHS`
 * says, and runs `composer install` in the copy, which runs this script again.
 * Nothing in the bundle is built from that directory, so in the bundle's copy
 * there is nothing of it to patch. Where the directory is here and a file under
 * it is not, the file moved, and that is still refused.
 */
const WHAT_THE_BUNDLE_LEAVES_OUT = '/../vendor/nativephp/mobile/resources';

/**
 * Where `native:install` copies a package template, which is what a build compiles.
 *
 * `native:install` copies the Android and Xcode projects out of the package
 * into `nativephp/android` and `nativephp/ios` once, and every build after that
 * compiles the copy without reading the package again. A rewrite of the
 * template reaches a project installed after it and never one installed before
 * it, so an entry under the template is made to the installed copy as well. A
 * tree with no installed project has nothing there to patch: a fresh
 * checkout, whose install copies the template this run patched, and the
 * bundle's copy, which leaves `nativephp` out as `BundleExclusions::PROJECT`
 * says.
 */
const WHERE_AN_INSTALL_COPIES_A_TEMPLATE = [
    '/../vendor/nativephp/mobile/resources/androidstudio' => '/../nativephp/android',
    '/../vendor/nativephp/mobile/resources/xcode' => '/../nativephp/ios',
];

/**
 * Each file an entry is made to, with what to say where the line is not in it.
 *
 * The package's own file, unless this is the bundle's copy, and the installed
 * project's copy of it where there is one.
 *
 * @return list<array{path: string, file_is_not_there: string, line_has_moved: string}>
 */
function whereItIsMade(string $where): array
{
    $made = [];

    $leftOutOfThisCopy = str_starts_with($where, sprintf('%s/', WHAT_THE_BUNDLE_LEAVES_OUT))
        && ! is_dir(sprintf('%s%s', __DIR__, WHAT_THE_BUNDLE_LEAVES_OUT));

    if (! $leftOutOfThisCopy) {
        $made[] = [
            'path' => sprintf('%s%s', __DIR__, $where),
            'file_is_not_there' => WHEN_THE_FILE_IS_NOT_THERE,
            'line_has_moved' => WHEN_THE_LINE_HAS_MOVED,
        ];
    }

    foreach (WHERE_AN_INSTALL_COPIES_A_TEMPLATE as $template => $installed) {
        $isUnderIt = str_starts_with($where, sprintf('%s/', $template));

        if ($isUnderIt && is_dir(sprintf('%s%s', __DIR__, $installed))) {
            $made[] = [
                'path' => sprintf('%s%s%s', __DIR__, $installed, mb_substr($where, mb_strlen($template))),
                'file_is_not_there' => WHEN_THE_INSTALLED_COPY_DIFFERS,
                'line_has_moved' => WHEN_THE_INSTALLED_COPY_DIFFERS,
            ];
        }
    }

    return $made;
}

$rewritten = 0;

foreach (WHAT_THIS_REWRITES as ['in' => $where, 'ships' => $ships, 'becomes' => $becomes]) {
    foreach (whereItIsMade($where) as ['path' => $path, 'file_is_not_there' => $fileIsNotThere, 'line_has_moved' => $lineHasMoved]) {
        if (! file_exists($path)) {
            fwrite(STDERR, sprintf($fileIsNotThere, $path));

            exit(1);
        }

        $source = file_get_contents($path);

        if (! is_string($source)) {
            fwrite(STDERR, sprintf("patch_nativephp: could not read %s.\n", $path));

            exit(1);
        }

        if (str_contains($source, $becomes)) {
            continue;
        }

        if (! str_contains($source, $ships)) {
            fwrite(STDERR, sprintf($lineHasMoved, $path));

            exit(1);
        }

        file_put_contents($path, str_replace($ships, $becomes, $source));

        $rewritten++;
    }
}

if ($rewritten > 0) {
    fwrite(STDOUT, sprintf("patch_nativephp: %d line(s) rewritten in NativePHP.\n", $rewritten));
}
