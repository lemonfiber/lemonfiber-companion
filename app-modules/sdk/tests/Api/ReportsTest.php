<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function array_map;
use function expect;
use function it;
use function iterator_to_array;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Modules\Kernel\Api\Category;
use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\CheckGaveNoReason;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Conclusion;
use Modules\Kernel\Api\EnvelopeIsNotRead;
use Modules\Kernel\Api\Finding;
use Modules\Kernel\Api\Overall;
use Modules\Kernel\Api\Remedies;
use Modules\Kernel\Api\Report;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\ServiceIsUnnamed;
use Modules\Kernel\Api\Severity;
use Modules\Kernel\Api\Standing;
use Modules\Kernel\Api\WhatItSaysUnderneath;
use Modules\Sdk\Api\ReportIsUnreadable;
use Modules\Sdk\Api\Reports;

use function sprintf;

/**
 * A `doctor` envelope holding whatever the case under test is about.
 *
 * Built by hand rather than fetched, because what is being tested is what
 * happens when the wire says something the contract does not allow — which a
 * client that honoured the contract could never produce.
 *
 * @param array<mixed> $data
 *
 * @return Envelope<mixed>
 */
function doctorSaying(array $data): Envelope
{
    return new Envelope(1, 'doctor', $data);
}

/**
 * One finding, with every part named.
 *
 * Composed rather than mutated after the fact: a fixture built once and then
 * indexed into is an `array<mixed>` the analyser refuses to reach through, and
 * reaching through it anyway is exactly the habit this parser exists to stop.
 *
 * @param array<string, mixed> $verdict
 *
 * @return array<string, mixed>
 */
function aFinding(string $check, string $category, string $title, array $verdict): array
{
    return ['check' => $check, 'category' => $category, 'title' => $title, 'verdict' => $verdict];
}

/** @return array<string, mixed> */
function aFailure(string $category = 'vpn'): array
{
    return aFinding('vpn.egress-match', $category, 'Torrent traffic leaves through the tunnel', [
        'outcome' => 'fail',
        'code' => 'VPN-3',
        'severity' => 'critical',
        'state' => 'guided',
        'summary' => 'Traffic left directly',
        'meaning' => 'Your address was visible',
        'remedies' => [['action' => 'Restart the tunnel']],
    ]);
}

/** @return array<string, mixed> */
function aPass(string $category = 'storage'): array
{
    return aFinding('storage.room', $category, 'Room to grow', ['outcome' => 'pass', 'note' => '412 GB free']);
}

/**
 * @param list<mixed> $findings
 *
 * @return array<string, mixed>
 */
function aRun(string $overall, array $findings): array
{
    return ['overall' => $overall, 'findings' => $findings];
}

/** @return array<string, mixed> */
function aWholeRun(): array
{
    return aRun('broken', [aFailure(), aPass()]);
}

/** @return list<string> */
function checksIn(Report $report): array
{
    return array_map(
        static fn(Finding $finding): string => $finding->check()->shown(),
        iterator_to_array($report->findings(), preserve_keys: false),
    );
}

it('reads a whole run into the shape the screens work in', function (): void {
    $report = Reports::in(doctorSaying(aWholeRun()));

    expect($report->overall())->toBe(Overall::Broken);
    expect($report->findings()->count())->toBe(2);
});

it('keeps the findings in the order the checks produced them', function (): void {
    // The order is information — two findings where one caused the other read
    // differently the other way round — so the parse preserves it. Which order
    // a person should read them in is `WorstFirst`'s decision, and it is not
    // made here.
    expect(checksIn(Reports::in(doctorSaying(aWholeRun()))))
        ->toBe(['vpn.egress-match', 'storage.room']);
});

it('takes the tag off the verdict and leaves what it carries', function (): void {
    // The health module's decision, carried out rather than restated: a
    // failure's whole problem and a pass's note are both on the wire, and
    // `Finding` takes neither, because what a warning said is read differently
    // on every screen and there is no screen yet.
    $findings = iterator_to_array(Reports::in(doctorSaying(aWholeRun()))->findings(), preserve_keys: false);

    expect($findings[0]->conclusion())->toBe(Conclusion::Failed);
    expect($findings[0]->category())->toBe(Category::Vpn);
    expect($findings[0]->title())->toBe('Torrent traffic leaves through the tunnel');
    expect($findings[1]->conclusion())->toBe(Conclusion::Passed);
});

