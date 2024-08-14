['load', 'new-filter'].forEach((type) => {
	window.addEventListener(type, () => {
		document.getElementById('mtl-filter-multiple').addEventListener('change', (e) => {
			document.querySelectorAll('select.allowsMultiple').forEach((select) => {
				const multipleSelected = Array.from(select.querySelectorAll(':scope>option')).filter((option) => {
					return option.selected;
				}).length > 1;

				select.multiple = e.target.checked || multipleSelected;
			})
		});
	});
});