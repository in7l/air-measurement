/**
 * A context class for a line chart. Used for better representation of historical trends.
 *
 * @param {LineChart object} lineChart A lineChart object for which a context should be displayed.
 */
function LineChartContext(lineChart) {
	var self = this;

	this.chart = lineChart;

	// Define the margins and dimensions, taking into account that some of them should not go below a certain minimum.
	// Use D3's margin convention.
	this.sizeValues = {};
	this.sizeValues.margin = {
		bottom: {
			current: LineChartContext.minMarginBottom,
			minimum: LineChartContext.minMarginBottom
		}
	};
	this.sizeValues.dimension = {
		height: {
			current: LineChartContext.startingHeight,
			minimum: LineChartContext.minHeight
		}
	};

	// Use D3's margin convention.
	// Define some default margins for the chart area.
	this.margin = {
		// Defines if a margin object (having current and optionally minimum fields) is down-scalable or not.
		isScalable: function(marginObject) {
			if (typeof marginObject !== 'object') {
				return null;
			}

			// If there is no defined minimum, or if the current value is still greater than the minimum, the margin can be scale down.
			if (marginObject.minimum === undefined || marginObject.current > marginObject.minimum) {
				return true;
			}
			else {
				return false;
			}
		},
		// The left and right margins should be the same as the ones of the line chart.
		get left() {
			return self.chart.margin.left;
		},
		get right() {
			return self.chart.margin.right;
		},
		get topPadding() {
			return LineChart.minPaddingBottom;
		},
		// The top margin should reserve enough space for the line chart itself + some extra padding.
		get top() {
			return self.chart.margin.top + self.chart.dimension.height + LineChart.minPaddingBottom;
		},
		set top(value) {
			// If there is a defined minimum and the value is less than that, fallback to the minimum value.
			if (self.sizeValues.margin.top.minimum !== undefined && value < self.sizeValues.margin.top.minimum) {
				value = self.sizeValues.margin.top.minimum;
			}

			// Set the value.
			self.sizeValues.margin.top.current = value;
		},
		get bottom() {
			return self.sizeValues.margin.bottom.current;
		},
		set bottom(value) {
			// If there is a defined minimum and the value is less than that, fallback to the minimum value.
			if (self.sizeValues.margin.bottom.minimum !== undefined && value < self.sizeValues.margin.bottom.minimum) {
				value = self.sizeValues.margin.bottom.minimum;
			}

			// Set the value.
			self.sizeValues.margin.bottom.current = value;
		}
	};

	this.dimension = {
		// Defines if a dimension object (having current and optionally minimum fields) is down-scalable or not.
		isScalable: function(dimensionObject) {
			if (typeof dimensionObject !== 'object') {
				return null;
			}

			// If there is no defined minimum, or if the current value is still greater than the minimum, the dimension can be scale down.
			if (dimensionObject.minimum === undefined || dimensionObject.current > dimensionObject.minimum) {
				return true;
			}
			else {
				return false;
			}
		},
		// The width should be the same as the one of the line chart.
		get width() {
			return self.chart.dimension.width;
		},
		get height() {
			return self.sizeValues.dimension.height.current;
		},
		set height(value) {
			// If there is a defined minimum and the value is less than that, fallback to the minimum value.
			if (self.sizeValues.dimension.height.minimum !== undefined && value < self.sizeValues.dimension.height.minimum) {
				value = self.sizeValues.dimension.height.minimum;
			}

			// Set the value.
			self.sizeValues.dimension.height.current = value;
		},
		// Define getters for calculating the full width and height (including margins).
		get fullWidth() {
			return self.chart.dimension.fullWidth();
		},
		get fullHeight() {
			return this.height + self.margin.topPadding + self.margin.bottom;
		},
		// Define a function for fetching the height margins or dimensions which can or cannot be scaled down.
		// If the scalable parameter is set to true, the scalable height component identifiers will be returned, otherwise the non-scalable ones.
		getHeightComponentIdentifiers: function(scalable) {
			var heightComponentIdentifiers = {
				margin: [],
				dimension: []
			};

			// Determine the expected value of the isScalable methods, based on the specified parameter.
			// Set the scalable value to e boolean, since exact comparisons are necessary.
			if (!scalable) {
				scalable = false;
			}
			else {
				scalable = true;
			}

			// Define a list of margins that could affect the height (as opposed to width).
			// Note that the top margin is excluded, since there is no actual setter for it.
			var allowedMarginNames = ['bottom'];

			for (var i = 0; i < allowedMarginNames.length; i++) {
				var marginName = allowedMarginNames[i];
				var marginObject = self.sizeValues.margin[marginName];
				// Check if this margin object is of the expected type.
				if (self.margin.isScalable(marginObject) === scalable) {
					// Add the margin to the list of identifiers.
					heightComponentIdentifiers.margin.push(marginName);
				}
			}

			// Define a list of dimensions that could affect the height (as opposed to width).
			var allowedDimensionNames = ['height'];

			for (i = 0; i < allowedDimensionNames.length; i++) {
				var dimensionName = allowedDimensionNames[i];
				var dimensionObjet = self.sizeValues.dimension[dimensionName];

				// Check if this dimension object is of the expected type.
				if (self.dimension.isScalable(dimensionObjet) === scalable) {
					// Add the dimension to the list of identifiers.
					heightComponentIdentifiers.dimension.push(dimensionName);
				}
			}

			return heightComponentIdentifiers;
		},
		// Define a getter for the non-scalable component identifiers.
		get nonScalableHeightComponentIdentifiers() {
			var result = this.getHeightComponentIdentifiers(false);

			return result;
		},
		// Define a getter for the scalable component identifiers.
		get scalableHeightComponentIdentifiers() {
			var result = this.getHeightComponentIdentifiers(true);

			return result;
		},
		// Define a getter for the height of the elements that are no longer scalable (e.g. because they have reached their minimum).
		get nonScalableHeight() {
			var nonScalableHeight = 0;

			// Get the non-scalable height component identifiers.
			var scalableComponentIdentifiers = this.nonScalableHeightComponentIdentifiers;

			for (var componentType in scalableComponentIdentifiers) {
				var componentNames = scalableComponentIdentifiers[componentType];
				for (var i = 0; i < componentNames.length; i++) {
					var componentName = componentNames[i];
					// Get the value for this component.
					var value = self[componentType][componentNames];

					if (value > 0) {
						nonScalableHeight += value;
					}
				}
			}

			return nonScalableHeight;
		},
		// Returns the number of scalable components for the line chart context.
		get scalableComponentsCount() {
			var scalableComponentIdentifiers = this.scalableHeightComponentIdentifiers;

			var scalableComponentsCount = scalableComponentIdentifiers.dimension.length + scalableComponentIdentifiers.margin.length;

			return scalableComponentsCount;
		}
	};

	// List of other properties being set mostly at the init phase.
	// They are just listed here to make it clearer that they exist.
	this.primaryField = null;
	this.color = null;
	this.data = null;
	this.scale = null;
	this.axis = null;
	this.area = null;
	this.brush = null;
	this.brushed = null;
	this.lastKnownBrushExtent = null;
	this.contextGroup = null;
	this.contextArea = null;
	this.axisGroup = null;
	this.brushGroup = null;
}


