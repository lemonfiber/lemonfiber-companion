import Foundation
import Testing

@testable import LemonfiberNative

// Where a handed-over file is written, and how little of it is left behind.
//
// Run against a temporary directory standing in for the app's own caches. The
// directory the cache owns sits inside it, so a test can see both what the
// cache wrote and that it wrote nowhere else.
//
// The same cases as `ShareCacheTest.kt`, in the same order.

/// A fresh directory standing in for the app's caches, and the cache inside it.
private func aCacheInAFreshDirectory() throws -> (caches: URL, cache: ShareCache) {
    let caches = FileManager.default.temporaryDirectory
        .appendingPathComponent(UUID().uuidString, isDirectory: true)
    try FileManager.default.createDirectory(at: caches, withIntermediateDirectories: true)

    return (caches, ShareCache.inside(caches))
}

/// Whether anything is at that URL.
private func thereIsSomethingAt(_ url: URL) -> Bool {
    FileManager.default.fileExists(atPath: url.path)
}

@Test("a file is written under the name given, byte for byte, in the directory named for handing over")
func aFileIsWrittenUnderItsName() throws {
    let (caches, cache) = try aCacheInAFreshDirectory()
    defer { try? FileManager.default.removeItem(at: caches) }

    let written = try #require(cache.write(name: "a-bundle.tar.gz", bytes: Data([1, 2, 3])))

    #expect(ShareCache.directoryName == "lemonfiber-handover")
    #expect(ShareCache.inThisAppsCaches()?.directory.lastPathComponent == "lemonfiber-handover")
    #expect(written == cache.directory.appendingPathComponent("a-bundle.tar.gz"))
    #expect(try Data(contentsOf: written) == Data([1, 2, 3]))
}

@Test("a second handover sweeps the first, so one file at most is ever held")
func aSecondHandoverSweepsTheFirst() throws {
    let (caches, cache) = try aCacheInAFreshDirectory()
    defer { try? FileManager.default.removeItem(at: caches) }

    let first = try #require(cache.write(name: "the-first.tar.gz", bytes: Data([1])))
    _ = cache.write(name: "the-second.tar.gz", bytes: Data([2]))

    #expect(!thereIsSomethingAt(first))
    #expect(
        try FileManager.default.contentsOfDirectory(atPath: cache.directory.path) == ["the-second.tar.gz"])
}

@Test("sweeping takes the directory and what is in it away, and touches nothing beside it")
func sweepingTakesOnlyItsOwnDirectory() throws {
    let (caches, cache) = try aCacheInAFreshDirectory()
    defer { try? FileManager.default.removeItem(at: caches) }

    let beside = caches.appendingPathComponent("somebody-elses.txt")
    try Data("kept".utf8).write(to: beside)
    _ = cache.write(name: "a-bundle.tar.gz", bytes: Data([1]))

    cache.sweep()
    cache.sweep()

    #expect(!thereIsSomethingAt(cache.directory))
    #expect(try Data(contentsOf: beside) == Data("kept".utf8))
}

@Test("a name that is nothing or a way out is refused, and the last file is still swept")
func aNameThatIsNotAFileIsRefused() throws {
    let (caches, cache) = try aCacheInAFreshDirectory()
    defer { try? FileManager.default.removeItem(at: caches) }

    let before = try #require(cache.write(name: "a-bundle.tar.gz", bytes: Data([1])))

    for name in ["", ".", "..", "../escaped", "a/b", "a\\b", "a\u{0}b"] {
        #expect(cache.write(name: name, bytes: Data([1])) == nil, "\(name)")
        #expect(!ShareCache.isAFileName(name), "\(name)")
    }

    #expect(!thereIsSomethingAt(before))
    #expect(!thereIsSomethingAt(caches.appendingPathComponent("escaped")))
}

@Test("nothing to write is refused, and the last file is still swept")
func nothingToWriteIsRefused() throws {
    let (caches, cache) = try aCacheInAFreshDirectory()
    defer { try? FileManager.default.removeItem(at: caches) }

    let before = try #require(cache.write(name: "a-bundle.tar.gz", bytes: Data([1])))

    #expect(cache.write(name: "a-bundle.tar.gz", bytes: Data()) == nil)
    #expect(!thereIsSomethingAt(before))
}

@Test("a directory that cannot be made answers nothing rather than failing")
func aDirectoryThatCannotBeMadeAnswersNothing() throws {
    let (caches, _) = try aCacheInAFreshDirectory()
    defer { try? FileManager.default.removeItem(at: caches) }

    let aFile = caches.appendingPathComponent("a-file")
    try Data("in the way".utf8).write(to: aFile)
    let blocked = ShareCache(directory: aFile.appendingPathComponent(ShareCache.directoryName))

    #expect(blocked.write(name: "a-bundle.tar.gz", bytes: Data([1])) == nil)
}

@Test("the only file a grant is ever for is the one the cache holds")
func theOnlyFileAGrantIsForIsTheOneHeld() throws {
    let (caches, cache) = try aCacheInAFreshDirectory()
    defer { try? FileManager.default.removeItem(at: caches) }

    let written = try #require(cache.write(name: "a-bundle.tar.gz", bytes: Data([1])))
    let beside = caches.appendingPathComponent("a-bundle.tar.gz")
    try Data([1]).write(to: beside)

    #expect(cache.holds(written))
    #expect(!cache.holds(beside))
    #expect(!cache.holds(cache.directory))

    cache.sweep()

    #expect(!cache.holds(written))
}
