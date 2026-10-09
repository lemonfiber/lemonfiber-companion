import Foundation
import UIKit

/// Playing a title from the household's library, on iOS.
///
/// Namespace: `Lemonfiber.Player.*`
///
/// The decisions worth testing are not in this file. Where a request holds
/// (`WhatToPlay`), which door a connection may reach (`DoorTrust`), what a
/// playlist may name (`PlaylistRule`), which tracks to start with
/// (`TrackRule`), when to report and when to stop waiting (`PlaybackRule`), and
/// what the app is told (`PlayerState`) all run on a laptop. What is here is
/// putting the player on screen and answering the app.
///
/// **What the player says reaches the app only by being asked.** The event this
/// sends carries nothing — it says *ask again* — so a forged or replayed one
/// can make the app ask, and nothing more. `State` is the answer, and it
/// carries no address, no grant and no fingerprint.
///
/// **Nothing the app sent is logged.** Not the location, not the grant, not the
/// fingerprint. The log lines here carry closed words.
enum PlayerFunctions {
    private static let tag = "Lemonfiber"

    /// What the app is told when the player's state moves.
    private static let moved = "Lemonfiber\\Native\\Events\\ThePlayerMoved"

    /// The extensions the player was built with, registered when the app starts.
    static let extensions = PlayerExtensions()

    /// The player on screen, if there is one. Touched on the main thread only.
    private static var screen: PlayerScreen?

    /// Run on the main thread and wait for the answer.
    private static func onMain<T>(_ work: () -> T) -> T {
        Thread.isMainThread ? work() : DispatchQueue.main.sync(execute: work)
    }

    /// Put the player on screen for what the app asked to play.
    class Open: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            let asked: WhatToPlay

            switch WhatToPlay.read(parameters) {
            case .refused(let why):
                NSLog("%@ player: refused, %@", tag, why.word)

                return Envelope.refusing("refused", because: why.word).asAnswer()
            case .toPlay(let read):
                asked = read
            }

            let opened = onMain { () -> Bool in
                screen?.close()
                screen?.dismiss(animated: false)

                let opening = PlayerScreen(asked: asked, extensions: extensions) {
                    LaravelBridge.shared.send?(moved, [:])
                }

                guard opening.open(), let top = topmost() else {
                    return false
                }

                opening.modalPresentationStyle = .fullScreen
                top.present(opening, animated: true)
                screen = opening

                return true
            }

            NSLog("%@ player: %@", tag, opened ? "showing" : "nothing to open")

            return opened
                ? Envelope.of("showing").asAnswer()
                : Envelope.refusing("refused", because: WhyPlaybackStopped.refused.word).asAnswer()
        }
    }

    /// Carry out one thing the app asked of the player on screen.
    class Command: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            let word = parameters["command"] as? String ?? ""
            let seconds = (parameters["seconds"] as? Double) ?? Double(parameters["seconds"] as? Int ?? 0)
            let track = parameters["track"] as? String ?? ""

            let done = onMain { () -> Bool in
                guard let screen else {
                    return false
                }

                screen.command(word, seconds: seconds, track: track)

                return true
            }

            return done
                ? Envelope.of("done").asAnswer()
                : Envelope.refusing("refused", because: "no_player").asAnswer()
        }
    }

    /// Take the player off screen.
    class Close: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            onMain {
                screen?.close()
                screen?.dismiss(animated: true)
            }

            return Envelope.of("closed").asAnswer()
        }
    }

    /// Where the player stands, which is how the app learns anything about playback.
    class State: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            let state = onMain { screen?.state ?? PlayerState.closed }

            return Envelope.of("said", carrying: state.answer()).asAnswer()
        }
    }

    private static func topmost() -> UIViewController? {
        let scenes = UIApplication.shared.connectedScenes.compactMap { $0 as? UIWindowScene }
        var top = scenes.flatMap(\.windows).first(where: \.isKeyWindow)?.rootViewController

        while let next = top?.presentedViewController {
            top = next
        }

        return top
    }
}