it('reads a healthy run with nothing in it', function (): void {
    $report = Reports::in(doctorSaying(aRun('healthy', [])));

    expect($report->overall())->toBe(Overall::Healthy);
    expect($report->findings()->count())->toBe(0);
});

it('refuses a verdict this app does not read, naming both sides', function (): void {
    $verdict = ['outcome' => 'inconclusive', 'reason' => 'the daemon did not answer'];
    $data = aRun('broken', [aFinding('vpn.egress-match', 'vpn', 'Egress', $verdict)]);

    expect(fn(): Report => Reports::in(doctorSaying($data)))
        ->toThrow(ReportIsUnreadable::class, 'inconclusive');
});

it('names the conclusions it does read, from the enum rather than a sentence', function (): void {
    $verdict = ['outcome' => 'inconclusive', 'reason' => 'the daemon did not answer'];
    $data = aRun('broken', [aFinding('vpn.egress-match', 'vpn', 'Egress', $verdict)]);

    expect(fn(): Report => Reports::in(doctorSaying($data)))
        ->toThrow(ReportIsUnreadable::class, '`unverified`');
});

it('refuses a category it does not know', function (): void {
    $data = aRun('broken', [aFailure(), aPass('weather')]);

    expect(fn(): Report => Reports::in(doctorSaying($data)))
        ->toThrow(ReportIsUnreadable::class, 'weather');
});

it('refuses a word for the run it does not know', function (): void {
    $data = aRun('fine', [aFailure()]);

    expect(fn(): Report => Reports::in(doctorSaying($data)))
        ->toThrow(ReportIsUnreadable::class, 'fine');
});

it('refuses a field that is there and is not text', function (): void {
    // Present and wrong is a different fault from absent, and the message says
    // the same thing for both on purpose: what a reader needs is the field, and
    // "it is a number" is detail the wire already makes visible.
    $data = ['overall' => 7, 'findings' => []];

    expect(fn(): Report => Reports::in(doctorSaying($data)))
        ->toThrow(ReportIsUnreadable::class, 'overall');
});

it('refuses the whole report rather than showing it one row short', function (): void {
    // The shape that does damage: nine findings where ten ran reads as a stack
    // with one fewer problem, and nothing on the screen says a row was dropped.
    $data = aRun('broken', [aFailure(), 'just a string']);

    expect(fn(): Report => Reports::in(doctorSaying($data)))
        ->toThrow(ReportIsUnreadable::class, 'Finding 1');
});

it('refuses findings that are not a list at all', function (): void {
    $data = ['overall' => 'broken', 'findings' => 'none'];

    expect(fn(): Report => Reports::in(doctorSaying($data)))
        ->toThrow(ReportIsUnreadable::class, 'Finding 0');
});

it('refuses a run with no findings key rather than inventing an empty one', function (): void {
    // Absent is not empty. A report that lost its findings in transit and one
    // where nothing had anything to say are different facts, and only the
    // second is the outcome the product is for.
    expect(fn(): Report => Reports::in(doctorSaying(['overall' => 'healthy'])))
        ->toThrow(ReportIsUnreadable::class, 'findings');
});

it('refuses a finding missing a field rather than filling it in', function (): void {
    $data = aRun('broken', [['check' => 'vpn.egress-match', 'category' => 'vpn',
        'verdict' => ['outcome' => 'pass']]]);

    expect(fn(): Report => Reports::in(doctorSaying($data)))
        ->toThrow(ReportIsUnreadable::class, 'title');
});

it('refuses a finding with no verdict at all', function (): void {
    $data = aRun('broken', [['check' => 'vpn.egress-match', 'category' => 'vpn', 'title' => 'Egress']]);

    expect(fn(): Report => Reports::in(doctorSaying($data)))
        ->toThrow(ReportIsUnreadable::class, 'verdict');
});

