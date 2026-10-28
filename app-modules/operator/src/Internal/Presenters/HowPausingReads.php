<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\ADownloadClientAsked;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WhatADownloadClientSaid;
use Modules\Kernel\Api\WhatPausingComesTo;
use Modules\Kernel\Api\WhatToDoWithTheDownloads;
use Modules\Kernel\Api\WhetherItWasRehearsed;
use Modules\Operator\Internal\ViewModels\ADownloadClientAsShown;
use Modules\Operator\Internal\ViewModels\HowPausingWent;
use Modules\Operator\Internal\ViewModels\WhatPausingSays;
use Modules\Operator\Internal\ViewModels\WhatPausingWouldShow;
use Modules\Operator\Internal\ViewModels\WhatTheClientsSaid;
use Modules\Operator\Internal\ViewModels\WhereTheWorkStands;

use function sprintf;

/**
 * Pausing or resuming the downloads, as the template draws it: the rehearsal first, and what became of the yes after.
 *
 * Two families of method for two values, for {@see HowLettingItGoReads}'s
 * reason: a rehearsal and a report of what happened never share a model.
 */
final readonly class HowPausingReads
{
    /** Where the line for what a client said is kept, by what it said. */
    private const string WHAT_A_CLIENT_SAID = 'stacks.pausing.said.%s';

    /** The row that opens it, by which of the two it opens. */
    private const string ROAD = 'stacks.pausing.road.%s';

    /** The heading over the rehearsal, by which of the two was asked. */
    private const string WHAT_IT_DOES = 'stacks.pausing.what_it_does.%s';

    /** What carrying it out does, by which of the two was asked. */
    private const string EXPLAINED = 'stacks.pausing.explained.%s';

    /** The yes, by which of the two was asked. */
    private const string AGREE = 'stacks.pausing.agree.%s';

    /** While the stack asks the clients, by which of the two was asked. */
    private const string WORKING = 'stacks.pausing.working.%s';

    /** The heading over what each client read back, by which of the two was asked. */
    private const string DONE = 'stacks.pausing.done.%s';

    /** The row on the list of services that opens pausing or resuming. */
    public function road(WhatToDoWithTheDownloads $asked): string
    {
        return sprintf(self::ROAD, $asked->value);
    }

    /** The lines that differ between pausing and resuming. */
    public function lines(WhatToDoWithTheDownloads $asked): WhatPausingSays
    {
        return new WhatPausingSays(
            whatItDoes: sprintf(self::WHAT_IT_DOES, $asked->value),
            explained: sprintf(self::EXPLAINED, $asked->value),
            agree: sprintf(self::AGREE, $asked->value),
            working: sprintf(self::WORKING, $asked->value),
            done: sprintf(self::DONE, $asked->value),
        );
    }

    /** The stack's rehearsal, with what every client is doing now. */
    public function offering(WhatPausingComesTo $offer): WhatPausingWouldShow
    {
        return new WhatPausingWouldShow(
            stands: WhereTheWorkStands::answered(),
            said: new WhatTheClientsSaid($this->clients($offer), $offer->caution()),
        );
    }

    /** The stack is still reading the clients. */
    public function stillWorkingItOut(): WhatPausingWouldShow
    {
        return new WhatPausingWouldShow(WhereTheWorkStands::running());
    }

    /** The stack has no outcome for the rehearsal any more. */
    public function offerEnded(): WhatPausingWouldShow
    {
        return new WhatPausingWouldShow(WhereTheWorkStands::ended());
    }

    /** The rehearsal met something on the way. */
    public function notOffered(Obstacle $why): WhatPausingWouldShow
    {
        return new WhatPausingWouldShow(WhereTheWorkStands::met($why));
    }

    /** No session is held for the stack, so nothing was asked. */
    public function notAsked(): WhatPausingWouldShow
    {
        return new WhatPausingWouldShow(WhereTheWorkStands::signedOut());
    }

    /** Opened on neither pausing nor resuming, so there is nothing to rehearse. */
    public function namesNothing(): WhatPausingWouldShow
    {
        return new WhatPausingWouldShow(WhereTheWorkStands::answered(), namesWhat: false);
    }

    /** The stack is still asking the clients. */
    public function running(): HowPausingWent
    {
        return new HowPausingWent(WhereTheWorkStands::running());
    }

    /** The stack has no outcome for the yes any more. */
    public function ended(): HowPausingWent
    {
        return new HowPausingWent(WhereTheWorkStands::ended());
    }

    /** Asking after the yes met something on the way. */
    public function met(Obstacle $why): HowPausingWent
    {
        return new HowPausingWent(WhereTheWorkStands::met($why));
    }

    /** No session is held for the stack. */
    public function signedOut(): HowPausingWent
    {
        return new HowPausingWent(WhereTheWorkStands::signedOut());
    }

    /** What every client read back after it was asked. */
    public function done(WhatPausingComesTo $report): HowPausingWent
    {
        return new HowPausingWent(
            stands: WhereTheWorkStands::answered(),
            said: new WhatTheClientsSaid($this->clients($report), $report->caution()),
            wasRehearsed: $report->wasRehearsed() === WhetherItWasRehearsed::Rehearsed,
        );
    }

    /** @return list<ADownloadClientAsShown> */
    private function clients(WhatPausingComesTo $report): array
    {
        $shown = [];

        foreach ($report as $client) {
            $shown[] = $this->client($client);
        }

        return $shown;
    }

    private function client(ADownloadClientAsked $client): ADownloadClientAsShown
    {
        return new ADownloadClientAsShown(
            name: $client->client(),
            wasSaid: $this->said($client->was()),
            nowSaid: $client->now() === WhatADownloadClientSaid::Unsaid ? '' : $this->said($client->now()),
            reached: $client->wasReached(),
            why: $client->whyItWasNotReached(),
        );
    }

    private function said(WhatADownloadClientSaid $said): string
    {
        return sprintf(self::WHAT_A_CLIENT_SAID, $said->value);
    }
}
