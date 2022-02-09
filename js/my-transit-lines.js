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
var label = '';
var wkt;
var warningMessage = '';
var bahnTyp;
var stationSelected = -1;
var viewFullscreen = false;
var countFeatures = 0;
var currentCat;
var countStations;
var lineLength;
if(typeof OpenLayers != 'undefined') var proj4326 = new OpenLayers.Projection("EPSG:4326");
if(typeof OpenLayers != 'undefined') var projmerc = new OpenLayers.Projection("EPSG:900913");
var newLabelCollection = [];
var mtlCenterLon = 0;
var mtlCenterLat = 0;
var initMap = true;

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
	if(!$('#mtl-post-form').length) mapLayers.push(new OpenLayers.Layer.OSM(objectL10n.titleOSM));
	
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
	if($('#mtl-post-form').length) mapLayers.push(new OpenLayers.Layer.OSM(objectL10n.titleOSM));
	
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
	
	// add Openrailwaymap
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
	
	
	
	
	// add OSM Mapnik Layer
	if($('#mtl-post-form').length) {
		if($('#mtl-colored-map').is(':checked')) $('#mtl-map').addClass('colored-map');
		
	}
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
	
	// center map to Hamburg
	map.setCenter(lonlat, 13);
	map.addControl(new OpenLayers.Control.ScaleLine({bottomOutUnits: '',maxWidth: 200, geodesic: true}));
	
	// load vector data from WKT string
	wkt = new OpenLayers.Format.WKT();
	
	if(vectorData) {
		if(vectorData.replace('POINT','') != vectorData || vectorData.replace('LINESTRING','') != vectorData) {
			features = wkt.read(vectorData);
			if(features.constructor != Array) {
				features = [features];
			}
			countFeatures = features.length;
			vectors.addFeatures(features);
			for(var i =0; i < vectors.features.length; i++) vectors.features[i].geometry.transform(proj4326,projmerc);
			zoomToFeatures();
			$('#mtl-box').append('<p id="zoomtofeatures" class="alignright" style="margin-top:-12px"><a href="javascript:zoomToFeatures()">'+objectL10n.fitToMap+'</a></p>');
			$('#features-data').val(vectorData);
		}
	}
	
	// load labels data from separate field
	if(vectorLabelsData) {
		var vectorLabelsArray = vectorLabelsData.split(',');
		for(var i=0; i<vectorLabelsArray.length; i++) {
			var labelText = vectorLabelsArray[i];
			labelText = labelText.replace(/&#44;/g,',');
			labelText = labelText.replace(/&quot;/g,'"');
			labelText = labelText.replace(/&apos;/g,'\'');
			if(vectors.features[i]) vectors.features[i].attributes = { name: labelText };
		}
		$('#features-label-data').val(vectorLabelsData);
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

function changeLinetype(vectorsLayer,iconSize,lineWidth) {
	if(!lineWidth) lineWidth = strokeWidth;
	if(!iconSize) {
		var currentGraphicHeightUnselected = graphicHeightUnselected;
		var currentGraphicWidthUnselected = graphicWidthUnselected;
	}
	else {
		var currentGraphicHeightUnselected = iconSize;
		var currentGraphicWidthUnselected = iconSize;
	}
	if(!vectorsLayer) vectorsLayer = vectors;
	// set style for selected transport mode
	var selectedTransportMode = $('.cat-select:checked').val();
	if(selectedTransportMode || parseInt(currentCat)) {
		if(currentCat) selectedTransportMode = currentCat;
		fillColor = transportModeStyleData[selectedTransportMode][0];
		strokeColor = transportModeStyleData[selectedTransportMode][0];
		externalGraphicUrl = transportModeStyleData[selectedTransportMode][1];
		externalGraphicUrlSelected = transportModeStyleData[selectedTransportMode][2];
	}
	
	// redraw all features on vector layer using the selected style
	for(var i =0; i < vectorsLayer.features.length; i++) {
		var featureString = vectorsLayer.features[i].geometry.toString();
		var currentFeatureName = '';
		if(vectorsLayer.features[i].attributes.name) {
			currentFeatureName = vectorsLayer.features[i].attributes.name.replace(/&#44;/g,',');
			currentFeatureName = currentFeatureName.replace(/&quot;/g,'"');
			currentFeatureName = currentFeatureName.replace(/&apos;/g,'\'');
		}
		if(featureString.replace('POINT','')!=featureString) vectorsLayer.features[i].style = {
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
	vectorsLayer.redraw();
}

// create event handlers
function vectorsEvents() {
	vectors.events.on({
		'featureadded': function() { updateFeaturesData('added') },
		'featuremodified': function() { updateFeaturesData('modified') },
		'featureremoved': function() { updateFeaturesData('removed') },
		'featureselected': function() { updateFeaturesData('selected') },
		'featureunselected': function() { updateFeaturesData('unselected') },
		'beforefeatureremoved': function() { updateFeaturesData('beforeremoved') },
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
	$('#feature-textinput').val('');
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
	$('.olEditorControlDrawPointItemInactive, .olEditorControlDrawPathItemInactive, .olControlModifyFeatureItemInactive, .olControlNavigationItemInactive').click(function(){
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

// update features added/modified/selected/unselected
function updateFeaturesData(changeType) {
	var featuresData = [];
	var featuresLabelData = [];
	if(changeType =='added' || changeType =='modified' || changeType =='removed') warningMessage = 'Seite wirklich verlassen?';;
	if(vectors.features[vectors.features.length-1]) var featureString = vectors.features[vectors.features.length-1].geometry.toString();
	
	// set label for new point feature
	if(changeType =='added' && featureString.replace('POINT','')!=featureString && $('#feature-textinput').val()!='') {
		var labelText = $('#feature-textinput').val();
		vectors.features[vectors.features.length-1].attributes = { name: labelText };
	}
	
	// set label for updated point feature
	if(changeType == 'unselected' && stationSelected>=0) {
		var labelText = $('#feature-textinput').val();
		if(vectors.features[stationSelected])  vectors.features[stationSelected].attributes = { name: labelText };
		stationSelected = -1;
		$('.feature-textinput-box').slideUp();
		$('#feature-textinput').val('');
		$('.set-name').css('display','none');
	}
	
	if(changeType =='added') {
		countFeatures++;
		var featureString = vectors.features[vectors.features.length-1].geometry.toString();
		if(featureString.replace('POINT','')!=featureString) $('#feature-textinput').val('');
	}

	countStations=0;
	lineLength=0;
	
	// redefine styles of all features
	for(var i =0; i < vectors.features.length; i++) {
		
		var featureString = vectors.features[i].geometry.toString();
		if(vectors.selectedFeatures.indexOf(vectors.features[i])==0) {
			
			// if a point feature has been selected: open text entry box
			if(featureString.replace('POINT','')!=featureString && changeType == 'selected') {
				$('#feature-textinput').val(vectors.features[i].attributes.name);
				$('.feature-textinput-box').slideDown();
				$('.set-name').css('display','block');
				$('.set-name').click(function(){
					unselectAllFeatures();
				});
				stationSelected = i;
			}
			
			// if a feature is selected: set 'selected' styles
			if(($('.olControlSelectFeatureItemActive').length || $('.olEditorControlDragFeatureItemActive').length) && changeType != 'unselected') {
				if(featureString.replace('POINT','')!=featureString) {
					vectors.features[i].style = {
						externalGraphic: externalGraphicUrlSelected,
						graphicHeight: graphicHeightSelected,
						graphicWidth: graphicWidthSelected,
						graphicZIndex: graphicZIndexSelected,
						label: vectors.features[i].attributes.name,
						fontColor: 'white',
						fontSize: "11px",
						fontWeight: "bold",
						labelAlign: "lc",
						labelXOffset: 20,
						labelYOffset: 0,
						labelOutlineColor: '#07f',
						labelOutlineWidth: 5
					}
					countStations++;
				}
				else {
					vectors.features[i].style = {
						fillColor: '#07f',
						strokeColor: '#037',
						strokeWidth: 3,
						graphicZIndex: graphicZIndexSelected
					}
				}
			}
		}
		else {
		
			// set styles for unselected features
			if(featureString.replace('POINT','')!=featureString) {
				if(!$('.olControlModifyFeatureItemActive').length) {
					vectors.features[i].style = {
						externalGraphic: externalGraphicUrl,
						graphicHeight: graphicHeightUnselected,
						graphicWidth: graphicWidthUnselected,
						graphicZIndex: graphicZIndexUnselectedPoint,
						label: vectors.features[i].attributes.name,
						fontColor: "white",
						fontSize: "11px",
						fontWeight: "bold",
						labelAlign: "lc",
						labelXOffset: 20,
						labelYOffset: 0,
						labelOutlineColor: fillColor,
						labelOutlineWidth: 5
					}
				}
				countStations++;
			}
			else {
				vectors.features[i].style = {
					fillColor: fillColor,
					strokeColor: strokeColor,
					strokeWidth: strokeWidth,
					graphicZIndex: graphicZIndexUnselectedLine
				}
			}
		}
		
		var transformedFeature = vectors.features[i].geometry.transform(projmerc,proj4326);
		if(featureString.replace('LINESTRING','')!=featureString) lineLength = lineLength + transformedFeature.getGeodesicLength();
		
		// write all features data to array as WKT
		if(i < countFeatures) featuresData.push(transformedFeature.toString());
		
		// write all features label data to array
		// replace commas, quotes, and apostrophes 
		if(i < countFeatures) {
			var modFeaturesLabelData;
			modFeaturesLabelData = '';
			if(vectors.features[i].attributes.name) {
				modFeaturesLabelData = vectors.features[i].attributes.name.replace(/,/g,'&#44;');
				modFeaturesLabelData = modFeaturesLabelData.replace(/"/g,'&quot;');
				modFeaturesLabelData = modFeaturesLabelData.replace(/'/g,'&apos;');
			}
			featuresLabelData.push(modFeaturesLabelData);
		}
		
		vectors.features[i].geometry.transform(proj4326,projmerc);
	}
	
	// write WKT features data to html element (will be saved to database on form submit)
	var collection = 'GEOMETRYCOLLECTION('+featuresData+')';
	var labelCollection = featuresLabelData.join();
	$('#mtl-feature-data').val(collection);
	$('#mtl-feature-labels-data').val(labelCollection);
	$('#mtl-count-stations').val(countStations);
	$('#mtl-line-length').val(lineLength);
	
	if(changeType == 'added' || changeType == 'modified') lastFeatureLabels = labelCollection
	
	// only redraw vectors when 'modify' tool not selected (prevent overwriting of styles for feature modification)
	if(!$('.olControlModifyFeatureItemActive').length) vectors.redraw();
}

// unselect vector features and write new label, when point feature was selected
function unselectAllFeatures() {
	var newCtrl = new OpenLayers.Control.SelectFeature(vectors);
	map.addControl(newCtrl);
	newCtrl.activate();
	newCtrl.unselectAll();
	newCtrl.destroy();
	if(stationSelected>=0) {
		var labelText = $('#feature-textinput').val();
		vectors.features[stationSelected].attributes = { name: labelText };
		stationSelected = -1;
		$('.feature-textinput-box').slideUp();
		$('#feature-textinput').val('');
	}
	updateFeaturesData('unselected');
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
	$('.category-checkbox:not(:checked)').parent().parent().next().css('display','none');
	$('.category-checkbox:not(:checked)').parent().parent().next().next().css('display','none');
	$('.category-checkbox:not(:checked)').parent().parent().next().next().next().css('display','none');
	$('.category-checkbox').change(function(){
		$('.category-checkbox:not(:checked)').parent().parent().next().css('display','none');
		$('.category-checkbox:not(:checked)').parent().parent().next().next().css('display','none');
		$('.category-checkbox:not(:checked)').parent().parent().next().next().next().css('display','none');
		$('.category-checkbox:checked').parent().parent().next().css('display','table-row');
		$('.category-checkbox:checked').parent().parent().next().next().css('display','table-row');
		$('.category-checkbox:checked').parent().parent().next().next().next().css('display','table-row');
	});
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
	
	// get current center position as marker if exists
	if(mapCenterLon != '' && mapCenterLat != '') {
		var lonLat = new OpenLayers.LonLat(mapCenterLon,mapCenterLat).transform(proj4326,projmerc);
		admin_map.setCenter (lonLat, 12);
	}
	  
	markers = new OpenLayers.Layer.Markers( "Markers" );
    admin_map.addLayer(markers);
	  
	if(mapCenterLon != '' && mapCenterLat != '') markers.addMarker(new OpenLayers.Marker(lonLat));
	
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
 
 
}

function changeMapMarker() {
	var proj4326 = new OpenLayers.Projection("EPSG:4326");
	var projmerc = new OpenLayers.Projection("EPSG:900913");
	markers.clearMarkers();
	var currentLon = parseFloat($('input#mtl-center-lon').val());
	var currentLat = parseFloat($('input#mtl-center-lat').val());
	var pos = new OpenLayers.LonLat(currentLon,currentLat);
	pos.transform(proj4326,projmerc);
	marker = new OpenLayers.Marker(pos);
	markers.addMarker(marker);
	admin_map.setCenter(pos);
	pos.transform(projmerc,proj4326);
	$('#mtl-center-lon').val(pos.lon);
	$('#mtl-center-lat').val(pos.lat);
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

