<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\ALineOfADiff;
use Modules\Kernel\Api\AnEditReverted;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheReset;
use Modules\Operator\Internal\ViewModels\ADiffLineAsShown;
use Modules\Operator\Internal\ViewModels\AnEditAsShown;
use Modules\Operator\Internal\ViewModels\ARefusalAsShown;
use Modules\Operator\Internal\ViewModels\AResetAsShown;
use Modules\Operator\Internal\ViewModels\AResetAsWorded;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;

/**
 * Putting the configuration back, as the template draws it, before the yes or after it.
 *
 * Which of the two is the presenter's own, because it decides how a job still
 * running, one the stack no longer knows, and a refusal are worded: asking
 * what would change is not putting anything back. How a report is worded is
 * the report's, because the stack's `confirmed` is what says whether anything
 * was written: a preview is worded in the conditional, and only a report the
 * stack says it carried out is worded in the past.
 */
final readonly class HowAResetReads
{
    private function __construct(private bool $afterTheYes) {}

    /** Asking what putting the configuration back would revert, before anything is agreed to. */
    public static function beforeTheYes(): self
    {
        return new self(afterTheYes: false);
    }

    /** Following the yes to what it reverted. */
    public static function afterTheYes(): self
    {
        return new self(afterTheYes: true);
    }

    /** The stack is still at it. */
    public function running(): AResetAsShown
    {
        return $this->following(HowTheReadingWent::itCameBack(), isWorking: true);
    }

    /** The stack has no outcome for it any more, which is not the same as it failing. */
    public function ended(): AResetAsShown
    {
        return $this->following(HowTheReadingWent::itCameBack(), hasEnded: true);
    }

    /** Asking, or asking after it, met this instead. */
    public function met(Obstacle $why): AResetAsShown
    {
        return $this->following(HowTheReadingWent::somethingStopped($why));
    }

    /** This device no longer holds a session for the stack. */
    public function signedOut(): AResetAsShown
    {
        return $this->following(HowTheReadingWent::theSessionEnded());
    }

    /** The stack refused, and this is why, in its words. */
    public function refused(ARefusalInItsWords $why): AResetAsShown
    {
        return $this->following(HowTheReadingWent::itCameBack(), refused: new HowARefusalReads()->inItsWords($why));
    }

    /** The stack's report: what putting the configuration back would revert, or reverted. */
    public function reported(TheReset $reset): AResetAsShown
    {
        $edits = [];

        foreach ($reset->edits() as $edit) {
            $edits[] = $this->edit($edit);
        }

        return new AResetAsShown(
            went: HowTheReadingWent::itCameBack(),
            isWorking: false,
            hasEnded: false,
            refused: null,
            isReported: true,
            changesNothing: $reset->changesNothing(),
            mayBeAgreedTo: ! $this->afterTheYes && $reset->mayBeAgreedTo(),
            words: $this->words($reset->wasCarriedOut()),
            edits: $edits,
            connections: [...$reset->connections()],
        );
    }

    /** One file, with every line marked as whose it is. */
    private function edit(AnEditReverted $edit): AnEditAsShown
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
            said: $line->isTheirs() ? 'stacks.reset.theirs' : 'stacks.reset.lemonfibers',
            line: $line->text(),
        );
    }

    /**
     * Every sentence's key: the report's in its own tense, and the following's in this presenter's.
     *
     * `$carriedOut` is the report's `confirmed` where there is a report, and
     * the side of the yes this presenter is on where there is none.
     */
    private function words(bool $carriedOut): AResetAsWorded
    {
        [$working, $ended, $refusal] = $this->afterTheYes
            ? ['stacks.reset.putting_back', 'stacks.reset.no_outcome', 'stacks.reset.refused']
            : ['stacks.reset.asking', 'stacks.reset.no_preview', 'stacks.reset.refused_preview'];

        [$heading, $files, $noFile, $noLine, $connections, $noConnection, $nothing] = $carriedOut
            ? [
                'stacks.reset.put_back',
                'stacks.reset.reverted_files',
                'stacks.reset.reverted_no_file',
                'stacks.reset.differed_in_no_line',
                'stacks.reset.reverted_connections',
                'stacks.reset.reverted_no_connection',
                'stacks.reset.changed_nothing',
            ]
            : [
                'stacks.reset.a_preview',
                'stacks.reset.would_revert_files',
                'stacks.reset.would_revert_no_file',
                'stacks.reset.differs_in_no_line',
                'stacks.reset.would_revert_connections',
                'stacks.reset.would_revert_no_connection',
                'stacks.reset.would_change_nothing',
            ];

        return new AResetAsWorded(
            heading: $heading,
            files: $files,
            noFile: $noFile,
            noLine: $noLine,
            connections: $connections,
            noConnection: $noConnection,
            nothing: $nothing,
            working: $working,
            ended: $ended,
            refusal: $refusal,
        );
    }

    /** A state with no report to draw. */
    private function following(
        HowTheReadingWent $went,
        bool $isWorking = false,
        bool $hasEnded = false,
        ?ARefusalAsShown $refused = null,
    ): AResetAsShown {
        return new AResetAsShown(
            went: $went,
            isWorking: $isWorking,
            hasEnded: $hasEnded,
            refused: $refused,
            isReported: false,
            changesNothing: false,
            mayBeAgreedTo: false,
            words: $this->words($this->afterTheYes),
            edits: [],
            connections: [],
        );
    }
}
