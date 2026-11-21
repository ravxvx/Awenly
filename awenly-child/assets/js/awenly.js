/* Awenly Circle — small front-end enhancements (no dependencies). */
(function () {
	var root = document.documentElement;
	root.classList.add('aw-js');

	var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	// Reveal on scroll.
	var items = document.querySelectorAll('.aw-reveal');
	if (!('IntersectionObserver' in window) || reduce) {
		items.forEach(function (el) { el.classList.add('is-in'); });
	} else {
		var io = new IntersectionObserver(function (entries) {
			entries.forEach(function (e) {
				if (e.isIntersecting) { e.target.classList.add('is-in'); io.unobserve(e.target); }
			});
		}, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
		items.forEach(function (el) { io.observe(el); });
	}

	if (reduce) { return; }

	// Gentle parallax on the hero landscape layers.
	var hero = document.querySelector('.awenly-hero');
	var layers = hero ? hero.querySelectorAll('.aw-layer, .aw-flowers, .aw-sun, .aw-sun-glow, .aw-stars') : [];
	if (hero && layers.length) {
		var depth = function (el) {
			if (el.classList.contains('aw-stars')) { return 0.2; }
			if (el.classList.contains('aw-sun') || el.classList.contains('aw-sun-glow')) { return 0.35; }
			if (el.classList.contains('aw-l1')) { return 0.5; }
			if (el.classList.contains('aw-l2')) { return 0.8; }
			if (el.classList.contains('aw-l3')) { return 1.2; }
			return 1.7;
		};
		var tx = 0, ty = 0, sy = 0, ticking = false;
		var apply = function () {
			layers.forEach(function (el) {
				var d = depth(el);
				el.style.transform = 'translate(' + (tx * d).toFixed(1) + 'px,' + (ty * d + sy * d * 0.12).toFixed(1) + 'px)';
			});
			ticking = false;
		};
		var req = function () { if (!ticking) { ticking = true; requestAnimationFrame(apply); } };
		hero.addEventListener('mousemove', function (e) {
			var r = hero.getBoundingClientRect();
			tx = ((e.clientX - r.left) / r.width - 0.5) * -14;
			ty = ((e.clientY - r.top) / r.height - 0.5) * -6;
			req();
		});
		window.addEventListener('scroll', function () {
			sy = Math.min(window.scrollY, 700);
			req();
		}, { passive: true });
	}
})();

/* Logo tagline under "Awenly Circle" in the BuddyBoss header */
(function () {
	var add = function () {
		document.querySelectorAll('.site-header .site-title a').forEach(function (a) {
			if (a.querySelector('.aw-logo-tag')) { return; }
			var s = document.createElement('small');
			s.className = 'aw-logo-tag';
			s.textContent = 'Community · Learning · Healing';
			a.appendChild(s);
		});
	};
	if (document.readyState !== 'loading') { add(); } else { document.addEventListener('DOMContentLoaded', add); }
})();
