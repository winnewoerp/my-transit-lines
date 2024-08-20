let current_location = new URL(window.location);

['load', 'reload'].forEach((elem) => {
	window.addEventListener(elem, addSearchBarOpenListener);
});

function addSearchBarOpenListener() {
	document.getElementById('mtl-filter-details').addEventListener('toggle', (e) => {
		const dest = document.getElementById(e.target.open ? 'mtl-search-submit-open' : 'mtl-search-submit-closed');
		dest.append(...document.querySelectorAll('.mtl-search-submit'));

		document.getElementById('mtl-search-bar-open').value = e.target.open ? 'open' : '';

		const to_replace = e.target.open ? 'closed' : 'open';
		const replace_with = e.target.open ? 'open' : 'closed';

		document.querySelectorAll('[data-mtl-toggle-class]').forEach((elem) => {
			elem.classList.replace(to_replace, replace_with);
		});
	});
}

['load', 'reload'].forEach((elem) => {
	window.addEventListener(elem, () => {
		document.getElementById('mtl-filter-multiple').addEventListener('change', (e) => {
			document.querySelectorAll('select.allowsMultiple').forEach((select) => {
				select.multiple = e.target.checked || select.selectedOptions.length > 1;
			})
		});
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

	window.addEventListener('querychange', () => {
		load_new_data();
		current_location = new URL(window.location);
	});
	window.addEventListener('popstate', (e) => {
		if (e.state && e.state.previous_location)
			return;

		e.stopImmediatePropagation();
		window.dispatchEvent(new PopStateEvent('popstate', {state: {previous_location: current_location}}));

		const query = new URLSearchParams(window.location.search);
		const old_query = new URLSearchParams(current_location.search);
		const form = document.getElementById('mtl-filter-form');

		current_location = new URL(window.location);

		let same_query = true;
		for (const key of new FormData(form).keys()) {
			if (query.get(key) !== old_query.get(key)) {
				same_query = false;
				break;
			}
		}

		if (!same_query)
			load_new_data();
	});

	document.getElementById('mtl-filter-form').addEventListener('submit', (e) => {
		e.preventDefault();
		submit_new_filter();
	});

	set_button_behaviour();
});

function load_new_data(link = window.location.toString()) {
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

	const params = Array.from(paramsObject.entries()).filter(([key, value]) => {
		return value !== "";
	}).map(([key, value]) => {
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
