<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function array_map;
use function expect;
use function implode;
use function it;
use function iterator_to_array;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\CheckIsUnnamed;
use Modules\Kernel\Api\HowItStands;
use Modules\Kernel\Api\Remedy;
use Modules\Kernel\Api\RemedySaysNothing;
use Modules\Kernel\Api\Severity;
use Modules\Kernel\Api\SummaryCountsBelowNothing;
use Modules\Kernel\Api\TheHealthSummary;
use Modules\Sdk\Api\Summaries;
use Modules\Sdk\Api\SummaryIsUnreadable;

use function sprintf;

/**
 * A `dashboard` envelope holding whatever health summary the case is about.
 *
 * Built by hand rather than fetched, because what is under test is what
 * happens when the wire says something the contract does not allow. The rest
 * of the dashboard is left out: the reader reads the summary and nothing else,
 * and `HearingContractTest` holds a whole dashboard against the contract.
 *
 * `dashboard` is stood in for and not judged: every case here is a payload the contract refuses, which is the point of it.
 *
 * @param array<mixed> $health
 *
 * @return Envelope<mixed>
 */
function aDashboardSaying(array $health): Envelope
{
    return new Envelope(1, 'dashboard', ['health' => $health]);
}

/**
 * One affected item with every part the reader insists on.
 *
 * @return array<string, mixed>
 */
function anAffectedRow(string $check = 'disk.space', string $severity = 'warning'): array
{
    return [
        'check' => $check,
        'severity' => $severity,
        'summary' => 'The disk is nearly full',
        'meaning' => 'New downloads will start failing soon',
        'remedies' => ['Make room', 'Add a disk'],
        'downstream' => ['Imports are failing'],
    ];
}

/**
 * One affected item missing one part.
 *
 * @return array<string, mixed>
 */
function anAffectedRowWithout(string $field): array
{
    $row = anAffectedRow();
    unset($row[$field]);

    return $row;
}

/**
 * A summary with every field, and whichever changes a case makes.
 *
 * @param array<string, mixed> $changed
 *
 * @return array<string, mixed>
 */
function aHealthSummary(array $changed = []): array
{
    return [
        'standing' => 'degraded',
        'wanting_attention' => 1,
        'worst' => 'The disk is nearly full',
        'affected' => [anAffectedRow()],
        ...$changed,
    ];
}

/**
 * What a reading refused, by the message it refused with.
 *
 * @param Envelope<mixed> $envelope
 */
function whyTheSummaryWasRefused(Envelope $envelope): string
{
    try {
        Summaries::in($envelope);
    } catch (SummaryIsUnreadable $why) {
        return $why->getMessage();
    }

    return 'nothing was refused';
}

it('reads every field of the summary, and every field of every affected item', function (): void {
    $summary = Summaries::in(aDashboardSaying(aHealthSummary([
        'affected' => [anAffectedRow(), anAffectedRow('vpn.leak', 'critical')],
        'wanting_attention' => 2,
    ])));
    $items = iterator_to_array($summary, preserve_keys: false);

    expect($summary->standing())->toBe(HowItStands::Degraded)
        ->and($summary->wantingAttention())->toBe(2)
        ->and($summary->worst())->toBe('The disk is nearly full')
        ->and($items)->toHaveCount(2)
        ->and($items[0]->check()->shown())->toBe('disk.space')
        ->and($items[0]->severity())->toBe(Severity::Warning)
        ->and($items[0]->summary())->toBe('The disk is nearly full')
        ->and($items[0]->meaning())->toBe('New downloads will start failing soon')
        ->and(implode('|', array_map(static fn(Remedy $remedy): string => $remedy->action(), iterator_to_array($items[0]->remedies(), preserve_keys: false))))->toBe('Make room|Add a disk')
        ->and(iterator_to_array($items[0]->downstream(), preserve_keys: false))->toBe(['Imports are failing'])
        ->and($items[1]->check()->shown())->toBe('vpn.leak')
        ->and($items[1]->severity())->toBe(Severity::Critical);
});

it('reads every word the core has for how a stack stands', function (): void {
    foreach (HowItStands::cases() as $standing) {
        expect(Summaries::in(aDashboardSaying(aHealthSummary(['standing' => $standing->value])))->standing())->toBe($standing);
    }
});

it('reads a worst thing left out, or sent as nothing, as nothing named', function (): void {
    $leftOut = aHealthSummary();
    unset($leftOut['worst']);

    expect(Summaries::in(aDashboardSaying($leftOut))->worst())->toBe('')
        ->and(Summaries::in(aDashboardSaying(aHealthSummary(['worst' => null])))->worst())->toBe('');
});

