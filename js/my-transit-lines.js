/* my-transit-lines.js
(C) by Jan Garloff and Johannes Bouchain - stadtkreation.de
*/

// TODO remove these by further refactoring
var editMode = editMode || false;
var multipleMode = multipleMode || false;

const MIN_ZOOM = 0;
const MAX_ZOOM = 19;
const MAX_ZOOM_OEPNV_MAP = 18;
const MAX_ZOOM_OPENTOPO_MAP = 17;
const UNSELECTED_Z_INDEX = 0;
const SELECTED_Z_INDEX = 1;
const ICON_SIZE_UNSELECTED = 21;
const ICON_SIZE_SELECTED = 23;
const COLOR_SELECTED = '#07f';
const STROKE_WIDTH_UNSELECTED = 4;
const STROKE_WIDTH_SELECTED = 3;
const TEXT_X_OFFSET = 15;
const ZOOM_ANIMATION_DURATION = 100;
const ZOOM_PADDING = [50, 50, 50, 50];
const MAP_ID = 'mtl-map';
const GEO_JSON_FORMAT = new ol.format.GeoJSON({
	featureClass: editMode ? ol.Feature : ol.render.RenderFeature,
});
const PROJECTION_OPTIONS = {
	dataProjection: 'EPSG:4326',
	featureProjection: 'EPSG:3857',
};

const OSM_SOURCE = new ol.source.OSM({
	crossOrigin: null,
}); OSM_SOURCE.setProperties({ title: objectL10n.titleOSM, id: 'osm' });
const OEPNVKARTE_SOURCE = new ol.source.OSM({
	url: 'https://tile.memomaps.de/tilegen/{z}/{x}/{y}.png',
	attributions: objectL10n.attributionOPNV,
	maxZoom: MAX_ZOOM_OEPNV_MAP,
	crossOrigin: null,
}); OEPNVKARTE_SOURCE.setProperties({title: objectL10n.titleOPNV, id: 'oepnv'});
const OPENTOPOMAP_SOURCE = new ol.source.OSM({
	url: 'https://tile.opentopomap.org/{z}/{x}/{y}.png',
	attributions: objectL10n.attributionOpentopomap,
	maxZoom: MAX_ZOOM_OPENTOPO_MAP,
	crossOrigin: null,
}); OPENTOPOMAP_SOURCE.setProperties({ title: objectL10n.titleOpentopomap, id: 'opentopo' });
const ESRI_SOURCE = new ol.source.OSM({
	url: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}.png',
	attributions: objectL10n.attributionESRISatellite,
	crossOrigin: null,
}); ESRI_SOURCE.setProperties({ title: objectL10n.titleESRISatellite, id: 'esri' });

const OPENRAILWAYMAP_STANDARD_SOURCE = new ol.source.OSM({
	url: 'https://tiles.openrailwaymap.org/standard/{z}/{x}/{y}.png',
	attributions: objectL10n.attributionOpenrailwaymap,
	opaque: false,
	crossOrigin: null,
}); OPENRAILWAYMAP_STANDARD_SOURCE.setProperties({ title: objectL10n.titleOpenrailwaymap, id: 'openrailway-standard' });
const OPENRAILWAYMAP_MAX_SPEED_SOURCE = new ol.source.OSM({
	url: 'https://tiles.openrailwaymap.org/maxspeed/{z}/{x}/{y}.png',
	attributions: objectL10n.attributionOpenrailwaymapMaxspeed,
	opaque: false,
	crossOrigin: null,
}); OPENRAILWAYMAP_MAX_SPEED_SOURCE.setProperties({ title: objectL10n.titleOpenrailwaymapMaxspeed, id: 'openrailway-maxspeed' });
const OPENRAILWAYMAP_ELECTRIFICATION_SOURCE = new ol.source.OSM({
	url: 'https://tiles.openrailwaymap.org/electrified/{z}/{x}/{y}.png',
	attributions: objectL10n.attributionOpenrailwaymapElectrified,
	opaque: false,
	crossOrigin: null,
}); OPENRAILWAYMAP_ELECTRIFICATION_SOURCE.setProperties({title: objectL10n.titleOpenrailwaymapElectrified, id: 'openrailway-electrification'});
const OPENRAILWAYMAP_SIGNALS_SOURCE = new ol.source.OSM({
	url: 'https://tiles.openrailwaymap.org/signals/{z}/{x}/{y}.png',
	attributions: objectL10n.attributionOpenrailwaymapSignals,
	opaque: false,
	crossOrigin: null,
}); OPENRAILWAYMAP_SIGNALS_SOURCE.setProperties({ title: objectL10n.titleOpenrailwaymapSignals, id: 'openrailway-signals' });
const OPENRAILWAYMAP_GAUGE_SOURCE = new ol.source.OSM({
	url: 'https://tiles.openrailwaymap.org/gauge/{z}/{x}/{y}.png',
	attributions: objectL10n.attributionOpenrailwaymapGauge,
	opaque: false,
	crossOrigin: null,
}); OPENRAILWAYMAP_GAUGE_SOURCE.setProperties({ title: objectL10n.titleOpenrailwaymapGauge, id: 'openrailway-gauge' });

