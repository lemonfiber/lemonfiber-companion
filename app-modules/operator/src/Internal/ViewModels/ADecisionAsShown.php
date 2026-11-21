<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use Modules\Operator\Internal\HowADecisionIsChanged;

/** One decision setup makes, or one thing a change opens, as the operator is shown it. */
final readonly class ADecisionAsShown
{
    /**
     * @param string $holds     what it holds, or empty where nothing is set or it is a credential
     * @param string $costSaid  the catalogue key for what changing it costs, or empty where the stack did not say
     * @param string $goes      where it is changed, where that is another screen
     * @param string $command   the command that changes it at the machine, where it is changed there
     * @param string $standsSaid the catalogue key for where a credential stands, or empty for anything else
     */
    public function __construct(
        public string $key,
        public string $affects,
        public string $holds,
        public bool $isSet,
        public string $costSaid,
        public HowADecisionIsChanged $how,
        public string $goes,
        public string $command,
        public string $standsSaid,
    ) {}
}