it('reads an item that took nothing down with it, and one with nothing to try', function (): void {
    $summary = Summaries::in(aDashboardSaying(aHealthSummary([
        'affected' => [[...anAffectedRow(), 'remedies' => [], 'downstream' => []]],
    ])));
    $item = iterator_to_array($summary, preserve_keys: false)[0];

    expect($item->remedies()->count())->toBe(0)
        ->and($item->downstream()->count())->toBe(0);
});

it('refuses a payload that is not a payload, and one with no summary in it', function (): void {
    expect(whyTheSummaryWasRefused(new Envelope(1, 'dashboard', 'a sentence')))->toContain('`data`')
        ->and(whyTheSummaryWasRefused(new Envelope(1, 'dashboard', [])))->toContain('`health`')
        ->and(whyTheSummaryWasRefused(new Envelope(1, 'dashboard', ['health' => 'fine'])))->toContain('`health`');
});

it('refuses a summary missing a field, or holding one that is not what the contract says', function (): void {
    $each = [
        'standing' => 3,
        'wanting_attention' => 'one',
        'affected' => 'none',
    ];

    foreach ($each as $field => $wrong) {
        $missing = aHealthSummary();
        unset($missing[$field]);

        expect(whyTheSummaryWasRefused(aDashboardSaying($missing)))->toContain(sprintf('`%s`', $field));

        expect(whyTheSummaryWasRefused(aDashboardSaying(aHealthSummary([$field => $wrong]))))
            ->toContain(sprintf('`%s`', $field));
    }

    expect(whyTheSummaryWasRefused(aDashboardSaying(aHealthSummary(['worst' => 7]))))->toContain('`worst`');
});

it('refuses a word for how a stack stands that it does not read, naming the words it does', function (): void {
    expect(whyTheSummaryWasRefused(aDashboardSaying(aHealthSummary(['standing' => 'splendid']))))
        ->toContain('`splendid`')
        ->toContain('`healthy`, `stopped`, `unconfigured`, `advisory`, `degraded`, `broken`, `critical`, `unknown`');
});

it('refuses an item it cannot read, naming the item rather than always the first', function (): void {
    $each = [
        'an item that is not an item' => ['a sentence', 'affected'],
        'an item with no check' => [anAffectedRowWithout('check'), 'check'],
        'an item with no severity' => [anAffectedRowWithout('severity'), 'severity'],
        'an item with no summary' => [anAffectedRowWithout('summary'), 'summary'],
        'an item with no meaning' => [anAffectedRowWithout('meaning'), 'meaning'],
        'an item with no remedies' => [anAffectedRowWithout('remedies'), 'remedies'],
        'an item whose remedies are not a list' => [[...anAffectedRow(), 'remedies' => 'restart'], 'remedies'],
        'an item with a remedy that is not text' => [[...anAffectedRow(), 'remedies' => [3]], 'remedies'],
        'an item with nothing downstream said' => [anAffectedRowWithout('downstream'), 'downstream'],
        'an item whose downstream is not text' => [[...anAffectedRow(), 'downstream' => [false]], 'downstream'],
    ];

    foreach ($each as [$row, $field]) {
        expect(whyTheSummaryWasRefused(aDashboardSaying(aHealthSummary(['affected' => [anAffectedRow(), $row]]))))
            ->toContain('Affected item 1 ')
            ->toContain(sprintf('`%s`', $field));
    }
});

it('refuses a field of an item that is there and is not text', function (): void {
    foreach (['check', 'severity', 'summary', 'meaning'] as $field) {
        expect(whyTheSummaryWasRefused(aDashboardSaying(aHealthSummary(['affected' => [[...anAffectedRow(), $field => 9]]]))))
            ->toContain('Affected item 0 ')
            ->toContain(sprintf('`%s`', $field));
    }
});

it('refuses a severity it does not read, naming the item and the words it does', function (): void {
    expect(whyTheSummaryWasRefused(aDashboardSaying(aHealthSummary(['affected' => [anAffectedRow(), anAffectedRow(severity: 'dire')]]))))
        ->toContain('Affected item 1 ')
        ->toContain('`dire`')
        ->toContain('`critical`, `error`, `warning`, `advisory`');
});

it('hands a blank check, a blank remedy and a count below nothing to the values that refuse them', function (): void {
    expect(static fn(): TheHealthSummary => Summaries::in(aDashboardSaying(aHealthSummary(['affected' => [anAffectedRow('  ')]]))))
        ->toThrow(CheckIsUnnamed::class)
        ->and(static fn(): TheHealthSummary => Summaries::in(aDashboardSaying(aHealthSummary(['affected' => [[...anAffectedRow(), 'remedies' => ['  ']]]]))))
        ->toThrow(RemedySaysNothing::class)
        ->and(static fn(): TheHealthSummary => Summaries::in(aDashboardSaying(aHealthSummary(['wanting_attention' => -1]))))
        ->toThrow(SummaryCountsBelowNothing::class);
});
