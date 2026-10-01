import LocalAuthentication

/// What the device's own prompt accepts as proof of who is holding the phone.
///
/// `.deviceOwnerAuthentication` is biometry with the passcode behind it: the
/// system offers the passcode where Face ID or Touch ID fails, is not enrolled
/// or has been removed, so the fallback is to the passcode rather than to
/// nothing, and rather than to unlocked. `.deviceOwnerAuthenticationWithBiometrics`
/// would lock an operator with a passcode and no enrolled face out of the app.
public enum WhatUnlocks {
    /// The policy every prompt this app raises is evaluated under.
    public static let accepted: LAPolicy = .deviceOwnerAuthentication
}
