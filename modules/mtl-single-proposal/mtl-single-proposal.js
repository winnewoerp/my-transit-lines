/**
 * (C) Jan Garloff
 */

const selectInteraction = new ol.interaction.Select({ layers: [vectorLayer], multi: true, style: selectedStyleFunction });

const selectedFeatures = selectInteraction.getFeatures();

map.addInteraction(selectInteraction);

selectedFeatures.on('add', handleFeatureSelected);
selectedFeatures.on('remove', handleFeatureUnselected);

// Open textinput for feature name and show size of feature
function handleFeatureSelected(event) {
	event.element.set('size', getFeatureSize(event.element));
}

// Set name of unselected feature to name from the textinput and remove size being shown
function handleFeatureUnselected(event) {
	if (!showSizes)
		event.element.unset('size');
}

const loaded_revisions = {};
window.addEventListener('load', add_revision_control);

function add_revision_control() {
	set_loaded_revisions();

	document.getElementById('mtl-revision-input').addEventListener('change', function(e) {
		const revision = e.target.value === e.target.max ? "current" : e.target.value;

		if (!Object.keys(loaded_revisions).includes(revision)) {
			fetch(wp.ajax.settings.url, {
				method: 'post',
				body: new URLSearchParams({
					action: 'mtl_proposal_data',
					post_id: Object.values(loaded_revisions)[0].id,
					revision: revision,
				}),
			}).then(response => {
				response.json().then(json => {
					loaded_revisions[revision] = json;

					if (document.getElementById('mtl-revision-input').value !== revision)
						return;

					load_revision(revision);
				});
			});
		} else {
			load_revision(revision);
		}
	});
}

function set_loaded_revisions() {
	Object.keys(loaded_revisions).forEach(revision => delete loaded_revisions[revision]);

	loaded_revisions[proposalList[0].revision] = proposalList[0];
}

/**
 * @param {string} revision_key which revision to load. This needs to be already fetched from the server
 */
function load_revision(revision_key) {
	let new_url = new URL(window.location);
	let new_search = new URLSearchParams(new_url.search);
	new_search.set('r', revision_key);
	new_url.search = new_search;

	history.replaceState(null, "", new_url);

	proposalList[0] = loaded_revisions[revision_key];
	loadNewFeatures();
}

add_event_listeners();
function add_event_listeners() {
	document.querySelectorAll('.nav-links a[data-mtl-no-reload]').forEach(link => link.addEventListener('click', e => {
		load_proposal(e.target.href);

		history.pushState(null, "", link);

		e.preventDefault();
	}));
}

function load_proposal(link) {
	fetch(link).then(response => response.text().then(text => {
		const fetched_document = (new DOMParser()).parseFromString(text, 'text/html');

		document.querySelectorAll('[data-mtl-replace-with]').forEach(elem => {
			elem.replaceWith(fetched_document.querySelector(elem.dataset.mtlReplaceWith));
		});

		add_event_listeners();
		loadDataScripts();
		loadNewFeatures();
		add_revision_control();
		document.title = proposalList[0].title + ' | LiniePlus';
	}));
}

window.addEventListener('popstate', function() {
	load_proposal(window.location);
});
