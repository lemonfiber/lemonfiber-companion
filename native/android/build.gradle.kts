// A plain Kotlin/JVM project, deliberately not an Android one.
//
// `CaptureRule.kt` imports nothing from the framework, which is the whole reason
// it was written as a separate file: a rule with no `android.*` in it can be run
// by a JVM in two seconds on any runner, and an Android unit test needs a
// toolchain, an SDK licence and a device or Robolectric to say the same thing.
// The framework-facing file beside it — `LemonfiberFunctions.kt` — is excluded
// below rather than stubbed, because a stub of a window is a test of the stub.
//
// The source directory points at `resources/android`, which is what the plugin
// actually ships. A harness built on a copy proves something about the copy, and
// the copy is not what goes on the phone.
plugins {
    kotlin("jvm") version "2.1.0"
    // The Kotlin half of "same bar as PHP". Pint formats and fixes there;
    // ktlint does here, over both the shipped sources and these tests.
    id("org.jlleitschuh.gradle.ktlint") version "12.1.2"
    // The analyser. ktlint is pint; this is PHPStan — complexity, swallowed
    // exceptions, the things that are formatted correctly and still wrong.
    id("io.gitlab.arturbosch.detekt") version "1.23.7"
    // Coverage, held to the same floor as the PHP: 100%. The rule is one small
    // file, and a line of it nothing runs is a line nobody has thought about.
    id("org.jetbrains.kotlinx.kover") version "0.9.1"
}

repositories {
    mavenCentral()
}

dependencies {
    testImplementation(kotlin("test"))
}

kotlin {
    jvmToolchain(17)

    // Every public declaration states its visibility and its return type.
    // The Kotlin counterpart of `declare(strict_types=1)` and an explicit
    // return type on every method: inference is convenient right up to the
    // point where a published signature changes because a body did.
    explicitApi()

    compilerOptions {
        // A warning the compiler already found is not a lower grade of problem
        // than one a linter found. This is what stops the first one being
        // scrolled past.
        allWarningsAsErrors.set(true)
    }
}

detekt {
    buildUponDefaultConfig = true
    config.setFrom(files("detekt.yml"))
    // Read the shipped sources and the tests. Android APIs are not on this
    // classpath and detekt parses rather than compiles, so it reads them fine.
    source.setFrom(files("../resources/android", "src/test/kotlin"))
}

kover {
    reports {
        verify {
            rule {
                bound {
                    minValue.set(100)
                }
            }
        }
    }
}

sourceSets {
    main {
        kotlin.setSrcDirs(listOf("../resources/android"))
        // Everything that touches a window, an activity or a lifecycle. These
        // need the Android framework on the classpath and a device to mean
        // anything, so they are proven by the app's own build rather than here.
        kotlin.exclude("LemonfiberFunctions.kt")
    }
    test {
        kotlin.setSrcDirs(listOf("src/test/kotlin"))
    }
}

ktlint {
    // Fail on a finding rather than report one. A linter that reports is a
    // linter somebody stops reading.
    ignoreFailures.set(false)
    // The shipped sources use Android APIs this project does not have on its
    // classpath, and ktlint parses rather than compiles, so it reads them fine.
    filter {
        include("**/*.kt")
    }
}

tasks.test {
    useJUnitPlatform()
    testLogging {
        events("passed", "failed", "skipped")
    }
}
