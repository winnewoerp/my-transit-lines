/* My Transit Line posttiles list */

function handle_new_data(new_document) {
	const content_pagination = '.mtl-paginate-links';
	const content_tiles = 'mtl-posttiles-list';

	const old_pagination = document.querySelectorAll(content_pagination);
	const old_tiles = document.getElementById(content_tiles);

	const new_pagination = new_document.querySelectorAll(content_pagination);
	const new_tiles = new_document.getElementById(content_tiles);

	old_pagination.forEach((elem, index) => {
		elem.replaceWith(new_pagination.item(index));
	});
	old_tiles.replaceWith(new_tiles);

	document.getElementById('data-scripts').childNodes.forEach((elem) => {
		if (elem instanceof HTMLScriptElement) {
			eval?.(elem.innerText); // Evaluates eval indirectly in the global scope to update js vars from fetched data. TODO replace with retrieval of JSON only
		}
	});
		
	for (var key of Object.keys(catList)) {
		createThumbMap(key);
	}
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

// thumb maps
function createThumbMap(mapNumber) {
	const currentCat = catList[mapNumber];

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

	const map = new ol.Map({
		controls: [],
		layers: [backgroundTileLayer, vectorLayer],
		target: 'thumblist-map' + mapNumber,
		view: view,
	});

	try {
		if (vectorFeaturesList[mapNumber])
			importToMapJSON(vectorFeaturesList[mapNumber], currentCat, 0, vectorSource);
		else if (vectorDataList[mapNumber])
			importToMapWKT(vectorDataList[mapNumber], [], currentCat, 0, vectorSource);
	} catch (e) {
		console.log(e);
	}

	zoomToFeatures(true, false, vectorSource, view);
	
	$('.olLayerGrid').css('opacity','.2');	
}

for (var key of Object.keys(catList)) {
	createThumbMap(key);
}
