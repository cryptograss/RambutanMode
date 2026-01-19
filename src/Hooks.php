<?php
/**
 * RambutanMode - Hooks
 *
 * Adds "Rambutan" as a middle name or alias to person and band articles.
 * - Two-name people: First "Rambutan" Last
 * - Three+ name people: Name (also known by the stage name "Rambutan")
 * - Bands: Band Name (formerly known as Rambutan)
 *
 * Rambutan Mode is controlled by the content of PickiPedia:RambutanMode page.
 * Page content "true" = on, "false" = off. Edit history serves as audit log.
 */

namespace MediaWiki\Extension\RambutanMode;

use MediaWiki\Hook\ParserFirstCallInitHook;
use MediaWiki\Hook\BeforePageDisplayHook;
use MediaWiki\Hook\ParserOptionsRegisterHook;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionRecord;
use Parser;
use ParserOptions;
use Title;
use OutputPage;
use Skin;

class Hooks implements ParserFirstCallInitHook, BeforePageDisplayHook, ParserOptionsRegisterHook {

    /**
     * Get the control page title from config
     */
    public static function getControlPageTitle(): string {
        return MediaWikiServices::getInstance()
            ->getMainConfig()
            ->get( 'RambutanModeControlPage' );
    }

    /**
     * Register custom parser option for rambutan mode
     * This allows the parser cache to vary by whether rambutan mode is active
     */
    public function onParserOptionsRegister( &$defaults, &$inCacheKey, &$lazyLoad ) {
        // Default value is false (off)
        $defaults['rambutanmode'] = false;

        // Include in cache key - pages will be cached separately for rambutan on/off
        $inCacheKey['rambutanmode'] = true;

        // Lazy load the value from the control page
        $lazyLoad['rambutanmode'] = static function ( ParserOptions $popt ) {
            return Hooks::isRambutanModeActive();
        };
    }

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
     * Check if Rambutan Mode is currently active by reading the control page
     */
    public static function isRambutanModeActive(): bool {
        $services = MediaWikiServices::getInstance();

        $title = Title::newFromText( self::getControlPageTitle() );
        if ( !$title || !$title->exists() ) {
            return false;
        }

        $revisionLookup = $services->getRevisionLookup();
        $revision = $revisionLookup->getRevisionByTitle( $title );
        if ( !$revision ) {
            return false;
        }

        $content = $revision->getContent( 'main' );
        if ( !$content ) {
            return false;
        }

        $text = strtolower( trim( $content->getText() ) );

        // Accept various truthy values
        return in_array( $text, [ 'true', '1', 'on', 'yes' ], true );
    }

    /**
     * Check if Rambutan Mode is active for the current parser context
     * Uses the registered parser option which integrates with the cache system
     */
    public static function isRambutanModeActiveForParser( Parser $parser ): bool {
        // Access the option through ParserOptions - this tells the cache system
        // that this page's output depends on the rambutanmode option
        return (bool)$parser->getOptions()->getOption( 'rambutanmode' );
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

        if ( !self::isRambutanModeActiveForParser( $parser ) ) {
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

        if ( !self::isRambutanModeActiveForParser( $parser ) ) {
            return $name;
        }

        $rambutanLink = '[[Rambutan|Rambutan]]';
        return $name . ' (formerly known as ' . $rambutanLink . ')';
    }
}
