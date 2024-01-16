/* my-transit-lines.js
(C) by Jan Garloff and Johannes Bouchain - stadtkreation.de
*/

const MIN_ZOOM = 0;
const MAX_ZOOM = 19;
const MAX_ZOOM_OEPNV_MAP = 18;
const MAX_ZOOM_OPENTOPO_MAP = 17;
const SELECTED_Z_INDEX = 1;
const UNSELECTED_Z_INDEX = 0;
const ICON_SIZE_UNSELECTED = 21;
const ICON_SIZE_SELECTED = 23;
const COLOR_SELECTED = '#07f';
const STROKE_WIDTH_SELECTED = 3;
const STROKE_WIDTH_UNSELECTED = 4;
const TEXT_X_OFFSET = 15;
const ZOOM_ANIMATION_DURATION = 100;
const ZOOM_PADDING = [50, 50, 50, 50];
const MAP_ID = 'mtl-map';
const GEO_JSON_FORMAT = new ol.format.GeoJSON({
	featureClass: (editMode || false) ? ol.Feature : ol.render.RenderFeature,
});
const WKT_FORMAT = new ol.format.WKT({
	splitCollection: true,
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
var editMode = editMode || false;

var showLabels = true;
var lowOpacity = true;
var mapColor = true;
var fullscreen = false;
var selectedFeatureIndex = -1;
var snapping = true;
var warningMessage = '';

class InteractionControl extends ol.control.Control {
	constructor(opt_options) {
		const options = opt_options || {};

		const element = document.createElement('div');
		element.className = 'interaction-control ol-control';

		super({
			element: element,
			target: options.target,
		});

		this.pointButton = this.createButton('Point', themeUrl + '/images/drawPoint.png');
		this.lineStringButton = this.createButton('LineString', themeUrl + '/images/drawLineString.png');
		this.polygonButton = this.createButton('Polygon', themeUrl + '/images/drawPolygon.png');
		this.circleButton = this.createButton('Circle', themeUrl + '/images/drawCircle.png');
		this.modifyButton = this.createButton('Modify', themeUrl + '/images/modifyFeature.png');
		this.selectButton = this.createButton('Select', themeUrl + '/images/selectFeatureAddName.png');
		this.deleteButton = this.createButton('Delete', themeUrl + '/images/deleteFeatures.png');
		this.deleteButton.classList.add('unselectable');
		this.navigateButton = this.createButton('Navigate', themeUrl + '/images/navigation.png');
		this.snappingButton = this.createButton('RemoveSnapping', themeUrl + '/images/removeSnapping.png');

		element.appendChild(this.pointButton);
		element.appendChild(this.lineStringButton);
		element.appendChild(this.polygonButton);
		element.appendChild(this.circleButton);
		element.appendChild(this.modifyButton);
		element.appendChild(this.selectButton);
		element.appendChild(this.deleteButton);
		element.appendChild(this.navigateButton);
		element.appendChild(this.snappingButton);
	}

	createButton(value, path) {
		const button = document.createElement('button');
		button.type = 'button';
		button.className = 'interaction-control';
		button.style.backgroundImage = 'url(' + path + ')';
		button.value = value;
		button.title = objectL10n[value];

		button.addEventListener('click', this.handleClick.bind(this), false);

		return button;
	}

	handleClick(event) {
		var target = event.target;

		if (target == this.deleteButton) {
			deleteSelected();
			return;
		}

		if (target == this.snappingButton) {
			toggleSnapping();

			if (snapping) {
				this.snappingButton.style.backgroundImage = 'url(' + themeUrl + '/images/removeSnapping.png)';
				this.snappingButton.title = objectL10n['RemoveSnapping'];
			} else {
				this.snappingButton.style.backgroundImage = 'url(' + themeUrl + '/images/addSnapping.png)';
				this.snappingButton.title = objectL10n['AddSnapping'];
			}

			return;
		}

		for (const node of target.parentElement.childNodes) {
			node.classList.remove('selected');
		}

		$('.mtl-tool-hint').css('display','none');
		$('.mtl-tool-hint.' + target.value).css('display','inline');

		target.classList.add('selected');

		setInteraction(target.value);
	}
}

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

		for (var source of BACKGROUND_SOURCES) {
			backgroundSelector.appendChild(this.createLayerOption(source, 'background', source == OSM_SOURCE));
		}

		return backgroundSelector;
	}

	createOverlaySelector() {
		let none = this.createLayerOption({ title: objectL10n.none, id: 'none' }, 'overlay', true);

		let overlaySelector = document.createElement('div');
		overlaySelector.className = 'layer-selector alignleft';
		overlaySelector.id = 'background-layer-selector';
		overlaySelector.textContent = objectL10n.overlaysTitle;
		overlaySelector.appendChild(none);

		for (var source of OVERLAY_SOURCES) {
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

	handleMenuToggle() {
		this.menuOpen = !this.menuOpen;

		if (this.menuOpen) {
			this.innerDiv.classList.remove('hidden');
		} else {
			this.innerDiv.classList.add('hidden');
		}
	}

	handleBackgroundSelector(event) {
		let target = event.target;

		if (target.id.includes('background')) {
			for (var source of BACKGROUND_SOURCES) {
				if (target.id.includes(source.get('id'))) {
					backgroundTileLayer.setSource(source);
					return;
				}
			}
		} else if (target.id.includes('overlay')) {
			for (var source of OVERLAY_SOURCES) {
				if (target.id.includes(source.get('id'))) {
					overlayTileLayer.setSource(source);
					return;
				}
			}
			overlayTileLayer.setSource(null);
		}
	}
}

const attributionLayer = new ol.layer.Layer({
	source: new ol.source.Source({attributions: objectL10n.attributionIcons}),
	render: function () { return null; }
});

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

const interactionControl = new InteractionControl();
const optionsControl = new OptionsControl();
const attributionControl = new ol.control.Attribution({
	collapsible: true,
	collapsed: false,
});

// global so we can remove them later
let drawInteraction;
const modifyInteraction = new ol.interaction.Modify({ source: vectorSource });
const dragBoxInteraction = new ol.interaction.DragBox();
const selectInteraction = new ol.interaction.Select({ layers: [vectorLayer], multi: true, style: styleFunction });
const snapInteraction = new ol.interaction.Snap({ source: vectorSource });

const selectedFeatures = selectInteraction.getFeatures();

const map = new ol.Map({
	controls: [new ol.control.Zoom(), new ol.control.ScaleLine(), attributionControl, optionsControl].concat(editMode ? [interactionControl] : []),
	layers: [attributionLayer, backgroundTileLayer, overlayTileLayer, vectorLayer],
	target: MAP_ID,
	view: view,
});

vectorLayer.on('sourceready', handleSourceReady);

dragBoxInteraction.on('boxend', handleBoxSelect);
dragBoxInteraction.on('boxstart', function (event) {
	if (!ol.events.condition.shiftKeyOnly(event.mapBrowserEvent))
		selectedFeatures.clear();
});

modifyInteraction.on('modifystart', function (event) {
	for (var feature of event.features.getArray()) {
		handleFeatureModified(feature);
	}
});
modifyInteraction.on('modifyend', function (event) {
	for (var feature of event.features.getArray()) {
		feature.unset('size');
	}
});

selectedFeatures.on('add', handleFeatureSelected);
selectedFeatures.on('remove', handleFeatureUnselected);

//Notify the user when about to leave page without saving changes
$(window).bind('beforeunload', function() {
	if (warningMessage != '') return warningMessage;
});

if (editMode) {
	$('#title, #description').on('input propertychange paste', function() {
		warningMessage = objectL10n.confirmLeaveWebsite;
	});
	$('input.cat-select').change(function() {
		warningMessage = objectL10n.confirmLeaveWebsite;
	});
}

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
	const colorUnselected = transportModeStyleData[getCategoryOf(feature)][0];
	const unselected = selectedFeatures.getArray().indexOf(feature) < 0;

	const fillStyle = new ol.style.Fill({
		color: unselected ? (colorUnselected + '40') : (COLOR_SELECTED + '4'),
	});

	const iconSize = unselected ? ICON_SIZE_UNSELECTED : ICON_SIZE_SELECTED;

	const imageStyle = new ol.style.Icon({
		src: transportModeStyleData[getCategoryOf(feature)][unselected ? 1 : 2],
		width: iconSize,
		height: iconSize,
	});

	const strokeStyle = new ol.style.Stroke({
		color: unselected ? colorUnselected : COLOR_SELECTED,
		width: unselected ? STROKE_WIDTH_UNSELECTED : STROKE_WIDTH_SELECTED,
	});

	var text = ((showLabels ? feature.get('name') : '') || '') + (feature.get('size') ? '\n' + feature.get('size') : '');

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

	const zIndex = unselected ? UNSELECTED_Z_INDEX : SELECTED_Z_INDEX;

	return new ol.style.Style({
		fill: fillStyle,
		image: imageStyle,
		stroke: strokeStyle,
		text: textStyle,
		zIndex: zIndex,
	});
}

// returns the style for the given feature while being drawn
function drawStyleFunction(feature) {
	style = ol.style.Style.createEditingStyle()[feature.getGeometry().getType()];

	style[0].text_ = new ol.style.Text({
		font: 'bold 11px sans-serif',
		text: feature.get('size') || '',
		textAlign: 'left',
		fill: new ol.style.Fill({
			color: 'white',
		}),
		stroke: new ol.style.Stroke({
			color: COLOR_SELECTED,
			width: STROKE_WIDTH_SELECTED,
		}),
		offsetX: TEXT_X_OFFSET,
		overflow: true,
	});

	return style;
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
		return objectL10n.lengthString + formatNumber(ol.sphere.getLength(geom));
	} else if (geom instanceof ol.geom.Polygon) {
		return objectL10n.area + formatNumber(ol.sphere.getArea(geom), true);
	} else if (geom instanceof ol.geom.Circle) {
		return objectL10n.radius + formatNumber(ol.sphere.getDistance(geom.transform('EPSG:3857', 'EPSG:4326').getCenter(), geom.getLastCoordinate()));
	}
}

