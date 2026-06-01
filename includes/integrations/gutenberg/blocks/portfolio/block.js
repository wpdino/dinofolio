(function (blocks, element, blockEditor, components, data, i18n, serverSideRender) {
	var el = element.createElement;
	var Fragment = element.Fragment;
	var useEffect = element.useEffect;
	var registerBlockType = blocks.registerBlockType;
	var useBlockProps = blockEditor.useBlockProps;
	var InspectorControls = blockEditor.InspectorControls;
	var PanelBody = components.PanelBody;
	var SelectControl = components.SelectControl;
	var RangeControl = components.RangeControl;
	var ToggleControl = components.ToggleControl;
	var TextControl = components.TextControl;
	var TreeSelect = components.TreeSelect;
	var Spinner = components.Spinner;
	var Disabled = components.Disabled;
	var useSelect = data.useSelect;
	var ServerSideRender = serverSideRender;
	var __ = i18n.__;

	var blockConfig = window.dinofolioBlockConfig_portfolio || { controls: [], sections: {} };
	var sections = blockConfig.sections || { content: __('Display', 'dinofolio'), query: __('Query', 'dinofolio') };

	function sanitizeTermIds(value) {
		if (!Array.isArray(value)) {
			return [];
		}

		return value
			.map(function (item) {
				return parseInt(item, 10);
			})
			.filter(function (item) {
				return !isNaN(item) && item > 0;
			});
	}

	function sanitizeAttributes(attributes, controls) {
		var sanitized = {};

		controls.forEach(function (control) {
			var key = control.name;
			var raw = attributes[key];

			if (control.type === 'checkbox') {
				sanitized[key] = !!raw;
				return;
			}

			if (control.type === 'taxonomy' || control.type === 'multiselect') {
				sanitized[key] = sanitizeTermIds(raw);
				return;
			}

			if (control.type === 'number') {
				if (raw === undefined || raw === null || raw === '') {
					sanitized[key] = parseInt(control.default, 10) || 0;
					return;
				}

				sanitized[key] = parseInt(raw, 10);
				if (isNaN(sanitized[key])) {
					sanitized[key] = parseInt(control.default, 10) || 0;
				}
				return;
			}

			if (raw === undefined || raw === null) {
				sanitized[key] = control.default !== undefined ? control.default : '';
				return;
			}

			sanitized[key] = String(raw);
		});

		return sanitized;
	}

	function valuesEqual(a, b) {
		if (Array.isArray(a) && Array.isArray(b)) {
			if (a.length !== b.length) {
				return false;
			}

			return a.every(function (item, index) {
				return item === b[index];
			});
		}

		return a === b;
	}

	function attributesNeedSync(attributes, sanitized, controlsList) {
		return controlsList.some(function (control) {
			var key = control.name;
			return !valuesEqual(attributes[key], sanitized[key]);
		});
	}

	function getAttributeValue(attributes, control) {
		var value = attributes[control.name];

		if (control.type === 'taxonomy' || control.type === 'multiselect') {
			return sanitizeTermIds(value);
		}

		if (value === undefined || value === null || value === '') {
			return control.default;
		}

		return value;
	}

	function buildTermsTree(flatTerms) {
		var byParent = {};

		flatTerms.forEach(function (term) {
			var parent = term.parent || 0;
			if (!byParent[parent]) {
				byParent[parent] = [];
			}
			byParent[parent].push(term);
		});

		function fillChildren(parentId) {
			return (byParent[parentId] || []).map(function (term) {
				return {
					id: term.id,
					name: term.name,
					children: fillChildren(term.id)
				};
			});
		}

		return fillChildren(0);
	}

	function TaxonomyControl(props) {
		var control = props.control;
		var attributes = props.attributes;
		var setAttributes = props.setAttributes;
		var terms = useSelect(
			function (select) {
				if (!control.taxonomy) {
					return [];
				}

				return select('core').getEntityRecords('taxonomy', control.taxonomy, {
					per_page: -1,
					hide_empty: false
				});
			},
			[control.taxonomy]
		);

		if (terms === null) {
			return el(Spinner);
		}

		var flatTerms = Array.isArray(terms) ? terms.slice() : [];
		flatTerms.sort(function (a, b) {
			return a.name.localeCompare(b.name);
		});

		var tree = control.hierarchical ? buildTermsTree(flatTerms) : flatTerms.map(function (term) {
			return {
				id: term.id,
				name: term.name,
				children: []
			};
		});

		tree.unshift({
			id: -1,
			name: __('All', 'dinofolio'),
			children: []
		});

		var selected = getAttributeValue(attributes, control);
		var selectedIds = selected.length ? selected.map(String) : ['-1'];

		return el(TreeSelect, {
			label: control.label,
			help: control.description || __('Multiple selections allowed. Choose All to show every item.', 'dinofolio'),
			tree: tree,
			selectedId: selectedIds,
			multiple: true,
			onChange: function (value) {
				var ids = Array.isArray(value) ? value : [value];
				var filtered = ids
					.map(function (id) {
						return parseInt(id, 10);
					})
					.filter(function (id) {
						return !isNaN(id) && id > 0;
					});

				var patch = {};
				patch[control.name] = filtered;
				setAttributes(patch);
			}
		});
	}

	function renderControl(control, attributes, setAttributes) {
		var value = getAttributeValue(attributes, control);
		var layout = attributes.layout || 'grid';
		var hideColumns = control.name === 'columns' && layout === 'list';
		var hideReadMoreLabel = control.name === 'readMoreLabel' && !attributes.showReadMore;
		var hideViewAllFields =
			(control.name === 'viewAllText' || control.name === 'viewAllLink') && !attributes.showViewAll;

		if (hideColumns || hideReadMoreLabel || hideViewAllFields) {
			return null;
		}

		if (control.type === 'taxonomy') {
			return el(TaxonomyControl, {
				key: control.name,
				control: control,
				attributes: attributes,
				setAttributes: setAttributes
			});
		}

		if (control.type === 'checkbox') {
			return el(ToggleControl, {
				key: control.name,
				label: control.label,
				checked: !!value,
				onChange: function (next) {
					var patch = {};
					patch[control.name] = next;
					setAttributes(patch);
				}
			});
		}

		if (control.type === 'number') {
			return el(RangeControl, {
				key: control.name,
				label: control.label,
				value: parseInt(value, 10) || 0,
				onChange: function (next) {
					var patch = {};
					patch[control.name] = next;
					setAttributes(patch);
				},
				min: control.min !== undefined ? control.min : undefined,
				max: control.max !== undefined ? control.max : undefined
			});
		}

		if (control.type === 'textfield') {
			return el(TextControl, {
				key: control.name,
				label: control.label,
				value: String(value || ''),
				onChange: function (next) {
					var patch = {};
					patch[control.name] = next;
					setAttributes(patch);
				}
			});
		}

		if (control.type === 'dropdown' && control.options) {
			return el(SelectControl, {
				key: control.name,
				label: control.label,
				value: String(value),
				options: control.options,
				onChange: function (next) {
					var patch = {};
					patch[control.name] = next;
					setAttributes(patch);
				}
			});
		}

		return null;
	}

	function groupControlsBySection(controlsList) {
		var grouped = {};

		Object.keys(sections).forEach(function (sectionKey) {
			grouped[sectionKey] = [];
		});

		controlsList.forEach(function (control) {
			var sectionKey = control.section || 'content';

			if (!grouped[sectionKey]) {
				grouped[sectionKey] = [];
			}

			grouped[sectionKey].push(control);
		});

		return grouped;
	}

	var controls = blockConfig.controls || [];
	var groupedControls = groupControlsBySection(controls);

	registerBlockType('dinofolio/portfolio', {
		edit: function (props) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var previewAttributes = sanitizeAttributes(attributes, controls);
			var blockProps = useBlockProps({
				className: 'dinofolio-portfolio-block-editor'
			});

			useEffect(
				function () {
					if (attributesNeedSync(attributes, previewAttributes, controls)) {
						setAttributes(previewAttributes);
					}
				},
				[]
			);

			return el(
				Fragment,
				{},
				el(
					InspectorControls,
					{},
					Object.keys(groupedControls).map(function (sectionKey) {
						var sectionControls = groupedControls[sectionKey];

						if (!sectionControls.length) {
							return null;
						}

						return el(
							PanelBody,
							{
								key: sectionKey,
								title: sections[sectionKey] || sectionKey,
								initialOpen: sectionKey === 'content'
							},
							sectionControls.map(function (control) {
								return renderControl(control, attributes, setAttributes);
							})
						);
					})
				),
				el(
					'div',
					blockProps,
					el(
						Disabled,
						null,
						el(ServerSideRender, {
							block: 'dinofolio/portfolio',
							attributes: previewAttributes,
							EmptyResponsePlaceholder: function () {
								return el('p', {}, __('No portfolio items found.', 'dinofolio'));
							},
							ErrorResponsePlaceholder: function () {
								return el(
									'p',
									{ className: 'dinofolio-block-preview-error' },
									__('Unable to load portfolio preview. Try refreshing the editor.', 'dinofolio')
								);
							}
						})
					)
				)
			);
		},
		save: function () {
			return null;
		}
	});
})(
	window.wp.blocks,
	window.wp.element,
	window.wp.blockEditor,
	window.wp.components,
	window.wp.data,
	window.wp.i18n,
	window.wp.serverSideRender
);
