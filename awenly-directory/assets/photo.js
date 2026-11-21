/* Awenly Directory: listing form helpers — photo resize/preview, gallery, type-specific fields, services rows. */
(function () {
	var MAX = 1600, LIMIT = 2 * 1024 * 1024;

	function loadImage(file) {
		return new Promise(function (resolve) {
			var url = URL.createObjectURL(file), img = new Image();
			img.onload = function () { resolve({ img: img, url: url }); };
			img.onerror = function () { resolve(null); };
			img.src = url;
		});
	}
	function shrink(file, loaded) {
		var w = loaded.img.naturalWidth, h = loaded.img.naturalHeight;
		var needs = w > MAX || h > MAX || file.size > LIMIT || !/^image\/(jpeg|png|webp)$/.test(file.type);
		if (!needs || !HTMLCanvasElement.prototype.toBlob) { return Promise.resolve(file); }
		var s = Math.min(1, MAX / Math.max(w, h)), c = document.createElement('canvas');
		c.width = Math.round(w * s); c.height = Math.round(h * s);
		var ctx = c.getContext('2d');
		ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, c.width, c.height);
		ctx.drawImage(loaded.img, 0, 0, c.width, c.height);
		return new Promise(function (resolve) {
			c.toBlob(function (blob) {
				resolve(blob ? new File([blob], (file.name || 'photo').replace(/\.[^.]+$/, '') + '.jpg', { type: 'image/jpeg' }) : file);
			}, 'image/jpeg', 0.86);
		});
	}
	function setMsg(el, text, bad) {
		if (el) { el.textContent = text; el.classList.toggle('awd-photo__err', !!bad); }
	}

	document.addEventListener('change', function (e) {
		var input = e.target;
		if (!input.matches) { return; }

		if (input.matches('[data-awd-photo]') && input.files && input.files[0]) {
			var box = input.closest('.awd-photo'), msg = box.querySelector('[data-awd-photo-msg]'), file = input.files[0];
			loadImage(file).then(function (loaded) {
				if (!loaded) { input.value = ''; setMsg(msg, 'This photo format is not supported. Please choose a JPG or PNG image.', true); return; }
				var prev = box.querySelector('[data-awd-preview]');
				if (prev) { prev.innerHTML = ''; var im = new Image(); im.src = loaded.url; prev.appendChild(im); }
				setMsg(msg, 'Optimising photo…');
				shrink(file, loaded).then(function (out) {
					if (out !== file && window.DataTransfer) { var dt = new DataTransfer(); dt.items.add(out); input.files = dt.files; }
					setMsg(msg, 'Photo ready — it will be uploaded when you save.');
				});
			});
		}

		if (input.matches('[data-awd-gallery]') && input.files && input.files.length) {
			var gmsg = input.closest('.awd-gallery-edit').querySelector('[data-awd-gallery-msg]');
			var files = Array.prototype.slice.call(input.files, 0, 8);
			setMsg(gmsg, 'Optimising ' + files.length + ' photo(s)…');
			Promise.all(files.map(function (f) {
				return loadImage(f).then(function (l) { return l ? shrink(f, l) : null; });
			})).then(function (outs) {
				outs = outs.filter(Boolean);
				if (window.DataTransfer) { var dt = new DataTransfer(); outs.forEach(function (o) { dt.items.add(o); }); input.files = dt.files; }
				setMsg(gmsg, outs.length + ' photo(s) ready — they will be uploaded when you save.');
			});
		}

		if (input.matches('[data-awd-type]')) { toggleTypes(input.closest('form')); }
	});

	function toggleTypes(form) {
		if (!form) { return; }
		var sel = form.querySelector('[data-awd-type]');
		if (!sel) { return; }
		form.querySelectorAll('[data-types]').forEach(function (el) {
			el.hidden = el.getAttribute('data-types').split(' ').indexOf(sel.value) === -1;
		});
	}

	document.addEventListener('click', function (e) {
		var add = e.target.closest && e.target.closest('[data-awd-service-add]');
		if (add) {
			var wrap = add.parentNode.querySelector('[data-awd-services]'), rows = wrap.querySelectorAll('[data-awd-service]');
			if (rows.length >= 10) { return; }
			var clone = rows[rows.length - 1].cloneNode(true), idx = Date.now() % 100000;
			clone.querySelectorAll('input').forEach(function (i) { i.value = ''; i.name = i.name.replace(/\[\d+\]/, '[' + idx + ']'); });
			wrap.appendChild(clone);
			clone.querySelector('input').focus();
		}
		var del = e.target.closest && e.target.closest('[data-awd-service-del]');
		if (del) {
			var row = del.closest('[data-awd-service]'), all = row.parentNode.querySelectorAll('[data-awd-service]');
			if (all.length > 1) { row.remove(); } else { row.querySelectorAll('input').forEach(function (i) { i.value = ''; }); }
		}
	});

	document.addEventListener('DOMContentLoaded', function () { document.querySelectorAll('[data-awd-form]').forEach(toggleTypes); });
})();
