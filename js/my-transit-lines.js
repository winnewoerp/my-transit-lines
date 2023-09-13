/* my-transit-lines.js
(c) by Johannes Bouchain - stadtkreation.de
*/

// define variables
var $ = jQuery;
var map, editor, style;
var fillColor;
var strokeColor;
var strokeWidth = 4;
var graphicHeightUnselected = 21;
var graphicWidthUnselected = 21;
var graphicHeightSelected = 23;
var graphicWidthSelected = 23;
var graphicZIndexUnselectedLine = 0;
var graphicZIndexUnselectedPoint = 1;
var graphicZIndexSelected = 5;
if(typeof themeUrl != 'undefined') var externalGraphicUrl = '';
if(typeof themeUrl != 'undefined') var externalGraphicUrlSelected = '';
const WKT_FORMAT = new OpenLayers.Format.WKT();
var warningMessage = '';
var defaultCategory;
var stationSelected = -1;
var anythingSelected = false;
var viewFullscreen = false;
var countFeatures = 0;
var currentCat;
if(typeof OpenLayers != 'undefined') var proj4326 = new OpenLayers.Projection("EPSG:4326");
if(typeof OpenLayers != 'undefined') var projmerc = new OpenLayers.Projection("EPSG:900913");
var mtlCenterLon = 0;
var mtlCenterLat = 0;
var mtlStandardZoom = 0;
var initMap = true;
var importFilename = '';

//Notify the user when about to leave page without saving changes
$(window).bind('beforeunload', function() {
	if (warningMessage != '') return warningMessage;
});

// Initiate map and load contents
$(document).ready(function(){
	//actions for frontend
	if($('#mtl-map').length && initMap) initMyTransitLines();
	
	//actions for backend
	else {
		initMyTransitLinesDashboard();
		if($('.mtl-color-picker-field').length) $('.mtl-color-picker-field').wpColorPicker();
		if($('body.options-discussion-php #comments_notify').length) $('body.options-discussion-php #comments_notify').parents('tr').remove();
	}
	
	if(typeof suggestUrl != 'undefined') $("#mtl-tag-select").suggest(suggestUrl,{multiple:true, multipleSep: ","});
});

