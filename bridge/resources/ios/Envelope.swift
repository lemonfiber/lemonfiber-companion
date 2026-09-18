import Foundation

/// What every function in this bridge answers with.
///
/// `outcome` is always there and is always one of a closed set the capability's
/// own page lists. `because` is there only where the outcome is a refusal and
/// is likewise closed. Neither ever carries a value the caller passed in — no
/// key, no token, no address, no scanned payload — which is what makes a
/// refusal safe to put in a log line.
///
/// **Its own file, and a type rather than a dictionary literal at each call
/// site.** Three capabilities answer refusals and a fourth answers a value
/// beside one, and the promise above is a promise about all of them at once.
/// Written out wherever an answer is built it is a convention, which is the
/// word this repository uses for the thing that holds until somebody new
/// arrives; written here it is one place a reader — or a check — can stand.
///
/// **A refusal cannot carry anything, and that is structural.** There is no
/// factory that takes a reason and a payload together, so the mistake is not
/// available: a refusal that carried the value it refused to hand over would be
/// the whole point of refusing, undone.
///
/// Deliberately mirrors `Envelope.kt` line for line.
public struct Envelope {
    /// What became of the call, as a word.
    public let outcome: String

    /// Why, where the outcome is a refusal, and never otherwise.
    public let because: String?

    /// What the call was asked for, where it is something other than an outcome.
    public let carrying: [String: Any]

    private init(outcome: String, because: String?, carrying: [String: Any]) {
        self.outcome = outcome
        self.because = because
        self.carrying = carrying
    }

    /// An answer that is only its outcome.
    ///
    /// - Parameter outcome: what became of the call.
    /// - Returns: the answer, ready to hand back.
    public static func of(_ outcome: String) -> Envelope {
        Envelope(outcome: outcome, because: nil, carrying: [:])
    }

    /// An answer that carries what it was asked for.
    ///
    /// The payload is a value this application asked the platform for — a list
    /// of what is still to come, a value that was kept. It sits beside the
    /// outcome rather than inside it, so that a caller reading only the outcome
    /// reads a closed word either way.
    ///
    /// - Parameters:
    ///   - outcome: what became of the call.
    ///   - carrying: what the call was asked for.
    /// - Returns: the answer, ready to hand back.
    public static func of(_ outcome: String, carrying: [String: Any]) -> Envelope {
        Envelope(outcome: outcome, because: nil, carrying: carrying)
    }

    /// A refusal, and the closed word for why.
    ///
    /// The outcome is passed in rather than fixed, because the word for a
    /// refusal is the capability's: a write is `refused`, a notification is
    /// `withheld`, a scan that found nothing is `nothing`. What is fixed is
    /// that a reason never appears without one.
    ///
    /// - Parameters:
    ///   - outcome: the capability's word for a refusal.
    ///   - because: the closed word for why.
    /// - Returns: the answer, ready to hand back.
    public static func refusing(_ outcome: String, because: String) -> Envelope {
        Envelope(outcome: outcome, because: because, carrying: [:])
    }

    /// This answer as the bridge hands it back.
    ///
    /// The keys are built here rather than at each handler so that `because`
    /// cannot appear beside a success and cannot go missing from a refusal.
    ///
    /// The envelope's own two keys are written after the payload rather than
    /// before it, so that something carried can never take the place of the
    /// outcome — a payload keyed `outcome` would otherwise be the one answer a
    /// caller reads, chosen by whoever named the field.
    ///
    /// - Returns: the answer, as keys and values.
    public func asAnswer() -> [String: Any] {
        var answer = carrying

        answer["outcome"] = outcome

        if let because { answer["because"] = because }

        return answer
    }
}
