<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * Putting the configuration back, before or after the yes, flattened for a template.
 *
 * One model for both readings, because both are the same report: the files
 * whose edits go and the connections that go with them. What differs is the
 * tense, and the tense is the stack's `confirmed` rather than which reading
 * asked, so each sentence arrives here as a catalogue key the presenter chose
 * from the report itself. A preview is never worded as having happened.
 */
final readonly class AResetAsShown
{
    /**
     * @param WhereTheWorkStands   $stands           where the work stands, and whether the stack could be asked at all
     * @param ?ARefusalAsShown    $refused        why the stack refused, in its words, and nothing unless it refused
     * @param bool                $isReported     whether there is a report to draw
     * @param bool                $changesNothing whether the report reverts no file and no connection
     * @param bool                $mayBeAgreedTo  whether it is a preview a yes may be given for
     * @param AResetAsWorded      $words          the catalogue keys each sentence is drawn from
     * @param list<AnEditAsShown> $edits          the files whose edits go, or went
     * @param list<string>        $connections    the connections that go, or went, with them
     */
    public function __construct(
        public WhereTheWorkStands $stands,
        public ?ARefusalAsShown $refused,
        public bool $isReported,
        public bool $changesNothing,
        public bool $mayBeAgreedTo,
        public AResetAsWorded $words,
        public array $edits,
        public array $connections,
    ) {}
}