function initMyTransitLines() {
	OpenLayers.Lang.setCode('de');
	
	// define map div	
	map = new OpenLayers.Map('mtl-map');
	
	var mapLayers = new Array();
	
	// add OSM Mapnik Layer as first layer except for proposal map
	if(!$('#mtl-post-form').length) mapLayers.push(new OpenLayers.Layer.OSM(
		objectL10n.titleOSM,
		["https://a.tile.openstreetmap.org/${z}/${x}/${y}.png","https://b.tile.openstreetmap.org/${z}/${x}/${y}.png","https://c.tile.openstreetmap.org/${z}/${x}/${y}.png"]));
	
	// Create OePNV-Karte map layer
	// add OSM OePNV Layer
	mapLayers.push(new OpenLayers.Layer.OSM(
		objectL10n.titleOPNV,
		"https://tile.memomaps.de/tilegen/${z}/${x}/${y}.png",
		{
			numZoomLevels: 19,
			displayInLayerSwitcher: true,
			buffer: 0,
			tileOptions: {
				crossOriginKeyword: null
			},
			attribution: objectL10n.attributionOPNV,
			keyname: 'oepnvde',
		}
	));
	
	// add OSM Mapnik Layer for proposal map
	if($('#mtl-post-form').length) mapLayers.push(new OpenLayers.Layer.OSM(
		objectL10n.titleOSM,
		["https://a.tile.openstreetmap.org/${z}/${x}/${y}.png","https://b.tile.openstreetmap.org/${z}/${x}/${y}.png","https://c.tile.openstreetmap.org/${z}/${x}/${y}.png"]));
	
	// add Opentopomap
	mapLayers.push(new OpenLayers.Layer.OSM(
		objectL10n.titleOpentopomap,
		["https://a.tile.opentopomap.org/${z}/${x}/${y}.png","https://b.tile.opentopomap.org/${z}/${x}/${y}.png","https://c.tile.opentopomap.org/${z}/${x}/${y}.png"],
		{
			numZoomLevels: 19,
			displayInLayerSwitcher: true,
			buffer: 0,
			tileOptions: {
				crossOriginKeyword: null
			},
			attribution: objectL10n.attributionOpentopomap,
			keyname: 'opentopomap',
		}
	));
	
	// add ESRI satellite images layer
	mapLayers.push(new OpenLayers.Layer.OSM(
		objectL10n.titleESRISatellite,
		["https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/${z}/${y}/${x}.png"],
		{
			numZoomLevels: 19,
			displayInLayerSwitcher: true,
			buffer: 0,
			tileOptions: {
				crossOriginKeyword: null
			},
			attribution: objectL10n.attributionESRISatellite,
			keyname: 'esrisatellite',
		}
	));
	
	// add Openrailwaymap standard
	mapLayers.push(new OpenLayers.Layer.OSM(
		objectL10n.titleOpenrailwaymap,
		["https://a.tiles.openrailwaymap.org/standard/${z}/${x}/${y}.png","https://b.tiles.openrailwaymap.org/standard/${z}/${x}/${y}.png","https://c.tiles.openrailwaymap.org/standard/${z}/${x}/${y}.png"],
		{
			numZoomLevels: 19,
			displayInLayerSwitcher: true,
			buffer: 0,
			tileOptions: {
				crossOriginKeyword: null
			},
			attribution: objectL10n.attributionOpenrailwaymap,
			keyname: 'openrailwaymap',
			opacity: 1,
			noOpaq: true,
			isBaseLayer: false,
			visibility: false,
		}
	));
	
	// add Openrailwaymap maxspeed
	mapLayers.push(new OpenLayers.Layer.OSM(
		objectL10n.titleOpenrailwaymapMaxspeed,
		["https://a.tiles.openrailwaymap.org/maxspeed/${z}/${x}/${y}.png","https://b.tiles.openrailwaymap.org/maxspeed/${z}/${x}/${y}.png","https://c.tiles.openrailwaymap.org/maxspeed/${z}/${x}/${y}.png"],
		{
			numZoomLevels: 19,
			displayInLayerSwitcher: true,
			buffer: 0,
			tileOptions: {
				crossOriginKeyword: null
			},
			attribution: objectL10n.attributionOpenrailwaymapMaxspeed,
			keyname: 'openrailwaymap-maxspeed',
			opacity: 1,
			noOpaq: true,
			isBaseLayer: false,
			visibility: false,
		}
	));
	
	//if($('#mtl-post-form').length) {
		if($('#mtl-colored-map').is(':checked')) $('#mtl-map').addClass('colored-map');
		
	//}
	for(var i = 0;i<mapLayers.length;i++) map.addLayer( mapLayers[i] );
	var layerOSM = mapLayers[0];
	map.addControl(new OpenLayers.Control.LayerSwitcher());
	$('.olControlLayerSwitcher .baseLbl').html(objectL10n.baselayersTitle);
	$('.olControlLayerSwitcher .dataLbl').html(objectL10n.overlaysTitle);
	
	// create vectors layer
	vectors = new OpenLayers.Layer.Vector(objectL10n.vectorLayerTitle, { styleMap: new OpenLayers.StyleMap(style), rendererOptions: { zIndexing: true } });
	map.addLayer(vectors);
	
	// projection transformation

	var lonlat = new OpenLayers.LonLat(mtlCenterLon,mtlCenterLat);
	lonlat.transform(proj4326, projmerc);
	
	// center map to the default from the settings
	map.setCenter(lonlat, mtlStandardZoom);
	map.addControl(new OpenLayers.Control.ScaleLine({bottomOutUnits: '', maxWidth: 200, geodesic: true}));

	if (vectorData && vectorLabelsData && vectorCategoriesData) {
		for (var i = 0; i < vectorData.length && i < vectorLabelsData.length && i < vectorCategoriesData.length; i++) {
			if(vectorData[i].includes('POINT') || vectorData[i].includes('LINESTRING')) {
				var features = WKTtoFeatures(vectorData[i]);
				countFeatures += features.length;
				for(var j = 0; j < features.length; j++) features[j].geometry.transform(proj4326,projmerc);

				var vectorLabelsArray = vectorLabelsData[i].split(',');
				for(var j = 0; j < vectorLabelsArray.length; j++) {
					var labelText = decodeSpecialChars(vectorLabelsArray[j]);
					if(features[j]) features[j].attributes.name = labelText;
				}
				$('#mtl-feature-labels-data').val(vectorLabelsData[i]);

				for (var j = 0; j < features.length; j++) {
					features[j].attributes.category = vectorCategoriesData[i];
				}

				vectors.addFeatures(features);
			}
		}
		zoomToFeatures();
		$('#mtl-box').append('<p id="zoomtofeatures" class="alignright" style="margin-top:-12px"><a href="javascript:zoomToFeatures()">'+objectL10n.fitToMap+'</a></p>');
		$('#features-data').val(vectorData[0]);
	}
	
	// GeoJSON import handling
	if($('#mtl-import-geojson').length) {
		document.querySelector('#mtl-import-geojson').addEventListener('change',function(){
			$('#page').append('<div id="still-importing-overlay" style="position:fixed;z-index:2000;top:0;left:0;width:100%;height:100vh;background:rgba(255,255,255,.8);display:table;text-align:center;font-weight:bold;"><div style="display:table-cell;vertical-align:middle">'+objectL10n.importDataBeingProcessed+'</div></div>');
			
			// get file and file name from respective input field
			let file = document.querySelector('input[type="file"]').files[0];
			importFilename = file.name;
			
			// define FileReader variable
			let reader = new FileReader();
			
			let result = '';

			// run respective funciton when file loaded in reader
			reader.onload = (e) => {
				importToMap(e.target.result);
			};
			
			// reader handling
			reader.onerror = (e) => alert(e.target.error.name);
			reader.readAsText(file);
			
		});
	}
	
	// add editing toolbar from "ole" framework, if editMode == true
	if(editMode) {
		editor = new OpenLayers.Editor(map, {
			activeControls: ['Navigation', 'SnappingSettings','Separator','DeleteFeature','DragFeature','SelectFeature', 'Separator', 'ModifyFeature', 'Separator'],
			featureTypes: ['path', 'point'],
			editLayer: vectors
		});
		editor.startEditMode();
	}
		
	// set styles of lines to selected transport mode
	changeLinetype();
	
	// set preferences when map is loaded
	layerOSM.events.register('loadend', layerOSM, setMapOpacity);
	layerOSM.events.register('loadend', layerOSM, setMapColors);
	layerOSM.events.register('loadend', layerOSM, setToolPreferences);
	layerOSM.events.register('loadend', layerOSM, vectorsEvents);
}

