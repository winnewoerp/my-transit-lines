const MAP_ID = 'mtl-admin-map-center';
const MIN_ZOOM = 0;
const MAX_ZOOM = 19;
const MARKER_WIDTH = 30;
const MARKER_HEIGHT = 30;

const BACKGROUND = new ol.layer.Tile({
	source: new ol.source.OSM(),
});

const MARKERS_SOURCE = new ol.source.Vector({
    features: [new ol.Feature(new ol.geom.Point(ol.proj.fromLonLat([mapCenterLon, mapCenterLat])))]
});
const MARKERS = new ol.layer.Vector({
    source: MARKERS_SOURCE,
    style: new ol.style.Style({
        image: new ol.style.Icon({
            src: themeUrl + '/images/map-marker.png',
            width: MARKER_WIDTH,
            height: MARKER_HEIGHT,
        }),
    }),
});

const VIEW = new ol.View({
	center: ol.proj.fromLonLat([mapCenterLon, mapCenterLat]),
	zoom: mapStandardZoom,
	minZoom: MIN_ZOOM,
	maxZoom: MAX_ZOOM,
});

const MAP = new ol.Map({
    controls: [new ol.control.Zoom(), new ol.control.Attribution({
        collapsible: true,
        collapsed: false,
    })],
    layers: [BACKGROUND, MARKERS],
    target: MAP_ID,
    view: VIEW,
});

MAP.on('click', function(event) {
    VIEW.setCenter(event.coordinate);

    MARKERS_SOURCE.getFeatures()[0].getGeometry().setCoordinates(event.coordinate);

    $('#mtl-center-lon').val(ol.proj.toLonLat(event.coordinate)[0]);
    $('#mtl-center-lat').val(ol.proj.toLonLat(event.coordinate)[1]);
});

MAP.on('moveend', function() {
	$('#mtl-standard-zoom').val(VIEW.getZoom());
})

// Initiate map and load contents
$(document).ready(function() {
	//actions for backend
	initMyTransitLinesDashboard();
});

// dashboard functions
function initMyTransitLinesDashboard() {
    // Init color pickers
	if($('.mtl-color-picker-field').length) $('.mtl-color-picker-field').wpColorPicker();

    // TODO: figure out what this is supposed to do?
	if($('body.options-discussion-php #comments_notify').length) $('body.options-discussion-php #comments_notify').parents('tr').remove();

    // Update map when inputs change
    if($('input#mtl-center-lon').length) $('input#mtl-center-lon').on('change propertychange paste', changeMapMarker);
    if($('input#mtl-center-lat').length) $('input#mtl-center-lat').on('change propertychange paste', changeMapMarker);
    if($('input#mtl-standard-zoom').length) $('input#mtl-standard-zoom').on('change propertychange paste', changeMapMarker);
	
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
	$('input.category-checkbox').change(function() {
			$('input.category-checkbox:not(:checked)').parent().parent().next().css('display','none');
			$('input.category-checkbox:not(:checked)').parent().parent().next().next().css('display','none');
			$('input.category-checkbox:not(:checked)').parent().parent().next().next().next().css('display','none');

			$('input.category-checkbox:checked').parent().parent().next().css('display','table-row');
			$('input.category-checkbox:checked').parent().parent().next().next().css('display','table-row');
			$('input.category-checkbox:checked').parent().parent().next().next().next().css('display','table-row');
		}
	);
}

function changeMapMarker() {
	var currentLon = parseFloat($('input#mtl-center-lon').val());
	var currentLat = parseFloat($('input#mtl-center-lat').val());
	var currentZoom = parseInt($('input#mtl-standard-zoom').val());
	$('#mtl-center-lon').val(currentLon);
	$('#mtl-center-lat').val(currentLat);
	$('#mtl-standard-zoom').val(currentZoom);

    VIEW.setCenter(ol.proj.fromLonLat([currentLon, currentLat]));
    VIEW.setZoom(currentZoom);

    MARKERS_SOURCE.getFeatures()[0].getGeometry().setCoordinates(ol.proj.fromLonLat([currentLon, currentLat]));
}
