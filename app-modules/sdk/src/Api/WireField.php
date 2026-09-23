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

    /** Whether the household could be read at all, which is why its list is empty. */
    case Available = 'available';

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

    /** What one member may watch, as the media server answered it. */
    case Holdings = 'holdings';

    /** What kind of thing one holding is. */
    case Medium = 'medium';

    /** When a holding came out. Absent where the core could not date it. */
    case Year = 'year';

    /**
     * Which request a decision is about, when one is sent.
     *
     * A second case for one number, because the wire says it twice under two
     * names: a reading calls it `id` inside the row it belongs to, and an
     * action asks for `request` because nothing around it says which kind of
     * thing is being named. One case serving both would be this app deciding
     * they are the same word, which is a fact about the contract and not about
     * this enum.
     */
    case Request = 'request';

    /** The people in a household, each with what they have asked for. */
    case Members = 'members';

    /**
     * The sentences a member is owed, written to them by the core.
     *
     * A list of strings and never parts to assemble: what a surface renders
     * here is what the core wrote, because a surface composing its own wording
     * from a policy and a standing would be a second voice able to disagree
     * with it.
     */
    case ToHandOver = 'to_hand_over';

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

    /**
     * That something was turned down, in the two places the wire says it.
     *
     * What a household request's decline carried, and whether the
     * stack has already said it will not make one of an update's changes. One
     * case because it is one word on the wire, and named here for both so the
     * next reader does not add a second case for the meaning this docblock
     * left out.
     */
    case Refused = 'refused';

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

    /** Which service something belongs to — a finding, or a stalled item. */
    case Service = 'service';

    /** What something is called in the core's words: a check, or a stalled item. */
    case Title = 'title';

    /** Whether a listing is short of what the stack actually holds. */
    case Incomplete = 'incomplete';

    /** The rows of a listing, where the envelope does not name them otherwise. */
    case Items = 'items';

    /** How far a stalled item got before it stopped. */
    case Stage = 'stage';

    /** When something happened, where the thing that wrote it said so. */
    case At = 'at';

    /** One line of what a service wrote, exactly as it wrote it. */
    case Line = 'line';

    /** Which of a service's two mouths a line came out of. */
    case Stream = 'stream';

    /** How a single check turned out, as a tagged union. */
    case Verdict = 'verdict';

    /** What the whole stack amounts to, over the services listed beside it. */
    case Condition = 'condition';

    /** The forms a stack has, whether or not anything in them is running. */
    case Forms = 'forms';

    /** The services a stack has, each with its own state. */
    case Services = 'services';

    /** Containers on the machine that the stack's own configuration does not declare. */
    case Undeclared = 'undeclared';

    /** What a container is running, in the machine's words rather than this app's. */
    case Describes = 'describes';

    /** How much a household loses when one service is not running. */
    case Criticality = 'criticality';

    /** The services one service will not work without. */
    case DependsOn = 'depends_on';

    /** Which form a service belongs to. */
    case Profile = 'profile';

    /** What a service that has ended exited with. Absent while it runs. */
    case Exit = 'exit';

    /** What each verb takes away, on a reading of what is running. */
    case Disturbs = 'disturbs';

    /** Which of a disturbance's two shapes this one is. */
    case Bound = 'bound';

    /** The shape with a clock on it. */
    case Bounded = 'bounded';

    /** How long that clock runs for. */
    case Seconds = 'seconds';

    /** What a disturbance with no clock on it waits for. */
    case Until = 'until';

    /** Bringing services up. */
    case Starting = 'starting';

    /** Taking services down. */
    case Stopping = 'stopping';

    /** Restarting services. */
    case Restarting = 'restarting';

    /**
     * Which of the two things an update reading is about — and, on a listing's
     * limits, the thing the stack cannot act on.
     *
     * One case for both, which is the opposite decision from {@see self::Refusal}
     * and made for the opposite reason: there the wire spends two words on two
     * ideas, and here it spends one word on one idea in two places. *What this
     * is about* is the same question whether the answer is a copy of lemonfiber
     * or a service a survey found, and a second case would assert a distinction
     * the contract does not draw.
     */
    case What = 'what';

    /** The limits a reading carried: what the stack found and cannot act on. */
    case Unsupported = 'unsupported';

    /** Why the stack cannot act on one of them, in the operator's terms. */
    case Because = 'because';

    /** The services, as opposed to this copy of lemonfiber. */
    case TheStack = 'stack';

    /** What taking an update would change, service by service. */
    case Changes = 'changes';

    /**
     * Whether one of those changes cannot be put back.
     *
     * A property of the change rather than of the release: an update can move
     * four services and be undoable for three of them, so this is read per
     * change and the services it is true of are named on their own.
     */
    case Irreversible = 'irreversible';

    /** Where the releases a stack could be on are listed. */
    case Changelog = 'changelog';

    /** The releases inside the changelog, newest as the stack ordered them. */
    case Releases = 'releases';

    /** The release in use. Absent where the stack has not determined one. */
    case Running = 'running';

    /** The technical detail under a verdict, where the core gave one. */
    case Detail = 'detail';

    /** What one release is called. */
    case Version = 'version';

    /**
     * What a release delivers, in the stack's own prose.
     *
     * Optional on the wire, and a release the generator had nothing to say
     * about is a state rather than a defect — read into the two arms of
     * {@see \Modules\Kernel\Api\WhatAReleaseDelivers} so that nothing said
     * and nothing to print cannot be confused on a row.
     */
    case Delivers = 'delivers';

    /** Whether somebody in the house would notice this release. */
    case UserFacing = 'user_facing';

    /** When a release was taken back. Absent on one that still stands. */
    case Withdrawn = 'withdrawn';

    /** What became of each service the last applied update touched. */
    case Applied = 'applied';

    /** How one service's share of an applied update finished. */
    case Ending = 'ending';

    /** The way back the stack named for one service. */
    case Reversal = 'reversal';

    /** Everything the stack is set to, as the `config` envelope lists it. */
    case Settings = 'settings';

    /** One setting's name. */
    case Key = 'key';

    /** What a setting holds, or the stack's note that it is set and withheld. */
    case Value = 'value';

    /**
     * Whether a setting's value was withheld rather than shown.
     *
     * Read as the two arms of {@see \Modules\Kernel\Api\WhatASettingHolds}
     * rather than carried on as a flag, so that no screen holds a value beside
     * a boolean saying whether it may be printed.
     */
    case Secret = 'secret';

    /** A proposed change, read against what is in force, and where it stands. */
    case Review = 'review';

    /** The difference a review is about, as it would be applied. */
    case Change = 'change';

    /** Where a proposed change stands. */
    case Stance = 'stance';

    /** Whether applying a change is cheap or consequential. */
    case Cost = 'cost';

    /**
     * What a setting holds before a proposed change.
     *
     * Absent where the setting holds nothing yet, which is why this is read
     * through an absence type rather than defaulted to an empty string: a
     * screen showing a blank line for *nobody has set this* is telling an
     * operator the setting is empty.
     */
    case From = 'from';

    /** What a setting would hold. */
    case To = 'to';

    /**
     * Why nothing was written, where the reason is not that nobody said yes.
     *
     * A different word on the wire from `refused`, which this enum already
     * carries for a request a household member was turned down for. Two cases
     * because they are two fields on two envelopes, and one case serving both
     * would be this app deciding they are the same thing.
     */
    case Refusal = 'refusal';

    /**
     * Who put a setting's value there, as the tagged word inside `origin`.
     *
     * The field and the tag inside it are both spelled `origin`, which is the
     * contract's doing and not worth a second case: the outer one is a table
     * and the inner one is the word that says which arm of it this is.
     */
    case Origin = 'origin';

    /**
     * Which plugin an origin is attributing a value to.
     *
     * Only on the `plugin` arm. Read into
     * {@see \Modules\Kernel\Api\WhereASettingCameFrom} rather than carried
     * on beside the word, so that no screen holds a name it has not checked
     * belongs to the arm that has one.
     */
    case Named = 'named';

    /**
     * Why an origin could not be established.
     *
     * Only on the `unknown` arm, and required there. A stack is required to
     * say unknown rather than guess, and *unknown* with no reason after it is
     * read as a default by everyone who sees it.
     */
    case Why = 'why';

    /** Every long-running command a machine has, hosted or not. */
    case Commands = 'commands';

    /** The service manager a machine has, or the absence of one this product configures. */
    case Manager = 'manager';

    /**
     * What to do instead, where this product cannot configure the platform.
     *
     * Read only on the `unsupported` arm and required there, for {@see Why}'s
     * reason: *not available here* with nothing after it is the empty box that
     * reads as *off*, and off is a thing somebody goes looking for a switch for.
     */
    case Instruction = 'instruction';

    /** How a long-running command is typed in a terminal, which is what hosting installs. */
    case Command = 'command';

    /** What a long-running command does for as long as it runs, in one sentence. */
    case Guarantees = 'guarantees';

    /** What stands between one long-running command and the machine. */
    case Standing = 'standing';

    /**
     * The program a service definition names, where nothing is there any more.
     *
     * Read only on the `orphaned` arm. On every other standing the wire carries
     * nothing here and there is nothing to carry — a row that is running has no
     * missing program, and reading one would be asking the stack a question
     * about a state it is not in.
     */
    case Missing = 'missing';

    /**
     * How far back a record goes, in the operator's terms.
     *
     * Required, and read before any change is: a trimmed record and one that
     * was always short look the same from their entries, and this is the only
     * field that says which.
     */
    case Horizon = 'horizon';

    /** What one change did, in the operator's terms. */
    case Did = 'did';

    /** The operation that made one change — a seed, a reconfigure, an applied fix. */
    case Operation = 'operation';

    /** What one change was made to. */
    case Target = 'target';

    /**
     * How many changes the operation behind one change made, it among them.
     *
     * An operation is the unit an operator agreed to, so this is what a
     * single line would take with it if it were undone.
     */
    case Alongside = 'alongside';

    /** What to do instead, where putting a change back stops short. */
    case Instead = 'instead';

    /** The image a service runs, without a version. */
    case Image = 'image';

    /**
     * The exact version a stack pins a service at.
     *
     * Its own field rather than part of the image reference, so a caller
     * comparing versions is not parsing them out of a string.
     */
    case Pinned = 'pinned';

    /** The project a service is built from, where its licence is checked. */
    case Upstream = 'upstream';

    /**
     * The licence a service is published under, as an SPDX identifier.
     *
     * Spelled as the wire spells it, which is the American way; the kernel's
     * word is `licence` and this case is the one place the two meet.
     */
    case License = 'license';

    /** Every request lemonfiber makes on its own account. */
    case Ours = 'ours';

    /**
     * What the stack's services reach, attributed to them.
     *
     * A separate field from `ours` on the wire and a separate list on every
     * screen: the two are never merged.
     */
    case Theirs = 'theirs';

    /**
     * Where a request goes.
     *
     * A list on lemonfiber's own requests and one string on a service's —
     * one case for both, because it is one word on the wire naming one idea.
     */
    case Destination = 'destination';

    /** Why a request is made. */
    case Purpose = 'purpose';

    /** Exactly what travels in one of lemonfiber's requests. */
    case Sends = 'sends';

    /** Whether this machine's settings let one of lemonfiber's requests go out. */
    case Allowed = 'allowed';

    /** The setting that switches one of lemonfiber's requests off. */
    case Switch = 'switch';

    /** Which of lemonfiber's own requests a row is. */
    case Reach = 'reach';

    /**
     * Whether the stack ships a record of what a service reaches.
     *
     * False is *nobody knows*, and a service's destination is not read at
     * all where it is — an empty destination would otherwise say the service
     * reaches nothing.
     */
    case Recorded = 'recorded';

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
