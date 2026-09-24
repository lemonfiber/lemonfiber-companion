<?php

declare(strict_types=1);

use Modules\Dx\Internal\WhatTheContractDeclares;
use Tests\Support\Tree;
use Tests\Support\WhatTheReadersRead;

// What the wire carries that this app does not read, and why.
//
// `WhatTheContractDoesNotCarryTest` watches one direction: a requirement this
// app cannot answer because the contract carries nothing to answer it with. The
// day lemonfiber adds the field, that suite goes red and says which requirement
// to go and answer.
//
// This is the other direction, and nothing was watching it. lemonfiber adds a
// field; no requirement asks this app to show it; no reader names it; every gate
// stays green; and the fact that somebody should have decided is recorded
// nowhere. The contract has moved under this app several times in a week, and
// each time the only thing that noticed was somebody reading a diff.
//
// So every path the contract declares on an envelope this app reads is either
// **read by a reader** — followed to the subscript that takes it — or **listed
// below with a reason**. A path that is neither fails this suite, which is the
// moment somebody decides rather than the moment somebody notices.
//
// **A name is not a place, and the difference is the whole of this rule.**
// Asking whether this app has a `WireField` case for a field answers yes for
// every path that field's name appears at: one case for `state` covers
// `error.state`, `status.services[].state`, `doctor.findings[].verdict.state`
// and `update.changelog.state` alike. Twenty-nine names do that across
// ninety-nine of the two hundred and twenty-three paths here.
//
// The last of those four is the argument. `update.changelog.state` is the field
// `Tests\Support\WhatTheContractAccepts` exists because a reader misread — the
// top-level `state` taken for the triple the contract puts under `changelog` —
// and a register built to catch that could not see it, because a case written
// for the `status` envelope had already answered for it. So the question is
// *does anything read this path*, and `Tests\Support\WhatTheReadersRead`
// answers it by following the reader.
//
// **It is a register of decisions, not of gaps.** Most rows here will never be
// read, and saying so is the point: *the household surface is blocked* and *no
// requirement asks for this* are different answers, and both are better than an
// unread field nobody weighed. A row is removed by reading the field, never by
// deleting the row because it is inconvenient.
//
// **The substitution rule cuts the other way here.** It forbids inventing a value the
// contract does not carry. This one refuses the opposite habit — building a
// surface *because* the wire happens to carry a field. A field wants a
// requirement before it wants a screen, and the row is where that is said.

/**
 * Every path this app has read and decided not to read, and why.
 *
 * `path` names one place on one envelope exactly, and covers everything beneath
 * it. `because` is the decision, and it is the only part that survives the
 * person who made it.
 */
