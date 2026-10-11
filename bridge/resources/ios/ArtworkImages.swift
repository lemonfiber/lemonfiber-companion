import UIKit

/// Artwork held in memory, decoded for drawing.
///
/// Decoding is paid once per picture rather than on every evaluation of a
/// view's body, and the decoded images are held in memory only, beside the
/// bytes in `ArtworkHeld`. Letting go of a stack's pictures or of every one
/// forgets their decoded images too.
enum ArtworkImages {
    nonisolated(unsafe) private static let decoded: NSCache<NSString, UIImage> = {
        let cache = NSCache<NSString, UIImage>()
        cache.totalCostLimit = 64 * 1024 * 1024

        return cache
    }()

    /// The image held under a source, where one is.
    static func image(for source: String) -> UIImage? {
        let key = source as NSString

        if let image = decoded.object(forKey: key) {
            return image
        }

        guard let bytes = ArtworkHeld.shared.bytes(forKey: source), let image = UIImage(data: bytes) else {
            return nil
        }

        decoded.setObject(image, forKey: key, cost: bytes.count)

        return image
    }

    /// Forget the decoded images of these sources.
    static func forget(_ sources: [String]) {
        sources.forEach { decoded.removeObject(forKey: $0 as NSString) }
    }

    /// Let go of every picture held, and of what was decoded from them.
    static func letGoOfEvery() {
        forget(ArtworkHeld.shared.letGoOfEvery())
    }
}
