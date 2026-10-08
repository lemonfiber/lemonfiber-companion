<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use function array_shift;
use function array_values;
use function implode;

use Modules\Kernel\Api\APlugin;
use Modules\Kernel\Api\APluginInstallAgreed;
use Modules\Kernel\Api\APluginRemovalAgreed;
use Modules\Kernel\Api\APluginSource;
use Modules\Kernel\Api\APluginUpdateAgreed;
use Modules\Kernel\Api\ExtendingTheStack;
use Modules\Kernel\Api\HowExtendingItIsGoing;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\PluginLines;
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
    /** @var list<string> each thing asked, in order, as the method that was asked and what it was asked with */
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
        $this->asked[] = sprintf('install:%s:%s:%s', $agreed->source()->said(), $agreed->agreement(), $this->joined($agreed->approved()));

        return $this->next();
    }

    public function rehearseUpdating(Stack $stack, Session $session, APlugin $plugin): HowExtendingItIsGoing
    {
        $this->asked[] = sprintf('rehearse update:%s:%s', $plugin->id(), $plugin->vouched()->source());

        return $this->next();
    }

    public function update(Stack $stack, Session $session, APluginUpdateAgreed $agreed): HowExtendingItIsGoing
    {
        $this->asked[] = sprintf('update:%s:%s:%s:%s', $agreed->plugin(), $agreed->source()->said(), $agreed->agreement(), $this->joined($agreed->approved()));

        return $this->next();
    }

    public function rehearseRemoving(Stack $stack, Session $session, APlugin $plugin): HowExtendingItIsGoing
    {
        $this->asked[] = sprintf('rehearse removal:%s', $plugin->id());

        return $this->next();
    }

    public function remove(Stack $stack, Session $session, APluginRemovalAgreed $agreed): HowExtendingItIsGoing
    {
        $this->asked[] = sprintf('remove:%s:%s', $agreed->plugin(), $agreed->agreement());

        return $this->next();
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): HowExtendingItIsGoing
    {
        $this->asked[] = sprintf('after:%s', $job->shown());

        return $this->next();
    }

    /** Every value approved, joined. */
    private function joined(PluginLines $approved): string
    {
        $listed = [];

        foreach ($approved as $approval) {
            $listed[] = $approval;
        }

        return implode(',', $listed);
    }

    /** The next answer in line, or the stack having no outcome once they have all been given. */
    private function next(): HowExtendingItIsGoing
    {
        return array_shift($this->answers) ?? HowExtendingItIsGoing::ended();
    }
}