const WHAT_THIS_APP_DOES_NOT_READ = [
    [
        'path' => 'AlertsEnvelope.changed',
        'because' => 'Whether the call that answered changed what the operator is told about. This app '
            . 'reads the setting and never changes it — what is heard about is the core\'s decision, '
            . 'configured where the core is — so every answer it asks for says no, and a screen showing '
            . 'that would be reporting on an act it did not perform.',
    ],
    [
        'path' => 'AlertsEnvelope.rehearsed',
        'because' => 'Whether the call that answered only reported what it would have written. The same '
            . 'reason as `changed`: this app makes no call that writes, so it makes none that '
            . 'rehearses, and a rehearsal label on a plain reading would describe something that never '
            . 'happened.',
    ],
    [
        'path' => 'HostingEnvelope.caveat',
        'because' => 'What is true of this machine\'s service manager and worth knowing before it is '
            . 'relied on — a launch agent runs in a login session, so a Mac that is never signed in keeps '
            . 'nothing running. It belongs on this surface and the sentence is the core\'s to write, so '
            . 'reading it is the next thing here rather than a decision against it. Unread today because '
            . 'the screen says what each command stands at and does not yet say what the manager as a '
            . 'whole is worth trusting for.',
    ],
    [
        'path' => 'HostingEnvelope.changed',
        'because' => 'What one run of an install or a take-it-back did to the machine. This app never '
            . 'asks for one: what is configured is the core\'s and this surface reads, so the field is '
            . 'absent on every answer it asks for, and a screen reading it would be reporting on an act '
            . 'it did not perform. Named as one path rather than five because the whole branch is '
            . 'unreachable from here for one reason.',
    ],
    [
        'path' => 'HostingEnvelope.commands[].definition',
        'because' => 'The service definition installed for a command — the plist or unit file. Technical '
            . 'detail, which is available and does not lead: it is what somebody opens a terminal for '
            . 'after the screen has told them which command is wrong, and putting a file path on the row '
            . 'itself would make the list unreadable for the nine times out of ten nobody needs it.',
    ],
    [
        'path' => 'HostingEnvelope.commands[].output',
        'because' => 'Where a hosted run writes what it would have said on a terminal. The same decision '
            . 'as `definition` and the same next step: a path to a log file is what an operator wants '
            . 'once, about one row, after they know which row. `N2-R10` already governs how this app '
            . 'reads a log and it reads them by service rather than by hosted command.',
    ],
    [
        'path' => 'HostingEnvelope.commands[].runs',
        'because' => 'The whole command line the definition runs, which is not the command as it is '
            . 'typed. They differ where the manager wraps it, and the wrapped form is what somebody '
            . 'debugging a launch agent needs — the same technical-detail decision as `definition`, and '
            . 'it becomes readable on the same day.',
    ],
    [
        'path' => 'ConfigEnvelope.review.findings',
        'because' => 'What a change comes to on this machine beyond the value it changes — the services it '
            . 'would stop, the library paths it would invalidate, the clients mid-transfer. Read by nothing '
            . 'yet, and it is the next thing this screen needs: a consequential change is agreed to on the '
            . 'strength of what it would disturb, and today the screen says the cost and not the extent. '
            . 'Named as one path rather than six because the whole branch is unread and a row per leaf would '
            . 'be six rows going green on the same day.',
    ],
    [
        'path' => 'ConfigEnvelope.review.proof',
        'because' => 'What proving a replacement credential against its live service came to. Present for '
            . 'exactly those settings and absent everywhere else — and this app does not offer to change a '
            . 'credential at all, so there is no path through this surface that could produce one. It '
            . 'becomes readable the day that changes, and not before.',
    ],
    [
        'path' => 'ConfigEnvelope.changed',
        'because' => 'Whether the proposal in `review` was written. Nothing here proposes one, so this is '
            . 'always false on every answer this app asks for, and a screen reading it would be reporting on '
            . 'an act it did not perform.',
    ],
    [
        'path' => 'ConfigEnvelope.rehearsed',
        'because' => 'Whether the proposal in `review` was a rehearsal rather than a write. The same '
            . 'reason as `changed`: this app never asks for one.',
    ],
    [
        'path' => 'ConfigEnvelope.consequence',
        'because' => 'What applying the proposal in `review` would mean, in the core\'s words. It belongs '
            . 'to the confirmation that screen will show before a consequential change, and there is no '
            . 'confirmation here because there is no change.',
    ],
    [
        'path' => 'DoctorEnvelope.findings[].said',
        'because' => 'A summary line beside the meaning. `N2-R3` has a finding carry its code, its meaning '
            . 'and its remedy, and a second sentence saying roughly the meaning again is the core being '
            . 'chatty rather than a fact a screen is short of.',
    ],
    [
        'path' => 'DoctorEnvelope.findings[].verdict.note',
        'because' => 'A note on a check that passed. `N2-R3` is about what a finding must carry when '
            . 'something is wrong, and nothing is.',
    ],
    [
        'path' => 'DoctorEnvelope.findings[].verdict.cause',
        'because' => 'The whole error again, nested under the finding it explains. The app attributes a '
            . 'finding to what caused it from `caused_by`, which names the check — a name a screen can show '
            . 'two rows up. Reading the nested copy would give one finding two accounts of itself that can '
            . 'disagree, and `G4-R3` asks for the cause reported rather than each symptom, not for both.',
    ],
    [
        'path' => 'DoctorEnvelope.findings[].verdict.summary',
        'because' => 'What the check found, in a sentence, before the meaning explains it. A screen shows '
            . 'the code, the meaning, the remedies and what the core said underneath, which is `N2-R3` and '
            . '`G4-R4` together; a fifth line saying the meaning shorter is the one an operator skips.',
    ],
    [
        'path' => 'DoctorEnvelope.findings[].verdict.remedies[].detail',
        'because' => 'A remedy is an action an operator takes. `N2-R3` has it carried in the words the core '
            . 'produced, and the action is those words — a second line under each one turns a list of things '
            . 'to try into a page to read.',
    ],
    [
        'path' => 'DoctorEnvelope.findings[].verdict.remedy.detail',
        'because' => 'The same field on the single remedy an unverified verdict offers, and the same answer.',
    ],
    [
        'path' => 'ErrorEnvelope.remedies[].detail',
        'because' => 'The same again on a refusal. An error body reaches the operator as an obstacle, which '
            . 'is one of four sentences this app has written and not a page of the core\'s.',
    ],
    [
        'path' => 'HouseholdEnvelope.findings',
        'because' => 'Plain sentences about the listing itself, beside the members. `N2-R3` has a finding '
            . 'carry a code, a meaning and a remedy, and those arrive on the `doctor` envelope; a bare '
            . 'string here has none of the three and is not something an operator can act on.',
    ],
    [
        'path' => 'HouseholdEnvelope.members[].access',
        'because' => 'What one member is allowed — administrator, disabled, which libraries, which ratings. '
            . 'Nothing reads it because nothing may act on it: what a member can do is the core\'s answer, '
            . 'and a surface holding the entitlement is a surface that could hide a control on its own '
            . 'reading of it, which `N3-R3` refuses. The app asks and the core refuses, which is why this '
            . 'stays unread even though the member\'s own reading is now narrowed to them. '
            . '`app-modules/household/src/README.md` holds the rest.',
    ],
    [
        'path' => 'HouseholdEnvelope.members[].asking',
        'because' => 'What one member has left of an allowance and when it comes back, in parts: a policy, '
            . 'a standing, two counts, an instant. `N3-R4` and `N3-R5` have this told to the member before '
            . 'they ask and it is — off `to_hand_over`, which carries the same facts as sentences the core '
            . 'wrote. Reading the parts as well would be a surface assembling its own wording for *within a '
            . 'limit*, which is a permission model with a template around it. The operator\'s reading of '
            . 'this payload (`N2-R11`) is about requests awaiting a decision rather than somebody\'s quota.',
    ],
    [
        'path' => 'HouseholdEnvelope.members[].claimed',
        'because' => 'Whether a member has taken up their invitation. The same block: it is a fact about a '
            . 'person rather than about a request, and `N2-R11` surfaces the requests.',
    ],
    [
        'path' => 'HouseholdEnvelope.members[].last_seen',
        'because' => 'When a member was last about. The same block, and the same distinction — `N2-R13` has '
            . 'a *reading* carry its age, which is how old this app\'s answer is rather than how long ago '
            . 'somebody opened a client.',
    ],
    [
        'path' => 'HouseholdEnvelope.members[].requests[].media',
        'because' => 'The library\'s own handle for the thing asked for. `N2-R11` asks for enough to decide '
            . 'on, and the words a person recognises are the title beside it. Nothing here reaches the thing '
            . 'itself: this app plays nothing today, so there is no door an identifier would open. `N3-R8` '
            . 'once said it never would and has been withdrawn — when the player arrives this row is one to '
            . 'read again, because a thing to play is exactly what a handle is for.',
    ],
    [
        'path' => 'HouseholdEnvelope.members[].requests[].waiting_days',
        'because' => 'How long a request has waited. Genuinely operator-facing — a fortnight is a different '
            . 'decision from an hour — and no requirement in `N1` to `N4` asks for it. Raise it against the '
            . 'spec before reading it, which is `N1-R17`.',
    ],
    [
        'path' => 'HouseholdEnvelope.members[].requests[].refused.expired',
        'because' => 'Whether a decline has lapsed. `N3-R7` has a refused request carry the reason it was '
            . 'given, which is what this app reads off `reason` and `at`; whether the refusal still stands '
            . 'is the stack\'s answer to somebody asking again, and nothing here asks again.',
    ],
    [
        'path' => 'HouseholdEnvelope.members[].requests[].refused.told',
        'because' => 'That the member was told, and who. `D7-R7` has a decline reach them by name and the '
            . 'stack is what reaches them; a screen here reporting that a notification was sent would be '
            . 'this app describing a message it neither sent nor can see.',
    ],
    [
        'path' => 'JobEnvelope.action',
        'because' => 'Which action the stack acknowledged. The caller already knows — it is the one that '
            . 'just asked — so reading it to check would be this app telling a stack what it had been asked, '
            . 'and reading it to decide would put a second answer beside the one the call site is holding.',
    ],
    [
        'path' => 'StatusEnvelope.disturbs.stopping_after_downloads',
        'because' => 'What stopping after the downloads finish would take away. `N2-R7` offers start, stop '
            . 'and restart, so this app has no verb this bound belongs to — and `N2-R8` states the bound on '
            . 'a disruptive action the operator is about to confirm, not on every verb the stack has.',
    ],
    [
        'path' => 'StatusEnvelope.disturbs.switching',
        'because' => 'The same, for switching. A verb this surface does not offer.',
    ],
    [
        'path' => 'StatusEnvelope.services[].describes',
        'because' => 'What a service is for, in the stack\'s words. Genuinely operator-facing — somebody '
            . 'reading a list of nineteen names would be better off knowing which is the one that fetches '
            . 'series — and no requirement in `N1` to `N4` asks for it. Raise it against the spec before '
            . 'reading it, which is `N1-R17`.',
    ],
    [
        'path' => 'UpdateEnvelope.applied[].detail',
        'because' => 'What went wrong for one service, in the core\'s words. `N2-R18` has the app report '
            . 'how the update ended for each service and tell the four endings apart, which the row does. '
            . 'A detail line under each is `G4-R4`\'s shape and `G4-R4` is about an error; this is an '
            . 'outcome. Worth raising rather than assuming.',
    ],
    [
        'path' => 'UpdateEnvelope.applied[].from',
        'because' => 'The version a service came off. `N2-R18` asks what became of it, not what it was — '
            . 'and the app already says what the stack is on now.',
    ],
    [
        'path' => 'UpdateEnvelope.applied[].to',
        'because' => 'The version it was going to, and the same answer.',
    ],
    [
        'path' => 'UpdateEnvelope.changelog.releases[].patches',
        'because' => 'The same, for what a release fixes.',
    ],
    [
        'path' => 'UpdateEnvelope.changelog.releases[].released_on',
        'because' => 'When a release was published. `N2-R13` has a reading carry its age wherever it is '
            . 'shown, which is about how old the *app\'s* answer is rather than how old a release is.',
    ],
    [
        'path' => 'UpdateEnvelope.changelog.requirements',
        'because' => 'Which requirement each release shipped, with a link. That is the spec talking about '
            . 'itself, and an operator deciding whether tonight is the night is not reading requirement '
            . 'identifiers.',
    ],
    [
        'path' => 'UpdateEnvelope.changelog.running.carried',
        'because' => 'What the release in use brought forward from the one before it. The app reads a '
            . 'release\'s version and whether it was taken back, which is what `N2-R15` and `N2-R16` ask '
            . 'of it; the rest of the entry is a changelog screen nobody has asked for.',
    ],
    [
        'path' => 'UpdateEnvelope.changelog.running.patches',
        'because' => 'The same, for what the release in use fixes.',
    ],
    [
        'path' => 'UpdateEnvelope.changelog.running.released_on',
        'because' => 'The same, for when it was published.',
    ],
    [
        'path' => 'UpdateEnvelope.changelog.running.tag',
        'because' => 'The name the release was published under, beside the version it is. Two names for one '
            . 'release on one screen is the shape an operator reads as two releases, and `N2-R15` asks for '
            . 'the one the stack reports itself as being on.',
    ],
    [
        'path' => 'UpdateEnvelope.changelog.running.groups',
        'because' => 'The release notes for the version in use, grouped and entry by entry, and everything '
            . 'under them. `N2-R16` has the stack answer whether the household will notice, which it does '
            . 'in a flag beside this; rendering the notes is the changelog screen no requirement asks for.',
    ],
    [
        'path' => 'UpdateEnvelope.changes[].because',
        'because' => 'Why the stack would make this change. `N2-R17` has the confirmation name the services '
            . 'an update would change, and a reason per service is a paragraph where a list belongs.',
    ],
    [
        'path' => 'UpdateEnvelope.changes[].current',
        'because' => 'The version a service is on. Said once for the stack (`N2-R15`) rather than per '
            . 'service, because a confirmation is about the evening rather than about nineteen numbers.',
    ],
    [
        'path' => 'UpdateEnvelope.changes[].target',
        'because' => 'The version it would go to, and the same answer.',
    ],
    [
        'path' => 'UpdateEnvelope.changes[].jump',
        'because' => 'How large the version jump is — major, minor, patch, or untellable. `N2-R16` already '
            . 'has the stack answer the question an operator is actually asking, which is whether the '
            . 'household will notice; a semantic-version magnitude is a different claim and a weaker one.',
    ],
    [
        'path' => 'UpdateEnvelope.stack_edits',
        'because' => 'The diffs an update would make to the stack\'s own configuration, and the paths they '
            . 'touch. `N2-R12` refuses to let this app set or change a value; showing a diff of one is the '
            . 'near neighbour of that and wants a requirement of its own before it wants a screen.',
    ],
    [
        'path' => 'UpdateEnvelope.state',
        'because' => 'How the last run of an update ended, at the top of the payload. It is the field this '
            . 'whole rule is about: `N2-R15` is answered from `changelog.state`, the two share half a '
            . 'vocabulary, and reading this one for that question is the mistake that would have refused '
            . 'every stack with an update waiting. What became of a run is `N2-R18`, which the app answers '
            . 'service by service off `applied` — a summary beside it is a second answer to one question.',
    ],
    [
        'path' => 'RepairEnvelope.beyond',
        'because' => 'Remedies for checks this repair did not attempt. `N2-R4` offers the repair the core '
            . 'offers for the finding in hand; a list of other checks belongs to a screen about the whole '
            . 'run, and no requirement asks for one.',
    ],
    [
        'path' => 'ErrorEnvelope.cause',
        'because' => 'What lay under a refusal, as whatever shape the check that raised it had. `N1-R10` '
            . 'has the app tell a refused credential from a stack that is not answering, and it decides '
            . 'that from the response rather than from a body — a stack that is asleep sends no envelope '
            . 'at all, so a reading that depended on one would work only where it was least needed.',
    ],
    [
        'path' => 'ErrorEnvelope.detail',
        'because' => 'The same reading, and the same answer. `N2-R3` has a finding carry its meaning in '
            . 'the words the core produced, and that arrives on the `doctor` envelope where a screen can '
            . 'show it; an error body reaches the operator as an obstacle, which is one of four sentences '
            . 'this app has written.',
    ],
    [
        'path' => 'HeldEnvelope.id',
        'because' => 'Which machine the shelf was read from, echoed back. This app asked a stack it '
            . 'already holds, over a connection pinned against that stack\'s fingerprint, so reading '
            . 'the answer\'s idea of which machine it is would be a second opinion about something '
            . 'already settled — and the only way the two could ever differ is a connection that went '
            . 'somewhere else, which the pin refuses before a body is read.',
    ],
    [
        'path' => 'HeldEnvelope.member',
        'because' => 'Whose shelf it is, echoed back. The app named the member in the request, so this '
            . 'is the same value returning; trusting the answer\'s copy over the one it sent would let a '
            . 'stack decide who is looking, which is the decision the signed-in identity makes. A screen '
            . 'showing a member their own name is not what a shelf is for.',
    ],
    [
        'path' => 'HouseholdEnvelope.allows',
        'because' => 'What the house\'s own policy allows in a period, said about the house. A member is '
            . 'told what applies to *them* in `to_hand_over`, written to them by the core and rendered '
            . 'unchanged, so a house-level sentence beside it would be a second statement of the same rule '
            . 'and able to disagree with the first the day one member is treated differently. The '
            . 'operator\'s reading of this payload is about requests awaiting a decision rather than about '
            . 'the house\'s defaults. `app-modules/household/src/README.md` holds the rest.',
    ],
    [
        'path' => 'HouseholdEnvelope.filtering',
        'because' => 'What the limits on this household are and are not. The same answer as `allows`: it '
            . 'is said about the house, and what a member reads is the core\'s sentences written to them.',
    ],
    [
        'path' => 'HouseholdEnvelope.policy',
        'because' => 'What happens to what the household asks for where nobody chose otherwise for one '
            . 'person. The house\'s default, and a member is owed what applies to them rather than what '
            . 'applies by default — which `to_hand_over` already says to them in the core\'s own words.',
    ],
    [
        'path' => 'RepairEnvelope.acted',
        'because' => 'What became of a repair is read from its outcome, which says what happened rather '
            . 'than whether anything did. A boolean beside it is a second answer to one question, and '
            . '`N2-R4` has the app state what a repair did in the words the core produced.',
    ],
    [
        'path' => 'StatusEnvelope.unsupported',
        'because' => 'What this stack cannot do, and why. It is the stack describing its own limits '
            . 'rather than its condition, and `N2-R1` opens this app on a verdict. A screen mixing the '
            . 'two would report a limitation as something wrong.',
    ],
    [
        'path' => 'UpdateEnvelope.backup',
        'because' => 'The snapshot taken before a run. `N2-R19` has the app say which way back the stack '
            . 'named, and the stack performs it — naming the snapshot would be this app describing a file '
            . 'it cannot reach. `app-modules/backups/src/README.md` holds the rest of this answer.',
    ],
    [
        'path' => 'UpdateEnvelope.confirmed',
        'because' => 'Whether the stack considers an update confirmed. `N2-R17` is about the operator '
            . 'confirming it *here*, before it runs, and reading the stack\'s own flag would let a '
            . 'confirmation somewhere else stand in for the one this app asked for.',
    ],
    [
        'path' => 'UpdateEnvelope.halted',
        'because' => 'When an update was stopped part way. `N2-R18` reports what became of each service, '
            . 'which is what an operator acts on; *when it stopped* is a different screen and no '
            . 'requirement asks for one.',
    ],
    [
        'path' => 'UpdateEnvelope.in_flight',
        'because' => 'The services an update is being applied to right now. Taking one hands back a job '
            . '(`Underway`), and what a job is doing is the job\'s reading rather than this one\'s — two '
            . 'places saying what is in flight can disagree, and the operator would believe whichever '
            . 'they were looking at.',
    ],
];

