/* Awenly Directory: listing photo preview + in-browser resize before upload. */
(function () {
	var MAX = 1600, LIMIT = 2 * 1024 * 1024;
	function msg(input, text, bad) {
		var m = input.closest('.awd-photo').querySelector('[data-awd-photo-msg]');
		if (m) { m.textContent = text; m.classList.toggle('awd-photo__err', !!bad); }
	}
	function preview(input, url) {
		var box = input.closest('.awd-photo').querySelector('[data-awd-preview]');
		if (box) { box.innerHTML = ''; var img = new Image(); img.src = url; img.alt = ''; box.appendChild(img); }
	}
	document.addEventListener('change', function (e) {
		var input = e.target;
		if (!input.matches || !input.matches('[data-awd-photo]') || !input.files || !input.files[0]) { return; }
		var file = input.files[0];
		var url = URL.createObjectURL(file);
		var img = new Image();
		img.onerror = function () {
			input.value = '';
			msg(input, 'This photo format is not supported. Please choose a JPG or PNG image.', true);
		};
		img.onload = function () {
			preview(input, url);
			var w = img.naturalWidth, h = img.naturalHeight;
			var needs = w > MAX || h > MAX || file.size > LIMIT || !/^image\/(jpeg|png|webp)$/.test(file.type);
			if (!needs || !window.DataTransfer || !HTMLCanvasElement.prototype.toBlob) {
				msg(input, 'Photo ready — it will be uploaded when you save.');
				return;
			}
			var s = Math.min(1, MAX / Math.max(w, h));
			var c = document.createElement('canvas');
			c.width = Math.round(w * s); c.height = Math.round(h * s);
			var ctx = c.getContext('2d');
			ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, c.width, c.height);
			ctx.drawImage(img, 0, 0, c.width, c.height);
			msg(input, 'Optimising photo…');
			c.toBlob(function (blob) {
				if (!blob) { msg(input, 'Photo ready — it will be uploaded when you save.'); return; }
				var name = (file.name || 'photo').replace(/\.[^.]+$/, '') + '.jpg';
				var dt = new DataTransfer();
				dt.items.add(new File([blob], name, { type: 'image/jpeg' }));
				input.files = dt.files;
				msg(input, 'Photo ready — it will be uploaded when you save.');
			}, 'image/jpeg', 0.86);
		};
		img.src = url;
	});
})();
