/* My Transit Line posttiles list */

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

	const proposal = proposalList.find((elem) => {
		return elem.id === evt.selected[0].get('proposal_data_index');
	});

	container.style.display = '';
	contentLink.href = proposal.link;
	contentTitle.textContent = proposal.title;
	contentAuthor.textContent = proposal.author;
	contentDate.textContent = proposal.date;

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
