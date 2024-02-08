/**
 * (C) by Jan Garloff and Johannes Bouchain - stadtkreation.de
 */

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

const attributionLayer = new ol.layer.Layer({
	source: new ol.source.Source({attributions: objectL10n.attributionIcons}),
	render: function () { return null; }
});
map.addLayer(attributionLayer);

// global so we can remove them later
let drawInteraction;
const modifyInteraction = new ol.interaction.Modify({ source: vectorSource });
const dragBoxInteraction = new ol.interaction.DragBox();
const selectInteraction = new ol.interaction.Select({ layers: [vectorLayer], multi: true, style: selectedStyleFunction });
const snapInteraction = new ol.interaction.Snap({ source: vectorSource });

const selectedFeatures = selectInteraction.getFeatures();

const interactionControl = new InteractionControl();
map.addControl(interactionControl);

window.addEventListener("load", addSaveEventListeners);
window.addEventListener("load", addTagInputEventListeners);

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

$('#title, #description').on('input propertychange paste', function() {
    warningMessage = objectL10n.confirmLeaveWebsite;
});
$('input.cat-select').change(function() {
    warningMessage = objectL10n.confirmLeaveWebsite;
});

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

		// if the view is not obliquely rotated the box geometry and
		// its extent are equalivalent so intersecting features can
		// be added directly to the collection
		const rotation = map.getView().getRotation();
		const oblique = rotation % (Math.PI / 2) !== 0;

		// when the view is obliquely rotated the box extent will
		// exceed its geometry so both the box and the candidate
		// feature geometries are rotated around a common anchor
		// to confirm that, with the box geometry aligned with its
		// extent, the geometries intersect
		if (oblique) {
			const anchor = [0, 0];
			const geometry = dragBoxInteraction.getGeometry().clone();
			geometry.translate(-world * worldWidth, 0);
			geometry.rotate(-rotation, anchor);
			const extent = geometry.getExtent();
			boxFeatures.forEach(function (feature) {
				const geometry = feature.getGeometry().clone();
				geometry.rotate(-rotation, anchor);
				if (geometry.intersectsExtent(extent)) {
					selectedFeatures.push(feature);
				}
			});
		} else {
			selectedFeatures.extend(boxFeatures);
		}
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
    vectorSource.on('addfeature', () => {saveToHTML()});
    vectorSource.on('changefeature', () => {saveToHTML()});
    vectorSource.on('removefeature', () => {saveToHTML()});
}

/**
 * Adds event listeners to enable autocompletion for tag input
 */
function addTagInputEventListeners() {
	function split( val ) {
		return val.split( /,\s*/ );
	}
	function extractLast( term ) {
		return split( term ).pop();
	}

	$( "#mtl-tag-input" )
	// don't navigate away from the field on tab when selecting an item
	.on( "keydown", function( event ) {
		if ( event.keyCode === $.ui.keyCode.TAB &&
			$( this ).autocomplete( "instance" ).menu.active ) {
			event.preventDefault();
		}
	})
	.autocomplete({
		minLength: 1,
		source: function( request, response ) {
			// delegate back to autocomplete, but extract the last term
			response( $.ui.autocomplete.filter(
			mtl_tags, extractLast( request.term ) ) );
		},
		focus: function() {
			// prevent value inserted on focus
			return false;
		},
		select: function( event, ui ) {
			var terms = split( this.value );
			// remove the current input
			terms.pop();
			// add the selected item
			terms.push( ui.item.value );
			// add placeholder to get the comma-and-space at the end
			terms.push( "" );
			this.value = terms.join( ", " );
			return false;
		}
	});
}
