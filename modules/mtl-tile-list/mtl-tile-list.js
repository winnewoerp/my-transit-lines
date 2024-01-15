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

	var $content_filter = '#mtl-list-filter';
	var $content_pagination = '.mtl-paginate-links';
	var $content_tiles = '.mtl-posttiles-list';

	$.get(link+'', function(data){
		var $new_content_filter = $($content_filter, data).wrapInner('').html(); // Grab just the filter content
		var $new_content_pagination = $($content_pagination, data).wrapInner('').html(); // Grab just the pagination content
		var $new_content_tiles = $($content_tiles, data).wrapInner('').html(); // Grab just the tile content
		$('.mtl-paginate-links .loader').remove();
		
		// add new content
		$($content_filter).html($new_content_filter);
		$($content_pagination).html($new_content_pagination);
		$($content_tiles).html($new_content_tiles);

		set_button_behaviour();
		
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
	var form = $('#mtl-filter-form');
	var actionUrl = form.attr('action');
	if(!actionUrl.includes('?')) var paramSeparator = '?';
	else var paramSeparator = '&';
	
	var formInputs = $(form).find(':input');
	var allParams = '';
	formInputs.each(function() {
		if($(this).attr('name')) allParams = allParams+paramSeparator+$(this).attr('name')+'='+$(this).val();
		paramSeparator = '&';
	});

	var newLink = actionUrl+allParams;
	var newHash = newLink.replace(tilePageUrl,'');
	window.location.hash  = '!'+newHash;
}

const ICON_SIZE_TILELIST = 13;
const STROKE_WIDTH_TILELIST = 3;
const MAX_ZOOM_TILELIST = 15;

// thumb maps
function createThumbMap(mapNumber) {
	if(!countLoads && currentHash.includes('#!')) {
		$('.mtl-post-tile').css('display','none');
	} else {
		const currentCat = catList[mapNumber];

		const cat_color = transportModeStyleData[currentCat][0];

		const backgroundTileLayer = new ol.layer.Tile({
			className: 'background-tilelayer',
			source: new ol.source.OSM(),
		});

		const vectorSource = new ol.source.Vector();
		const vectorLayer = new ol.layer.Vector({
			source: vectorSource,
			style: new ol.style.Style({
				fill: new ol.style.Fill({
					color: cat_color + '40',
				}),
				image: new ol.style.Icon({
					src: transportModeStyleData[currentCat][1],
					width: ICON_SIZE_TILELIST,
					height: ICON_SIZE_TILELIST,
				}),
				stroke: new ol.style.Stroke({
					color: cat_color,
					width: STROKE_WIDTH_TILELIST,
				}),
			}),
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

		importToMapWKT(vectorDataList[mapNumber], [], currentCat, 0, vectorSource);

		zoomToFeatures(true, false, vectorSource, view);
		
		$('.olLayerGrid').css('opacity','.2');
	}	
}

for (var key of Object.keys(catList)) {
	createThumbMap(key);
}
