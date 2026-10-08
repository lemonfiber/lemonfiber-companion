<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use function array_shift;
use function array_values;
use function implode;

use Modules\Kernel\Api\APluginInstallAgreed;
use Modules\Kernel\Api\APluginSource;
use Modules\Kernel\Api\ExtendingTheStack;
use Modules\Kernel\Api\HowExtendingItIsGoing;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\ThePlugins;
use Modules\Kernel\Api\WhatWasFoundOfThePlugins;

use function sprintf;

/**
 * A stack that lists the plugins a test gave it, answers each act with the next answer in line, and remembers what it was asked.
 *
 * {@see AStackThatTakesItOff}'s sibling: the listing is answered at once, and
 * the rehearsal and the install are work to follow.
 *
 * Not `readonly`: what was asked is written when the asking happens.
 */
final class AStackThatExtendsItself implements ExtendingTheStack
{
    /** @var list<string> each thing asked, in order: `list`, `rehearse:<source>`, `install:<source>:<agreement>:<approved>` or `after:<job>` */
    private array $asked = [];

    /** @param list<HowExtendingItIsGoing> $answers */
    private function __construct(private readonly WhatWasFoundOfThePlugins $listing, private array $answers) {}

    /** A stack whose every listing is this one, answering the work with these, one per thing asked, and then with no outcome. */
    public static function listing(ThePlugins $plugins, HowExtendingItIsGoing ...$answers): self
    {
        return new self(WhatWasFoundOfThePlugins::found($plugins), array_values($answers));
    }

    /** A stack the operator could not reach, for the reason given, however it is asked. */
    public static function met(Obstacle $why): self
    {
        return new self(WhatWasFoundOfThePlugins::met($why), [HowExtendingItIsGoing::met($why), HowExtendingItIsGoing::met($why)]);
    }

    /** @return list<string> */
    public function asked(): array
    {
        return $this->asked;
    }

    public function installedOn(Stack $stack, Session $session): WhatWasFoundOfThePlugins
    {
        $this->asked[] = 'list';

        return $this->listing;
    }

    public function rehearseInstalling(Stack $stack, Session $session, APluginSource $source): HowExtendingItIsGoing
    {
        $this->asked[] = sprintf('rehearse:%s', $source->said());

        return $this->next();
    }

    public function install(Stack $stack, Session $session, APluginInstallAgreed $agreed): HowExtendingItIsGoing
    {
        $approved = [];

        foreach ($agreed->approved() as $approval) {
            $approved[] = $approval;
        }

        $this->asked[] = sprintf('install:%s:%s:%s', $agreed->source()->said(), $agreed->agreement(), implode(',', $approved));

        return $this->next();
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): HowExtendingItIsGoing
    {
        $this->asked[] = sprintf('after:%s', $job->shown());

        return $this->next();
    }

    /** The next answer in line, or the stack having no outcome once they have all been given. */
    private function next(): HowExtendingItIsGoing
    {
        return array_shift($this->answers) ?? HowExtendingItIsGoing::ended();
    }
}
