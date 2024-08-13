['load', 'new-filter'].forEach((type) => {
	window.addEventListener(type, () => {
		document.getElementById('mtl-filter-multiple').addEventListener('change', (e) => {
			document.querySelectorAll('select.allowsMultiple').forEach((select) => {
				select.multiple = e.target.checked;
			})
		});
	});
});