it('refuses a verdict that is not an object', function (): void {
    $data = aRun('broken', [['check' => 'vpn.egress-match', 'category' => 'vpn',
        'title' => 'Egress', 'verdict' => 'fail']]);

    expect(fn(): Report => Reports::in(doctorSaying($data)))
        ->toThrow(ReportIsUnreadable::class, 'verdict');
});

it('refuses a payload that is not an object at all', function (): void {
    expect(fn(): Report => Reports::in(new Envelope(1, 'doctor', 'sorry')))
        ->toThrow(ReportIsUnreadable::class, 'data');
});

it('leaves the wrong kind to the client to report', function (): void {
    expect(fn(): Report => Reports::in(new Envelope(1, 'status', aWholeRun())))
        ->toThrow(UnexpectedKind::class);
});

it('N1-R13 — refuses an envelope in a wire version this app does not read', function (): void {
    // Asserted here and not only over `Wire`, because what is being pinned is
    // that this translator asks. A gate nothing calls is a gate.
    //
    // A report read out of an envelope this app does not understand is the worst
    // of the two: a screen shows a stack as healthy, or as broken, from fields
    // whose meaning moved.
    expect(fn(): Report => Reports::in(new Envelope(99, 'doctor', [])))
        ->toThrow(EnvelopeIsNotRead::class, 'version 99');
});

it('N2-R3 — reads the code, the meaning and the remedies off a failing verdict', function (): void {
    // All three cross the wire on the verdict, beside the outcome tag. Reading
    // the tag alone is enough to colour a row and not enough to act on it.
    $report = Reports::in(doctorSaying(aRun('broken', [aFailure()])));
    $said = [];

    foreach ($report->findings() as $finding) {
        $said[] = $finding->said()->either(
            nothingWrong: static fn(): Code => Code::of('nothing-wrong'),
            wentWrong: static fn(Code $code, string $meaning, Remedies $remedies): Code => Code::of(sprintf(
                '%s|%s|%d',
                $code->shown(),
                $meaning,
                $remedies->count(),
            )),
            couldNotSay: static fn(string $reason, Remedies $remedies): Code => Code::of(
                sprintf('could-not-say|%s|%d', $reason, $remedies->count()),
            ),
        );
    }

    expect($said[0]->shown())->toBe('VPN-3|Your address was visible|1');
});

it('N2-R3 — reads how much it matters and where it stands, off the same verdict', function (): void {
    // Both cross the wire beside the code, and both were dropped for as long as
    // this app had nowhere to put them. Severity is taken as sent rather than
    // worked out from the conclusion: a screen deciding for itself would be a
    // second opinion about a judgement the engine already made, and the engine
    // is the side that knows whether a failed check costs an afternoon or a
    // library.
    $report = Reports::in(doctorSaying(aRun('broken', [aFailure()])));
    $said = [];

    foreach ($report->findings() as $finding) {
        $said[] = $finding->said()->either(
            nothingWrong: static fn(): Code => Code::of('nothing-wrong'),
            wentWrong: static fn(
                Code $code,
                string $meaning,
                Remedies $remedies,
                Severity $severity,
                Standing $standing,
            ): Code => Code::of(sprintf('%s|%s', $severity->value, $standing->value)),
            couldNotSay: static fn(string $reason, Remedies $remedies): Code => Code::of(
                sprintf('could-not-say|%s|%d', $reason, $remedies->count()),
            ),
        );
    }

    expect($said[0]->shown())->toBe('critical|guided');
});

it('N2-R3 — refuses a severity this app cannot read rather than calling it advisory', function (): void {
    // The quiet arm is the wrong place to land. A critical finding whose word
    // did not parse would be shown as informational, and quiet is the one thing
    // it must not be — `D4`'s argument, and the same one the category makes.
    $verdict = ['outcome' => 'fail', 'code' => 'VPN-3', 'severity' => 'urgent', 'state' => 'guided', 'meaning' => 'Visible'];

    expect(fn(): Report => Reports::in(doctorSaying(aRun('broken', [
        aFinding('vpn.egress-match', 'vpn', 'Egress', $verdict),
    ]))))->toThrow(ReportIsUnreadable::class, 'urgent');
});

