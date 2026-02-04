(function (wp) {
	if (!wp || !wp.blocks) {
		return;
	}

	var registerBlockType = wp.blocks.registerBlockType;
	var registerBlockVariation = wp.blocks.registerBlockVariation;
	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var InspectorControls = (wp.blockEditor || wp.editor).InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var TextControl = wp.components.TextControl;
	var SelectControl = wp.components.SelectControl;
	var ToggleControl = wp.components.ToggleControl;
	var ServerSideRender = wp.serverSideRender;

	registerBlockType('lightshare/share-buttons', {
		title: __('Lightshare Buttons', 'lightshare'),
		icon: 'share',
		category: 'widgets',
		attributes: {
			networks: {
				type: 'string',
				default: ''
			},
			style: {
				type: 'string',
				default: ''
			},
			showLabel: {
				type: 'boolean',
				default: true
			},
			labelText: {
				type: 'string',
				default: ''
			}
		},
		edit: function (props) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;

			return [
				el(
					InspectorControls,
					{ key: 'inspector' },
					el(
						PanelBody,
						{ title: __('Lightshare Settings', 'lightshare'), initialOpen: true },
						el(TextControl, {
							label: __('Networks (comma-separated)', 'lightshare'),
							value: attributes.networks,
							onChange: function (value) {
								setAttributes({ networks: value });
							}
						}),
						el(SelectControl, {
							label: __('Style', 'lightshare'),
							value: attributes.style,
							options: [
								{ label: __('Default', 'lightshare'), value: '' },
								{ label: __('Rounded', 'lightshare'), value: 'rounded' },
								{ label: __('Circle', 'lightshare'), value: 'circle' },
								{ label: __('Minimal', 'lightshare'), value: 'minimal' }
							],
							onChange: function (value) {
								setAttributes({ style: value });
							}
						}),
						el(ToggleControl, {
							label: __('Show Label', 'lightshare'),
							checked: attributes.showLabel,
							onChange: function (value) {
								setAttributes({ showLabel: value });
							}
						}),
						el(TextControl, {
							label: __('Label Text', 'lightshare'),
							value: attributes.labelText,
							onChange: function (value) {
								setAttributes({ labelText: value });
							}
						})
					)
				),
				el(
					'div',
					{ className: 'lightshare-block-preview' },
					el(ServerSideRender, {
						block: 'lightshare/share-buttons',
						attributes: attributes
					})
				)
			];
		},
		save: function () {
			return null;
		}
	});

	registerBlockVariation('core/shortcode', {
		name: 'lightshare-shortcode',
		title: __('Lightshare Shortcode', 'lightshare'),
		description: __('Insert the Lightshare shortcode.', 'lightshare'),
		attributes: { text: '[lightshare]' },
		isActive: function (blockAttributes) {
			return blockAttributes.text === '[lightshare]';
		}
	});
})(window.wp);
