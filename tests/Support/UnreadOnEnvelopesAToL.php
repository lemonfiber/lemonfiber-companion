<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * The paths this app does not read on an envelope named `A` to `L`, and why.
 *
 * Part of {@see WhatThisAppDoesNotRead::rows()}.
 */
final readonly class UnreadOnEnvelopesAToL
{
    /** @var list<array{path: string, because: string}> */
    public const array ROWS = [
        [
            'path' => 'AdoptionEnvelope.rehearsed',
            'because' => WhatThisAppDoesNotRead::NEVER_ASKED_FOR_A_REHEARSAL,
        ],
        [
            'path' => 'BandwidthEnvelope.rehearsed',
            'because' => WhatThisAppDoesNotRead::NEVER_ASKED_FOR_A_REHEARSAL,
        ],
        [
            'path' => 'BesideEnvelope.rehearsed',
            'because' => WhatThisAppDoesNotRead::NEVER_ASKED_FOR_A_REHEARSAL,
        ],
        [
            'path' => 'BundleEnvelope.rehearsed',
            'because' => WhatThisAppDoesNotRead::NEVER_ASKED_FOR_A_REHEARSAL,
        ],
        [
            'path' => 'CredentialsEnvelope.rehearsed',
            'because' => WhatThisAppDoesNotRead::NEVER_ASKED_FOR_A_REHEARSAL,
        ],
        [
            'path' => 'DoctorEnvelope.rehearsed',
            'because' => WhatThisAppDoesNotRead::NEVER_ASKED_FOR_A_REHEARSAL,
        ],
        [
            'path' => 'HeldEnvelope.rehearsed',
            'because' => WhatThisAppDoesNotRead::NEVER_ASKED_FOR_A_REHEARSAL,
        ],
        [
            'path' => 'HostingEnvelope.rehearsed',
            'because' => WhatThisAppDoesNotRead::NEVER_ASKED_FOR_A_REHEARSAL,
        ],
        [
            'path' => 'HouseholdEnvelope.rehearsed',
            'because' => WhatThisAppDoesNotRead::NEVER_ASKED_FOR_A_REHEARSAL,
        ],
        [
            'path' => 'ImportEnvelope.rehearsed',
            'because' => WhatThisAppDoesNotRead::NEVER_ASKED_FOR_A_REHEARSAL,
        ],
        [
            'path' => 'HandoffEnvelope.quick_connect',
            'because' => 'Whether the media server lets a device be approved with a short code. Where it does, the stack says so in the steps, which are read and drawn in its words; the flag beside them says it again.',
        ],
        [
            'path' => 'HandoffEnvelope.rehearsed',
            'because' => 'Whether the hand-off was only described. This app never asks for a rehearsal: the first asking is the one that writes down when the code was given, which is the operator\'s tap.',
        ],
        [
            'path' => 'ClientsEnvelope.devices[].deep_link',
            'because' => 'A link that opens the suggested client. The screen names the client for the person to find on their own device, which is often not the phone holding this app; no requirement asks the app to open another one.',
        ],
        [
            'path' => 'BackupEnvelope.path',
            'because' => 'Where the copy was written on the machine. A copy is named on the screen by the name the listing of copies gives it, which is what putting it back is asked for by; a path on a machine the operator has no filesystem in front of names nothing they can use.',
        ],
        [
            'path' => 'BackupEnvelope.scope.trees[].archive_path',
            'because' => 'Where each tree of an existing setup sits inside the archive. The host path each was read from is what says whose directories were copied, and it is read; the place inside the file is the archive\'s own layout.',
        ],
        [
            'path' => 'BandwidthEnvelope.applied',
            'because' => 'Whether the call that answered wrote limits to the clients or only read them. This app reads the line and never writes a limit, so every answer it asks for says it only read, and a screen showing that would be reporting on an act it did not perform.',
        ],
        [
            'path' => 'BandwidthEnvelope.clients',
            'because' => 'What each download client was asked to hold to and what it is doing about it, direction by direction. A surface of its own — one row per client, with its verdict and whether it is fetching at all — and the next thing to read here; the line\'s standing, its capacity and its cap come first because they answer what `N10` asks, and a client\'s holding answers a question about that client.',
        ],
        [
            'path' => 'BandwidthEnvelope.metered',
            'because' => 'What the stack itself moved this calendar month, and what that count leaves out. It belongs beside the cap it is measured against, with its exclusions always said, and it is not read yet: the cap\'s standing already says within, warning or exceeded, and a byte count drawn without its exclusions would read as the whole line\'s usage.',
        ],
        [
            'path' => 'BandwidthEnvelope.respite',
            'because' => 'A temporary override lifting the limits, with how long it has left or how long ago it ran out. The line\'s standing already says *overridden* while one is in force; reading the countdown is part of the surface that reads each client, where an override is felt.',
        ],
        [
            'path' => 'BandwidthEnvelope.respite_says',
            'because' => 'What the override amounts to, in words. Read with `respite`, for its reason.',
        ],
        [
            'path' => 'BandwidthEnvelope.rhythm',
            'because' => 'The household\'s waking hours, declared once for every client. The line\'s standing already says whether it is inside or outside them right now; the hours themselves are a setting, shown where settings are, and read with the client surface that acts on them.',
        ],
        [
            'path' => 'BandwidthEnvelope.zone',
            'because' => 'The zone those hours are read in. Read with `rhythm`, for its reason.',
        ],
        [
            'path' => 'BandwidthEnvelope.down.limit',
            'because' => 'How the download limit was expressed — unlimited, a share, or a figure. The `says` sentence beside it carries the limit together with the line it is a share of, which is the rule the stack keeps in one place so that no surface shows a share without its figure; reading the structure here as well would be a second place for that rule.',
        ],
        [
            'path' => 'BandwidthEnvelope.down.resolved',
            'because' => 'What the download limit comes to against the measured line. Carried in the `says` sentence, for `down.limit`\'s reason.',
        ],
        [
            'path' => 'BandwidthEnvelope.up.limit',
            'because' => 'How the upload limit was expressed. Carried in the `says` sentence, for `down.limit`\'s reason.',
        ],
        [
            'path' => 'BandwidthEnvelope.up.resolved',
            'because' => 'What the upload limit comes to. Carried in the `says` sentence, for `down.limit`\'s reason.',
        ],
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
            'path' => 'HostingEnvelope.commands[].definition',
            'because' => 'The service definition installed for a command — the plist or unit file. Technical '
                . 'detail, which is available and does not lead: it is what somebody opens a terminal for '
                . 'after the screen has told them which command is wrong, and putting a file path on the row '
                . 'itself would make the list unreadable for the nine times out of ten nobody needs it.',
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
            'path' => 'CredentialsEnvelope.held[].fingerprint',
            'because' => 'A short likeness of the value, for telling two copies apart in a report. Nothing on the '
                . 'credentials screen compares copies, and a likeness of a value drawn beside a credential is '
                . 'the nearest this surface would come to drawing the value, which `N9-R4` refuses.',
        ],
        [
            'path' => 'CredentialsEnvelope.held[].from',
            'because' => 'Whose line the credential is: the stack\'s own, or an installed plugin\'s. `N9-R3` asks '
                . 'who produced it, which `origin` answers; which plugin brought the service is a question about '
                . 'provenance no requirement here asks, and it belongs beside the plugin, where `F7` draws it.',
        ],
        [
            'path' => 'CredentialsEnvelope.held[].location',
            'because' => 'Where the value lives on the machine, as a path. It is where somebody at the machine '
                . 'goes to replace it, and nothing on this surface replaces a credential (`N9-R4`); a path on a '
                . 'phone is a direction to a place the person holding it is not.',
        ],
        [
            'path' => 'CredentialsEnvelope.held[].setting',
            'because' => 'The setting the credential is recorded under, a name and never a value. It is how the '
                . 'settings screen would find it, and no requirement here asks the credentials screen to link '
                . 'there; the credential is named in the operator\'s words by `name`.',
        ],
        [
            'path' => 'CredentialsEnvelope.revealed',
            'because' => 'One value, handed back only where an operator asked at the terminal to see it and '
                . 'confirmed. This app never asks for one and never draws one (`N9-R4`), so the field that '
                . 'could carry a value is the one field of this envelope that is never read.',
        ],
        [
            'path' => 'CredentialsEnvelope.rotated',
            'because' => 'What became of a rotation, where one was asked for. Rotating is done at the machine, '
                . 'and nothing here asks for one, so no answer this app receives carries it.',
        ],
        [
            'path' => 'CapabilitiesEnvelope.scope',
            'because' => 'Whose credential asked. The admission that opened the session names the member, or '
                . 'nobody for the operator, and that decides which application a person is given (`N3-R1`); '
                . 'nothing here is inferred from which reads came back permitted. A second answer to the same '
                . 'question on every declaration could only disagree with the first.',
        ],
        [
            'path' => 'CapabilitiesEnvelope.stack',
            'because' => 'The stack\'s own identifier, the one pairing material carries. This app learns it '
                . 'from the pairing (`N1-R63`) and asks over a connection pinned to that stack\'s certificate, '
                . 'so the answer\'s copy is the same value come back, for `HeldEnvelope.id`\'s reason.',
        ],
        [
            'path' => 'DashboardEnvelope.downloaders',
            'because' => 'Every download client and whether it is paused, the reading that pausing and '
                . 'resuming downloads act on. This app offers neither (`Pausing` is under `NOT_YET` in '
                . '`EveryActionTheStackOffersTest`), and the reading goes with the action.',
        ],
        [
            'path' => 'DashboardEnvelope.alerts',
            'because' => 'Alerts as they start and resolve, each with its remedies. What a stack will wake somebody for is read from `AlertsEnvelope`, and every condition standing now reaches this app as an affected item of the health summary, with its remedies. A feed of onsets and resolutions is a log, and no companion requirement asks for one.',
        ],
        [
            'path' => 'DashboardEnvelope.door',
            'because' => 'How the household reaches the stack. Read from `FrontDoorEnvelope` for the front door\'s own screen; the same answer inside the front page would be a second reading of it on a screen that does not draw it.',
        ],
        [
            'path' => 'DashboardEnvelope.household',
            'because' => 'The household\'s members and what they asked for. Read from `HouseholdEnvelope` for what the house asked; the same answer inside the front page would be a second reading of it on a screen that does not draw it.',
        ],
        [
            'path' => 'DashboardEnvelope.queue',
            'because' => 'How deep each service\'s queue is and how many items are stuck in it. What stopped coming in is read from `StuckEnvelope`, and a queue that matters to the operator reaches the health summary as an affected item.',
        ],
        [
            'path' => 'DashboardEnvelope.services',
            'because' => 'Every service and the state it is in. Read from `StatusEnvelope` for what runs here, from the same gather that feeds this envelope.',
        ],
        [
            'path' => 'DashboardEnvelope.storage',
            'because' => 'Free space, how long until it runs out, and whether imports link or copy. Read from `SpaceEnvelope` for how full the machine is.',
        ],
        [
            'path' => 'DashboardEnvelope.telemetry',
            'because' => 'Whether the core\'s own view of the stack is live, degraded or disconnected. The summary\'s word is already `unknown` where the core cannot tell, and whether this app\'s own reading is current is decided by the stream\'s heartbeat, which is about the connection this app holds rather than about the core\'s.',
        ],
        [
            'path' => 'DashboardEnvelope.transfers',
            'because' => 'Downloads in flight, with their speed and time left. No companion requirement asks for them, and a figure that moves every second is one this app would draw two seconds late at best.',
        ],
        [
            'path' => 'DashboardEnvelope.vpn',
            'because' => 'The tunnel\'s exit and whether traffic leaves through it. VPN verification reaches this app as findings in the doctor report, and a failed one as an affected item of the health summary.',
        ],
        [
            'path' => 'DashboardEnvelope.health.affected[].onset',
            'because' => 'When the check went wrong. `N27-R19` says which problems are new by it, and '
                . '`/api/news` carries the same moment for each problem, so that read is where it is taken. '
                . 'The health summary draws what is wrong, not since when.',
        ],
        [
            'path' => 'DoctorEnvelope.findings[].onset',
            'because' => 'When the check went wrong, the same moment `/api/news` carries for it, which is '
                . 'where `N27-R19` takes it. The diagnosis screen draws each finding\'s verdict and remedy, '
                . 'and no requirement asks it to say since when.',
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
            'path' => 'DoctorEnvelope.findings[].verdict.steps',
            'because' => 'Every step of a plugin\'s recipe and what each came to, where a run of them stopped '
                . 'part-way. This app installs no plugin and runs no recipe, so no screen here has a run to '
                . 'account for, and no companion requirement asks for one — `N1-R17`.',
        ],
        [
            'path' => 'ErrorEnvelope.steps',
            'because' => 'The same steps on a refusal, and the same answer: the refusal reaches the operator as '
                . 'an obstacle, and nothing this app asks for runs a recipe.',
        ],
        [
            'path' => 'FormsEnvelope.forms[].name',
            'because' => 'What a form is called, in the stack\'s words. The `id` is what `up`, `down` and '
                . '`restart` are told, and a control here shows the id so that its label and its request '
                . 'cannot disagree. The stack\'s own name beside it would read better, and no requirement in '
                . '`N2` asks for it — raise it before reading it, which is `N1-R17`.',
        ],
        [
            'path' => 'FormsEnvelope.forms[].description',
            'because' => 'What a form is for, in one line of the stack\'s. `N18-R4` has what a form would '
                . 'start shown before it is started, and that is the `preview` reading rather than this '
                . 'line; nothing asks for the line on its own.',
        ],
        [
            'path' => 'FormsEnvelope.forms[].composable',
            'because' => 'Whether a form can run alongside another. This app starts one form per tap, and a '
                . 'combination the stack cannot compose is refused by the stack in its own words, which is '
                . 'an answer. Saying it before the choice is made is a screen `N18` has not reached.',
        ],
        [
            'path' => 'LifecycleEnvelope.offer',
            'because' => 'The offer a restart\'s rehearsal answers. A restart carrying no offer acts as it '
                . 'does without one (`ARCH-R164`), and this app sends none: no companion requirement asks a '
                . 'restart to be refused where the services it named have moved.',
        ],
        [
            'path' => 'LifecycleEnvelope.action',
            'because' => 'The Compose subcommand that was run. The screen judges the report against the verb the '
                . 'operator agreed to, which it sent and holds; reading the subcommand to decide would put a '
                . 'second answer beside that one, for `JobEnvelope.action`\'s reason.',
        ],
        [
            'path' => 'LifecycleEnvelope.forwarding',
            'because' => 'What starting did about the VPN\'s forwarded port, where it did anything. No `N` '
                . 'requirement asks what a verb came to to carry it: `N2-R9` has VPN verification reachable, '
                . 'which is a reading of the tunnel rather than a sentence on one start. Raise it against the '
                . 'spec before reading it, which is `N1-R17`.',
        ],
        [
            'path' => 'LifecycleEnvelope.status',
            'because' => 'The exit status of the command. What the verb came to is read off the condition and '
                . 'off each service the stack waited for, which say what came back; a process status beside '
                . 'them is a second answer about the same run and names nothing `N16-R6` asks to be named.',
        ],
        [
            'path' => 'LifecycleEnvelope.switched',
            'because' => 'What narrowing moved, where the command was a switch. Absent for every other action, '
                . 'and this app offers no switch (`N2-R7` offers start, stop and restart), for the reason '
                . '`StatusEnvelope.disturbs.switching` is not read.',
        ],
        [
            'path' => 'LifecycleEnvelope.plan.dropped',
            'because' => 'The profiles the verb left out, each with what it would need. `filtered` says the '
                . 'same service by service, which is what the report draws, for `PreviewEnvelope.dropped`\'s '
                . 'reason.',
        ],
        [
            'path' => 'LifecycleEnvelope.plan.filtered[].profile',
            'because' => 'Which profile a service left out belongs to, for `PreviewEnvelope.filtered[].profile`\'s reason.',
        ],
        [
            'path' => 'LifecycleEnvelope.plan.footprint',
            'because' => 'What the stack estimated the services would need. It is drawn before a start, as the '
                . 'rehearsal (`N18-R5`); after one, what came back is the answer, and an estimate beside it '
                . 'would read as a measurement of what is running, which `N18-R5` refuses.',
        ],
        [
            'path' => 'LifecycleEnvelope.plan.forms',
            'because' => 'The forms the operator named, repeated back. The screen sent the one it is about and '
                . 'draws the report under that name, for `PreviewEnvelope.forms`\'s reason.',
        ],
        [
            'path' => 'LifecycleEnvelope.plan.profiles',
            'because' => 'The profiles the verb activated, for `PreviewEnvelope.profiles`\'s reason.',
        ],
        [
            'path' => 'LifecycleEnvelope.plan.running',
            'because' => 'Which of the services were already running when the verb was asked for. It is drawn '
                . 'before a start, from the rehearsal (`N18-R10`); after one, `services` says where each ended up '
                . 'and is read, and a list of what was up beforehand would describe a moment already past.',
        ],
        [
            'path' => 'LifecycleEnvelope.plan.services',
            'because' => 'The services the plan named. Where the stack waited for them, `services` says where '
                . 'each ended up and is read; the plan\'s list beside it would name them twice. Where it did '
                . 'not wait, which is a stop, the listing read afterwards says where they stand.',
        ],
        [
            'path' => 'LifecycleEnvelope.services[].criticality',
            'because' => 'How much a service\'s absence costs. `N16-R6` asks for what did not come back to be '
                . 'named, which is its name and where it stood; the service\'s own frame draws how much it '
                . 'matters, off the listing read now rather than a report of a moment ago.',
        ],
        [
            'path' => 'LifecycleEnvelope.services[].depends_on',
            'because' => 'What a service will not work without. Drawn on the service\'s own frame off the '
                . 'listing, for `services[].criticality`\'s reason.',
        ],
        [
            'path' => 'LifecycleEnvelope.services[].describes',
            'because' => 'What a service is for, in the stack\'s words, for `StatusEnvelope.services[].describes`\'s reason.',
        ],
        [
            'path' => 'LifecycleEnvelope.services[].exit',
            'because' => 'What a service exited with. Drawn on the service\'s own frame off the listing, which '
                . 'carries the same field, for `services[].criticality`\'s reason.',
        ],
        [
            'path' => 'LifecycleEnvelope.services[].forms',
            'because' => 'The forms that brought a service in. `N18-R1` has that drawn where what is running is '
                . 'listed, and it is, off the listing; the report names what did not come back.',
        ],
        [
            'path' => 'LifecycleEnvelope.services[].id',
            'because' => 'The service\'s identifier. The report names a service by what an operator reads, and '
                . 'nothing on it is asked for by identifier.',
        ],
        [
            'path' => 'LifecycleEnvelope.services[].profile',
            'because' => 'The compose profile a service belongs to, for `StatusEnvelope.services[].profile`\'s reason.',
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
            'path' => 'HouseholdEnvelope.members[].last_seen',
            'because' => 'When a member was last about. The same block, and the same distinction — `N2-R13` has '
                . 'a *reading* carry its age, which is how old this app\'s answer is rather than how long ago '
                . 'somebody opened a client.',
        ],
        [
            'path' => 'HouseholdEnvelope.members[].requests[].media',
            'because' => 'The library\'s own handle for the thing asked for, the id `/api/held` lists it under. '
                . '`N2-R11` asks for enough to decide on, and the words a person recognises are the title beside '
                . 'it. Where a title streams from is answered on the shelf and on the title itself, and no '
                . 'screen opens a request\'s title by this handle.',
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
            'path' => 'HouseholdEnvelope.members[].requests[].arrived',
            'because' => 'When the title arrived on the media server. A request is drawn by where it stands, '
                . 'and no companion requirement asks a screen to say when it arrived — raise it against the '
                . 'spec before reading it, which is `N1-R17`.',
        ],
        [
            'path' => 'HouseholdEnvelope.members[].requests[].shelf_id',
            'because' => 'The media server\'s handle for the title once it has arrived, the one the held read '
                . 'names it by. This app plays nothing and opens no shelf, for the reason '
                . '`HouseholdEnvelope.members[].requests[].media` gives, so there is nothing for the handle to '
                . 'find.',
        ],
        [
            'path' => 'HouseholdEnvelope.members[].requests[].year',
            'because' => 'The year the title came out. `N2-R11` asks for enough to decide on, and the title '
                . 'beside it is what a person recognises; no requirement asks for the year — `N1-R17`.',
        ],
        [
            'path' => 'HouseholdEnvelope.members[].standing',
            'because' => 'Where a member stands: invited, expired, active or suspended. No requirement asks a '
                . 'screen here for it. The one that comes nearest, `N9-R7`, asks for an invitation that lapsed '
                . 'to be told apart from one the invitee declined, and `declined` is not one of these four, so '
                . 'reading `expired` alone would answer half of it and look like all of it. `N1-R17` has the '
                . 'field wait for a requirement.',
        ],
        [
            'path' => 'JobEnvelope.action',
            'because' => 'Which action the stack acknowledged. The caller already knows — it is the one that '
                . 'just asked — so reading it to check would be this app telling a stack what it had been asked, '
                . 'and reading it to decide would put a second answer beside the one the call site is holding.',
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
            'because' => 'No reader here opens it: the SDK\'s `Refusal` does, and it quotes what a service '
                . 'said, fit to show to the person who asked and not to log, report or forward. A refused '
                . 'bundle, a run the stack would not put back and a copy it will not restore carry it from '
                . 'there to the screen that asked as `WhatTheRefusalNamed`, and every other refusal reaches '
                . 'the operator as an obstacle, which is one of four sentences this app has written.',
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
            'path' => 'HeldEnvelope.holdings[].poster',
            'because' => 'Where a holding\'s poster is served at the door. An image there is fetched with '
                . 'the member\'s grant over the door\'s pin, and nothing in this app fetches one: it issues no '
                . 'request of its own and the bridge fetches only what the player plays. Every poster is '
                . 'lettered with its name (`N3-R24`).',
        ],
        [
            'path' => 'HeldEnvelope.holdings[].backdrop',
            'because' => 'Where a holding\'s backdrop is served at the door, for `holdings[].poster`\'s reason.',
        ],
        [
            'path' => 'HeldEnvelope.holdings[].stream_from',
            'because' => 'Where a holding streams from. Play is drawn on the title\'s own page (`N3-R22`), '
                . 'and `/api/held/{id}` answers the same location for that one title as the member opens it; '
                . 'the shelf\'s copy would be a second, older answer to the question that page asks.',
        ],
        [
            'path' => 'HeldEnvelope.holdings[].door',
            'because' => 'The door\'s fingerprint beside a holding\'s location, for `holdings[].stream_from`\'s reason.',
        ],
        [
            'path' => 'HeldEnvelope.holdings[].unlocated',
            'because' => 'Why a holding has no location, for `holdings[].stream_from`\'s reason.',
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
    ];
}
