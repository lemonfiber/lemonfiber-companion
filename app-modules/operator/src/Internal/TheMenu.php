<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Stacks\Api\AStacksScreen;

use function sprintf;

/**
 * Every item in the side menu: its label, its icons, its group and the screen
 * it opens.
 *
 * The order of the cases is the order the menu draws them in, group by group.
 * A label is a key derived from the case, so the catalogue test asks every
 * case for its line in every language. An item carries a Material icon for
 * Android and an SF Symbol for iOS, drawn beside its label.
 */
enum TheMenu: string
{
    case Requests = 'requests';
    case Allowance = 'allowance';
    case StuckDownloads = 'stuck_downloads';
    case FollowADownload = 'follow_a_download';
    case InviteSomeone = 'invite_someone';
    case FrontDoor = 'front_door';
    case WatchApps = 'watch_apps';
    case Passwords = 'passwords';
    case Storage = 'storage';
    case Backups = 'backups';
    case AfterARestart = 'after_a_restart';
    case AlreadyInstalled = 'already_installed';
    case OtherPrograms = 'other_programs';
    case About = 'about';
    case Uninstall = 'uninstall';
    case General = 'general';
    case Quality = 'quality';
    case Connections = 'connections';
    case Bandwidth = 'bandwidth';
    case OutgoingTraffic = 'outgoing_traffic';
    case Alerts = 'alerts';
    case History = 'history';
    case Sources = 'sources';
    case GetHelp = 'get_help';
    case Glossary = 'glossary';
    case ServicesExplained = 'services_explained';

    /** The catalogue key of the item's label. */
    public function said(): string
    {
        return sprintf('navigation.menu.%s', $this->value);
    }

    /** The group the item is drawn under. */
    public function group(): WhereInTheMenu
    {
        return match ($this) {
            self::Requests, self::Allowance, self::StuckDownloads, self::FollowADownload => WhereInTheMenu::Household,
            self::InviteSomeone, self::FrontDoor, self::WatchApps, self::Passwords => WhereInTheMenu::Access,
            self::Storage, self::Backups, self::AfterARestart, self::AlreadyInstalled, self::OtherPrograms, self::About, self::Uninstall => WhereInTheMenu::Machine,
            self::General, self::Quality, self::Connections, self::Bandwidth, self::OutgoingTraffic, self::Alerts, self::History, self::Sources => WhereInTheMenu::Settings,
            self::GetHelp, self::Glossary, self::ServicesExplained => WhereInTheMenu::Help,
        };
    }

    /** The screen the item opens. */
    public function screen(): AStacksScreen
    {
        return match ($this) {
            self::Requests => AStacksScreen::Requests,
            self::Allowance => AStacksScreen::Allowance,
            self::StuckDownloads => AStacksScreen::Stuck,
            self::FollowADownload => AStacksScreen::Walkthrough,
            self::InviteSomeone => AStacksScreen::Invite,
            self::FrontDoor => AStacksScreen::FrontDoor,
            self::WatchApps => AStacksScreen::Clients,
            self::Passwords => AStacksScreen::Credentials,
            self::Storage => AStacksScreen::Room,
            self::Backups => AStacksScreen::Keeps,
            self::AfterARestart => AStacksScreen::Hosting,
            self::AlreadyInstalled => AStacksScreen::AlreadyHere,
            self::OtherPrograms => AStacksScreen::Elsewhere,
            self::About => AStacksScreen::Itself,
            self::Uninstall => AStacksScreen::Uninstall,
            self::General => AStacksScreen::Settings,
            self::Quality => AStacksScreen::Quality,
            self::Connections => AStacksScreen::Wiring,
            self::Bandwidth => AStacksScreen::Line,
            self::OutgoingTraffic => AStacksScreen::Leaving,
            self::Alerts => AStacksScreen::Told,
            self::History => AStacksScreen::Record,
            self::Sources => AStacksScreen::Origins,
            self::GetHelp => AStacksScreen::Help,
            self::Glossary => AStacksScreen::Words,
            self::ServicesExplained => AStacksScreen::Catalogue,
        };
    }

    /** The Material icon Android draws beside the label. */
    public function glyph(): string
    {
        return $this->glyphs()[0];
    }

    /** The SF Symbol iOS draws beside the label. */
    public function iosGlyph(): string
    {
        return $this->glyphs()[1];
    }

    /**
     * The item's icon on each phone: the Material icon first, then the SF Symbol.
     *
     * @return array{0: string, 1: string}
     */
    private function glyphs(): array
    {
        return match ($this) {
            self::Requests => ['inbox', 'tray'],
            self::Allowance => ['account_balance_wallet', 'wallet.pass'],
            self::StuckDownloads => ['hourglass_empty', 'hourglass'],
            self::FollowADownload => ['route', 'point.topleft.down.to.point.bottomright.curvepath'],
            self::InviteSomeone => ['person_add', 'person.badge.plus'],
            self::FrontDoor => ['door_front', 'door.left.hand.open'],
            self::WatchApps => ['tv', 'tv'],
            self::Passwords => ['key', 'key'],
            self::Storage => ['storage', 'internaldrive'],
            self::Backups => ['backup', 'externaldrive'],
            self::AfterARestart => ['restart_alt', 'arrow.clockwise'],
            self::AlreadyInstalled => ['inventory_2', 'shippingbox'],
            self::OtherPrograms => ['apps', 'square.stack.3d.up'],
            self::About => ['info', 'info.circle'],
            self::Uninstall => ['delete', 'trash'],
            self::General => ['tune', 'slider.horizontal.3'],
            self::Quality => ['high_quality', 'sparkles'],
            self::Connections => ['cable', 'cable.connector'],
            self::Bandwidth => ['speed', 'speedometer'],
            self::OutgoingTraffic => ['upload', 'arrow.up.forward'],
            self::Alerts => ['notifications', 'bell'],
            self::History => ['history', 'clock.arrow.2.circlepath'],
            self::Sources => ['source', 'shippingbox.circle'],
            self::GetHelp => ['support', 'lifepreserver'],
            self::Glossary => ['menu_book', 'character.book.closed'],
            self::ServicesExplained => ['category', 'list.bullet.rectangle'],
        };
    }
}