const BACKGROUND_SOURCES = [OSM_SOURCE, OEPNVKARTE_SOURCE, OPENTOPOMAP_SOURCE, ESRI_SOURCE];
const OVERLAY_SOURCES = [OPENRAILWAYMAP_STANDARD_SOURCE, OPENRAILWAYMAP_MAX_SPEED_SOURCE, OPENRAILWAYMAP_ELECTRIFICATION_SOURCE, OPENRAILWAYMAP_SIGNALS_SOURCE, OPENRAILWAYMAP_GAUGE_SOURCE];

var centerLon = centerLon || 0;
var centerLat = centerLat || 0;
var standardZoom = standardZoom || 2;

var showLabels = typeof showLabels === "undefined" ? true : showLabels;
var lowOpacity = true;
var mapColor = true;
var fullscreen = false;

class OptionsControl extends ol.control.Control {
	constructor(opt_options) {
		const options = opt_options || {};

		const element = document.createElement('div');
		element.className = 'layer-control ol-control alignright';

		super({
			element: element,
			target: options.target,
		});

		this.innerDiv = document.createElement('div');
		this.innerDiv.className = 'layer-control hidden';
		this.innerDiv.appendChild(this.createBackgroundSelector());
		this.innerDiv.appendChild(this.createOverlaySelector());

		this.menuOpen = false;
		this.menuToggle = this.createMenuToggle();

		element.appendChild(this.menuToggle);
		element.appendChild(this.innerDiv);
	}

	createMenuToggle() {
		let menuToggle = document.createElement('button');
		menuToggle.className = 'layer-control';
		menuToggle.id = 'toggle-layer-switcher';
		menuToggle.type = 'button';
		menuToggle.textContent = '...';
		menuToggle.addEventListener('click', this.handleMenuToggle.bind(this), false);

		return menuToggle;
	}

	createBackgroundSelector() {
		let backgroundSelector = document.createElement('div');
		backgroundSelector.className = 'layer-selector alignleft';
		backgroundSelector.id = 'background-layer-selector';
		backgroundSelector.textContent = objectL10n.baselayersTitle;

		for (let source of BACKGROUND_SOURCES) {
			backgroundSelector.appendChild(this.createLayerOption(source, 'background', source == OSM_SOURCE));
		}

		return backgroundSelector;
	}

	createOverlaySelector() {
		let overlaySelector = document.createElement('div');
		overlaySelector.className = 'layer-selector alignleft';
		overlaySelector.id = 'background-layer-selector';
		overlaySelector.textContent = objectL10n.overlaysTitle;

		this.showVectorLayer = true;
		let vectorLayerToggle = this.createVectorLayerOption();
		overlaySelector.appendChild(vectorLayerToggle);

		let none = this.createLayerOption({ title: objectL10n.none, id: 'none' }, 'overlay', true);
		overlaySelector.appendChild(none);

		for (let source of OVERLAY_SOURCES) {
			overlaySelector.appendChild(this.createLayerOption(source, 'overlay'));
		}

		return overlaySelector;
	}

	createLayerOption(source, type, checked = false) {
		let selector = document.createElement('input');
		selector.type = 'radio';
		selector.checked = checked;
		selector.id = (source.id || source.get('id')) + '-' + type + '-selector';
		selector.name = type + '-selector';
		selector.addEventListener('change', this.handleBackgroundSelector.bind(this));

		let label = document.createElement('label');
		label.id = (source.id || source.get('id')) + '-' + type;
		label.textContent = (source.title || source.get('title')) + ' ';
		label.className = 'alignright layer-control';
		label.appendChild(selector);

		return label;
	}

