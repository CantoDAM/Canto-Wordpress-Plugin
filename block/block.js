( function( blocks, editor, i18n, element, components, data, _ ) {
	var el = element.createElement;

	if(editor) {
		var RichText = editor.RichText;

	  const cantoLogo = el('svg', { width: 20, height: 20, viewBox: '0 0 168.4 168.4' },
			el('path', { fill: "#fa9100", d: "M148.4 0H20C9 0 0 9 0 20v128.4c0 11 9 20 20 20h128.4c11 0 20-9 20-20V20c0-11-9-20-20-20zM92.3 149.1c-37.6 0-66.7-28.3-66.7-65.2 0-36.5 29.4-64.5 67.6-64.5 18.3 0 36.9 7.8 49.3 20.4l-14.3 17.9c-9.1-10.2-22.1-16.6-34.7-16.6-24.1 0-42.9 18.6-42.9 42.4 0 23.9 18.8 42.5 42.9 42.5 12.2 0 25.2-5.7 34.7-15l14.4 16.1c-13 13.4-31.8 22-50.3 22zm34.8-64.8c0 18.2-14.7 32.9-32.9 32.9s-32.9-14.7-32.9-32.9S76 51.4 94.2 51.4s32.9 14.7 32.9 32.9z" } )
		);

		blocks.registerBlockType( 'canto/canto-block', {
			title: i18n.__( 'Canto', 'canto' ),
	    icon: cantoLogo,
			category: 'common',
			attributes: {
	      isOpen: {
	        type: 'boolean',
	        default: false,
	      },
				mediaID: {
					type: 'number',
				},
				mediaURL: {
					type: 'string',
					source: 'attribute',
					selector: 'img',
					attribute: 'src',
				},
				mediaAttachment: {
					type: 'string',
				},
				altText: {
					type: 'string',
					default: '',
				},
			},
			edit: function( props ) {

				var attributes = props.attributes;
				var PostData = data.select("core/editor");
				var PostID = PostData.getCurrentPostId();

	      var openModal = function(  ) {
	        return props.setAttributes( {
						isOpen: true,
					} );
	      };

	      var closeModal = function(  ) {
	        return props.setAttributes( {
						isOpen: false,
					} );
	      };

				return (
					el( 'div', { className: props.className },
	          el( 'div', { className: 'canto-image' },
						!attributes.mediaID &&
							el( components.Button, {
			            className: 'button button-large aligncenter',
									id: 'canto-button',
			            onClick: openModal
		            },
		            'Select Asset from Canto'
		          ),
						attributes.mediaID &&
							el( RichText, {
								tagName: 'div',
								inline: false,
								value: props.attributes.mediaAttachment,
								onClick: openModal,
							} ),
	          attributes.isOpen &&
	            el( components.Modal, {
	                title: cantoLogo,
	                className: 'canto-modal',
	                onRequestClose: closeModal
	              },
								el( components.FocusableIframe , {
										src: `${args.FBC_URL}/block/canto.php?chromeless=1&tab=canto&post_id=${PostID}&FBC_URL=${args.FBC_URL}&args=${JSON.stringify(args)}&wpClientId=${JSON.stringify(props.clientId)}`,
										className: 'canto-iframe',
									}
								),
	            ),
	          ),
					)
				);
			},
			save: function( props ) {
				var attributes = props.attributes;

				return (
					el( 'div', { className: props.className },
						attributes.mediaID &&
							el( 'div', { className: 'canto-image' },
								el( RichText.Content, {
										tagName: 'div', value: attributes.mediaAttachment
								} ),
							),
					)
				);
			},
		} );
	}
} )(
	window.wp.blocks,
	window.wp.editor,
	window.wp.i18n,
	window.wp.element,
	window.wp.components,
	window.wp.data,
	window._,
);