it('N2-R3 — refuses a standing this app cannot read rather than offering a button', function (): void {
    // Sharper than the severity case: the distinction between `actionable` and
    // `guided` decides whether a screen offers to do something, so a word this
    // app cannot read must not become the arm that offers one.
    $verdict = ['outcome' => 'fail', 'code' => 'VPN-3', 'severity' => 'error', 'state' => 'pending', 'meaning' => 'Visible'];

    expect(fn(): Report => Reports::in(doctorSaying(aRun('broken', [
        aFinding('vpn.egress-match', 'vpn', 'Egress', $verdict),
    ]))))->toThrow(ReportIsUnreadable::class, 'pending');
});

it('N2-R3 — a check that passed carries none of it', function (): void {
    $report = Reports::in(doctorSaying(aRun('healthy', [aPass()])));
    $said = [];

    foreach ($report->findings() as $finding) {
        $said[] = $finding->said()->either(
            nothingWrong: static fn(): Code => Code::of('nothing-wrong'),
            wentWrong: static fn(Code $code, string $meaning, Remedies $remedies): Code => Code::of(sprintf(
                '%s|%s|%d',
                $code->shown(),
                $meaning,
                $remedies->count(),
            )),
            couldNotSay: static fn(string $reason, Remedies $remedies): Code => Code::of(
                sprintf('could-not-say|%s|%d', $reason, $remedies->count()),
            ),
        );
    }

    expect($said[0]->shown())->toBe('nothing-wrong');
});

it('N2-R3 — a check that could not run carries why, and what to do to get an answer', function (): void {
    // This read as a passing check until the reader stopped sniffing for a
    // `code` key. `unverified` carries neither a code nor a severity, so the
    // presence test put it on the arm that means nothing is wrong — which is
    // the one mistake `Conclusion` splits its cases to prevent, made in the
    // reader instead of on the screen.
    $verdict = [
        'outcome' => 'unverified',
        'reason' => 'The container engine did not answer, so egress could not be established',
        'remedy' => ['action' => 'Start the container engine and check again'],
    ];
    $report = Reports::in(doctorSaying(aRun('broken', [aFinding('vpn.egress-match', 'vpn', 'Egress', $verdict)])));
    $said = [];

    foreach ($report->findings() as $finding) {
        $said[] = $finding->said()->either(
            nothingWrong: static fn(): Code => Code::of('nothing-wrong'),
            wentWrong: static fn(): Code => Code::of('went-wrong'),
            couldNotSay: static fn(string $reason, Remedies $remedies): Code => Code::of(
                sprintf('%s|%d', $reason, $remedies->count()),
            ),
        );
    }

    expect($said[0]->shown())
        ->toBe('The container engine did not answer, so egress could not be established|1');
});

it('N2-R3 — a skipped check carries why, and nothing to do about it', function (): void {
    // No remedy on the wire and none invented here. A prerequisite that was
    // absent is a fact about the machine, not something the core is asking
    // anybody to go and fix, and an empty list says so without a sentence.
    $verdict = ['outcome' => 'skipped', 'reason' => 'No VPN is configured, so there was nothing to check'];
    $report = Reports::in(doctorSaying(aRun('healthy', [aFinding('vpn.egress-match', 'vpn', 'Egress', $verdict)])));
    $said = [];

    foreach ($report->findings() as $finding) {
        $said[] = $finding->said()->either(
            nothingWrong: static fn(): Code => Code::of('nothing-wrong'),
            wentWrong: static fn(): Code => Code::of('went-wrong'),
            couldNotSay: static fn(string $reason, Remedies $remedies): Code => Code::of(
                sprintf('%s|%d', $reason, $remedies->count()),
            ),
        );
    }

    expect($said[0]->shown())->toBe('No VPN is configured, so there was nothing to check|0');
});

