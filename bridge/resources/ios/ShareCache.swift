import Foundation

/// The one directory a file handed to the platform's sheet is written to.
///
/// The chosen app has to be able to read the file after the sheet has closed,
/// and nothing on this side learns when it is done. So the bound is on the
/// directory instead: one directory inside the app's own caches, used for
/// nothing else, emptied before every new handover and on the next launch. At
/// most one file is ever in it.
///
/// No UIKit in sight: a directory and some bytes, which is what lets it be run
/// in SwiftPM against a temporary directory rather than demonstrated on a
/// handset. The sheet is `HandoverFunctions.swift`'s.
///
/// Deliberately mirrors `ShareCache.kt`.
public struct ShareCache: Sendable {
    /// What the directory is called inside the app's own caches.
    public static let directoryName = "lemonfiber-handover"

    /// The directory this cache owns, whole. Nothing else writes into it.
    public let directory: URL

    /// A cache owning that directory.
    public init(directory: URL) {
        self.directory = directory
    }

    /// The cache whose directory sits inside that caches directory.
    public static func inside(_ caches: URL) -> ShareCache {
        ShareCache(directory: caches.appendingPathComponent(directoryName, isDirectory: true))
    }

    /// The cache inside this app's own caches directory, where the platform names one.
    public static func inThisAppsCaches() -> ShareCache? {
        FileManager.default.urls(for: .cachesDirectory, in: .userDomainMask).first.map(inside)
    }

    /// Take the directory away, and whatever was handed over last with it.
    public func sweep() {
        try? FileManager.default.removeItem(at: directory)
    }

    /// Sweep, then write the bytes under that name, and answer the file.
    ///
    /// Answers nothing where there is nothing to write, where the name is not
    /// one file's name, or where the file could not be written. The sweep
    /// happens either way, so a refused handover leaves nothing behind either.
    public func write(name: String, bytes: Data) -> URL? {
        sweep()

        guard !bytes.isEmpty, Self.isAFileName(name) else {
            return nil
        }

        let file = directory.appendingPathComponent(name, isDirectory: false)

        do {
            try FileManager.default.createDirectory(at: directory, withIntermediateDirectories: true)
            try bytes.write(to: file)

            return file
        } catch {
            return nil
        }
    }

    /// Whether this is the file the cache holds: the only file the sheet is
    /// ever given.
    public func holds(_ file: URL) -> Bool {
        var isADirectory: ObjCBool = false
        let exists = FileManager.default.fileExists(atPath: file.path, isDirectory: &isADirectory)

        return exists && !isADirectory.boolValue
            && file.resolvingSymlinksInPath().deletingLastPathComponent().path
                == directory.resolvingSymlinksInPath().path
    }

    /// Whether a name is one file's name, rather than nothing or a way out of the directory.
    public static func isAFileName(_ name: String) -> Bool {
        !name.isEmpty && name != "." && name != ".."
            && !name.contains { $0 == "/" || $0 == "\\" || $0 == "\u{0}" }
    }
}
