/**
 * (C) by Jan Garloff and Johannes Bouchain - stadtkreation.de
 */

const GEO_JSON_FORMAT = new ol.format.GeoJSON({
	featureClass: ol.render.RenderFeature,
});
const WKT_FORMAT = new ol.format.WKT({
	splitCollection: true,
});
const PROJECTION_OPTIONS = {
	dataProjection: 'EPSG:4326',
	featureProjection: 'EPSG:3857',
};

const countryFeatures = countrySource ? GEO_JSON_FORMAT.readFeatures(countrySource, PROJECTION_OPTIONS) : [];
const stateFeatures = stateSource ? GEO_JSON_FORMAT.readFeatures(stateSource, PROJECTION_OPTIONS) : [];
const districtFeatures = districtSource ? GEO_JSON_FORMAT.readFeatures(districtSource, PROJECTION_OPTIONS) : [];

let feature_data = [];

/**
 * Returns a comma-separated string list containing all location tags for the stations in the features array
 * 
 * @param {FeatureLike[]} features the array of features to "search" for stations
 * @returns {string} the location tags of the stations placed on the map
 */
function getStationLocations(features = feature_data) {
	let result = '';
	let hasStations = getCountStations(features) > 0;

	for (let station of features) {
		if (!(station.getGeometry() instanceof ol.geom.Point) && hasStations) {
			continue;
		}

		let tags = getStationLocation(station).split(',');

		for (let tag of tags) {
			if (!result.includes(tag))
				result += tag + ',';
		}
	}

	return result;
}

/**
 * Gets the location tags for a single station
 * @param {FeatureLike} station the feature to get the location of
 * @return {string} either the empty string or a comma-separated list of up 3 location tags ending with a comma
 */
function getStationLocation(station) {
	let country = '', state = '', district = '';

	country = getStationLocationLayer(station, countryFeatures, true);

	if (!country)
		return 'International,';

	state = getStationLocationLayer(station, stateFeatures, true);

	if (!state)
		return country;

	district = getStationLocationLayer(station, districtFeatures, false);

	return country + state + district;
}

/**
 * Gets the location tag of the station on the layer specified by features
 * @param {FeatureLike} station the station to get the location of
 * @param {FeatureLike[]} features the layer to search on. Individual features need to be able to intersect the stations coordinates and have the "GEN" property set as their name
 * @param {boolean} onlyFirstWord whether you want only use the first word of the name found in the "GEN" property word seperator is ' '
 * @return {string} the location tag, either empty or ending with a comma
 */
function getStationLocationLayer(station, features, onlyFirstWord) {
	let coordinate;
	if (station.getGeometry() instanceof ol.geom.Point)
		coordinate = station.getGeometry().getCoordinates();
	else if (station.getGeometry().getCoordinates().length > 0) {
		coordinate = station.getGeometry().getCoordinates()[0];
	}

	for (let feature of features) {
		if (feature.getGeometry().intersectsCoordinate(coordinate)) {
			let name = feature.get("GEN").replace(',', '');
			if (onlyFirstWord)
				name = name.split(' ')[0];
			return name + ',';
		}
	}
	return "";
}

/**
 * Returns the amount of stations in the features array
 * 
 * @param {FeatureLike[]} features the array of features to "search" for stations
 * @returns {number} the amount of stations placed on the map
 */
function getCountStations(features = feature_data) {
	let count = 0;
	for (let feature of features) {
		if (feature.getGeometry() instanceof ol.geom.Point)
			count++;
	}
	return count;
}

function loadNewTags() {
	feature_data = data ? WKT_FORMAT.readFeatures(data, PROJECTION_OPTIONS) : [];
	let tags = getStationLocations();
	$('#tags_input').val(tags);

	for (let tag of tags.split(',')) {
		$('#list-start').append('<li><a>'+tag+'</a></li> ');
	}
}

$(document).ready(function () {
	$('#no-reload').val('true');
	loadNewTags();
});

document.getElementById('add_tags').addEventListener("submit",
	function (event) {
		event.preventDefault();

		// TODO custom post submit and new post load
		$.post(window.location.href, $( "#add_tags" ).serialize(), function(new_data) {
			var new_id = $('#id_input', new_data);
			var new_data_script = $('#mtl-data-script', new_data);
			var new_post_title = $('#post-title', new_data);
			
			// add new content
			$('#id_input').replaceWith(new_id);
			$('#mtl-data-script').replaceWith(new_data_script);
			$('#post-title').replaceWith(new_post_title);

			$('#list-start').empty();

			loadNewTags();
		});
	});
