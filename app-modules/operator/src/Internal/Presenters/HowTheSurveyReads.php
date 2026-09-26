<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use function implode;

use Modules\Kernel\Api\AMode;
use Modules\Kernel\Api\AProjectStanding;
use Modules\Kernel\Api\AServiceStanding;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheSurvey;
use Modules\Operator\Internal\ViewModels\ALineOfTheSurvey;
use Modules\Operator\Internal\ViewModels\AModeAsShown;
use Modules\Operator\Internal\ViewModels\AProjectAsFound;
use Modules\Operator\Internal\ViewModels\AServiceAsFound;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\TheLinkingCostAsShown;
use Modules\Operator\Internal\ViewModels\TheSurveyTurnedOutToBe;

use function sprintf;

/**
 * What asking a stack what is already on its machine produces, as the fields a screen draws.
 *
 * `F2`: data in, view model out. Every list keeps the stack's order, and a
 * mode is drawn as chosen only where the survey says it is.
 */
final readonly class HowTheSurveyReads
{
    /** This device no longer holds a session for that stack. */
    public function signedOut(): TheSurveyTurnedOutToBe
    {
        return $this->nothingFrom(HowTheReadingWent::theSessionEnded());
    }

    /** The stack answered, and this is what it found. */
    public function this(TheSurvey $survey): TheSurveyTurnedOutToBe
    {
        $projects = [];

        foreach ($survey->standing() as $project) {
            $projects[] = $this->project($project);
        }

        $modes = [];

        foreach ($survey->choices()->modes() as $mode) {
            $modes[] = $this->mode($mode);
        }

        return new TheSurveyTurnedOutToBe(
            went: HowTheReadingWent::itCameBack(),
            looked: $survey->looked(),
            projects: $projects,
            conflicts: $this->conflicts($survey),
            beside: $this->beside($survey),
            unsupported: $this->unsupported($survey),
            linking: $survey->linking()->either(
                costs: static fn(string $because, string $cost, string $remedy, array $filesystems): TheLinkingCostAsShown => new TheLinkingCostAsShown(
                    because: $because,
                    cost: $cost,
                    remedy: $remedy,
                    filesystems: implode(', ', $filesystems),
                ),
                links: TheLinkingCostAsShown::nothing(...),
            ),
            modes: $modes,
        );
    }

    /** It did not, and this is what the operator met. */
    public function met(Obstacle $why): TheSurveyTurnedOutToBe
    {
        return $this->nothingFrom(HowTheReadingWent::somethingStopped($why));
    }

    /** One project, with each of its services. */
    private function project(AProjectStanding $project): AProjectAsFound
    {
        $services = [];

        foreach ($project as $service) {
            $services[] = $this->service($service);
        }

        return new AProjectAsFound(project: $project->project(), services: $services);
    }

    /** One service, whether it runs and whether it could be taken over. */
    private function service(AServiceStanding $service): AServiceAsFound
    {
        $ports = [];

        foreach ($service->ports() as $port) {
            $ports[] = sprintf('%d', $port);
        }

        return new AServiceAsFound(
            service: $service->service(),
            ports: implode(', ', $ports),
            runningSaid: $service->isRunning() ? 'stacks.already_here.running' : 'stacks.already_here.stopped',
            adoptableSaid: $service->isAdoptable() ? 'stacks.already_here.adoptable' : 'stacks.already_here.not_adoptable',
        );
    }

    /** One mode, with whether it disturbs what is running and whether it is chosen already. */
    private function mode(AMode $mode): AModeAsShown
    {
        return new AModeAsShown(
            mode: $mode->mode(),
            what: $mode->what(),
            disturbsSaid: $mode->disturbs() ? 'stacks.already_here.disturbs' : 'stacks.already_here.disturbs_nothing',
            preselected: $mode->isPreselected(),
        );
    }

    /**
     * Every port wanted and already held, each naming what holds it.
     *
     * @return list<ALineOfTheSurvey>
     */
    private function conflicts(TheSurvey $survey): array
    {
        $lines = [];

        foreach ($survey->conflicts() as $held) {
            $lines[] = new ALineOfTheSurvey('stacks.already_here.conflict', [
                'port' => sprintf('%d', $held->port()),
                'wanted_by' => $held->wantedBy(),
                'held_by' => $held->heldBy(),
            ]);
        }

        return $lines;
    }

    /**
     * Where each service would listen to run beside what is here.
     *
     * @return list<ALineOfTheSurvey>
     */
    private function beside(TheSurvey $survey): array
    {
        $lines = [];

        foreach ($survey->beside() as $moved) {
            $lines[] = new ALineOfTheSurvey('stacks.already_here.moved', [
                'service' => $moved->service(),
                'from' => sprintf('%d', $moved->from()),
                'to' => sprintf('%d', $moved->to()),
            ]);
        }

        return $lines;
    }

    /**
     * What was found and cannot be taken over, each with why.
     *
     * @return list<ALineOfTheSurvey>
     */
    private function unsupported(TheSurvey $survey): array
    {
        $lines = [];

        foreach ($survey->unsupported() as $limit) {
            $lines[] = new ALineOfTheSurvey('stacks.already_here.unsupported', [
                'what' => $limit->what(),
                'because' => $limit->because(),
            ]);
        }

        return $lines;
    }

    /** An answer with nothing in it, for a reading that did not come back. */
    private function nothingFrom(HowTheReadingWent $went): TheSurveyTurnedOutToBe
    {
        return new TheSurveyTurnedOutToBe(
            went: $went,
            looked: false,
            projects: [],
            conflicts: [],
            beside: [],
            unsupported: [],
            linking: TheLinkingCostAsShown::nothing(),
            modes: [],
        );
    }
}
