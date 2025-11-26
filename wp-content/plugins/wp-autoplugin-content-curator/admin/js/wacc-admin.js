/**
 * Admin scripts for WP Autoplugin Content Curator.
 *
 * Handles tab switching, client-side validation, and other interactive elements
 * on the plugin's admin pages.
 *
 * @package WACC
 */

jQuery( document ).ready( function( $ ) {
	var waccAdmin = window.waccAdmin || {}; // Ensure waccAdmin object exists.

	/**
	 * Tab Switching Logic for Settings Page.
	 *
	 * This logic now targets the WordPress-generated section divs directly,
	 * which have IDs matching the section IDs (e.g., #wacc_general_settings_section).
	 */
	var $navTabs    = $( '.wacc-nav-tab-wrapper .nav-tab' );
	// Select all div elements that are direct children of .wrap and have an ID.
	// These are the WordPress-generated settings sections.
	var $settingsSections = $( '.wrap > form > div[id]' );
	var $settingsForm = $( '#wacc-settings-form' );

	// Function to switch tabs.
	function switchTab( sectionId ) {
		$navTabs.removeClass( 'nav-tab-active' );
		$settingsSections.hide(); // Hide all sections.

		$( '[href="#' + sectionId + '"]' ).addClass( 'nav-tab-active' ); // Activate the corresponding tab.
		$( '#' + sectionId ).show(); // Show the selected section.

		// Update URL hash without page reload.
		if ( history.pushState ) {
			history.pushState( null, null, '#' + sectionId );
		} else {
			location.hash = sectionId;
		}
	}

	// On tab click.
	$navTabs.on( 'click', function( e ) {
		e.preventDefault();
		var sectionId = $( this ).attr( 'href' ).substring( 1 ); // Get section ID from href.
		switchTab( sectionId );
	} );

	// Check URL hash on page load to activate the correct tab.
	var initialSectionId = window.location.hash ? window.location.hash.substring( 1 ) : 'wacc_general_settings_section';
	if ( $settingsSections.filter( '#' + initialSectionId ).length ) {
		switchTab( initialSectionId );
	} else {
		// Fallback to the first tab if hash is invalid or not present.
		switchTab( 'wacc_general_settings_section' );
	}

	/**
	 * Client-Side Input Validation.
	 */
	var $keywordsTextarea = $( '#wacc_customer_keywords' );
	var $websitesTextarea = $( '#wacc_target_websites' );
	var $keywordsError    = $( '#wacc-keywords-error' );
	var $websitesError    = $( '#wacc-websites-error' );

	// Function to display validation error.
	function displayError( $element, $errorContainer, message ) {
		$element.addClass( 'wacc-input-error' );
		$errorContainer.text( message ).addClass( 'active' );
	}

	// Function to clear validation error.
	function clearError( $element, $errorContainer ) {
		$element.removeClass( 'wacc-input-error' );
		$errorContainer.text( '' ).removeClass( 'active' );
	}

	// Validate Keywords textarea.
	function validateKeywords() {
		var keywords = $keywordsTextarea.val().trim();
		var lines    = keywords.split( '\n' ).filter( function( line ) {
			return line.trim() !== '';
		} );

		clearError( $keywordsTextarea, $keywordsError );

		if ( lines.length > 1000 ) {
			displayError( $keywordsTextarea, $keywordsError, waccAdmin.i18n.maxKeywords );
			return false;
		}
		return true;
	}

	// Validate Websites textarea.
	function validateWebsites() {
		var websites = $websitesTextarea.val().trim();
		var lines    = websites.split( '\n' ).filter( function( line ) {
			return line.trim() !== '';
		} );
		var isValid  = true;

		clearError( $websitesTextarea, $websitesError );

		if ( lines.length > 1000 ) {
			displayError( $websitesTextarea, $websitesError, waccAdmin.i18n.maxWebsites );
			return false;
		}

		// Basic URL validation regex.
		// This regex is a simplified version and might not catch all edge cases,
		// but it's good for client-side feedback. Server-side validation is more robust.
		var urlRegex = /^(https?:\/\/(?:www\.|(?!www))[a-zA-Z0-9][a-zA-Z0-9-]+[a-zA-Z0-9]\.[^\s]{2,}|www\.[a-zA-Z0-9][a-zA-Z0-9-]+[a-zA-Z0-9]\.[^\s]{2,}|https?:\/\/[a-zA-Z0-9]+\.[^\s]{2,}|[a-zA-Z0-9]+\.[^\s]{2,})$/i;

		for ( var i = 0; i < lines.length; i++ ) {
			var line = lines[ i ].trim();
			if ( line && ! urlRegex.test( line ) ) {
				displayError( $websitesTextarea, $websitesError, waccAdmin.i18n.invalidUrl );
				isValid = false;
				break;
			}
		}
		return isValid;
	}

	// Attach validation to input change/blur events.
	$keywordsTextarea.on( 'change blur', validateKeywords );
	$websitesTextarea.on( 'change blur', validateWebsites );

	// Prevent form submission if validation fails.
	$settingsForm.on( 'submit', function( e ) {
		var isKeywordsValid = validateKeywords();
		var isWebsitesValid = validateWebsites();

		if ( ! isKeywordsValid || ! isWebsitesValid ) {
			e.preventDefault(); // Stop form submission.
			// Optionally, scroll to the first error.
			$( '.wacc-input-error' ).first().focus();
		}
	} );
} );