// Format a number and its unit
function formatNumber(number, squared = false, unit = 'm') {
	unit = unit + (squared ? '²' : '');
	step = 1000 * (squared ? 1000 : 1);

	if (number < step) {
		if (number > 1E3) {
			return Math.round(number) + unit;
		}
		return number.toPrecision(3).replace('.', objectL10n.decimalSeparator) + unit;
	} else {
		if (number / step > 1E3) {
			return Math.round(number / step) + 'k' + unit;
		}
		return (number / step).toPrecision(3).replace('.', objectL10n.decimalSeparator) + 'k' + unit;
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
 * @returns the selected category determined by the selected category checkbox, or if none is selected by the 
 */
function getSelectedCategory() {
	var selectedTransportMode = $('.cat-select:checked').val();
	if (!selectedTransportMode && parseInt(currentCat)) {
		selectedTransportMode = currentCat;
	}
	if (!selectedTransportMode)
		selectedTransportMode = defaultCategory;
	return selectedTransportMode;
}

// redraws the map to update color/icons
function redraw() {
	vectorSource.dispatchEvent('change');
}

function unselectAllFeatures() {
	selectedFeatures.clear();
}

// removes all selected features
function deleteSelected() {
	var featureArray = Array();
	for (var i = selectedFeatures.getArray().length; i > 0; i--) {
		featureArray.push(selectedFeatures.getArray()[i - 1]);
		selectedFeatures.removeAt(i - 1);
	}
	featureArray.forEach(function (feature) {
		vectorSource.removeFeature(feature);
	});
}

// Open textinput for feature name and show size of feature
function handleFeatureSelected(event) {
	interactionControl.deleteButton.classList.remove('unselectable');

	$('#feature-textinput').val(event.element.get('name'));
	$('.feature-textinput-box').slideDown();
	$('.set-name').css('display', 'block');
	$('.set-name').click(function () {
		unselectAllFeatures();
	});
	selectedFeatureIndex = vectorSource.getFeatures().indexOf(event.element);

	event.element.set('size', getFeatureSize(event.element));
}

// Set name of unselected feature to name from the textinput and remove size being shown
function handleFeatureUnselected(event) {
	if (selectedFeatures.getArray().length == 0)
		interactionControl.deleteButton.classList.add('unselectable');

	if (vectorSource.getFeatures().indexOf(event.element) == selectedFeatureIndex) {
		vectorSource.getFeatures()[selectedFeatureIndex].set('name', $('#feature-textinput').val());
		selectedFeatureIndex = -1;
		$('#feature-textinput').val('');
		$('.feature-textinput-box').slideUp();
		$('.set-name').css('display', 'none');
	}

	event.element.unset('size');
}

// Show the size of modified features
function handleFeatureModified(feature) {
	feature.set('size', getFeatureSize(feature));
	feature.on('change', function () {
		if (feature.get('size'))
			feature.set('size', getFeatureSize(feature));
	});
}

// Selects all features inside the box dragged for selection
function handleBoxSelect() {
	const boxExtent = dragBoxInteraction.getGeometry().getExtent();

	// if the extent crosses the antimeridian process each world separately
	const worldExtent = map.getView().getProjection().getExtent();
	const worldWidth = ol.extent.getWidth(worldExtent);
	const startWorld = Math.floor((boxExtent[0] - worldExtent[0]) / worldWidth);
	const endWorld = Math.floor((boxExtent[2] - worldExtent[0]) / worldWidth);

	for (let world = startWorld; world <= endWorld; ++world) {
		const left = Math.max(boxExtent[0] - world * worldWidth, worldExtent[0]);
		const right = Math.min(boxExtent[2] - world * worldWidth, worldExtent[2]);
		const extent = [left, boxExtent[1], right, boxExtent[3]];

		const boxFeatures = vectorSource
			.getFeaturesInExtent(extent)
			.filter(
				(feature) =>
					!selectedFeatures.getArray().includes(feature) &&
					feature.getGeometry().intersectsExtent(extent)
			);

		// features that intersect the box geometry are added to the
		// collection of selected features
		selectedFeatures.extend(boxFeatures);
	}
}

// Runs after the sourceready event was fired
function handleSourceReady() {
	importAllWKT();

	addSaveEventListeners();
}

/**
 * Sets only the specified interaction removing all others
 * 
 * @param {string} interactionType 
 */
function setInteraction(interactionType) {
	removeInteractions();

	switch (interactionType) {
		case 'Circle':
			alert(objectL10n.circleNotSupported);
		case 'LineString':
		case 'Point':
		case 'Polygon':
			drawInteraction = new ol.interaction.Draw({ source: vectorSource, type: interactionType, style: drawStyleFunction });
			drawInteraction.on('drawstart', function (event) {
				handleFeatureModified(event.feature);
			});
			drawInteraction.on('drawend', function (event) {
				event.feature.unset('size');
			});
			map.addInteraction(drawInteraction);
			map.addInteraction(snapInteraction);
			break;
		case 'Select':
			map.addInteraction(dragBoxInteraction);
			map.addInteraction(selectInteraction);
			break;
		case 'Modify':
			map.addInteraction(modifyInteraction);
			map.addInteraction(snapInteraction);
			break;
		case 'Navigate':
			break;
		default:
			throw 'Unrecognised interaction type';
	}
}

/**
 * Removes all interactions
 */
function removeInteractions() {
	map.removeInteraction(drawInteraction);
	selectedFeatures.clear();
	map.removeInteraction(selectInteraction);
	map.removeInteraction(modifyInteraction);
	map.removeInteraction(snapInteraction);
	map.removeInteraction(dragBoxInteraction);
}

// Removes all features from the layer
function removeAllFeatures() {
	vectorSource.clear();
}

// Reloads features from source vars
function loadNewFeatures() {
	removeAllFeatures();

	importAllWKT();
}

// Imports all features from vectorData, vectorLabelsData and vectorCategoriesData and handles errors
function importAllWKT() {
	for (var i = 0; i < vectorData.length && i < vectorCategoriesData.length && i < vectorLabelsData.length; i++) {
		try {
			importToMapWKT(vectorData[i], vectorLabelsData[i].split(','), vectorCategoriesData[i]);
		} catch (e) {
			console.log(e);
		}
	}

	zoomToFeatures(true);
}

/**
 * Imports all feature data from the files of the filePicker input to the map
 * @param {Node} filePicker 
 */
function importJSONFiles(filePicker) {
	for (var i = 0; i < filePicker.files.length; i++) {
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
 * Imports source strings to the map using the WKT format
 * Only handles one WKT string at a time
 * @param {string} source vector data
 * @param {string[]} labelsSource labels data
 * @param {string} categorySource category to use
 */
function importToMapWKT(source, labelsSource, categorySource, vector = vectorSource) {
	if (source == '' || source == 'GEOMETRYCOLLECTION()')
		return;

	let features = WKT_FORMAT.readFeatures(source, PROJECTION_OPTIONS);

	var labelIndex = 0;
	for (var feature of features) {
		feature.set('category', categorySource);

		feature.set('name', decodeSpecialChars(labelsSource[labelIndex] || ''));
		labelIndex++;
	}

	if (selectedFeatureIndex) {
		selectedFeatureIndex += features.length;
	}

	vector.addFeatures(features);
}

/**
 * Exports features from vectorSource to an array of a wkt string and a labels string (separated by commas)
 * @returns {string[]}
 */
function exportToWKT() {
	var features = removeCircles(vectorSource.getFeatures(), false);

	let wkt_string = WKT_FORMAT.writeFeatures(features, PROJECTION_OPTIONS);

	let labelString = '';
	for (var feature of features) {
		labelString += encodeSpecialChars(feature.get('name') || "") + ",";
	}

	return [wkt_string, labelString];
}

/**
 * Imports source string to the map using the GeoJSON format
 * @param {string|JSON} source the JSON string or object to import
 * @param {string} categorySource the category to use for the features
 */
function importToMapJSON(source, categorySource) {
	if (source == '' || source == '{}')
		return;

	let features = GEO_JSON_FORMAT.readFeatures(source.replaceAll("\r", "").replaceAll("\n", ""), PROJECTION_OPTIONS);

	for (var feature of features) {
		feature.set('category', categorySource);

		feature.set('name', decodeSpecialChars(feature.get('name') || ""));
	}

	features = addCircles(features);

	if (selectedFeatureIndex) {
		selectedFeatureIndex += features.length;
	}

	vectorSource.addFeatures(features);
}

/**
 * Exports features from vectorSource to a GeoJSON string
 * @returns {string}
 */
function exportToJSON() {
	let features = removeCircles(vectorSource.getFeatures());

	for (var feature of features) {
		feature.set('name', encodeSpecialChars(feature.get('name') || ""));
	}

	let json_string = GEO_JSON_FORMAT.writeFeatures(features, PROJECTION_OPTIONS);

	for (var feature of features) {
		feature.set('name', decodeSpecialChars(feature.get('name') || ""));
	}

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
			padding: padding ? ZOOM_PADDING : [0, 0, 0, 0],
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

function setTitle(newTitle) {
	$('title').html(newTitle);
	$('h1.entry-title').html(newTitle);
}

/*
* Decodes string to include , " '
* 
* "&#44;"  becomes ','
* "&quot;" becomes '"'
* "&apos;" becomes '''
*/
function decodeSpecialChars(p_string) {
	p_string = p_string.replace(/&#44;/g,',');
	p_string = p_string.replace(/&quot;/g,'"');
	p_string = p_string.replace(/&apos;/g,'\'');
	return p_string;
}

/*
* Encodes string and removes , " '
* 
* ',' becomes "&#44;"
* '"' becomes "&quot;"
* ''' becomes "&apos;"
*/
function encodeSpecialChars(p_string) {
	p_string = p_string.replace(/,/g,'&#44;');
	p_string = p_string.replace(/"/g,'&quot;');
	p_string = p_string.replace(/'/g,'&apos;');
	return p_string;
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

	for (var feature of features) {
		if (feature.getGeometry() instanceof ol.geom.Circle) {
			if (replace) {
				let center = feature.getGeometry().getCenter();
				let radius = feature.getGeometry().getRadius();

				let newFeature = new ol.Feature(new ol.geom.Point(center));
				newFeature.set('radius', radius);

				result.push(newFeature);
			}
		} else {
			result.push(feature);
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

	for (var feature of features) {
		if (feature.getGeometry() instanceof ol.geom.Point && feature.get('radius')) {
			let center = feature.getGeometry().getCoordinates();
			let radius = feature.get('radius');

			let newFeature = new ol.Feature(new ol.geom.Circle(center, radius));

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
 * @returns the amount of stations placed on the map
 */
function getCountStations(features = vectorSource.getFeatures()) {
	var count = 0;
	for (var feature of features) {
		if (feature.getGeometry() instanceof ol.geom.Point)
			count++;
	}
	return count;
}

/**
 * Returns the combined length of the lines in the features array
 * 
 * @param {FeatureLike[]} features the array of features to "search" for lines
 * @returns the combined length of the lines placed on the map
 */
function getLineLength(features = vectorSource.getFeatures()) {
	var length = 0.0;
	for (var feature of features) {
		if (feature.getGeometry() instanceof ol.geom.LineString) {
			length += ol.sphere.getLength(feature.getGeometry());
		}
	}
	return length;
}

/**
 * Saves the WKT data of the features array passed into the function to the HTML <input> elements.
 * The data is saved to the DB when the user saves the proposal
 * 
 * @param {FeatureLike[]} features the array of features to save
 */
function saveToHTML(features = vectorSource.getFeatures()) {
	warningMessage = objectL10n.confirmLeaveWebsite;

	var wkt_strings = exportToWKT(features);

	// write WKT features data to html element (will be saved to database on form submit)
	$('#mtl-feature-data').val(wkt_strings[0]);
	$('#mtl-feature-labels-data').val(wkt_strings[1]);
	$('#mtl-count-stations').val(getCountStations(features));
	$('#mtl-line-length').val(getLineLength(features));
}

/**
 * Adds event listeners to save to HTML automatically
 */
function addSaveEventListeners() {
	if (editMode) {
		vectorSource.on('addfeature', () => {saveToHTML()});
		vectorSource.on('changefeature', () => {saveToHTML()});
		vectorSource.on('removefeature', () => {saveToHTML()});
	}
}