/**
 * The envelopes this app actually reads.
 *
 * Read off the readers rather than listed, so an envelope somebody starts
 * reading is one this rule covers without an edit — which is the difference
 * between a register and a list that goes stale the first time the app grows.
 *
 * Kept beside `WhatTheReadersRead::envelopes()` rather than replaced by it, and
 * the two are held against each other below. This one finds an envelope by the
 * call that opens it and knows nothing about what happens next; that one finds
 * it by following a payload to a subscript. An envelope the first sees and the
 * second does not is a reader the following stopped partway through, which is
 * the one failure that would make every rule here quietly narrower.
 *
 * @return list<string>
 */
function everyEnvelopeThisAppReads(): array
{
    $named = [];

    foreach (Tree::filesUnder(Tree::at('app-modules/sdk/src'), '.php') as $file) {
        preg_match_all('/(\w+Envelope)::in\b/', (string) file_get_contents($file), $found);

        foreach ($found[1] as $envelope) {
            $named[] = $envelope;
        }
    }

    sort($named);

    return array_values(array_unique($named));
}

/**
 * Every path every envelope declares, as `Envelope.path`.
 *
 * Nested and not only top-level, because the fields a decision is most often
 * owed about are nested: `update.changelog.state` is two levels down, and a
 * register that read the top of a payload reported that envelope clean.
 *
 * @return list<string>
 */
