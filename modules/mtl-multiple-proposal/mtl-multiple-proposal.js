/* My Transit Line posttiles list */

function handle_new_data(new_document) {
	const content_pagination = '.mtl-paginate-links';
	const content_map = 'mtl-multiple-proposal-data-script';

	const old_pagination = document.querySelectorAll(content_pagination);

	const new_pagination = new_document.querySelectorAll(content_pagination);
	const new_map = new_document.getElementById(content_map);

	old_pagination.forEach((elem, index) => {
		elem.replaceWith(new_pagination.item(index));
	});
	eval?.(new_map.innerText); // Evaluates eval indirectly in the global scope to update js vars from fetched data. TODO replace with retrieval of JSON only

	loadNewFeatures();
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

	container.style.display = '';
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
	container.style.display = 'none';
	overlay.setPosition(undefined);
	closer.blur();
	selectInteraction.getFeatures().clear();
}
