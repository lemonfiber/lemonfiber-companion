<?php

declare(strict_types=1);

use Tests\Support\Tree;

// W8 — the requirements this app cannot answer, and the field each waits on.
//
// Where the contract does not carry something a requirement
// asks the app to state, the app must not substitute a value of its own: the
// gap is raised against the contract and the requirement is answered there.
// `NoSubstitutedWireValueRule` enforces the first half in the readers. This is
// the second half, and it is a different kind of check — not *did somebody
// invent a value* but *is this still missing*.
//
// **Both halves carry `W8`, and that is not duplication.** The register joins a
// row to its mechanism by the identifier, per kind: the row claims `phpstan`
// and `arch`, and a row claiming two kinds with only one artifact spelling the
// identifier passes on whichever one spells it while the other clause rides
// free. Carrying it here is what makes the `arch` half of that row answerable —
// and `TheRulesAreRealTest` goes red naming the kind if either artifact stops
// carrying it.
//
// A list of gaps kept in prose would be a list nobody reads twice. What makes
// this one worth having is that every entry is checked against the generated
// types, in the direction that matters: the day lemonfiber carries the field,
// this suite goes **red** and says which requirement to go and answer. A gap
// register that only recorded gaps would quietly outlive them, and the
// requirement would stay unanswered with a note explaining why that was once
// reasonable.
//
// An entry is removed by answering its requirement, never by deleting the row.
//
// **Name an envelope only where the field could not land anywhere else.** A row
// that names one is searched in that one alone, so a guess about *where* an
// answer will arrive becomes a condition for noticing that it has. The bound's
// row below named `LifecycleEnvelope` on good reasoning — that is where what an
// operation touched already arrives — and the bound landed on the status
// reading instead, because it is worth having before the verb runs rather than
// after. Had the row kept the name, the gap would have closed and this suite
// would have gone on passing, which is the one failure a register cannot
// survive: it would have been a note explaining why a requirement was
// reasonably unanswered, attached to a requirement that was answerable.
//
// Leaving the envelope null costs a wider search and the odd false positive.
// A false positive sends somebody to read a row; a false negative is the
// register quietly becoming decoration.

/**
 * Every requirement this app is holding, and what it is waiting for.
 *
 * A row watches one of two things. `field` is a name that is missing from a
 * payload and would be added to it. `shape` is a whole payload that says
 * nothing about the subject at all, recorded as it stands — there is no field
 * name to watch for, because what is missing is not a field.
 *
 * Read through {@see everyGapThisAppIsHolding()} rather than directly, which is
 * what keeps the rules below able to see anything. With one row left the
 * analyser reads every optional field as the value that row happens to carry,
 * and the rule's own guards become constant comparisons it refuses — the
 * register turning itself off at the moment it has almost nothing left to
 * watch, which is a gate that works until it nearly succeeds.
 */