/* Static class properties */
LineChartContext.startingHeight = 50;
LineChartContext.minMarginBottom = 30;
LineChartContext.minHeight = 20;
// Constant-like variables used for properly handling the brush extent.
LineChartContext.BRUSH_EXTENT_START = 'start';
LineChartContext.BRUSH_EXTENT_END = 'end';


/**
 * Adjusts the dimensions of the line chart context element.
 *
 * @param  {Number} verticalMultiplicationCoefficient The multiplication coefficient used for scaling the vertical dimensions.
 */
LineChartContext.prototype.adjustDimensions = function(verticalMultiplicationCoefficient) {
	var self = this;

	// Get the components that are still scalable in height.
	var scalableComponentIdentifiers = this.dimension.scalableHeightComponentIdentifiers;

	// Apply the multiplication component to them. Their setters should ensure that the values do not go below their defined minimum.
	for (var componentType in scalableComponentIdentifiers) {
		var componentNames = scalableComponentIdentifiers[componentType];
		for (var i = 0; i < componentNames.length; i++) {
			var componentName = componentNames[i];
			// Get the value for this component.
			var value = self[componentType][componentNames];
			if (value > 0) {
				// Calculate the new value for this component.
				value *= verticalMultiplicationCoefficient;
				// Round down the value. This will ensure there are fewer height adjustment iterations.
				roundedValue = Math.floor(value);
				// If the rounding did not produce a significant difference, it is better to subtract.
				if (value - roundedValue < 0.25) {
					value -= 0.25;
				}
				else {
					// The rounding produced a significant difference. Just use that one.
					value = roundedValue;
				}
				// Assign the value to the component.
				self[componentType][componentNames] = value;
			}
		}
	}
};