function everyPathTheContractDeclares(): array
{
    $declared = [];

    foreach (WhatTheReadersRead::envelopes() as $envelope) {
        foreach (WhatTheContractDeclares::everyPathIn(WhatTheContractDeclares::shapeOf($envelope)) as $path) {
            $declared[] = sprintf('%s.%s', $envelope, $path);
        }
    }

    return $declared;
}

/**
 * Whether a row's path covers this one.
 *
 * A row covers its own path and everything beneath it, because that is what a
 * decision not to read something means: `changelog.running.groups` is one
 * decision about the release notes and the eight paths inside them, and eight
 * rows saying so would be one decision written out eight times.
 *
 * It is not a way to excuse a payload wholesale, and the rule below prints what
 * each row covers so that a row which has quietly grown over something somebody
 * should have read is visible at a glance rather than worked out.
 */
function thePathIsCoveredBy(string $path, string $row): bool
{
    // Both ways into a subtree: a field (`.`) and an entry (`[]`). A check that
    // knew one of them would cover a subtree everywhere except through the
    // other, which is the shape of gap this whole file exists to refuse.
    return $path === $row
        || str_starts_with($path, sprintf('%s.', $row))
        || str_starts_with($path, sprintf('%s[', $row));
}

/**
 * What a path hangs off, which is where a decision about it belongs.
 *
 * A field under something nothing reads is not a decision of its own: the app
 * does not read `update.changelog.running.groups`, so whether it reads the
 * `title` of each group is not a question anybody has to answer. Reporting the
 * frontier — the first path down each branch that nothing reads — is what keeps
 * this register a list of decisions instead of a transcription of the contract.
 *
 * The entry marker comes off with the last segment, because a reader steps into
 * a list and then reads a field of it: `changelog.releases[].version` hangs off
 * `changelog.releases`, which is the subscript that found the list.
 */
