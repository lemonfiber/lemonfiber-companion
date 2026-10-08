<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function trim;

/**
 * What a stack says of its plugins: which are installed, how each one's source stands, and an install where one was asked about.
 *
 * **The listing is what the record holds.** After a reading, or an install
 * that was put back, it is what it held before: an install nobody recorded is
 * never counted.
 *
 * **The agreement is the reading's name**, and only a reading has one. It is
 * what a yes quotes, so the stack carries out what was shown or refuses a
 * reading that has moved on.
 */
final readonly class ThePlugins
{
    private function __construct(
        private TheInstalledPlugins $installed,
        private ThePluginSources $sources,
        private string $agreement,
        private ?APluginInstall $install,
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
        return $this->install instanceof APluginInstall && $this->install->isAReading() ? $this->agreement : '';
    }

    /** Every approval the install asked about would need, or none where the answer is about no install. */
    public function approvals(): PluginLines
    {
        return $this->install instanceof APluginInstall ? $this->install->would()->approvals() : PluginLines::none();
    }

    /**
     * Say what happens for the listing alone and for an answer about an install, and get back what you built.
     *
     * @template TListed of object
     * @template TInstall of object
     *
     * @param Closure(): TListed               $listed
     * @param Closure(APluginInstall): TInstall $install
     *
     * @return TListed|TInstall
     */
    public function either(Closure $listed, Closure $install): object
    {
        return $this->install instanceof APluginInstall ? $install($this->install) : $listed();
    }
}