/**
 * Initializes the line chart context.
 *
 * @param {String} primaryField The machine name of the field, for which the context area should be visualized.
 * @param {String} color The color for the primary field.
 */
LineChartContext.prototype.init = function(primaryField, color) {
	// Initialize the scales for the context and set their output ranges.
	this.initScales();
	// Initialize the x axis.
	this.initAxes();

	// Set the primary field to be used for the data.
	this.setPrimaryField(primaryField);
	// Fetch the proper data for the context.
	this.initData();
	// Set the input domains for the scales.
	this.setScaleInputDomains();

	// Set the color for the context area.
	this.setColor(color);

	// Initializes the area for the context.
	this.initArea();
	// Initialize the brush.
	this.initBrush();
};


/**
 * Sets the primary field to be picked from the data grouped by field.
 *
 * @param {String} primaryField The machine name of the field, for which the context area should be visualized.
 */
LineChartContext.prototype.setPrimaryField = function(primaryField) {
	this.primaryField = primaryField;
};


/**
 * Sets the color for the context area.
 *
 * @param {String} color The color for the primary field.
 */
LineChartContext.prototype.setColor = function(color) {
	this.color = color;
};


/**
 * Sets the relevant data, available through the chart, based on the specified primary field.
 *
 * If no data for the primary field is available, the data will be an empty array.
 */
LineChartContext.prototype.initData = function() {
	var dataByField = this.chart.dataByField;
	var data = [];

	// Look for the correct grouped data based on the primary field.
	for (var i = 0; i < dataByField.length; i++) {
		if (dataByField[i].name == this.primaryField) {
			data = dataByField[i].values || [];
			// The relevant data group was found. No need to go through the rest.
			break;
		}
	}

	// Find the minimum value and replace null values with that.
	// This is needed because otherwise null values produce a glitch that visualizes them as negative values.
	var minimumValue;
	for (i = 0; i < data.length; i++) {
		if (data[i].fieldValue !== null) {
			if (typeof minimumValue === 'undefined' || data[i].fieldValue < minimumValue) {
				minimumValue = data[i].fieldValue;
			}
		}
	}
	// Replace null values with the minimum value.
	data = data.map(function(datum) {
		if (datum.fieldValue === null) {
			// Create a new object to be returned in the result with a modified fieldValue.
			// Do not modify the original object, as that will alter the line chart.
			return {
				date: datum.date,
				measurement_id: datum.measurement_id,
				fieldValue: minimumValue
			};
		}
		else {
			return datum;
		}
	});

	this.data = data;
};


/**
 * Sets the input domains for the x and y scales based on the data.
 */
LineChartContext.prototype.setScaleInputDomains = function() {
	// Define the input domain for the xScale depending on the date field.
	this.scale.x.domain(d3.extent(this.data, function(d) {
		return d.date;
	}));

	// Define the input domain for the yScale similar to the xScale but depending on the min and max of the fieldValue.
	this.scale.y.domain(d3.extent(this.data, function(d) {
		return d.fieldValue;
	}));
};


/**
 * Initializes the x and y scales and sets the output range for them.
 */
LineChartContext.prototype.initScales = function() {
	// Define the horizontal scale that represents the time.
	// The output range can vary between 0 (leftmost part of the chart) and width (rightmost part of the chart).
	var xScale = d3.time.scale()
		.range([0, this.dimension.width]);

	// The vertical scale does not represent a specific type of value so it should be linear.
	// The output range will be between 0 and height.
	var yScale = d3.scale.linear()
		.range([this.dimension.height, 0]);

	// Store the scales to a property of the LineChartContext object so they are accessible from other methods.
	this.scale = {
		x: xScale,
		y: yScale
	};
};

/**
 * Initializes the x axis for the context depending on the defined scales.
 */
LineChartContext.prototype.initAxes = function() {
	var xAxisTicks = 6;
	var xAxis = d3.svg.axis()
		.scale(this.scale.x)
		.orient("bottom")
		.ticks(xAxisTicks);

	// Store the axes to a property of the LineChartContext object so they are accessible from other methods.
	this.axis = {
		x: xAxis
	};
};


/**
 * Initializes the area for the context.
 *
 * @param Object data A data object with grouped values for a single value field.
 */
LineChartContext.prototype.initArea = function(data) {
	var self = this;

	var area = d3.svg.area()
		.interpolate("monotone")
		.x(function(d) {
			// Determine the horizontal position for this point based on the date in this datum and the defined xScale.
			return self.scale.x(d.date);
		})
		.y0(this.dimension.height)
		.y1(function(d) {
			// Determine the vertical position for this point based on the fieldValue in this datum and the defined yScale.
			return self.scale.y(d.fieldValue);
		});

	this.area = area;
};


