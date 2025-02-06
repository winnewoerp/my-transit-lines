function getGeoJSON() {
	var featuresDataObject = JSON.parse(exportToJSON().replaceAll("'", '"'));

	featuresDataObject.name = title_for_geojson;
	featuresDataObject.description = content_for_geojson;
	featuresDataObject.author = author_for_geojson;
	featuresDataObject.date = date_for_geojson;
	featuresDataObject.transit_mode = category_for_geojson;
	featuresDataObject.original_website = website_for_geojson;
	featuresDataObject.license_link = license_link_for_geojson;
	
	return JSON.stringify(featuresDataObject);
}

document.getElementById('mtl-geojson-download').onclick = function(event) {
	try {
		this.href = 'data:text/json;charset=utf-8,'+encodeURIComponent(getGeoJSON());
	} catch (e) {
		console.log(e);
		alert("Something has gone wrong while generating the GeoJSON. Please contact the administrator of the Website!");
		event.preventDefault();
	}
};