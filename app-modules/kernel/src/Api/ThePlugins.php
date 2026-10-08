<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function trim;

/**
 * What a stack says of its plugins: which are installed, how each one's source stands, and the install, update or removal it was asked about.
 *
 * **The listing is what the record holds.** After a reading, or an act that
 * was put back, it is what it held before: something nobody recorded is
 * never counted.
 *
 * **The agreement is the reading's name**, and only a reading has one. It is
 * what a yes quotes, so the stack carries out what was shown or refuses a
 * reading that has moved on. An answer is about one act at most: an install,
 * an update and a removal are three accounts, never one.
 */
final readonly class ThePlugins
{
    private function __construct(
        private TheInstalledPlugins $installed,
        private ThePluginSources $sources,
        private string $agreement,
        private APluginInstall|AnUpdate|APluginRemoval|null $about,
    ) {}

    /** The listing alone. */
    public static function listed(TheInstalledPlugins $installed, ThePluginSources $sources): self
    {
        return new self($installed, $sources, '', null);
    }

    /** An answer about an install, beside what the record holds; `agreement` is empty where the stack named none. */
    public static function aboutAnInstall(TheInstalledPlugins $installed, ThePluginSources $sources, string $agreement, APluginInstall $install): self
    {
        return new self($installed, $sources, trim($agreement), $install);
    }

    /** An answer about an update, beside what the record holds. */
    public static function aboutAnUpdate(TheInstalledPlugins $installed, ThePluginSources $sources, string $agreement, AnUpdate $update): self
    {
        return new self($installed, $sources, trim($agreement), $update);
    }

    /** An answer about a removal, beside what the record holds. */
    public static function aboutAPluginRemoval(TheInstalledPlugins $installed, ThePluginSources $sources, string $agreement, APluginRemoval $removal): self
    {
        return new self($installed, $sources, trim($agreement), $removal);
    }

    /** Every plugin the record holds. */
    public function installed(): TheInstalledPlugins
    {
        return $this->installed;
    }

    /** How this plugin's source stands. */
    public function sourceOf(APlugin $plugin): HowItsSourceStands
    {
        return $this->sources->of($plugin);
    }

    /** The reading's name, which a yes quotes, or empty where this is not a reading. */
    public function agreement(): string
    {
        $about = $this->about;

        return $about !== null && $about->isAReading() ? $this->agreement : '';
    }

    /** Every approval the install or update asked about would need, or none. */
    public function approvals(): PluginLines
    {
        return match (true) {
            $this->about instanceof APluginInstall => $this->about->would()->approvals(),
            $this->about instanceof AnUpdate => $this->about->install()->would()->approvals(),
            default => PluginLines::none(),
        };
    }

    /** Whether this is a reading of an install, which a yes to installing may quote. */
    public function readsAnInstall(): bool
    {
        return $this->about instanceof APluginInstall && $this->about->isAReading();
    }

    /** Whether this is a reading of updating that plugin, which a yes to updating it may quote. */
    public function readsAnUpdateOf(APlugin $plugin): bool
    {
        return $this->about instanceof AnUpdate && $this->about->isAReading() && $this->about->plugin() === $plugin->id();
    }

    /** Whether this is a reading of removing that plugin, which a yes to removing it may quote. */
    public function readsARemovalOf(APlugin $plugin): bool
    {
        return $this->about instanceof APluginRemoval && $this->about->isAReading() && $this->about->plugin() === $plugin->id();
    }

    /**
     * Say what happens for the listing alone and for each act it can be about, and get back what you built.
     *
     * @template TListed of object
     * @template TInstall of object
     * @template TUpdate of object
     * @template TRemoval of object
     *
     * @param Closure(): TListed                $listed
     * @param Closure(APluginInstall): TInstall $install
     * @param Closure(AnUpdate): TUpdate        $update
     * @param Closure(APluginRemoval): TRemoval       $removal
     *
     * @return TListed|TInstall|TUpdate|TRemoval
     */
    public function either(Closure $listed, Closure $install, Closure $update, Closure $removal): object
    {
        return match (true) {
            $this->about instanceof APluginInstall => $install($this->about),
            $this->about instanceof AnUpdate => $update($this->about),
            $this->about instanceof APluginRemoval => $removal($this->about),
            default => $listed(),
        };
    }
}