function changeLinetype() {
	for (i = 0; i < vectors.features.length; i++) {
		setFeatureStyle(i, vectors.selectedFeatures.includes(vectors.features[i]), getCategoryOf(vectors.features[i]));
	}
	setToolPreferences();
	unselectAllFeatures();
	vectors.redraw();
}

function changeLinetypeTileList(vectorsLayer,iconSize,lineWidth) {
	var currentGraphicHeightUnselected = iconSize;
	var currentGraphicWidthUnselected = iconSize;
	
	if(parseInt(currentCat)) {
		fillColor = transportModeStyleData[currentCat][0];
		strokeColor = transportModeStyleData[currentCat][0];
		externalGraphicUrl = transportModeStyleData[currentCat][1];
		externalGraphicUrlSelected = transportModeStyleData[currentCat][2];
	}
	
	// redraw all features on vector layer using the selected style
	for(var i = 0; i < vectorsLayer.features.length; i++) {
		var featureString = vectorsLayer.features[i].geometry.toString();
		var currentFeatureName = '';
		if(vectorsLayer.features[i].attributes.name) {
			currentFeatureName = decodeSpecialChars(vectorsLayer.features[i].attributes.name);
		}
		if(featureString.includes('POINT')) vectorsLayer.features[i].style = {
			externalGraphic: externalGraphicUrl,
			graphicHeight: currentGraphicHeightUnselected,
			graphicWidth: currentGraphicWidthUnselected,
			graphicZIndex: graphicZIndexUnselectedPoint,
			label: currentFeatureName,
			fontColor: "white",
			fontSize: "15px",
			fontWeight: "normal",
			labelAlign: "lc",
			labelXOffset: 15,
			labelYOffset: 0,
			labelOutlineColor: fillColor,
			labelOutlineWidth: 4
		};			
		else vectorsLayer.features[i].style = {
			fillColor: fillColor,
			strokeColor: strokeColor,
			strokeWidth: lineWidth,
			graphicZIndex: graphicZIndexUnselectedLine,
		}
	}
	setToolPreferences();
	
	// unselecting all features needed to avoid problems when feature styles are changed
	unselectAllFeatures();
	vectorsLayer.redraw();
}

// create event handlers
function vectorsEvents() {
	vectors.events.on({
		'featureadded': onFeatureAdded, // triggered after a feature was added
		'featuremodified': onFeatureModified, // triggered after part of a feature was modified by the modify-tool
		'featureremoved': onFeatureRemoved, // triggered after a feature was removed
		'featureselected': onFeatureSelected, // triggered when selecting a feature with the select-tool
		'featureunselected': onFeatureUnselected, // triggered when unselecting a feature with the select-tool or when automatically being unselected when selecting a different tool or setting a label
		'afterfeaturemodified': function() { saveToHTML(vectors.features); }, // triggered after a feature was moved or after a feature is no longer "selected" by modify-tool
	});
}

// map opacity switcher
function setMapOpacity() {
	if($('#mtl-opacity-low').is(':checked')) $('#mtl-map').removeClass('full-opacity');
	else $('#mtl-map').addClass('full-opacity');
}

// map color mode switcher
function setMapColors() {
	if($('#mtl-colored-map').is(':checked')) $('#mtl-map').addClass('colored-map');
	else $('#mtl-map').removeClass('colored-map');
}

