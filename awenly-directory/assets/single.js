/* Awenly Directory: single listing — click tracking, copy link, gallery lightbox. */
(function () {
	if (!window.AWD) { return; }
	function track(what) {
		var fd = new FormData();
		fd.append('action', 'awd_track'); fd.append('id', AWD.id); fd.append('what', what);
		if (navigator.sendBeacon) { navigator.sendBeacon(AWD.ajax, fd); }
		else { fetch(AWD.ajax, { method: 'POST', body: fd, keepalive: true }); }
	}
	document.addEventListener('click', function (e) {
		var t = e.target.closest && e.target.closest('[data-awd-track]');
		if (t) { track(t.getAttribute('data-awd-track')); }
		var c = e.target.closest && e.target.closest('[data-awd-copy]');
		if (c) {
			var url = c.getAttribute('data-awd-copy');
			(navigator.clipboard ? navigator.clipboard.writeText(url) : Promise.reject()).then(function () {
				c.classList.add('is-done'); c.setAttribute('title', AWD.copied);
				setTimeout(function () { c.classList.remove('is-done'); }, 1800);
			}).catch(function () { window.prompt('', url); });
		}
		var g = e.target.closest && e.target.closest('[data-awd-lightbox]');
		if (g) {
			e.preventDefault();
			var box = document.createElement('div');
			box.className = 'awd-lightbox';
			box.innerHTML = '<img alt="">';
			box.querySelector('img').src = g.getAttribute('href');
			box.addEventListener('click', function () { box.remove(); });
			document.addEventListener('keydown', function esc(ev) { if (ev.key === 'Escape') { box.remove(); document.removeEventListener('keydown', esc); } });
			document.body.appendChild(box);
		}
	});
})();
