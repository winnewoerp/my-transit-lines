<?php
/**
 * My Transit Lines
 * Map module
 *
 * @package My Transit Lines
 */
 
/* created by Jan Garloff, 2024-08-21 */

// Output for the map. Doesn't include any scripts except for my-transit-lines.js
function the_map_output() {
	wp_enqueue_script('my-transit-lines', get_template_directory_uri() . '/js/my-transit-lines.js', [], wp_get_theme()->version);

	return '
	<div id="mtl-box">'.
		apply_filters('mtl-map-box',
		'<div id="mtl-map-box" data-mtl-toggle-fullscreen>
			<div id="mtl-map"></div>
		</div>').
		'<div class="mtl-map-controls">
			<p id="map-color-opacity" data-mtl-toggle-fullscreen>
				<span id="mtl-colored-map-box">
					<label for="mtl-colored-map">
						<input type="checkbox" checked="checked" id="mtl-colored-map" name="colored-map" onclick="toggleMapColors()"> '.
						__('colored map','my-transit-lines').
					'</label>
				</span>
				&nbsp;
				<span id="mtl-opacity-low-box">
					<input type="checkbox" checked="checked" id="mtl-opacity-low" name="opacity-low" onclick="toggleMapOpacity()">
					<label id="mtl-opacity-low-brighter" for="mtl-opacity-low">'.
						__('brightened map','my-transit-lines').
					'</label>
					<label id="mtl-opacity-low-darker" for="mtl-opacity-low">'.
						__('darkened map','my-transit-lines').
					'</label>
				</span>
			</p>
			<p id="zoomtofeatures" class="alignright">
				<a href="javascript:zoomToFeatures()" data-mtl-toggle-fullscreen>'.
					__('Fit proposition to map','my-transit-lines').
				'</a>
			</p>
			<a id="toggle-fullscreen" href="javascript:toggleFullscreen()" data-mtl-toggle-fullscreen class="alignright">
				<span class="fullscreen-closed">'.
					__('Fullscreen view','my-transit-lines').
				'</span>
				<span class="fullscreen-open">'.
					__('Close fullscreen view','my-transit-lines').
				'</span>
			</a>
			<p class="alignright" id="mtl-toggle-labels">
				<label>
					<input type="checkbox" checked="checked" id="mtl-toggle-labels-link" onclick="toggleLabels()"> '.
					__('Show labels','my-transit-lines').
				'</label>
			</p>
			<p class="alignright" id="mtl-toggle-sizes">
				<label>
					<input type="checkbox" autocomplete="off" id="mtl-toggle-sizes-link" onclick="toggleSizes()"> '.
					__('Show feature sizes','my-transit-lines').
				'</label>
			</p>
		</div>
	</div>';
}