it('N2-R3 — refuses a check that could not run and said nothing about why', function (): void {
    // The same refusal a failure with no meaning gets, and for a sharper
    // reason: the row already says there is no answer, so a blank sentence
    // leaves an operator with a line that reads as a check that found nothing.
    $verdict = ['outcome' => 'unverified', 'reason' => '   ', 'remedy' => ['action' => 'Try again']];

    expect(fn(): Report => Reports::in(doctorSaying(aRun('broken', [
        aFinding('vpn.egress-match', 'vpn', 'Egress', $verdict),
    ]))))->toThrow(CheckGaveNoReason::class);
});

it('N2-R3 — refuses a remedy on an unverified check that is not a remedy', function (): void {
    $verdict = ['outcome' => 'unverified', 'reason' => 'The engine did not answer', 'remedy' => 'start it'];

    expect(fn(): Report => Reports::in(doctorSaying(aRun('broken', [
        aFinding('vpn.egress-match', 'vpn', 'Egress', $verdict),
    ]))))->toThrow(ReportIsUnreadable::class, 'remedy');
});

it('N2-R3 — a failure the core offered nothing for carries no remedies', function (): void {
    // Different from a failure whose remedies are an empty list, and read the
    // same way on purpose: both mean there is nothing to offer, and a screen
    // that told them apart would be showing the difference between the core
    // having no suggestion and the core saying so.
    $verdict = ['outcome' => 'fail', 'code' => 'VPN-3', 'severity' => 'critical', 'state' => 'guided', 'meaning' => 'Your address was visible'];
    $report = Reports::in(doctorSaying(aRun('broken', [aFinding('vpn.egress-match', 'vpn', 'Egress', $verdict)])));
    $counted = [];

    foreach ($report->findings() as $finding) {
        $counted[] = $finding->said()->either(
            nothingWrong: static fn(): Code => Code::of('nothing-wrong'),
            wentWrong: static fn(Code $code, string $meaning, Remedies $remedies): Code => Code::of(
                sprintf('%d', $remedies->count()),
            ),
            couldNotSay: static fn(string $reason, Remedies $remedies): Code => Code::of(
                sprintf('could-not-say|%s|%d', $reason, $remedies->count()),
            ),
        );
    }

    expect($counted[0]->shown())->toBe('0');
});

it('N2-R3 — refuses remedies that are not a list', function (): void {
    $verdict = ['outcome' => 'fail', 'code' => 'VPN-3', 'severity' => 'error', 'state' => 'guided', 'meaning' => 'Visible', 'remedies' => 'restart it'];

    expect(fn(): Report => Reports::in(doctorSaying(aRun('broken', [
        aFinding('vpn.egress-match', 'vpn', 'Egress', $verdict),
    ]))))->toThrow(ReportIsUnreadable::class);
});

it('N2-R3 — refuses a remedy that is not a remedy', function (): void {
    // A list of strings where a list of objects belongs. The core producing
    // this is a fault worth seeing where the payload is read, rather than as a
    // type error on somebody's screen.
    $verdict = ['outcome' => 'fail', 'code' => 'VPN-3', 'severity' => 'error', 'state' => 'guided', 'meaning' => 'Visible', 'remedies' => ['restart it']];

    expect(fn(): Report => Reports::in(doctorSaying(aRun('broken', [
        aFinding('vpn.egress-match', 'vpn', 'Egress', $verdict),
    ]))))->toThrow(ReportIsUnreadable::class);
});

it('N2-R3 — reads which service a finding is about', function (): void {
    // Beside the title rather than inside it. The same check runs against
    // whichever service fills a role, so a title naming one would be wrong on
    // the next machine — and an operator with nineteen services needs the name.
    $row = aFinding('vpn.egress-match', 'vpn', 'The tunnel', ['outcome' => 'pass']);
    $row['service'] = 'gluetun';
    $report = Reports::in(doctorSaying(aRun('healthy', [$row])));
    $named = [];

    foreach ($report->findings() as $finding) {
        $named[] = $finding->whatItIsAbout()->either(
            theMachine: static fn(): Code => Code::of('the-machine'),
            theService: static fn(ServiceId $service): Code => Code::of($service->named()),
        );
    }

    expect($named[0]->shown())->toBe('gluetun');
});