const WHAT_THE_CONTRACT_DOES_NOT_CARRY = [
    [
        'requirement' => 'N3-R15',
        'asks' => 'to decline playback with the reason where the media server cannot be reached',
        // Unnamed on purpose, and this is the row the register's own warning
        // was written about. A location could land on `held` beside the
        // holding it is about, or on a read of its own asked for one holding
        // at a time — the second is the likelier shape for something that
        // expires, and naming the first would be a guess about *where* an
        // answer arrives becoming a condition for noticing that it has.
        'envelope' => null,
        // `stream` and `source` are both spent on this wire already — one on a
        // log's two streams and one elsewhere — so a row watching either would
        // fire today and go on firing. `stream_from` is free, and it is the
        // plainest name for the thing that is missing: where this holding can
        // be streamed from, said by whoever knows.
        //
        // A field that closes this gap under another name is a row to update
        // rather than a silence to live with. That is the cost of naming one
        // at all, and it is smaller than the cost of naming none: a row with
        // no field is a row nothing can ever fire on.
        'field' => 'stream_from',
        'shape' => null,
        'raised' => 'The shelf is read and nothing can be played from it. `HeldEnvelope.holdings[]` '
            . 'carries `id`, `medium`, `title` and `year`, and nothing that turns an identifier into '
            . 'something a player can open. The app must not compose one: an address built here out '
            . 'of a stack\'s address and a holding\'s id is a second copy of how the library works, '
            . 'which is the one thing `N3-R14` says a player may not hold, and it is server-specific '
            . 'besides — the path a Jellyfin library serves is not the path a Plex one does, so the '
            . 'app would be deciding which media server the household runs. It is also the assumption '
            . 'that breaks first: the route to a library is a local wire today and will not always be '
            . 'one, and an app that built the address is an app that built the wrong one the day it '
            . 'is reached from somewhere else. So the location belongs to whoever already knows both '
            . 'the library and the route, which is the core. A location is half of it. The other half '
            . 'is the member\'s authorisation to stream that holding: the media server applies an '
            . 'account\'s age limit and library access to whoever the stream is authorised as, so a '
            . 'stream opened with the stack\'s own credential is one no limit applies to, and this app '
            . 'holds no credential of the member\'s for the media server at all. What closes this row '
            . 'is a location per holding and the member\'s authorisation for it, issued by the core '
            . 'for the member who asked and saying when it stops standing — one address the core '
            . 'signed, or an address and a grant beside it. Until both are carried, `N3-R15` cannot be '
            . 'answered at all — playback cannot be declined for a reason by an app that has no way '
            . 'to attempt it — and `N3-R16` is answered only by there being no player to implement '
            . 'anything in. `NothingPlaysMediaHereTest` refuses every player for as long as this row '
            . 'stands, and is the rule to replace with ones holding the player to `N3-R14` and '
            . '`N3-R16` when it goes. `HouseholdEnvelope.members[].requests[].media` is the other '
            . 'handle on this wire, and its row in `WhatTheContractCarriesThatNothingReadsTest` is '
            . 'read beside this one.',
    ],
    [
        'requirement' => 'N9-R7',
        'asks' => 'to tell an invitation that lapsed unaccepted from one the invitee declined',
        // Named, and watched as a whole payload: the distinction is two words
        // the invitation's `standing` does not have, `lapsed` and `declined`,
        // and either arriving changes the shape recorded here. A field row
        // could watch only one of them, and `declined` is spent on this wire
        // already, on a household request and on a repair.
        'envelope' => 'InvitationEnvelope',
        'field' => null,
        'shape' => 'array{address: string, applied?: array{filtering: string, libraries: list<string>, limit?: string|null, requesting: \'made\'|\'not-yet\'|\'not-tried\', unrated: \'held-back\'|\'let-through\'}|null, caution?: string|null, hours: int, linked: \'made\'|\'not-yet\'|\'not-tried\', name: string, rehearsed: bool, standing: \'made\'|\'waiting\'|\'joined\'|\'reset\', suspended: list<string>, withdrawn: list<string>}',
        'raised' => 'An invitation\'s `standing` is `made`, `waiting`, `joined` or `reset`, and none of those '
            . 'says that it ran out unaccepted or that the person turned it down. `waiting` past its '
            . '`hours` is the nearest, and reading it as lapsed would be this app working out a state '
            . 'the core has not stated, which `N2-R14` refuses. The invitation screen draws the standing '
            . 'the stack gives and the hours it stands, and invitations taken back on the way past with '
            . 'the answer they arrived on, and says nothing of a decline, which the contract does not carry.',
    ],
    [
        'requirement' => 'N13-R7',
        'asks' => 'to carry, on taking somebody out of the household, an agreement naming the reading it was given for',
        // Named, because the reading an agreement would name is this payload
        // and nowhere else. `agreement` is the word the uninstall's reading
        // already names itself by, and a person's removal gaining one would
        // most plausibly use it too.
        'envelope' => 'RemovalEnvelope',
        'field' => 'agreement',
        'shape' => null,
        'raised' => 'The `remove` action takes a name and a bare `confirm`, and the `removal` envelope names '
            . 'nothing a yes could quote. So nothing on the wire can say which reading of what taking '
            . 'somebody out costs was agreed to, or tell one agreement from another: a yes sent after the '
            . 'cost moved is carried out against whatever it costs by then. The screen asks every time, '
            . 'holds the reading only while it is in front of the operator, and sends the yes only beneath '
            . 'it, which is as much of `N13-R7` as a bare `confirm` allows. What closes this row is the '
            . 'reading naming itself and the action taking that name back, as an uninstall\'s does.',
    ],
    [
        'requirement' => 'N13-R10',
        'asks' => 'to label a rehearsed removal of somebody from the household as a rehearsal',
        // Named, and watched for the word the invitation already marks a
        // rehearsal with: a person's removal able to be rehearsed would have to
        // say so on this payload, and `confirmed: false` cannot.
        'envelope' => 'RemovalEnvelope',
        'field' => 'rehearsed',
        'shape' => null,
        'raised' => 'No action argument on the HTTP route asks for a rehearsal, so none can arrive, and the '
            . '`removal` envelope carries no marker of one: `confirmed: false` is a reading nobody agreed '
            . 'to and a rehearsal alike. The screen labels that reading as what taking them out would cost, '
            . 'with nobody taken out, and never as a rehearsal, because nothing on the wire says one ran. '
            . 'What closes this row is a rehearsal the route can be asked for and the payload saying it was one.',
    ],
    [
        'requirement' => 'N13-R19',
        'asks' => 'to state what taking somebody out of the household does to their watch history',
        // Named, and watched as a whole payload: the effect could arrive as a
        // sentence, a flag or a count, under any name, and the payload saying
        // anything it does not say now is what closes this.
        'envelope' => 'RemovalEnvelope',
        'field' => null,
        'shape' => 'array{\'asks-through-the-request-service\': bool, confirmed: bool, findings: list<string>, name: string, requests: int, revoked: \'everywhere\'|\'media-server-only\'|\'nothing\'}',
        'raised' => '`D6-R9` has the effect on watch history stated before a removal is confirmed. The `removal` '
            . 'envelope carries what could not be done in `findings` and how many requests are destroyed '
            . 'in `requests`, and nothing that says the watch history goes with the account. A sentence '
            . 'written here would be this app asserting what the media server does with an account it '
            . 'removes, which `N2-R14` refuses. The screen states the requests as the stack counts them and '
            . 'every finding in its words, and says nothing about watch history.',
    ],
    [
        'requirement' => 'N13-R10',
        'asks' => 'to ask for a rehearsal of taking lemonfiber off, and label what it answers as one',
        // Named, and watched as a whole payload. What is missing is an
        // argument on the action's route, which no payload can show; the
        // payload a rehearsal answers with is where a route that can be asked
        // for one would say so, and anything it says that it does not say now
        // sends somebody to read this row.
        'envelope' => 'UninstallEnvelope',
        'field' => null,
        'shape' => 'array{manifest: array{agreement: string, backup?: string|null, bytes: int, coming: list<array{name: string, progress: int}>, confidence: array{complete: bool, unread: list<string>}, foreign: list<array{at: string, bytes: int, files: int}>, items: list<array{bytes?: int|null, kept?: string|null, name: string, secret: bool, sort: \'container\'|\'network\'|\'image\'|\'path\', what: string}>, keeps: string, outside: list<array{by_hand: string, found: bool, what: string, why: string}>, removes: string, tier: \'stop\'|\'services\'|\'configuration\'|\'media\', volume?: string|null}, removal: array{state: \'surveyed\'}|array{state: \'confirmed\'}|array{credentials: list<string>, gone: list<string>, state: \'complete\'}|array{credentials: list<string>, gone: list<string>, left: list<array{by_hand: string, name: string, why: string}>, state: \'partial\'}}',
        'raised' => 'The `removal` state `confirmed` is the state an uninstall rehearsal ends in, and the screen '
            . 'labels it as a rehearsal where it arrives. None can be asked for: the `uninstall` action '
            . 'takes `tier`, `confirm`, `offer` and `wait`, and no argument on the HTTP route asks for a '
            . 'rehearsal, so none arrives from this app. What closes this row is a rehearsal the route '
            . 'can be asked for.',
    ],
    [
        'requirement' => 'N7-R11',
        'asks' => 'to show where each service the survey found keeps its configuration and its library',
        // Named, and watched as a whole payload: the answer could arrive as a
        // field on a service or as a list beside the projects, and either
        // changes the shape recorded here.
        'envelope' => 'MigrationEnvelope',
        'field' => null,
        'shape' => 'array{beside: list<array{from: int, service: string, to: int}>, carrying: list<array{backup_first: bool, because: string, existing: string, ours: string, refused: bool, service: string, verdict: string}>, conflicts: list<array{held_by: string, port: int, wanted_by: string}>, linking?: array{because: string, cost: string, filesystems: list<string>, forced: bool, links: bool, remedy: string}|null, modes: list<array{disturbs: bool, mode: string, preselected: bool, what: string}>, not_carried: list<array{because: string, what: string}>, read: bool, standing: list<array{project: string, services: list<array{adoptable: bool, ports: list<int>, running: bool, service: string}>}>, unsupported: list<array{because: string, what: string}>}',
        'raised' => '`A5-R2` has the survey name each service\'s configuration source and library '
            . 'location, and a service here carries `service`, `ports`, `running` and `adoptable` and '
            . 'neither of those. The only place a library appears is the filesystems a layout that '
            . 'cannot link names, which is about a hardlink rather than a service. The app does not '
            . 'guess either from a service\'s name or its ports; the survey draws what it carries and '
            . 'says nothing about where a service keeps its settings.',
    ],
    [
        'requirement' => 'N12-R8',
        'asks' => 'to label a rehearsed cleanup of what costs nothing as a rehearsal, and never report it as room freed',
        // Named, because the answer can land nowhere else: whether a cleanup
        // was rehearsed is a fact about that cleanup, and `reclaimed` inside
        // the `space` answer is where the cleanup is reported. `rehearsed` is
        // the word the `stop-seeding` answer already uses for the same fact
        // about letting one download go, so it is the word to watch for here.
        'envelope' => 'SpaceEnvelope',
        'field' => 'rehearsed',
        'shape' => null,
        'raised' => 'A confirmed `space` action takes what the account named as costing nothing, and its '
            . 'answer reports that in `reclaimed` as the paths taken and the bytes they occupied. A stack '
            . 'running as a rehearsal takes nothing and fills in `reclaimed` exactly the same way, so an '
            . 'answer this app could read would say room was freed whether or not any was, which `N12-R8` '
            . 'forbids. The action also takes a bare `confirm` rather than the offer\'s name, so a yes '
            . 'cannot say which reading of the disk it was given for. This app therefore does not ask for '
            . 'the cleanup: stopping seeding, which the stack answers with `gone.rehearsed` and agrees to by '
            . 'the offer\'s own name, is the one removal it offers, one download at a time. What closes this '
            . 'row is `reclaimed` saying whether it was rehearsed.',
    ],
    [
        'requirement' => 'N24-R1',
        'asks' => 'to offer the presets a choice may be made from, and the kinds of media it may be made for, in the stack\'s plain terms',
        // Unnamed, because the list could land on the quality reading or on a
        // reading of its own. `presets` is free on this wire, and is the
        // plainest name for what is missing.
        'envelope' => null,
        'field' => 'presets',
        'shape' => null,
        'raised' => 'The `quality` envelope lists the choices in force and nothing else: which presets a '
            . 'choice may be made from, which formats music may take, and which kinds of media a preset '
            . 'may be set apart for are the core\'s, and none is carried. A list written here would be a '
            . 'second copy of the core\'s vocabulary that goes stale the day it changes, which `N2-R14` '
            . 'refuses. So the operator names a preset and a kind in the stack\'s words, as the choices '
            . 'in force name them, and the stack takes the name or refuses it. What closes this row is '
            . 'the offered presets, formats and kinds carried beside the choices in force.',
    ],
    [
        'requirement' => 'N24-R4',
        'asks' => 'to state what upgrading the library costs in total before it is confirmed',
        // Named, and watched as a whole payload: a total could arrive under any
        // name, and the payload saying anything it does not say now is what
        // closes this.
        'envelope' => 'UpgradeEnvelope',
        'field' => null,
        'shape' => 'array{confirmed: bool, media: list<array{media_type: string, outcome?: array{state: \'started\'}|array{state: \'not-started\'}|array{detail: string, state: \'failed\'}|null, preset: string, size_per_hour: string}>}',
        'raised' => 'An upgrade is described kind by kind with the preset in force and what an hour of it '
            . 'costs, and nothing else. `D2-R7` asks for its cost stated, and a rate per hour is not a '
            . 'cost: nothing says how many hours the library holds, and `N2-R14` forbids working one out. '
            . 'The screen draws each kind\'s rate as the stack words it and no sum.',
    ],
    [
        'requirement' => 'N24-R4',
        'asks' => 'to say, in the stack\'s words, that choosing a preset shapes what is fetched next and changes nothing already here',
        // Named, and watched as a whole payload, for the row above's reason.
        'envelope' => 'QualityEnvelope',
        'field' => null,
        'shape' => 'array{choices: list<array{means: string, needs_transcoding_here: bool, preset: string, resolution: string, scope: string, size_per_hour: string, transcoding: string}>, customised: bool, disposition: \'shown\'|\'recorded\'|\'rehearsed\'|\'held\'|\'reapplied\'|\'would-reapply\', music?: array{format: string, means: string, note: string, scope: string, size_per_hour: string, targets: string}|null, overwritten?: array{diff: string, path: string}|null}',
        'raised' => '`D2-R6` requires a changed preset to state that it affects future acquisitions only, and '
            . 'the `quality` envelope carries no such statement. A sentence written here would be this app '
            . 'asserting what the core does with a choice, which is the core\'s to say. The screen offers '
            . 'upgrading as its own act and says nothing about what choosing does to the library.',
    ],
    [
        'requirement' => 'N23-R11',
        'asks' => 'to say, before a guard starts, the data location it would guard, how often it looks and the command it would run',
        // Named, and watched as a whole payload: the name a guard is started
        // under is the one answer this app has before it runs, and the watch
        // could as well come to carry what it would guard there. A rehearsal
        // argument on the action is the other way it could close, and the SDK
        // generates no request shapes for a row to watch.
        'envelope' => 'JobEnvelope',
        'field' => null,
        'shape' => 'array{action: string, job: string}',
        'raised' => '`watch.would` carries the root, the interval and the command, and it is answered only '
            . 'by a rehearsal; no argument the watch action takes over the web API asks for one. So the '
            . 'app cannot read from the stack, before the guard runs, which location it would guard, how '
            . 'often it looks, or what it would run. It does not take the location from a setting or '
            . 'state an interval of its own: the screen says what a guard does in general, says that the '
            . 'stack does not say those three beforehand, and starts the guard only on a yes naming the '
            . 'forms it would stop.',
    ],
    [
        'requirement' => 'N23-R13',
        'asks' => 'to tell a guard another client released from one the stack let go because nothing asked about it',
        // Named, and watched as a whole payload, for the row above's reason:
        // both answer this envelope at `200`, and the distinction would arrive
        // as a word it does not carry now.
        'envelope' => 'JobEnvelope',
        'field' => null,
        'shape' => 'array{action: string, job: string}',
        'raised' => 'A guard released and a guard let go for want of asking both answer `job` at `200`, the '
            . 'standing `JobStanding` reads as ended. This app releases a guard only as its screen is left, '
            . 'so it never draws its own release; what it can draw is a guard that ended without seeing '
            . 'anything, told apart from one that saw the data location go, one that never started and one '
            . 'the stack no longer knows, and it says it was released or not asked about without saying '
            . 'which.',
    ],
    [
        'requirement' => 'N15-R1',
        'asks' => 'to show what setup settled — where the library lives, which protocols are on, who the stack runs as — as setup\'s own decisions',
        // Named, and watched as a whole payload: `wizard` is what setup's own
        // read answers with, and the settled facts arriving there is the
        // likeliest way this closes. A field row cannot watch them: `data_root`,
        // `protocols` and `service_user` are all carried today, by `setup`,
        // which nothing serves over HTTP.
        'envelope' => 'WizardEnvelope',
        'field' => null,
        'shape' => 'array{asks: bool, at: \'welcome\'|\'preflight\'|\'prerequisites\'|\'protocols\'|\'vpn\'|\'data-location\'|\'credentials\'|\'provider\'|\'service-user\'|\'library\'|\'household\'|\'notifications\'|\'autostart\'|\'review\', offered: bool, phase: \'in-progress\'|\'reviewing\'|\'applying\'|\'applied\', plan: list<array{key: string, origin: array{origin: \'bundled\'}|array{origin: \'operator\'}|array{named: string, origin: \'plugin\'}|array{origin: \'unknown\', why: string}|array{named: string, origin: \'overridden\', replaced: array{from: mixed, value?: string|null, withheld: bool}}|array{named: string, origin: \'orphaned\'}, secret: bool, value: string}>, proof?: array{observed: string, outcome: \'valid\'}|array{detail: string, outcome: \'rejected\'}|array{detail: string, outcome: \'unreachable\'}|array{detail: string, outcome: \'degraded\'}|null, ready_for_review: bool, unanswered: list<\'welcome\'|\'preflight\'|\'prerequisites\'|\'protocols\'|\'vpn\'|\'data-location\'|\'credentials\'|\'provider\'|\'service-user\'|\'library\'|\'household\'|\'notifications\'|\'autostart\'|\'review\'>, written: list<string>}',
        'raised' => 'What setup settled is carried whole only by `setup` — `data_root`, `protocols`, '
            . '`service_user` — and only the command line writes that kind; no read, action or endpoint '
            . 'answers with it. Setup\'s own read, `GET /api/setup`, answers with `wizard`, whose `plan` '
            . 'is what applying *will* write and is empty once setup has applied, and the SDK names no '
            . 'path for that read at all, so this app cannot ask it. The settings listing, `config`, '
            . 'carries what setup wrote among every other setting, each with its origin, and nothing '
            . 'on it says which ones setup settled. The settings screen says setup\'s decisions are '
            . 'among the settings it draws and that changing one is reconfiguration; it singles none '
            . 'out, because a list of setup\'s keys kept here would be a second copy of the core\'s '
            . 'plan that goes stale the day it changes, which `N2-R14` refuses.',
    ],
    [
        'requirement' => 'N15-R5',
        'asks' => 'to tie a stage said on the event stream to the job it is about',
        // Named, and watched as a whole payload: a job's name could arrive under
        // any word, and `job` is spent on this wire already, on the `job`
        // envelope itself.
        'envelope' => 'StepEnvelope',
        'field' => null,
        'shape' => 'array{detail: string, said: string, step: \'choosing\'|\'searching\'|\'grabbing\'|\'downloading\'|\'importing\'|\'scanning\'|\'available\'}',
        'raised' => 'A `step` carries the stage, what was said and the detail, and nothing naming the walk '
            . 'it belongs to. The screen following a walk takes every step heard while its walk runs '
            . 'as that walk\'s, which holds while one walk runs at a time; a second walk started from '
            . 'another surface meanwhile would be heard as this one. No other job carries a stage at '
            . 'all: the `job` envelope is `action` and `job`, so a repair, an update or a copy is shown '
            . 'as running and never as at a stage.',
    ],
];

