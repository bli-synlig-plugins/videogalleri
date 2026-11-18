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
		if (slider.getAttribute('data-vg-ready') === '1') {
			return;
		}
		slider.setAttribute('data-vg-ready', '1');

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

	function formatTime(seconds) {
		if (!isFinite(seconds) || seconds < 0) {
			seconds = 0;
		}
		seconds = Math.floor(seconds);
		var hours = Math.floor(seconds / 3600);
		var minutes = Math.floor((seconds % 3600) / 60);
		var secs = seconds % 60;
		if (hours > 0) {
			return hours + ':' + String(minutes).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
		}
		return minutes + ':' + String(secs).padStart(2, '0');
	}

	function setIcon(button, playing) {
		button.classList.toggle('is-playing', playing);
		button.setAttribute('aria-label', playing ? 'Pause' : 'Spill av');
	}

	function setVolumeIcon(button, muted, low) {
		button.classList.toggle('is-muted', muted);
		button.classList.toggle('is-low', !muted && low);
		button.setAttribute('aria-pressed', muted ? 'true' : 'false');
		button.setAttribute('aria-label', muted ? 'Lyd på' : 'Lyd av');
	}

	function initPlayer(root) {
		if (root.getAttribute('data-vg-ready') === '1') {
			return;
		}
		var media = root.querySelector('.vg-player__media');
		var bar = root.querySelector('.vg-player__bar');
		if (!media || !bar || media.tagName !== 'VIDEO') {
			return;
		}
		root.setAttribute('data-vg-ready', '1');

		var play = bar.querySelector('.vg-player__play');
		var time = bar.querySelector('.vg-player__time');
		var seek = bar.querySelector('.vg-player__seek');
		var mute = bar.querySelector('.vg-player__mute');
		var level = bar.querySelector('.vg-player__level');
		var full = bar.querySelector('.vg-player__full');
		var message = root.querySelector('.vg-player__message');
		var dragging = false;
		var pointerInside = false;
		var barWasAway = false;
		var hideTimer = null;
		var coarse = window.matchMedia('(pointer: coarse)').matches;
		var hideDelay = coarse ? 3000 : 2500;
		var lastVolume = media.volume > 0 ? media.volume : 1;

		function keyboardFocus() {
			var active = document.activeElement;
			return !!(active && bar.contains(active) && active.matches(':focus-visible'));
		}

		function canHide() {
			return !media.paused && !dragging && !bar.classList.contains('is-idle') && !keyboardFocus();
		}

		function reveal() {
			bar.classList.remove('is-away');
			window.clearTimeout(hideTimer);
			if (!canHide()) {
				return;
			}
			hideTimer = window.setTimeout(function () {
				if (canHide()) {
					bar.classList.add('is-away');
				}
			}, hideDelay);
		}

		function hideNow() {
			window.clearTimeout(hideTimer);
			if (canHide()) {
				bar.classList.add('is-away');
			} else {
				bar.classList.remove('is-away');
			}
		}

		function paintTime() {
			var duration = media.duration || 0;
			var current = media.currentTime || 0;
			if (time) {
				time.textContent = formatTime(current) + ' / ' + formatTime(duration);
			}
			if (seek && !dragging && duration > 0) {
				var progress = (current / duration) * 1000;
				seek.value = String(progress);
				seek.style.setProperty('--vg-progress', (progress / 10) + '%');
			}
		}

		function showFailure() {
			if (message) {
				message.hidden = false;
				message.textContent = 'Videoen kunne ikke lastes.';
			}
			bar.classList.add('is-idle');
			bar.classList.remove('is-away');
			bar.querySelectorAll('button, input, select').forEach(function (control) {
				control.disabled = true;
			});
		}

		function togglePlay() {
			if (media.paused) {
				if (play) {
					setIcon(play, true);
				}
				var pending = media.play();
				if (pending && typeof pending.catch === 'function') {
					pending.catch(function () {
						if (media.paused && play) {
							setIcon(play, false);
						}
					});
				}
			} else {
				media.pause();
			}
		}

		bar.addEventListener('click', function (event) {
			event.stopPropagation();
		});

		if (play) {
			play.addEventListener('click', function (event) {
				event.preventDefault();
				event.stopPropagation();
				togglePlay();
			});
		}

		media.addEventListener('click', function (event) {
			if (bar.contains(event.target)) {
				return;
			}
			if (coarse && barWasAway && !media.paused) {
				reveal();
				return;
			}
			togglePlay();
			reveal();
		});
		media.addEventListener('play', function () {
			if (play) {
				setIcon(play, true);
			}
			if (coarse || pointerInside) {
				reveal();
			} else {
				hideNow();
			}
		});
		media.addEventListener('pause', function () {
			if (play) {
				setIcon(play, false);
			}
			reveal();
		});
		media.addEventListener('ended', reveal);
		media.addEventListener('timeupdate', paintTime);
		media.addEventListener('loadedmetadata', paintTime);
		media.addEventListener('durationchange', paintTime);
		media.addEventListener('error', showFailure);

		function endDrag() {
			dragging = false;
			reveal();
		}

		function watchDrag(control) {
			control.addEventListener('pointerdown', function () {
				dragging = true;
				reveal();
				window.addEventListener('pointerup', endDrag, { once: true });
				window.addEventListener('pointercancel', endDrag, { once: true });
			});
		}

		if (seek) {
			watchDrag(seek);
			seek.addEventListener('input', function () {
				var duration = media.duration || 0;
				var ratio = Number(seek.value) / 1000;
				seek.style.setProperty('--vg-progress', (ratio * 100) + '%');
				if (duration > 0) {
					media.currentTime = ratio * duration;
				}
			});
		}

		if (play) {
			setIcon(play, !media.paused);
		}

		function paintVolume() {
			if (!mute) {
				return;
			}
			var amount = media.volume;
			if (!(amount >= 0)) {
				amount = lastVolume;
			}
			var muted = media.muted || amount === 0;
			setVolumeIcon(mute, muted, amount > 0 && amount <= 0.5);
			if (!level || document.activeElement === level) {
				return;
			}
			var shown = muted ? 0 : Math.round(amount * 100);
			level.value = String(shown);
			level.style.setProperty('--vg-volume', shown + '%');
		}

		function applyVolume(next) {
			if (next > 0) {
				lastVolume = next;
			}
			try {
				media.volume = next;
			} catch (err) {
				/* iOS lar volumet ligge hos enheten. */
			}
			media.muted = next === 0;
			paintVolume();
		}

		if (mute) {
			paintVolume();
			mute.addEventListener('click', function () {
				if (media.muted || media.volume === 0) {
					applyVolume(lastVolume > 0 ? lastVolume : 1);
					return;
				}
				lastVolume = media.volume > 0 ? media.volume : lastVolume;
				media.muted = true;
				paintVolume();
			});
		}

		if (level) {
			watchDrag(level);
			level.addEventListener('input', function () {
				var shown = Number(level.value);
				level.style.setProperty('--vg-volume', shown + '%');
				applyVolume(shown / 100);
			});
			media.addEventListener('volumechange', paintVolume);
		}

		if (full) {
			full.addEventListener('click', function () {
				var active = document.fullscreenElement || document.webkitFullscreenElement;
				if (active === root) {
					if (document.exitFullscreen) {
						document.exitFullscreen();
					} else if (document.webkitExitFullscreen) {
						document.webkitExitFullscreen();
					}
					return;
				}
				if (root.requestFullscreen) {
					root.requestFullscreen();
				} else if (root.webkitRequestFullscreen) {
					root.webkitRequestFullscreen();
				}
			});
		}

		root.addEventListener('pointerenter', function () {
			pointerInside = true;
			reveal();
		});
		root.addEventListener('pointerleave', function () {
			pointerInside = false;
			if (!coarse) {
				hideNow();
			}
		});
		root.addEventListener('pointermove', function () {
			pointerInside = true;
			reveal();
		});
		root.addEventListener('pointerdown', function () {
			barWasAway = bar.classList.contains('is-away');
			reveal();
		});
		bar.addEventListener('focusin', reveal);
		bar.addEventListener('focusout', function () {
			window.setTimeout(reveal, 0);
		});

		paintTime();
	}

	function boot() {
		document.querySelectorAll('.vg-slider').forEach(initSlider);
		document.querySelectorAll('.vg-player--file').forEach(initPlayer);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}

	if (window.MutationObserver && document.documentElement) {
		var observer = new MutationObserver(function () {
			boot();
		});
		observer.observe(document.documentElement, { childList: true, subtree: true });
	}
})();