it('N2-R3 — a check about the machine names no service', function (): void {
    $report = Reports::in(doctorSaying(aRun('healthy', [aPass()])));
    $named = [];

    foreach ($report->findings() as $finding) {
        $named[] = $finding->whatItIsAbout()->either(
            theMachine: static fn(): Code => Code::of('the-machine'),
            theService: static fn(ServiceId $service): Code => Code::of($service->named()),
        );
    }

    expect($named[0]->shown())->toBe('the-machine');
});

it('N2-R3 — refuses a finding that claims a service and names none', function (): void {
    // Different from a finding that claims none: one is a check about the
    // machine and the other is a row with a fault in it. An operator shown a
    // blank where a service belongs learns less than one shown nothing.
    $row = aFinding('vpn.egress-match', 'vpn', 'The tunnel', ['outcome' => 'pass']);
    $row['service'] = '   ';

    expect(fn(): Report => Reports::in(doctorSaying(aRun('healthy', [$row]))))
        ->toThrow(ServiceIsUnnamed::class);
});

it('N2-R3 — reads which check explains a finding', function (): void {
    // The engine sets this after the run, because a check is independent by
    // construction and cannot see what any other found. It is the difference
    // between an operator reading five broken things and reading one.
    $row = aFinding('vpn.egress-match', 'vpn', 'Egress', ['outcome' => 'pass']);
    $row['caused_by'] = 'vpn.up';
    $report = Reports::in(doctorSaying(aRun('healthy', [$row])));
    $explained = [];

    foreach ($report->findings() as $finding) {
        $explained[] = $finding->whatExplainsIt()->either(
            alone: static fn(): Code => Code::of('stands-alone'),
            explained: static fn(Check $cause): Code => Code::of($cause->shown()),
        );
    }

    expect($explained[0]->shown())->toBe('vpn.up');
});

it('N2-R3 — a finding nothing explains stands alone', function (): void {
    $report = Reports::in(doctorSaying(aRun('healthy', [aPass()])));
    $explained = [];

    foreach ($report->findings() as $finding) {
        $explained[] = $finding->whatExplainsIt()->either(
            alone: static fn(): Code => Code::of('stands-alone'),
            explained: static fn(Check $cause): Code => Code::of($cause->shown()),
        );
    }

    expect($explained[0]->shown())->toBe('stands-alone');
});

it('names which finding it could not read, rather than blaming the envelope', function (): void {
    // The second one, deliberately. A refusal inside a finding names the
    // finding, because the envelope's words — *the doctor envelope has no
    // `title`* — are untrue of an envelope holding nine good findings and one
    // bad one, and give a reader nothing to look at. The position is the only
    // thing that makes the bad row findable, and a fixture whose bad row is
    // first passes whether or not the position is carried.
    $data = aRun('broken', [
        aFinding('storage.one-filesystem', 'storage', 'Disk', ['outcome' => 'pass']),
        ['check' => 'vpn.egress-match', 'category' => 'vpn', 'verdict' => ['outcome' => 'pass']],
    ]);

    expect(fn(): Report => Reports::in(doctorSaying($data)))
        ->toThrow(ReportIsUnreadable::class, 'Finding 1');
});

it('names the finding for a verdict field as well as for a finding field', function (): void {
    // The reads inside a verdict are finding-level too: a `severity` is a field
    // of one row, not of the report, so it names the row the same way. Asserted
    // separately because a verdict is read through its own helpers, which could
    // carry the position for the finding's own fields and drop it here.
    $data = aRun('broken', [
        aFinding('storage.one-filesystem', 'storage', 'Disk', ['outcome' => 'pass']),
        aFinding('vpn.egress-match', 'vpn', 'Egress', [
            'outcome' => 'fail',
            'severity' => 'error',
            'state' => 'guided',
            'meaning' => 'Traffic is leaving unprotected',
        ]),
    ]);

    expect(fn(): Report => Reports::in(doctorSaying($data)))
        ->toThrow(ReportIsUnreadable::class, 'Finding 1');
});