/**
 * The register, as a row may be rather than as the rows presently are.
 *
 * The declared shape is the point. The rules below ask whether a row names an
 * envelope, a field or a payload shape, and every one of those questions is a
 * comparison against null that the analyser answers for itself the moment the
 * rows left all happen to agree.
 *
 * @return list<array{
 *     requirement: string,
 *     asks: string,
 *     envelope: string|null,
 *     field: string|null,
 *     shape: string|null,
 *     raised: string,
 * }>
 */
function everyGapThisAppIsHolding(): array
{
    return WHAT_THE_CONTRACT_DOES_NOT_CARRY;
}

/**
 * Every generated envelope, as the file it is written in.
 *
 * Read from the package rather than from a list here, so an envelope added to
 * the contract is one this rule sees without an edit.
 *
 * @return array<string, string>
 */
function everyGeneratedEnvelope(): array
{
    $found = [];

    foreach (Tree::filesUnder(Tree::at('vendor/lemonfiber/sdk-php/src/Generated'), '.php') as $path) {
        $found[basename($path, '.php')] = (string) file_get_contents($path);
    }

    return $found;
}

/**
 * The payload shape one envelope declares, as text.
 *
 * The `@phpstan-type Data` line and nothing else, so a field name that appears
 * in a docblock explaining the envelope is not read as a field it carries —
 * which is the mistake `K1` made about its own markers, in a different file.
 */
