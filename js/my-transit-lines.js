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
const ZOOM_PADDING = [50, 50, 50, 50]; // TODO should depend on screen size?
const DELETE_INTERACTION_UNSUPPORTED_OPACITY = 0.5;
const MAP_ID = 'mtl-map';

var centerLon;
var centerLat;
var standardZoom;
var showLabels = true;
var fullscreen = false;
var selectedFeatureIndex = -1;

class DrawTypeSelectorControl extends ol.control.Control {
	/**
	 * @param {Object} [opt_options] Control options.
	 */
	constructor(opt_options) {
		const options = opt_options || {};

		const element = document.createElement('div');
		element.className = 'draw-type-selector ol-control';

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
		this.deleteButton.style.opacity = 0.4;
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
		button.style.backgroundImage = 'url(' + path + ')';
		button.style.backgroundPosition = 'center';
		button.style.backgroundRepeat = 'no-repeat';
		button.value = value;

		button.addEventListener('click', this.handleClick.bind(this), false);

		return button;
	}

	handleClick(event) {
		var target = event.target;

		if (target != this.deleteButton) {
			for (const node of target.parentElement.childNodes) {
				node.style.backgroundColor = 'white';
			}

			target.style.backgroundColor = 'gray';
		} else {
			handleDelete();
			return;
		}

		setInteraction(target.value);
	}
}

const OSM_SOURCE = new ol.source.OSM();
const OEPNVKARTE_SOURCE = new ol.source.OSM({
	url: 'https://tile.memomaps.de/tilegen/{z}/{x}/{y}.png',
	attributions: objectL10n.attributionOPNV,
	maxZoom: MAX_ZOOM_OEPNV_MAP,
});
const OPENTOPOMAP_SOURCE = new ol.source.OSM({
	url: 'https://tile.opentopomap.org/{z}/{x}/{y}.png',
	attributions: objectL10n.attributionOpentopomap,
	maxZoom: MAX_ZOOM_OPENTOPO_MAP,
});
const ESRI_SOURCE = new ol.source.OSM({
	url: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}.png',
	attributions: objectL10n.attributionESRISatellite,
})
const OPENRAILYWAYMAP_STANDARD_SOURCE = new ol.source.OSM({
	url: 'https://tiles.openrailwaymap.org/standard/{z}/{x}/{y}.png',
	attributions: objectL10n.attributionOpenrailwaymap,
	opaque: false,
});
const OPENRAILYWAYMAP_MAX_SPEED_SOURCE = new ol.source.OSM({
	url: 'https://tiles.openrailwaymap.org/maxspeed/{z}/{x}/{y}.png',
	attributions: objectL10n.attributionOpenrailwaymapMaxspeed,
	opaque: false,
});
const OPENRAILWAYMAP_ELECTRIFICATION_SOURCE = new ol.source.OSM({
	url: 'https://tiles.openrailwaymap.org/electrified/{z}/{x}/{y}.png',
	attributions: objectL10n.attributionOpenrailwaymapElectrified,
	opaque: false,
});
const OPENRAILWAYMAP_SIGNALS_SOURCE = new ol.source.OSM({
	url: 'https://tiles.openrailwaymap.org/signals/{z}/{x}/{y}.png',
	attributions: objectL10n.attributionOpenrailwaymapSignals,
	opaque: false,
});
const OPENRAILWAYMAP_GAUGE_SOURCE = new ol.source.OSM({
	url: 'https://tiles.openrailwaymap.org/gauge/{z}/{x}/{y}.png',
	attributions: objectL10n.attributionOpenrailwaymapGauge,
	opaque: false,
});

const backgroundTileLayer = new ol.layer.Tile({
	className: 'background-tilelayer',
	source: OSM_SOURCE,
});
const overlayTileLayer = new ol.layer.Tile({
	className: 'overlay-tilelayer',
	source: OPENRAILYWAYMAP_MAX_SPEED_SOURCE,
});

const vectorSource = new ol.source.Vector();
const vectorLayer = new ol.layer.Vector({ // TODO use VectorImage for multiple proposal view
	source: vectorSource,
	style: styleFunction,
});

