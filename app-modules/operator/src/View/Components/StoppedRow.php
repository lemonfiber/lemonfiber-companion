<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

use function implode;
use function is_string;

use Modules\Operator\Internal\ViewModels\AStoppageAsShown;

use function view;

/**
 * One row of what stopped moving, drawn the same under either heading: a port row.
 *
 * The item, or the cause several items share, is the row's name. Under it, on
 * one line: which kind of stopped it is, how many items share the cause where
 * it stands for several, what the service said was in the way in its own
 * words, and how long it has been that way. A row standing for one item leads
 * to where that item got to, and is read aloud as that road first. One component because a stuck row and a slow row
 * are one shape, told apart by the tone their heading hands them.
 */
final class StoppedRow extends Component
{
    /** How many items share the cause, where the row stands for several. */
    private const string SHARED = 'health.stopped_stands_for';

    /** What the service said was in the way, in its own words. */
    private const string BLOCKING = 'health.stopped_blocking';

    /** Where following one item leads, said for a row that stands for one. */
    private const string ROAD = 'health.trace.road_in';

    /** Everything said about it, on the one line under its name. */
    public readonly string $said;

    /** What a screen reader says for the row: where it leads, where it leads anywhere, and what is said about it. */
    public readonly string $named;

    /**
     * @param AStoppageAsShown $row   the row, as the presenter flattened it
     * @param string           $trace where following it leads, or empty where the row stands for several items
     * @param string           $tone  the tone its port is drawn in, a `Modules\Design\View\Tone` value
     */
    public function __construct(
        Translator $catalogue,
        public readonly AStoppageAsShown $row,
        public readonly string $trace,
        public readonly string $tone,
    ) {
        $kind = $catalogue->get($row->kindSaid);
        $said = [is_string($kind) ? $kind : $row->kindSaid];

        if ($row->items > 1) {
            $said[] = $catalogue->choice(self::SHARED, $row->items);
        }

        if ($row->blocking !== '') {
            $blocking = $catalogue->get(self::BLOCKING, ['words' => $row->blocking]);
            $said[] = is_string($blocking) ? $blocking : $row->blocking;
        }

        $said[] = $catalogue->choice($row->heldSaid, $row->heldCount);

        $this->said = implode(PortRow::BETWEEN, $said);

        $road = $catalogue->get(self::ROAD, ['item' => $row->name]);
        $this->named = $trace === '' || ! is_string($road) ? '' : implode(PortRow::BETWEEN, [$road, $this->said]);
    }

    public function render(): View
    {
        return view('operator::components.stopped-row');
    }
}
