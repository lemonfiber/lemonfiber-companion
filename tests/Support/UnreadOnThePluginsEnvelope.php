<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * The paths this app does not read on the `plugins` envelope, and why.
 *
 * Apart from {@see UnreadOnEnvelopesMToZ} for its size. An update carries an
 * install's whole account and each of an update and a removal carries a
 * rollback report, so most rows here repeat an install row's decision at a
 * second place, and say so.
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
            'path' => 'PluginsEnvelope.install.reversed.reversed[].action.id',
            'because' => 'For `UndoEnvelope.reversed[].action.id`\'s reason: the same rollback report, carried inside a plugin install that did not hold.',
        ],
        [
            'path' => 'PluginsEnvelope.install.reversed.reversed[].action.resource',
            'because' => 'For `UndoEnvelope.reversed[].action.resource`\'s reason: the same rollback report, carried inside a plugin install that did not hold.',
        ],
        [
            'path' => 'PluginsEnvelope.install.reversed.reversed[].action.key',
            'because' => 'For `UndoEnvelope.reversed[].action.key`\'s reason: the same rollback report, carried inside a plugin install that did not hold.',
        ],
        [
            'path' => 'PluginsEnvelope.install.reversed.reversed[].action.value',
            'because' => 'For `UndoEnvelope.reversed[].action.value`\'s reason: the same rollback report, carried inside a plugin install that did not hold.',
        ],
        [
            'path' => 'PluginsEnvelope.install.reversed.reversed[].action.wrote',
            'because' => 'For `UndoEnvelope.reversed[].action.wrote`\'s reason: the same rollback report, carried inside a plugin install that did not hold.',
        ],
        [
            'path' => 'PluginsEnvelope.install.reversed.reversed[].action.path',
            'because' => 'For `UndoEnvelope.reversed[].action.path`\'s reason: the same rollback report, carried inside a plugin install that did not hold.',
        ],
        [
            'path' => 'PluginsEnvelope.install.reversed.reversed[].action.owner',
            'because' => 'For `UndoEnvelope.reversed[].action.owner`\'s reason: the same rollback report, carried inside a plugin install that did not hold.',
        ],
        [
            'path' => 'PluginsEnvelope.install.reversed.reversed[].action.written',
            'because' => 'For `UndoEnvelope.reversed[].action.written`\'s reason: the same rollback report, carried inside a plugin install that did not hold.',
        ],
        [
            'path' => 'PluginsEnvelope.install.reversed.reversed[].action.previous',
            'because' => 'For `UndoEnvelope.reversed[].action.previous`\'s reason: the same rollback report, carried inside a plugin install that did not hold.',
        ],
        [
            'path' => 'PluginsEnvelope.install.reversed.reversed[].action.current',
            'because' => 'For `UndoEnvelope.reversed[].action.current`\'s reason: the same rollback report, carried inside a plugin install that did not hold.',
        ],
        [
            'path' => 'PluginsEnvelope.install.reversed.reversed[].action.field',
            'because' => 'For `UndoEnvelope.reversed[].action.field`\'s reason: the same rollback report, carried inside a plugin install that did not hold.',
        ],
        [
            'path' => 'PluginsEnvelope.install.reversed.reversed[].action.name',
            'because' => 'For `UndoEnvelope.reversed[].action.name`\'s reason: the same rollback report, carried inside a plugin install that did not hold.',
        ],
        [
            'path' => 'PluginsEnvelope.install.reversed.reversed[].action.recorded',
            'because' => 'For `UndoEnvelope.reversed[].action.recorded`\'s reason: the same rollback report, carried inside a plugin install that did not hold.',
        ],
        [
            'path' => 'PluginsEnvelope.install.reversed.reversed[].action.earlier',
            'because' => 'For `UndoEnvelope.reversed[].action.earlier`\'s reason: the same rollback report, carried inside a plugin install that did not hold.',
        ],
        [
            'path' => 'PluginsEnvelope.install.reversed.reversed[].action.filed',
            'because' => 'For `UndoEnvelope.reversed[].action.filed`\'s reason: the same rollback report, carried inside a plugin install that did not hold.',
        ],
        [
            'path' => 'PluginsEnvelope.install.reversed.reversed[].action.kept',
            'because' => 'For `UndoEnvelope.reversed[].action.kept`\'s reason: the same rollback report, carried inside a plugin install that did not hold.',
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
            'path' => 'PluginsEnvelope.removal.went_back.reversed[].action.id',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.id`: what taking the plugin\'s changes off the machine came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.removal.went_back.reversed[].action.resource',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.resource`: what taking the plugin\'s changes off the machine came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.removal.went_back.reversed[].action.key',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.key`: what taking the plugin\'s changes off the machine came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.removal.went_back.reversed[].action.value',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.value`: what taking the plugin\'s changes off the machine came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.removal.went_back.reversed[].action.wrote',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.wrote`: what taking the plugin\'s changes off the machine came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.removal.went_back.reversed[].action.path',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.path`: what taking the plugin\'s changes off the machine came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.removal.went_back.reversed[].action.owner',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.owner`: what taking the plugin\'s changes off the machine came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.removal.went_back.reversed[].action.written',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.written`: what taking the plugin\'s changes off the machine came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.removal.went_back.reversed[].action.previous',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.previous`: what taking the plugin\'s changes off the machine came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.removal.went_back.reversed[].action.current',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.current`: what taking the plugin\'s changes off the machine came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.removal.went_back.reversed[].action.field',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.field`: what taking the plugin\'s changes off the machine came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.removal.went_back.reversed[].action.name',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.name`: what taking the plugin\'s changes off the machine came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.removal.went_back.reversed[].action.recorded',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.recorded`: what taking the plugin\'s changes off the machine came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.removal.went_back.reversed[].action.earlier',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.earlier`: what taking the plugin\'s changes off the machine came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.removal.went_back.reversed[].action.filed',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.filed`: what taking the plugin\'s changes off the machine came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.removal.went_back.reversed[].action.kept',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.kept`: what taking the plugin\'s changes off the machine came to is the same rollback report, read by the same reader.',
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
            'path' => 'PluginsEnvelope.update.went_back.reversed[].action.id',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.id`: what putting the installed version\'s changes back came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.update.went_back.reversed[].action.resource',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.resource`: what putting the installed version\'s changes back came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.update.went_back.reversed[].action.key',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.key`: what putting the installed version\'s changes back came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.update.went_back.reversed[].action.value',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.value`: what putting the installed version\'s changes back came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.update.went_back.reversed[].action.wrote',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.wrote`: what putting the installed version\'s changes back came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.update.went_back.reversed[].action.path',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.path`: what putting the installed version\'s changes back came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.update.went_back.reversed[].action.owner',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.owner`: what putting the installed version\'s changes back came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.update.went_back.reversed[].action.written',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.written`: what putting the installed version\'s changes back came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.update.went_back.reversed[].action.previous',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.previous`: what putting the installed version\'s changes back came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.update.went_back.reversed[].action.current',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.current`: what putting the installed version\'s changes back came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.update.went_back.reversed[].action.field',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.field`: what putting the installed version\'s changes back came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.update.went_back.reversed[].action.name',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.name`: what putting the installed version\'s changes back came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.update.went_back.reversed[].action.recorded',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.recorded`: what putting the installed version\'s changes back came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.update.went_back.reversed[].action.earlier',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.earlier`: what putting the installed version\'s changes back came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.update.went_back.reversed[].action.filed',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.filed`: what putting the installed version\'s changes back came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.update.went_back.reversed[].action.kept',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.kept`: what putting the installed version\'s changes back came to is the same rollback report, read by the same reader.',
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
            'path' => 'PluginsEnvelope.update.install.reversed.reversed[].action.id',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.id`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.reversed.reversed[].action.resource',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.resource`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.reversed.reversed[].action.key',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.key`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.reversed.reversed[].action.value',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.value`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.reversed.reversed[].action.wrote',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.wrote`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.reversed.reversed[].action.path',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.path`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.reversed.reversed[].action.owner',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.owner`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.reversed.reversed[].action.written',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.written`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.reversed.reversed[].action.previous',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.previous`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.reversed.reversed[].action.current',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.current`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.reversed.reversed[].action.field',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.field`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.reversed.reversed[].action.name',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.name`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.reversed.reversed[].action.recorded',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.recorded`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.reversed.reversed[].action.earlier',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.earlier`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.reversed.reversed[].action.filed',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.filed`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.reversed.reversed[].action.kept',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.kept`: an update carries the new version\'s install account whole, and the one reader reads both.',
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
    ];
}
