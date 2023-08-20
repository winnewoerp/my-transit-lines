/* My Transit Line posttiles list */

var $ = jQuery;
var countLoads = 0;

// Ajax Pagination
var currentHash = window.location.hash;
var paginationClicked = false;

jQuery(document).ready(function($){
	if(currentHash.includes('#!')) loadNewTiles(tilePageUrl+currentHash.replace('#!',''));

	$('.mtl-paginate-links a').on('click', function(e)  {
		e.preventDefault();
		window.location.hash = '!'+$(this).attr('href').replace(tilePageUrl,'');
    });
	
	$(window).on('hashchange',function(){
		if(window.location.href.includes(tilePageUrl)) {
			currentHash = window.location.hash;
			if(currentHash.includes('#!') && !paginationClicked) loadNewTiles(tilePageUrl+currentHash.replace('#!',''));
		}
	});
	
	$('#mtl-filter-form').submit(function(e) {
		e.preventDefault();
		submitFilter();
	});
	
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
		
		// remove old content
		$($content_filter,$content_pagination,$content_tiles).html('');
			
		// add new content
		$($content_filter).html($new_content_filter); 
		$($content_pagination).html($new_content_pagination); 
		$($content_tiles).html($new_content_tiles);
		
		$('#mtl-filter-form').submit(function(e) {
			e.preventDefault();
			submitFilter();
		});

		$('.mtl-paginate-links a').on('click', function(e)  {
			e.preventDefault();
			window.location.hash = '!'+$(this).attr('href').replace(tilePageUrl,'');
		});
		
		// add rating
		if($('.mtl-rating-section').length) mtl_rating_section_handler();
		if($('.mtl-tile-rating').length) mtl_tile_rating_handler();
	});
	countLoads++;
	paginationClicked = false;
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

// thumb maps
var thumbmap = new Array();
var thumbvectors = new Array();
var mapLayers = new Array();
var getMapLayers = new Array();
function createThumbMap(mapNumber) {
	var loadThumbmaps = true;
	if(!countLoads && currentHash.includes('#!')) {
		loadThumbmaps = false;
		$('.mtl-post-tile').css('display','none');
	}
	if(loadThumbmaps) {
		$('.mtl-post-tile').css('display','block');
		features = '';
		thumbmap.push(new OpenLayers.Map('thumblist-map'+mapNumber,{controls:[]}));
		var mapLayers = new Array();
		// add OSM OePNV Layer
		mapLayers[thumbmap.length-1] = new OpenLayers.Layer.OSM(
			objectL10n.titleOSM,
			["https://a.tile.openstreetmap.org/${z}/${x}/${y}.png","https://b.tile.openstreetmap.org/${z}/${x}/${y}.png","https://c.tile.openstreetmap.org/${z}/${x}/${y}.png"]);
		
		thumbmap[thumbmap.length-1].addLayer( mapLayers[thumbmap.length-1] );

		thumbvectors[thumbmap.length-1] = new OpenLayers.Layer.Vector(objectL10n.vectorLayerTitle, { styleMap: new OpenLayers.StyleMap(style), rendererOptions: { zIndexing: true } });
		thumbmap[thumbmap.length-1].addLayer(thumbvectors[thumbmap.length-1]);
		
		var lonlat = new OpenLayers.LonLat(mtlCenterLon,mtlCenterLat);
		lonlat.transform(proj4326, projmerc);
		
		// center map to Hamburg
		thumbmap[thumbmap.length-1].setCenter(lonlat, 13);
		
		wkt = new OpenLayers.Format.WKT();
		if(vectorData && vectorData.length > 0) {
			if(vectorData[0].includes('POINT') || vectorData[0].includes('LINESTRING')) {
				features = wkt.read(vectorData[0]);
				if(features.constructor != Array) {
					features = [features];
				}
				countFeatures = features.length;
				thumbvectors[thumbmap.length-1].addFeatures(features);
				for(var i =0; i < thumbvectors[thumbmap.length-1].features.length; i++) thumbvectors[thumbmap.length-1].features[i].geometry.transform(proj4326,projmerc);
				zoomToFeatures(thumbmap[thumbmap.length-1],thumbvectors[thumbmap.length-1]);
			}
		}
		fillColor = transportModeStyleData[currentCat][0];
		strokeColor = transportModeStyleData[currentCat][0];
		externalGraphicUrl = transportModeStyleData[currentCat][1];
		externalGraphicUrlSelected = transportModeStyleData[currentCat][2];
		if(countFeatures>2) var symbolSize = 13;
		else var symbolSize = 20;
		changeLinetypeTileList(thumbvectors[thumbmap.length-1],symbolSize,3);
		
		$('.olLayerGrid').css('opacity','.2');
	}	
}