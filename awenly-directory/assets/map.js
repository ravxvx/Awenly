/* Awenly Practitioner Directory — OpenStreetMap (Leaflet) maps. */
(function () {
	function init() {
		if (!window.L) { return; }
		document.querySelectorAll('.awd-map[data-points]').forEach(function (el) {
			if (el._awdMap) { return; }
			var pts;
			try { pts = JSON.parse(el.getAttribute('data-points')) || []; } catch (e) { pts = []; }
			if (!pts.length) { return; }
			var map = L.map(el, { scrollWheelZoom: false, attributionControl: true });
			el._awdMap = map;
			L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
				maxZoom: 18,
				attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
			}).addTo(map);
			var icon = L.divIcon({ className: 'awd-pin', html: '<span></span>', iconSize: [30, 38], iconAnchor: [15, 36], popupAnchor: [0, -32] });
			var bounds = [];
			pts.forEach(function (p) {
				var m = L.marker([p.lat, p.lng], { icon: icon, title: p.title }).addTo(map);
				var a = document.createElement('a');
				a.href = p.url; a.textContent = p.title;
				var box = document.createElement('div');
				box.className = 'awd-pop';
				box.appendChild(a);
				if (p.place) { var s = document.createElement('small'); s.textContent = p.place; box.appendChild(s); }
				m.bindPopup(box);
				bounds.push([p.lat, p.lng]);
			});
			if (bounds.length === 1) { map.setView(bounds[0], el.classList.contains('awd-map--single') ? 12 : 9); }
			else { map.fitBounds(bounds, { padding: [40, 40], maxZoom: 11 }); }
			el.addEventListener('click', function () { map.scrollWheelZoom.enable(); }, { once: true });
		});
	}
	if (document.readyState !== 'loading') { init(); } else { document.addEventListener('DOMContentLoaded', init); }
	window.addEventListener('load', init);
})();
