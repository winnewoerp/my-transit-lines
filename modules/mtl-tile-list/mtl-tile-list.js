/* My Transit Line posttiles list */

var $ = jQuery;

var mtlPosttilesWidth = 210;
var mtlPosttilesSpace = 20;

var countLoads = 0;

$(document).ready(function(){
	mtlChangePostTilePadding();
});

$( window ).resize(function(){
	mtlChangePostTilePadding();
});

function mtlChangePostTilePadding() {
	if($('.mtl-posttiles-list').length) {
		var el = '.mtl-posttiles-list';
		var elWidth = $(el).innerWidth();
		var tileListWidth = mtlPosttilesWidth+mtlPosttilesSpace;
		while(elWidth>=(tileListWidth+mtlPosttilesWidth+mtlPosttilesSpace)) {
			tileListWidth = tileListWidth+mtlPosttilesWidth+mtlPosttilesSpace;
		}
		var leftPadding = (elWidth-tileListWidth)/2;
		$(el).css('padding-left',leftPadding+'px');
	}
}



// Ajax Pagination
var currentHash = window.location.hash;
var paginationClicked = false;

jQuery(document).ready(function($){
	if(currentHash.replace('#!','')!=currentHash ) loadNewTiles(tilePageUrl+currentHash.replace('#!',''));

	$('.mtl-paginate-links a').on('click', function(e)  {
		e.preventDefault();
		window.location.hash = '!'+$(this).attr('href').replace(tilePageUrl,'');
		
    });
	$(window).on('hashchange',function(){
		if(window.location.href.replace(tilePageUrl,'')!=window.location.href) {
			currentHash = window.location.hash;
			if(currentHash.replace('#!','')!=currentHash && !paginationClicked) loadNewTiles(tilePageUrl+currentHash.replace('#!',''));
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
	if(actionUrl.replace('?','')==actionUrl) var paramSeparator = '?';
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
	if(!countLoads && currentHash.replace('#!','')!=currentHash) {
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
		);
		
		thumbmap[thumbmap.length-1].addLayer( mapLayers[thumbmap.length-1] );

		thumbvectors[thumbmap.length-1] = new OpenLayers.Layer.Vector(objectL10n.vectorLayerTitle, { styleMap: new OpenLayers.StyleMap(style), rendererOptions: { zIndexing: true } });
		thumbmap[thumbmap.length-1].addLayer(thumbvectors[thumbmap.length-1]);
		
		var lonlat = new OpenLayers.LonLat(mtlCenterLon,mtlCenterLat);
		lonlat.transform(proj4326, projmerc);
		
		// center map to Hamburg
		thumbmap[thumbmap.length-1].setCenter(lonlat, 13);
		
		wkt = new OpenLayers.Format.WKT();
		if(vectorData) {
			if(vectorData.replace('POINT','') != vectorData || vectorData.replace('LINESTRING','') != vectorData) {
				features = wkt.read(vectorData);
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
		changeLinetype(thumbvectors[thumbmap.length-1],symbolSize,3);
		
		$('.olLayerGrid').css('opacity','.2');
	}	
}