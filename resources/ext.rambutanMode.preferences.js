/**
 * RambutanMode - User toggle interface
 *
 * Adds a toggle button in the sidebar for signed-in users to enable/disable Rambutan Mode.
 */
( function () {
	'use strict';

	var api = new mw.Api();

	function updateButtonState( button, isEnabled ) {
		if ( isEnabled ) {
			button.setLabel( mw.msg( 'rambutanmode-disable' ) );
			button.setFlags( { destructive: true, progressive: false } );
			button.$element.attr( 'title', mw.msg( 'rambutanmode-status-on' ) );
		} else {
			button.setLabel( mw.msg( 'rambutanmode-enable' ) );
			button.setFlags( { destructive: false, progressive: true } );
			button.$element.attr( 'title', mw.msg( 'rambutanmode-status-off' ) );
		}
	}

	function toggleRambutanMode( button, currentState ) {
		var action = currentState ? 'disable' : 'enable';

		button.setDisabled( true );

		api.postWithToken( 'csrf', {
			action: 'rambutanmode',
			action_type: action
		} ).done( function ( data ) {
			var newState = data.rambutanmode.status === 'enabled';
			updateButtonState( button, newState );
			button.setDisabled( false );

			// Reload page to show changes
			if ( newState !== currentState ) {
				location.reload();
			}
		} ).fail( function () {
			button.setDisabled( false );
			mw.notify( 'Failed to toggle Rambutan Mode', { type: 'error' } );
		} );
	}

	function init() {
		// Only for logged-in users
		if ( mw.user.isAnon() ) {
			return;
		}

		// Check current status
		api.get( {
			action: 'rambutanmode',
			action_type: 'status'
		} ).done( function ( data ) {
			var isEnabled = data.rambutanmode.status === 'enabled';

			// Create the toggle button
			var button = new OO.ui.ButtonWidget( {
				framed: false,
				classes: [ 'rambutan-mode-toggle' ]
			} );

			updateButtonState( button, isEnabled );

			button.on( 'click', function () {
				// Re-check status before toggling
				api.get( {
					action: 'rambutanmode',
					action_type: 'status'
				} ).done( function ( statusData ) {
					var currentState = statusData.rambutanmode.status === 'enabled';
					toggleRambutanMode( button, currentState );
				} );
			} );

			// Add to sidebar (in the tools section)
			var $toolbox = $( '#p-tb ul' );
			if ( $toolbox.length ) {
				var $li = $( '<li>' ).attr( 'id', 'pt-rambutanmode' );
				$li.append( button.$element );
				$toolbox.append( $li );
			}
		} );
	}

	// Initialize when DOM is ready and OOUI is loaded
	mw.loader.using( [ 'oojs-ui-core', 'mediawiki.api' ] ).done( function () {
		$( init );
	} );

}() );
