(function ($) {
	'use strict';

	function initLinkPicker() {
		if (typeof vgAdmin === 'undefined') {
			return;
		}

		var root = document.querySelector('[data-vg-link-picker]');
		if (!root) {
			return;
		}

		var cfg = vgAdmin;
		var $type = $('#vg_link_type');
		var $row = $('#vg_link_post_row');
		var input = root.querySelector('#vg_link_id');
		var selected = root.querySelector('[data-vg-link-selected]');
		var selectedTitle = root.querySelector('[data-vg-link-selected-title]');
		var selectedSub = root.querySelector('[data-vg-link-selected-sub]');
		var searchWrap = root.querySelector('[data-vg-link-search-wrap]');
		var search = root.querySelector('[data-vg-link-search]');
		var hint = root.querySelector('[data-vg-link-hint]');
		var results = root.querySelector('[data-vg-link-results]');
		var removeBtn = root.querySelector('[data-vg-link-remove]');
		var searchTimer = null;

		function clearSelection() {
			input.value = '';
			selected.hidden = true;
			selectedTitle.textContent = '';
			selectedSub.textContent = '';
			searchWrap.hidden = false;
			search.value = '';
			results.hidden = true;
			results.innerHTML = '';
			if (hint) {
				hint.hidden = false;
			}
		}

		function selectItem(item) {
			input.value = String(item.id);
			selectedTitle.textContent = item.title;
			selectedSub.textContent = item.subtitle || '';
			selected.hidden = false;
			searchWrap.hidden = true;
			search.value = '';
			results.hidden = true;
			results.innerHTML = '';
		}

		function renderResults(items) {
			results.innerHTML = '';

			if (!items.length) {
				results.innerHTML = '<div class="vg-link-picker__empty">' + cfg.i18n.noResults + '</div>';
				results.hidden = false;
				return;
			}

			items.forEach(function (item) {
				var btn = document.createElement('button');
				btn.type = 'button';
				btn.className = 'vg-link-picker__result';

				var title = document.createElement('span');
				title.className = 'vg-link-picker__result-title';
				title.textContent = item.title;
				btn.appendChild(title);

				if (item.subtitle) {
					var sub = document.createElement('span');
					sub.className = 'vg-link-picker__result-sub';
					sub.textContent = item.subtitle;
					btn.appendChild(sub);
				}

				btn.addEventListener('click', function () {
					selectItem(item);
				});

				results.appendChild(btn);
			});

			results.hidden = false;
		}

		function runSearch() {
			var type = $type.val();
			var q = search.value.trim();

			if (!type) {
				return;
			}

			if (q.length < 2) {
				results.hidden = true;
				results.innerHTML = '';
				if (hint) {
					hint.hidden = false;
				}
				return;
			}

			if (hint) {
				hint.hidden = true;
			}

			$.getJSON(cfg.ajaxUrl, {
				action: 'vg_search_posts',
				nonce: cfg.nonce,
				post_type: type,
				q: q
			}).done(function (response) {
				var items = (response && response.success && response.data && response.data.items) || [];
				renderResults(items);
			}).fail(function () {
				results.innerHTML = '<div class="vg-link-picker__empty">' + cfg.i18n.loadError + '</div>';
				results.hidden = false;
			});
		}

		$type.on('change', function () {
			var type = $(this).val();

			if (!type) {
				$row.hide();
				clearSelection();
				return;
			}

			$row.show();
			clearSelection();
			search.focus();
		});

		search.addEventListener('input', function () {
			clearTimeout(searchTimer);
			searchTimer = setTimeout(runSearch, 250);
		});

		removeBtn.addEventListener('click', function (e) {
			e.preventDefault();
			clearSelection();
			search.focus();
		});

		if (!$type.val()) {
			$row.hide();
		}
	}

	var $list = $('#vg-order-list');
	var $save = $('#vg-order-save');
	var $status = $('#vg-order-status');

	if ($list.length && typeof vgOrder !== 'undefined') {
		var initialOrder = $list.children().map(function () {
			return $(this).data('id');
		}).get().join(',');

		$list.sortable({
			handle: '.vg-order-handle',
			axis: 'y',
			update: function () {
				updateNumbers();
				checkDirty();
			}
		});

		function updateNumbers() {
			$list.children('.vg-order-item').each(function (index) {
				$(this).find('.vg-order-num').text(index + 1);
			});
		}

		function checkDirty() {
			var current = $list.children().map(function () {
				return $(this).data('id');
			}).get().join(',');

			var dirty = current !== initialOrder;
			$save.prop('disabled', !dirty);

			if (!dirty) {
				$status.text('');
			}
		}

		$save.on('click', function () {
			var order = $list.children().map(function () {
				return $(this).data('id');
			}).get();

			$save.prop('disabled', true);
			$status.text(vgOrder.i18n.saving);

			$.post(vgOrder.ajaxUrl, {
				action: 'vg_save_order',
				nonce: vgOrder.nonce,
				order: order
			}).done(function (response) {
				if (response.success) {
					initialOrder = order.join(',');
					$status.text(vgOrder.i18n.saved);
				} else {
					$status.text(vgOrder.i18n.error);
					$save.prop('disabled', false);
				}
			}).fail(function () {
				$status.text(vgOrder.i18n.error);
				$save.prop('disabled', false);
			});
		});
	}

	$(function () {
		initLinkPicker();
	});
})(jQuery);
