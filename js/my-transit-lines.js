/* my-transit-lines.js
(C) by Jan Garloff
*/

const MIN_ZOOM = 0;
const MAX_ZOOM = 19;
const MAX_ZOOM_OEPNV_MAP = 18;
const MAX_ZOOM_OPENTOPO_MAP = 17;
const SELECTED_Z_INDEX = 2;
const POINT_UNSELECTED_Z_INDEX = 1;
const LINE_UNSELECTED_Z_INDEX = 0;
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
	featureClass: editMode ? ol.Feature : ol.render.RenderFeature,
});
const WKT_FORMAT = new ol.format.WKT({
	splitCollection: true,
});

const OSM_SOURCE = new ol.source.OSM(); OSM_SOURCE.setProperties({ title: objectL10n.titleOSM, id: 'osm' });
/*const OEPNVKARTE_SOURCE = new ol.source.OSM({
	url: 'https://tile.memomaps.de/tilegen/{z}/{x}/{y}.png',
	attributions: objectL10n.attributionOPNV,
	maxZoom: MAX_ZOOM_OEPNV_MAP,
}); OEPNVKARTE_SOURCE.setProperties({title: objectL10n.titleOPNV, id: 'oepnv'});*/
const OPENTOPOMAP_SOURCE = new ol.source.OSM({
	url: 'https://tile.opentopomap.org/{z}/{x}/{y}.png',
	attributions: objectL10n.attributionOpentopomap,
	maxZoom: MAX_ZOOM_OPENTOPO_MAP,
}); OPENTOPOMAP_SOURCE.setProperties({ title: objectL10n.titleOpentopomap, id: 'opentopo' });
const ESRI_SOURCE = new ol.source.OSM({
	url: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}.png',
	attributions: objectL10n.attributionESRISatellite,
}); ESRI_SOURCE.setProperties({ title: objectL10n.titleESRISatellite, id: 'esri' });

const OPENRAILWAYMAP_STANDARD_SOURCE = new ol.source.OSM({
	url: 'https://tiles.openrailwaymap.org/standard/{z}/{x}/{y}.png',
	attributions: objectL10n.attributionOpenrailwaymap,
	opaque: false,
}); OPENRAILWAYMAP_STANDARD_SOURCE.setProperties({ title: objectL10n.titleOpenrailwaymap, id: 'openrailway-standard' });
const OPENRAILWAYMAP_MAX_SPEED_SOURCE = new ol.source.OSM({
	url: 'https://tiles.openrailwaymap.org/maxspeed/{z}/{x}/{y}.png',
	attributions: objectL10n.attributionOpenrailwaymapMaxspeed,
	opaque: false,
}); OPENRAILWAYMAP_MAX_SPEED_SOURCE.setProperties({ title: objectL10n.titleOpenrailwaymapMaxspeed, id: 'openrailway-maxspeed' });
/*const OPENRAILWAYMAP_ELECTRIFICATION_SOURCE = new ol.source.OSM({
	url: 'https://tiles.openrailwaymap.org/electrified/{z}/{x}/{y}.png',
	attributions: objectL10n.attributionOpenrailwaymapElectrified,
	opaque: false,
}); OPENRAILWAYMAP_ELECTRIFICATION_SOURCE.setProperties({title: objectL10n.titleOpenrailwaymapElectrified, id: 'openrailway-electrification'});*/
const OPENRAILWAYMAP_SIGNALS_SOURCE = new ol.source.OSM({
	url: 'https://tiles.openrailwaymap.org/signals/{z}/{x}/{y}.png',
	attributions: objectL10n.attributionOpenrailwaymapSignals,
	opaque: false,
}); OPENRAILWAYMAP_SIGNALS_SOURCE.setProperties({ title: objectL10n.titleOpenrailwaymapSignals, id: 'openrailway-signals' });
const OPENRAILWAYMAP_GAUGE_SOURCE = new ol.source.OSM({
	url: 'https://tiles.openrailwaymap.org/gauge/{z}/{x}/{y}.png',
	attributions: objectL10n.attributionOpenrailwaymapGauge,
	opaque: false,
}); OPENRAILWAYMAP_GAUGE_SOURCE.setProperties({ title: objectL10n.titleOpenrailwaymapGauge, id: 'openrailway-gauge' });

