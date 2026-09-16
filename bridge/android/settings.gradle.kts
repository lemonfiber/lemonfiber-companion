// The Android half's test harness.
//
// Named apart from the plugin itself because it builds none of it: what ships
// is a directory of Kotlin sources the NativePHP builder copies into the host
// app, with no Gradle project of its own. This exists solely so the decision in
// `CaptureRule.kt` can be run.
rootProject.name = "lemonfiber-native-android"
