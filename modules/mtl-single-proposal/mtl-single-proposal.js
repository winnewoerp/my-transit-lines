/**
 * (C) Jan Garloff
 */

const selectInteraction = new ol.interaction.Select({ layers: [vectorLayer], multi: true, style: selectedStyleFunction });
const dragBoxInteraction = new ol.interaction.DragBox();

const selectedFeatures = selectInteraction.getFeatures();

map.addInteraction(selectInteraction);

dragBoxInteraction.on('boxend', handleBoxSelect);
dragBoxInteraction.on('boxstart', function (event) {
	if (!ol.events.condition.shiftKeyOnly(event.mapBrowserEvent))
		selectedFeatures.clear();
});

selectedFeatures.on('add', handleFeatureSelected);
selectedFeatures.on('remove', handleFeatureUnselected);

// Open textinput for feature name and show size of feature
function handleFeatureSelected(event) {
	event.element.set('size', getFeatureSize(event.element));
}

// Set name of unselected feature to name from the textinput and remove size being shown
function handleFeatureUnselected(event) {
	event.element.unset('size');
}

// Selects all features inside the box dragged for selection
function handleBoxSelect() {
	const boxExtent = dragBoxInteraction.getGeometry().getExtent();

	// if the extent crosses the antimeridian process each world separately
	const worldExtent = map.getView().getProjection().getExtent();
	const worldWidth = ol.extent.getWidth(worldExtent);
	const startWorld = Math.floor((boxExtent[0] - worldExtent[0]) / worldWidth);
	const endWorld = Math.floor((boxExtent[2] - worldExtent[0]) / worldWidth);

	for (let world = startWorld; world <= endWorld; ++world) {
		const left = Math.max(boxExtent[0] - world * worldWidth, worldExtent[0]);
		const right = Math.min(boxExtent[2] - world * worldWidth, worldExtent[2]);
		const extent = [left, boxExtent[1], right, boxExtent[3]];

		const boxFeatures = vectorSource
			.getFeaturesInExtent(extent)
			.filter(
				(feature) =>
					!selectedFeatures.getArray().includes(feature) &&
					feature.getGeometry().intersectsExtent(extent)
			);

		// features that intersect the box geometry are added to the
		// collection of selected features
		selectedFeatures.extend(boxFeatures);
	}
}