function theHolderOf(string $path): string
{
    $at = strrpos($path, '.');

    return $at === false ? $path : rtrim(substr($path, 0, $at), '[]');
}

/** The envelope a path is on, which is the payload every path here hangs off. */
function theEnvelopeIn(string $path): string
{
    $at = strpos($path, '.');

    return $at === false ? $path : substr($path, 0, $at);
}

it('N1-R17 — every path on an envelope this app reads has been decided about', function (): void {
    // The half that matters. A field lemonfiber adds is a field somebody has to
    // weigh, and this is what makes that happen on the day it arrives rather
    // than on the day a screen turns out to be missing something.
    $read = WhatTheReadersRead::paths();
    $declared = everyPathTheContractDeclares();
    $undecided = [];

    foreach ($declared as $path) {
        if (in_array($path, $read, strict: true)) {
            continue;
        }

        foreach (WHAT_THIS_APP_DOES_NOT_READ as $row) {
            if (thePathIsCoveredBy($path, $row['path'])) {
                continue 2;
            }
        }

        // The holder of a top-level field is the envelope, whose payload is
        // read by definition — which is what makes every top-level field a
        // decision and leaves the deeper ones to the branch they hang off.
        $holder = theHolderOf($path);

        if ($holder !== theEnvelopeIn($path) && ! in_array($holder, $read, strict: true)) {
            continue;
        }

        $undecided[] = $path;
    }

    // A rule that has stopped finding the envelopes reports no violations, and
    // no violations is exactly what compliance looks like. Both halves are
    // asserted: a contract that declared nothing, and a following that read
    // nothing, are two ways to the same green tick.
    expect(count($declared))->toBeGreaterThan(1, 'no path was read off the contract, so this rule read nothing');
    expect(count($read))->toBeGreaterThan(1, 'no path was read off a reader, so this rule read nothing');

    expect($undecided)->toBe([], sprintf(
        "The contract carries these and this app has not said anything about them:\n  %s\n\n"
        . 'Read the path — give it a `WireField` case and a reader — or add a row to '
        . "`WHAT_THIS_APP_DOES_NOT_READ` saying why not. A field wants a requirement before it wants a "
        . "screen (`N1-R17`), so *no requirement asks for this* is a complete answer and an unweighed "
        . "field is not.\n",
        implode("\n  ", $undecided),
    ));
});

