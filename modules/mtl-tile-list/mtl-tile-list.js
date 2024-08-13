/* My Transit Line posttiles list */

var $ = jQuery;
var countLoads = 0;

// Ajax Pagination
var currentHash = window.location.hash;
var paginationClicked = false;

jQuery(document).ready(function($){
	if(currentHash.includes('#!')) loadNewTiles(tilePageUrl+currentHash.replace('#!',''));
	
	$(window).on('hashchange',function(){
		if(window.location.href.includes(tilePageUrl)) {
			currentHash = window.location.hash;
			if(currentHash.includes('#!') && !paginationClicked) loadNewTiles(tilePageUrl+currentHash.replace('#!',''));
		}
	});

	set_button_behaviour();
	
}); // end ready function

function loadNewTiles(link) {
	$('.mtl-posttiles-list').prepend("<div class=\"mtl-list-loader\">"+loadingNewProposalsText+"</div>");
	$('.mtl-posttiles-list').prepend("<div class=\"mtl-list-loader bottom\">"+loadingNewProposalsText+"</div>");

	var $content_pagination = '.mtl-paginate-links';
	var $content_tiles = '.mtl-posttiles-list';

	$.get(link+'', function(data){
		var $new_content_pagination = $($content_pagination, data).wrapInner('').html(); // Grab just the pagination content
		var $new_content_tiles = $($content_tiles, data).wrapInner('').html(); // Grab just the tile content
		$('.mtl-paginate-links .loader').remove();
		
		// add new content
		$($content_pagination).html($new_content_pagination);
		$($content_tiles).html($new_content_tiles);

		window.dispatchEvent(new Event('new-filter'));

		set_button_behaviour();
		
		for (var key of Object.keys(catList)) {
			createThumbMap(key);
		}
		
		// add rating
		if($('.mtl-rating-section').length) mtl_rating_section_handler();
		if($('.mtl-tile-rating').length) mtl_tile_rating_handler();
	});
	countLoads++;
	paginationClicked = false;
}

function set_button_behaviour() {
	$('#mtl-filter-form').submit(function(e) {
		e.preventDefault();
		submitFilter();
	});

	$('.mtl-paginate-links a').on('click', function(e)  {
		e.preventDefault();
		window.location.hash = '!'+$(this).attr('href').replace(tilePageUrl,'');
	});

	$('#mtl-post-map-link').attr('href', post_map_url + currentHash);
}

// submit filter
function submitFilter() {
	const form = $('#mtl-filter-form');
	const actionUrl = form.attr('action');
	let paramSeparator = actionUrl.includes('?') ? '&' : '?';
	
	const formInputs = $(form).find(':input');
	let allParams = '';
	formInputs.each(function() {
		if ($(this).attr('name')) {
			allParams += paramSeparator+$(this).attr('name')+'='+$(this).val();
			paramSeparator = '&';
		}
	});

	const newHash = (actionUrl+allParams).replace(tilePageUrl,'');
	window.location.hash  = '!'+newHash;
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
	if(!countLoads && currentHash.includes('#!')) {
		$('.mtl-post-tile').css('display','none');
	} else {
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
}

for (var key of Object.keys(catList)) {
	createThumbMap(key);
}
