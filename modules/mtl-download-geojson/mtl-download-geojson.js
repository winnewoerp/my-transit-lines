function getGeoJSON() {
	var featuresDataObject = JSON.parse(exportToJSON());

	featuresDataObject.name = title_for_geojson;
	featuresDataObject.description = content_for_geojson;
	featuresDataObject.author = author_for_geojson;
	featuresDataObject.date = date_for_geojson;
	featuresDataObject.transit_mode = category_for_geojson;
	featuresDataObject.original_website = website_for_geojson;
	featuresDataObject.license_link = license_link_for_geojson;
	
	return JSON.stringify(featuresDataObject);
}

window.addEventListener("load", function() {
	document.getElementById('mtl-geojson-download').onclick = function() {
		this.href = 'data:text/json;charset=utf-8,'+encodeURIComponent(getGeoJSON());
	};
});