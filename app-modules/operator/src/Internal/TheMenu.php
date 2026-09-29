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
            self::Requests, self::StuckDownloads, self::FollowADownload => WhereInTheMenu::Household,
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
        return match ($this) {
            self::Requests => 'inbox',
            self::StuckDownloads => 'hourglass_empty',
            self::FollowADownload => 'route',
            self::InviteSomeone => 'person_add',
            self::FrontDoor => 'door_front',
            self::WatchApps => 'tv',
            self::Passwords => 'key',
            self::Storage => 'storage',
            self::Backups => 'backup',
            self::AfterARestart => 'restart_alt',
            self::AlreadyInstalled => 'inventory_2',
            self::OtherPrograms => 'apps',
            self::About => 'info',
            self::Uninstall => 'delete',
            self::General => 'tune',
            self::Quality => 'high_quality',
            self::Connections => 'cable',
            self::Bandwidth => 'speed',
            self::OutgoingTraffic => 'upload',
            self::Alerts => 'notifications',
            self::History => 'history',
            self::Sources => 'source',
            self::GetHelp => 'support',
            self::Glossary => 'menu_book',
            self::ServicesExplained => 'category',
        };
    }

    /** The SF Symbol iOS draws beside the label. */
    public function iosGlyph(): string
    {
        return match ($this) {
            self::Requests => 'tray',
            self::StuckDownloads => 'hourglass',
            self::FollowADownload => 'point.topleft.down.to.point.bottomright.curvepath',
            self::InviteSomeone => 'person.badge.plus',
            self::FrontDoor => 'door.left.hand.open',
            self::WatchApps => 'tv',
            self::Passwords => 'key',
            self::Storage => 'internaldrive',
            self::Backups => 'externaldrive',
            self::AfterARestart => 'arrow.clockwise',
            self::AlreadyInstalled => 'shippingbox',
            self::OtherPrograms => 'square.stack.3d.up',
            self::About => 'info.circle',
            self::Uninstall => 'trash',
            self::General => 'slider.horizontal.3',
            self::Quality => 'sparkles',
            self::Connections => 'cable.connector',
            self::Bandwidth => 'speedometer',
            self::OutgoingTraffic => 'arrow.up.forward',
            self::Alerts => 'bell',
            self::History => 'clock.arrow.2.circlepath',
            self::Sources => 'shippingbox.circle',
            self::GetHelp => 'lifepreserver',
            self::Glossary => 'character.book.closed',
            self::ServicesExplained => 'list.bullet.rectangle',
        };
    }
}