const BACKGROUND_SOURCES = [OSM_SOURCE, /*OEPNVKARTE_SOURCE,*/ OPENTOPOMAP_SOURCE, ESRI_SOURCE];
const OVERLAY_SOURCES = [OPENRAILWAYMAP_STANDARD_SOURCE, OPENRAILWAYMAP_MAX_SPEED_SOURCE, /*OPENRAILWAYMAP_ELECTRIFICATION_SOURCE,*/ OPENRAILWAYMAP_SIGNALS_SOURCE, OPENRAILWAYMAP_GAUGE_SOURCE];

var centerLon = centerLon || 0;
var centerLat = centerLat || 0;
var standardZoom = standardZoom || 2;

var showLabels = true;
var fullscreen = false;
var selectedFeatureIndex = -1;
var snapping = true;

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

		element.appendChild(this.pointButton);
		element.appendChild(this.lineStringButton);
		element.appendChild(this.polygonButton);
		element.appendChild(this.circleButton);
		element.appendChild(this.modifyButton);
		element.appendChild(this.selectButton);
		element.appendChild(this.deleteButton);
		element.appendChild(this.navigateButton);
	}

	createButton(value, path) {
		const button = document.createElement('button');
		button.type = 'button';
		button.className = 'interaction-control';
		button.style.backgroundImage = 'url(' + path + ')';
		button.value = value;

		button.addEventListener('click', this.handleClick.bind(this), false);

		return button;
	}

	handleClick(event) {
		var target = event.target;

		if (target != this.deleteButton) {
			for (const node of target.parentElement.childNodes) {
				node.classList.remove('selected');
			}

			target.classList.add('selected');
		} else {
			deleteSelected();
			return;
		}

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
		this.innerDiv.appendChild(this.createSnappingToggle());
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

	createSnappingToggle() {
		let snappingToggle = document.createElement('input');
		snappingToggle.id = 'toggle-snapping';
		snappingToggle.type = 'checkbox';
		snappingToggle.autocomplete = 'off';
		snappingToggle.checked = true;
		snappingToggle.addEventListener('change', toggleSnapping, false);

		let snappingToggleLabel = document.createElement('label');
		snappingToggleLabel.className = 'layer-control alignright';
		snappingToggleLabel.id = 'toggle-snapping-label';
		snappingToggleLabel.for = 'toggle-snapping';
		snappingToggleLabel.textContent = objectL10n.snapping + ' ';
		snappingToggleLabel.appendChild(snappingToggle);

		return snappingToggleLabel;
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
	controls: [new ol.control.Zoom(), attributionControl, optionsControl].concat(editMode ? [interactionControl] : []),
	layers: [backgroundTileLayer, overlayTileLayer, vectorLayer],
	target: MAP_ID,
	view: view,
});

vectorLayer.on('sourceready', handleSourceReady);

dragBoxInteraction.on('boxend', handleBoxSelect);
dragBoxInteraction.on('boxstart', function (event) {
	if (!ol.events.condition.shiftKeyOnly(event.mapBrowserEvent))
		selectedFeatures.clear();
});

