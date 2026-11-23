/**
 * WhiteLabel Login Customizer — settings page interactions.
 */
(function () {
	'use strict';

	var preview = document.getElementById('wl-login-preview');

	function setVar(name, value) {
		if (preview && name) {
			preview.style.setProperty(name, value);
		}
	}

	function cssImageValue(url) {
		if (!url) {
			return 'none';
		}
		// Defensive: the server sanitizes URLs; this only affects the preview iframe-less DOM.
		return 'url("' + url.replace(/["'()<>\s]/g, '') + '")';
	}

	function hexToRgba(hex, alpha) {
		var value = String(hex || '').replace('#', '');
		if (/^[0-9a-f]{3}$/i.test(value)) {
			value = value[0] + value[0] + value[1] + value[1] + value[2] + value[2];
		}
		if (!/^[0-9a-f]{6}$/i.test(value)) {
			return 'rgba(0, 115, 170, ' + alpha + ')';
		}
		return 'rgba(' + parseInt(value.slice(0, 2), 16) + ', ' + parseInt(value.slice(2, 4), 16) + ', ' + parseInt(value.slice(4, 6), 16) + ', ' + alpha + ')';
	}

	function valueForField(el) {
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
			return cssImageValue(value);
		}

		var unit = el.getAttribute('data-css-unit');
		return unit ? value + unit : value;
	}

	function syncField(el) {
		var name = el.getAttribute('data-css-var');
		if (name) {
			var value = valueForField(el);
			setVar(name, value);
			if (name === '--wl-field-focus') {
				setVar('--wl-field-focus-ring', hexToRgba(value, 0.25));
			}
			if (name === '--wl-font' && !value) {
				setVar('--wl-font', '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif');
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
			syncField(this);
		});
		fields[i].addEventListener('change', function () {
			syncField(this);
		});
		syncField(fields[i]);
	}

	/* Hovering or focusing a setting shows which part of the preview it affects. */
	var highlighted = [];

	function clearHighlight() {
		for (var h = 0; h < highlighted.length; h++) {
			highlighted[h].classList.remove('wl-highlight');
		}
		highlighted = [];
	}

	function highlightTarget(source) {
		clearHighlight();

		var selector = source.getAttribute('data-preview-target');
		if (!selector || !preview) {
			return;
		}

		var targets = preview.querySelectorAll(selector);
		for (var t = 0; t < targets.length; t++) {
			targets[t].classList.add('wl-highlight');
			highlighted.push(targets[t]);
		}
	}

	var targetSources = document.querySelectorAll('[data-preview-target]');
	for (var s = 0; s < targetSources.length; s++) {
		targetSources[s].addEventListener('mouseenter', function () {
			highlightTarget(this);
		});
		targetSources[s].addEventListener('mouseleave', clearHighlight);
		targetSources[s].addEventListener('focusin', function () {
			highlightTarget(this);
		});
		targetSources[s].addEventListener('focusout', clearHighlight);
	}

	/* Section tabs, with deep links (#general, #colors, ...) and arrow keys. */
	var tabs = document.querySelectorAll('[data-wl-tab]');
	var panels = document.querySelectorAll('[data-wl-panel]');

	function activateTab(id, updateHash) {
		for (var t = 0; t < tabs.length; t++) {
			var active = tabs[t].getAttribute('data-wl-tab') === id;
			tabs[t].classList.toggle('is-active', active);
			tabs[t].setAttribute('aria-selected', active ? 'true' : 'false');
		}
		for (var p = 0; p < panels.length; p++) {
			panels[p].hidden = panels[p].getAttribute('data-wl-panel') !== id;
		}
		if (updateHash && window.history && window.history.replaceState) {
			window.history.replaceState(null, '', '#' + id);
		}
	}

	for (var n = 0; n < tabs.length; n++) {
		tabs[n].addEventListener('click', function () {
			activateTab(this.getAttribute('data-wl-tab'), true);
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
			activateTab(tabs[next].getAttribute('data-wl-tab'), true);
		});
	}

	var hash = (window.location.hash || '').replace('#', '');
	for (var m = 0; m < tabs.length; m++) {
		if (tabs[m].getAttribute('data-wl-tab') === hash) {
			activateTab(hash, false);
			break;
		}
	}

	/* One-click palettes. Defaults stay stock WordPress. */
	var palettes = (window.wlLoginAdmin && window.wlLoginAdmin.palettes) || {};
	var presetButtons = document.querySelectorAll('[data-wl-preset]');

	for (var p = 0; p < presetButtons.length; p++) {
		presetButtons[p].addEventListener('click', function () {
			var self = this;
			var palette = palettes[self.getAttribute('data-wl-preset')];
			if (!palette || !palette.colors) {
				return;
			}

			Object.keys(palette.colors).forEach(function (key) {
				var input = document.querySelector('[name="wl_login_options[' + key + ']"]');
				if (!input) {
					return;
				}
				input.value = palette.colors[key];
				input.dispatchEvent(new Event('input', { bubbles: true }));
				input.dispatchEvent(new Event('change', { bubbles: true }));
			});

			var label = self.querySelector('.wl-palette-name');
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

	function openMediaPicker(button) {
		if (typeof window.wp === 'undefined' || !window.wp.media) {
			window.alert('Media library is not available.');
			return;
		}

		var wrap = button.closest('.wl-media-field');
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
			var remove = wrap.querySelector('.wl-media-remove');

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

	function createLoginPage(button) {
		var config = window.wlLoginAdmin || {};
		var labels = config.createPage || {};
		var status = button.parentElement.querySelector('.wl-create-page-status');
		var titleInput = document.getElementById('wl-new-login-page-title');
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
		body.append('action', 'wl_login_create_page');
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

				var select = document.querySelector('select[name="wl_login_options[custom_page_id]"]');
				if (select) {
					var option = new Option(result.data.title, result.data.id, true, true);
					select.add(option);
					select.value = String(result.data.id);
				}

				var enabled = document.querySelector('input[name="wl_login_options[custom_page_enabled]"]');
				if (enabled) {
					enabled.checked = true;
				}

				var viewLink = document.querySelector('.wl-view-link');
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

	function copyToClipboard(text, button) {
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
		var create = event.target.closest('.wl-create-login-page');
		if (create) {
			event.preventDefault();
			createLoginPage(create);
			return;
		}

		var copy = event.target.closest('.wl-copy-shortcode');
		if (copy) {
			event.preventDefault();
			copyToClipboard(copy.getAttribute('data-wl-copy-shortcode') || '', copy);
			return;
		}

		var choose = event.target.closest('.wl-media-choose');
		if (choose) {
			event.preventDefault();
			openMediaPicker(choose);
			return;
		}

		var remove = event.target.closest('.wl-media-remove');
		if (remove) {
			event.preventDefault();
			var wrap = remove.closest('.wl-media-field');
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

	var deviceButtons = document.querySelectorAll('[data-wl-device]');
	for (var d = 0; d < deviceButtons.length; d++) {
		deviceButtons[d].addEventListener('click', function () {
			var device = this.getAttribute('data-wl-device');
			var container = document.querySelector('.wl-preview-device');
			if (container) {
				container.className = 'wl-preview-device wl-device-' + device;
			}
			for (var j = 0; j < deviceButtons.length; j++) {
				deviceButtons[j].classList.toggle('active', deviceButtons[j] === this);
			}
		});
	}

	var resetForm = document.querySelector('.wl-reset-form');
	if (resetForm) {
		resetForm.addEventListener('submit', function (event) {
			var message = (window.wlLoginAdmin && window.wlLoginAdmin.confirmReset) || 'Reset all settings?';
			if (!window.confirm(message)) {
				event.preventDefault();
			}
		});
	}
}());
