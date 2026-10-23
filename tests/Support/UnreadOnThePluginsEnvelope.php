<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * The paths this app does not read on the `plugins` envelope, and why.
 *
 * Apart from {@see UnreadOnEnvelopesMToZ} for its size. An update carries an
 * install's whole account, so most rows here repeat an install row's decision
 * at a second place, and say so. What each rollback report's actions carry is
 * in {@see UnreadOnThePluginRollbacks}.
 *
 * Part of {@see WhatThisAppDoesNotRead::rows()}.
 */
final readonly class UnreadOnThePluginsEnvelope
{
    /** @var list<array{path: string, because: string}> */
    public const array ROWS = [
        [
            'path' => 'PluginsEnvelope.install.would.manifest',
            'because' => 'The SHA-256 of the manifest the plugin was installed from. Whether it was reviewed, where it came from, its revision and what signed it are drawn (`N25-R8`); matching the digest against the catalogue is the stack\'s check, and not one the operator is asked to make.',
        ],
        [
            'path' => 'PluginsEnvelope.installed[].manifest',
            'because' => 'The SHA-256 of the manifest the plugin was installed from. Whether it was reviewed, where it came from, its revision and what signed it are drawn (`N25-R8`); matching the digest against the catalogue is the stack\'s check, and not one the operator is asked to make.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.would.manifest',
            'because' => 'The SHA-256 of the manifest the plugin was installed from. Whether it was reviewed, where it came from, its revision and what signed it are drawn (`N25-R8`); matching the digest against the catalogue is the stack\'s check, and not one the operator is asked to make.',
        ],
        [
            'path' => 'PluginsEnvelope.install.would.tree',
            'because' => 'The digest of the plugin\'s tree it was installed from, for `PluginsEnvelope.install.would.manifest`\'s reason: where it came from, its revision and what signed it are drawn (`N25-R8`), and matching a digest is the stack\'s check.',
        ],
        [
            'path' => 'PluginsEnvelope.installed[].tree',
            'because' => 'As `PluginsEnvelope.install.would.tree`: the record keeps the digest of the tree it was installed from, and matching it is the stack\'s check.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.would.tree',
            'because' => 'As `PluginsEnvelope.install.would.tree`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.unvetted',
            'because' => WhatThisAppDoesNotRead::THE_OPERATORS_OVERLAY,
        ],
        [
            'path' => 'PluginsEnvelope.proof.kept[].at',
            'because' => 'As `PluginsEnvelope.nonconforming[].at`: a proof again lists the same answers kept against the plugin, and what they say is drawn; when each was given changes none of it.',
        ],
        [
            'path' => 'PluginsEnvelope.proof.proofs[].came_to.declared[].constraint',
            'because' => 'As `PluginsEnvelope.install.proofs[].came_to.declared[].constraint`: a proof again carries the same proofs an install does, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.proof.proofs[].came_to.declared[].fixture',
            'because' => 'As `PluginsEnvelope.install.proofs[].came_to.declared[].fixture`: a proof again carries the same proofs an install does, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.proof.proofs[].came_to.declared[].held',
            'because' => 'As `PluginsEnvelope.install.proofs[].came_to.declared[].held`: a proof again carries the same proofs an install does, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.proof.proofs[].came_to.declared[].place',
            'because' => 'As `PluginsEnvelope.install.proofs[].came_to.declared[].place`: a proof again carries the same proofs an install does, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.proof.proofs[].of',
            'because' => 'As `PluginsEnvelope.install.proofs[].of`: a proof again carries the same proofs an install does, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.nonconforming[].at',
            'because' => 'When an adapter answered outside its contract. What it answered, as which capability, and that the plugin fills none of it until it is proved again are drawn; the moment changes none of that, and the stack keeps the answer until a proof clears it.',
        ],
        [
            'path' => 'PluginsEnvelope.install.against',
            'because' => 'What the proofs\' verdicts were reached against: the recordings a plugin ships, or the service on this machine. An install this app agrees to is proven against the service, and each proof\'s outcome is read and drawn (`N25-R2`); which evidence an author\'s read used is not a question the operator is asked.',
        ],
        [
            'path' => 'PluginsEnvelope.install.proofs[].came_to.declared[].constraint',
            'because' => 'Which constraint of which recorded fixture a proof fails as its plugin declared it would, and where. Why each failure is declared is read and drawn; the fixture and the constraint are the author\'s test data, and `N25-R2` asks for the outcome.',
        ],
        [
            'path' => 'PluginsEnvelope.install.proofs[].came_to.declared[].fixture',
            'because' => 'Which constraint of which recorded fixture a proof fails as its plugin declared it would, and where. Why each failure is declared is read and drawn; the fixture and the constraint are the author\'s test data, and `N25-R2` asks for the outcome.',
        ],
        [
            'path' => 'PluginsEnvelope.install.proofs[].came_to.declared[].held',
            'because' => 'Which constraint of which recorded fixture a proof fails as its plugin declared it would, and where. Why each failure is declared is read and drawn; the fixture and the constraint are the author\'s test data, and `N25-R2` asks for the outcome.',
        ],
        [
            'path' => 'PluginsEnvelope.install.proofs[].came_to.declared[].place',
            'because' => 'Which constraint of which recorded fixture a proof fails as its plugin declared it would, and where. Why each failure is declared is read and drawn; the fixture and the constraint are the author\'s test data, and `N25-R2` asks for the outcome.',
        ],
        [
            'path' => 'PluginsEnvelope.install.proofs[].of',
            'because' => 'Which of the plugin\'s own services a proof asks. What it asks, as the method and the path, is read and drawn, and the plugin\'s services are named nowhere else on the screen.',
        ],
        [
            'path' => 'PluginsEnvelope.install.asks',
            'because' => 'Every link the plugin\'s services would ask for, with what each would reach and how it would be settled. The rehearsal draws what `N25-R1` asks of it, and a link a service holds once it is installed is drawn with every other on the wiring screen; no requirement asks for it before the yes.',
        ],
        [
            'path' => 'PluginsEnvelope.install.recipes_ran',
            'because' => 'Every install recipe that ran and held, with what each step came to. It is filled only on an install that held, which the screen says in one line; a recipe that did not hold ends the install with a refusal carrying its own account, which is read in the stack\'s words.',
        ],
        [
            'path' => 'PluginsEnvelope.install.verified.broke[].before',
            'because' => 'What a check said before the install, beside what it says now: the stack\'s evidence for calling it worse or unsettled. The screen names each such check by its title now (`N25-R3`).',
        ],
        [
            'path' => 'PluginsEnvelope.install.verified.broke[].now.category',
            'because' => 'The rest of a check\'s finding. The screen names the check by its title, which is how the health screen lists it, and the health screen draws the whole finding.',
        ],
        [
            'path' => 'PluginsEnvelope.install.verified.broke[].now.caused_by',
            'because' => 'The rest of a check\'s finding. The screen names the check by its title, which is how the health screen lists it, and the health screen draws the whole finding.',
        ],
        [
            'path' => 'PluginsEnvelope.install.verified.broke[].now.check',
            'because' => 'The rest of a check\'s finding. The screen names the check by its title, which is how the health screen lists it, and the health screen draws the whole finding.',
        ],
        [
            'path' => 'PluginsEnvelope.install.verified.broke[].now.onset',
            'because' => 'The rest of a check\'s finding. The screen names the check by its title, which is how the health screen lists it, and the health screen draws the whole finding.',
        ],
        [
            'path' => 'PluginsEnvelope.install.verified.broke[].now.origin',
            'because' => 'The rest of a check\'s finding. The screen names the check by its title, which is how the health screen lists it, and the health screen draws the whole finding.',
        ],
        [
            'path' => 'PluginsEnvelope.install.verified.broke[].now.said',
            'because' => 'The rest of a check\'s finding. The screen names the check by its title, which is how the health screen lists it, and the health screen draws the whole finding.',
        ],
        [
            'path' => 'PluginsEnvelope.install.verified.broke[].now.service',
            'because' => 'The rest of a check\'s finding. The screen names the check by its title, which is how the health screen lists it, and the health screen draws the whole finding.',
        ],
        [
            'path' => 'PluginsEnvelope.install.verified.broke[].now.service_name',
            'because' => 'The rest of a check\'s finding. The screen names the check by its title, which is how the health screen lists it, and the health screen draws the whole finding.',
        ],
        [
            'path' => 'PluginsEnvelope.install.verified.broke[].now.verdict',
            'because' => 'The rest of a check\'s finding. The screen names the check by its title, which is how the health screen lists it, and the health screen draws the whole finding.',
        ],
        [
            'path' => 'PluginsEnvelope.install.verified.unsettled[].before',
            'because' => 'What a check said before the install, beside what it says now: the stack\'s evidence for calling it worse or unsettled. The screen names each such check by its title now (`N25-R3`).',
        ],
        [
            'path' => 'PluginsEnvelope.install.verified.unsettled[].now.category',
            'because' => 'The rest of a check\'s finding. The screen names the check by its title, which is how the health screen lists it, and the health screen draws the whole finding.',
        ],
        [
            'path' => 'PluginsEnvelope.install.verified.unsettled[].now.caused_by',
            'because' => 'The rest of a check\'s finding. The screen names the check by its title, which is how the health screen lists it, and the health screen draws the whole finding.',
        ],
        [
            'path' => 'PluginsEnvelope.install.verified.unsettled[].now.check',
            'because' => 'The rest of a check\'s finding. The screen names the check by its title, which is how the health screen lists it, and the health screen draws the whole finding.',
        ],
        [
            'path' => 'PluginsEnvelope.install.verified.unsettled[].now.onset',
            'because' => 'The rest of a check\'s finding. The screen names the check by its title, which is how the health screen lists it, and the health screen draws the whole finding.',
        ],
        [
            'path' => 'PluginsEnvelope.install.verified.unsettled[].now.origin',
            'because' => 'The rest of a check\'s finding. The screen names the check by its title, which is how the health screen lists it, and the health screen draws the whole finding.',
        ],
        [
            'path' => 'PluginsEnvelope.install.verified.unsettled[].now.said',
            'because' => 'The rest of a check\'s finding. The screen names the check by its title, which is how the health screen lists it, and the health screen draws the whole finding.',
        ],
        [
            'path' => 'PluginsEnvelope.install.verified.unsettled[].now.service',
            'because' => 'The rest of a check\'s finding. The screen names the check by its title, which is how the health screen lists it, and the health screen draws the whole finding.',
        ],
        [
            'path' => 'PluginsEnvelope.install.verified.unsettled[].now.service_name',
            'because' => 'The rest of a check\'s finding. The screen names the check by its title, which is how the health screen lists it, and the health screen draws the whole finding.',
        ],
        [
            'path' => 'PluginsEnvelope.install.verified.unsettled[].now.verdict',
            'because' => 'The rest of a check\'s finding. The screen names the check by its title, which is how the health screen lists it, and the health screen draws the whole finding.',
        ],
        [
            'path' => 'PluginsEnvelope.install.would.adapters',
            'because' => 'Which of lemonfiber\'s adapters the plugin\'s own services name. The adapter each recipe step reaches through is read and drawn (`N20-R6`); the services\' own adapters drive the stack\'s wiring, and nothing about them is agreed to here.',
        ],
        [
            'path' => 'PluginsEnvelope.install.would.contributions',
            'because' => 'The rows a plugin adds to registers lemonfiber already runs, such as its checks. No requirement asks the plugins screen to list them, and each is drawn where its register is.',
        ],
        [
            'path' => 'PluginsEnvelope.install.would.declared.claims',
            'because' => 'Every capability the plugin claims. What an install would leave contested is read and drawn; a claim that contests nothing changes nothing the operator chooses.',
        ],
        [
            'path' => 'PluginsEnvelope.install.would.declared.overrides',
            'because' => 'Every bundled setting the plugin declares it may change, as its record keeps it. An install\'s account carries the same list as `install.overrides`, which is read and drawn before the yes.',
        ],
        [
            'path' => 'PluginsEnvelope.install.would.declared.reaches',
            'because' => 'Every destination outside the plugin\'s own services a recipe could reach. Each recipe\'s steps and pairs are read and drawn with their destinations (`N20-R1`, `N20-R5`), which say the same with what goes there.',
        ],
        [
            'path' => 'PluginsEnvelope.install.would.declared.secrets',
            'because' => 'Every credential the plugin says it will hold, with no value. `N25-R13` asks for each secret on the plugin with whether a value is held and where it came from, which this list does not carry: it waits for the settings read the SDK does not offer yet — `N1-R17`.',
        ],
        [
            'path' => 'PluginsEnvelope.install.would.description',
            'because' => 'What the plugin does, in its author\'s words. No requirement asks for it here, and its name and version say which plugin it is.',
        ],
        [
            'path' => 'PluginsEnvelope.install.would.installed_at',
            'because' => 'When it was installed, in whole seconds since the epoch. The record (`N11`) draws when each change was made; the plugins screen asks nothing of the time.',
        ],
        [
            'path' => 'PluginsEnvelope.install.would.provides',
            'because' => 'Every core capability the plugin\'s services fill. Removing a plugin reads it, to say what would be left unfilled (`N25-R6`), and removal comes with updating, after installing.',
        ],
        [
            'path' => 'PluginsEnvelope.install.would.run_proofs',
            'because' => 'The proofs that run the image\'s own program, as the plugin declares them, kept so a proof again can run them without its source. What running one came to arrives in `proofs` beside the others, which is what is read and drawn.',
        ],
        [
            'path' => 'PluginsEnvelope.install.would.recipes[].steps[].adapter.owner',
            'because' => 'Whose adapter a step reaches through, which the contract fixes to lemonfiber. The screen says each adapter is lemonfiber\'s and not the plugin\'s (`N20-R6`), the one answer the field can give.',
        ],
        [
            'path' => 'PluginsEnvelope.installed[].adapters',
            'because' => 'Which of lemonfiber\'s adapters the plugin\'s own services name. The adapter each recipe step reaches through is read and drawn (`N20-R6`); the services\' own adapters drive the stack\'s wiring, and nothing about them is agreed to here.',
        ],
        [
            'path' => 'PluginsEnvelope.installed[].contributions',
            'because' => 'The rows a plugin adds to registers lemonfiber already runs, such as its checks. No requirement asks the plugins screen to list them, and each is drawn where its register is.',
        ],
        [
            'path' => 'PluginsEnvelope.installed[].declared.claims',
            'because' => 'Every capability the plugin claims. What an install would leave contested is read and drawn; a claim that contests nothing changes nothing the operator chooses.',
        ],
        [
            'path' => 'PluginsEnvelope.installed[].declared.overrides',
            'because' => 'Every bundled setting the plugin declares it may change, as its record keeps it. An install\'s account carries the same list as `install.overrides`, which is read and drawn before the yes.',
        ],
        [
            'path' => 'PluginsEnvelope.installed[].declared.reaches',
            'because' => 'Every destination outside the plugin\'s own services a recipe could reach. Each recipe\'s steps and pairs are read and drawn with their destinations (`N20-R1`, `N20-R5`), which say the same with what goes there.',
        ],
        [
            'path' => 'PluginsEnvelope.installed[].declared.secrets',
            'because' => 'Every credential the plugin says it will hold, with no value. `N25-R13` asks for each secret on the plugin with whether a value is held and where it came from, which this list does not carry: it waits for the settings read the SDK does not offer yet — `N1-R17`.',
        ],
        [
            'path' => 'PluginsEnvelope.installed[].description',
            'because' => 'What the plugin does, in its author\'s words. No requirement asks for it here, and its name and version say which plugin it is.',
        ],
        [
            'path' => 'PluginsEnvelope.installed[].installed_at',
            'because' => 'When it was installed, in whole seconds since the epoch. The record (`N11`) draws when each change was made; the plugins screen asks nothing of the time.',
        ],
        [
            'path' => 'PluginsEnvelope.installed[].provides',
            'because' => 'Every core capability the plugin\'s services fill. Removing a plugin reads it, to say what would be left unfilled (`N25-R6`), and removal comes with updating, after installing.',
        ],
        [
            'path' => 'PluginsEnvelope.installed[].run_proofs',
            'because' => 'As `PluginsEnvelope.install.would.run_proofs`: what a proof again runs is answered in that proof\'s own account, which is read and drawn (`N25-R15`).',
        ],
        [
            'path' => 'PluginsEnvelope.installed[].recipes[].steps[].adapter.owner',
            'because' => 'Whose adapter a step reaches through, which the contract fixes to lemonfiber. The screen says each adapter is lemonfiber\'s and not the plugin\'s (`N20-R6`), the one answer the field can give.',
        ],
        [
            'path' => 'PluginsEnvelope.rehearsed',
            'because' => 'Whether the run was a dry run of the whole stack. A plugin install asked without an offer is its reading and leaves this false; a reading is told by nothing having been recorded or put back, which is read.',
        ],
        [
            'path' => 'PluginsEnvelope.sources[].from',
            'because' => 'The source the record says a plugin came from. The listing reads it off each plugin\'s own record, `installed[].from`, and draws it there.',
        ],
        [
            'path' => 'PluginsEnvelope.substituted',
            'because' => 'Every capability the operator chose an installed plugin\'s service to fill. The Connections screen draws what fills each capability; the plugins screen asks nothing of it.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.against',
            'because' => 'As `PluginsEnvelope.install.against`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.proofs[].came_to.declared[].constraint',
            'because' => 'As `PluginsEnvelope.install.proofs[].came_to.declared[].constraint`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.proofs[].came_to.declared[].fixture',
            'because' => 'As `PluginsEnvelope.install.proofs[].came_to.declared[].fixture`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.proofs[].came_to.declared[].held',
            'because' => 'As `PluginsEnvelope.install.proofs[].came_to.declared[].held`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.proofs[].came_to.declared[].place',
            'because' => 'As `PluginsEnvelope.install.proofs[].came_to.declared[].place`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.proofs[].of',
            'because' => 'As `PluginsEnvelope.install.proofs[].of`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.asks',
            'because' => 'As `PluginsEnvelope.install.asks`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.recipes_ran',
            'because' => 'As `PluginsEnvelope.install.recipes_ran`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.verified.broke[].before',
            'because' => 'As `PluginsEnvelope.install.verified.broke[].before`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.verified.broke[].now.category',
            'because' => 'As `PluginsEnvelope.install.verified.broke[].now.category`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.verified.broke[].now.caused_by',
            'because' => 'As `PluginsEnvelope.install.verified.broke[].now.caused_by`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.verified.broke[].now.check',
            'because' => 'As `PluginsEnvelope.install.verified.broke[].now.check`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.verified.broke[].now.onset',
            'because' => 'As `PluginsEnvelope.install.verified.broke[].now.onset`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.verified.broke[].now.origin',
            'because' => 'As `PluginsEnvelope.install.verified.broke[].now.origin`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.verified.broke[].now.said',
            'because' => 'As `PluginsEnvelope.install.verified.broke[].now.said`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.verified.broke[].now.service',
            'because' => 'As `PluginsEnvelope.install.verified.broke[].now.service`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.verified.broke[].now.service_name',
            'because' => 'As `PluginsEnvelope.install.verified.broke[].now.service_name`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.verified.broke[].now.verdict',
            'because' => 'As `PluginsEnvelope.install.verified.broke[].now.verdict`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.verified.unsettled[].before',
            'because' => 'As `PluginsEnvelope.install.verified.unsettled[].before`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.verified.unsettled[].now.category',
            'because' => 'As `PluginsEnvelope.install.verified.unsettled[].now.category`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.verified.unsettled[].now.caused_by',
            'because' => 'As `PluginsEnvelope.install.verified.unsettled[].now.caused_by`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.verified.unsettled[].now.check',
            'because' => 'As `PluginsEnvelope.install.verified.unsettled[].now.check`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.verified.unsettled[].now.onset',
            'because' => 'As `PluginsEnvelope.install.verified.unsettled[].now.onset`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.verified.unsettled[].now.origin',
            'because' => 'As `PluginsEnvelope.install.verified.unsettled[].now.origin`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.verified.unsettled[].now.said',
            'because' => 'As `PluginsEnvelope.install.verified.unsettled[].now.said`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.verified.unsettled[].now.service',
            'because' => 'As `PluginsEnvelope.install.verified.unsettled[].now.service`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.verified.unsettled[].now.service_name',
            'because' => 'As `PluginsEnvelope.install.verified.unsettled[].now.service_name`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.verified.unsettled[].now.verdict',
            'because' => 'As `PluginsEnvelope.install.verified.unsettled[].now.verdict`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.would.adapters',
            'because' => 'As `PluginsEnvelope.install.would.adapters`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.would.contributions',
            'because' => 'As `PluginsEnvelope.install.would.contributions`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.would.declared.claims',
            'because' => 'As `PluginsEnvelope.install.would.declared.claims`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.would.declared.overrides',
            'because' => 'As `PluginsEnvelope.install.would.declared.overrides`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.would.declared.reaches',
            'because' => 'As `PluginsEnvelope.install.would.declared.reaches`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.would.declared.secrets',
            'because' => 'As `PluginsEnvelope.install.would.declared.secrets`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.would.description',
            'because' => 'As `PluginsEnvelope.install.would.description`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.would.installed_at',
            'because' => 'As `PluginsEnvelope.install.would.installed_at`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.would.provides',
            'because' => 'As `PluginsEnvelope.install.would.provides`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.would.run_proofs',
            'because' => 'As `PluginsEnvelope.install.would.run_proofs`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.would.recipes[].steps[].adapter.owner',
            'because' => 'As `PluginsEnvelope.install.would.recipes[].steps[].adapter.owner`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.installed[].services[].api',
            'because' => 'What else was placed for one of the plugin\'s services. Its id is read, to list the settings and secrets its services read (`N25-R12`, `N25-R13`); updating and removing name the services they stop from lists of their own, and nothing else here asks for this.',
        ],
        [
            'path' => 'PluginsEnvelope.installed[].services[].asks',
            'because' => 'What else was placed for one of the plugin\'s services. Its id is read, to list the settings and secrets its services read (`N25-R12`, `N25-R13`); updating and removing name the services they stop from lists of their own, and nothing else here asks for this.',
        ],
        [
            'path' => 'PluginsEnvelope.installed[].services[].asks_for',
            'because' => 'What else was placed for one of the plugin\'s services. Its id is read, to list the settings and secrets its services read (`N25-R12`, `N25-R13`); updating and removing name the services they stop from lists of their own, and nothing else here asks for this.',
        ],
        [
            'path' => 'PluginsEnvelope.installed[].services[].config_path',
            'because' => 'What else was placed for one of the plugin\'s services. Its id is read, to list the settings and secrets its services read (`N25-R12`, `N25-R13`); updating and removing name the services they stop from lists of their own, and nothing else here asks for this.',
        ],
        [
            'path' => 'PluginsEnvelope.installed[].services[].description',
            'because' => 'What else was placed for one of the plugin\'s services. Its id is read, to list the settings and secrets its services read (`N25-R12`, `N25-R13`); updating and removing name the services they stop from lists of their own, and nothing else here asks for this.',
        ],
        [
            'path' => 'PluginsEnvelope.installed[].services[].digest',
            'because' => 'What else was placed for one of the plugin\'s services. Its id is read, to list the settings and secrets its services read (`N25-R12`, `N25-R13`); updating and removing name the services they stop from lists of their own, and nothing else here asks for this.',
        ],
        [
            'path' => 'PluginsEnvelope.installed[].services[].fields',
            'because' => 'What else was placed for one of the plugin\'s services. Its id is read, to list the settings and secrets its services read (`N25-R12`, `N25-R13`); updating and removing name the services they stop from lists of their own, and nothing else here asks for this.',
        ],
        [
            'path' => 'PluginsEnvelope.installed[].services[].fronts',
            'because' => 'What else was placed for one of the plugin\'s services. Its id is read, to list the settings and secrets its services read (`N25-R12`, `N25-R13`); updating and removing name the services they stop from lists of their own, and nothing else here asks for this.',
        ],
        [
            'path' => 'PluginsEnvelope.installed[].services[].health',
            'because' => 'What else was placed for one of the plugin\'s services. Its id is read, to list the settings and secrets its services read (`N25-R12`, `N25-R13`); updating and removing name the services they stop from lists of their own, and nothing else here asks for this.',
        ],
        [
            'path' => 'PluginsEnvelope.installed[].services[].hidden',
            'because' => 'What else was placed for one of the plugin\'s services. Its id is read, to list the settings and secrets its services read (`N25-R12`, `N25-R13`); updating and removing name the services they stop from lists of their own, and nothing else here asks for this.',
        ],
        [
            'path' => 'PluginsEnvelope.installed[].services[].icon',
            'because' => 'What else was placed for one of the plugin\'s services. Its id is read, to list the settings and secrets its services read (`N25-R12`, `N25-R13`); updating and removing name the services they stop from lists of their own, and nothing else here asks for this.',
        ],
        [
            'path' => 'PluginsEnvelope.installed[].services[].image',
            'because' => 'What else was placed for one of the plugin\'s services. Its id is read, to list the settings and secrets its services read (`N25-R12`, `N25-R13`); updating and removing name the services they stop from lists of their own, and nothing else here asks for this.',
        ],
        [
            'path' => 'PluginsEnvelope.installed[].services[].listens',
            'because' => 'What else was placed for one of the plugin\'s services. Its id is read, to list the settings and secrets its services read (`N25-R12`, `N25-R13`); updating and removing name the services they stop from lists of their own, and nothing else here asks for this.',
        ],
        [
            'path' => 'PluginsEnvelope.installed[].services[].media_types',
            'because' => 'What else was placed for one of the plugin\'s services. Its id is read, to list the settings and secrets its services read (`N25-R12`, `N25-R13`); updating and removing name the services they stop from lists of their own, and nothing else here asks for this.',
        ],
        [
            'path' => 'PluginsEnvelope.installed[].services[].name',
            'because' => 'What else was placed for one of the plugin\'s services. Its id is read, to list the settings and secrets its services read (`N25-R12`, `N25-R13`); updating and removing name the services they stop from lists of their own, and nothing else here asks for this.',
        ],
        [
            'path' => 'PluginsEnvelope.installed[].services[].native',
            'because' => 'What else was placed for one of the plugin\'s services. Its id is read, to list the settings and secrets its services read (`N25-R12`, `N25-R13`); updating and removing name the services they stop from lists of their own, and nothing else here asks for this.',
        ],
        [
            'path' => 'PluginsEnvelope.installed[].services[].networks',
            'because' => 'What else was placed for one of the plugin\'s services. Its id is read, to list the settings and secrets its services read (`N25-R12`, `N25-R13`); updating and removing name the services they stop from lists of their own, and nothing else here asks for this.',
        ],
        [
            'path' => 'PluginsEnvelope.installed[].services[].provides',
            'because' => 'What else was placed for one of the plugin\'s services. Its id is read, to list the settings and secrets its services read (`N25-R12`, `N25-R13`); updating and removing name the services they stop from lists of their own, and nothing else here asks for this.',
        ],
        [
            'path' => 'PluginsEnvelope.installed[].services[].reached',
            'because' => 'What else was placed for one of the plugin\'s services. Its id is read, to list the settings and secrets its services read (`N25-R12`, `N25-R13`); updating and removing name the services they stop from lists of their own, and nothing else here asks for this.',
        ],
        [
            'path' => 'PluginsEnvelope.installed[].services[].reaches',
            'because' => 'What else was placed for one of the plugin\'s services. Its id is read, to list the settings and secrets its services read (`N25-R12`, `N25-R13`); updating and removing name the services they stop from lists of their own, and nothing else here asks for this.',
        ],
        [
            'path' => 'PluginsEnvelope.installed[].services[].replaces',
            'because' => 'What else was placed for one of the plugin\'s services. Its id is read, to list the settings and secrets its services read (`N25-R12`, `N25-R13`); updating and removing name the services they stop from lists of their own, and nothing else here asks for this.',
        ],
        [
            'path' => 'PluginsEnvelope.installed[].services[].shape',
            'because' => 'What else was placed for one of the plugin\'s services. Its id is read, to list the settings and secrets its services read (`N25-R12`, `N25-R13`); updating and removing name the services they stop from lists of their own, and nothing else here asks for this.',
        ],
        [
            'path' => 'PluginsEnvelope.installed[].services[].speaks',
            'because' => 'What else was placed for one of the plugin\'s services. Its id is read, to list the settings and secrets its services read (`N25-R12`, `N25-R13`); updating and removing name the services they stop from lists of their own, and nothing else here asks for this.',
        ],
        [
            'path' => 'PluginsEnvelope.installed[].services[].starting',
            'because' => 'What else was placed for one of the plugin\'s services. Its id is read, to list the settings and secrets its services read (`N25-R12`, `N25-R13`); updating and removing name the services they stop from lists of their own, and nothing else here asks for this.',
        ],
        [
            'path' => 'PluginsEnvelope.installed[].services[].tag',
            'because' => 'What else was placed for one of the plugin\'s services. Its id is read, to list the settings and secrets its services read (`N25-R12`, `N25-R13`); updating and removing name the services they stop from lists of their own, and nothing else here asks for this.',
        ],
        [
            'path' => 'PluginsEnvelope.installed[].services[].takes_data',
            'because' => 'What else was placed for one of the plugin\'s services. Its id is read, to list the settings and secrets its services read (`N25-R12`, `N25-R13`); updating and removing name the services they stop from lists of their own, and nothing else here asks for this.',
        ],
        [
            'path' => 'PluginsEnvelope.install.would.services[].api',
            'because' => 'As `PluginsEnvelope.installed[].services[].api`: the record an install would settle is the record of an installed plugin, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.install.would.services[].asks',
            'because' => 'As `PluginsEnvelope.installed[].services[].asks`: the record an install would settle is the record of an installed plugin, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.install.would.services[].asks_for',
            'because' => 'As `PluginsEnvelope.installed[].services[].asks_for`: the record an install would settle is the record of an installed plugin, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.install.would.services[].config_path',
            'because' => 'As `PluginsEnvelope.installed[].services[].config_path`: the record an install would settle is the record of an installed plugin, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.install.would.services[].description',
            'because' => 'As `PluginsEnvelope.installed[].services[].description`: the record an install would settle is the record of an installed plugin, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.install.would.services[].digest',
            'because' => 'As `PluginsEnvelope.installed[].services[].digest`: the record an install would settle is the record of an installed plugin, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.install.would.services[].fields',
            'because' => 'As `PluginsEnvelope.installed[].services[].fields`: the record an install would settle is the record of an installed plugin, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.install.would.services[].fronts',
            'because' => 'As `PluginsEnvelope.installed[].services[].fronts`: the record an install would settle is the record of an installed plugin, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.install.would.services[].health',
            'because' => 'As `PluginsEnvelope.installed[].services[].health`: the record an install would settle is the record of an installed plugin, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.install.would.services[].hidden',
            'because' => 'As `PluginsEnvelope.installed[].services[].hidden`: the record an install would settle is the record of an installed plugin, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.install.would.services[].icon',
            'because' => 'As `PluginsEnvelope.installed[].services[].icon`: the record an install would settle is the record of an installed plugin, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.install.would.services[].image',
            'because' => 'As `PluginsEnvelope.installed[].services[].image`: the record an install would settle is the record of an installed plugin, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.install.would.services[].listens',
            'because' => 'As `PluginsEnvelope.installed[].services[].listens`: the record an install would settle is the record of an installed plugin, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.install.would.services[].media_types',
            'because' => 'As `PluginsEnvelope.installed[].services[].media_types`: the record an install would settle is the record of an installed plugin, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.install.would.services[].name',
            'because' => 'As `PluginsEnvelope.installed[].services[].name`: the record an install would settle is the record of an installed plugin, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.install.would.services[].native',
            'because' => 'As `PluginsEnvelope.installed[].services[].native`: the record an install would settle is the record of an installed plugin, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.install.would.services[].networks',
            'because' => 'As `PluginsEnvelope.installed[].services[].networks`: the record an install would settle is the record of an installed plugin, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.install.would.services[].provides',
            'because' => 'As `PluginsEnvelope.installed[].services[].provides`: the record an install would settle is the record of an installed plugin, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.install.would.services[].reached',
            'because' => 'As `PluginsEnvelope.installed[].services[].reached`: the record an install would settle is the record of an installed plugin, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.install.would.services[].reaches',
            'because' => 'As `PluginsEnvelope.installed[].services[].reaches`: the record an install would settle is the record of an installed plugin, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.install.would.services[].replaces',
            'because' => 'As `PluginsEnvelope.installed[].services[].replaces`: the record an install would settle is the record of an installed plugin, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.install.would.services[].shape',
            'because' => 'As `PluginsEnvelope.installed[].services[].shape`: the record an install would settle is the record of an installed plugin, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.install.would.services[].speaks',
            'because' => 'As `PluginsEnvelope.installed[].services[].speaks`: the record an install would settle is the record of an installed plugin, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.install.would.services[].starting',
            'because' => 'As `PluginsEnvelope.installed[].services[].starting`: the record an install would settle is the record of an installed plugin, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.install.would.services[].tag',
            'because' => 'As `PluginsEnvelope.installed[].services[].tag`: the record an install would settle is the record of an installed plugin, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.install.would.services[].takes_data',
            'because' => 'As `PluginsEnvelope.installed[].services[].takes_data`: the record an install would settle is the record of an installed plugin, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.would.services[].api',
            'because' => 'As `PluginsEnvelope.installed[].services[].api`: the record an install would settle is the record of an installed plugin, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.would.services[].asks',
            'because' => 'As `PluginsEnvelope.installed[].services[].asks`: the record an install would settle is the record of an installed plugin, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.would.services[].asks_for',
            'because' => 'As `PluginsEnvelope.installed[].services[].asks_for`: the record an install would settle is the record of an installed plugin, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.would.services[].config_path',
            'because' => 'As `PluginsEnvelope.installed[].services[].config_path`: the record an install would settle is the record of an installed plugin, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.would.services[].description',
            'because' => 'As `PluginsEnvelope.installed[].services[].description`: the record an install would settle is the record of an installed plugin, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.would.services[].digest',
            'because' => 'As `PluginsEnvelope.installed[].services[].digest`: the record an install would settle is the record of an installed plugin, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.would.services[].fields',
            'because' => 'As `PluginsEnvelope.installed[].services[].fields`: the record an install would settle is the record of an installed plugin, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.would.services[].fronts',
            'because' => 'As `PluginsEnvelope.installed[].services[].fronts`: the record an install would settle is the record of an installed plugin, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.would.services[].health',
            'because' => 'As `PluginsEnvelope.installed[].services[].health`: the record an install would settle is the record of an installed plugin, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.would.services[].hidden',
            'because' => 'As `PluginsEnvelope.installed[].services[].hidden`: the record an install would settle is the record of an installed plugin, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.would.services[].icon',
            'because' => 'As `PluginsEnvelope.installed[].services[].icon`: the record an install would settle is the record of an installed plugin, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.would.services[].image',
            'because' => 'As `PluginsEnvelope.installed[].services[].image`: the record an install would settle is the record of an installed plugin, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.would.services[].listens',
            'because' => 'As `PluginsEnvelope.installed[].services[].listens`: the record an install would settle is the record of an installed plugin, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.would.services[].media_types',
            'because' => 'As `PluginsEnvelope.installed[].services[].media_types`: the record an install would settle is the record of an installed plugin, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.would.services[].name',
            'because' => 'As `PluginsEnvelope.installed[].services[].name`: the record an install would settle is the record of an installed plugin, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.would.services[].native',
            'because' => 'As `PluginsEnvelope.installed[].services[].native`: the record an install would settle is the record of an installed plugin, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.would.services[].networks',
            'because' => 'As `PluginsEnvelope.installed[].services[].networks`: the record an install would settle is the record of an installed plugin, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.would.services[].provides',
            'because' => 'As `PluginsEnvelope.installed[].services[].provides`: the record an install would settle is the record of an installed plugin, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.would.services[].reached',
            'because' => 'As `PluginsEnvelope.installed[].services[].reached`: the record an install would settle is the record of an installed plugin, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.would.services[].reaches',
            'because' => 'As `PluginsEnvelope.installed[].services[].reaches`: the record an install would settle is the record of an installed plugin, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.would.services[].replaces',
            'because' => 'As `PluginsEnvelope.installed[].services[].replaces`: the record an install would settle is the record of an installed plugin, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.would.services[].shape',
            'because' => 'As `PluginsEnvelope.installed[].services[].shape`: the record an install would settle is the record of an installed plugin, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.would.services[].speaks',
            'because' => 'As `PluginsEnvelope.installed[].services[].speaks`: the record an install would settle is the record of an installed plugin, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.would.services[].starting',
            'because' => 'As `PluginsEnvelope.installed[].services[].starting`: the record an install would settle is the record of an installed plugin, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.would.services[].tag',
            'because' => 'As `PluginsEnvelope.installed[].services[].tag`: the record an install would settle is the record of an installed plugin, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.would.services[].takes_data',
            'because' => 'As `PluginsEnvelope.installed[].services[].takes_data`: the record an install would settle is the record of an installed plugin, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.demand',
            'because' => 'What running one of a plugin\'s demand recipes came to. Nothing this app sends asks for a recipe '
                . 'by name, so no answer it reads carries one, and no `N` requirement offers running one. Raise it '
                . 'against the spec before reading it, which is `N1-R17`.',
        ],
        [
            'path' => 'PluginsEnvelope.install.verified.broke[].now.disruptive',
            'because' => 'The rest of a check\'s finding. The screen names the check by its title, which is how the health '
                . 'screen lists it, and the health screen draws the whole finding.',
        ],
        [
            'path' => 'PluginsEnvelope.install.verified.unsettled[].now.disruptive',
            'because' => 'The rest of a check\'s finding. The screen names the check by its title, which is how the health '
                . 'screen lists it, and the health screen draws the whole finding.',
        ],
        [
            'path' => 'PluginsEnvelope.install.would.recipes[].inputs',
            'because' => 'Every value a recipe asks the operator for when it runs. The screen says under every install and '
                . 'update that this app gives a recipe no values of the operator\'s, and that a plugin whose recipes '
                . 'ask for one is installed at the web console or the terminal; no `N` requirement asks the app to '
                . 'take them.',
        ],
        [
            'path' => 'PluginsEnvelope.install.would.recipes[].on',
            'because' => 'When a recipe runs: during the install, or only when asked for by name. The screen draws every '
                . 'recipe the plugin declares, each step in the order it gives (`N20-R5`), and no `N` requirement '
                . 'asks it to say when one runs. Raise it against the spec before reading it, which is `N1-R17`.',
        ],
        [
            'path' => 'PluginsEnvelope.install.would.request_proofs',
            'because' => 'The proofs that ask one of the plugin\'s services a question, as the plugin declares them, kept '
                . 'so a proof again can ask without its source. What asking one came to arrives in `proofs` beside '
                . 'the others, which is what is read and drawn.',
        ],
        [
            'path' => 'PluginsEnvelope.installed[].recipes[].inputs',
            'because' => 'As `PluginsEnvelope.install.would.recipes[].inputs`: the record an install would settle is the '
                . 'record of an installed plugin, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.installed[].recipes[].on',
            'because' => 'As `PluginsEnvelope.install.would.recipes[].on`: the record an install would settle is the '
                . 'record of an installed plugin, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.installed[].request_proofs',
            'because' => 'As `PluginsEnvelope.installed[].run_proofs`: what a proof again asks is answered in that proof\'s '
                . 'own account, which is read and drawn (`N25-R15`).',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.verified.broke[].now.disruptive',
            'because' => 'The rest of a check\'s finding. The screen names the check by its title, which is how the health '
                . 'screen lists it, and the health screen draws the whole finding.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.verified.unsettled[].now.disruptive',
            'because' => 'The rest of a check\'s finding. The screen names the check by its title, which is how the health '
                . 'screen lists it, and the health screen draws the whole finding.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.would.recipes[].inputs',
            'because' => 'As `PluginsEnvelope.install.would.recipes[].inputs`: an update carries the new version\'s install '
                . 'account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.would.recipes[].on',
            'because' => 'As `PluginsEnvelope.install.would.recipes[].on`: an update carries the new version\'s install '
                . 'account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.would.request_proofs',
            'because' => 'As `PluginsEnvelope.install.would.request_proofs`: an update carries the new version\'s install '
                . 'account whole, and the one reader reads both.',
        ],
    ];
}
