<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * The paths this app does not read on an envelope named `M` to `Z`, and why.
 *
 * `PluginsEnvelope` has a class of its own, {@see UnreadOnThePluginsEnvelope}.
 *
 * Part of {@see WhatThisAppDoesNotRead::rows()}.
 */
final readonly class UnreadOnEnvelopesMToZ
{
    /** @var list<array{path: string, because: string}> */
    public const array ROWS = [
        [
            'path' => 'MusicEnvelope.rehearsed',
            'because' => WhatThisAppDoesNotRead::NEVER_ASKED_FOR_A_REHEARSAL,
        ],
        [
            'path' => 'QualityEnvelope.rehearsed',
            'because' => WhatThisAppDoesNotRead::NEVER_ASKED_FOR_A_REHEARSAL,
        ],
        [
            'path' => 'RemovalEnvelope.rehearsed',
            'because' => 'Whether taking somebody out of the household was only rehearsed. `N13-R10` asks for that rehearsal, and the `remove` action takes `dry_run` now; the screen does not ask for one yet, so every answer it reads says it was not, and asking for one is the work `N13-R10` is owed.',
        ],
        [
            'path' => 'RepairEnvelope.rehearsed',
            'because' => WhatThisAppDoesNotRead::NEVER_ASKED_FOR_A_REHEARSAL,
        ],
        [
            'path' => 'ReplacementEnvelope.rehearsed',
            'because' => WhatThisAppDoesNotRead::NEVER_ASKED_FOR_A_REHEARSAL,
        ],
        [
            'path' => 'ResetEnvelope.rehearsed',
            'because' => WhatThisAppDoesNotRead::NEVER_ASKED_FOR_A_REHEARSAL,
        ],
        [
            'path' => 'RestoreEnvelope.rehearsed',
            'because' => WhatThisAppDoesNotRead::NEVER_ASKED_FOR_A_REHEARSAL,
        ],
        [
            'path' => 'SpaceEnvelope.rehearsed',
            'because' => WhatThisAppDoesNotRead::NEVER_ASKED_FOR_A_REHEARSAL,
        ],
        [
            'path' => 'StopSeedingEnvelope.rehearsed',
            'because' => WhatThisAppDoesNotRead::NEVER_ASKED_FOR_A_REHEARSAL,
        ],
        [
            'path' => 'SubstitutionEnvelope.rehearsed',
            'because' => 'Whether a choice of filler was rehearsed. lemonfiber answers a choice\'s reading as the reading itself, with this `false` whether or not `dry_run` was sent; whether anything was written is `applied`, which is read.',
        ],
        [
            'path' => 'SubstitutionEnvelope.substitution.setting',
            'because' => 'The setting a choice of filler is recorded under, in lemonfiber\'s own spelling of it. The screen says the same thing in the operator\'s terms, from the capability and the service that would answer it.',
        ],
        [
            'path' => 'StoredEnvelope.rehearsed',
            'because' => WhatThisAppDoesNotRead::NEVER_ASKED_FOR_A_REHEARSAL,
        ],
        [
            'path' => 'UninstallEnvelope.rehearsed',
            'because' => 'Whether taking lemonfiber off was only rehearsed. `N13-R10` asks for that rehearsal, and the `uninstall` action takes `dry_run` now; the screen does not ask for one yet, so every answer it reads says it was not, and asking for one is the work `N13-R10` is owed.',
        ],
        [
            'path' => 'UpdateEnvelope.rehearsed',
            'because' => WhatThisAppDoesNotRead::NEVER_ASKED_FOR_A_REHEARSAL,
        ],
        [
            'path' => 'UpgradeEnvelope.rehearsed',
            'because' => WhatThisAppDoesNotRead::NEVER_ASKED_FOR_A_REHEARSAL,
        ],
        [
            'path' => 'WatchEnvelope.rehearsed',
            'because' => WhatThisAppDoesNotRead::NEVER_ASKED_FOR_A_REHEARSAL,
        ],
        [
            'path' => 'PairingEnvelope.material.stack',
            'because' => 'The stack\'s own identifier, carried inside the pairing line for the other phone to know this stack by. Nothing on the screen that makes the code names it, and no requirement asks it to.',
        ],
        [
            'path' => 'PairingEnvelope.until',
            'because' => 'When the code stops being good, written as a date and a time in UTC. The screen says the same moment, `material.expires`, as the clock on the phone holding it reads, which is the time somebody pairing a phone is looking at.',
        ],
        [
            'path' => 'RestoreEnvelope.would.manifest.scope.trees[].archive_path',
            'because' => 'Where each tree of an existing setup sits inside the archive, for `BackupEnvelope.scope.trees[].archive_path`\'s reason.',
        ],
        [
            'path' => 'RestoreEnvelope.done.scope.trees[].archive_path',
            'because' => 'Where each tree of an existing setup sits inside the archive, for `BackupEnvelope.scope.trees[].archive_path`\'s reason.',
        ],
        [
            'path' => 'RestoreEnvelope.would.manifest.members[].archive_path',
            'because' => 'Where each thing a copy holds sits inside the archive. What it is called is read and listed as what putting it back would overwrite; the place inside the file is the archive\'s own layout.',
        ],
        [
            'path' => 'RestoreEnvelope.would.manifest.data_root',
            'because' => 'The data root the copy was taken against. Where that differs from this machine\'s, the stack says so in `relocation`, which is read and drawn before the yes; where it does not, the data goes back where it was, which the screen says.',
        ],
        [
            'path' => 'RestoreEnvelope.would.manifest.schema',
            'because' => 'The archive format. Whether this machine can read it is the stack\'s decision, and one it cannot is refused before anything is listed, so a listing that arrives is one it can restore.',
        ],
        [
            'path' => 'RestoreEnvelope.would.manifest.sensitive',
            'because' => 'Whether the copy carries credentials, as its own account of itself records it. The copy\'s report says so when it is taken, and every capture the stack takes carries them; `N6` asks nothing of a restore\'s listing about it.',
        ],
        [
            'path' => 'UndoEnvelope.reversed[].action.id',
            'because' => 'The identifier, inside a service, of what a removal or a reconfigure is about. What each reversal was against is drawn from `target` and what going back did from `does`; an identifier inside a service names nothing an operator can look up from a phone.',
        ],
        [
            'path' => 'UndoEnvelope.reversed[].action.resource',
            'because' => 'The kind of resource, inside a service, a removal or a reconfigure is about. `target` names the service and `does` what going back did, and both are drawn; the resource kind is the instruction the stack carried out with.',
        ],
        [
            'path' => 'UndoEnvelope.reversed[].action.key',
            'because' => 'The setting a restore put back, or the file key a withdrawal took a region out of. `target` and `does` are drawn for every reversal; the key is the instruction the stack carried out with.',
        ],
        [
            'path' => 'UndoEnvelope.reversed[].action.value',
            'because' => 'What a setting was put back to. A value can be a credential, and this app draws no value a reversal carries, which is the line `N6-R7` draws for what the stack holds.',
        ],
        [
            'path' => 'UndoEnvelope.reversed[].action.wrote',
            'because' => 'What lemonfiber had written to a setting, carried so the stack can tell its own work from the operator\'s before it acts, and a value that can be a credential. A setting somebody chose since is reported in `left` with why, which is read.',
        ],
        [
            'path' => 'UndoEnvelope.reversed[].action.path',
            'because' => 'The file a deletion or a withdrawal acted on. `target` names what the change was against and is drawn; the path is on a machine the operator has no filesystem in front of.',
        ],
        [
            'path' => 'UndoEnvelope.reversed[].action.owner',
            'because' => 'Whose region a withdrawal takes out, as the file\'s markers name it: the stack\'s check that the region is its own. A region edited since is reported in `left` with why, which is read.',
        ],
        [
            'path' => 'UndoEnvelope.reversed[].action.written',
            'because' => 'The checksum of the region a withdrawal takes out: the stack\'s check that it is still its own work, and a number that means nothing on a screen.',
        ],
        [
            'path' => 'UndoEnvelope.reversed[].action.current',
            'because' => 'The version a run moved a service to, which a repin checks is still running. The stack carries no repin out: a run carried out reports it in `left` with why, which is read, and a rehearsal names it with `does`, which is drawn.',
        ],
        [
            'path' => 'UndoEnvelope.reversed[].action.name',
            'because' => 'The name of the key a revoke takes away or a reinstatement would make good again. `target` names what the change was against and `does` says a key is revoked or reinstated, and both are drawn; no requirement asks this app to name a key (`N1-R17`).',
        ],
        [
            'path' => 'UndoEnvelope.reversed[].action.previous',
            'because' => 'The version a repin would put back, for `UndoEnvelope.reversed[].action.current`\'s reason.',
        ],
        [
            'path' => 'UndoEnvelope.reversed[].action.field',
            'because' => 'The field of a service\'s own resource a reconfigure puts back. `target` names the service and `does` says its own setting went back, and both are drawn; a service that did not answer is reported in `left` with why, which is read.',
        ],
        [
            'path' => 'WatchEnvelope.would',
            'because' => 'The guard a run would keep — the data location, how often it looks, and the command it '
                . 'would run — which is exactly what a guard is owed before it starts. It is answered only by a '
                . 'rehearsal, and no argument the watch action takes over the web API asks for one, so no answer '
                . 'this app can ask for carries it: a guard started here is a real one, and its answer arrives '
                . 'without this. The gap is in `WhatTheContractDoesNotCarryTest`, and this is read the day an '
                . 'answer this app can ask for carries it.',
        ],
        [
            'path' => 'QualityEnvelope.overwritten',
            'because' => 'The hand-edited configuration a re-assert replaced, or a rehearsed one would, with the '
                . 'diff. Carried only where the preset was put back over a hand-edit, and this app does not '
                . 'offer that: `N24-R5` shows a hand-edit as respected and keeps the re-assert off this surface, '
                . 'so no answer it asks for carries one.',
        ],
        [
            'path' => 'PreviewEnvelope.dropped',
            'because' => 'The profiles a start would leave out, each with what it would need. `filtered` '
                . 'says the same service by service, which is what an operator knows the stack by, and it '
                . 'is what the rehearsal reads.',
        ],
        [
            'path' => 'PreviewEnvelope.filtered[].profile',
            'because' => 'Which profile a service left out belongs to. The service is named, with what it '
                . 'would need; its profile is the stack\'s grouping of it and nothing an operator starts.',
        ],
        [
            'path' => 'StatusEnvelope.filtered[].profile',
            'because' => 'Which profile a service the running forms left out belongs to, for the reason '
                . 'the same field on the rehearsal is not read.',
        ],
        [
            'path' => 'PreviewEnvelope.forms',
            'because' => 'The forms the rehearsal was asked about, repeated back. The screen asked about one '
                . 'form and draws the rehearsal under that form\'s own name, so the echo says nothing the '
                . 'screen does not already hold.',
        ],
        [
            'path' => 'PreviewEnvelope.profiles',
            'because' => 'The profiles a start would bring up. `N18-R4` asks for what would start and what '
                . 'would be left out, with the reason for each; the services are what would start, and a '
                . 'profile is the stack\'s grouping of them rather than something an operator starts.',
        ],
        [
            'path' => 'StatusEnvelope.forms',
            'because' => 'The forms this reading was asked about, which is not the forms the stack declares. '
                . '`/api/status` asks about none, so on the one reading of it this app makes the field is '
                . 'always empty — read as the stack\'s forms, it drew no form control against a real stack. '
                . 'The forms are read from `FormsEnvelope.forms`, the one answer that lists them.',
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
            'path' => 'StatusEnvelope.services[].profile',
            'because' => 'The compose profile a service belongs to. Profiles are how the stack assembles a '
                . 'form, and `B1` keeps them from the operator: they are not selectable (`B1-R8`), and the '
                . 'one place `N18` shows them is a form\'s preview before it is started (`N18-R4`), which '
                . 'is the `preview` reading rather than this field.',
        ],
        [
            'path' => 'StatusEnvelope.services[].describes',
            'because' => 'What a service is for, in the stack\'s words. Genuinely operator-facing — somebody '
                . 'reading a list of nineteen names would be better off knowing which is the one that fetches '
                . 'series — and no requirement in `N1` to `N4` asks for it. Raise it against the spec before '
                . 'reading it, which is `N1-R17`.',
        ],
        [
            'path' => 'UpdateEnvelope.offer',
            'because' => 'The offer an update answers, built from each step it would take. An update '
                . 'carrying no offer acts as it does without one (`ARCH-R164`), and this app sends none: no '
                . 'companion requirement asks an update to be refused where the releases it named have moved.',
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
                . 'release\'s version, what it delivers, whether the household would notice and whether it was '
                . 'taken back, which is what `N2-R16` and `E5-R6` ask of it; the rest of the entry is a '
                . 'changelog screen nobody has asked for.',
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
                . 'in a flag beside this. The notes are drawn on the version screen, which reads them off the '
                . '`version` envelope.',
        ],
        [
            'path' => 'VersionEnvelope.supported_schema',
            'because' => 'The generations of the stack\'s own definition this copy of lemonfiber reads. Whether '
                . 'a definition is one it can read is the machine\'s decision, taken before anything is shown, '
                . 'and a list of generation numbers is nothing an operator can act on from a phone; the version '
                . 'screen names the stack by the version it runs instead.',
        ],
        [
            'path' => 'VersionEnvelope.changelog.releases',
            'because' => 'Every release the record holds. The version screen answers what runs and what the '
                . 'running release changed (`N14-R7`); the list of releases is the update screen\'s, which reads '
                . 'the same list off the `update` envelope.',
        ],
        [
            'path' => 'VersionEnvelope.changelog.requirements',
            'because' => 'Which requirement each release shipped, for `UpdateEnvelope.changelog.requirements`\'s reason.',
        ],
        [
            'path' => 'VersionEnvelope.changelog.running.carried',
            'because' => 'What the release in use brought forward from the one before it, for '
                . '`UpdateEnvelope.changelog.running.carried`\'s reason.',
        ],
        [
            'path' => 'VersionEnvelope.changelog.running.patches',
            'because' => 'The same, for what the release in use fixes.',
        ],
        [
            'path' => 'VersionEnvelope.changelog.running.released_on',
            'because' => 'The same, for when it was published.',
        ],
        [
            'path' => 'VersionEnvelope.changelog.running.tag',
            'because' => 'The name the release was published under, for `UpdateEnvelope.changelog.running.tag`\'s reason.',
        ],
        [
            'path' => 'VersionEnvelope.changelog.running.groups[].entries[].reference',
            'because' => 'Where a change was reviewed. The notes are read for what changed, in the stack\'s own '
                . 'words; a link to a review on a forge is not something an operator decides anything with.',
        ],
        [
            'path' => 'VersionEnvelope.changelog.running.groups[].entries[].requirements',
            'because' => 'Which requirements a change served, for `UpdateEnvelope.changelog.requirements`\'s reason.',
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
            'path' => 'RepairEnvelope.beyond',
            'because' => 'Remedies for checks this repair did not attempt. `N2-R4` offers the repair the core '
                . 'offers for the finding in hand; a list of other checks belongs to a screen about the whole '
                . 'run, and no requirement asks for one.',
        ],
        [
            'path' => 'RepairEnvelope.acted',
            'because' => 'What became of a repair is read from its outcome, which says what happened rather '
                . 'than whether anything did. A boolean beside it is a second answer to one question, and '
                . '`N2-R4` has the app state what a repair did in the words the core produced.',
        ],
        [
            'path' => 'SelfUpdateEnvelope.asked',
            'because' => 'The version an operator asked to move to. This app asks about the running copy and names '
                . 'no version, since it moves nothing (`N14-R6`), so every reading it asks for leaves this empty.',
        ],
        [
            'path' => 'SelfUpdateEnvelope.at',
            'because' => 'Where the running binary is on the machine, which tells apart several copies on one search '
                . 'path. That is a question asked at the machine, where the command is run; the screen names the '
                . 'version running and how it was installed (`N14-R1`), and a path adds nothing a phone can act on.',
        ],
        [
            'path' => 'SelfUpdateEnvelope.changed',
            'because' => 'The newest release\'s notes as its release page words them, in markdown. About names the '
                . 'newer version and what updating to it brings (`N14-R5`); the running release\'s notes are drawn '
                . 'grouped on the versions screen from the `changelog` block (`N14-R4`, `N14-R7`). Drawn here, this '
                . 'text arrived as one unparsed run of markdown, longer than the screen could scroll past.',
        ],
        [
            'path' => 'SelfUpdateEnvelope.configuration',
            'because' => 'Whether a named version can read this machine\'s configuration. It is filled only where a '
                . 'version was asked for, and this app asks for none.',
        ],
        [
            'path' => 'SelfUpdateEnvelope.replaceable',
            'because' => 'Whether the process answering can write where the binary sits. That describes the account '
                . 'the stack runs as, not the one an operator types the command in, so it cannot say whether the '
                . 'command shown will work for them; the command itself, or why there is none, is what `N14-R2` asks for.',
        ],
        [
            'path' => 'SpaceEnvelope.agreement',
            'because' => 'What the offer to reclaim names itself, so that an answer to it can say which offer it '
                . 'was answering. It exists for the cleanup of what costs nothing, which this app does not ask '
                . 'for: the SDK\'s `space` endpoint takes nothing, so a caller can neither confirm the cleanup nor '
                . 'name this offer. Stopping seeding one download is the removal this app offers, and it reads '
                . 'that offer\'s own name.',
        ],
        [
            'path' => 'SpaceEnvelope.reclaimed',
            'because' => 'What became of a confirmed cleanup. Only a call that confirms fills it in, and this app '
                . 'makes none, for the reason `agreement` gives, so every reading it asks for leaves it empty.',
        ],
        [
            'path' => 'SpaceEnvelope.reclaimable',
            'because' => 'A second reading of bytes `consumption` already counts, as the stack says itself, and '
                . 'each line of `consumption` carries what getting it back would cost. Drawing both would show '
                . 'the same room twice, which is the error the stack arranges its accounting to avoid.',
        ],
        [
            'path' => 'SpaceEnvelope.outsized',
            'because' => 'Single files far out of line with the rest, named by path. `N12-R6` has the room shown by '
                . 'the categories the contract gives and never as a file listing, and a list of paths is one.',
        ],
        [
            'path' => 'SpaceEnvelope.interrupted',
            'because' => 'Imports that stopped part-way, with what is on disk for each. A stall is `N8`\'s, and the '
                . '`stuck` envelope is where this app reads what did not come in; `N12` asks nothing about it.',
        ],
        [
            'path' => 'SpaceEnvelope.volumes[].at',
            'because' => 'The path the stack measured. The screen names a volume by where it is mounted, which is '
                . 'what its limit and every figure beside it belong to.',
        ],
        [
            'path' => 'SpaceEnvelope.consumption[].tally.files',
            'because' => 'How many names a line counted. The line answers where the room went in bytes, and a count '
                . 'of names beside it answers nothing `N12` asks.',
        ],
        [
            'path' => 'SpaceEnvelope.consumption[].tally.shared',
            'because' => 'How many of those names point at a file already counted. What sharing saves is shown as '
                . 'the difference between what a line occupies and what it would take with nothing shared, which '
                . 'is the figure an operator can weigh; the count of names is not.',
        ],
        [
            'path' => 'StoredEnvelope.removal',
            'because' => 'Whether the call that answered removed what it lists. The listing this app asks for '
                . 'removes nothing and always answers `not-asked`; only the errand that forgets everything fills '
                . 'it in, and that errand is taking away (`N13`), which this app does not offer. A screen showing '
                . 'it would report on an act nothing here asked for.',
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
            'because' => 'Whether the run that answered was agreed to. The reading this app asks for never is, '
                . 'and `N2-R17` is about the operator confirming *here*, before it runs — reading the stack\'s '
                . 'own flag would let a confirmation somewhere else stand in for the one this app asked for.',
        ],
        [
            'path' => 'UpdateEnvelope.halted',
            'because' => 'Why the stack is not as a run found it, where a run stopped part way or left it down. '
                . '`N2-R18` reports what became of each service, which is what an operator acts on; a '
                . 'sentence about the run as a whole is a second answer and no requirement asks for one.',
        ],
        [
            'path' => 'UpdateEnvelope.in_flight',
            'because' => 'What the download clients are still transferring, named so an operator can tell '
                . 'whether what they are waiting for is among them. `E1-R12` has an update report these '
                . 'before it runs; no `N2` requirement asks the app to, and the downloads screen is where an '
                . 'operator reads what is in flight.',
        ],
        [
            'path' => 'MigrationEnvelope.linking.links',
            'because' => 'Whether imports can be hardlinks across the existing layout. It is false whenever '
                . '`linking` is sent at all, because a layout that links is not reported, so the presence of '
                . '`linking` already carries the whole answer and this flag could only repeat it.',
        ],
        [
            'path' => 'MigrationEnvelope.linking.forced',
            'because' => 'Whether lemonfiber would carry the remedy out itself. It is always false: the '
                . 'layout and the library in it are the operator\'s. `N7-R13` draws the remedy as words with '
                . 'no act beside them, which is what this flag being false asks, so reading it would change '
                . 'nothing drawn.',
        ],
        [
            'path' => 'MigrationEnvelope.carrying[].existing',
            'because' => 'The version of a service standing here now. Adopting is asked about as an act of its '
                . 'own, and its answer carries the same versions for every service it would open, in '
                . '`AdoptionEnvelope.upgrades[]`, which is read and drawn before the yes (`N7-R4`). The '
                . 'survey reads the service, what adopting it means, whether a copy is wanted first and '
                . 'whether adopting is refused, and draws none of them: choosing a mode is where they are said.',
        ],
        [
            'path' => 'MigrationEnvelope.carrying[].ours',
            'because' => 'The version lemonfiber pins for that service. Read with `existing`, for its reason.',
        ],
        [
            'path' => 'MigrationEnvelope.carrying[].verdict',
            'because' => 'Which of the two versions is the later, in one word. Read with `existing`, for its '
                . 'reason.',
        ],
    ];
}