	createVectorLayerOption() {
		let selector = document.createElement('input');
		selector.type = 'checkbox';
		selector.checked = true;
		selector.id = 'vector-layer-toggle-selector';
		selector.addEventListener('change', this.handleVectorLayerToggle.bind(this));

		let label = document.createElement('label');
		label.id = 'vector-layer-toggle';
		label.textContent = objectL10n.vectorLayerToggle + ' ';
		label.className = 'alignright layer-control';
		label.appendChild(selector);

		return label;
	}

	handleMenuToggle() {
		this.menuOpen = !this.menuOpen;

		if (this.menuOpen) {
			this.innerDiv.classList.remove('hidden');
		} else {
			this.innerDiv.classList.add('hidden');
		}
	}

	handleVectorLayerToggle() {
		this.showVectorLayer = !this.showVectorLayer;

		vectorLayer.setVisible(this.showVectorLayer);
	}

	handleBackgroundSelector(event) {
		let target = event.target;

		if (target.id.includes('background')) {
			for (let source of BACKGROUND_SOURCES) {
				if (target.id.includes(source.get('id'))) {
					backgroundTileLayer.setSource(source);
					return;
				}
			}
		} else if (target.id.includes('overlay')) {
			for (let source of OVERLAY_SOURCES) {
				if (target.id.includes(source.get('id'))) {
					overlayTileLayer.setSource(source);
					return;
				}
			}
			overlayTileLayer.setSource(null);
		}
	}
}

const backgroundTileLayer = new ol.layer.Tile({
	className: 'background-tilelayer',
	source: OSM_SOURCE,
});
const overlayTileLayer = new ol.layer.Tile({
	className: 'overlay-tilelayer',
	source: null,
});

const vectorSource = new ol.source.Vector();
const vectorLayerConfig = {
	source: vectorSource,
	style: styleFunction,
};
const vectorLayer = editMode ? new ol.layer.Vector(vectorLayerConfig) : new ol.layer.VectorImage(vectorLayerConfig);

const view = new ol.View({
	center: ol.proj.fromLonLat([centerLon, centerLat]),
	zoom: standardZoom,
	minZoom: MIN_ZOOM,
	maxZoom: MAX_ZOOM,
});

const optionsControl = new OptionsControl();
const attributionControl = new ol.control.Attribution({
	collapsible: true,
	collapsed: false,
});

const map = new ol.Map({
	controls: [new ol.control.Zoom(), new ol.control.ScaleLine(), new ol.control.Rotate(), attributionControl, optionsControl],
	layers: [backgroundTileLayer, overlayTileLayer, vectorLayer],
	target: MAP_ID,
	view: view,
});

window.addEventListener("load", importAllJSON);

$(document).ready(function(){
	// Proposal contact form
	if($('#proposal-author-contact-form').length) {
		$('#proposal-author-contact-form .pacf-toggle').on('click',function(e){
			e.preventDefault();
			$(this).closest('div').find('form').slideToggle();
		});
	}
});

// returns the style for the given feature
function styleFunction(feature) {
	const color = transportModeStyleData[getCategoryOf(feature)]['color'];

	const fillStyle = new ol.style.Fill({
		color: color + '40',
	});

	const imageStyle = new ol.style.Icon({
		src: transportModeStyleData[getCategoryOf(feature)]['image'],
		width: ICON_SIZE_UNSELECTED,
		height: ICON_SIZE_UNSELECTED,
	});

	const strokeStyle = new ol.style.Stroke({
		color: color,
		width: STROKE_WIDTH_UNSELECTED,
	});

	let text = ((showLabels ? feature.get('name') : '') || '') + (feature.get('size') ? '\n' + feature.get('size') : '');

	const textStyle = new ol.style.Text({
		font: 'bold 11px sans-serif',
		text: text,
		textAlign: 'left',
		fill: new ol.style.Fill({
			color: 'white',
		}),
		stroke: strokeStyle,
		offsetX: TEXT_X_OFFSET,
		overflow: true,
	});

	const zIndex = UNSELECTED_Z_INDEX;

	return new ol.style.Style({
		fill: fillStyle,
		image: imageStyle,
		stroke: strokeStyle,
		text: textStyle,
		zIndex: zIndex,
	});
}

