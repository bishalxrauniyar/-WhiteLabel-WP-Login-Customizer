/**
 * WhiteLabel Login Customizer — settings page interactions.
 */
(function () {
	'use strict';

	var preview = document.getElementById('wlwp-login-preview');

	function wlwpSetVar(name, value) {
		if (preview && name) {
			preview.style.setProperty(name, value);
		}
	}

	function wlwpCssImageValue(url) {
		if (!url) {
			return 'none';
		}
		// Defensive: the server sanitizes URLs; this only affects the preview iframe-less DOM.
		return 'url("' + url.replace(/["'()<>\s]/g, '') + '")';
	}

	function wlwpHexToRgba(hex, alpha) {
		var value = String(hex || '').replace('#', '');
		if (/^[0-9a-f]{3}$/i.test(value)) {
			value = value[0] + value[0] + value[1] + value[1] + value[2] + value[2];
		}
		if (!/^[0-9a-f]{6}$/i.test(value)) {
			return 'rgba(0, 115, 170, ' + alpha + ')';
		}
		return 'rgba(' + parseInt(value.slice(0, 2), 16) + ', ' + parseInt(value.slice(2, 4), 16) + ', ' + parseInt(value.slice(4, 6), 16) + ', ' + alpha + ')';
	}

	function wlwpValueForField(el) {
		var value = el.value;
		var map = el.getAttribute('data-css-map');

		if (map) {
			try {
				var parsed = JSON.parse(map);
				if (Object.prototype.hasOwnProperty.call(parsed, value)) {
					return parsed[value];
				}
			} catch (error) {
				return value;
			}
		}

		if (el.getAttribute('data-css-image') === '1') {
			return wlwpCssImageValue(value);
		}

		var unit = el.getAttribute('data-css-unit');
		return unit ? value + unit : value;
	}

	function wlwpSyncField(el) {
		var name = el.getAttribute('data-css-var');
		if (name) {
			var value = wlwpValueForField(el);
			wlwpSetVar(name, value);
			if (name === '--wlwp-field-focus') {
				wlwpSetVar('--wlwp-field-focus-ring', wlwpHexToRgba(value, 0.25));
			}
			if (name === '--wlwp-font' && !value) {
				wlwpSetVar('--wlwp-font', '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif');
			}
		}

		var hideSelector = el.getAttribute('data-preview-hide');
		if (hideSelector && preview) {
			var targets = preview.querySelectorAll(hideSelector);
			for (var i = 0; i < targets.length; i++) {
				targets[i].style.display = el.checked ? 'none' : '';
			}
		}
	}

	var fields = document.querySelectorAll('[data-css-var], [data-preview-hide]');
	for (var i = 0; i < fields.length; i++) {
		fields[i].addEventListener('input', function () {
			wlwpSyncField(this);
		});
		fields[i].addEventListener('change', function () {
			wlwpSyncField(this);
		});
		wlwpSyncField(fields[i]);
	}

	/* Hovering or focusing a setting shows which part of the preview it affects. */
	var highlighted = [];

	function wlwpClearHighlight() {
		for (var h = 0; h < highlighted.length; h++) {
			highlighted[h].classList.remove('wlwp-highlight');
		}
		highlighted = [];
	}

	function wlwpHighlightTarget(source) {
		wlwpClearHighlight();

		var selector = source.getAttribute('data-preview-target');
		if (!selector || !preview) {
			return;
		}

		var targets = preview.querySelectorAll(selector);
		for (var t = 0; t < targets.length; t++) {
			targets[t].classList.add('wlwp-highlight');
			highlighted.push(targets[t]);
		}
	}

	var targetSources = document.querySelectorAll('[data-preview-target]');
	for (var s = 0; s < targetSources.length; s++) {
		targetSources[s].addEventListener('mouseenter', function () {
			wlwpHighlightTarget(this);
		});
		targetSources[s].addEventListener('mouseleave', wlwpClearHighlight);
		targetSources[s].addEventListener('focusin', function () {
			wlwpHighlightTarget(this);
		});
		targetSources[s].addEventListener('focusout', wlwpClearHighlight);
	}

	/* Section tabs, with deep links (#general, #colors, ...) and arrow keys. */
	var tabs = document.querySelectorAll('[data-wlwp-tab]');
	var panels = document.querySelectorAll('[data-wlwp-panel]');

	function wlwpActivateTab(id, updateHash) {
		for (var t = 0; t < tabs.length; t++) {
			var active = tabs[t].getAttribute('data-wlwp-tab') === id;
			tabs[t].classList.toggle('is-active', active);
			tabs[t].setAttribute('aria-selected', active ? 'true' : 'false');
		}
		for (var p = 0; p < panels.length; p++) {
			panels[p].hidden = panels[p].getAttribute('data-wlwp-panel') !== id;
		}
		if (updateHash && window.history && window.history.replaceState) {
			window.history.replaceState(null, '', '#' + id);
		}
	}

	for (var n = 0; n < tabs.length; n++) {
		tabs[n].addEventListener('click', function () {
			wlwpActivateTab(this.getAttribute('data-wlwp-tab'), true);
		});
		tabs[n].addEventListener('keydown', function (event) {
			if (event.key !== 'ArrowRight' && event.key !== 'ArrowLeft') {
				return;
			}
			event.preventDefault();
			var current = Array.prototype.indexOf.call(tabs, this);
			var step = event.key === 'ArrowRight' ? 1 : tabs.length - 1;
			var next = (current + step) % tabs.length;
			tabs[next].focus();
			wlwpActivateTab(tabs[next].getAttribute('data-wlwp-tab'), true);
		});
	}

	var hash = (window.location.hash || '').replace('#', '');
	for (var m = 0; m < tabs.length; m++) {
		if (tabs[m].getAttribute('data-wlwp-tab') === hash) {
			wlwpActivateTab(hash, false);
			break;
		}
	}

	/* One-click palettes. Defaults stay stock WordPress. */
	var palettes = (window.wlwpLoginAdmin && window.wlwpLoginAdmin.palettes) || {};
	var presetButtons = document.querySelectorAll('[data-wlwp-preset]');

	for (var p = 0; p < presetButtons.length; p++) {
		presetButtons[p].addEventListener('click', function () {
			var self = this;
			var palette = palettes[self.getAttribute('data-wlwp-preset')];
			if (!palette || !palette.colors) {
				return;
			}

			Object.keys(palette.colors).forEach(function (key) {
				var input = document.querySelector('[name="wlwp_login_options[' + key + ']"]');
				if (!input) {
					return;
				}
				input.value = palette.colors[key];
				input.dispatchEvent(new Event('input', { bubbles: true }));
				input.dispatchEvent(new Event('change', { bubbles: true }));
			});

			var label = self.querySelector('.wlwp-palette-name');
			var original = label ? label.textContent : self.textContent;
			var message = 'Applied — save changes';
			if (label) {
				label.textContent = message;
			} else {
				self.textContent = message;
			}
			self.classList.add('is-applied');
			window.setTimeout(function () {
				if (label) {
					label.textContent = original;
				} else {
					self.textContent = original;
				}
				self.classList.remove('is-applied');
			}, 2200);
		});
	}

	function wlwpOpenMediaPicker(button) {
		if (typeof window.wp === 'undefined' || !window.wp.media) {
			window.alert('Media library is not available.');
			return;
		}

		var wrap = button.closest('.wlwp-media-field');
		if (!wrap) {
			return;
		}

		var frame = window.wp.media({
			title: button.getAttribute('data-media-title') || 'Choose image',
			button: { text: 'Use this image' },
			multiple: false
		});

		frame.on('select', function () {
			var attachment = frame.state().get('selection').first().toJSON();
			var url = attachment.url || attachment.source_url || '';
			var idInput = wrap.querySelector('[data-media-id]');
			var urlInput = wrap.querySelector('[data-media-url]');
			var image = wrap.querySelector('[data-media-preview]');
			var remove = wrap.querySelector('.wlwp-media-remove');

			if (idInput) {
				idInput.value = attachment.id;
			}
			if (urlInput) {
				urlInput.value = url;
				urlInput.dispatchEvent(new Event('change', { bubbles: true }));
			}
			if (image) {
				image.src = url;
				image.style.display = 'block';
			}
			if (remove) {
				remove.style.display = '';
			}
		});

		frame.open();
	}

	function wlwpCreateLoginPage(button) {
		var config = window.wlwpLoginAdmin || {};
		var labels = config.createPage || {};
		var status = button.parentElement.querySelector('.wlwp-create-page-status');
		var titleInput = document.getElementById('wlwp-new-login-page-title');
		var original = button.textContent;

		function message(text, isError) {
			if (status) {
				status.textContent = text;
				status.classList.toggle('is-error', !!isError);
			}
		}

		if (!config.ajaxUrl || !config.createNonce) {
			message(labels.error || 'Could not create the page. Please try again.', true);
			return;
		}

		var body = new FormData();
		body.append('action', 'wlwp_create_page');
		body.append('nonce', config.createNonce);
		body.append('title', titleInput ? titleInput.value : '');

		button.disabled = true;
		button.textContent = labels.working || 'Creating…';
		message('');

		fetch(config.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: body
		})
			.then(function (response) {
				return response.json();
			})
			.then(function (result) {
				if (!result.success || !result.data) {
					throw new Error('create failed');
				}

				var select = document.querySelector('select[name="wlwp_login_options[custom_page_id]"]');
				if (select) {
					var option = new Option(result.data.title, result.data.id, true, true);
					select.add(option);
					select.value = String(result.data.id);
				}

				var enabled = document.querySelector('input[name="wlwp_login_options[custom_page_enabled]"]');
				if (enabled) {
					enabled.checked = true;
				}

				var viewLink = document.querySelector('.wlwp-view-link');
				if (viewLink && result.data.url) {
					viewLink.href = result.data.url;
				}

				message(labels.done || 'Page created and selected.', false);
			})
			.catch(function () {
				message(labels.error || 'Could not create the page. Please try again.', true);
			})
			.then(function () {
				button.disabled = false;
				button.textContent = original;
			});
	}

	function wlwpCopyToClipboard(text, button) {
		var original = button.textContent;
		var doneLabel = button.getAttribute('data-copied-label') || 'Copied!';
		var failLabel = button.getAttribute('data-copy-failed-label') || 'Copy failed';

		function show(label) {
			button.textContent = label;
			window.setTimeout(function () {
				button.textContent = original;
			}, 1800);
		}

		if (navigator.clipboard && window.isSecureContext) {
			navigator.clipboard.writeText(text).then(function () {
				show(doneLabel);
			}).catch(function () {
				show(failLabel);
			});
			return;
		}

		var temp = document.createElement('textarea');
		temp.value = text;
		temp.setAttribute('readonly', '');
		temp.style.position = 'absolute';
		temp.style.left = '-9999px';
		document.body.appendChild(temp);
		temp.select();
		var copied = false;
		try {
			copied = document.execCommand('copy');
		} catch (error) {
			copied = false;
		}
		document.body.removeChild(temp);
		show(copied ? doneLabel : failLabel);
	}

	document.addEventListener('click', function (event) {
		var create = event.target.closest('.wlwp-create-login-page');
		if (create) {
			event.preventDefault();
			wlwpCreateLoginPage(create);
			return;
		}

		var copy = event.target.closest('.wlwp-copy-shortcode');
		if (copy) {
			event.preventDefault();
			wlwpCopyToClipboard(copy.getAttribute('data-wlwp-copy-shortcode') || '', copy);
			return;
		}

		var choose = event.target.closest('.wlwp-media-choose');
		if (choose) {
			event.preventDefault();
			wlwpOpenMediaPicker(choose);
			return;
		}

		var remove = event.target.closest('.wlwp-media-remove');
		if (remove) {
			event.preventDefault();
			var wrap = remove.closest('.wlwp-media-field');
			if (!wrap) {
				return;
			}

			var idInput = wrap.querySelector('[data-media-id]');
			var urlInput = wrap.querySelector('[data-media-url]');
			var image = wrap.querySelector('[data-media-preview]');

			if (idInput) {
				idInput.value = '0';
			}
			if (urlInput) {
				urlInput.value = '';
				urlInput.dispatchEvent(new Event('change', { bubbles: true }));
			}
			if (image) {
				image.src = '';
				image.style.display = 'none';
			}
			remove.style.display = 'none';
		}
	});

	var deviceButtons = document.querySelectorAll('[data-wlwp-device]');
	for (var d = 0; d < deviceButtons.length; d++) {
		deviceButtons[d].addEventListener('click', function () {
			var device = this.getAttribute('data-wlwp-device');
			var container = document.querySelector('.wlwp-preview-device');
			if (container) {
				container.className = 'wlwp-preview-device wlwp-device-' + device;
			}
			for (var j = 0; j < deviceButtons.length; j++) {
				deviceButtons[j].classList.toggle('active', deviceButtons[j] === this);
			}
		});
	}

	var resetForm = document.querySelector('.wlwp-reset-form');
	if (resetForm) {
		resetForm.addEventListener('submit', function (event) {
			var message = (window.wlwpLoginAdmin && window.wlwpLoginAdmin.confirmReset) || 'Reset all settings?';
			if (!window.confirm(message)) {
				event.preventDefault();
			}
		});
	}
}());
