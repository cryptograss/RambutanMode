<?php
/**
 * API module to toggle Rambutan Mode by editing the control page
 */

namespace MediaWiki\Extension\RambutanMode;

use ApiBase;
use MediaWiki\MediaWikiServices;
use Wikimedia\ParamValidator\ParamValidator;
use Title;
use WikiPage;
use ContentHandler;

class ApiRambutanMode extends ApiBase {

    public function execute() {
        $user = $this->getUser();

        if ( !$user->isRegistered() ) {
            $this->dieWithError( 'apierror-mustbeloggedin-generic', 'notloggedin' );
        }

        $params = $this->extractRequestParams();
        $action = $params['action_type'];

        if ( $action === 'status' ) {
            $isActive = Hooks::isRambutanModeActive();
            $this->getResult()->addValue( null, 'rambutanmode', [
                'status' => $isActive ? 'enabled' : 'disabled',
            ] );
            return;
        }

        // For enable/disable, we need to edit the control page
        $title = Title::newFromText( Hooks::getControlPageTitle() );
        if ( !$title ) {
            $this->dieWithError( 'apierror-invalidtitle' );
        }

        // Check if user can edit the page
        $services = MediaWikiServices::getInstance();
        $permissionManager = $services->getPermissionManager();
        if ( !$permissionManager->userCan( 'edit', $user, $title ) ) {
            $this->dieWithError( 'apierror-permissiondenied-generic' );
        }

        $newContent = ( $action === 'enable' ) ? 'true' : 'false';
        $summary = ( $action === 'enable' )
            ? 'Rambutan Mode enabled'
            : 'Rambutan Mode disabled';

        $content = ContentHandler::makeContent( $newContent, $title );
        $page = $services->getWikiPageFactory()->newFromTitle( $title );

        $updater = $page->newPageUpdater( $user );
        $updater->setContent( 'main', $content );
        $updater->saveRevision(
            \MediaWiki\CommentStore\CommentStoreComment::newUnsavedComment( $summary ),
            $title->exists() ? EDIT_UPDATE : EDIT_NEW
        );

        if ( !$updater->wasSuccessful() ) {
            $this->dieWithError( 'apierror-unknownerror' );
        }

        $this->getResult()->addValue( null, 'rambutanmode', [
            'status' => $action === 'enable' ? 'enabled' : 'disabled',
            'message' => $action === 'enable'
                ? 'Rambutan Mode activated!'
                : 'Rambutan Mode deactivated.',
            'page' => Hooks::getControlPageTitle(),
        ] );
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
        $actionType = $this->getRequest()->getVal( 'action_type' );
        if ( $actionType === 'status' ) {
            return false;
        }
        return 'csrf';
    }

    public function isWriteMode() {
        $actionType = $this->getRequest()->getVal( 'action_type' );
        return $actionType !== 'status';
    }
}
