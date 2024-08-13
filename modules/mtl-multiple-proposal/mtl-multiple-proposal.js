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
	var $content_pagination = '.mtl-paginate-links';
	var $content_map = '#mtl-multiple-proposal-data-script';

    $.get(link+'', function(data) {
		var $new_content_pagination = $($content_pagination, data); // Grab just the pagination content
		var $new_content_map = $($content_map, data); // Grab just the map content
			
		// add new content
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

const container = document.getElementById('popup');
const contentLink = document.getElementById('popup-content-link');
const contentTitle = document.getElementById('popup-content-title');
const contentAuthor = document.getElementById('popup-content-author');
const contentDate = document.getElementById('popup-content-date');
const closer = document.getElementById('popup-closer');

const overlay = new ol.Overlay({
	element: container,
	autoPan: {
		animation: {
			duration: 250,
		},
	},
});
map.addOverlay(overlay);

const selectInteraction = new ol.interaction.Select({ layers: [vectorLayer], style: styleFunction });
map.addInteraction(selectInteraction);

selectInteraction.on('select', function (evt) {
	if (evt.selected.length < 1) {
		closePopup();
		return;
	}

	const coordinate = evt.mapBrowserEvent.coordinate;

	const proposalData = vectorProposalData[evt.selected[0].get('proposal_data_index')];

	contentLink.href = proposalData.link;
	contentTitle.textContent = proposalData.title;
	contentAuthor.textContent = proposalData.author;
	contentDate.textContent = proposalData.date;

 	overlay.setPosition(coordinate);
});

/**
 * Add a click handler to hide the popup.
 * @return {boolean} Don't follow the href.
 */
closer.onclick = function () {
	closePopup();
	return false;
};

function closePopup() {
	overlay.setPosition(undefined);
	closer.blur();
	selectInteraction.getFeatures().clear();
}
