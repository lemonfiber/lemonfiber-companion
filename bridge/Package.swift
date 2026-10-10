// swift-tools-version: 6.0
import PackageDescription

// A test harness for the iOS half of this plugin.
//
// The manifest sits at the package root rather than beside the tests because
// SwiftPM refuses a target path outside it, and the sources this has to compile
// are the ones the plugin ships — `resources/ios`, not a copy. A harness built
// on a copy proves something about the copy, and the copy is not what goes on
// the phone.
//
// Only the UIKit-free files are in the target. Everything touching a window, a
// scene or a notification centre needs an iOS runtime, and this package builds
// for the host so `swift test` works on a laptop and on a macOS runner with no
// simulator. That split is not a compromise: deciding *when* to protect the
// window is the part worth a test, and it is the part that fits here.
let package = Package(
    name: "LemonfiberNative",
    platforms: [.macOS(.v13)],
    targets: [
        .target(
            name: "LemonfiberNative",
            path: "resources/ios",
            exclude: [
                "LemonfiberFunctions.swift",
                "LemonfiberInit.swift",
                "LemonfiberAuth.swift",
                "TheLock.swift",
                "TellingFunctions.swift",
                "ScanningFunctions.swift",
                "ScanningViewController.swift",
                "StorageFunctions.swift",
                "LinkFunctions.swift",
                "LocalNetworkFunctions.swift",
                "ResolveFunctions.swift",
                "SettingsFunctions.swift",
                "HandoverFunctions.swift",
                "ClockFunctions.swift",
                "ReorderableRenderer.swift",
                "DoorRelay.swift",
                "PlayerScreen.swift",
                "PlayerFunctions.swift",
            ]
        ),
        .testTarget(
            name: "CaptureRuleTests",
            dependencies: ["LemonfiberNative"],
            path: "ios/Tests/CaptureRuleTests"
        ),
        .testTarget(
            name: "LockRuleTests",
            dependencies: ["LemonfiberNative"],
            path: "ios/Tests/LockRuleTests"
        ),
        .testTarget(
            name: "NotificationRuleTests",
            dependencies: ["LemonfiberNative"],
            path: "ios/Tests/NotificationRuleTests"
        ),
        .testTarget(
            name: "CameraRuleTests",
            dependencies: ["LemonfiberNative"],
            path: "ios/Tests/CameraRuleTests"
        ),
        .testTarget(
            name: "EnvelopeTests",
            dependencies: ["LemonfiberNative"],
            path: "ios/Tests/EnvelopeTests"
        ),
        .testTarget(
            name: "StorageRuleTests",
            dependencies: ["LemonfiberNative"],
            path: "ios/Tests/StorageRuleTests"
        ),
        .testTarget(
            name: "RecurrenceTests",
            dependencies: ["LemonfiberNative"],
            path: "ios/Tests/RecurrenceTests"
        ),
        .testTarget(
            name: "HandoverRuleTests",
            dependencies: ["LemonfiberNative"],
            path: "ios/Tests/HandoverRuleTests"
        ),
        .testTarget(
            name: "ShareCacheTests",
            dependencies: ["LemonfiberNative"],
            path: "ios/Tests/ShareCacheTests"
        ),
        .testTarget(
            name: "LinkRuleTests",
            dependencies: ["LemonfiberNative"],
            path: "ios/Tests/LinkRuleTests"
        ),
        .testTarget(
            name: "LocalNetworkRuleTests",
            dependencies: ["LemonfiberNative"],
            path: "ios/Tests/LocalNetworkRuleTests"
        ),
        .testTarget(
            name: "ResolveRuleTests",
            dependencies: ["LemonfiberNative"],
            path: "ios/Tests/ResolveRuleTests"
        ),
        .testTarget(
            name: "SettingsRuleTests",
            dependencies: ["LemonfiberNative"],
            path: "ios/Tests/SettingsRuleTests"
        ),
        .testTarget(
            name: "WhatTheOperatorSaidTests",
            dependencies: ["LemonfiberNative"],
            path: "ios/Tests/WhatTheOperatorSaidTests"
        ),
        .testTarget(
            name: "ReorderingTests",
            dependencies: ["LemonfiberNative"],
            path: "ios/Tests/ReorderingTests"
        ),
        .testTarget(
            name: "DoorPinTests",
            dependencies: ["LemonfiberNative"],
            path: "ios/Tests/DoorPinTests"
        ),
        .testTarget(
            name: "DoorTests",
            dependencies: ["LemonfiberNative"],
            path: "ios/Tests/DoorTests"
        ),
        .testTarget(
            name: "PlaylistRuleTests",
            dependencies: ["LemonfiberNative"],
            path: "ios/Tests/PlaylistRuleTests"
        ),
        .testTarget(
            name: "RangeRuleTests",
            dependencies: ["LemonfiberNative"],
            path: "ios/Tests/RangeRuleTests"
        ),
        .testTarget(
            name: "PlaybackRuleTests",
            dependencies: ["LemonfiberNative"],
            path: "ios/Tests/PlaybackRuleTests"
        ),
        .testTarget(
            name: "WhatToPlayTests",
            dependencies: ["LemonfiberNative"],
            path: "ios/Tests/WhatToPlayTests"
        ),
        .testTarget(
            name: "TrackRuleTests",
            dependencies: ["LemonfiberNative"],
            path: "ios/Tests/TrackRuleTests"
        ),
        .testTarget(
            name: "PlayerExtensionsTests",
            dependencies: ["LemonfiberNative"],
            path: "ios/Tests/PlayerExtensionsTests"
        ),
        .testTarget(
            name: "DoorTrustTests",
            dependencies: ["LemonfiberNative"],
            path: "ios/Tests/DoorTrustTests"
        ),
        .testTarget(
            name: "PlayerStateTests",
            dependencies: ["LemonfiberNative"],
            path: "ios/Tests/PlayerStateTests"
        ),
        .testTarget(
            name: "ControllerRuleTests",
            dependencies: ["LemonfiberNative"],
            path: "ios/Tests/ControllerRuleTests"
        ),
        .testTarget(
            name: "ExtraSubtitlesTests",
            dependencies: ["LemonfiberNative"],
            path: "ios/Tests/ExtraSubtitlesTests"
        ),
        .testTarget(
            name: "RelayRuleTests",
            dependencies: ["LemonfiberNative"],
            path: "ios/Tests/RelayRuleTests"
        ),
    ]
)
