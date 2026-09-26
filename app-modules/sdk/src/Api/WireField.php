<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

/**
 * What the wire calls each field that more than one envelope this module reads carries.
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
 *
 * **Only the shared words are here.** A word one envelope carries lives in
 * that envelope's own enum under `Fields`, so a name there says which payload
 * it belongs to; a word read out of two or more envelopes lives here, and
 * `data`, the payload every envelope has, does too. `EveryWireFieldIsReadTest`
 * holds each word to the enum it belongs in.
 */
enum WireField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** What the operator can do about a problem, on one remedy. */
    case Action = 'action';

    /** Which part of the machine a check is about. */
    case Category = 'category';

    /** The stable identifier for the thing a check established. */
    case Check = 'check';

    /** Whether the household could be read at all, which is why its list is empty. */
    case Available = 'available';

    /** The stable identifier for a kind of problem, quotable and searchable. */
    case Code = 'code';

    /** The body of an envelope, under its kind and version. */
    case Data = 'data';

    /** The rows of a diagnostic run, in the order the checks produced them. */
    case Findings = 'findings';

    /** The figure inside an estimate. */
    case Bytes = 'bytes';

    /** What a stack calls one request, for naming it when acting on it. */
    case Id = 'id';

    /** What something is called: a household member, or a credential in the operator's words. */
    case Name = 'name';

    /** What something comes to for the operator, in the core's words: a problem, or the front door. */
    case Meaning = 'meaning';

    /** Which arm of the verdict union this is — the tag, not a field beside it. */
    case Outcome = 'outcome';

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
     *
     * The `credentials` envelope spends the same word on where a credential
     * stands.
     */
    case State = 'state';

    /**
     * Which service something belongs to — a finding, or a stalled item — and
     * the service the household begins at, or can reach beside it.
     */
    case Service = 'service';

    /** What something is called in the core's words: a check, or a stalled item. */
    case Title = 'title';

    /** When something happened, where the thing that wrote it said so. */
    case At = 'at';

    /** The services a stack has, each with its own state. */
    case Services = 'services';

    /**
     * Forms, in the two senses the wire uses the word: the forms a stack
     * declares, listed on the `forms` envelope and named by an action, and
     * the forms lemonfiber writes one of its words in, on each word of the
     * `glossary`.
     */
    case Forms = 'forms';

    /**
     * The services the forms asked for left out, each with what it would
     * need: of the forms running, on the `status` envelope, and of the forms
     * named, on the `preview` envelope.
     */
    case Filtered = 'filtered';

    /** What a service left out would have needed to be started. */
    case Needs = 'needs';

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

    /**
     * Why, in the operator's terms: why the stack cannot act on something,
     * what is wrong behind a symptom, and why a service is not the front door.
     */
    case Because = 'because';

    /** What taking an update would change, service by service. */
    case Changes = 'changes';

    /** The way back the stack named for one service. */
    case Reversal = 'reversal';

    /**
     * Whether a setting's value was withheld rather than shown.
     *
     * Read as the two arms of {@see \Modules\Kernel\Api\WhatASettingHolds}
     * rather than carried on as a flag, so that no screen holds a value beside
     * a boolean saying whether it may be printed.
     */
    case Secret = 'secret';

    /** Whether applying a change is cheap or consequential. */
    case Cost = 'cost';

    /**
     * Who put a setting's value there, as the tagged word inside `origin`.
     *
     * The field and the tag inside it are both spelled `origin`, which is the
     * contract's doing and not worth a second case: the outer one is a table
     * and the inner one is the word that says which arm of it this is.
     *
     * On the `credentials` envelope it is a plain word, not a table: who
     * produced the credential.
     */
    case Origin = 'origin';

    /**
     * Which plugin an origin is attributing a value to.
     *
     * Only on the `plugin` arm. Read into
     * {@see \Modules\Kernel\Api\WhoPutItThere} rather than carried
     * on beside the word, so that no screen holds a name it has not checked
     * belongs to the arm that has one.
     *
     * On the front door, what the operator named as the door where the stack
     * refused it.
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

    /**
     * What a plugin's change replaced, on the `overridden` arm of an origin.
     *
     * Read into {@see \Modules\Kernel\Api\WhatItReplaced} with its own
     * origin, so no screen calls a replaced value the stack's own when the
     * operator or another plugin set it.
     */
    case Replaced = 'replaced';

    /** Whether a replaced value is withheld because the setting holds a credential. */
    case Withheld = 'withheld';

    /**
     * Where something was before: what a setting holds before a proposed
     * change, and where the value a plugin replaced came from.
     *
     * The setting's is absent where it holds nothing yet, which is why it is
     * read through an absence type rather than defaulted to an empty string: a
     * blank line for *nobody has set this* tells an operator the setting is
     * empty.
     */
    case From = 'from';

    /**
     * What a setting holds, or the stack's note that it is set and withheld,
     * and the value a plugin replaced.
     */
    case Value = 'value';

    /** Where something stands: one long-running command, or the household's front door. */
    case Standing = 'standing';

    /** What an alert preset means, in the operator's terms. */
    case Means = 'means';

    /** What throttling the upload costs, where an upload limit is in force. */
    case Ratio = 'ratio';

    /**
     * What is running: the version of the running copy of lemonfiber on the
     * `self-update` envelope, and the release in use inside an `update`
     * changelog, which is absent where the stack has not determined one.
     */
    case Running = 'running';

    /**
     * What is on offer: the repairs a stack says it would carry out, and the
     * newest version of lemonfiber released.
     */
    case Offered = 'offered';

    /**
     * What to type: how a long-running command is typed in a terminal, which
     * is what hosting installs, and exactly what to type to update lemonfiber.
     */
    case Command = 'command';

    /**
     * What to do instead: where putting a change back stops short, why there
     * is nothing exact to type to update lemonfiber, what to use on a device
     * that is poorly served, and what stops playback straining.
     */
    case Instead = 'instead';

    /**
     * A stage in the pipeline: how far a stalled item got before it stopped,
     * and each stage a traced item reached.
     */
    case Stage = 'stage';

    /**
     * Two meanings under one word, each in its own envelope: whether an event
     * set apart is heard about, whatever the preset says, and how many parts
     * of a traced series were asked for.
     */
    case Wanted = 'wanted';

    /**
     * What happened, in one plain sentence, on an `error`; and what the
     * credential store is, before any claim about what it protects against.
     */
    case Summary = 'summary';

    /**
     * A preset by the name it was chosen under: the alert preset in force, and
     * the quality preset that strains playback on this machine.
     */
    case Preset = 'preset';

    /**
     * What sits beside the thing asked about: what is on the machine that is
     * not the stack's to keep, and the services the household can reach that
     * are not the front door.
     */
    case Beside = 'beside';

    /**
     * What is worth knowing before starting: on a device an app is recommended
     * for, on what strains playback, and on an address handed to the household.
     */
    case Caution = 'caution';

    /**
     * How the running copy got onto the machine, on a `self-update`; and
     * whether a hosting run installed a command rather than took it back.
     */
    case Installed = 'installed';

    /**
     * What the version on offer says it changed, on a `self-update`; and what
     * one install or removal did to the machine, on a `hosting`.
     */
    case Changed = 'changed';

    /**
     * Where somebody is sent: a service beside the front door, or the one
     * address an invitation hands over. Either way the machine's own text.
     */
    case Address = 'address';

    /** A volume's own size or its quota, and how far up the ratings an invited member may watch. */
    case Limit = 'limit';

    /**
     * What was taken back: a release that no longer stands, or the invitations
     * nobody claimed in time.
     */
    case Withdrawn = 'withdrawn';

    /**
     * What an action wrote: what became of each service an update touched, and
     * what an invitation wrote on the account.
     */
    case Applied = 'applied';

    /**
     * Whether an act was only described rather than carried out: a hosting
     * run, in which case nothing it lists happened, and an invitation, in
     * which case no account was made.
     */
    case Rehearsed = 'rehearsed';

    /**
     * Whether something disturbs what is running: what each verb takes away,
     * on a reading of what is running, and whether a mode of moving in stops
     * or alters what is already here.
     */
    case Disturbs = 'disturbs';

    /**
     * What would put something right, in one sentence: the single thing to do
     * about an unverified check, and what would fix a layout that cannot hold
     * a hardlink.
     */
    case Remedy = 'remedy';

    /**
     * What was found and cannot be acted on: the limits a reading of what is
     * stuck carried, and what a survey found and cannot take over.
     */
    case Unsupported = 'unsupported';

    /** Where something would go: what a setting would hold, and the port a service would take instead. */
    case To = 'to';
}
