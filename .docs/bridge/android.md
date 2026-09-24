# Android

## Where things are

```
bridge/resources/android/          what actually ships, and what the harness compiles
  <Capability>Rule.kt              the decision, no framework in it
  <Capability>Functions.kt         the shim: gather, ask the rule, apply
bridge/android/                    a plain Kotlin/JVM project, deliberately not an Android one
  build.gradle.kts                 ktlint, detekt, kover
  src/test/kotlin/                 one test file per rule
```

The harness points its source directory at `resources/android` rather than at a
copy. A harness built on a copy proves something about the copy, and the copy is
not what goes on the phone.

## What the harness can and cannot see

`build.gradle.kts` excludes every file that touches a window, an activity or a
lifecycle. Those need the framework on the classpath and a device to mean
anything, and they are excluded rather than stubbed — a stub of a window is a
test of the stub.

So the rules are tested here and the shims are not tested anywhere except by
running the app. That is the deal, and it is why the rules carry all the
deciding: the untested half should be as close to *ask the platform, hand the
answer over* as it can be made.

## The gates

`gradle ktlintCheck detekt test koverVerify` — formatting, analysis, tests and a
100% coverage floor over the rules. The floor is not ambition: the rules are
small files, and a line of one that nothing runs is a line nobody has thought
about.

## What Android makes hard

**Notification permission has three states and Android reports two.**
`areNotificationsEnabled()` answers false for *never asked* and for *refused
forever* alike, and `shouldShowRequestPermissionRationale` is true only in the
narrow window between a first refusal and a permanent one. Telling the three
apart needs a record of whether this application has ever raised the prompt, and
nothing in the platform keeps it — so the bridge does, written where the prompt
is raised rather than inferred afterwards.

**A runtime permission that does not exist below API 33.** Notifications off on
an older device is a refusal already given rather than a question still open,
and prompting for a permission the platform has never heard of does nothing at
all. The rule takes that as an input rather than branching on a version number
inside itself.

**The camera can be refused twice.** Once by declining the dialog, and again by
the operator having turned it off in settings since. Both arrive as the same
denial, and the difference matters to what the screen should say next.

**A plugin's init function is a top-level function taking a `Context`.** The
generated `PluginBridgeFunctionRegistration.kt` imports the symbol by its full
path and calls it with the context it was handed, so a member of an object is
not reachable from there however `@JvmStatic` it is — `import a.b.C.install`
does not compile. iOS takes a class with a static method instead, so the same
job needs a different shape on each side and `nativephp.json` is where that
difference is written down.

What makes it worth a page rather than a comment is the failure when the
manifest names nothing at all. The builder emits an import and a call for what
it is told about and emits nothing for what it is not, so a half nobody switches
on produces no error: the plugin compiles, every bridge function registers and
answers, every screen renders, and the one call that had to happen before any of
them never did. `bridge/tests/EveryDeclaredFunctionHasAHandlerTest.php` is what notices.
