#!/usr/bin/env bash
#
# Typechecks every Swift file the bridge ships, the way the app build compiles
# them: against the iOS SDK and beside the NativePHP sources they call into.
#
# `native.yml` builds and tests only the files that need no iOS runtime, on the
# host. Nothing else compiles the rest — every `BridgeFunction`, everything
# touching UIKit — before the app build does.
#
# The NativePHP half is read from the installed `vendor/nativephp/mobile`, so
# the bridge is checked against the package the app is built with. One stand-in
# is written here: `NativeElementBridge`, NativePHP's renderer, which the two
# files below name and the bridge never does. The real one draws in the rest of
# the renderer and, through it, the PHP runtime's headers, which the package
# does not ship.
#
# The deployment target and language mode are read from NativePHP's Xcode
# project, which is the target the bridge is compiled into.
#
# The bridge's Kotlin is not checked here. `kotlinc` needs every supertype on
# its classpath, and the Android sources extend AndroidX and Play Services
# classes whose transitive AARs only a dependency resolver assembles.
set -euo pipefail

cd "$(dirname "$0")/.."

xcode=vendor/nativephp/mobile/resources/xcode

# What the bridge names from NativePHP: `BridgeFunction`, `BridgeError` and
# `BridgeResponse` in the first, `LaravelBridge` in the second.
nativephp=(
    "${xcode}/NativePHP/Bridge/BridgeRouter.swift"
    "${xcode}/NativePHP/Bridge/NativePHP.swift"
)

for source in "${nativephp[@]}"; do
    if [ ! -f "${source}" ]; then
        echo "::error::${source} is not installed. Run composer install; if it is installed, NativePHP moved it and this list must follow."
        exit 1
    fi
done

# One value per setting across the project's targets, or the target this
# compiles for is a guess.
setting() {
    local values
    values=$(sed -n "s/^[[:space:]]*$1 = \(.*\);\$/\1/p" "${xcode}/NativePHP.xcodeproj/project.pbxproj" | sort -u)
    if [ "$(printf '%s\n' "${values}" | grep -c .)" -ne 1 ]; then
        echo "::error::NativePHP's Xcode project sets $1 to '${values//$'\n'/, }', not to one value." >&2
        exit 1
    fi
    printf '%s\n' "${values}"
}

deployment=$(setting IPHONEOS_DEPLOYMENT_TARGET)
language=$(setting SWIFT_VERSION)

stand_in=$(mktemp -d)
trap 'rm -rf "${stand_in}"' EXIT

cat > "${stand_in}/NativeElementBridge.swift" <<'SWIFT'
final class NativeElementBridge {
    static func registerRegion(_ region: UnsafeMutableRawPointer) {}
    static func unregisterRegion() {}
    static func postTreeUpdateFromRegion() {}
    static func sendNativeEvent(eventName: String, payloadJson: String) {}
}
SWIFT

echo "Typechecking bridge/resources/ios for iOS ${deployment}, Swift ${language}."

xcrun --sdk iphonesimulator swiftc \
    -target "arm64-apple-ios${deployment}-simulator" \
    -swift-version "${language%.0}" \
    -typecheck \
    bridge/resources/ios/*.swift \
    "${nativephp[@]}" \
    "${stand_in}/NativeElementBridge.swift"

echo "Every Swift file the bridge ships compiles."
