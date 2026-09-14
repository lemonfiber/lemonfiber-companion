<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function sprintf;

/**
 * What the wire calls each field of an envelope this module reads.
 *
 * The case's value *is* the name on the wire, which is the pattern every
 * translated word in this app follows — {@see \Modules\Kernel\Api\Conclusion}
 * and {@see \Modules\Kernel\Api\Severity} are the same idea one level up, where
 * the case's value is the word the core writes for a state. A reader spelling
 * `'severity'` in quotes says the same thing without saying where it came from.
 *
 * **The duplication this removes was structural, not careless.** A `Problem` is
 * flattened into two envelopes — on its own in `problem`, and onto the failing
 * arms of a verdict in `doctor` — so {@see Problems} and {@see Reports} read
 * the same eight names. Spelled as literals they were eight pairs of strings in
 * two files with nothing holding them in agreement, and a rename on the core
 * side would have been fixed in one reader and missed in the other. The failure
 * that produces is a report whose findings all read as unreadable while the
 * problem envelope carries on working, which looks like a broken screen rather
 * than a renamed field.
 *
 * **Not derived from the contract document**, which carries envelope kinds and
 * versions and not the shape inside them. This is the nearest thing to a single
 * place the names live, and being an enum makes the set enumerable — which is
 * what lets a test ask whether every name here is one a reader actually reads.
 */
enum WireField: string
{
    /** What the operator can do about a problem, on one remedy. */
    case Action = 'action';

    /** Which part of the machine a check is about. */
    case Category = 'category';

    /** The check whose finding explains this one, where another does. */
    case CausedBy = 'caused_by';

    /** The stable identifier for the thing a check established. */
    case Check = 'check';

    /** The name a stack gives one listing of repairs, quoted back on a yes. */
    case Agreement = 'agreement';

    /** The stable identifier for a kind of problem, quotable and searchable. */
    case Code = 'code';

    /** The body of an envelope, under its kind and version. */
    case Data = 'data';

    /** How big a request is thought to be, and whether anybody measured it. */
    case Estimate = 'estimate';

    /** The rows of a diagnostic run, in the order the checks produced them. */
    case Findings = 'findings';

    /** What one repair would do, said in the stack's own words. */
    case Does = 'does';

    /** What else that repair touches on its way. */
    case Effects = 'effects';

    /** The name a stack gave a piece of work it agreed to do. */
    case Job = 'job';

    /** The repairs a stack says it would carry out. */
    case Offered = 'offered';

    /** What became of each repair a stack was agreed to carry out. */
    case Mended = 'mended';

    /** What a repair left on the machine where it stopped part-way. */
    case Leaving = 'leaving';

    /** The repair one outcome is about, inside a record of what was done. */
    case Repair = 'repair';

    /** Whether a repair can be taken back afterwards. */
    case Reversible = 'reversible';

    /** The figure inside an estimate. */
    case Bytes = 'bytes';

    /** Whether the figure beside it was measured rather than worked out. */
    case Measured = 'measured';

    /** What a stack calls one request, for naming it when acting on it. */
    case Id = 'id';

    /** The people in a household, each with what they have asked for. */
    case Members = 'members';

    /** What a household member is called. */
    case Name = 'name';

    /** What one member has asked their stack for. */
    case Requests = 'requests';

    /** What a problem means for the operator, in the core's words. */
    case Meaning = 'meaning';

    /** Which arm of the verdict union this is — the tag, not a field beside it. */
    case Outcome = 'outcome';

    /** What a whole run came to, in the run's own judgement. */
    case Overall = 'overall';

    /** Why a check has no answer, on the arms that produced none. */
    case Reason = 'reason';

    /** The single thing to do about an unverified check. Singular on the wire. */
    case Remedy = 'remedy';

    /** What to do about a problem, likeliest first. Plural on the wire. */
    case Remedies = 'remedies';

    /** How much a problem matters, in the engine's judgement. */
    case Severity = 'severity';

    /**
     * Where a problem stands with respect to being fixed.
     *
     * Named `state` on the wire and `Standing` in this app, because `state` is
     * a word a screen already uses for other things. The case is named for the
     * wire, which is what this enum is for; the type it becomes is not.
     */
    case State = 'state';

    /** What happened, in one plain sentence, before it is explained. */
    case Summary = 'summary';

    /** The service a finding is about, where it is about one. */
    case Service = 'service';

    /** What a check is called, in the core's words. */
    case Title = 'title';

    /** How a single check turned out, as a tagged union. */
    case Verdict = 'verdict';

    /**
     * This field's name as a path, where it is read off another field's value.
     *
     * A refusal saying an envelope's `state` is unreadable is ambiguous in the
     * `doctor` envelope, which carries one on every verdict and none at the
     * top — so the message says `verdict.state` and the operator knows which
     * row to look at. Built here rather than written out, so that renaming a
     * case renames it in the sentence too.
     */
    public function under(self $parent): string
    {
        return sprintf('%s.%s', $parent->value, $this->value);
    }
}