function thePayloadShapeOf(string $said): string
{
    preg_match('/@phpstan-type Data (.*)/', $said, $shape);

    return $shape[1] ?? '';
}

it('N2-R14 — every gap names an envelope the contract still has', function (): void {
    // The half that keeps the register honest about the present. An envelope
    // renamed in the contract would leave a row describing a type nobody
    // speaks, and the row below would then be checking nothing at all.
    $envelopes = everyGeneratedEnvelope();
    $gone = [];

    // The generated directory is the vendor's to move, and a move is what the
    // rule below cannot tell from an answer: a row watching a field searches an
    // empty list, finds no field there, and reports the gap as still open —
    // which is what it would report about a contract it had actually read. The
    // register itself has no floor and must not grow one: every gap closing is
    // the state this file is written to reach.
    expect($envelopes)->not->toBe([], 'the contract declares no envelope, so this rule read nothing');

    foreach (everyGapThisAppIsHolding() as $gap) {
        $named = $gap['envelope'];

        if ($named === null) {
            continue;
        }

        if (! array_key_exists($named, $envelopes)) {
            $gone[] = sprintf('%s waits on %s, which the contract no longer has', $gap['requirement'], $named);
        }
    }

    expect($gone)->toBe([], sprintf(
        "These wait on an envelope that is not in the contract any more:\n  %s\n\n"
        . "Find where the field went and answer the requirement, or name the envelope it moved to.\n",
        implode("\n  ", $gone),
    ));
});

