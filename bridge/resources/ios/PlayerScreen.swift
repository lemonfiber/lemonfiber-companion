import AVFoundation
import AVKit
import UIKit

/// The player on screen: AVPlayer over the door, full screen, with the platform's own controls.
///
/// AVKit draws the controls, picture-in-picture and the lock-screen entry,
/// and plays on with the screen locked where the audio session allows it. What
/// is decided here is what the platform does not decide: which tracks to start
/// with (`TrackRule`), when to report how far it got and when to stop waiting
/// (`PlaybackRule`), and why it stopped where it did.
///
/// **It never waits on a spinner.** A stall is waited out for as long as the
/// rule allows and then the player stops and says the library is out of reach;
/// nothing buffers for ever and nothing is retried in a loop.
final class PlayerScreen: AVPlayerViewController {
    /// What the member asked to play.
    private let asked: WhatToPlay

    /// Every extension the player was built with.
    private let extensions: PlayerExtensions

    /// What fetches every byte from the door.
    private let relay: DoorRelay

    /// Where the stream plays from, as the extensions resolved it, or nil where nothing could play it.
    private let source: PlayableSource?

    /// Called whenever the state moves, so the app can be told to ask again.
    private let moved: () -> Void

    /// How often the stall and the position are looked at.
    private static let lookEvery = CMTime(seconds: 1, preferredTimescale: 1)

    /// The periodic observer, kept so it can be removed.
    private var looking: Any?

    /// The status observer, kept so it can be removed.
    private var watchingStatus: NSKeyValueObservation?

    /// The time-control observer, kept so it can be removed.
    private var watchingControl: NSKeyValueObservation?

    /// The seek observer, kept so it can be removed.
    private var watchingSeeks: NSObjectProtocol?

    private var watchingFailures: NSObjectProtocol?

    /// When the picture last stopped for want of data, or nil while it is not stalled.
    private var stalledSince: Date?

    /// When the position was last reported.
    private var lastReported = Date.distantPast

    /// Where playback stands now.
    private(set) var state = PlayerState.closed

    /// The sound and subtitle groups the stream offers, loaded once it is ready.
    private var groups: [AVMediaCharacteristic: AVMediaSelectionGroup] = [:]

    init(asked: WhatToPlay, extensions: PlayerExtensions, moved: @escaping () -> Void) {
        self.asked = asked
        self.extensions = extensions
        let source = extensions.source(for: asked)
        self.source = source
        self.relay = DoorRelay(
            asked: asked, top: source?.address, extras: ExtraSubtitles(extensions.extraTracks(for: asked)))
        self.moved = moved
        super.init(nibName: nil, bundle: nil)
    }

    @available(*, unavailable)
    required init?(coder: NSCoder) {
        fatalError("The player is only ever built from what the app asked to play.")
    }

    /// Open the stream and start playing from where the member was.
    ///
    /// - Returns: whether there was anything to open.
    func open() -> Bool {
        guard let source, let handed = relay.open(source.address) else {
            return false
        }

        try? AVAudioSession.sharedInstance().setCategory(.playback, mode: .moviePlayback)

        let item = AVPlayerItem(asset: AVURLAsset(url: handed))
        item.externalMetadata = [Self.titled(asked.shown.title)]

        let player = AVPlayer(playerItem: item)
        player.allowsExternalPlayback = true
        self.player = player
        allowsPictureInPicturePlayback = true
        canStartPictureInPictureAutomaticallyFromInline = true

        settle(.opening, position: asked.startAt)
        watch(item, on: player)
        DispatchQueue.main.asyncAfter(deadline: .now() + PlaybackRule.patienceWhileOpening) { [weak self] in
            self?.openingTookTooLong()
        }

        return true
    }

    /// Carry out one thing the app asked of the player.
    func command(_ word: String, seconds: Double, track: String) {
        guard let player else {
            return
        }

        switch word {
        case "play":
            player.play()
        case "pause":
            player.pause()
        case "seek":
            player.seek(to: CMTime(seconds: seconds, preferredTimescale: 600))
        case "audio":
            choose(track, in: .audible)
        case "subtitle":
            choose(track, in: .legible)
        default:
            return
        }
    }

    /// Stop, say where it got to, and let go of everything.
    func close() {
        guard player != nil else {
            return
        }

        let position = player?.currentTime().seconds ?? 0

        extensions.tell(.closed(position: position.isFinite ? position : 0))
        player?.pause()
        unwatch()
        relay.close()
        player = nil
        settle(.closed, position: position)
    }

    override func viewDidDisappear(_ animated: Bool) {
        super.viewDidDisappear(animated)

        if isBeingDismissed || presentingViewController == nil {
            close()
        }
    }

    // MARK: - Watching

    private func watch(_ item: AVPlayerItem, on player: AVPlayer) {
        watchingStatus = item.observe(\.status) { [weak self] item, _ in
            DispatchQueue.main.async { self?.statusChanged(item) }
        }

        watchingControl = player.observe(\.timeControlStatus) { [weak self] player, _ in
            DispatchQueue.main.async { self?.controlChanged(player) }
        }

        watchingSeeks = NotificationCenter.default.addObserver(
            forName: AVPlayerItem.timeJumpedNotification, object: item, queue: .main
        ) { [weak self] _ in
            self?.report(moved: true)
        }

        watchingFailures = NotificationCenter.default.addObserver(
            forName: AVPlayerItem.failedToPlayToEndTimeNotification, object: item, queue: .main
        ) { [weak self] _ in
            self?.stop(self?.relay.why ?? .unreachable)
        }

        looking = player.addPeriodicTimeObserver(forInterval: Self.lookEvery, queue: .main) { [weak self] _ in
            self?.tick()
        }
    }

