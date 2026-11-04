(function (wp) {
	const { registerBlockType, createBlock } = wp.blocks;
	const { createElement: el, Fragment, useState } = wp.element;
	const { InspectorControls, MediaUpload, MediaUploadCheck, useBlockProps, InnerBlocks, RichText } = wp.blockEditor;
	const { PanelBody, TextControl, TextareaControl, SelectControl, ToggleControl, Button, FormTokenField } = wp.components;
	const { useSelect, useDispatch } = wp.data;
	const { __ } = wp.i18n;
	const ServerSideRender = wp.serverSideRender;

	if (wp.blocks.registerBlockCollection) {
		wp.blocks.registerBlockCollection('sparkvn', { title: 'Spark' });
	}

	function resolveUri(url) {
		if (!url) {
			return '';
		}
		if (/^(https?:)?\/\//i.test(url) || url.indexOf('data:') === 0 || url.charAt(0) === '/') {
			return url;
		}
		const base = (window.sparkvnEditor && sparkvnEditor.themeUri) || '';
		return base ? base.replace(/\/$/, '') + '/' + url.replace(/^\//, '') : url;
	}

	function mediaButton(label, onSelect, allowed) {
		return el(MediaUploadCheck, {},
			el(MediaUpload, {
				onSelect: onSelect,
				allowedTypes: allowed || ['image'],
				render: function (obj) {
					return el(Button, { variant: 'secondary', onClick: obj.open }, label);
				},
			})
		);
	}

	function MediaField(props) {
		const { url, onSelect, onClear, label, allowed } = props;
		const src = resolveUri(url);
		return el('div', { className: 'sparkvn-media-field', style: { marginBottom: '12px' } },
			src ? el(allowed && allowed[0] === 'video' ? 'video' : 'img', { src: src, alt: '', style: { maxWidth: '100%', display: 'block', marginBottom: '8px' }, controls: allowed && allowed[0] === 'video' }) : null,
			mediaButton(label || __('Choose image', 'sparkvn'), onSelect, allowed),
			url ? el(Button, { variant: 'link', isDestructive: true, onClick: onClear }, __('Remove', 'sparkvn')) : null
		);
	}

	function Canvas(props) {
		return el('div', useBlockProps({ className: 'sparkvn-ssr-canvas' }),
			el(ServerSideRender, {
				block: props.block,
				attributes: props.attributes,
			})
		);
	}

	registerBlockType('sparkvn/hero', {
		edit: function (props) {
			const a = props.attributes;
			const set = props.setAttributes;
			return el(Fragment, {},
				el(InspectorControls, {},
					el(PanelBody, { title: __('Layout', 'sparkvn'), initialOpen: true },
						el(SelectControl, {
							label: __('Variation', 'sparkvn'),
							value: a.variation,
							options: [
								{ label: 'Page', value: 'page' },
								{ label: 'Home (video)', value: 'home' },
							],
							onChange: function (v) { set({ variation: v }); },
						}),
						el(ToggleControl, { label: __('Center', 'sparkvn'), checked: !!a.center, onChange: function (v) { set({ center: v }); } }),
						el(ToggleControl, { label: __('Short', 'sparkvn'), checked: !!a.short, onChange: function (v) { set({ short: v }); } }),
						el(TextControl, { label: __('Eyebrow', 'sparkvn'), value: a.eyebrow, onChange: function (v) { set({ eyebrow: v }); } }),
						el(TextControl, { label: __('Title', 'sparkvn'), value: a.title, onChange: function (v) { set({ title: v }); } }),
						el(TextareaControl, { label: __('Lead', 'sparkvn'), value: a.lead, onChange: function (v) { set({ lead: v }); } }),
						el(TextControl, { label: __('Aria label', 'sparkvn'), value: a.ariaLabel, onChange: function (v) { set({ ariaLabel: v }); } })
					),
					el(PanelBody, { title: __('Media', 'sparkvn'), initialOpen: true },
						el(MediaField, {
							url: a.imageUrl,
							label: __('Hero image', 'sparkvn'),
							onSelect: function (m) { set({ imageUrl: m.url }); },
							onClear: function () { set({ imageUrl: '' }); },
						}),
						a.variation === 'home' ? el(MediaField, {
							url: a.posterUrl,
							label: __('Video poster', 'sparkvn'),
							onSelect: function (m) { set({ posterUrl: m.url }); },
							onClear: function () { set({ posterUrl: '' }); },
						}) : null,
						a.variation === 'home' ? el(MediaField, {
							url: a.videoUrl,
							label: __('Hero video', 'sparkvn'),
							allowed: ['video'],
							onSelect: function (m) { set({ videoUrl: m.url }); },
							onClear: function () { set({ videoUrl: '' }); },
						}) : null
					),
					el(PanelBody, { title: __('CTA', 'sparkvn'), initialOpen: false },
						el(TextControl, { label: __('CTA label', 'sparkvn'), value: a.ctaLabel, onChange: function (v) { set({ ctaLabel: v }); } }),
						el(TextControl, { label: __('CTA URL', 'sparkvn'), value: a.ctaUrl, onChange: function (v) { set({ ctaUrl: v }); } }),
						el(TextareaControl, { label: __('Chips (one per line, or separated by |)', 'sparkvn'), value: a.chips, onChange: function (v) { set({ chips: v }); } }),
						el(TextControl, { label: __('Lines (home tagline)', 'sparkvn'), value: a.lines, onChange: function (v) { set({ lines: v }); } }),
						el(TextControl, { label: __('Lines small', 'sparkvn'), value: a.linesSmall, onChange: function (v) { set({ linesSmall: v }); } })
					)
				),
				el(Canvas, { block: 'sparkvn/hero', attributes: a })
			);
		},
		save: function () { return null; },
	});

	function tabLabels(raw) {
		return String(raw || '')
			.split(/\s*\|\s*|\r\n|\r|\n/)
			.map(function (s) { return s.trim(); })
			.filter(Boolean);
	}

	function tabLabelNodes(label) {
		if (String(label).indexOf('::') === -1) {
			return label;
		}
		const parts = String(label).split('::');
		return [
			el('span', { className: 'k' }, parts[0]),
			parts.slice(1).join('::'),
		];
	}

	function splitPanelInner(title) {
		return [
			createBlock('core/group', { className: 'split-media' }, [
				createBlock('core/image'),
			]),
			createBlock('core/group', { className: 'split-copy copy' }, [
				createBlock('core/heading', { level: 3, content: title || '' }),
				createBlock('core/paragraph', { placeholder: __('Describe this level…', 'sparkvn') }),
				createBlock('core/heading', { level: 4, content: __('Skills Developed', 'sparkvn') }),
				createBlock('core/list', {}, [
					createBlock('core/list-item', { content: __('Skill one', 'sparkvn') }),
				]),
				createBlock('core/heading', { level: 4, content: __('Outcome', 'sparkvn') }),
				createBlock('core/paragraph', { placeholder: __('What students can do after this level…', 'sparkvn') }),
			]),
		];
	}

	function copyPanelInner(title) {
		return [
			createBlock('core/heading', { level: 3, content: title || '' }),
			createBlock('core/paragraph', { placeholder: __('Tab copy…', 'sparkvn') }),
		];
	}

	function panelLabel(block, index, fallbacks) {
		if (block && block.name === 'sparkvn/tab-panel' && block.attributes && block.attributes.label) {
			return block.attributes.label;
		}
		if (fallbacks && fallbacks[index]) {
			return fallbacks[index];
		}
		return __('Tab', 'sparkvn') + ' ' + (index + 1);
	}

	function panelLayout(block) {
		if (block && block.name === 'sparkvn/tab-panel') {
			return block.attributes.layout || 'split';
		}
		const cls = (block && block.attributes && block.attributes.className) || '';
		return cls.indexOf('split') !== -1 ? 'split' : 'copy';
	}

	registerBlockType('sparkvn/tab-panel', {
		edit: function (props) {
			const layout = props.attributes.layout || 'split';
			return el(Fragment, {},
				el(InspectorControls, {},
					el(PanelBody, { title: __('Tab', 'sparkvn'), initialOpen: true },
						el(TextControl, {
							label: __('Tab label', 'sparkvn'),
							value: props.attributes.label || '',
							onChange: function (v) { props.setAttributes({ label: v }); },
							help: __('Shown on the tab button.', 'sparkvn'),
						}),
						el(SelectControl, {
							label: __('Layout', 'sparkvn'),
							value: layout,
							options: [
								{ label: __('Image + copy', 'sparkvn'), value: 'split' },
								{ label: __('Copy only', 'sparkvn'), value: 'copy' },
							],
							onChange: function (v) { props.setAttributes({ layout: v }); },
						})
					)
				),
				el('div', useBlockProps({ className: 'tab-panel ' + layout + ' active' }),
					el(InnerBlocks, {
						template: layout === 'split'
							? [
								['core/group', { className: 'split-media' }, [['core/image', {}]]],
								['core/group', { className: 'split-copy copy' }, [
									['core/heading', { level: 3, placeholder: __('Level title…', 'sparkvn') }],
									['core/paragraph', { placeholder: __('Describe this level…', 'sparkvn') }],
								]],
							]
							: [
								['core/heading', { level: 3, placeholder: __('Title…', 'sparkvn') }],
								['core/paragraph', { placeholder: __('Tab copy…', 'sparkvn') }],
							],
						templateLock: false,
					})
				)
			);
		},
		save: function (props) {
			const layout = (props.attributes && props.attributes.layout) || 'split';
			return el('div', useBlockProps.save({ className: 'tab-panel ' + layout }),
				el(InnerBlocks.Content)
			);
		},
	});

	registerBlockType('sparkvn/tabs', {
		edit: function (props) {
			const a = props.attributes;
			const [active, setActive] = useState(0);
			const [editing, setEditing] = useState(-1);
			const inner = useSelect(function (select) {
				return select('core/block-editor').getBlocks(props.clientId);
			}, [props.clientId]);
			const { insertBlock, removeBlock, updateBlockAttributes } = useDispatch('core/block-editor');
			const fallbacks = tabLabels(a.labels);
			const count = inner.length;
			const current = Math.min(active, Math.max(0, count - 1));

			function syncLabels(nextInner, override) {
				const names = (nextInner || inner).map(function (b, i) {
					if (override && override.index === i) {
						return override.value;
					}
					return panelLabel(b, i, fallbacks);
				});
				props.setAttributes({ labels: names.join(' | ') });
			}

			function addTab() {
				const n = count + 1;
				const layout = count ? panelLayout(inner[count - 1]) : 'split';
				const title = __('Level', 'sparkvn') + ' ' + n;
				const block = createBlock(
					'sparkvn/tab-panel',
					{ label: title, layout: layout },
					layout === 'split' ? splitPanelInner(title) : copyPanelInner(title)
				);
				insertBlock(block, count, props.clientId, true);
				setActive(count);
				setEditing(-1);
				const names = inner.map(function (b, i) { return panelLabel(b, i, fallbacks); });
				names.push(title);
				props.setAttributes({ labels: names.join(' | ') });
			}

			function removeTab(i) {
				if (count < 2 || !inner[i]) {
					return;
				}
				removeBlock(inner[i].clientId, false);
				const names = inner.filter(function (_b, idx) { return idx !== i; }).map(function (b, idx) {
					return panelLabel(b, idx, fallbacks);
				});
				props.setAttributes({ labels: names.join(' | ') });
				setActive(Math.max(0, i - 1));
				setEditing(-1);
			}

			function renameTab(i, value) {
				if (inner[i] && inner[i].name === 'sparkvn/tab-panel') {
					updateBlockAttributes(inner[i].clientId, { label: value });
				}
				syncLabels(inner, { index: i, value: value });
			}

			let cls = 'tabs sparkvn-tabs-editor';
			if (a.variation === 'opt-tabs') {
				cls += ' opt-tabs';
			}
			if (a.variation === 'school-tabs') {
				cls += ' school-tabs';
			}
			const listCls = a.variation === 'letters' ? 'spark-letters tab-list' : 'tab-list';

			return el(Fragment, {},
				el(InspectorControls, {},
					el(PanelBody, { title: __('Tabs', 'sparkvn') },
						el(SelectControl, {
							label: __('Style', 'sparkvn'),
							value: a.variation,
							options: [
								{ label: 'Default', value: 'default' },
								{ label: 'School tabs', value: 'school-tabs' },
								{ label: 'Option tabs', value: 'opt-tabs' },
								{ label: 'Letter tabs', value: 'letters' },
							],
							onChange: function (v) { props.setAttributes({ variation: v }); },
						}),
						el(TextControl, {
							label: __('Heading (optional)', 'sparkvn'),
							value: a.heading,
							onChange: function (v) { props.setAttributes({ heading: v }); },
						}),
						el('p', { className: 'components-base-control__help', style: { marginTop: '8px' } },
							__('Click a tab to switch. Double-click to rename. Use × to remove. + Add tab to add another.', 'sparkvn')
						),
						el(Button, { variant: 'secondary', onClick: addTab, style: { marginTop: '8px' } }, __('Add tab', 'sparkvn'))
					)
				),
				el('div', useBlockProps({ className: cls, 'data-active-tab': String(current) }),
					a.heading ? el('div', { className: 'sec-head' }, el('h2', {}, a.heading)) : null,
					el('div', { className: listCls, role: 'tablist' },
						inner.map(function (b, i) {
							const label = panelLabel(b, i, fallbacks);
							const isOn = i === current;
							return el('div', { key: b.clientId, className: 'sparkvn-tab-item' + (isOn ? ' is-active' : '') },
								el('button', {
									type: 'button',
									className: isOn ? 'active' : '',
									onClick: function () { setActive(i); },
									onDoubleClick: function (e) { e.preventDefault(); setActive(i); setEditing(i); },
								},
									editing === i
										? el('input', {
											className: 'sparkvn-tab-input',
											value: label,
											autoFocus: true,
											onClick: function (e) { e.stopPropagation(); },
											onChange: function (e) { renameTab(i, e.target.value); },
											onBlur: function () { setEditing(-1); },
											onKeyDown: function (e) {
												if (e.key === 'Enter' || e.key === 'Escape') {
													e.preventDefault();
													setEditing(-1);
												}
											},
										})
										: tabLabelNodes(label)
								),
								count > 1 ? el('button', {
									type: 'button',
									className: 'sparkvn-tab-remove',
									'aria-label': __('Remove tab', 'sparkvn'),
									onClick: function (e) { e.preventDefault(); e.stopPropagation(); removeTab(i); },
								}, '×') : null
							);
						}),
						el('button', {
							type: 'button',
							className: 'sparkvn-tab-add',
							onClick: addTab,
						}, '+ ' + __('Add tab', 'sparkvn'))
					),
					el('div', { className: 'tab-panels' },
						el(InnerBlocks, {
							allowedBlocks: ['sparkvn/tab-panel', 'core/group'],
							template: [
								['sparkvn/tab-panel', { label: 'Level 1', layout: 'split' }, [
									['core/group', { className: 'split-media' }, [['core/image', {}]]],
									['core/group', { className: 'split-copy copy' }, [
										['core/heading', { level: 3, content: 'Level 1' }],
										['core/paragraph', { placeholder: __('Describe this level…', 'sparkvn') }],
									]],
								]],
								['sparkvn/tab-panel', { label: 'Level 2', layout: 'split' }, [
									['core/group', { className: 'split-media' }, [['core/image', {}]]],
									['core/group', { className: 'split-copy copy' }, [
										['core/heading', { level: 3, content: 'Level 2' }],
										['core/paragraph', { placeholder: __('Describe this level…', 'sparkvn') }],
									]],
								]],
							],
							templateLock: false,
							renderAppender: function () { return null; },
						})
					)
				)
			);
		},
		save: function () { return el(InnerBlocks.Content); },
	});

	function queryBlock(name, title) {
		registerBlockType(name, {
			edit: function (props) {
				return el(Fragment, {},
					el(InspectorControls, {},
						el(PanelBody, { title: title },
							props.attributes && 'category' in props.attributes
								? el(TextControl, { label: __('Category', 'sparkvn'), value: props.attributes.category, onChange: function (v) { props.setAttributes({ category: v }); } })
								: null,
							props.attributes && 'title' in props.attributes
								? el(TextControl, { label: __('Heading', 'sparkvn'), value: props.attributes.title, onChange: function (v) { props.setAttributes({ title: v }); } })
								: null
						)
					),
					el(Canvas, { block: name, attributes: props.attributes })
				);
			},
			save: function () { return null; },
		});
	}
	queryBlock('sparkvn/query-programs', __('Programs hub', 'sparkvn'));
	queryBlock('sparkvn/query-prizes', __('Prize catalog', 'sparkvn'));
	queryBlock('sparkvn/query-resources', __('Resource library', 'sparkvn'));

	registerBlockType('sparkvn/query-posts', {
		edit: function (props) {
			const a = props.attributes;
			const set = props.setAttributes;
			const variation = a.variation || 'blog';
			const decode = (wp.htmlEntities && wp.htmlEntities.decodeEntities) || function (s) { return s; };
			const posts = useSelect(function (select) {
				return select('core').getEntityRecords('postType', 'post', { per_page: 100, status: 'publish' }) || [];
			}, []);
			const labelOf = function (p) { return decode(p.title.rendered || p.title.raw || '') + ' · ' + p.id; };
			const byLabel = {};
			posts.forEach(function (p) { byLabel[labelOf(p)] = p.id; });
			const byId = {};
			posts.forEach(function (p) { byId[p.id] = labelOf(p); });
			const selected = (a.ids || []).map(function (id) { return byId[id] || ('#' + id); });
			return el(Fragment, {},
				el(InspectorControls, {},
					el(PanelBody, { title: __('Posts query', 'sparkvn'), initialOpen: true },
						el(SelectControl, {
							label: __('Mode', 'sparkvn'),
							value: variation,
							options: [
								{ label: __('Blog archive', 'sparkvn'), value: 'blog' },
								{ label: __('Featured picks', 'sparkvn'), value: 'featured' },
								{ label: __('More posts', 'sparkvn'), value: 'more' },
							],
							onChange: function (v) { set({ variation: v }); },
						}),
						variation === 'featured'
							? el(FormTokenField, {
								label: __('Featured posts', 'sparkvn'),
								value: selected,
								suggestions: posts.map(labelOf),
								onChange: function (tokens) {
									set({ ids: tokens.map(function (t) { return byLabel[t] || parseInt(String(t).replace(/\D/g, ''), 10); }).filter(Boolean) });
								},
								placeholder: __('Type to add a post…', 'sparkvn'),
								__experimentalExpandOnFocus: true,
							})
							: (variation === 'blog' ? el(TextControl, { label: __('Category slug', 'sparkvn'), value: a.category, onChange: function (v) { set({ category: v }); } }) : null),
						el(TextControl, { label: __('Heading (optional)', 'sparkvn'), value: a.title, onChange: function (v) { set({ title: v }); } }),
						el(ToggleControl, { label: __('Center heading', 'sparkvn'), checked: !!a.center, onChange: function (v) { set({ center: v }); } })
					)
				),
				el(Canvas, { block: 'sparkvn/query-posts', attributes: a })
			);
		},
		save: function () { return null; },
	});

	registerBlockType('sparkvn/form', {
		edit: function (props) {
			const a = props.attributes;
			return el(Fragment, {},
				el(InspectorControls, {},
					el(PanelBody, { title: __('Form', 'sparkvn') },
						el(SelectControl, {
							label: __('Variation', 'sparkvn'),
							value: a.variation,
							options: [
								{ label: 'Assessment', value: 'assessment' },
								{ label: 'Contact', value: 'contact' },
								{ label: 'Resource', value: 'resource' },
								{ label: 'Ask a Teacher', value: 'ask-teacher' },
							],
							onChange: function (v) { props.setAttributes({ variation: v }); },
						}),
						el(TextControl, { label: __('Heading', 'sparkvn'), value: a.heading, onChange: function (v) { props.setAttributes({ heading: v }); } }),
						el(TextareaControl, { label: __('Subcopy', 'sparkvn'), value: a.sub, onChange: function (v) { props.setAttributes({ sub: v }); } })
					)
				),
				el(Canvas, { block: 'sparkvn/form', attributes: a })
			);
		},
		save: function () { return null; },
	});

	const goArrow = el('svg', {
		viewBox: '0 0 24 24',
		fill: 'none',
		stroke: 'currentColor',
		strokeWidth: '2',
		strokeLinecap: 'round',
		strokeLinejoin: 'round',
		'aria-hidden': 'true',
	}, el('path', { d: 'M5 12h14M13 6l6 6-6 6' }));

	registerBlockType('sparkvn/card', {
		edit: function (props) {
			const a = props.attributes;
			const set = props.setAttributes;
			const variation = a.variation || 'hub';
			const img = resolveUri(a.imageUrl);
			const inspector = el(InspectorControls, {},
				el(PanelBody, { title: __('Card', 'sparkvn'), initialOpen: true },
					el(SelectControl, {
						label: __('Type', 'sparkvn'),
						value: variation,
						options: [
							{ label: 'Hub', value: 'hub' },
							{ label: 'Advantage', value: 'advantage' },
							{ label: 'Team', value: 'team' },
							{ label: 'Testimonial', value: 'testimonial' },
							{ label: 'Program', value: 'program' },
						],
						onChange: function (v) { set({ variation: v }); },
					}),
					el(TextControl, {
						label: __('Link URL', 'sparkvn'),
						value: a.url,
						onChange: function (v) { set({ url: v }); },
						help: __('Where this card goes when clicked.', 'sparkvn'),
					}),
					variation !== 'hub' ? el(TextControl, { label: __('Title', 'sparkvn'), value: a.title, onChange: function (v) { set({ title: v }); } }) : null,
					variation !== 'hub' ? el(TextareaControl, { label: __('Description', 'sparkvn'), value: a.excerpt, onChange: function (v) { set({ excerpt: v }); } }) : null,
					variation !== 'hub' ? el(TextControl, { label: __('Go / CTA label', 'sparkvn'), value: a.goLabel, onChange: function (v) { set({ goLabel: v }); } }) : null,
					variation === 'hub' ? el(TextareaControl, {
						label: __('Description (optional)', 'sparkvn'),
						value: a.excerpt,
						onChange: function (v) { set({ excerpt: v }); },
						help: __('Leave empty for a compact Exclusive row. Add text for a Teachers Corner card.', 'sparkvn'),
					}) : null,
					variation === 'team' ? el(TextControl, { label: __('Role', 'sparkvn'), value: a.role, onChange: function (v) { set({ role: v }); } }) : null,
					variation === 'team' ? el(TextareaControl, { label: __('Full bio', 'sparkvn'), value: a.bio, help: __('Shown behind Read full bio. Use paragraph tags.', 'sparkvn'), onChange: function (v) { set({ bio: v }); } }) : null,
					variation === 'testimonial' ? el(TextControl, { label: __('Cite name', 'sparkvn'), value: a.cite, onChange: function (v) { set({ cite: v }); } }) : null,
					variation === 'testimonial' ? el(TextControl, { label: __('Source', 'sparkvn'), value: a.source, onChange: function (v) { set({ source: v }); } }) : null,
					variation === 'testimonial' ? el(TextControl, { label: __('Date', 'sparkvn'), value: a.date, onChange: function (v) { set({ date: v }); } }) : null,
					el(MediaField, {
						url: a.imageUrl,
						onSelect: function (m) { set({ imageUrl: m.url, imageId: m.id }); },
						onClear: function () { set({ imageUrl: '', imageId: 0 }); },
						label: __('Choose image (optional)', 'sparkvn'),
					})
				)
			);

			if (variation !== 'hub') {
				return el(Fragment, {}, inspector, el(Canvas, { block: 'sparkvn/card', attributes: a }));
			}

			const classes = ['hub-card', 'sparkvn-card'];
			if (img) {
				classes.push('has-img');
			} else if (!a.excerpt) {
				classes.push('hub-card--row');
			}

			const titleEl = el(RichText, {
				tagName: 'h3',
				value: a.title,
				allowedFormats: [],
				onChange: function (v) { set({ title: v }); },
				placeholder: __('Card title', 'sparkvn'),
			});
			const goEl = el('span', { className: 'go' },
				el(RichText, {
					tagName: 'span',
					value: a.goLabel,
					allowedFormats: [],
					onChange: function (v) { set({ goLabel: v }); },
					placeholder: __('Open', 'sparkvn'),
				}),
				goArrow
			);
			const excerptEl = el(RichText, {
				tagName: 'p',
				value: a.excerpt,
				onChange: function (v) { set({ excerpt: v }); },
				placeholder: __('Description (optional)', 'sparkvn'),
			});

			let body;
			if (img) {
				body = [
					el('img', { src: img, alt: '', key: 'img' }),
					el('div', { className: 'hc-body', key: 'body' }, titleEl, excerptEl, goEl),
				];
			} else if (a.excerpt) {
				body = [titleEl, excerptEl, goEl];
			} else {
				body = [titleEl, goEl];
			}

			return el(Fragment, {},
				inspector,
				el('div', useBlockProps({ className: classes.join(' ') }), body)
			);
		},
		save: function () { return null; },
	});

	function paintPatternScreenshots() {
		const shots = (window.sparkvnEditor && sparkvnEditor.screenshots) || {};
		Object.keys(shots).forEach(function (title) {
			document.querySelectorAll('.block-editor-block-patterns-list__list-item').forEach(function (item) {
				const label = item.getAttribute('aria-label') || '';
				if (label !== title && label.indexOf(title) !== 0) {
					return;
				}
				if (item.querySelector('.sparkvn-pattern-shot')) {
					return;
				}
				const preview = item.querySelector('.block-editor-block-preview__container');
				if (!preview) {
					return;
				}
				const img = document.createElement('img');
				img.className = 'sparkvn-pattern-shot';
				img.src = shots[title];
				img.alt = title;
				preview.innerHTML = '';
				preview.appendChild(img);
			});
		});
	}

	function paintBlockHoverShots() {
		const shots = (window.sparkvnEditor && sparkvnEditor.blockScreenshots) || {};
		Object.keys(shots).forEach(function (slug) {
			document.querySelectorAll('.editor-block-list-item-sparkvn-' + slug).forEach(function (item) {
				if (item.querySelector('.sparkvn-block-hover')) {
					return;
				}
				const img = document.createElement('img');
				img.className = 'sparkvn-block-hover';
				img.src = shots[slug];
				img.alt = '';
				item.appendChild(img);
			});
		});

		const preview = document.querySelector('.block-editor-inserter__preview .block-editor-block-preview__container, .block-editor-inserter__preview-content .block-editor-block-preview__container');
		const hovered = document.querySelector('[class*="editor-block-list-item-sparkvn-"]:hover, [class*="editor-block-list-item-sparkvn-"].is-hovered');
		if (!preview || !hovered) {
			return;
		}
		const match = hovered.className.match(/editor-block-list-item-sparkvn-([a-z0-9-]+)/);
		const src = match && shots[match[1]];
		if (!src || preview.querySelector('.sparkvn-block-hover-preview')) {
			return;
		}
		preview.innerHTML = '';
		const img = document.createElement('img');
		img.className = 'sparkvn-block-hover-preview';
		img.src = src;
		img.alt = '';
		preview.appendChild(img);
	}

	if (wp.domReady) {
		wp.domReady(function () {
			let timer;
			const run = function () {
				clearTimeout(timer);
				timer = setTimeout(function () {
					paintPatternScreenshots();
					paintBlockHoverShots();
				}, 120);
			};
			run();
			const mo = new MutationObserver(run);
			mo.observe(document.body, { childList: true, subtree: true });
		});
	}
})(window.wp);