it('N2-R14 — every gap is still a gap', function (): void {
    // The half that matters. This rule exists to stop being true: the day
    // lemonfiber carries one of these, the row here is what says so, and it
    // says so by failing rather than by being read.
    $envelopes = everyGeneratedEnvelope();
    $closed = [];

    expect($envelopes)->not->toBe([], 'the contract declares no envelope, so this rule read nothing');

    foreach (everyGapThisAppIsHolding() as $gap) {
        $named = $gap['envelope'];
        $shape = $gap['shape'];

        // A row watching a whole payload has nothing to search for: what would
        // close its gap is the payload saying anything it does not say now.
        if ($shape !== null) {
            // A shape row names its envelope by construction — the rule below
            // refuses a row that watches neither or both, and an unnamed one
            // would be watching nothing.
            $said = trim(thePayloadShapeOf($named === null ? '' : $envelopes[$named] ?? ''));

            if ($said !== $shape) {
                $closed[] = sprintf(
                    '%s waits on %s saying more than `%s`, and it now says `%s` — %s',
                    $gap['requirement'],
                    $named,
                    $shape,
                    $said,
                    $gap['asks'],
                );
            }

            continue;
        }

        $looking = $named === null ? $envelopes : [$named => $envelopes[$named] ?? ''];
        $field = sprintf('/\b%s\??:/', preg_quote((string) $gap['field'], '/'));

        foreach ($looking as $envelope => $said) {
            if (preg_match($field, thePayloadShapeOf($said)) === 1) {
                $closed[] = sprintf(
                    '%s waits on `%s`, and %s now carries it — %s',
                    $gap['requirement'],
                    $gap['field'],
                    $envelope,
                    $gap['asks'],
                );
            }
        }
    }

    expect($closed)->toBe([], sprintf(
        "The contract now carries what these were waiting for:\n  %s\n\n"
        . 'Answer the requirement and delete its row. A gap that has closed and a register that '
        . "still lists it is how a requirement stays unanswered with a reason attached.\n",
        implode("\n  ", $closed),
    ));
});

it('N2-R14 — every gap says what it asks for and where it was raised', function (): void {
    // A row with an empty reason is a row nobody can act on, and the reason is
    // the only part that survives the person who wrote it. The requirement's
    // own name is not checked here: it is what every message above is written
    // around, so a row without one fails those rules first and by name.
    //
    // A row must also watch exactly one thing. Both would be two rules about
    // one gap that can disagree, and neither would be a row that passes by
    // having nothing to check — the failure this whole file exists to refuse.
    $thin = [];

    foreach (everyGapThisAppIsHolding() as $gap) {
        foreach (['asks', 'raised'] as $part) {
            if (trim($gap[$part]) === '') {
                $thin[] = sprintf('%s has no %s', $gap['requirement'], $part);
            }
        }
    }

    foreach (everyGapThisAppIsHolding() as $gap) {
        if (($gap['field'] === null) === ($gap['shape'] === null)) {
            $thin[] = sprintf('%s watches neither a field nor a shape, or both', $gap['requirement']);
        }
    }

    expect($thin)->toBe([], sprintf("These rows do not say enough to act on:\n  %s\n", implode("\n  ", $thin)));
});