// set additional preferences the editing toolbar
function setToolPreferences() {
	$('.olEditorControlDrawPathItemActive, .olEditorControlDrawPathItemInactive').attr('title',objectL10n.buildLine);
	$('.olEditorControlDrawPointItemActive, .olEditorControlDrawPointItemInactive').attr('title',objectL10n.buildStations);
	$('.olControlModifyFeatureItemActive, .olControlModifyFeatureItemInactive').attr('title',objectL10n.editObjects);
	$('.olControlSelectFeatureItemActive, .olControlSelectFeatureItemInactive').attr('title',objectL10n.selectObjects);
	$('.olEditorControlDragFeatureItemActive, .olEditorControlDragFeatureItemInactive').attr('title',objectL10n.moveObjects);
	$('.olEditorControlDeleteFeatureItemActive, .olEditorControlDeleteFeatureItemInactive').attr('title',objectL10n.deleteObjects);
	$('.olButton').click(function() {
		if($('.olControlModifyFeatureItemActive').length) {
			$('.transport-mode-select-inactive').css('visibility','visible');
			if(!$('.submit-hint').length) $('#submit-box').append('<span class="submit-hint">'+objectL10n.changeToSubmit+'</span>');
		}
		else {
			$('.transport-mode-select-inactive').css('visibility','hidden');
			$('.submit-hint').remove();
		}
	});
	/*$('.olEditorControlDrawPointItemInactive').click(function() {
		$('.feature-textinput-box').slideDown();
		$('.olButton').click(function(e) {
			if(!$(this).hasClass('olEditorControlDrawPointItemActive')) {
				$('.feature-textinput-box').slideUp();
				$('#feature-textinput').val('');
			}
		});
	});*/
	$('.olEditorControlDrawPointItemInactive, .olEditorControlDrawPathItemInactive, .olControlModifyFeatureItemInactive, .olControlNavigationItemInactive, .olEditorControlDragFeatureItemInactive').click(function(){
		unselectAllFeatures();
	});
	$('#feature-textinput').keypress(function(e){
         var k=e.keyCode || e.which;
         if(k==13){
             e.preventDefault();
         }
     });
	 
	// tool usage hints	 
	$('.olEditorControlDrawPointItemInactive').click(function(){
		$('.mtl-tool-hint').css('display','none');
		$('.mtl-tool-hint.point').css('display','inline');
	});
	$('.olEditorControlDrawPathItemInactive').click(function(){
		$('.mtl-tool-hint').css('display','none');
		$('.mtl-tool-hint.line').css('display','inline');
	});
	$('.olControlModifyFeatureItemInactive').click(function(){
		$('.mtl-tool-hint').css('display','none');
		$('.mtl-tool-hint.edit').css('display','inline');
	});
	$('.olControlSelectFeatureItemInactive').click(function(){
		$('.mtl-tool-hint').css('display','none');
		$('.mtl-tool-hint.select').css('display','inline');
	});
	$('.olEditorControlDragFeatureItemInactive').click(function(){
		$('.mtl-tool-hint').css('display','none');
		$('.mtl-tool-hint.move').css('display','inline');
	});
	$('olControlNavigationItemInactive').click(function(){
		$('.mtl-tool-hint').css('display','none');
		$('.mtl-tool-hint.navigate').css('display','inline');
	});
	
	$('.olControlSelectFeatureItemActive, .olControlSelectFeatureItemInactive').css('background-image','url('+themeUrl+'/images/selectFeatureAddName.png)');
	$('#title, #description').on('input propertychange paste',function() {
		warningMessage = 'Seite wirklich verlassen?';
	});
	$('.lap-category input').change(function(){
		warningMessage = 'Seite wirklich verlassen?';
	});
	$('#new_post').submit(function() {
		warningMessage = '';
	});
	$('#post').submit(function() {
		warningMessage = '';
	});
}

/**
 * Event handler for features being added
 */
function onFeatureAdded() {
	warningMessage = 'Seite wirklich verlassen?';

	countFeatures++;

	if(vectors.features[vectors.features.length-1].geometry instanceof OpenLayers.Geometry.Point) {
		var labelText = $('#feature-textinput').val();
		vectors.features[vectors.features.length-1].attributes.name = labelText;

		$('#feature-textinput').val('');
	}

	setFeatureStyle(vectors.features.length - 1, false, getCategoryOf(vectors.features[vectors.features.length - 1]));

	vectors.redraw();

	saveToHTML(vectors.features);
}

/**
 * Event handler for features being removed
 */
function onFeatureRemoved() {
	stationSelected = -1;
	anythingSelected = false;
	$('.feature-textinput-box').slideUp();
	$('#feature-textinput').val('');
	$('.set-name').css('display','none');

	if (editMode) {
		warningMessage = 'Seite wirklich verlassen?';
		saveToHTML(vectors.features);
	}
}

/**
 * Event handler for features being modified
 */
function onFeatureModified() {
	warningMessage = 'Seite wirklich verlassen?';
	saveToHTML(vectors.features);
}

/**
 * Event handler for features being selected
 */
function onFeatureSelected() {
	anythingSelected = true;

	for(var i = 0; i < vectors.selectedFeatures.length; i++) {
		var is_point_feature = vectors.selectedFeatures[i].geometry instanceof OpenLayers.Geometry.Point;
		var realIndex = vectors.features.indexOf(vectors.selectedFeatures[i]);

		if (is_point_feature) {
			// if a point feature has been selected: open text entry box
			$('#feature-textinput').val(vectors.selectedFeatures[i].attributes.name);
			$('.feature-textinput-box').slideDown();
			$('.set-name').css('display','block');
			$('.set-name').click(function(){
				unselectAllFeatures();
			});
			stationSelected = realIndex;
		}
		
		setFeatureStyle(realIndex, true, getCategoryOf(vectors.selectedFeatures[i]));
	}

	vectors.redraw();

	saveToHTML(vectors.features);
}

/**
 * Event handler for features being unselected
 */
