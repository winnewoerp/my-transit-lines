document.getElementById('mtl-filter-details').addEventListener('toggle', (e) => {
	const dest = document.getElementById(e.target.open ? 'mtl-search-submit-open' : 'mtl-search-submit-closed');

	dest.append(...document.querySelectorAll('.mtl-search-submit'));
});

window.addEventListener('load', () => {
	document.getElementById('mtl-filter-multiple').addEventListener('change', (e) => {
		document.querySelectorAll('select.allowsMultiple').forEach((select) => {
			const multipleSelected = Array.from(select.querySelectorAll(':scope>option')).filter((option) => {
				return option.selected;
			}).length > 1;

			select.multiple = e.target.checked || multipleSelected;
		})
	});
});

document.addEventListener('DOMContentLoaded', () => {
	loadDataScripts();

	// Redirect old links
	if (window.location.hash.includes('#!')) {
		const new_query = window.location.hash.replace('#!','');
		window.location.hash = "";
		window.location.search = new_query;
	}

	['querychange','popstate'].forEach((elem) => {
		window.addEventListener(elem, () => {
			load_new_data(window.location.toString());
		});
	});

	document.getElementById('mtl-filter-form').addEventListener('submit', (e) => {
		e.preventDefault();
		submit_new_filter();
	});

	set_button_behaviour();
});

function load_new_data(link) {
	document.querySelectorAll('.mtl-list-loader').forEach((elem) => {
		elem.style.display = '';
	});

	const request = new XMLHttpRequest();
	request.open("GET", link.toString());
	request.responseType = "document";

	request.addEventListener('load', () => {
		const to_replace = document.querySelectorAll('[data-mtl-replace-with]');

		to_replace.forEach((elem) => {
			elem.replaceWith(request.responseXML.querySelector(elem.dataset.mtlReplaceWith));
		});

		loadDataScripts();

		set_button_behaviour();

		window.dispatchEvent(new Event('reload'));

		document.querySelectorAll('.mtl-list-loader').forEach((elem) => {
			elem.style.display = 'none';
		});
	});

	request.send();
}

function set_button_behaviour() {
	document.querySelectorAll('.mtl-paginate-links a').forEach((elem) => {
		elem.removeEventListener('click', handlePaginateClick);
		elem.addEventListener('click', handlePaginateClick);
	});

	document.querySelectorAll('[data-mtl-search-link]').forEach((elem) => {
		const link_url = new URL(elem.href);
		link_url.search = window.location.search;

		elem.href = link_url.href;
	});
}

function handlePaginateClick(e) {
	e.preventDefault();
	history.pushState({},"",e.target.href);
	window.dispatchEvent(new Event('querychange'));
}

function submit_new_filter() {
	const form = document.getElementById('mtl-filter-form');

	const actionUrl = new URL(form.action);
	const actionSearch = actionUrl.search;

	const paramsObject = new URLSearchParams(new FormData(form));

	for (let key of paramsObject.keys()) {
		paramsObject.set(key, paramsObject.getAll(key).join(','));
	}

	const params = Array.from(paramsObject.entries()).map(([key, value]) => {
		return key + "=" + value;
	}).join("&");

	actionUrl.search = actionSearch ? actionSearch + '&' + params : '?' + params;

	if (actionUrl.toString() != window.location) {
		history.pushState({},"",actionUrl.toString());
		window.dispatchEvent(new Event('querychange'));
	}
}

function loadDataScripts() {
	document.querySelectorAll('[data-mtl-data-script]').forEach((elem) => {
		if (!(elem instanceof HTMLScriptElement) || elem.type != "application/json")
			return;

		const data = JSON.parse(elem.innerText);

		for (let key in data) {
			globalThis[key] = data[key];
		}
	});
}
