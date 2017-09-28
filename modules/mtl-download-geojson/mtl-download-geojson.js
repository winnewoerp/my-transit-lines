var geojson_options = {};
var feature_labels_data_for_geojson_array = feature_labels_data_for_geojson.split(',');
var wkt_format = new OpenLayers.Format.WKT();
var wkt_features = wkt_format.read(feature_data_for_geojson);
var wkt_options = {};
var geojson_format = new OpenLayers.Format.GeoJSON(wkt_options);
var prepare1 = geojson_format.write(wkt_features);
var prepare2 = JSON.parse(prepare1);
prepare2.name = title_for_geojson;
prepare2.description = content_for_geojson;
prepare2.author = author_for_geojson;
prepare2.date = date_for_geojson;
prepare2.transit_mode = category_for_geojson;
prepare2.original_website = website_for_geojson;
prepare2.license_link = license_link_for_geojson;
for(var i = 0;i<prepare2.features.length;i++) {
	if(prepare2.features[i].geometry.type=='Point') {
		prepare2.features[i].properties.name = feature_labels_data_for_geojson_array[i];
		prepare2.features[i].properties.description = category_for_geojson+"\n"+author_for_geojson+"\n"+date_for_geojson+"\n"+website_for_geojson+"\n"+license_link_for_geojson;
	}
	if(prepare2.features[i].geometry.type=='LineString') {
		prepare2.features[i].properties.name = title_for_geojson;
		prepare2.features[i].properties.description = content_for_geojson+"\n\n"+category_for_geojson+"\n"+author_for_geojson+"\n"+date_for_geojson+"\n"+website_for_geojson+"\n"+license_link_for_geojson;
	}
}
var geojson_ready = JSON.stringify(prepare2);

window.onload = function() {
  document.getElementById('mtl-geojson-download').onclick = function() {
	this.href = 'data:text/json;charset=utf-8,'+encodeURIComponent(geojson_ready);
  };
};