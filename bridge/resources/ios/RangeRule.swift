/// Byte ranges, as the player asks for part of a file and checks what came back.
///
/// Direct play reads a file from wherever the viewer seeks to, so the player
/// asks the door for a range rather than the whole. What comes back is checked
/// against what was asked: a door that answers a range with the whole file
/// would hand the player the wrong bytes at the right position, and a picture
/// that plays the wrong second is worse than one that stops.
///
/// Deliberately mirrors `RangeRule.kt` line for line.
public enum RangeRule {
    /// The status a whole file is answered with.
    public static let whole = 200

    /// The status part of one is answered with.
    public static let part = 206

    /// The `Range` header for a read, or nil where the read is the whole file.
    ///
    /// - Parameters:
    ///   - offset: the first byte wanted.
    ///   - length: how many bytes, or nil for everything after the first.
    /// - Returns: the header's value, or nil.
    public static func asked(offset: Int64, length: Int64?) -> String? {
        guard let length else {
            return offset == 0 ? nil : "bytes=\(offset)-"
        }

        return "bytes=\(offset)-\(offset + length - 1)"
    }

    /// The whole file's length, read off a `Content-Range` header, or nil where it does not say.
    ///
    /// - Parameter contentRange: the header's value, if there was one.
    /// - Returns: the length, or nil.
    public static func total(contentRange: String?) -> Int64? {
        guard let contentRange, let slash = contentRange.lastIndex(of: "/") else {
            return nil
        }

        return Int64(contentRange[contentRange.index(after: slash)...])
    }

    /// Whether an answer is the one asked for.
    ///
    /// - Parameters:
    ///   - status: the status the door answered with.
    ///   - askedForPart: whether a `Range` header was sent.
    /// - Returns: whether the bytes that came back are the bytes wanted.
    public static func admits(status: Int, askedForPart: Bool) -> Bool {
        status == (askedForPart ? part : whole)
    }
}