function onFeatureUnselected() {
	if (stationSelected < 0 && !anythingSelected)
		return;
	
	if(stationSelected >= 0) {
		var labelText = $('#feature-textinput').val();
		if(vectors.features[stationSelected]) vectors.features[stationSelected].attributes.name = labelText;
		stationSelected = -1;
		$('.feature-textinput-box').slideUp();
		$('#feature-textinput').val('');
		$('.set-name').css('display','none');
	}

	if (vectors.selectedFeatures.length == 0) {
		anythingSelected = false;
	}

	for (i = 0; i < vectors.features.length; i++) {
		setFeatureStyle(i, vectors.selectedFeatures.includes(vectors.features[i]), getCategoryOf(vectors.features[i]));
	}

	vectors.redraw();
	
	saveToHTML(vectors.features);
}

/**
 * Saves the WKT data of the features array passed into the function to the HTML <input> elements.
 * The data is saved to the DB when the user saves the proposal
 * 
 * @param {*} features the array of features to save
 */
function saveToHTML(features) {
	var wkt_strings = featuresToWKT(features);

	// write WKT features data to html element (will be saved to database on form submit)
	$('#mtl-feature-data').val(wkt_strings[0]);
	$('#mtl-feature-labels-data').val(wkt_strings[1]);
	$('#mtl-count-stations').val(getCountStations(features));
	$('#mtl-line-length').val(getLineLength(features));
}

/**
 * Returns the amount of stations in the features array
 * 
 * @param {*} features the array of features to "search" for stations
 * @returns the amount of stations placed on the map
 */
function getCountStations(features) {
	var count = 0;
	for (i = 0; i < features.length; i++) {
		if (features[i].geometry instanceof OpenLayers.Geometry.Point)
			count++;
	}
	return count;
}

/**
 * Returns the combined length of the lines in the features array
 * 
 * @param {*} features the array of features to "search" for lines
 * @returns the combined length of the lines placed on the map
 */
function getLineLength(features) {
	var length = 0.0;
	for (i = 0; i < features.length; i++) {
		if (features[i].geometry instanceof OpenLayers.Geometry.LineString) {
			var transformedFeature = features[i].geometry.transform(projmerc,proj4326);
			length += transformedFeature.getGeodesicLength();

			features[i].geometry.transform(proj4326,projmerc);
		}
	}
	return length;
}

/**
 * Returns an array of two strings: the first one is the geometry data, the second one the label data
 * 
 * @param {*} features 
 * @returns string[]
 */
function featuresToWKT(features) {
	featuresData = [];
	featuresLabelData = [];

	for (var i = 0; i < features.length; i++) {
		var transformedFeature = vectors.features[i].geometry.transform(projmerc,proj4326);
		
		if(i < countFeatures) {
			// write all features data to array as WKT
			featuresData.push(transformedFeature.toString());

			var modFeaturesLabelData = '';
			if(vectors.features[i].attributes.name) {
				modFeaturesLabelData = encodeSpecialChars(vectors.features[i].attributes.name);
			}
			// write all features label data to array
			featuresLabelData.push(modFeaturesLabelData);
		}
		
		vectors.features[i].geometry.transform(proj4326,projmerc);
	}

	return ['GEOMETRYCOLLECTION('+featuresData+')',featuresLabelData.join()];
}

/**
 * Returns the array of features, does nothing else
 * 
 * @param {*} vectorData 
 * @returns features[]
 */
function WKTtoFeatures(vectorData) {
	features = WKT_FORMAT.read(vectorData);
	if(features.constructor != Array) {
		features = [features];
	}
	return features;
}

/**
 * Get the category of the feature passed to the function.
 * This is the category saved in feature.attributes.category if present of getSelectedCategory() otherwise
 * 
 * @param {*} feature the feature to get the category of
 * @returns the category of the feature passed to the function
 */
function getCategoryOf(feature) {
	return feature.attributes.category ? feature.attributes.category : getSelectedCategory();
}

/**
 * @returns the selected category determined by the selected category checkbox, or if none is selected by the 
 */
function getSelectedCategory() {
	var selectedTransportMode = $('.cat-select:checked').val();
	if(!selectedTransportMode && parseInt(currentCat)) {
		selectedTransportMode = currentCat;
	}
	if(!selectedTransportMode)
		selectedTransportMode = defaultCategory;
	return selectedTransportMode;
}

/**
 * Sets the feature style of the specified feature
 * 
 * @param {number} featureIndex which feature to set the style of
 * @param {boolean} selected if the feature is selected
 * @param {number} categoryId which category the feature has
 */
function setFeatureStyle(featureIndex, selected, categoryId) {
	categoryColor = transportModeStyleData[categoryId][0];

	if (vectors.features[featureIndex].geometry instanceof OpenLayers.Geometry.Point) {
		vectors.features[featureIndex].style = {
			externalGraphic: selected ? transportModeStyleData[categoryId][2] : transportModeStyleData[categoryId][1],
			graphicHeight: selected ? graphicHeightSelected : graphicHeightUnselected,
			graphicWidth: selected ? graphicWidthSelected : graphicWidthUnselected,
			graphicZIndex: selected ? graphicZIndexSelected : graphicZIndexUnselectedPoint,
			label: vectors.features[featureIndex].attributes.name,
			fontColor: 'white',
			fontSize: "11px",
			fontWeight: "bold",
			labelAlign: "lc",
			labelXOffset: 20,
			labelYOffset: 0,
			labelOutlineColor: selected ? '#07f' : categoryColor,
			labelOutlineWidth: 5
		}
	} else {
		vectors.features[featureIndex].style = {
			fillColor: selected ? '#07f' : categoryColor,
			strokeColor: selected ? '#037' : transportModeStyleData[categoryId][0],
			strokeWidth: selected ? 3 : strokeWidth,
			graphicZIndex: selected ? graphicZIndexSelected : graphicZIndexUnselectedLine
		}
	}
}

