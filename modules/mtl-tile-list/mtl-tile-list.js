/* My Transit Line posttiles list */

window.addEventListener('load', createThumbMaps);

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
function createThumbMap(proposal) {
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

	const map = new ol.Map({
		controls: [],
		layers: [backgroundTileLayer, vectorLayer],
		target: 'thumblist-map' + proposal.id,
		view: view,
	});

	try {
		importToMapJSON(proposal.features, currentCat, 0, vectorSource);
	} catch (e) {
		console.log(e);
	}

	zoomToFeatures(true, false, vectorSource, view);
	
	$('.olLayerGrid').css('opacity','.2');	
}

function createThumbMaps() {
	for (let proposal of proposalList) {
		createThumbMap(proposal);
	}
}