    private func unwatch() {
        if let looking {
            player?.removeTimeObserver(looking)
        }

        if let watchingSeeks {
            NotificationCenter.default.removeObserver(watchingSeeks)
        }

        if let watchingFailures {
            NotificationCenter.default.removeObserver(watchingFailures)
        }

        looking = nil
        watchingSeeks = nil
        watchingFailures = nil
        watchingStatus = nil
        watchingControl = nil
    }

    private func statusChanged(_ item: AVPlayerItem) {
        switch item.status {
        case .readyToPlay:
            loadGroups(of: item)
            extensions.tell(.ready(duration: item.duration.seconds.isFinite ? item.duration.seconds : 0))
            player?.seek(to: CMTime(seconds: asked.startAt, preferredTimescale: 600)) { [weak self] _ in
                self?.player?.play()
            }
        case .failed:
            stop(relay.why ?? .unsupportedFormat)
        default:
            return
        }
    }

    private func openingTookTooLong() {
        if state.stands == .opening {
            stop(relay.why ?? .unreachable)
        }
    }

    private func controlChanged(_ player: AVPlayer) {
        switch player.timeControlStatus {
        case .playing:
            stalledSince = nil
            settle(.playing)
        case .paused:
            stalledSince = nil
            report(moved: true)
            settle(player.currentItem.map { $0.currentTime() >= $0.duration } == true ? .ended : .paused)

            if state.stands == .ended {
                extensions.tell(.ended)
            }
        case .waitingToPlayAtSpecifiedRate:
            stalledSince = stalledSince ?? Date()
            settle(.stalled)
        @unknown default:
            return
        }
    }

    private func tick() {
        if let stalledSince, PlaybackRule.givesUp(stalledFor: Date().timeIntervalSince(stalledSince)) {
            stop(relay.why ?? .unreachable)

            return
        }

        report(moved: false)
    }

    /// Load the sound and subtitle groups, then start the tracks the member prefers.
    private func loadGroups(of item: AVPlayerItem) {
        let asset = item.asset

        Task { @MainActor [weak self] in
            for characteristic in [AVMediaCharacteristic.audible, .legible] {
                if let group = try? await asset.loadMediaSelectionGroup(for: characteristic) {
                    self?.groups[characteristic] = group
                }
            }

            self?.startTracks()
        }
    }

    // MARK: - Deciding

    private func startTracks() {
        let audio = Self.offered(groups[.audible])
        let subtitles = Self.offered(groups[.legible])

        if let id = TrackRule.audio(offered: audio, preferred: asked.shown.audio) {
            choose(id, in: .audible)
        }

        choose(
            TrackRule.subtitle(offered: subtitles, preferred: asked.shown.subtitle) ?? TrackRule.off,
            in: .legible)
    }

    private func choose(_ id: String, in characteristic: AVMediaCharacteristic) {
        guard let item = player?.currentItem, let group = groups[characteristic] else {
            return
        }

        let option = Int(id).flatMap { group.options.indices.contains($0) ? group.options[$0] : nil }

        item.select(option, in: group)
        settle(state.stands)
    }

    private func report(moved: Bool) {
        let position = player?.currentTime().seconds ?? 0
        let since = Date().timeIntervalSince(lastReported)
        let isPlaying = player?.timeControlStatus == .playing

        guard PlaybackRule.reportsProgress(sinceLastReport: since, isPlaying: isPlaying, moved: moved) else {
            return
        }

        lastReported = Date()
        extensions.tell(isPlaying ? .progressed(position: position) : .paused(position: position))
        settle(state.stands, position: position)
    }

    private func stop(_ why: WhyPlaybackStopped) {
        guard state.stands != .stopped else {
            return
        }

        player?.pause()
        extensions.tell(.stopped(why))
        settle(.stopped, why: why)
    }

    /// Move to a new state and tell the app to ask again.
    private func settle(
        _ stands: WherePlaybackStands, position: Double? = nil, why: WhyPlaybackStopped? = nil
    ) {
        let item = player?.currentItem
        let now = position ?? item?.currentTime().seconds ?? state.position
        let length = item?.duration.seconds ?? 0

        state = PlayerState(
            stands: stands,
            position: now.isFinite ? now : 0,
            duration: length.isFinite ? length : 0,
            audio: Self.offered(groups[.audible]),
            subtitles: Self.offered(groups[.legible]),
            chosenAudio: item.flatMap { Self.chosen($0, groups[.audible]) },
            chosenSubtitle: item.flatMap { Self.chosen($0, groups[.legible]) },
            why: why ?? (stands == .stopped ? state.why : nil))
        moved()
    }

    // MARK: - Reading the platform

    /// Every option in one group, as tracks.
    private static func offered(_ group: AVMediaSelectionGroup?) -> [OfferedTrack] {
        guard let group else {
            return []
        }

        return group.options.enumerated().map { at, option in
            OfferedTrack(
                id: String(at),
                language: option.extendedLanguageTag ?? option.locale?.identifier ?? "",
                label: option.displayName,
                isDefault: group.defaultOption == option)
        }
    }

    /// The option chosen in one group, or nil.
    private static func chosen(_ item: AVPlayerItem, _ group: AVMediaSelectionGroup?) -> String? {
        guard let group, let option = item.currentMediaSelection.selectedMediaOption(in: group),
            let at = group.options.firstIndex(of: option)
        else {
            return nil
        }

        return String(at)
    }

    /// The title, as the lock screen and picture-in-picture show it.
    private static func titled(_ title: String) -> AVMetadataItem {
        let item = AVMutableMetadataItem()
        item.identifier = .commonIdentifierTitle
        item.value = title as NSString
        item.extendedLanguageTag = "und"

        return item
    }
}
