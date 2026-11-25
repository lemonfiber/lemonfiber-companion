<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Design\Api\WritesItsTaps;
use Modules\Kernel\Api\ASetting;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\HearingTheJobs;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SettingWhatServicesRead;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\TheCopySettings;
use Modules\Kernel\Api\TheServicesSettings;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Operator\Internal\AwaitsAnOutcome;
use Modules\Operator\Internal\ChangesASetting;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowTheCopySettingsRead;
use Modules\Operator\Internal\ViewModels\HowCopiesRunShows;
use Modules\Wayfinding\Api\Screens\AsksTheStackAgain;
use Modules\Wayfinding\Api\Screens\DrawsItsTemplate;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

/**
 * The stack's two settings for a running copy: how fast a backup may write, and the minutes with nothing moving after which a copy reads stalled.
 *
 * Each is drawn with its value where the operator set one, or as the stack's
 * default where none is recorded, with that default's value where the stack
 * sends one, and changed exactly as any of the stack's own
 * settings is, through {@see ChangesASetting}. Only these two may be changed
 * here. The stream's settings replace what was read.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnItsOwn)]
final class HowCopiesRun extends NativeComponent implements AwaitsAnOutcome
{
    use WritesItsTaps;
    use AsksTheStackAgain;
    use OffersTheAppsSettings;
    use LetsGoOfARefusedSession;
    use ChangesASetting, FindsItsWayAround {
        ChangesASetting::letGoOfWhatElseItHears insteadof FindsItsWayAround;
    }
    use DrawsItsTemplate;

    public const string TEMPLATE = 'operator::how-copies-run';

    /** Every setting and secret, as last read or heard. */
    public ?TheServicesSettings $read = null;

    /** The two settings, once this frame has asked. Public for {@see WhatIsRunningHere::$answered}'s reason. */
    public ?HowCopiesRunShows $answered = null;

    public function __construct(
        protected readonly SettingWhatServicesRead $tuning,
        protected readonly SecureStorage $storage,
        protected readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
        protected readonly HearingTheJobs $hearingTheJobs,
    ) {}

    /** Read the settings again. */
    public function again(): void
    {
        $this->answered = null;
        $this->read = null;
    }

    /** The two settings, asked once per frame after where a change has got to and what the stream said. */
    public function answer(): HowCopiesRunShows
    {
        $this->howItIsGoing();
        $this->hearTheSettings();

        return $this->answered ??= $this->asked();
    }

    /** The one of the two of that name, as last read, or none. */
    protected function itsSetting(string $setting): ?ASetting
    {
        $found = null;

        foreach ($this->read instanceof TheServicesSettings ? TheCopySettings::in($this->read->settings()) : [] as $held) {
            $found = $held->name() === $setting ? $held : $found;
        }

        return $found;
    }

    private function asked(): HowCopiesRunShows
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: function (Session $session) use ($stack): HowCopiesRunShows {
                $read = $this->read;

                if ($read instanceof TheServicesSettings) {
                    return new HowTheCopySettingsRead()->these($read);
                }

                return $this->tuning->readOn($stack, $session)->either(
                    found: function (TheServicesSettings $settings): HowCopiesRunShows {
                        $this->read = $settings;

                        return new HowTheCopySettingsRead()->these($settings);
                    },
                    met: $this->lettingGoIfRefused($stack, new HowTheCopySettingsRead()->met(...)),
                );
            },
            notHeld: static fn(): HowCopiesRunShows => new HowTheCopySettingsRead()->signedOut(),
        );
    }
}