// returns the style for a selected feature
function selectedStyleFunction(feature) {
    const fillStyle = new ol.style.Fill({
		color: COLOR_SELECTED + '4',
	});

	const imageStyle = new ol.style.Icon({
		src: transportModeStyleData[getCategoryOf(feature)]['image-selected'],
		width: ICON_SIZE_SELECTED,
		height: ICON_SIZE_SELECTED,
	});

	const strokeStyle = new ol.style.Stroke({
		color: COLOR_SELECTED,
		width: STROKE_WIDTH_SELECTED,
	});

	let text = ((showLabels ? feature.get('name') : '') || '') + (feature.get('size') ? '\n' + feature.get('size') : '');

	const textStyle = new ol.style.Text({
		font: 'bold 11px sans-serif',
		text: text,
		textAlign: 'left',
		fill: new ol.style.Fill({
			color: 'white',
		}),
		stroke: strokeStyle,
		offsetX: TEXT_X_OFFSET,
		overflow: true,
	});

	const zIndex = SELECTED_Z_INDEX;

	return new ol.style.Style({
		fill: fillStyle,
		image: imageStyle,
		stroke: strokeStyle,
		text: textStyle,
		zIndex: zIndex,
	});
}

/**
 * Gets the size (length, radius, area) of the given feature
 * @param {FeatureLike} feature 
 * @returns {string} Containing the size in a human readable format (not just the number)
 */
function getFeatureSize(feature) {
	geom = feature.clone().getGeometry();

	if (geom instanceof ol.geom.Point) {
		return "";
	} else if (geom instanceof ol.geom.LineString) {
		return objectL10n.lengthString + formatNumber(ol.sphere.getLength(geom), false) + 'm';
	} else if (geom instanceof ol.geom.Polygon) {
		return objectL10n.area + formatNumber(ol.sphere.getArea(geom), false, 1E6) + 'm²';
	} else if (geom instanceof ol.geom.Circle) {
		return objectL10n.radius + formatNumber(ol.sphere.getDistance(geom.transform('EPSG:3857', 'EPSG:4326').getCenter(), geom.getLastCoordinate()), false) + 'm';
	}
}

/**
 * Get the category of the feature passed to the function.
 * This is the category saved in feature.get('category') if present of getSelectedCategory() otherwise
 * 
 * @param {*} feature the feature to get the category of
 * @returns the category of the feature passed to the function
 */
function getCategoryOf(feature) {
	return feature.get('category') ? feature.get('category') : getSelectedCategory();
}

/**
 * @returns the selected category determined by the selected category checkbox, or if none is selected by the defaultCategory variable
 */
function getSelectedCategory() {
	let checkedBox = Array.from(document.querySelectorAll('input.cat-select')).filter(node => {
		return node.checked;
	})[0];

	if (!checkedBox || !checkedBox.value)
		return defaultCategory;

	return checkedBox.value;
}

// redraws the map to update color/icons
function redraw() {
	vectorSource.dispatchEvent('change');
}

// Removes all features from the layer
function removeAllFeatures() {
	vectorSource.clear();
}

// Reloads features from source vars
function loadNewFeatures() {
	removeAllFeatures();

	importAllJSON();
}

// Imports all features from vectorFeatures and vectorCategoriesData and handles errors
function importAllJSON() {
	for (let proposal of proposalList) {
		try {
			importToMapJSON(proposal.features, proposal.category, proposal.id);
		} catch (e) {
			console.log(e);
		}
	}

	zoomToFeatures(true);
	window.dispatchEvent(new Event('map-load'));
}

/**
 * Imports all feature data from the files of the filePicker input to the map
 * @param {Node} filePicker 
 */
function importJSONFiles(filePicker) {
	for (let i = 0; i < filePicker.files.length; i++) {
		let file = filePicker.files[i];

		file.text().then((value) => {
			if (isJsonParsable(value)) {
				try {
					importToMapJSON(value);
	
					zoomToFeatures();
				} catch (error) {
					console.log(error);
				}
			}
		}, (value) => alert(value));
	}
}