it('refuses a report with no overall at all, as against one it cannot read', function (): void {
    // Told apart from a word this app does not know, which is the case above:
    // one is a stack speaking a vocabulary this version has not learned, and
    // this is an envelope missing the field the whole report is judged by.
    expect(fn(): Report => Reports::in(doctorSaying(['findings' => []])))
        ->toThrow(ReportIsUnreadable::class, 'overall');
});

it('refuses a finding whose field is there and is not text', function (): void {
    // Present-and-wrong rather than absent. A presence check alone lets it
    // through, and a stack of a newer version is the likeliest thing to send
    // it — a title that became a structured object, say.
    $data = aRun('broken', [
        aFinding('storage.one-filesystem', 'storage', 'Disk', ['outcome' => 'pass']),
        ['check' => 'vpn.egress-match', 'category' => 'vpn', 'title' => 41,
            'verdict' => ['outcome' => 'pass']],
    ]);

    expect(fn(): Report => Reports::in(doctorSaying($data)))
        ->toThrow(ReportIsUnreadable::class, 'Finding 1');
});

it('G4-R4 — reads the technical detail a verdict carried', function (): void {
    $verdict = [
        'outcome' => 'fail',
        'code' => 'VPN-3',
        'severity' => 'critical',
        'state' => 'guided',
        'summary' => 'The tunnel is down',
        'meaning' => 'The tunnel is not carrying anything',
        'remedies' => [['action' => 'Restart the tunnel']],
        'detail' => 'dial tcp 10.0.0.4:8989: connect: connection refused',
    ];

    expect(whatOneFindingSaysUnderneath($verdict))
        ->toBe('dial tcp 10.0.0.4:8989: connect: connection refused');
});

it('G4-R4 — a verdict with no detail is one the core added nothing to', function (): void {
    // The one field here that does not refuse when it is absent. The contract
    // marks it optional, so missing is a core with nothing to add rather than a
    // conversation gone wrong — and `N2-R14` is about values a payload should
    // have carried.
    $verdict = [
        'outcome' => 'fail',
        'code' => 'VPN-3',
        'severity' => 'critical',
        'state' => 'guided',
        'summary' => 'The tunnel is down',
        'meaning' => 'The tunnel is not carrying anything',
        'remedies' => [['action' => 'Restart the tunnel']],
    ];

    expect(whatOneFindingSaysUnderneath($verdict))->toBe('nothing')
        ->and(whatOneFindingSaysUnderneath([...$verdict, 'detail' => null]))->toBe('nothing');
});

/**
 * What the one finding in a report says underneath.
 *
 * @param array<string, mixed> $verdict
 */
function whatOneFindingSaysUnderneath(array $verdict): string
{
    $report = Reports::in(doctorSaying(aRun('broken', [
        aFinding('vpn.egress', 'vpn', 'Egress', $verdict),
    ])));

    foreach ($report->findings() as $finding) {
        return $finding->said()->either(
            nothingWrong: static fn(): WhatWasAddedUnderneath => new WhatWasAddedUnderneath('nothing'),
            wentWrong: static fn(
                Code $code,
                string $meaning,
                Remedies $remedies,
                Severity $severity,
                Standing $standing,
                WhatItSaysUnderneath $underneath,
            ): WhatWasAddedUnderneath => new WhatWasAddedUnderneath($underneath->either(
                said: static fn(string $detail): WhatWasAddedUnderneath => new WhatWasAddedUnderneath($detail),
                none: static fn(): WhatWasAddedUnderneath => new WhatWasAddedUnderneath('nothing'),
            )->said),
            couldNotSay: static fn(): WhatWasAddedUnderneath => new WhatWasAddedUnderneath('nothing'),
        )->said;
    }

    return 'no finding at all';
}

/** One answer carried out of an `either()` arm, which hands back objects. */
final readonly class WhatWasAddedUnderneath
{
    public function __construct(public string $said) {}
}