// unselect vector features and write new label, when point feature was selected
function unselectAllFeatures() {
	if (editMode) {
		var newCtrl = new OpenLayers.Control.SelectFeature(vectors);
		map.addControl(newCtrl);
		newCtrl.activate();
		newCtrl.unselectAll();
		newCtrl.destroy();
	}
}

// Deletes all old features (including possible changes made to them) and loads the new features from vectorData, vectorLabelsData and vectorCategoriesData
function loadNewFeatures() {
	map.removeLayer(vectors);
	vectors.destroy();

	vectors = new OpenLayers.Layer.Vector(objectL10n.vectorLayerTitle, { styleMap: new OpenLayers.StyleMap(style), rendererOptions: { zIndexing: true } });
	map.addLayer(vectors);

	if (vectorData && vectorLabelsData && vectorCategoriesData) {
		for (var i = 0; i < vectorData.length && i < vectorLabelsData.length && i < vectorCategoriesData.length; i++) {
			if(vectorData[i].includes('POINT') || vectorData[i].includes('LINESTRING')) {
				var features = WKTtoFeatures(vectorData[i]);
				countFeatures += features.length;
				for(var j = 0; j < features.length; j++) features[j].geometry.transform(proj4326,projmerc);

				var vectorLabelsArray = vectorLabelsData[i].split(',');
				for(var j = 0; j < vectorLabelsArray.length; j++) {
					var labelText = decodeSpecialChars(vectorLabelsArray[j]);
					if(features[j]) features[j].attributes.name = labelText;
				}
				$('#mtl-feature-labels-data').val(vectorLabelsData[i]);

				for (var j = 0; j < features.length; j++) {
					features[j].attributes.category = vectorCategoriesData[i];
				}

				vectors.addFeatures(features);
			}
		}
		zoomToFeatures();
	}
	changeLinetype();
}

// fullscreen map
function mtlFullscreenMap() {
	if(!viewFullscreen) {
		$('#mtl-map-box').addClass('fullscreen');
		$('#mtl-fullscreen-link').addClass('fullscreen');
		$('#mtl-fullscreen-link').addClass('fullscreen');
		$('#mtl-category-select').addClass('fullscreen');
		$('#mtl-color-opacity').addClass('fullscreen');
		$('#mtl-fullscreen-link .fullscreen-open').css('display','block');
		$('#mtl-fullscreen-link .fullscreen-closed').css('display','none');
		viewFullscreen = true;
	}
	else {
		$('#mtl-box').find('.fullscreen').removeClass('fullscreen');
		$('#mtl-fullscreen-link .fullscreen-open').css('display','none');
		$('#mtl-fullscreen-link .fullscreen-closed').css('display','block');
		viewFullscreen = false;		
	}
	map.updateSize();
	$('#mtl-fullscreen-link').blur();
}

// zoom to features shown on map
function zoomToFeatures(mapId,vectorsLayer) {
	if(!mapId) mapId = map;
	if(!vectorsLayer) vectorsLayer = vectors;
	var bounds = vectorsLayer.getDataExtent();
	mapId.zoomToExtent(bounds);
	if(mapId.getZoom()>14) mapId.setCenter(bounds.getCenterLonLat(),14);
	else mapId.setCenter(bounds.getCenterLonLat());
	if($('#zoomtofeatures').length) $('#zoomtofeatures a').blur();
}

