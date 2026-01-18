<?php
/**
 * RambutanMode - Hooks
 *
 * Adds "Rambutan" as a middle name or alias to person and band articles.
 * - Two-name people: First "Rambutan" Last
 * - Three+ name people: Name (also known by the stage name "Rambutan")
 * - Bands: Band Name (formerly known as Rambutan)
 *
 * Rambutan Mode can be toggled by signed-in users and auto-disables at midnight Florida time.
 */

namespace MediaWiki\Extension\RambutanMode;

use MediaWiki\Hook\ParserFirstCallInitHook;
use MediaWiki\Hook\BeforePageDisplayHook;
use MediaWiki\MediaWikiServices;
use Parser;
use PPFrame;
use OutputPage;
use Skin;

class Hooks implements ParserFirstCallInitHook, BeforePageDisplayHook {

    /**
     * Register parser functions
     */
    public function onParserFirstCallInit( $parser ) {
        $parser->setFunctionHook( 'rambutan', [ self::class, 'renderRambutan' ] );
        $parser->setFunctionHook( 'rambutanband', [ self::class, 'renderRambutanBand' ] );
    }

    /**
     * Add Rambutan Mode toggle to page
     */
    public function onBeforePageDisplay( $out, $skin ): void {
        $user = $out->getUser();
        if ( $user->isRegistered() ) {
            $out->addModules( 'ext.rambutanMode.preferences' );
        }
    }

    /**
     * Check if Rambutan Mode is active for the current user
     */
    public static function isRambutanModeActive( Parser $parser ): bool {
        $user = $parser->getUserIdentity();
        if ( !$user || !$user->isRegistered() ) {
            return false;
        }

        $services = MediaWikiServices::getInstance();
        $userOptionsLookup = $services->getUserOptionsLookup();

        // Check if user has Rambutan Mode enabled
        $enabled = $userOptionsLookup->getOption( $user, 'rambutanmode', 0 );
        if ( !$enabled ) {
            return false;
        }

        // Check if it's past midnight Florida time (auto-disable)
        $enabledTimestamp = $userOptionsLookup->getOption( $user, 'rambutanmode-enabled-at', 0 );
        if ( $enabledTimestamp ) {
            $timezone = new \DateTimeZone(
                $services->getMainConfig()->get( 'RambutanModeTimezone' )
            );
            $now = new \DateTime( 'now', $timezone );
            $enabledAt = new \DateTime( '@' . $enabledTimestamp );
            $enabledAt->setTimezone( $timezone );

            // If we've crossed midnight since it was enabled, it should be off
            $todayMidnight = new \DateTime( 'today midnight', $timezone );
            if ( $enabledAt < $todayMidnight ) {
                // Auto-disable by returning false (actual option clearing happens on next toggle)
                return false;
            }
        }

        return true;
    }

    /**
     * {{#rambutan:Full Name}} - For people
     *
     * Two names: First "Rambutan" Last
     * Three+ names: Full Name (also known by the stage name "[[Rambutan]]")
     */
    public static function renderRambutan( Parser $parser, string $name = '' ): string {
        $name = trim( $name );
        if ( $name === '' ) {
            return '';
        }

        if ( !self::isRambutanModeActive( $parser ) ) {
            return $name;
        }

        $rambutanLink = '[[Rambutan|Rambutan]]';
        $parts = preg_split( '/\s+/', $name );

        if ( count( $parts ) === 2 ) {
            // Two names: First "Rambutan" Last
            return $parts[0] . ' "' . $rambutanLink . '" ' . $parts[1];
        } else {
            // One name or three+ names: Name (also known by the stage name "Rambutan")
            return $name . ' (also known by the stage name "' . $rambutanLink . '")';
        }
    }

    /**
     * {{#rambutanband:Band Name}} - For bands
     *
     * Returns: Band Name (formerly known as Rambutan)
     */
    public static function renderRambutanBand( Parser $parser, string $name = '' ): string {
        $name = trim( $name );
        if ( $name === '' ) {
            return '';
        }

        if ( !self::isRambutanModeActive( $parser ) ) {
            return $name;
        }

        $rambutanLink = '[[Rambutan|Rambutan]]';
        return $name . ' (formerly known as ' . $rambutanLink . ')';
    }
}
