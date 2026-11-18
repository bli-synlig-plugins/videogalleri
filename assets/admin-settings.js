(function ($) {
	'use strict';

	var boot = window.vgSettings;
	if (!boot || !boot.settings) {
		return;
	}

	var root = document.querySelector('.vg-admin');
	if (!root) {
		return;
	}

	function clone(value) {
		return JSON.parse(JSON.stringify(value));
	}

	var saved = clone(boot.settings);
	var current = clone(boot.settings);

	function $(id) {
		return document.getElementById(id);
	}

	function isDirty() {
		return JSON.stringify(current) !== JSON.stringify(saved);
	}

	function readFields() {
		var next = clone(saved);
		root.querySelectorAll('[data-field]').forEach(function (el) {
			var key = el.getAttribute('data-field');
			if (!key) {
				return;
			}
			next[key] = el.value;
		});
		return next;
	}

	function writeFields(state) {
		root.querySelectorAll('[data-field]').forEach(function (el) {
			var key = el.getAttribute('data-field');
			if (!key || !Object.prototype.hasOwnProperty.call(state, key)) {
				return;
			}
			el.value = state[key] == null ? '' : state[key];
			if (el.classList.contains('vg-color-field') && window.jQuery) {
				var $field = window.jQuery(el);
				if ($field.data('wpWpColorPicker')) {
					$field.wpColorPicker('color', el.value);
				}
			}
		});
	}

	function syncBar() {
		current = readFields();
		var bar = $('vg-save');
		if (bar) {
			bar.classList.toggle('is-show', isDirty());
		}
	}

	function showToast(message) {
		var toast = $('vg-toast');
		if (!toast) {
			return;
		}
		if (message) {
			toast.textContent = message;
		}
		toast.classList.add('is-show');
		window.setTimeout(function () {
			toast.classList.remove('is-show');
		}, 2200);
	}

	root.querySelectorAll('.vg-nav button').forEach(function (button) {
		button.addEventListener('click', function () {
			var target = button.getAttribute('data-tab');
			root.querySelectorAll('.vg-nav button').forEach(function (item) {
				item.setAttribute('aria-selected', item === button ? 'true' : 'false');
			});
			root.querySelectorAll('.vg-tab').forEach(function (tab) {
				var on = tab.id === 'vg-tab-' + target;
				tab.classList.toggle('is-on', on);
				tab.hidden = !on;
			});
		});
	});

	root.addEventListener('input', function (event) {
		var el = event.target;
		if (el.getAttribute && el.getAttribute('data-field')) {
			syncBar();
		}
	});

	root.addEventListener('change', function (event) {
		var el = event.target;
		if (el.getAttribute && el.getAttribute('data-field')) {
			syncBar();
		}
	});

	if (window.jQuery && window.jQuery.fn.wpColorPicker) {
		window.jQuery(root).find('.vg-color-field').wpColorPicker({
			change: function () {
				window.setTimeout(syncBar, 0);
			},
			clear: function () {
				window.setTimeout(syncBar, 0);
			}
		});
	}

	var reset = $('vg-rst');
	if (reset) {
		reset.addEventListener('click', function () {
			current = clone(saved);
			writeFields(current);
			syncBar();
		});
	}

	var save = $('vg-sv');
	if (save) {
		save.addEventListener('click', function () {
			var label = boot.i18n.save || 'Lagre endringer';
			save.disabled = true;
			save.classList.add('is-busy');
			save.setAttribute('aria-busy', 'true');
			save.textContent = boot.i18n.saving || 'Lagrer…';
			current = readFields();
			var body = new window.FormData();
			body.append('action', 'vg_save_settings');
			body.append('nonce', boot.nonce);
			body.append('settings', JSON.stringify(current));

			window
				.fetch(boot.ajaxUrl, {
					method: 'POST',
					credentials: 'same-origin',
					body: body
				})
				.then(function (response) {
					return response.json();
				})
				.then(function (payload) {
					if (!payload || !payload.success) {
						throw new Error('save failed');
					}
					saved = clone(payload.data.settings || current);
					current = clone(saved);
					writeFields(current);
					syncBar();
					showToast((payload.data && payload.data.message) || boot.i18n.saved);
				})
				.catch(function () {
					window.alert(boot.i18n.error);
				})
				.then(function () {
					save.disabled = false;
					save.classList.remove('is-busy');
					save.removeAttribute('aria-busy');
					save.textContent = label;
				});
		});
	}

	writeFields(saved);
	syncBar();
})(jQuery);