it('N1-R17 — the reading follows a reader rather than recognising a name', function (): void {
    // What the rule above rests on, asserted on the pair that proves it. Both
    // of these are called `state`, one is read and one is not, and a reading
    // that answered from `WireField` would call both of them read — which is
    // how the register came to say nothing about the exact field
    // `WhatTheContractAccepts` was written because a reader misread.
    $read = WhatTheReadersRead::paths();

    expect($read)->toContain('UpdateEnvelope.changelog.state')
        ->and($read)->not->toContain('UpdateEnvelope.state');

    // And a second pair one level further in, where both paths are nested and
    // the app reads three of the five verbs the stack describes. A reading that
    // had quietly stopped following calls would answer `false` to both of
    // these, which the first expectation alone would not notice.
    expect($read)->toContain('StatusEnvelope.disturbs.starting.bound')
        ->and($read)->not->toContain('StatusEnvelope.disturbs.switching.bound');
});

it('N1-R17 — every reach into a payload is one the reading placed', function (): void {
    // The failure this rule cannot survive quietly. A subscript the following
    // cannot seat reads a field the register is then told nothing reads, and
    // the answer to that is a row explaining why a field that *is* read is not
    // — a false decision, written down, that outlives everybody who could have
    // spotted it.
    //
    // So an unplaceable reach is fatal rather than silently unread. It names
    // the reader and the line, because what it means is that a reader here is
    // written in a shape the following does not model, and the fix is in one of
    // the two of them.
    expect(WhatTheReadersRead::unseated())->toBe([], sprintf(
        "These reach for a field off something this reading could not place:\n  %s\n\n"
        . 'Every one of them reads a field that the register above will be told nothing reads. Either '
        . 'the reader seats its payload somewhere `WhatTheReadersRead` does not follow — a call it '
        . "cannot resolve, a value it cannot type — or the following is short of a shape.\n",
        implode("\n  ", WhatTheReadersRead::unseated()),
    ));
});