/**
 * Initializes the brush for the line chart context.
 */
LineChartContext.prototype.initBrush = function() {
	var brushed = this.getDefaultBrushEventListener();
	this.brushed = brushed;

	var brush = d3.svg.brush()
		.x(this.scale.x)
		.on("brush", brushed);

	this.brush = brush;
};


/**
 * Renders the data in an initialized line chart context.
 */
LineChartContext.prototype.renderData = function() {
	// Append a context g element to the svg.
	this.appendContextGroup();
	// Append the axes and their related elements.
	this.appendAxisGroups();
	// Append the area visualising the context.
	this.appendContextArea();
	// Append the g element to hold the brushing capabilities.
	this.appendBrushGroup();
};


/**
 * Appends an SVG g element for the line chart context.
 */
LineChartContext.prototype.appendContextGroup = function() {
	var svg = this.chart.getSvgElement();
	// Append a g element to the svg and move it based on the margins.
	var contextGroupTranslate = "translate(" + this.margin.left + ", " + this.margin.top + ")";
	this.contextGroup = svg.append("g")
		.attr("id", "contextGroup")
		.attr("transform", contextGroupTranslate);
};


/**
 * Appends the area that visualizes the context.
 */
LineChartContext.prototype.appendContextArea = function() {
	var contextArea = this.contextGroup.append("path")
		.attr("class", "area")
		.attr("fill", this.color);

	this.contextArea = contextArea;

	// Bind data to the context area element.
	this.bindContextAreaData();
};

/**
 * Binds data to the context area element.
 */
LineChartContext.prototype.bindContextAreaData = function() {
	this.contextArea.datum(this.data)
		.attr("d", this.area);
};


/**
 * Appends svg g elements that will group axis elements, such as labels.
 */
LineChartContext.prototype.appendAxisGroups = function() {
	var xAxisGroup = this.contextGroup.append("g")
		.attr("class", "x axis")
		.attr("transform", "translate(0," + this.dimension.height + ")")
		.call(this.axis.x);

	this.axisGroup = {
		x: xAxisGroup
	};
};


LineChartContext.prototype.appendBrushGroup = function() {
	var brushGroup = this.contextGroup.append("g")
		.attr("class", "x brush")
		.call(this.brush);

	brushGroup.selectAll("rect")
		.attr("y", -6)
		.attr("height", this.dimension.height + 7);

	this.brushGroup = brushGroup;
};


/**
 * @return {Function} The default brish event listener function.
 */
LineChartContext.prototype.getDefaultBrushEventListener = function() {
	var self = this;

	var defaultBrushEventListener = function() {
		// If there is no data, no brushing should be allowed.
		if (!self.data || self.data.length < 1) {
			self.brush.clear();
			// Make the brush group pick up the new extent.
			self.brushGroup.call(self.brush);
			return;
		}

		// Set the input domain for the x scale.
		var lineChartXScale = self.chart.getXScale();
		lineChartXScale.domain(self.brush.empty() ? self.scale.x.domain() : self.brush.extent());
		// Re-draw the chart, without applying any changes to the vertical scale.
		self.chart.redrawChart(true);

		// Store the last known brush extent.
		self.saveLastKnownBrushExtent();
	};

	return defaultBrushEventListener;
};


LineChartContext.prototype.saveLastKnownBrushExtent = function() {
	// Get the current extent of the brush.
	var lastKnownBrushExtent = this.brush.extent();

	// If the extent was retrieved successfully and there is some data.
	if (lastKnownBrushExtent && this.data && this.data.length > 0) {
		// Get the start and end dates.
		var startDate = lastKnownBrushExtent[0];
		var endDate = lastKnownBrushExtent[1];

		// // If the end date is earlier than the start date, make them equal so that the brush selection is cleared.
		// if (startDate.getTime() > endDate.getTime()) {
		// 	endDate = startDate;
		// 	lastKnownBrushExtent[1] = endDate;
		// }

		// Get the earliest and latest date in the data set.
		var earliestDate = this.data[0].date || null;
		var latestDate = this.data[this.data.length - 1].date || null;

		// Do some additional checks if the earliest and latest date could be fetched successfully.
		if (earliestDate && latestDate) {
			// If the start date is earlier or the same as the earliest available date, do some replacement in the last known brush extent.
			if (startDate.getTime() <= earliestDate.getTime()) {
				lastKnownBrushExtent[0] = LineChartContext.BRUSH_EXTENT_START;
			}
			// If the end date is later or the same as the latest available date, do some replacement in the last known brush extent.
			if (endDate.getTime() >= latestDate.getTime()) {
				lastKnownBrushExtent[1] = LineChartContext.BRUSH_EXTENT_END;
			}
		}
	}

	// Store the last known brush extent.
	this.lastKnownBrushExtent = lastKnownBrushExtent;
};