// dashboard functions
function initMyTransitLinesDashboard() {
	if($('.mtl-section-hidden-fields').length) $('.mtl-section-hidden-fields').next().css('display','none');
	if(typeof OpenLayers != 'undefined') {
		if($('#mtl-admin-map-center').length) addAdminMapCenter();
		if($('input#mtl-center-lon').length) $('input#mtl-center-lon').on('change propertychange paste',function(){
			changeMapMarker();
		});
		if($('input#mtl-center-lat').length) $('input#mtl-center-lat').on('change propertychange paste',function(){
			changeMapMarker();
		});
		if($('input#mtl-standard-zoom').length) $('input#mtl-standard-zoom').on('change propertychange paste',function(){
			changeMapMarker();
		});
	}
	
	// handle image upload fields
	
	var custom_uploader;
	var url_field;
	var current_button;
	var image_field;
 
    $('.upload_image_button').unbind('click').on('click',function(e) {
 
        e.preventDefault();
		current_button = $(this);
		url_field = $(this).prev();
		if($(this).next().find('img').attr('src')) image_field = $(this).next().find('img');
		
        //If the uploader object has already been created, reopen the dialog
        if (custom_uploader) {
            custom_uploader.open();
            return;
        }
 
        //Extend the wp.media object
        custom_uploader = wp.media.frames.file_frame = wp.media({
            title: 'Choose Image',
            button: {
                text: 'Choose Image'
            },
            multiple: false
        });
 
        //When a file is selected, grab the URL and set it as the text field's value
        custom_uploader.on('select', function() {
            attachment = custom_uploader.state().get('selection').first().toJSON();
            url_field.val(attachment.url);
			if(image_field) image_field.attr('src',attachment.url);
			else current_button.after(' &nbsp; <span style="height:30px;overflow:visible;display:inline-block"><img src="'+attachment.url+'" style="vertical-align:top;margin-top:-3px;max-height:60px" alt="" /></span>');
        });
 
        //Open the uploader dialog
        custom_uploader.open();
 
    });
	
	// hide not used category image and color fields
	$('input.category-checkbox:not(:checked)').parent().parent().next().css('display','none');
	$('input.category-checkbox:not(:checked)').parent().parent().next().next().css('display','none');
	$('input.category-checkbox:not(:checked)').parent().parent().next().next().next().css('display','none');
	$('input.category-checkbox').change(
		function(){
			$('input.category-checkbox:not(:checked)').parent().parent().next().css('display','none');
			$('input.category-checkbox:not(:checked)').parent().parent().next().next().css('display','none');
			$('input.category-checkbox:not(:checked)').parent().parent().next().next().next().css('display','none');
			$('input.category-checkbox:checked').parent().parent().next().css('display','table-row');
			$('input.category-checkbox:checked').parent().parent().next().next().css('display','table-row');
			$('input.category-checkbox:checked').parent().parent().next().next().next().css('display','table-row');
		}
	);
}

// define admin vars
var admin_map, markers

function addAdminMapCenter() {
	var proj4326 = new OpenLayers.Projection("EPSG:4326");
	var projmerc = new OpenLayers.Projection("EPSG:900913");

	$('#mtl-admin-map-center').css({'max-width':'500px','height':'300px'});
	admin_map = new OpenLayers.Map('mtl-admin-map-center');
	var layerOSM = new OpenLayers.Layer.OSM();
	admin_map.addLayer( layerOSM );
	admin_map.setCenter(new OpenLayers.LonLat(0,0).transform(proj4326,projmerc), 1);
	  
	markers = new OpenLayers.Layer.Markers( "Markers" );
    admin_map.addLayer(markers);
	
	// get current center position as marker if exists
	if(mapCenterLon != '' && mapCenterLat != '' && mapStandardZoom != '') {
		var lonLat = new OpenLayers.LonLat(mapCenterLon,mapCenterLat).transform(proj4326,projmerc);
		markers.addMarker(new OpenLayers.Marker(lonLat));
		admin_map.setCenter(lonLat, mapStandardZoom);
	}
	
	admin_map.events.register("click", admin_map, function(evt) {
		markers.clearMarkers();
		var pos = admin_map.getLonLatFromPixel(evt.xy);
		marker = new OpenLayers.Marker(pos);
		markers.addMarker(marker);
		admin_map.setCenter(pos);
		pos.transform(projmerc,proj4326);
		$('#mtl-center-lon').val(pos.lon);
		$('#mtl-center-lat').val(pos.lat);
		
	});

	admin_map.events.register("moveend", admin_map, function(evt) {
		var currentZoom = admin_map.getZoom();
		$('#mtl-standard-zoom').val(currentZoom);
	});
}

function changeMapMarker() {
	var proj4326 = new OpenLayers.Projection("EPSG:4326");
	var projmerc = new OpenLayers.Projection("EPSG:900913");
	markers.clearMarkers();
	var currentLon = parseFloat($('input#mtl-center-lon').val());
	var currentLat = parseFloat($('input#mtl-center-lat').val());
	var currentZoom = parseInt($('input#mtl-standard-zoom').val());
	var pos = new OpenLayers.LonLat(currentLon,currentLat);
	pos.transform(proj4326,projmerc);
	marker = new OpenLayers.Marker(pos);
	markers.addMarker(marker);
	admin_map.setCenter(pos);
	admin_map.zoomTo(currentZoom);
	pos.transform(projmerc,proj4326);
	$('#mtl-center-lon').val(pos.lon);
	$('#mtl-center-lat').val(pos.lat);
	$('#mtl-standard-zoom').val(currentZoom);
}

function manipulateTitle(newTitle) {
	$('title').html(newTitle);
	$('h1.entry-title').html(newTitle);
}

// Proposal contact form
$(document).ready(function(){
	if($('#proposal-author-contact-form').length) {
		$('#proposal-author-contact-form .pacf-toggle').on('click',function(e){
			e.preventDefault();
			$(this).closest('div').find('form').slideToggle();
		});
	}		
});

// check if string can be parsed as JSON
function isJsonParsable(string) {
	try {
		JSON.parse(string);
	} catch (e) {
		return false;
	}
	return true;
}

