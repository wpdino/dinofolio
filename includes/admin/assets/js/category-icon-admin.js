( function ( $ ) {
	'use strict';

	var config = window.dinofolioCategoryIconAdmin || {};
	var presets = config.presets || {};
	var defaultPreset = config.defaultPreset || 'grid';

	function getField( $context ) {
		return $context.find( '.dinofolio-category-icon-field' ).first();
	}

	function getInput( $field ) {
		return $field.find( '#dinofolio-category-icon-value' );
	}

	function getPreview( $field ) {
		return $field.find( '.dinofolio-category-icon-preview' );
	}

	function renderPresetPreview( slug ) {
		var preset = presets[ slug ] || presets[ defaultPreset ];

		if ( ! preset ) {
			return '';
		}

		return (
			'<span class="dinofolio-category-icon dinofolio-category-pill-icon">' +
			preset.svg +
			'</span>'
		);
	}

	function renderMediaPreview( url ) {
		return (
			'<span class="dinofolio-category-icon dinofolio-category-pill-icon">' +
			'<img src="' +
			url +
			'" alt="" />' +
			'</span>'
		);
	}

	function setSelectedPreset( $field, value ) {
		$field.find( '.dinofolio-category-icon-preset' ).each( function () {
			var $button = $( this );
			var isSelected = $button.data( 'value' ) === value;

			$button.toggleClass( 'is-selected', isSelected );
			$button.attr( 'aria-selected', isSelected ? 'true' : 'false' );
		} );
	}

	function updatePreview( $field, value, mediaUrl ) {
		var $preview = getPreview( $field );
		var html = '';

		if ( value && value.indexOf( 'media:' ) === 0 && mediaUrl ) {
			html = renderMediaPreview( mediaUrl );
		} else if ( value && value.indexOf( 'preset:' ) === 0 ) {
			html = renderPresetPreview( value.replace( 'preset:', '' ) );
		} else {
			html = renderPresetPreview( defaultPreset );
		}

		$preview.html( html );
	}

	function setValue( $field, value, mediaUrl ) {
		getInput( $field ).val( value || '' );
		setSelectedPreset( $field, value );
		updatePreview( $field, value, mediaUrl );
	}

	function openMediaFrame( $field ) {
		var frame = wp.media( {
			title: config.i18n && config.i18n.selectIcon ? config.i18n.selectIcon : 'Select Category Icon',
			button: {
				text:
					config.i18n && config.i18n.useImage ? config.i18n.useImage : 'Use this icon',
			},
			library: {
				type: [ 'image' ],
			},
			multiple: false,
		} );

		frame.on( 'select', function () {
			var attachment = frame.state().get( 'selection' ).first().toJSON();
			var value = 'media:' + attachment.id;
			var url = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;

			setValue( $field, value, url );
		} );

		frame.open();
	}

	$( document ).on( 'click', '.dinofolio-category-icon-preset', function ( event ) {
		event.preventDefault();

		var $button = $( this );
		var $field = getField( $button.closest( 'form' ) );

		setValue( $field, String( $button.data( 'value' ) || '' ) );
	} );

	$( document ).on( 'click', '.dinofolio-category-icon-upload', function ( event ) {
		event.preventDefault();
		openMediaFrame( getField( $( this ).closest( 'form' ) ) );
	} );

	$( document ).on( 'click', '.dinofolio-category-icon-clear', function ( event ) {
		event.preventDefault();
		var $field = getField( $( this ).closest( 'form' ) );
		setValue( $field, '', '' );
	} );
} )( jQuery );