/**
 * Imports source string to the map using the GeoJSON format
 * @param {string|JSON} source the JSON string or object to import
 * @param {string} categorySource the category to use for the features
 * @param {int} proposal_data_index relevant for multiple proposal view: which proposal data index to add to the features. Default: 0
 */
function importToMapJSON(source, categorySource, proposal_data_index = 0, vector = vectorSource) {
	if (source == '' || source == '{}')
		return;

	if (!categorySource)
		categorySource = getSelectedCategory();

	if (typeof source === "string" || source instanceof String)
		source = source.replaceAll("\r", "").replaceAll("\n", "").replaceAll("'", "\"");

	let features = GEO_JSON_FORMAT.readFeatures(source, PROJECTION_OPTIONS);

	for (let feature of features) {
		if (!feature.get('category'))
			feature.set('category', categorySource);

		feature.set('name', decodeSpecialChars(feature.get('name') || ""));

		feature.set('proposal_data_index', proposal_data_index);
	}

	features = addCircles(features);

	if (editMode && selectedFeatureIndex) {
		selectedFeatureIndex += features.length;
	}

	vector.addFeatures(features);
}

/**
 * Exports features from vectorSource to a GeoJSON string
 * @returns {string}
 */
function exportToJSON() {
	let features = removeCircles(vectorSource.getFeatures());

	for (let feature of features) {
		feature.set('name', encodeSpecialChars(feature.get('name') || ""));
		feature.unset('proposal_data_index');
		feature.unset('location');
		feature.unset('size');
	}

	let json_string = GEO_JSON_FORMAT.writeFeatures(features, PROJECTION_OPTIONS).replaceAll("\"", "'");

	return json_string;
}

/**
 * Zooms to show all features in the map
 * @param {boolean} immediately if the zooming should happen immediately or with an animation. Default is animation
 * @param {boolean} padding if there should be a padding between the features and the map border. Default is padding
 * @param {ol.source.Vector} source which source's vectors to zoom to. Default is vectorSource
 * @param {ol.View} viewObject which view to apply the zoom to. Default is view
 */
function zoomToFeatures(immediately = false, padding = true, source = vectorSource, viewObject = view) {
	if (source.getFeatures().length > 0) {
		viewObject.fit(source.getExtent(), {
			padding: padding ? ZOOM_PADDING : [5, 5, 5, 5],
			duration: immediately ? 0 : ZOOM_ANIMATION_DURATION,
		});
	}
}

// toggles snapping
function toggleSnapping() {
	snapping = !snapping;

	snapInteraction.setActive(snapping);
}

// toggles whether the labels get shown on the map or not
function toggleLabels() {
	showLabels = !showLabels;

	redraw();
}

// Toggle if the map is displayed in fullscreen or not
function toggleFullscreen() {
	fullscreen = !fullscreen;
	if (fullscreen) {
		$('#mtl-map-box').addClass('fullscreen');
		$('#mtl-fullscreen-link').addClass('fullscreen');
		$('#mtl-fullscreen-link').addClass('fullscreen');
		$('#mtl-category-select').addClass('fullscreen');
		$('#mtl-color-opacity').addClass('fullscreen');
		$('#mtl-fullscreen-link .fullscreen-open').css('display', 'block');
		$('#mtl-fullscreen-link .fullscreen-closed').css('display', 'none');
	} else {
		$('#mtl-box').find('.fullscreen').removeClass('fullscreen');
		$('#mtl-fullscreen-link .fullscreen-open').css('display', 'none');
		$('#mtl-fullscreen-link .fullscreen-closed').css('display', 'block');
	}
}

// Toggle if the map is brigthened (low opacity) or not (full opacity)
function toggleMapOpacity() {
	lowOpacity = !lowOpacity;

	if (lowOpacity) $('#mtl-map').removeClass('full-opacity');
	else $('#mtl-map').addClass('full-opacity');
}

// Toggle if the map has colors or not
function toggleMapColors() {
	mapColor = !mapColor;

	if (mapColor) $('#mtl-map').removeClass('grayscale-map');
	else $('#mtl-map').addClass('grayscale-map');
}

