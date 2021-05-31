/**
 * Zoom behavior for a measurement diagram.
 * @param {Integer} minScaleExtent The minimum extent of the zoom scaling.
 * @param {Integer} maxScaleExtent The maximum extent of the zoom scaling.
 * @param {Object} diagram MeasurementDiagram object that contains the data to be displayed in the line chart.
 */
function ZoomBehavior(minScaleExtent, maxScaleExtent, diagram) {
	// Validate the minScaleExtent and maxScaleExtent parameters and use default values in case they were not specified or out of range.
	if (!minScaleExtent || minScaleExtent < 1 || minScaleExtent > 32) {
		minScaleExtent = 1;
	}
	if (!maxScaleExtent || maxScaleExtent < 1 || maxScaleExtent > 32) {
		maxScaleExtent = 32;
	}

	// Make sure the diagram object is valid and that it contains a line chart.
	if (typeof diagram !== "object" || typeof diagram.chart !== "object") {
		throw "Invalid diagram. Expected an initialized MeasurementDiagram object with a chart.";
	}

	// Store references to the measurement diagram and its child line chart.
	this.diagram = diagram;
	this.chart = diagram.getChart();
	// Store references to the tooltips of the diagram.
	this.tooltip = {
		measurement: diagram.getMeasurementTooltip(),
		legend: diagram.getLegendTooltip()
	};

	this.scaleExtent = {
		min: minScaleExtent,
		max: maxScaleExtent
	};

	// Last known zoom behavior properties.
	this.lastKnownZoomProperty = {
		scale: null,
		translate: null,
		// A flag that indicates that some zooming was applied and it was not reversed so far.
		zoomed: false,
		// Indicates whether geometric scaling is in use.
		useGeometricScaling: false
	};

	// Define a zoom behavior that will automatically create events for zooming and panning.
	// Specify also the zoom scaleExtent.
	this.behavior = d3.behavior.zoom()
		.scaleExtent([this.scaleExtent.min, this.scaleExtent.max]);

	// List of other properties being set mostly at the init phase.
	// They are just listed here to make it clearer that they exist.
	this.zoomEventListener = null;
}


/**
 * Initializes the zoom behavior.
 */
ZoomBehavior.prototype.init = function() {
	// Register the default zoom event listener function.
	this.setZoomEventListener(this.getDefaultZoomEventListener());
	// Register the scales for the chart with the zoom behavior.
	this.setChartScales();
};


/**
 * Registers a zoom event listener function.
 * @param {Function} eventListener The event listener function to be executed on zoom.
 */
ZoomBehavior.prototype.setZoomEventListener = function(eventListener) {
	// Store the event listener as a property of the ZoomBehavior object.
	this.zoomEventListener = eventListener;

	// Register the zoom event listener function.
	this.behavior.on("zoom", this.zoomEventListener);
};


/**
 * Registers the zoom behavior with the chart scales, making sure the input domains adjust when zooming.
 *
 * @param {[Boolean]} ignoreVerticalScales If set to TRUE, the vertical scale
 *   will not be set. This is useful e.g. when using brushing.
 */
ZoomBehavior.prototype.setChartScales = function(ignoreVerticalScale) {
	var xScale = this.chart.getXScale();
	var yScale = this.chart.getYScale();

	// Automatically adjust the input domain of the x and y scale when zooming.
	this.behavior.x(xScale);

	if (!ignoreVerticalScale) {
		this.behavior.y(yScale);
	}
};


/**
 * Restores any last known translation and scaling zoom behavior properties and calls the zoom event listener.
 */
ZoomBehavior.prototype.restoreLastSavedZoomProperties = function() {
	// If there is a last saved zoom translate property, apply it now.
	var translate = this.lastKnownZoomProperty.translate;
	if (translate !== null) {
		this.behavior.translate(translate);
	}

	// If there is a last saved zoom scale property, apply it now.
	var scale = this.lastKnownZoomProperty.scale;
	if (scale !== null) {
		this.behavior.scale(scale);
	}

	// Manually call the zoom event listener.
	this.zoomEventListener();
};


/**
 * @return {Function} The default zoom event listener function.
 */
ZoomBehavior.prototype.getDefaultZoomEventListener = function() {
	var self = this;

	// Define a function that will use the outer method's closure.
	// In this way it will be possible to refer to the zoom behavior object.
	var defaultZoomEventListener = function() {
		// Determine if geometric scaling should be applied.
		// This needs to be done before modifying the last known zoom properties.
		var useGeometricScaling = self.geometricScalingIsPreferred();

		// Get the translate and zoom values for the D3js zoom behavior at the moment of calling this event listener.
		var translate = self.behavior.translate();
		var scale = self.behavior.scale();

		// Store the fetched values as the last known zoom behavior properties. They can be useful when re-drawing the chart.
		self.lastKnownZoomProperty.scale = scale;
		self.lastKnownZoomProperty.translate = translate;
		// If the scaling is set to 1, then any zooming possibly initiated in the past has now been reverted.
		self.lastKnownZoomProperty.zoomed = (scale != 1);

		// Panning-limit example taken from: http://stackoverflow.com/a/16758790
		// Manually restrict the translate for zoom.
		var tx = Math.min(0, Math.max(self.chart.getWidth() * (1 - scale), translate[0]));
		var ty = Math.min(0, Math.max(self.chart.getHeight() * (1 - scale), translate[1]));
		self.behavior.translate([tx, ty]);

		if (scale == 1) {
			// Display the chart axis groups as they may have been hidden before.
			self.chart.showAxisGroups();
		}

		// If geometric scaling is preferred, apply some extra transforms to the chart elements.
		if (useGeometricScaling) {
			if (scale != 1) {
				// Hide the chart axis groups since they are not displaye properly when using geometric scaling (i.e. they cannot fit in their reserved margins).
				self.chart.hideAxisGroups();
			}

			self.chart.applyGeometricScale(scale);
		}

		// Hide the circles in the chart after zoom.
		// Their position needs to be re-calculated.
		self.chart.hideCircleGroups(true);
		// Re-draw the chart axes.
		self.chart.updateAxes();
		// Re-draw the lines.
		self.chart.updateLines();

		// Hide the measurement tooltip.
		// self.tooltip.measurement.hide();
	};

	return defaultZoomEventListener;
};


/**
 * @return {Boolean} true If the chart indicates that geometric scalng is preferred for it and there is no known non-geometric scaling applied already, or false otherwise.
 */
ZoomBehavior.prototype.geometricScalingIsPreferred = function() {
	// If no zooming has been initiated yet.
	if (!this.lastKnownZoomProperty.zoomed) {
		// Check if geometric scaling is preferred for the chart.
		var geometricScalingIsPreferredForChart = this.chart.geometricScalingIsPreferred();

		// Store the preference as in the last known zoom properties object.
		this.lastKnownZoomProperty.useGeometricScaling = geometricScalingIsPreferredForChart;		
	}

	// Return whether the last applied scaling was geometric or if the new one is preferred to be geometric.
	return this.lastKnownZoomProperty.useGeometricScaling;
};


/**
 * @return {Object} A d3js zoom behavior.
 */
ZoomBehavior.prototype.getBehavior = function() {
	return this.behavior;
};
