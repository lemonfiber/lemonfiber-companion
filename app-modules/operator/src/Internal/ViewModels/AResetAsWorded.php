<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * The catalogue keys one reading of putting the configuration back is drawn in.
 *
 * Two tenses: the report's, which is the stack's `confirmed`, and the
 * following's, which is whether the yes was sent. A preview's report is
 * worded in the conditional and a carried-out one in the past, and a job still
 * running is worded by what was asked of it.
 */
final readonly class AResetAsWorded
{
    /**
     * @param string $heading      what heads the report
     * @param string $files        what leads the files
     * @param string $noFile       what stands for no file
     * @param string $noLine       what stands for a file whose difference no line shows
     * @param string $connections  what leads the connections
     * @param string $noConnection what stands for no connection
     * @param string $nothing      what stands for a report reverting nothing at all
     * @param string $working      what is said while the stack is at it
     * @param string $ended        what is said where the stack has no outcome for it any more
     * @param string $refusal      what heads the stack's refusal
     */
    public function __construct(
        public string $heading,
        public string $files,
        public string $noFile,
        public string $noLine,
        public string $connections,
        public string $noConnection,
        public string $nothing,
        public string $working,
        public string $ended,
        public string $refusal,
    ) {}
}
