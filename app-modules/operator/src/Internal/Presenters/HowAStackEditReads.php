<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\ALineOfADiff;
use Modules\Kernel\Api\AStackEdit;
use Modules\Kernel\Api\TheStackEdits;
use Modules\Operator\Internal\ViewModels\ADiffLineAsShown;
use Modules\Operator\Internal\ViewModels\AnEditAsShown;

/**
 * A stack file the operator edited, as the lines a screen draws.
 *
 * One place for it, because three screens draw the one contract type: a file a
 * reset reverts, and a file a start or an update leaves as the operator set it.
 * Every line is marked as the operator's or lemonfiber's with the keys the
 * legend under a kept file names.
 */
final readonly class HowAStackEditReads
{
    /** The operator's line, `-` and the line. */
    public const string THEIRS = 'stacks.edits.theirs';

    /** lemonfiber's line, `+` and the line. */
    public const string LEMONFIBERS = 'stacks.edits.lemonfibers';

    /**
     * Every file, in the stack's order.
     *
     * @return list<AnEditAsShown>
     */
    public function these(TheStackEdits $edits): array
    {
        $shown = [];

        foreach ($edits as $edit) {
            $shown[] = $this->of($edit);
        }

        return $shown;
    }

    /** One file, with every line marked as whose it is. */
    public function of(AStackEdit $edit): AnEditAsShown
    {
        $lines = [];

        foreach ($edit as $line) {
            $lines[] = $this->line($line);
        }

        return new AnEditAsShown(path: $edit->path(), lines: $lines);
    }

    /** One line, marked as the operator's or lemonfiber's. */
    private function line(ALineOfADiff $line): ADiffLineAsShown
    {
        return new ADiffLineAsShown(
            said: $line->isTheirs() ? self::THEIRS : self::LEMONFIBERS,
            line: $line->text(),
        );
    }
}