/*
* Decodes string to include , " '
* Doesn't change p_string and returns the result
* 
* "&#44;"  becomes ','
* "&quot;" becomes '"'
* "&apos;" becomes '''
*/
function decodeSpecialChars(p_string) {
	let new_string = p_string;
	new_string = new_string.replace(/&#44;/g,',');
	new_string = new_string.replace(/&quot;/g,'"');
	new_string = new_string.replace(/&apos;/g,'\'');
	return new_string;
}

/*
* Encodes string and removes , " '
* Doesn't change p_string and returns the result
* 
* ',' becomes "&#44;"
* '"' becomes "&quot;"
* ''' becomes "&apos;"
*/
function encodeSpecialChars(p_string) {
	let new_string = p_string;
	new_string = new_string.replace(/,/g,'&#44;');
	new_string = new_string.replace(/"/g,'&quot;');
	new_string = new_string.replace(/'/g,'&apos;');
	return new_string;
}

// check if string can be parsed as JSON
function isJsonParsable(string) {
	try {
		JSON.parse(string);
	} catch (e) {
		return false;
	}
	return true;
}

/**
 * Removes all circles from the features array
 * @param {FeatureLike[]} features 
 * @param {boolean} replace determines if the circle is replaced by a point with a radius attribute
 * @returns {FeatureLike[]}
 */
function removeCircles(features, replace = true) {
	let result = [];

	for (let feature of features) {
		if (feature.getGeometry() instanceof ol.geom.Circle) {
			if (replace) {
				let center = feature.getGeometry().getCenter();
				let radius = feature.getGeometry().getRadius();

				let newFeature = new ol.Feature(new ol.geom.Point(center));
				newFeature.set('radius', radius);

				result.push(newFeature);
			}
		} else {
			result.push(feature.clone());
		}
	}
	return result;
}

/**
 * Replaces points that have the "radius" attribute with circles with that radius
 * @param {FeatureLike[]} features 
 * @returns {FeatureLike[]}
 */
function addCircles(features) {
	let result = [];

	for (let feature of features) {
		if (feature.getGeometry() instanceof ol.geom.Point && feature.get('radius')) {
			let center = feature.getGeometry().getCoordinates();
			let radius = feature.get('radius');

			let old_properties = feature.getProperties();
			delete old_properties.geometry;

			let newFeature = new ol.Feature(new ol.geom.Circle(center, radius));
			newFeature.setProperties(old_properties);

			result.push(newFeature);
		} else {
			result.push(feature);
		}
	}

	return result;
}

/**
 * Returns the amount of stations in the features array
 * 
 * @param {FeatureLike[]} features the array of features to "search" for stations
 * @returns {number} the amount of stations placed on the map
 */
function getCountStations(features = vectorSource.getFeatures()) {
	let count = 0;
	for (let feature of features) {
		if (feature.getGeometry() instanceof ol.geom.Point)
			count++;
	}
	return count;
}

/**
 * Returns the combined length of the lines in the features array
 * 
 * @param {FeatureLike[]} features the array of features to "search" for lines
 * @returns {number} the combined length of the lines placed on the map
 */
function getLineLength(features = vectorSource.getFeatures()) {
	let length = 0.0;
	for (let feature of features) {
		if (feature.getGeometry() instanceof ol.geom.LineString) {
			length += ol.sphere.getLength(feature.getGeometry());
		}
	}
	return length;
}

/**
 * Returns the combined costs of the lines in the features array
 * 
 * @param {FeatureLike[]} features the array of features to "search" for lines
 * @returns {number} the combined cost of the lines placed on the map
 */
function getLineCost(features = vectorSource.getFeatures()) {
	let cost = 0.0;
	for (let feature of features) {
		if (feature.getGeometry() instanceof ol.geom.LineString) {
			cost += ol.sphere.getLength(feature.getGeometry()) / 1000 * transportModeStyleData[getCategoryOf(feature)]['costs'];
		}
	}
	return cost;
}

/**
 * Returns an Array of category ids used in the map
 * @param {FeatureLike[]} features
 * @returns {Number[]}
 */
function getUsedCats(features = vectorSource.getFeatures()) {
	let result = [];
	for (feature of features) {
		if (!result.includes(Number.parseFloat(feature.get('category'))))
			result.push(Number.parseFloat(feature.get('category')));
	}
	return result;
}
