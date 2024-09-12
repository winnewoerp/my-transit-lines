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
