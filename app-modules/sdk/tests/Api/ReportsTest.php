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
use Modules\Kernel\Api\Conclusion;
use Modules\Kernel\Api\Finding;
use Modules\Kernel\Api\Overall;
use Modules\Kernel\Api\Report;
use Modules\Sdk\Api\ReportIsUnreadable;
use Modules\Sdk\Api\Reports;

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
