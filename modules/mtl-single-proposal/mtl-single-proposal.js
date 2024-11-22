/**
 * (C) Jan Garloff
 */

const selectInteraction = new ol.interaction.Select({ layers: [vectorLayer], multi: true, style: selectedStyleFunction });

const selectedFeatures = selectInteraction.getFeatures();

map.addInteraction(selectInteraction);

selectedFeatures.on('add', handleFeatureSelected);
selectedFeatures.on('remove', handleFeatureUnselected);

// Open textinput for feature name and show size of feature
function handleFeatureSelected(event) {
	event.element.set('size', getFeatureSize(event.element));
}

// Set name of unselected feature to name from the textinput and remove size being shown
function handleFeatureUnselected(event) {
	if (!showSizes)
		event.element.unset('size');
}

const loaded_revisions = {[proposalList[0].revision]: proposalList[0]};
document.getElementById('mtl-revision-input').addEventListener('change', function(e) {
	const load_revision = e.target.value === e.target.max ? "current" : e.target.value;

	if (!Object.keys(loaded_revisions).includes(load_revision)) {
		fetch(wp.ajax.settings.url, {
			method: 'post',
			body: new URLSearchParams({
				action: 'mtl_proposal_data',
				post_id: Object.values(loaded_revisions)[0].id,
				revision: load_revision,
			}),
		}).then(response => {
			response.json().then(json => {
				if (document.getElementById('mtl-revision-input').value !== load_revision)
					return;

				loaded_revisions[load_revision] = json;
				proposalList[0] = loaded_revisions[load_revision];
				loadNewFeatures();
			});
		});
	} else {
		proposalList[0] = loaded_revisions[load_revision];
		loadNewFeatures();
	}
});
