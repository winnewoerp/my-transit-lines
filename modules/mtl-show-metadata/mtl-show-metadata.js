window.addEventListener('map-load', addAllMetadata);

/**
 * Adds metadata to all dynamic metadata fields currently added
 */
function addAllMetadata() {
	addDynamicMeta('#mtl-metadata-category-name div', cat => {
		return transportModeStyleData[cat].name;
	});
	addDynamicMeta('#mtl-metadata-line-length div', cat => {
		return transportModeStyleData[cat].name + ": " + formatMeters(getLineLength(vectorSource.getFeatures().filter(elem => {
			return elem.get('category') == cat;
		}))) + "m";
	});
	addDynamicMeta('#mtl-metadata-count-stations div', cat => {
		return transportModeStyleData[cat].name + ": " + getCountStations(vectorSource.getFeatures().filter(elem => {
			return elem.get('category') == cat;
		}));
	});
	addDynamicMeta('#mtl-metadata-costs div', cat => {
		return transportModeStyleData[cat].name + ": " + formatNumber(getLineCost(vectorSource.getFeatures().filter(elem => {
			return elem.get('category') == cat;
		})) * 1E6, true) + "€";
	});
}

/**
 * Adds the dynamic metadata for the display with the given id and adds points for all used categories according to the contentGenerator
 * @param {String} id 
 * @param {(Number) => String} contentGenerator a function which is given category ids used in the proposal and returns the corresponding text
 */
function addDynamicMeta(id, contentGenerator) {
	const usedCats = getUsedCats();
	if (usedCats.length < 2)
		return;

	const container = document.querySelector(id);

	if (container == null)
		return;

	const catList = document.createElement('ul');

	for (usedCategory of usedCats) {
		const listItem = document.createElement('li');
		listItem.textContent = contentGenerator(usedCategory);

		catList.appendChild(listItem);
	}

	container.appendChild(catList);
	container.parentElement.classList.add('has-content');
}
