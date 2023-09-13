/* My Transit Line posttiles list */

var $ = jQuery;
var currentHash = window.location.hash;

jQuery(document).ready(function($) {
	if(currentHash.includes('#!')) load_new_data(multiple_proposal_page_url+currentHash.replace('#!',''));
	
	$(window).on('hashchange', function() {
		if(window.location.href.includes(multiple_proposal_page_url)) {
			currentHash = window.location.hash;
			if(currentHash.includes('#!')) load_new_data(multiple_proposal_page_url+currentHash.replace('#!',''));
		}
	});
	
	set_button_behaviour();
});

function load_new_data(link) {
	var $content_filter = '#mtl-list-filter';
	var $content_pagination = '.mtl-paginate-links';
	var $content_map = '#mtl-multiple-proposal-data-script';

    $.get(link+'', function(data) {
		var $new_content_filter = $($content_filter, data); // Grab just the filter content
		var $new_content_pagination = $($content_pagination, data); // Grab just the pagination content
		var $new_content_map = $($content_map, data); // Grab just the map content
			
		// add new content
		$($content_filter).replaceWith($new_content_filter);
		$($content_pagination).replaceWith($new_content_pagination);
		$($content_map).replaceWith($new_content_map);
		loadNewFeatures();

		set_button_behaviour();
	});
}

function set_button_behaviour() {
	$('#mtl-filter-form').submit(function(e) {
		e.preventDefault();
		submit_new_filter();
	});

	$('.mtl-paginate-links a').on('click', function(e)  {
		e.preventDefault();
		window.location.hash = '!'+$(this).attr('href').replace(multiple_proposal_page_url,'');
	});

	$('#mtl-post-list-link').attr('href', post_list_url + currentHash);
}

function submit_new_filter() {
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
	var newHash = newLink.replace(multiple_proposal_page_url,'');
	window.location.hash  = '!'+newHash;
}