selectedFeatures.on('add', handleFeatureSelected);
selectedFeatures.on('remove', handleFeatureSelected);

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

	const textStyle = new ol.style.Text({
		font: 'bold 11px sans-serif',
		text: showLabels ? feature.get('name') : '',
		textAlign: 'left',
		fill: new ol.style.Fill({
			color: 'white',
		}),
		stroke: strokeStyle,
		offsetX: TEXT_X_OFFSET,
	});

	const zIndex = unselected ? (feature.getGeometry() instanceof ol.geom.Point ? POINT_UNSELECTED_Z_INDEX : LINE_UNSELECTED_Z_INDEX) : SELECTED_Z_INDEX;

	return new ol.style.Style({
		fill: fillStyle,
		image: imageStyle,
		stroke: strokeStyle,
		text: textStyle,
		zIndex: zIndex,
	});
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
	if(!selectedTransportMode && parseInt(currentCat)) {
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

function handleFeatureSelected(event) {
	if (event.type == 'add') {
		interactionControl.deleteButton.classList.remove('unselectable');

		$('#feature-textinput').val(event.element.get('name'));
		$('.feature-textinput-box').slideDown();
		$('.set-name').css('display', 'block');
		$('.set-name').click(function () {
			unselectAllFeatures();
		});
		selectedFeatureIndex = vectorSource.getFeatures().indexOf(event.element);
	} else if (event.type = 'remove') {
		if (selectedFeatures.getArray().length == 0)
			interactionControl.deleteButton.classList.add('unselectable');

		if (vectorSource.getFeatures().indexOf(event.element) == selectedFeatureIndex) {
			vectorSource.getFeatures()[selectedFeatureIndex].set('name', $('#feature-textinput').val());
			selectedFeatureIndex = -1;
			$('#feature-textinput').val('');
			$('.feature-textinput-box').slideUp();
			$('.set-name').css('display', 'none');
		}
	}
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

function handleSourceReady() {
	setMapColors();
	setMapOpacity();

	for (var i = 0; i < vectorData.length && i < vectorCategoriesData.length && i < vectorLabelsData.length; i++) {
		importToMapWKT(vectorData[i], vectorLabelsData[i].split(','), vectorCategoriesData[i]);
	}

	zoomToFeatures(true);
}

// Toggles snapping on/off
function toggleSnapping() {
	snapping = !snapping;

	snapInteraction.setActive(snapping);
}

/**
 * Sets only the specified interaction removing all others
 * 
 * @param {string} interactionType 
 */
function setInteraction(interactionType) {
	removeInteractions();

	switch (interactionType) {
		case 'LineString':
		case 'Point':
		case 'Circle':
		case 'Polygon':
			drawInteraction = new ol.interaction.Draw({ source: vectorSource, type: interactionType });
			map.addInteraction(drawInteraction);
			if (snapping)
				map.addInteraction(snapInteraction);
			break;
		case 'Select':
			map.addInteraction(dragBoxInteraction);
			map.addInteraction(selectInteraction);
			break;
		case 'Modify':
			map.addInteraction(modifyInteraction);
			if (snapping)
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

function removeAllFeatures() {
	vectorSource.clear();
}

function loadNewFeatures() {
	removeAllFeatures();

	for (var i = 0; i < vectorData.length && i < vectorCategoriesData.length && i < vectorLabelsData.length; i++) {
		importToMapWKT(vectorData[i], vectorLabelsData[i].split(','), vectorCategoriesData[i]);
	}

	zoomToFeatures(true);
}

/**
 * Imports source strings to the map using the WKT format
 * Only handles one WKT string at a time
 * @param {string} source vector data
 * @param {string[]} labelsSource labels data
 * @param {string} categorySource category to use
 */
function importToMapWKT(source, labelsSource, categorySource) {
	if (source == '' || source == 'GEOMETRYCOLLECTION()')
		return;

	let features = WKT_FORMAT.readFeatures(source, {
		dataProjection: 'EPSG:4326',
		featureProjection: 'EPSG:3857',
	});

	var labelIndex = 0;

	for (var feature of features) {
		feature.set('category', categorySource);

		feature.set('name', labelsSource[labelIndex]);
		labelIndex++;
	}

	vectorSource.addFeatures(features);
}

// toggles whether the labels get shown on the map or not
function toggleLabels() {
	showLabels = !showLabels;

	redraw();
}

// zoom to show all features with some padding
function zoomToFeatures(immediately = false) {
	if (vectorSource.getFeatures().length > 0) {
		view.fit(vectorSource.getExtent(), {
			padding: ZOOM_PADDING,
			duration: immediately ? 0 : ZOOM_ANIMATION_DURATION,
		});
	}
}

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

// map opacity switcher
function setMapOpacity() {
	if ($('#mtl-opacity-low').is(':checked')) $('#mtl-map').removeClass('full-opacity');
	else $('#mtl-map').addClass('full-opacity');
}

// map color mode switcher
function setMapColors() {
	if ($('#mtl-colored-map').is(':checked')) $('#mtl-map').addClass('colored-map');
	else $('#mtl-map').removeClass('colored-map');
}

function setTitle(newTitle) {
	$('title').html(newTitle);
	$('h1.entry-title').html(newTitle);
}