// import GeoJSON data from file input field, remove parts that can't be used and output in WKT format including the station names string
function importToMap(result) {
	importFilenameParts = importFilename.split('.');
	if(isJsonParsable(result) && importFilenameParts[importFilenameParts.length-1] == 'geojson') {
		let geoJSONFeaturesJSON = JSON.parse(result);
		let newGeoJSON = [];
		let stationNames = '';
		let first = true;
		if(geoJSONFeaturesJSON) {
			for(let i = 0;i<geoJSONFeaturesJSON.features.length;i++) {
				// only use LineString and Point features (other feature types are not allowed in MTL) and write these features to new GeoJSON array
				if(geoJSONFeaturesJSON.features[i].geometry.type=='MultiLineString' || geoJSONFeaturesJSON.features[i].geometry.type=='LineString' || geoJSONFeaturesJSON.features[i].geometry.type=='Point') {
					newGeoJSON.push(geoJSONFeaturesJSON.features[i]);
					
					// add point feature names to respective string if existing
					if(geoJSONFeaturesJSON.features[i].geometry.type=='Point' && geoJSONFeaturesJSON.features[i].properties.name) {
						if(!first) stationNames += ',';
						var labelText = decodeSpecialChars(geoJSONFeaturesJSON.features[i].properties.name);
						stationNames += labelText;
					}
					else {
						if(!first) stationNames += ',';
					}
					first = false;
				}								
			}
			
			// parse GeoJSON to WKT. Selfmade! TODO: Simplify & cleanup.
			let WKTFeatures = 'GEOMETRYCOLLECTION(';
			let firstFeature = true;
			console.log(newGeoJSON.length);
			for(let i = 0; i < newGeoJSON.length; i++) {
				if(!firstFeature) WKTFeatures += ',';
				if(newGeoJSON[i].geometry.type == 'Point') WKTFeatures += 'POINT(' + newGeoJSON[i].geometry.coordinates[0] + ' ' + newGeoJSON[i].geometry.coordinates[1] + ')';
				if(newGeoJSON[i].geometry.type == 'LineString') {
					WKTFeatures += 'LINESTRING(';
					let firstLineStringPart = true;
					for(let j = 0;j < newGeoJSON[i].geometry.coordinates.length;j++) {
						if(!firstLineStringPart) WKTFeatures += ',';
						WKTFeatures += newGeoJSON[i].geometry.coordinates[j][0] + ' ' + newGeoJSON[i].geometry.coordinates[j][1];
						firstLineStringPart = false;
					}
					WKTFeatures += ')';
				}
				if(newGeoJSON[i].geometry.type == 'MultiLineString') {
					WKTFeatures += 'MULTILINESTRING(';
					let firstLineString = true;
					for(let j = 0;j < newGeoJSON[i].geometry.coordinates.length;j++) {
						if(!firstLineString) WKTFeatures += ',';
						WKTFeatures += '(';
						let firstLineStringPart = true;
						for(let k = 0;k < newGeoJSON[i].geometry.coordinates[j].length;k++) {
							if(!firstLineStringPart) WKTFeatures += ',';
							WKTFeatures += newGeoJSON[i].geometry.coordinates[j][k][0] + ' ' + newGeoJSON[i].geometry.coordinates[j][k][1];
							firstLineStringPart = false;
						}
						WKTFeatures += ')';
						firstLineString = false;
					}
					WKTFeatures += ')';
				}
				firstFeature = false;
			}
			WKTFeatures += ')';

			if(WKTFeatures) {
				if(WKTFeatures.includes('POINT') || WKTFeatures.includes('LINESTRING') || WKTFeatures.includes('MULTILINESTRING')) {
					var oldFeatures = vectors.features;
					vectors.removeAllFeatures();
					features = WKT_FORMAT.read(WKTFeatures);
					if(features.constructor != Array) {
						features = [features];
					}
					countFeatures = features.length;
					vectors.addFeatures(features);
					for(var i =0; i < vectors.features.length; i++) vectors.features[i].geometry.transform(proj4326,projmerc);
					
					if(stationNames) {
						var vectorLabelsArray = stationNames.split(',');
						for(var i=0; i<vectorLabelsArray.length; i++) {
							var labelText = decodeSpecialChars(vectorLabelsArray[i]);
							if(vectors.features[i]) vectors.features[i].attributes.name = labelText;
						}
					}
					var newFeatures = vectors.features;
					vectors.removeAllFeatures();
					
					// add all features again in correct order
					vectors.addFeatures(oldFeatures);
					vectors.addFeatures(newFeatures);
					
					// zoom to features layer extent
					zoomToFeatures();
					//$('#mtl-box').append('<p id="zoomtofeatures" class="alignright" style="margin-top:-12px"><a href="javascript:zoomToFeatures()">'+objectL10n.fitToMap+'</a></p>');
					//$('#features-data').val($('#features-data').val()+WKTFeatures);
				}
			}
			$('#still-importing-overlay').remove();
			
			// add imported labels data to respective input field
			//$('#mtl-feature-labels-data').val($('#mtl-feature-labels-data').val()+stationNames);
		}
		else {
			$('#still-importing-overlay').remove();
			alert('no valid features found');
		}
	}
	else {
		$('#still-importing-overlay').remove();
		alert('no json file');
	}
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