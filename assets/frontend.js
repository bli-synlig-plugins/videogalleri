(function () {
	'use strict';

	function navIsVisible(slider) {
		var prev = slider.querySelector('.vg-slider__nav--prev');
		if (!prev) {
			return false;
		}

		return window.getComputedStyle(prev).display !== 'none';
	}

	function updateNav(slider) {
		if (!navIsVisible(slider)) {
			return;
		}

		var track = slider.querySelector('.vg-slider__track');
		var prev = slider.querySelector('.vg-slider__nav--prev');
		var next = slider.querySelector('.vg-slider__nav--next');

		if (!track || !prev || !next) {
			return;
		}

		var maxScroll = track.scrollWidth - track.clientWidth;
		prev.disabled = track.scrollLeft <= 4;
		next.disabled = track.scrollLeft >= maxScroll - 4;
	}

	function scrollByCard(slider, direction) {
		if (!navIsVisible(slider)) {
			return;
		}

		var track = slider.querySelector('.vg-slider__track');
		var card = slider.querySelector('.vg-slider__card');
		if (!track || !card) {
			return;
		}

		var gap = parseFloat(getComputedStyle(track).gap) || 16;
		var amount = (card.offsetWidth + gap) * direction;
		track.scrollBy({ left: amount, behavior: 'smooth' });
	}

	function initSlider(slider) {
		var track = slider.querySelector('.vg-slider__track');
		var prev = slider.querySelector('.vg-slider__nav--prev');
		var next = slider.querySelector('.vg-slider__nav--next');

		if (!track) {
			return;
		}

		track.addEventListener('scroll', function () {
			updateNav(slider);
		}, { passive: true });

		if (prev) {
			prev.addEventListener('click', function () {
				scrollByCard(slider, -1);
			});
		}

		if (next) {
			next.addEventListener('click', function () {
				scrollByCard(slider, 1);
			});
		}

		updateNav(slider);
		window.addEventListener('resize', function () {
			updateNav(slider);
		});
	}

	function boot() {
		document.querySelectorAll('.vg-slider').forEach(initSlider);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
