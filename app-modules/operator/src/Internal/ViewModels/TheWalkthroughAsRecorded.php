<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What a finished walkthrough reported, flattened for the template.
 *
 * A record, drawn whole: the lines arrive together once the walk has finished,
 * so a walk that ran while nobody was looking reads exactly as one that was
 * watched, and nothing on the screen pretends the lines are still arriving.
 */
final readonly class TheWalkthroughAsRecorded
{
    /**
     * @param string                        $item          what it walked, or empty where it never got as far as choosing
     * @param bool                          $alreadyHere   whether what was asked for was already here, its own outcome
     * @param string                        $shapeSaid     the catalogue key for which walk this was
     * @param string                        $stateSaid     the catalogue key for where it ended up
     * @param string                        $proves        what it set out to prove, as the stack said it
     * @param list<AWalkthroughLineAsShown> $lines         every line it said, in the order it said them
     * @param list<string>                  $suggestions   what could be walked instead, as the stack named them
     * @param bool                          $inBackground  whether the download was handed to the background
     * @param string                        $linkSaid      the catalogue key for what the import did, or empty where it never imported
     * @param list<AStepOnAsShown>          $next          what to do next, in order; empty where it names nothing
     * @param WhereItStoppedAsShown         $stopped       where and why it stopped, or that it did not
     */
    public function __construct(
        public string $item,
        public bool $alreadyHere,
        public string $shapeSaid,
        public string $stateSaid,
        public string $proves,
        public array $lines,
        public array $suggestions,
        public bool $inBackground,
        public string $linkSaid,
        public array $next,
        public WhereItStoppedAsShown $stopped,
    ) {}
}
