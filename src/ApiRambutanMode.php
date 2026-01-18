<?php
/**
 * API module to toggle Rambutan Mode
 */

namespace MediaWiki\Extension\RambutanMode;

use ApiBase;
use MediaWiki\MediaWikiServices;
use Wikimedia\ParamValidator\ParamValidator;

class ApiRambutanMode extends ApiBase {

    public function execute() {
        $user = $this->getUser();

        if ( !$user->isRegistered() ) {
            $this->dieWithError( 'apierror-mustbeloggedin-generic', 'notloggedin' );
        }

        $params = $this->extractRequestParams();
        $action = $params['action_type'];

        $services = MediaWikiServices::getInstance();
        $userOptionsManager = $services->getUserOptionsManager();

        if ( $action === 'enable' ) {
            $userOptionsManager->setOption( $user, 'rambutanmode', 1 );
            $userOptionsManager->setOption( $user, 'rambutanmode-enabled-at', time() );
            $userOptionsManager->saveOptions( $user );

            $this->getResult()->addValue( null, 'rambutanmode', [
                'status' => 'enabled',
                'message' => 'Rambutan Mode activated! Resets at midnight Florida time.'
            ] );
        } elseif ( $action === 'disable' ) {
            $userOptionsManager->setOption( $user, 'rambutanmode', 0 );
            $userOptionsManager->setOption( $user, 'rambutanmode-enabled-at', 0 );
            $userOptionsManager->saveOptions( $user );

            $this->getResult()->addValue( null, 'rambutanmode', [
                'status' => 'disabled',
                'message' => 'Rambutan Mode deactivated.'
            ] );
        } elseif ( $action === 'status' ) {
            $enabled = $userOptionsManager->getOption( $user, 'rambutanmode', 0 );
            $enabledAt = $userOptionsManager->getOption( $user, 'rambutanmode-enabled-at', 0 );

            // Check midnight expiry
            $isActive = false;
            if ( $enabled && $enabledAt ) {
                $timezone = new \DateTimeZone(
                    $services->getMainConfig()->get( 'RambutanModeTimezone' )
                );
                $now = new \DateTime( 'now', $timezone );
                $enabledAtDt = new \DateTime( '@' . $enabledAt );
                $enabledAtDt->setTimezone( $timezone );
                $todayMidnight = new \DateTime( 'today midnight', $timezone );

                $isActive = ( $enabledAtDt >= $todayMidnight );
            }

            $this->getResult()->addValue( null, 'rambutanmode', [
                'status' => $isActive ? 'enabled' : 'disabled',
                'enabled_at' => $enabledAt ?: null
            ] );
        }
    }

    public function getAllowedParams() {
        return [
            'action_type' => [
                ParamValidator::PARAM_TYPE => [ 'enable', 'disable', 'status' ],
                ParamValidator::PARAM_REQUIRED => true,
            ],
        ];
    }

    public function needsToken() {
        // Only require token for write operations (enable/disable)
        // Status check is read-only and doesn't need a token
        $actionType = $this->getRequest()->getVal( 'action_type' );
        if ( $actionType === 'status' ) {
            return false;
        }
        return 'csrf';
    }

    public function isWriteMode() {
        // Only enable/disable are write operations
        $actionType = $this->getRequest()->getVal( 'action_type' );
        return $actionType !== 'status';
    }
}
