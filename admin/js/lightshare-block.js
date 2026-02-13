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

	registerBlockType("lightshare/share-buttons", {
		title: __("Lightshare Buttons", "lightshare-social-sharing"),
		icon: "share",
		category: "widgets",
		attributes: {
			networks: {
				type: "string",
				default: ""
			},
			showLabel: {
				type: "boolean",
				default: true
			},
			labelText: {
				type: "string",
				default: ""
			}
		},
		edit: function (props) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;

			return [
				el(
					InspectorControls,
					{ key: "inspector" },
					el(
						PanelBody,
						{
							title: __(
								"Lightshare Settings",
								"lightshare-social-sharing"
							),
							initialOpen: true
						},
						el(TextControl, {
							label: __(
								"Networks (comma-separated)",
								"lightshare-social-sharing"
							),
							value: attributes.networks,
							onChange: function (value) {
								setAttributes({ networks: value });
							}
						}),
						el(ToggleControl, {
							label: __("Show Label", "lightshare-social-sharing"),
							checked: attributes.showLabel,
							onChange: function (value) {
								setAttributes({ showLabel: value });
							}
						}),
						el(TextControl, {
							label: __("Label Text", "lightshare-social-sharing"),
							value: attributes.labelText,
							onChange: function (value) {
								setAttributes({ labelText: value });
							}
						})
					)
				),
				el(
					"div",
					{ className: "lightshare-block-preview" },
					el(ServerSideRender, {
						block: "lightshare/share-buttons",
						attributes: attributes
					})
				)
			];
		},
		save: function () {
			return null;
		}
	});

	registerBlockVariation("core/shortcode", {
		name: "lightshare-shortcode",
		title: __("Lightshare Shortcode", "lightshare-social-sharing"),
		description: __(
			"Insert the Lightshare shortcode.",
			"lightshare-social-sharing"
		),
		attributes: { text: "[lightshare]" },
		isActive: function (blockAttributes) {
			return blockAttributes.text === "[lightshare]";
		}
	});
})(window.wp);