it('N1-R17 — every envelope a reader opens is one the reading was seated on', function (): void {
    // The other half of the same guarantee, and the one that catches a whole
    // reader dropping out rather than one line of it. An envelope opened by
    // `XEnvelope::in` and missing from the following is an envelope whose every
    // field would read as unread, and the register's answer to that is fifty
    // rows nobody should ever have written.
    $seated = WhatTheReadersRead::envelopes();
    $opened = everyEnvelopeThisAppReads();
    $lost = [];

    foreach ($opened as $envelope) {
        if (! in_array($envelope, $seated, strict: true)) {
            $lost[] = $envelope;
        }
    }

    expect($opened)->not->toBe([], 'no envelope was found being opened, so this rule read nothing');

    expect($lost)->toBe([], sprintf(
        "A reader opens these and the reading never seated a payload on them:\n  %s\n",
        implode("\n  ", $lost),
    ));
});

it('N1-R17 — every row still names a path the contract has', function (): void {
    // The half that keeps the register honest about the present. A field
    // renamed or removed leaves a row explaining a decision about something
    // that is not there, and the rule above would then be excusing a field
    // nobody can find.
    $declared = everyPathTheContractDeclares();
    $gone = [];

    foreach (WHAT_THIS_APP_DOES_NOT_READ as $row) {
        if (! in_array($row['path'], $declared, strict: true)) {
            $gone[] = sprintf('%s is not a path the contract has', $row['path']);
        }
    }

    expect($gone)->toBe([], sprintf(
        "These rows describe a decision about something the contract no longer has:\n  %s\n\n"
        . "Delete the row. A register listing fields that are gone is one nobody trusts about the ones that are not.\n",
        implode("\n  ", $gone),
    ));
});