// returns the style for the given feature
function styleFunction(feature) {
	const colorUnselected = transportModeStyleData[getCategoryOf(feature)][0];
	const notSelected = selectedFeatures.getArray().indexOf(feature) < 0;

	return new ol.style.Style({
		fill: new ol.style.Fill({
			color: notSelected ? (colorUnselected + '40') : (COLOR_SELECTED + '4'),
		}),
		image: new ol.style.Icon({
			src: transportModeStyleData[getCategoryOf(feature)][notSelected ? 1 : 2],
			width: notSelected ? ICON_SIZE_UNSELECTED : ICON_SIZE_SELECTED,
			height: notSelected ? ICON_SIZE_UNSELECTED : ICON_SIZE_UNSELECTED,
		}),
		stroke: new ol.style.Stroke({
			color: notSelected ? colorUnselected : COLOR_SELECTED,
			width: notSelected ? STROKE_WIDTH_UNSELECTED : STROKE_WIDTH_SELECTED,
		}),
		text: new ol.style.Text({
			font: 'bold 11px sans-serif',
			text: showLabels ? feature.get('name') : '',
			textAlign: 'left',
			fill: new ol.style.Fill({
				color: 'white',
			}),
			stroke: new ol.style.Stroke({
				color: notSelected ? colorUnselected : COLOR_SELECTED,
				width: notSelected ? STROKE_WIDTH_UNSELECTED : STROKE_WIDTH_SELECTED,
			}),
			offsetX: TEXT_X_OFFSET,
		}),
		zIndex: notSelected ? (feature.getGeometry() instanceof ol.geom.Point ? POINT_UNSELECTED_Z_INDEX : LINE_UNSELECTED_Z_INDEX) : SELECTED_Z_INDEX,
	});
}

/**
 * Get the category of the feature passed to the function.
 * This is the category saved in feature.attributes.category if present of getSelectedCategory() otherwise
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
function handleDelete() {
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
		interactionControl.deleteButton.style.opacity = 1.0;

		$('#feature-textinput').val(event.element.get('name'));
		$('.feature-textinput-box').slideDown();
		$('.set-name').css('display', 'block');
		$('.set-name').click(function () {
			unselectAllFeatures();
		});
		selectedFeatureIndex = vectorSource.getFeatures().indexOf(event.element);
	} else if (event.type = 'remove') {
		if (vectorSource.getFeatures().indexOf(event.element) == selectedFeatureIndex) {
			interactionControl.deleteButton.style.opacity = DELETE_INTERACTION_UNSUPPORTED_OPACITY;

			vectorSource.getFeatures()[selectedFeatureIndex].set('name', $('#feature-textinput').val());
			selectedFeatureIndex = -1;
			$('#feature-textinput').val('');
			$('.feature-textinput-box').slideUp();
			$('.set-name').css('display', 'none');
		}
	}
}

function handleBoxDragEnd() {
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
			map.addInteraction(snapInteraction); // TODO controlled seperately later
			break;
		case 'Select':
			map.addInteraction(dragBoxInteraction);
			map.addInteraction(selectInteraction);
			break;
		case 'Modify':
			map.addInteraction(modifyInteraction);
			map.addInteraction(snapInteraction); // TODO controlled seperately later
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

const view = new ol.View({
	center: ol.proj.fromLonLat([centerLon, centerLat]),
	zoom: standardZoom,
	minZoom: MIN_ZOOM,
	maxZoom: MAX_ZOOM,
});

const interactionControl = new DrawTypeSelectorControl();

const map = new ol.Map({
	controls: [new ol.control.Zoom(), new ol.control.Attribution()].concat(editMode ? [interactionControl] : []),
	layers: [backgroundTileLayer, overlayTileLayer, vectorLayer],
	target: MAP_ID,
	view: view,
});

vectorLayer.on('sourceready', setMapColors);
vectorLayer.on('sourceready', setMapOpacity);

// global so we can remove them later
// TODO only include this when editMode
let drawInteraction;
const modifyInteraction = new ol.interaction.Modify({ source: vectorSource });
const dragBoxInteraction = new ol.interaction.DragBox();
const selectInteraction = new ol.interaction.Select({ layers: [vectorLayer], multi: true, style: styleFunction });
const snapInteraction = new ol.interaction.Snap({ source: vectorSource });

const selectedFeatures = selectInteraction.getFeatures();

dragBoxInteraction.on('boxend', handleBoxDragEnd);

// clear selection when drawing a new box without holding shift
dragBoxInteraction.on('boxstart', function (event) {
	if (!ol.events.condition.shiftKeyOnly(event.mapBrowserEvent))
		selectedFeatures.clear();
});

selectedFeatures.on('add', handleFeatureSelected);
selectedFeatures.on('remove', handleFeatureSelected);

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
