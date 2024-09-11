window.addEventListener('DOMContentLoaded', () => {
	document.querySelectorAll('button.mtl-tab-selector-option').forEach((elem) => {
		elem.addEventListener('click', tabSwitchListener);
	});
});

/**
 * The event listener for clicks on tab switch buttons
 * @param {Event} e 
 */
function tabSwitchListener(e) {
	e.preventDefault();

	if (!e.currentTarget.classList.contains('unselected'))
		return;

	show_tab(e.currentTarget.id.replace('mtl-tab-selector-',''));
}

let selectedTab = "";
/**
 * Display the tab with the given id
 * @param {string} id 
 */
function show_tab(id, update_url = true) {
	document.querySelectorAll('.mtl-tab-selector-option, .mtl-tab').forEach((elem) => {
		elem.classList.add('unselected');
	});

	document.getElementById('mtl-tab-' + id).classList.remove('unselected');
	document.getElementById('mtl-tab-selector-' + id).classList.remove('unselected');

	const form = document.getElementById('mtl-filter-form');
	if (form) {
		const actionUrl = new URL(form.action);
		actionUrl.searchParams.set('mtl-tab', id);
		form.action = actionUrl.toString();
	}

	selectedTab = id;

	if (update_url) {
		const query = new URLSearchParams(window.location.search);
		query.set('mtl-tab', id);

		const new_url = new URL(window.location.toString());
		new_url.search = query.toString();

		history.pushState({}, "", new_url.toString());
		current_location = new URL(window.location);
	}
}

function show_all_tabs() {
	selectedTab = document.querySelector('.mtl-tab:not(.unselected)').id.replace('mtl-tab-','');

	document.querySelectorAll('.mtl-tab-selector-option, .mtl-tab').forEach((elem) => {
		elem.classList.remove('unselected');
	});
}

window.addEventListener('popstate', (e) => {
	if (!e.state || !e.state.previous_location)
		return;

	const query = new URLSearchParams(window.location.search);
	const tab = query.get('mtl-tab') || 'tiles';

	const old_query = new URLSearchParams(e.state.previous_location.search);
	const old_tab = old_query.get('mtl-tab') || 'tiles';

	if (old_tab !== tab)
		show_tab(tab, false);
});

window.addEventListener('load', () => {
	show_all_tabs();

	map.updateSize();
	zoomToFeatures(true);

	createThumbMaps();
	show_tab(selectedTab, false);
});

const container = document.getElementById('popup');
const contentLink = document.getElementById('popup-content-link');
const contentTitle = document.getElementById('popup-content-title');
const contentAuthor = document.getElementById('popup-content-author');
const contentDate = document.getElementById('popup-content-date');
const closer = document.getElementById('popup-closer');

const overlay = new ol.Overlay({
	element: container,
	autoPan: {
		animation: {
			duration: 250,
		},
	},
});
map.addOverlay(overlay);

const selectInteraction = new ol.interaction.Select({ layers: [vectorLayer], style: styleFunction });
map.addInteraction(selectInteraction);

selectInteraction.on('select', function (evt) {
	if (evt.selected.length < 1) {
		closePopup();
		return;
	}

	const coordinate = evt.mapBrowserEvent.coordinate;

	const proposal = proposalList.find((elem) => {
		return elem.id === evt.selected[0].get('proposal_data_index');
	});

	container.style.display = '';
	contentLink.href = proposal.link;
	contentTitle.textContent = proposal.title;
	contentAuthor.textContent = proposal.author;
	contentDate.textContent = proposal.date;

 	overlay.setPosition(coordinate);
});

/**
 * Add a click handler to hide the popup.
 * @return {boolean} Don't follow the href.
 */
closer.onclick = function () {
	closePopup();
	return false;
};

function closePopup() {
	container.style.display = 'none';
	overlay.setPosition(undefined);
	closer.blur();
	selectInteraction.getFeatures().clear();
}

const ICON_SIZE_TILELIST = 13;
const STROKE_WIDTH_TILELIST = 3;
const MAX_ZOOM_TILELIST = 15;

// returns the style for the given feature
function tilelistStyleFunction(feature) {
	const color = transportModeStyleData[getCategoryOf(feature)]['color'];

	const fillStyle = new ol.style.Fill({
		color: color + '40',
	});

	const imageStyle = new ol.style.Icon({
		src: transportModeStyleData[getCategoryOf(feature)]['image'],
		width: ICON_SIZE_TILELIST,
		height: ICON_SIZE_TILELIST,
	});

	const strokeStyle = new ol.style.Stroke({
		color: color,
		width: STROKE_WIDTH_TILELIST,
	});

	return new ol.style.Style({
		fill: fillStyle,
		image: imageStyle,
		stroke: strokeStyle,
	});
}

/**
 * Creates a thumb map in the place specified by target with the proposal's id appended
 * @param {object} proposal 
 * @param {string} target 
 */
function createThumbMap(proposal, target) {
	const currentCat = proposal.category;

	const backgroundTileLayer = new ol.layer.Tile({
		className: 'background-tilelayer',
		source: new ol.source.OSM(),
	});

	const vectorSource = new ol.source.Vector();
	const vectorLayer = new ol.layer.Vector({
		source: vectorSource,
		style: tilelistStyleFunction,
	});

	const view = new ol.View({
		center: ol.proj.fromLonLat([centerLon, centerLat]),
		zoom: standardZoom,
		minZoom: MIN_ZOOM,
		maxZoom: MAX_ZOOM_TILELIST,
		constrainResolution: true,
	});

	new ol.Map({
		controls: [],
		layers: [backgroundTileLayer, vectorLayer],
		target: target + proposal.id,
		view: view,
	});

	try {
		importToMapJSON(proposal.features, currentCat, 0, vectorSource);
	} catch (e) {
		console.log(e);
	}

	zoomToFeatures(true, false, vectorSource, view);
}

/**
 * Creates all thumb maps
 */
function createThumbMaps() {
	for (let proposal of proposalList) {
		createThumbMap(proposal, 'tiles-map');
		createThumbMap(proposal, 'list-map');
	}
}