it('N1-R17 — every row names a path nothing reads', function (): void {
    // The direction a name-based reading could not ask about at all, and the
    // one that had two rows wrong: a row saying nothing under `members` is read
    // while the app reads a member's name and every request under them, and a
    // row saying the entry for the release in use is not read while being up to date is
    // answered off it.
    //
    // A row like that is worse than a missing one. It reads as a decision
    // somebody took, it is false, and the next reader weighing whether to build
    // a screen believes it.
    $read = WhatTheReadersRead::paths();
    $overtaken = [];

    foreach (WHAT_THIS_APP_DOES_NOT_READ as $row) {
        if (in_array($row['path'], $read, strict: true)) {
            $overtaken[] = $row['path'];
        }
    }

    expect($overtaken)->toBe([], sprintf(
        "These rows say a path is not read and something reads it:\n  %s\n\n"
        . 'Delete the row, or move it down to the paths beneath that are genuinely unread. A row '
        . "covering a subtree it sits at the top of excuses the fields under it on a reason that is not true.\n",
        implode("\n  ", $overtaken),
    ));
});

it('N1-R17 — every row says why, and says how much it covers', function (): void {
    // The reason is the only part that survives the person who wrote it, and a
    // row without one is a row the next reader has to re-decide from scratch.
    //
    // The count is printed rather than capped. A row covering a subtree is
    // sometimes exactly right — the release notes are one decision about nine
    // paths — and sometimes a row that has quietly grown over something
    // somebody should have read. A number a reader can see tells those apart;
    // a cap would refuse the first along with the second.
    $declared = everyPathTheContractDeclares();
    $thin = [];
    $covers = [];

    foreach (WHAT_THIS_APP_DOES_NOT_READ as $row) {
        if (trim($row['because']) === '') {
            $thin[] = sprintf('%s has no reason', $row['path']);
        }

        $under = 0;

        foreach ($declared as $path) {
            if (thePathIsCoveredBy($path, $row['path'])) {
                $under++;
            }
        }

        $covers[] = sprintf('%s covers %d', $row['path'], $under);
    }

    expect($thin)->toBe([], sprintf("These rows do not say enough to act on:\n  %s\n", implode("\n  ", $thin)))
        ->and($covers)->not->toBe([], 'no row was read, so this rule read nothing');
});
