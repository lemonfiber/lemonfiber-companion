import Foundation

/// Whether a connection the player opened reached the door the core stated.
///
/// Two things together, and neither alone: the connection is to the door's
/// host and port, and the certificate it presented is the one pinned. A right
/// certificate somewhere else is a copy of the door's key on another machine;
/// the right machine presenting another certificate is not the door the core
/// vouched for. **A connection that presented no certificate is refused**,
/// because there is nothing to compare and nothing to trust.
///
/// The platform's own trust evaluation is not asked. What it would say about a
/// certificate on a house's own network is not the question; whether this is
/// the certificate the core stated is.
///
/// Deliberately mirrors `DoorTrust.kt` line for line.
public struct DoorTrust: Sendable {
    /// The door the connection must be to.
    public let door: Door

    /// The certificate it must present.
    public let pin: DoorPin

    /// Trust in one door, by one pin.
    public init(door: Door, pin: DoorPin) {
        self.door = door
        self.pin = pin
    }

    /// Whether a connection is to the door and presented its certificate.
    ///
    /// - Parameters:
    ///   - leaf: the leaf certificate the connection presented, DER-encoded, or nil for none.
    ///   - host: the host the connection is to.
    ///   - port: the port it is to.
    /// - Returns: whether to trust it.
    public func admits(leaf: Data?, host: String, port: Int) -> Bool {
        guard let leaf, host.lowercased() == door.host, port == door.port else {
            return false
        }

        return pin.admits(leaf: leaf)
    }
}
