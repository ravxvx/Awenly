/* Awenly Practitioner Directory — OpenStreetMap (Leaflet) maps. */
(function () {
	var CACHE = 'awd_geo_v1';
	function cache() { try { return JSON.parse(localStorage.getItem(CACHE)) || {}; } catch (e) { return {}; } }
	function remember(q, c) { try { var all = cache(); all[q.toLowerCase()] = c; localStorage.setItem(CACHE, JSON.stringify(all)); } catch (e) {} }
	function geocode(q) {
		var hit = cache()[q.toLowerCase()];
		if (hit) { return Promise.resolve(hit); }
		return fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + encodeURIComponent(q), { headers: { 'Accept-Language': 'en' } })
			.then(function (r) { return r.ok ? r.json() : []; })
			.then(function (d) { if (d && d[0]) { var c = [parseFloat(d[0].lat), parseFloat(d[0].lon)]; remember(q, c); return c; } return null; })
			.catch(function () { return null; });
	}
	function init() {
		if (!window.L) { return; }
		document.querySelectorAll('.awd-map[data-points]').forEach(function (el) {
			if (el._awdMap) { return; }
			var pts;
			try { pts = JSON.parse(el.getAttribute('data-points')) || []; } catch (e) { pts = []; }
			if (!pts.length) { return; }
			var single = el.classList.contains('awd-map--single');
			var map = L.map(el, { scrollWheelZoom: false }).setView([53.4, -7.9], 6);
			el._awdMap = map;
			L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
				maxZoom: 18,
				attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
			}).addTo(map);
			var icon = L.divIcon({ className: 'awd-pin', html: '<span></span>', iconSize: [30, 38], iconAnchor: [15, 36], popupAnchor: [0, -32] });
			var bounds = [];
			function add(p, ll) {
				var box = document.createElement('div');
				box.className = 'awd-pop';
				var a = document.createElement('a'); a.href = p.url; a.textContent = p.title; box.appendChild(a);
				if (p.place) { var s = document.createElement('small'); s.textContent = p.place; box.appendChild(s); }
				L.marker(ll, { icon: icon, title: p.title }).addTo(map).bindPopup(box);
				bounds.push(ll);
				if (bounds.length === 1) { map.setView(ll, single ? 12 : 9); }
				else { map.fitBounds(bounds, { padding: [40, 40], maxZoom: 11 }); }
			}
			var queue = [];
			pts.forEach(function (p) { if (typeof p.lat === 'number') { add(p, [p.lat, p.lng]); } else if (p.q) { queue.push(p); } });
			(function next() {
				var p = queue.shift();
				if (!p) { return; }
				geocode(p.q).then(function (c) { if (c) { add(p, c); } setTimeout(next, 1100); });
			})();
			el.addEventListener('click', function () { map.scrollWheelZoom.enable(); }, { once: true });
			setTimeout(function () { map.invalidateSize(); }, 300);
		});
	}
	if (document.readyState !== 'loading') { init(); } else { document.addEventListener('DOMContentLoaded', init); }
	window.addEventListener('load', init);
})();
