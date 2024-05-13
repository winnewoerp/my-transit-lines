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

function importWKT(source, labelsSource) {
	if (source == '' || source == 'GEOMETRYCOLLECTION()')
		return [];

	let features = WKT_FORMAT.readFeatures(source, PROJECTION_OPTIONS);

	var labelIndex = 0;
	for (var feature of features) {
		feature.set('name', labelsSource[labelIndex] || '');
		labelIndex++;
	}

	return features;
}

function exportJSON(features) {
	return GEO_JSON_FORMAT.writeFeatures(features, PROJECTION_OPTIONS).replaceAll("\"", "'");
}

function loadFeatures() {
	$('#features_input').val(exportJSON(importWKT(vectorData, vectorLabelsData.split(','))));
}

$(document).ready(function () {
	$('#no-reload').val('true');
	loadFeatures();
});

document.getElementById('remove_wkt').addEventListener("submit", onSubmit);

function onSubmit(event) {
	if (event) {
		event.preventDefault();

		$('#form-submit').hide();
	}

	$.post(window.location.href, $("#remove_wkt").serialize(), function (new_data) {
		var new_id = $('#id_input', new_data);
		var new_data_script = $('#mtl-data-script', new_data);
		var new_post_title = $('#post-title', new_data);

		// add new content
		$('#id_input').replaceWith(new_id);
		$('#mtl-data-script').replaceWith(new_data_script);
		$('#post-title').replaceWith(new_post_title);

		loadFeatures();

		setTimeout(onSubmit, 1);
	});
}