/**
 * Updates the context visualization (useful when the data changes or a context for a new value field was selected).
 */
LineChartContext.prototype.updateContextData = function() {
	// Apply the last set color to the context area.
	this.applyColorToContextArea();
	// Fetch the proper data for the context.
	this.initData();
	// Update the scale input domains.
	this.setScaleInputDomains();
	// Bind the new data to the context area element.
	this.bindContextAreaData();
	// Update the axis groups.
	this.updateAxisGroups();
	// Re-apply the last known brush extent so that the user selection is preserved.
	this.applyLastKnownBrushExtent();
};


/**
 * Applies the set color to the context area.
 *
 * @return {Boolean} TRUE if the context area element existed and the color was appplied to it, or false otherwise.
 */
LineChartContext.prototype.applyColorToContextArea = function() {
	if (this.contextArea) {
		this.contextArea.attr("fill", this.color);
		return true;
	}
	else {
		return false;
	}
};


/**
 * Updates the axis groups so they reflect the new input domains when the data has changed.
 */
LineChartContext.prototype.updateAxisGroups = function() {
	this.axisGroup.x.call(this.axis.x);
};


/**
 * Applies the last stored brush extent.
 *
 * This is usefl when the data is updated but the brush selection should stay as before.
 *
 * If the last known brush selection was extending till the start or the end of the context, the selection will "stick" to those ends.
 */
LineChartContext.prototype.applyLastKnownBrushExtent = function() {
	// Get the last known brush extent if any.
	var lastKnownBrushExtent = this.lastKnownBrushExtent;

	// If the last known brush extent is not defined then no brush selection should be applied.
	// or there is no data,
	// 
	if (!lastKnownBrushExtent) {
		return;
	}
	// If there is no data, and there is a last known brush extent, the brush should be cleared.
	else if (!this.data || this.data.length < 1) {
		// Clear the last known brush extent.
		this.lastKnownBrushExtent = null;
		// Clear the brush selection.
		this.brush.clear();
		// Make the brush group pick up the new extent.
		this.brushGroup.call(this.brush);
		return;
	}
	else {
		// Get the start and end date for the last known brush extent.
		var startDate = lastKnownBrushExtent[0];
		var endDate = lastKnownBrushExtent[1];

		// If the start date is a certain constant, that means the brush extent should "stick" to the earliest date available.
		if (startDate == LineChartContext.BRUSH_EXTENT_START) {
			// Get the earliest date from the available data.
			var earliestDate = this.data[0].date || null;
			// Replace the starting point for the last known extent.
			lastKnownBrushExtent[0] = earliestDate;
		}

		// If the end date is a certain constant, that means the brush extent should "stick" to the latest date available.
		if (endDate == LineChartContext.BRUSH_EXTENT_END) {
			// Get the latest date from the available data.
			var latestDate = this.data[this.data.length - 1].date || null;
			// Replace the end point for the last known extent.
			lastKnownBrushExtent[1] = latestDate;
		}

		// Re-fetch the start and end dates as they may have been modified.
		startDate = lastKnownBrushExtent[0];
		endDate = lastKnownBrushExtent[1];

		// If either the start or the end dates were not fetched successfully, do not do anything else.
		if (!startDate || !endDate) {
			return;
		}

		// If the end date is earlier than the start date, make them equal.
		if (startDate.getTime() > endDate.getTime()) {
			endDate = startDate;
			lastKnownBrushExtent[1] = endDate;
		}

		// If the start and end values are not the same,
		// call the brushed event function to apply the necessary changes to the chart.
		if (startDate.getTime() != endDate.getTime()) {
			// Store the modified last known brush extent.
			this.lastKnownBrushExtent = lastKnownBrushExtent;

			// Apply the last known extent.
			this.brush.extent(lastKnownBrushExtent);

			// Call the function that should render the relevant data based on the selected extent.
			this.brushed();
		}
		else {
			// The start and end date are the same, meaning that no brushing should be applied.
			// Clear the last known brush extent.
			this.lastKnownBrushExtent = null;
			// Clear the brush selection.
			this.brush.clear();
		}
		
		// Make the brush group pick up the new extent.
		this.brushGroup.call(this.brush);
	}
};
