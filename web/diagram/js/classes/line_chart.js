/**
 * Class for creating a single or multi value line chart.
 *
 * @param {Object} diagram MeasurementDiagram object that contains the data to be displayed in the line chart.
 */
function LineChart(diagram) {
	// Get a reference to this object. This is necessary in order for some getters to be able to access all the necessary properties of the LineChart object.
	var self = this;

	// Make sure the diagram object is valid.
	if (!diagram.getData() || !diagram.getDataByField()) {
		throw "Invalid diagram. Expected an initialized MeasurementDiagram object.";
	}
	// Store a reference to the MeasurementDiagram object.
	this.diagram = diagram;
	// Store references to the tooltips of the diagram.
	this.tooltip = {
		measurement: diagram.getMeasurementTooltip(),
		legend: diagram.getLegendTooltip()
	};
	// Store a reference to the diagram's legend.
	this.legend = diagram.getLegend();

	// Define the margins and dimensions, taking into account that some of them should not go below a certain minimum.
	// Use D3's margin convention.
	this.sizeValues = {};
	this.sizeValues.margin = {
		top: {
			current: LineChart.startingMarginTop,
			minimum: LineChart.minMarginTop,
		},
		right: {
			current: LineChart.startingMarginRight
		},
		bottom: {
			current: LineChart.minMarginBottom,
			minimum: LineChart.minMarginBottom
		},
		left: {
			current: LineChart.startingMarginLeft
		}
	};
	this.sizeValues.dimension = {
		width: {
			current: LineChart.startingWidth - self.sizeValues.margin.left.current - self.sizeValues.margin.right.current
		},
		height: {
			current: LineChart.startingHeight - self.sizeValues.margin.top.current - self.sizeValues.margin.bottom.current
		}
	};

	// Define getters and setters for the margins.
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
		get top() {
			return self.sizeValues.margin.top.current;
		},
		set top(value) {
			// If there is a defined minimum and the value is less than that, fallback to the minimum value.
			if (self.sizeValues.margin.top.minimum !== undefined && value < self.sizeValues.margin.top.minimum) {
				value = self.sizeValues.margin.top.minimum;
			}

			// Set the value.
			self.sizeValues.margin.top.current = value;
		},
		get right() {
			return self.sizeValues.margin.right.current;
		},
		set right(value) {
			// If there is a defined minimum and the value is less than that, fallback to the minimum value.
			if (self.sizeValues.margin.right.minimum !== undefined && value < self.sizeValues.margin.right.minimum) {
				value = self.sizeValues.margin.right.minimum;
			}

			// Set the value.
			self.sizeValues.margin.right.current = value;
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
		},
		get left() {
			return self.sizeValues.margin.left.current;
		},
		set left(value) {
			// If there is a defined minimum and the value is less than that, fallback to the minimum value.
			if (self.sizeValues.margin.left.minimum !== undefined && value < self.sizeValues.margin.left.minimum) {
				value = self.sizeValues.margin.left.minimum;
			}

			// Set the value.
			self.sizeValues.margin.left.current = value;
		}
	};

	// Define getters and setters for the dimensions.
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
		get width() {
			return self.sizeValues.dimension.width.current;
		},
		set width(value) {
			// If there is a defined minimum and the value is less than that, fallback to the minimum value.
			if (self.sizeValues.dimension.width.minimum !== undefined && value < self.sizeValues.dimension.width.minimum) {
				value = self.sizeValues.dimension.width.minimum;
			}

			// Set the value.
			self.sizeValues.dimension.width.current = value;
		},
		get height() {
			return self.sizeValues.dimension.height.current;
		},
		set height(value) {
			// If there is a defined minimum and the value is less than that, fallback to the minimum value.
			if (self.sizeValues.dimension.height.minimum !== undefined && value < self.sizeValues.dimension.height.minimum) {
				value = self.sizeValues.dimension.height.minimum;
			}

			// Set the value.3
			self.sizeValues.dimension.height.current = value;
		},
		// Define a getter that calculates the aspect x:y aspect ratio.
		get ratioXY() {
			return this.width / this.height;
		},
		// Define getter for calculating the full width (including margins).
		get fullWidth() {
			return this.width + self.margin.right + self.margin.left;
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
			var allowedMarginNames = ['top', 'bottom'];

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
		// Returns the number of scalable components for the line chart.
		get scalableComponentsCount() {
			var scalableComponentIdentifiers = this.scalableHeightComponentIdentifiers;

			var scalableComponentsCount = scalableComponentIdentifiers.dimension.length + scalableComponentIdentifiers.margin.length;

			return scalableComponentsCount;
		}
	};

	// Create a line chart context object.
	this.context = new LineChartContext(this);

	// Define some extra getters that rely on the line chart context object having been created.
	Object.defineProperty(this.dimension,
		"fullHeight",
		{
			get: function () {
				return this.height + self.margin.top + self.context.dimension.fullHeight + self.margin.bottom;
			}
		}
	);
	// Returns the sum of vertical dimensions that are not scalable, both of the chart itself and of its context.
	Object.defineProperty(this.dimension,
		"fullNonScalableHeight",
		{
			get: function () {
				var nonScalableHeight = self.dimension.nonScalableHeight + self.context.dimension.nonScalableHeight;
				return nonScalableHeight;
			}
		}
	);
	// Returns the sum of the scalable components, both of the chart itself and of its context.
	Object.defineProperty(this.dimension,
		"totalScalableComponentsCount",
		{
			get: function () {
				var scalableComponentsCount = self.dimension.scalableComponentsCount + self.context.dimension.scalableComponentsCount;
				return scalableComponentsCount;
			}
		}
	);

	// Define some properties related to the circle (measurement points) displayed in the chart.
	this.circle = {
		radius: 4,
		strokeWidth: 1,
		fillColorNoFocus: "#FFFFFF"
	};

	// Mark that the line chart is not yet fully initialized.
	this.initialized = false;

	// List of other properties being set mostly at the init phase.
	// They are just listed here to make it clearer that they exist.
	this.containerId = null;
	this.svg = null;
	this.scale = null;
	this.axis = null;
	this.lineGenerator = null;
	this.zoom = null;
	this.dataKey = null;
	this.mainGroup = null;
	this.chartAreaGroup = null;
	this.clippingArea = null;
	this.axisGroup = null;
	this.mouseEventsArea = null;
	this.chartDataGroup = null;
	this.fieldGroups = null;
	this.lineGroups = null;
	this.lines = null;
	this.circleGroups = null;
	this.circleWrappers = null;
	this.circles = null;
	this.eventProperty = null;
	this.legendGroup = null;
	this.legendTooltipTrigger = null;
	this.legendValueGroups = null;
	this.legendValuesDataKey = null;
	this.legendValuesData = null;
	this.lastKnownLineChartContextProperties = null;
}

/* Static properties for the class */
LineChart.legendTriggerShowContent = "Show legend";
LineChart.legendTriggerHideContent = "Hide legend";
LineChart.startingWidth = 960;
LineChart.startingHeight = 500;
LineChart.minSvgActualHeight = 200;
LineChart.startingMarginTop = 80;
LineChart.startingMarginRight = 65;
LineChart.startingMarginLeft = 65;
LineChart.minMarginTop = 50;
// Controls the bottom margin of the line chart.
// Useful if no line chart context should be shown below the line chart.
LineChart.minMarginBottom = 0;
// Controls the spacing between the line chart and the line chart context.
LineChart.minPaddingBottom = 35;

/**
 * Initializes and displays a line chart at the specified container.
 * @param {String} containerId A string representing the HTML id of a container element for the line chart. This can optionally contain the '#' character in the beginning.
 */
LineChart.prototype.init = function(containerId) {
	// First assign the container to the object.
	this.assignContainer(containerId);
	// Add the (empty) svg element.
	this.initSvg();
	// Adjust the chart dimensions.
	this.adjustDimensions();
	// Initialize the scales for the chart and set their output ranges.
	this.initScales();
	// Initialize the x and y axes.
	this.initAxes();
	// Initialize the line generator.
	this.initLineGenerator();
	// Do all the relevant functionality that is dependent on the data.
	this.initData();

	// Initialize the line chart context.
	this.initLineChartContext();

	// Store a status signifying the line chart is now initialized and ready for rendering.
	this.initialized = true;
};


/**
 * Performs different actions that are dependent on the data.
 */
LineChart.prototype.initData = function() {
	// Initialize data binding.
	this.initDataBinding();
	// Initialize the legend data binding.
	this.initLegendDataBinding();
	// Set the input domains for the scales.
	this.setScaleInputDomains();
	// Initialize the zoom behavior. This needs to be done after the input domains have been initialized.
	this.initZoom();
	// Set the colors for the values depending on the data.
	this.initColorBinding();	
};


/**
 * Initializes the line chart context object.
 */
LineChart.prototype.initLineChartContext = function() {
	// Get the default line chart context properties object.
	var contextProperties = this.getDefaultLineChartContextProperties();
	if (contextProperties) {
		// Apply the selected field to the context.
		this.useFieldInContext(contextProperties.machineName, contextProperties.color);
	}
};


/**
 * Fetches the first available machine name and its corresponding color to be used for the line chart context.
 *
 * @return {Mixed} A line chart context object consisting of the following properties:
 *   - machineName: The machine name for the field to be displayed in the context.
 *   - color: The color for the field to be displayed in the context.,
 * or null if no grouped data is available.
 */
LineChart.prototype.getDefaultLineChartContextProperties = function() {
	// If there is no data gruped by field name, nothing can be done.
	if (!this.dataByField || this.dataByField.length < 1) {
		return null;
	}

	// Pick the first field group
	var contextFieldGroup = this.dataByField[0];
	var result = {
		machineName: contextFieldGroup.name || null,
		color: this.color(contextFieldGroup.name) || "#000000"
	};

	return result;
};


/**
 * Assigns a container element for the line chart.
 *
 * @param {String} containerId A string representing the HTML id of a container element for the line chart. This can optionally contain the '#' character in the beginning.
 */
LineChart.prototype.assignContainer = function(containerId) {
	// Make sure the containerId is valid.
	if (typeof containerId !== "string" || containerId.length < 1) {
		throw "Invalid containerId: " + containerId + ". Expected a non-empty string.";
	}

	// If the first character of the containerId is not '#', then prepend it.
	if (containerId.charAt(0) !== '#') {
		containerId = '#' + containerId;
	}

	// Store the container id in an object property.
	this.containerId = containerId;
};


/**
 * Appends an svg element to the assigned container for this diagram.
 */
LineChart.prototype.initSvg = function() {
	this.svg = d3.select(this.containerId).append("svg");
};

/**
 * Adjusts the chart dimensions based on the detected browser size.
 */
LineChart.prototype.adjustDimensions = function() {
	var self = this;

	// Get the width of the chart container element.
	var containerWidth = this.getContainerWidth();
	// Calculate the width scaling coefficient.
	var widthScalingCoefficient = containerWidth / this.dimension.fullWidth;
	// Calculate the actual height of the svg.
	var svgActualHeight = widthScalingCoefficient * this.dimension.fullHeight;

	// Get the browser height.
	var browserHeight = $( window ).height();

	// Add some extra rules for android devices, due to a problem with Firefox for Android, where the window height is not properly determined most of the time.
	var userAgent = navigator.userAgent.toLowerCase();
	var isAndroid = userAgent.indexOf("android") > -1; //&& ua.indexOf("mobile");
	if (isAndroid) {
		browserHeight = Math.min(browserHeight, window.innerHeight, window.outerHeight);
	}
	// Get the y position of the diagram-container element. This will be used to calculate how much space can be reserved for the diagram based on the browser's height
	var diagramContainerY = $( this.containerId ).offset().top;

	// If the browser height and the y-coordinate for the diagram container were fetched successfully, then calculate the target svg height based on that.
	var targetSvgActualHeight;
	if (!isNaN(browserHeight) && !isNaN(diagramContainerY)) {
		// The svg height should fit on the screen without any vertical scrolling.
		targetSvgActualHeight = browserHeight - diagramContainerY - 5;
	}

	// If the svg height was not calculated successfully or it is too small, then fallback to some minimum value.
	var minSvgActualHeight = LineChart.minSvgActualHeight;
	if (!targetSvgActualHeight || targetSvgActualHeight < minSvgActualHeight) {
		targetSvgActualHeight = minSvgActualHeight;
	}

	// Calculate the target SVG height that is not in actual pixels but in SVG measurement units, which are relative and possibly scaled up or down in reality.
	var targetSvgHeight = targetSvgActualHeight / widthScalingCoefficient;

	var scalableComponentsCount = 0;
	// Define a maximum number of re-calculations. The chart's height should be quite well adjusted after that many iterations.
	var maxReadjustments = 5;
	var readjustmentIteration = 0;
	do {
		// Get the total height of the elements that are not scalable vertically.
		var nonScalableHeight = this.dimension.fullNonScalableHeight;
		// Get the total height of the elements that are scalable.
		var scalableHeight = this.dimension.fullHeight - nonScalableHeight;
		
		// If the target svg height is less than the non-scalable height, then increase the target svg height
		// in order to try to fit all the necessary components.
		if (targetSvgHeight < nonScalableHeight) {
			targetSvgHeight = nonScalableHeight + 1;
		}

		// Calculate the multiplication coefficient for the height and margins.
		// Keep into consideration that there are some non-scalable dimensions, so the target height for the scalable elements is smaller than the targetSvgActualHeight.
		var verticalMultiplicationCoefficient = (targetSvgHeight - nonScalableHeight) / scalableHeight;

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

		// Adjust the dimensions for the line chart's context.
		this.context.adjustDimensions(verticalMultiplicationCoefficient);

		readjustmentIteration++;
		if (readjustmentIteration == maxReadjustments) {
			// Do not do any more readjustments.
			// The chart should be scaled well enough, it is better to avoid any further CPU costs.
			break;
		}

		// Get the scalable components from the line chart context.
		var contextScalableComponentIdentifiers = this.context.dimension.scalableHeightComponentIdentifiers;
		// Get the total number of scalable components left.
		scalableComponentsCount = this.dimension.totalScalableComponentsCount;
	}
	while (this.dimension.fullHeight > targetSvgHeight && scalableComponentsCount > 0);

	// Calculate the padding-top percentage for the diagram container based on the suggested method in http://tympanus.net/codrops/2014/08/19/making-svgs-responsive-with-css/
	var paddingTopPercentage = (this.dimension.fullHeight / this.dimension.fullWidth) * 100;
	// Adjust the padding-top of the container.
	d3.select(this.containerId)
		.style("padding-top", paddingTopPercentage + "%");
};


/**
 * Initializes the x and y scales and sets the output range for them.
 */
LineChart.prototype.initScales = function() {
	// Define the horizontal scale that represents the time.
	// The output range can vary between 0 (leftmost part of the chart) and width (rightmost part of the chart).
	var xScale = d3.time.scale()
		.range([0, this.dimension.width]);

	// The vertical scale does not represent a specific type of value so it should be linear.
	// The output range will be between 0 and height.
	var yScale = d3.scale.linear()
		.range([this.dimension.height, 0]);

	// Store the scales to a property of the LineChart object so they are accessible from other methods.
	this.scale = {
		x: xScale,
		y: yScale
	};
};


/**
 * Initializes the x and y axes for the chart depending on the defined scales.
 */
LineChart.prototype.initAxes = function() {
	// The horizontal axis should scale according to the defined xScale.
	// It should have the labels displayed below it. Specify also the approximate amount of ticks.
	var xAxisTicks = 5;
	var xAxis = d3.svg.axis()
		.scale(this.scale.x)
		.orient("bottom")
		.ticks(xAxisTicks);

	// Define the yAxis similar to the xAxis. The difference is that the axis is vertical and the labels should be displayed to the left of it.
	var yAxisTicks = 5;
	var yAxis = d3.svg.axis()
		.scale(this.scale.y)
		.orient("left")
		.ticks(yAxisTicks);

	// Store the axes to a property of the LineChart object so they are accessible from other methods.
	this.axis = {
		x: xAxis,
		y: yAxis
	};
};


/**
 * Initializes a generator for the lines in the line chart/
 */
LineChart.prototype.initLineGenerator = function() {
	var self = this;

	// Define the line interpolation type.
	var lineType = "linear";
	// If the fieldValue is null at some places, the line should be discontinuous. Define a function to check this.
	var lineIsDefined = function(d) {
		return d.fieldValue != null;
	};
	// Define the line generator.
	// Allow for discontinuous lines using the 'defined' method.
	var lineGenerator = d3.svg.line()
		.interpolate(lineType)
		.defined(lineIsDefined)
		.x(function(d) {
			// Determine the horizontal position for this point based on the date in this datum and the defined xScale.
			return self.scale.x(d.date);
		})
		.y(function(d) {
			// Determine the vertical position for this point based on the fieldValue in this datum and the defined yScale.
			return self.scale.y(d.fieldValue);
		});

	// Make the line generator accessible from other methods in the line chart class.
	this.lineGenerator = lineGenerator;
};


/**
 * Initializes a behavior for zooming and panning the chart.
 */
LineChart.prototype.initZoom = function() {
	var minScaleExtent = 1;
	var maxScaleExtent = 32;

	// Create a zoom behavior object that will handle everything related to zooming and panning.
	this.zoom = new ZoomBehavior(minScaleExtent, maxScaleExtent, this.diagram);
	// Initialize the zoom behavior.
	this.zoom.init();
};

/**
 * Defines a key function for data binding to DOM elements and defines some getter functions for easier fetching of the data defined in the parent measurement diagram.
 */
LineChart.prototype.initDataBinding = function() {
	// Define a key function for binding the data to DOM elements.
	this.dataKey = function(d) {
		return d.name;
	};

	// Define getters for easier fetching of the measurement diagram's data.
	Object.defineProperty(this,
		"data",
		{ get: function () { return this.diagram.getData(); } }
	);
	Object.defineProperty(this,
		"dataByField",
		{ get: function () { return this.diagram.getDataByField(); } }
	);
};


/**
 * Initializes the key function for binding data to the legend values.
 * Initializes the legend values data.
 */
LineChart.prototype.initLegendDataBinding = function() {
	// Define a key function for binding the data to DOM.
	this.legendValuesDataKey = function(d) {
		return d.name;
	};

	/**
	 * Fetches the legend's data that should be shown in the legend of the line chart.
	 * @return {Array} An array of objects, each containing the following properties:
	 *   'name' - The value's name.
	 *   'color' - The value's color.
	 *   'machineName' - The value's machine name.
	 * based on the legend's valueSet.
	 * Returns an empty array if no legend values should be shown directly (e.g. when they should be grouped in a legend tooltip).
	 */
	var getLegendValuesData = function() {
		var legendValueSet = this.legend.getIterableValueSet();

		// The legend data needs to be an array (of objects).
		var legendData = [];

		for (var valueName in legendValueSet) {
			// Attempt to fetch the proper machine name for this field name.
			var machineName = null;
			if (this.dataByField) {
				for (var i = 0; i < this.dataByField.length; i++) {
					if (this.dataByField[i].name == valueName) {
						machineName = this.dataByField[i].field;
						// The corresponding machine name was found, no need to search through the rest of the data field value groups.
						break;
					}
				}
			}

			var datum = {
				name: valueName,
				color: legendValueSet[valueName],
				machineName: machineName
			};

			legendData.push(datum);
		}

		// TODO: return an empty object if no legend should be shown in the line chart.
		return legendData;
	};

	// Define a getter for the legend values data.
	Object.defineProperty(this,
		"legendValuesData",
		{ get: getLegendValuesData }
	);
};


/**
 * Initializes a getter for the color scale.
 */
LineChart.prototype.initColorBinding = function() {
	// Define a getter for easier fetching of the measurement diagram's color scale.
	Object.defineProperty(this,
		"color",
		{ get: function () { return this.diagram.getColorScale(); }}
	);
};



/**
 * Sets the input domains for the x and y scales based on the data.
 */
LineChart.prototype.setScaleInputDomains = function() {
	// Define the input domain for the xScale depending on the date field.
	// Since the data grouped by field is used here, it is not possible to use directly d3.extent.
	// The min and max should be found separately and they should be searched from the values field of each data group.
	this.scale.x.domain([
		d3.min(this.dataByField, function(dbf) {
			return d3.min(dbf.values, function(d) {
				return d.date;
			});
		}),

		d3.max(this.dataByField, function(dbf) {
			return d3.max(dbf.values, function(d) {
				return d.date;
			});
		})
	]);

	// Define the input domain for the yScale similar to the xScale but depending on the min and max of the fieldValue.
	this.scale.y.domain([
		d3.min(this.dataByField, function(dbf) {
			return d3.min(dbf.values, function(d) {
				return d.fieldValue;
			});
		}),

		d3.max(this.dataByField, function(dbf) {
			return d3.max(dbf.values, function(d) {
				return d.fieldValue;
			});
		})
	]);
};


/**
 * Renders the data in an initialized line chart.
 */
LineChart.prototype.renderData = function() {
	if (!this.initialized) {
		throw "Cannot render data in an uninitialized line chart.";
	}

	// Set the viewBox attribute for the svg.
	this.setViewBox();

	// Add the main g element to the svg.
	this.appendMainGroup();
	// Append a legend g element to the svg.
	this.appendLegendGroup();

	// Append axis groups holding the axis labels, etc.
	this.appendAxisGroups();
	// Append a chart area group holding pretty much all the other chart elements within the chart area but excluding the axes.
	this.appendChartAreaGroup();
	// Add clipping areas so that certain elements do not appear outside of the chart area.
	this.appendClippingAreas();
	// Append an area that will help with registering mouse events.
	this.appendMouseEventsArea();
	// Append the chart data elements.
	this.appendChartData();

	// Append the legend tooltip trigger element.
	this.appendLegendTooltipTrigger();
	// Append the legend value groups.
	this.appendLegendValueGroups();

	// Render the line chart context.
	this.context.renderData();
};


/**
 * Sets the viewBox attribute of the SVG based on the defined or calculated width and height.
 */
LineChart.prototype.setViewBox = function() {
	// The width and height should include the margins.
	var viewBoxWidth = this.dimension.fullWidth;
	var viewBoxHeight = this.dimension.fullHeight;

	this.svg.attr("viewBox", "0 0 " + viewBoxWidth + " " + viewBoxHeight);
};


/**
 * Appends a main g (group) element to the svg.
 */
LineChart.prototype.appendMainGroup = function() {
	// Append a g element to the svg and move it based on the margins.
	var mainGroupTranslate = "translate(" + this.margin.left + ", " + this.margin.top + ")";
	this.mainGroup = this.svg.append("g")
		.attr("id", "mainGroup")
		.attr("transform", mainGroupTranslate);
};


/**
 * Appends a legend g (group) element to the svg.
 */
LineChart.prototype.appendLegendGroup = function() {
	// Append a g element to the svg and move it based on the left margin.
	var legendGroupTranslate = "translate(" + this.margin.left + ", " + 0+ ")";
	this.legendGroup = this.svg.append("g")
		.attr("id", "legendGroup")
		.attr("transform", legendGroupTranslate);
};


/**
 * Appends clipPath (clipping areas) to the chart that prevent certain elements from being rendered outside of the desired space.
 */
LineChart.prototype.appendClippingAreas = function() {
	// Define an area outside of which the lines should be clipped.
	var lineClippingAreaId = "clip-lines";
	var lineClippingArea = this.mainGroup.append("clipPath")
		.attr("id", lineClippingAreaId);
	lineClippingArea.append("rect")
		.attr("x", 0)
		.attr("y", 0)
		.attr("width", this.dimension.width)
		.attr("height", this.dimension.height);

	// Define an area outside of which the circles should be clipped.
	// This differs from the clipping area for the lines since it's nicer to always show full circles in the diagram.
	var circleClippingAreaId = "clip-circles";
	var circleClippingArea = this.mainGroup.append("clipPath")
		.attr("id", circleClippingAreaId);
	circleClippingArea.append("rect")
		.attr("x", -(this.circle.radius + this.circle.strokeWidth))
		.attr("y", -(this.circle.radius + this.circle.strokeWidth))
		.attr("width", this.dimension.width + 2 * (this.circle.radius + this.circle.strokeWidth))
		.attr("height", this.dimension.height + 2 * (this.circle.radius + this.circle.strokeWidth));

	var legendClippingAreaId = "clip-legend";
	var legendClippingArea = this.legendGroup.append("clipPath")
		.attr("id", legendClippingAreaId)
		.attr("class", "legend-value-clipping");
	legendClippingArea.append("rect")
		.attr("x", 0)
		.attr("y", 0)
		.attr("width", this.dimension.width)
		.attr("height", this.margin.top);

	// Register the clipping areas as properties of the line chart.
	this.clippingArea = {
		line: {
			id: ("#" + lineClippingAreaId),
			element: lineClippingArea,
		},
		circle: {
			id: ("#" + circleClippingAreaId),
			element: circleClippingArea
		},
		legend: {
			id: ("#" + legendClippingAreaId),
			element: legendClippingArea
		}
	};
};



/**
 * Appends svg g elements that will group axis elements, such as labels.
 */
LineChart.prototype.appendAxisGroups = function() {
	// Append a g element representing the xAxis.
	// Put it right below the chart area.
	var xAxisGroup = this.mainGroup.append("g")
		.attr("class", "x axis")
		.attr("transform", "translate(0, " + this.dimension.height + ")")
		.call(this.axis.x);

	// Append also a label for the x axis.
	// Position it above the axis but towards its end.
	xAxisGroup.append("text")
		.attr("class", "axis-label")
		.attr("x", this.dimension.width)
		.attr("dx", "-.71em") // Offset the text a bit to the left
		.attr("dy", "-.71em") // Ofset the text a bit upwards
		.attr("text-anchor", "end")
		.text("Time");

	// Append a g element representing the yAxis.
	// Put it to the left of the chart area.
	var yAxisGroup = this.mainGroup.append("g")
		.attr("class", "y axis")
		.call(this.axis.y);

	// Register the axis groups as properties of the line chart.
	this.axisGroup = {
		x: xAxisGroup,
		y: yAxisGroup
	};
};


/**
 * Appends an svg g element that will hold elements in the chart area (such as lines and circles) but excluding the axes.
 */
LineChart.prototype.appendChartAreaGroup = function() {
	this.chartAreaGroup = this.mainGroup.append("g")
		.attr("id", "chart-area-group");
};


/**
 * Appends a rectangle to the line chart that will help in registering mouse events.
 */
LineChart.prototype.appendMouseEventsArea = function() {
	// Add a rectangle representing the area that will register mouse events.
	// Additionally. register the zoom behavior for that area.
	this.mouseEventsArea = this.chartAreaGroup.append("rect")
		.attr("id", "mouse-events-area")
		.attr("class", "pane")
		.attr("width", this.dimension.width)
		.attr("height", this.dimension.height)
		.attr("fill", "none")
		.style("pointer-events", "all")
		.call(this.zoom.getBehavior());
};


/**
 * Appends SVG elements that represent the chart data.
 */
LineChart.prototype.appendChartData = function() {
	// Append a group that will hold the chart data representation.
	this.appendChartDataGroup();
	// Append groups that will hold the data representation for each value field in the chart.
	// This will trigger appending of all the necessary child elements.
	this.appendFieldGroups();
};


/**
 * Appends an svg g element that will hold the elements representing the chart data.
 */
LineChart.prototype.appendChartDataGroup = function() {
	// Add a group that will hold the lines and the points representing the data.
	this.chartDataGroup = this.chartAreaGroup.append("g")
		.attr("class", "chart-data");
};


/**
 * Appends svg g elements for each value field group. The total number depends on the dataByField grouping.
 * Once the data is bound, this will trigger appending of child elements.
 */
LineChart.prototype.appendFieldGroups = function() {
	// Select elements for each field group.
	// This will return a selector even if there are no field groups added yet.
	var fieldGroups = this.chartDataGroup.selectAll("g.field-group");
	// Register the field groups as a property of the line chart.
	this.fieldGroups = fieldGroups;

	// Bind data to the field groups. This may trigger removal or appending of child elements.
	this.bindFieldGroupsData();
};


/**
 * Binds data to the field groups of the chart.
 * Handles appending and removal of child elements based on the bound data.
 */
LineChart.prototype.bindFieldGroupsData = function() {
	var self = this;

	this.fieldGroups = this.fieldGroups.data(this.dataByField, this.dataKey);

	// Define what should happen if there is no field-group  for some datum.
	var handleFieldGroupEnter = function(fieldGroupSelection) {
		// Optimization: if the selector is empty, do not attempt to append any elements.
		if (fieldGroupSelection.empty()) {
			return;
		}

		// Append a field-group g element.
		fieldGroupSelection = fieldGroupSelection
			.append("g")
			.attr("class", "field-group");

		// Append the line groups that will hold the lines.
		var appendedLineGroups = self.appendLineGroups(fieldGroupSelection);
		// Append the lines representing the actual data.
		self.appendLines(appendedLineGroups);

		// Append groups that will hold the circles (measurement points) in the chart.
		var appendedCircleGroups = self.appendCircleGroups(fieldGroupSelection);
		// Append circle wrappers which are needed for proper geometric scaling.
		var appendedCircleWrappers = self.appendCircleWrappers(appendedCircleGroups);
		// Append the circles.
		var appendedCircles = self.appendCircles(appendedCircleWrappers);

		// Register event listeners for the elements.
		self.registerChartDataEventListeners(fieldGroupSelection, appendedCircles);
	};

	this.fieldGroups.enter().call(handleFieldGroupEnter);

	// Define what should happen if there is an unnecessary field-group for some datum.
	var handleFieldGroupExit = function(fieldGroupSelection) {
		// Optimization: If the selection is empty, do not attempt to remove any elements.
		if (fieldGroupSelection.empty()) {
			return;
		}

		// Remove the field group and all its child elements.
		fieldGroupSelection.remove();

		// Update the stored selection properties.
		self.updateSelectionProperties();

		// Update the legend's valueSet.
		self.updateLegendValueSet();
	};

	this.fieldGroups.exit().call(handleFieldGroupExit);
};


/**
 * Appends svg g elements that will hold the lines in the chart.
 *
 * @param {[Selection Object]} fieldGroupSelection If specified, the line groups will be appended only to that selection, instead of all the field-groups.
 * @return {Selection Object} The selection for the line groups that were just appended.
 */
LineChart.prototype.appendLineGroups = function(fieldGroupSelection) {
	// If no field group selection was passed as a parameter, append the line groups to all the field-groups.
	if (typeof fieldGroupSelection !== 'object') {
		fieldGroupSelection = this.fieldGroups;
	}

	// Define a line group that will hold the path elements showing the measurement data.
	// These are useful e.g. for dot highlighting.
	var appendedLineGroups = fieldGroupSelection.append("g")
		.attr("class", "line-group")
		.attr("clip-path", "url(" + this.clippingArea.line.id + ")");

	// Update the selection property for all line groups.
	this.updateLineGroupsSelection();

	// Update the legend's value set based on the new line groups selection.
	this.updateLegendValueSet();

	return appendedLineGroups;
};


/**
 * Appends lines (svg path elements) that represent the chart data.
 *
 * @param {[Selection Object]} lineGroupSelection If specified, the lines will be appended only to that selection, instead of all the line-groups.
 * @return {Selection Object} The selection for the lines that were just appended.
 */
LineChart.prototype.appendLines = function (lineGroupSelection) {
	var self = this;

	// If no line group selection was passed as a parameter, append the lines to all the line-groups.
	if (typeof lineGroupSelection !== 'object') {
		lineGroupSelection = this.lineGroups;
	}

	// Append a path for each of the line-group elements.
	// Define a d svg attribute for the path based on the datum values.
	var appendedLines = lineGroupSelection.append("path")
		.attr("class", "line")
		.attr("d", function(d) {
			return self.lineGenerator(d.values);
		})
		.style("stroke", function(d) {
			return self.color(d.name);
		});

	// Update the selection property for all lines.
	this.updateLinesSelection();

	// Return only the appended lines.
	return appendedLines;
};


/**
 * Appends svg g elements that will hold the circles (measurement points) in the chart.
 *
 * @param {[Selection Object]} fieldGroupSelection If specified, the circle groups will be appended only to that selection, instead of all the field-groups.
 * @return {Selection Object} The selection for the circle groups that were just appended.
 */
LineChart.prototype.appendCircleGroups = function(fieldGroupSelection) {

	// If no field group selection was passed as a parameter, append the circle groups to all the field-groups.
	if (typeof fieldGroupSelection !== 'object') {
		fieldGroupSelection = this.fieldGroups;
	}

	// Define circle groups that will hold the measurement points in the chart.
	// These are useful e.g. for dot highlighting.
	var appendedCircleGroups = fieldGroupSelection.append("g")
		.attr("class", "circle-group")
		.attr("clip-path", "url(" + this.clippingArea.circle.id + ")")
		.style("display", "none")
		// Also register the zoom for the dots since otherwise
		// it is not possible to zoom when the mouse is over them.
		.call(this.zoom.getBehavior());

	// Update the selection property for all circle groups and rebind the data.
	this.updateCircleGroupsSelectionAndRebindData();

	// Return only the appended circle-groups.
	return appendedCircleGroups;
};


/**
 * Binds data to the circle groups. This is useful e.g. in order to display the correct tooltip when hovering over circles.
 *
 * NOTE: This will also re-select the circles, as they should inherit the data bound to the circle groups.
 */
LineChart.prototype.bindCircleGroupsData = function() {
	var self = this;

	this.circleGroups.datum(function(d, i) {
		// Wrap the datum d in another object
		// that will hold the current index for the point
		// that is being highlighted.
		var wrapperDatum = {
			currentIndex: -1,
			datum: self.dataByField[i]
		};

		return wrapperDatum;
	});

	// Update the circle wrappers selection, so that it inherits data from the circle groups.
	this.updateCircleWrappersSelection();

	// Update the circles selection, so that it inherits the data from the circle wrappers.
	this.updateCirclesSelection();
};


LineChart.prototype.appendCircleWrappers = function(circleGroupSelection) {

	// If no circle group selection was passed as a parameter, append the circle wrappers to all the circle groups.
	if (typeof circleGroupSelection !== 'object') {
		circleGroupSelection = this.circleGroups;
	}

	// Append a circle wrapper for each circle group.
	var appendedCircleWrappers = circleGroupSelection.append("g")
		.attr("class", "circle-wrapper");

	// Update the circle wrapper selection property.
	this.updateCircleWrappersSelection();

	// Return only the appended circle wrappers.
	return appendedCircleWrappers;
};


/**
 * Appends an svg circle element for each circle group (i.e. one circle per field group).
 *
 * @param {[Selection Object]} circleWrapperSelection If specified, the circles will be appended only to that selection, instead of all the circle wrappers.
 * @return {Selection Object} The selection for the circles that were just appended.
 */
LineChart.prototype.appendCircles = function(circleWrapperSelection) {
	var self = this;

	// If no circle group selection was passed as a parameter, append the circles to all the circle groups.
	if (typeof circleWrapperSelection !== 'object') {
		circleWrapperSelection = this.circleWrappers;
	}

	// Append a circle for each circle group (one circle per circle group).
	var appendedCircles = circleWrapperSelection.append("circle")
		.attr("class", "dot")
		.attr("r", this.circle.radius)
		.style("stroke", function(d) {
			// Fetch the color for this line field.
			return self.color(d.datum.name);
		})
		.attr("fill", this.circle.fillColorNoFocus);

	// Update the selection property for all circles.
	this.updateCirclesSelection();

	// Return only the appended circles.
	return appendedCircles;
};


/**
 * Appends svg g elements for each element in the legend's valueSet.
 * Once the data is bound, this will trigger appending of child elements.
 */
LineChart.prototype.appendLegendValueGroups = function() {
	// Select elements for each legend value group.
	// This will return a selector even if there are no legend value groups added yet.
	var legendValueGroups = this.legendGroup.selectAll("g.legend-value-group");
	// Register the legend value groups as a property of the line chart.
	this.legendValueGroups = legendValueGroups;

	var legendValueSet = this.legend.getValueSet();

	// Check if the data needs to be cleared.
	var clearData = false;
	// If the legend values exceed the amount of allowed values, then clear them.
	if (legendValueSet.getLength() > this.getMaximumAllowedLegendValues()) {
		clearData = true;
	}

	// Bind data to the legend value groups. This may trigger removal or appending of child elements.
	this.bindLegendValueGroupsData(clearData);
};


/**
 * Binds data to the legend value groups of the chart.
 * Handles appending and removal of child elements based on the bound data.
 * @param {[Boolean]} clearValues If set to true, the legend value groups will be cleared as a result of assigning empty data.
 */
LineChart.prototype.bindLegendValueGroupsData = function(clearValues) {
	var self = this;

	var legendValuesData = this.legendValuesData;
	// If it was selected that the values should be cleared, use empty data array.
	if (clearValues) {
		legendValuesData = [];
	}

	this.legendValueGroups = this.legendValueGroups.data(legendValuesData, this.legendValuesDataKey);

	// Define what should happen if there is no legend value group for some datum.
	var handleLegendValueGroupEnter = function(legendValueGroupSelection) {
		// Optimization: if the selector is empty, do not attempt to append any elements.
		if (legendValueGroupSelection.empty()) {
			return;
		}

		// Append a legend value group g element.
		legendValueGroupSelection = legendValueGroupSelection
			.append("g")
			.attr("class", "legend-value-group")
			.attr("clip-path", "url(" + self.clippingArea.legend.id +  ")");

		// Append legend values for the newly added legend value groups.
		self.appendLegendValues(legendValueGroupSelection);
	};

	this.legendValueGroups.enter().call(handleLegendValueGroupEnter);

	// Define what should happen if there is an unnecessary legend value group for some datum.
	var handleLegendValueGroupExit = function(legendValueGroupSelection) {
		// Optimization: If the selection is empty, do not attempt to remove any elements.
		if (legendValueGroupSelection.empty()) {
			return;
		}

		// Remove the legend value group and all its child elements.
		legendValueGroupSelection.remove();
		// Re-apply the properties for any value elements still left.
		self.refreshLegendValueProperties();
	};

	this.legendValueGroups.exit().call(handleLegendValueGroupExit);
};


/**
 * Defines functions for setting properties to legend elements.
 *
 * @return {Object} An object containing the following methods:
 *   'setRectangleProperties'
 *   'setClippingAreaRectangleProperties'
 *   'setTextProperties'
 *   'setLegendTooltipTriggerProperties'
 */
LineChart.prototype.getLegendPropertyUtilityFunctions = function() {
	var self = this;

	// Define the left and right padding between the rectangles and the text elements.
	var horizontalPadding = 5;

	// Calculate the height for the color rectangles.
	var rectangleHeight1 = Math.floor(this.margin.top / 3);
	// Calculate the width for the color rectangles.
	var rectangleWidth1 = rectangleHeight1 * 2;

	// In case the chart width is too small but its height is large, the rectangle dimensions should be calculated differently.
	var rectangleWidth2 = Math.floor(this.dimension.width / (2 * this.getMaximumAllowedLegendValues()));
	var rectangleHeight2 = Math.floor(rectangleWidth2 / 2);

	// Select the minimum of both of the proposed rectangle widths and heights.
	var rectangleWidth = Math.min(rectangleWidth1, rectangleWidth2);
	var rectangleHeight = Math.min(rectangleHeight1, rectangleHeight2);

	// Calculate the vertical position for the color rectangles.
	var rectangleY = Math.floor((this.margin.top - rectangleHeight) / 2);

	// Calculate the vertical position for the value name text elements.
	var textY = rectangleY + rectangleHeight;
	// Calculate the maximum allowed space for text elements.
	var textMaxWidth = Math.floor(this.dimension.width / this.getMaximumAllowedLegendValues()) - rectangleWidth - horizontalPadding;

	// Calculate the height for the text clipping areas.
	var clippingAreaHeight = rectangleHeight + rectangleY;
	// Calculate the width for the text clipping areas.
	var clippingAreaWidth = textMaxWidth - horizontalPadding;

	// Calculate the ending x-coordinate for the legend tooltip trigger element.
	var legendTooltipTriggerX = this.dimension.width;

	// Define a function for calculating the horizontal position for color rectangles.
	var rectangleX = function(i) {
		var result = (rectangleWidth + horizontalPadding + textMaxWidth) * i;
		return result;
	};
	// Define a function for calculating the horizontal position for the value name text elements.
	var textX = function(i) {
		var result = rectangleX(i) + rectangleWidth + horizontalPadding;
		return result;
	};

	// Define a function for setting the rectangle properties.
	var setRectangleProperties = function(rectangleSelection, d, i) {
		rectangleSelection
			.attr("class", "legend-value-color")
			.attr("width", rectangleWidth)
			.attr("height", rectangleHeight)
			.attr("x", rectangleX(i))
			.attr("y", rectangleY)
			.attr("stroke", "black")
			.attr("fill", d.color);
	};

	// Define a function for setting the clipping area rectangle properties.
	var setClippingAreaRectangleProperties = function(clippingAreaRectangleSelection, d, i) {
		clippingAreaRectangleSelection
			.attr("x", textX(i))
			.attr("y", rectangleY)
			.attr("width", clippingAreaWidth)
			.attr("height", clippingAreaHeight);
	};

	// Define a function for setting the value name text properties.
	var setTextProperties = function(textSelection, clippingAreaId, d, i) {
		textSelection
			.attr("class", "legend-value-name")
			.attr("clip-path", "url(#" + clippingAreaId +  ")")
			.attr("x", textX(i))
			.attr("y", textY)
			.text(d.name);
	};

	// Define a function for setting the legend tooltip trigger properties.
	var setLegendTooltipTriggerProperties = function(legendTooltipTriggerSelection, visible, expanded) {
		// If the legend tooltip trigger should be visible, adjust the display setting accordingly.
		var display = "none";
		if (visible) {
			display = null;
		}
		// If the legend tooltip has been expanded (shown), adjust the text accordingly.
		if (typeof expanded === 'undefined') {
			// It hasn't been specified whether the legend tooltip has been expanded.
			// Check that directly.
			if (!self.tooltip.legend) {
				// No legend tooltip exists yet.
				expanded = false;
			}
			else {
				// Check if the legend tooltip is visible.
				expanded = self.tooltip.legend.isVisible();
			}
		}
		var textContent = LineChart.legendTriggerShowContent;
		if (expanded) {
			textContent = LineChart.legendTriggerHideContent;
		}

		legendTooltipTriggerSelection
			.attr("id", "legend-tooltip-trigger")
			.attr("x", legendTooltipTriggerX)
			.attr("y", textY)
			.attr("text-anchor", "end")
			.attr("display", display)
			.text(textContent);
	};

	var result = {
		setRectangleProperties: setRectangleProperties,
		setClippingAreaRectangleProperties: setClippingAreaRectangleProperties,
		setTextProperties: setTextProperties,
		setLegendTooltipTriggerProperties: setLegendTooltipTriggerProperties
	};

	return result;
};


/**
 * Appends a legend tooltip trigger element. By default it is hidden and non-expanded.
 */
LineChart.prototype.appendLegendTooltipTrigger = function() {
	// Get utility functions for adjusting the legend elements' properties.
	var utils = this.getLegendPropertyUtilityFunctions();

	// Append the legend tooltip trigger to the legend group.
	var legendTooltipTrigger = this.legendGroup.append("text");

	// Register the legend tooltip trigger as a property of the line chart.
	this.legendTooltipTrigger = legendTooltipTrigger;

	// Apply some properties to the tooltip trigger element.
	utils.setLegendTooltipTriggerProperties(this.legendTooltipTrigger);

	var legendValueSet = this.legend.getValueSet();

	// If the legend values exceed the amount of allowed values.
	if (legendValueSet.getLength() > this.getMaximumAllowedLegendValues()) {
		// Show the legend tooltip trigger.
		this.showLegendTooltipTrigger();
	}
};


/**
 * Shows the legend tooltip trigger element.
 */
LineChart.prototype.showLegendTooltipTrigger = function() {
	this.legendTooltipTrigger
		.attr("display", null);
};


/**
 * Hides the legend tooltip trigger element.
 */
LineChart.prototype.hideLegendTooltipTrigger = function() {
	this.legendTooltipTrigger
		.attr("display", "none");
};


/**
 * Expands the legend tooltip and adjusts the legend tooltip trigger's text base on that.
 */
LineChart.prototype.expandLegendTooltip = function() {
	var textContent = LineChart.legendTriggerHideContent;

	this.legendTooltipTrigger
		.text(textContent);

	this.tooltip.legend.show();

	// Set the position for the legend tooltip.
	this.tooltip.legend.setPosition("right-15 top+20", $( "#legend-tooltip-trigger" ));
};


/**
 * Collapses the legend tooltip and adjusts the legend tooltip trigger's text base on that.
 */
LineChart.prototype.collapseLegendTooltip = function() {
	var textContent = LineChart.legendTriggerShowContent;

	this.legendTooltipTrigger
		.text(textContent);

	this.tooltip.legend.hide();
};


/**
 * Hides or collapses the legend depending on the last known state.
 */
LineChart.prototype.triggerLegendTooltipExpandOrCollapse = function() {
	var textContent = this.legendTooltipTrigger.text() || "";

	// If the tooltip trigger displays a "hide" button, assume the legend is expanded.
	if (textContent == LineChart.legendTriggerHideContent) {
		// Collapse the legend.
		this.collapseLegendTooltip();
	}
	else {
		// Expand the legend.
		this.expandLegendTooltip();
	}
};


/**
 * Re-calculates the positions for the existing legend value elements and then appends the newly added ones.
 * @param  {[Selection Object]} legendValueGroupSelection If specified, the legend values will be appended only to that selection, instead of all the legend value groups.
 */
LineChart.prototype.appendLegendValues = function (legendValueGroupSelection) {

	// If no legend value group selection was passed as a parameter, append the legend values to all the legend value groups.
	if (typeof legendValueGroupSelection !== 'object') {
		legendValueGroupSelection = this.legendValueGroups;
	}

	// Get utility functions for adjusting the legend value elements' properties.
	var utils = this.getLegendPropertyUtilityFunctions();

	// First refresh the properties of the existing legend values.
	this.refreshLegendValueProperties();

	// Now go through the newly added legend value groups.
	// For each legend value group, append two elements: a rectangle with the color and a text with the value name.
	legendValueGroupSelection.each(function(d, i) {
		var legendValueGroup = d3.select(this);
		var rectangleSelection = legendValueGroup.append("rect");
		utils.setRectangleProperties(rectangleSelection, d, i);

		// Append a clipping area for the text, in case it doesn't fit in the allocated space.
		var clippingAreaId = "clip-legend-value-name-" + Util.sanitizeNameForId(d.name);
		var clippingAreaRectangleSelection = legendValueGroup.append("clipPath")
			.attr("id", clippingAreaId)
			.attr("class", "legend-value-clipping")
			.append("rect");
		utils.setClippingAreaRectangleProperties(clippingAreaRectangleSelection, d, i);

		var textSelection = legendValueGroup.append("text");
		utils.setTextProperties(textSelection, clippingAreaId, d, i);
	});
};


/**
 * Re-calculates and applies the properties for all the existing legend value elements.
 */
LineChart.prototype.refreshLegendValueProperties = function() {
	// Get utility functions for adjusting the legend value elements' properties.
	var utils = this.getLegendPropertyUtilityFunctions();

	// Apply the properties to the elements in the existing value groups.
	this.legendValueGroups.each(function(d, i) {
		var legendValueGroup = d3.select(this);
		var rectangleSelection = legendValueGroup.select("rect.legend-value-color");
		utils.setRectangleProperties(rectangleSelection, d, i);

		var clippingAreaId = "clip-legend-value-name-" + d.name;
		var clippingAreaRectangleSelection = legendValueGroup.select(".legend-value-clipping rect");
		utils.setClippingAreaRectangleProperties(clippingAreaRectangleSelection, d, i);

		var textSelection = legendValueGroup.select("text.legend-value-name");
		utils.setTextProperties(textSelection, clippingAreaId, d, i);
	});
};


/**
 * Clears the svg of the chart and all of its child elements.
 */
LineChart.prototype.clear = function() {
	this.svg.remove();
};


/**
 * Removes the SVG elements representing the chart data.
 */
LineChart.prototype.clearChartData = function() {
	// Remove the chart data group and all of its child elements.
	this.chartDataGroup.remove();
};


/**
 * Updates the chart data (useful when the data changes).
 */
LineChart.prototype.updateChartData = function() {
	// Update the scale input domains.
	this.setScaleInputDomains();

	// Do all the necessary adjustments so that the chart is correctly redrawn.
	this.redrawChart();

	// Update the line chart context to reflect the new data.
	this.context.updateContextData();
};


/**
 * Re-draws the chart without necessarily updating the data.
 * This is useful e.g. when the chart should be re-drawn based on the selected context.
 *
 * @param {[Boolean]} ignoreVerticalScales If set to TRUE, the vertical scale
 *   will not be adjusted. This is useful e.g. when using brushing.
 */
LineChart.prototype.redrawChart = function(ignoreVerticalScale) {
	// Update the zoom behavior to handle the new scale input domains.
	this.zoom.setChartScales(ignoreVerticalScale);
	// Set the zoom scale and translate to be the same as before.
	this.zoom.restoreLastSavedZoomProperties();
	// Re-bind the updated data to the field groups.
	this.bindFieldGroupsData();
	// Re-bind the data to the lines and re-draw them.
	this.updateLines(true);
	// Re-bind the data to the circle groups.
	this.bindCircleGroupsData();
	// Hide the circle groups, as the circles' positions need to be recalculated. Also mark that the chart is being updated.
	this.hideCircleGroups(true);
};


/**
 * Updates the stored d3js selection properties that may have had some of their elements removed.
 */
LineChart.prototype.updateSelectionProperties = function() {
	// Update the line groups selection.
	this.updateLineGroupsSelection();
	// Update the lines selection.
	this.updateLinesSelection();
	// Update the circle groups selection and re-bind data for them.
	this.updateCircleGroupsSelectionAndRebindData();
	// Update the circles selection.
	this.updateCirclesSelection();
};


/**
 * Updates the d3js selection for line groups.
 */
LineChart.prototype.updateLineGroupsSelection = function() {
	this.lineGroups = this.fieldGroups.select("g.line-group");
};


/**
 * Updates the d3js selection for lines.
 */
LineChart.prototype.updateLinesSelection = function() {
	this.lines = this.lineGroups.select("path.line");
};


/**
 * Updates the d3js selection for circle-groups.
 */
LineChart.prototype.updateCircleGroupsSelectionAndRebindData = function() {
	this.circleGroups = this.fieldGroups.select("g.circle-group");
	// Re-bind the data for the new circle groups selection.
	this.bindCircleGroupsData();
};


LineChart.prototype.updateCircleWrappersSelection = function() {
	this.circleWrappers = this.circleGroups.select("g.circle-wrapper");
};


/**
 * Updates the d3js selection for circles.
 */
LineChart.prototype.updateCirclesSelection = function() {
	this.circles = this.circleWrappers.select("circle.dot");
};


/**
 * Updates the legend's value set based on the line groups selection property.
 */
LineChart.prototype.updateLegendValueSet = function() {
	var self = this;

	// Construct a valueSet for the legend. This is necessary so that the legend update listeners are notified only once.
	var legendValueSet = {};
	this.lineGroups.each(function(d) {
		var valueName = d.name;
		var color = self.color(d.name);
		legendValueSet[valueName] = color;
	});
	// Assign the new valueSet to the legend.
	this.legend.assignValueSet(legendValueSet);
};


/**
 * Registers event listeners for certain svg elements.
 */
LineChart.prototype.registerEvents = function() {
	// Define some event properties needed for coordinated state between events.
	this.eventProperty = {
		// Marks whether chart elements hiding should be prevented.
		preventElementsHiding: false,
		// Marks whether the chart is being updated right now.
		chartUpdating: false
	};

	// Register event listeners for the diagram container.
	this.registerContainerEventListeners();
	// Register event listeners for the mouse events area.
	this.registerMouseEventsAreaEventListeners();
	// Register event listeners for legend updates.
	this.registerLegendUpdateEventListeners();
	// Register event listeners for legend context selection.
	this.registerLegendContextSelectListeners();
	// Register event listeners for legend tooltip trigger.
	this.registerLegendTooltipTriggerEventListeners();
};


/**
 * Registers event listeners for the mouse events area.
 */
LineChart.prototype.registerMouseEventsAreaEventListeners = function() {
	var self = this;

	this.mouseEventsArea
		.on("mouseover", function() {
			// Schedule chart elements showing.
			self.scheduleMouseOverElementsShowing();
		})
		.on("mouseout", function() {
			// Schedule chart elements hiding.
			self.scheduleMouseOutElementsHiding();
		})
		.on("mousemove", this.getFocusOnDateMouseMoveEventListener());
};


/**
 * Registers event listeners for the diagram container element.
 */
LineChart.prototype.registerContainerEventListeners = function() {
	var self = this;
	// Register a jQuery mousemove event used to position the tooltip element.
	$( this.containerId ).on("mousemove", function(event) {
		// Adjust the measurement tooltip's position with jQuery.
		self.tooltip.measurement.setPosition("left+15 top", event);
	});
};


/**
 * @return {Function} A mouse move event listener function that handles focusing on the nearest date.
 */
LineChart.prototype.getFocusOnDateMouseMoveEventListener = function() {
	var self = this;

	// Define a function that will use the outer method's closure.
	// In this way it will be possible to refer to the line chart object.
	var focusOnDateMouseMoveEventListener = function() {
		// Get the x-coordinate for this mouse event.
		var mouseX = d3.mouse(this)[0];
		// Convert the x position to a date.
		var x0 = self.scale.x.invert(mouseX);
		// Find the closest date to x0, among the available ones.
		var closestDatum = self.findClosestDateDatum(x0, self);
		var ds = closestDatum.d;
		var i = closestDatum.i;

		// If there is no data in the diagram, no closest data can be found.
		if (i == -1) {
			return;
		}

		// Find the data sources which are available for this datum.
		var dataInfo = self.getDataInfoForDateTime(ds.date, i, self);
		var dataInfoFieldNames = Object.getOwnPropertyNames(dataInfo);

		// Find the index for the relevant datum in the data grouped by data source.
		var indexForDataSources = self.getIndexForDatumByDataSources(ds.date, self);

		self.circles
			.each(function(d) {
				// A flag that marks whether this circle is relevant to be displayed.
				var circleIsRelevant = false;
				if ('datum' in d && 'dataSource' in d.datum && 'field' in d.datum) {
					var dataInfoFieldName = d.datum.field + '-' + d.datum.dataSource;
					// Check if there is a value for this field at the selected date.
					if (dataInfoFieldNames.indexOf(dataInfoFieldName) >= 0) {
						circleIsRelevant = true;
					}
				}

				// If this circle is to be displayed.
				if (circleIsRelevant) {
					// Show the parent circle-group element in case it was hidden due to discontinuous lines.
					d3.select(this.parentNode).each(function() {
						d3.select(this.parentNode)
							.style("display", null);
					});
				}
				else {
					// Make sure to hide the parent circle-group element.
					d3.select(this.parentNode).each(function() {
						d3.select(this.parentNode)
							.style("display", "none");
					});
				}
			})
			.attr("transform", function(d) {
				// A flag that marks whether this circle is relevant to be displayed.
				var circleIsRelevant = false;
				var dataInfoFieldName;
				if ('datum' in d && 'dataSource' in d.datum && 'field' in d.datum) {
					dataInfoFieldName = d.datum.field + '-' + d.datum.dataSource;
					// Check if there is a value for this field at the selected date.
					if (dataInfoFieldNames.indexOf(dataInfoFieldName) >= 0) {
						circleIsRelevant = true;
					}
				}

				// If this circle is to be displayed.
				if (circleIsRelevant) {
					return "translate(" +
						self.scale.x(ds.date) + "," +
						self.scale.y(dataInfo[dataInfoFieldName]) + ")";
				}
				else {
					return null;
				}
			})
			.datum(function(d) {
				// A flag that marks whether this circle is relevant to be displayed.
				var circleIsRelevant = false;
				if ('datum' in d && 'dataSource' in d.datum && 'field' in d.datum) {
					var dataInfoFieldName = d.datum.field + '-' + d.datum.dataSource;
					// Check if there is a value for this field at the selected date.
					if (dataInfoFieldNames.indexOf(dataInfoFieldName) >= 0) {
						circleIsRelevant = true;
					}
				}

				// If this circle is to be displayed.
				if (circleIsRelevant) {
					d.currentIndex = indexForDataSources[d.datum.dataSource];
				}
				else {
					d.currentIndex = -1;
				}

				return d;
			});

		// Display a measurement tooltip with the selected date.
		var date = ds.date;
		self.tooltip.measurement.setMeasurementContent(date);
		self.tooltip.measurement.show();
	};

	return focusOnDateMouseMoveEventListener;
};


/**
 * Finds the datum whose date is closest to the one specified as a parameter.
 * @param  {Date object} x0 The date whose closest available one should be found.
 * @param {[Object]} self A reference to the line chart object. This is necessary in case this method is used within an event listener.
 * @return {Object} A result object containing the closest date datum and the datum index.
 */
LineChart.prototype.findClosestDateDatum = function(x0, self) {
	// If no line chart reference was passed as an argument,
	// assume that "this" refers to the line chart object.
	if (self === undefined) {
		self = this;
	}

	// Define a way of finding the closest available date that is equal to or larger than the current one.
	var bisectDate = d3.bisector(function(d) {
		return d.date;
	}).left;

	// Get the index of the closest larger date.
	var closestLargerDateIndex = bisectDate(self.data, x0, 1);
	// Get the closest larger date and the date before it (if any).
	var d0 = null;
	if (closestLargerDateIndex > 0) {
		d0 = self.data[closestLargerDateIndex - 1];
	}
	var d1 = null;
	if (closestLargerDateIndex < self.data.length) {
		d1 = self.data[closestLargerDateIndex];
	}
	// Check which date is closer to the current one and select it to be displayed.
	var ds = null;
	// Also find the index for that date.
	var i = 0;
	// If both d0 and d1 are valid, compare the distance.
	if (d0 && d1) {
		if ((x0 - d0.date) < (d1.date - x0)) {
			ds = d0;
			i = closestLargerDateIndex - 1;
		}
		else {
			ds = d1;
			i = closestLargerDateIndex;
		}
	}
	// Only d0 is valid (mouse pointer is near the end of the diagram).
	else if (d0) {
		ds = d0;
		i = closestLargerDateIndex - 1;
	}
	// Only d1 is valid (mouse pointer is near the beginning of the diagram).
	else {
		ds = d1;
		i = closestLargerDateIndex;
	}

	// If no closest date datum could be found, set the index to -1;
	if (!ds) {
		i = -1;
	}

	// Include the chosen datum and its index in the result.
	var result = {
		d: ds,
		i: i
	};

	return result;
};

/**
 * Finds the index in the data grouped by data source based on specified date.
 *
 * @param {Object} date A date that should be present in the selected data.
 * @param {[Object]} self A reference to the line chart object. This is necessary in case this method is used within an event listener.
 * @return {Integer} The index of the relevant datum in the array of data gruped by data source.
 */
LineChart.prototype.getIndexForDatumByDataSources = function(date, self) {
	// If no line chart reference was passed as an argument,
	// assume that "this" refers to the line chart object.
	if (self === undefined) {
		self = this;
	}

	var indexes = {};
	for (var i = 0; i < self.dataByField.length; i++) {
		var dataSource = self.dataByField[i].dataSource;
		var dataValues = self.dataByField[i].values;
		for (var j = 0; j < dataValues.length; j++) {
			if (dataValues[j].date.getTime() == date.getTime()) {
				// This is the relevant measurement id. Add it to the result.
				indexes[dataSource] = j;

			}
		}

		if (!(dataSource in indexes)) {
			// If no result was found, add -1.
			indexes[dataSource] = -1;
		}
	}

	return indexes;
};


/**
 * Fetches the data source values for a certain date time.
 *
 * @param {Object} date A date that should be present in the selected data.
 * @param {Integer} i The index in the line chart's data array where to start the search.
 * @param {[Object]} self A reference to the line chart object. This is necessary in case this method is used within an event listener.
 * @returns {Object} An object where the keys are "fieldName - dataSource" and the values are the measurement values.
 */
LineChart.prototype.getDataInfoForDateTime = function(date, i, self) {
	var dataInfo = {
	};

	// Field names that are not really measurement value fields.
	var systemReservedFieldNames = [
		'id',
		'source',
		'measurementTime'
	];

	// Start from the data at index "i" and proceed upwards inspecting each element.
	for (var j = i; j < self.data.length; j++) {
		if (self.data[j].date.getTime() == date.getTime()) {
			// The date of this datum matches the one specified as a parameter.

			var dataSource = self.data[j].source;
			// Go through each of the fields available in this datum.
			for (var fieldName in self.data[j]) {
				// Make sure this field name is to be considered.
				if (systemReservedFieldNames.indexOf(fieldName) < 0 && self.data[j].fieldName !== null) {
					// Add the field name and the value to the results array.
					var resultsFieldName = fieldName + '-' + dataSource;
					dataInfo[resultsFieldName] = self.data[j][fieldName];
				}
			}
		}
		else {
			// The date does not match the one specified as a parameter. Stop inspecting the rest of the data.
			break;
		}
	}

	// Start from the data at index "i-1" and proceed downwards inspecting each element.
	for (j = i - 1; j >= 0; j--) {
		if (self.data[j].date.getTime() == date.getTime()) {
			// The date of this datum matches the one specified as a parameter.

			var dataSource = self.data[j].source;
			// Go through each of the fields available in this datum.
			for (var fieldName in self.data[j]) {
				// Make sure this field name is to be considered.
				if (systemReservedFieldNames.indexOf(fieldName) < 0 && self.data[j].fieldName !== null) {
					// Add the field name and the value to the results array.
					var resultsFieldName = fieldName + '-' + dataSource;
					dataInfo[resultsFieldName] = self.data[j][fieldName];
				}
			}
		}
		else {
			// The date does not match the one specified as a parameter. Stop inspecting the rest of the data.
			break;
		}
	}


	return dataInfo;
};


/**
 * Registers event listeners for the chart data elements.
 *
 * @param {[Selection Object]} fieldGroupSelection If specified, the listeners will be registered only to that selection, instead of all the field-groups.
 * @param {[Selection Object]} circleSelection If specified,the listeners will be registered only to that selection, instead of all the circles.
 */
LineChart.prototype.registerChartDataEventListeners = function(fieldGroupSelection, circleSelection) {
	// Register event listeners for the field groups.
	this.registerFieldGroupsEventListeners(fieldGroupSelection);
	// Register event listeners for the circles.
	this.registerCirclesEventListeners(circleSelection);
};


/**
 * Registers event listeners for the circles in the chart.
 *
 * @param {[Selection Object]} circleSelection If specified,the listeners will be registered only to that selection, instead of all the circles.
 */
LineChart.prototype.registerCirclesEventListeners = function(circleSelection) {
	var self = this;

	// If no circle selection selection was passed as a parameter, register the listeners for all the circles.
	if (typeof circleSelection !== 'object') {
		circleSelection = this.circles;
	}

	circleSelection
		.on("mouseover", function(d) {
			// Fill the dot when the mouse is over it.
			d3.select(this).attr("fill", function(d) {
				return self.color(d.datum.name);
			});

			// Display a tooltip with the value that this point represents.
			var i = d.currentIndex;
			// If there is no data in the chart, the index will be -1.
			if (i == -1) {
				return;
			}

			var date = d.datum.values[i].date;
			var valueField = d.datum.name;
			var value = d.datum.values[i].fieldValue;

			self.tooltip.measurement.setMeasurementContent(date, valueField, value);
			self.tooltip.measurement.show();
		})
		.on("mouseout", function(d) {
			// Unfill (fill with white color) the dot when the mouse moves away from it.
			d3.select(this).attr("fill", self.circle.fillColorNoFocus);
		});
};


/**
 * Registers event listeners for the field-group elements.
 * @param {[Selection Object]} fieldGroupSelection If specified, the listeners will be registered only to that selection, instead of all the field-groups.
 */
LineChart.prototype.registerFieldGroupsEventListeners = function(fieldGroupSelection) {
	var self = this;

	// If no field group selection was passed as a parameter, register the listeners for all the field-groups.
	if (typeof fieldGroupSelection !== 'object') {
		fieldGroupSelection = this.fieldGroups;
	}

	fieldGroupSelection
		.on("mouseover", function(d) {
			// Prevent chart elements hiding.
			self.preventMouseOutElementsHiding();
		})
		.on("mouseout", function(d) {
			// Schedule chart elements hiding.
			self.scheduleMouseOutElementsHiding();
		});
};


/**
 * Registers listener functions to be called on legend valueSet update.
 */
LineChart.prototype.registerLegendUpdateEventListeners = function() {
	this.legend.addUpdateListener(this.onLegendUpdate, this);
};


/**
 * Registers listeners that act on selecting a legend element.
 *
 * These listeners will update the line chart context.
 */
LineChart.prototype.registerLegendContextSelectListeners = function() {
	var self = this;

	var legendValueSet = this.legend.getValueSet();

	// If the legend values exceed the amount of allowed values.
	if (legendValueSet.getLength() > this.getMaximumAllowedLegendValues()) {
		// Using a legend tooltip.

		// Get the main element of the legend tooltip.
		var legendTooltipElement = this.tooltip.legend.getElement();
		// Select all the legend-value classed elements.
		var legendTooltipLegendValues = legendTooltipElement.selectAll(".legend-value");

		// Get a more detailed legend values data set, that includes a machine name.
		var legendValuesData = this.legendValuesData;

		// Bind data to the legend value elements.
		legendTooltipLegendValues.each(function() {
			var selection = d3.select(this);
			// Get the human-readable name of the field.
			var name = selection.html();

			// Attempt to find the legendValuesData corresponding to this name. It should include a machine name and a color.
			if (legendValuesData) {
				for (var i = 0; i < legendValuesData.length; i++) {
					if (legendValuesData[i].name == name) {
						// Found the necessary datum for this legend value selection. Bind it to it.
						selection.datum(legendValuesData[i]);
						break;
					}
				}
			}
		});

		legendTooltipLegendValues
			.on('click', function(d) {
				// Attempt to fetch the machine name and the color for the field to be displayed in the context.

				// Default to black color if something fails.
				var color = d.color || "#000000";
				var machineName = d.name || null;

				if (!machineName) {
					// If no machine name could be fetched, nothing should be done in regards to changing the context.
					return;
				}

				// Apply the newly selected field to the context.
				self.useFieldInContext(machineName, color);
			});
	}
	else {
		// Using legend SVG elements.
		// Register onclick event listeners for all the legend value groups.
		this.legendValueGroups.on('click', function(d) {
			// Attempt to fetch the machine name and the color for the field to be displayed in the context.

			// Default to black color if something fails.
			var color = d.color || "#000000";
			var machineName = d.name || null;

			if (!machineName) {
				// If no machine name could be fetched, nothing should be done in regards to changing the context.
				return;
			}

			// Apply the newly selected field to the context.
			self.useFieldInContext(machineName, color);
		});
	}
};


/**
 * Applies a certain field to the line chart context in case that field is not used already.
 *
 * @param  {String} machineName The machine name of the field to be displayed in the line chart context
 * @param  {[type]} color The color for the context area corresponding to that field.
 */
LineChart.prototype.useFieldInContext = function(machineName, color) {
	if (!this.lastKnownLineChartContextProperties) {
		// If there are no last known line chart context properties, most likely the context hasn't been initialized at all. Do that now.
		this.context.init(machineName, color);
	}
	else {
		// The context has already been initialized.
		// Apply the changes only if they differ from the last selection.
		if (this.lastKnownLineChartContextProperties.machineName != machineName || this.lastKnownLineChartContextProperties.color != color) {
			this.context.setPrimaryField(machineName);
			this.context.setColor(color);
			this.context.updateContextData();
		}
	}

	// Store the arguments in the last known context properties object, for future reference.
	this.lastKnownLineChartContextProperties = {
		machineName: machineName,
		color: color
	};
};


/**
 * Registers event listeners for the legend tooltip trigger.
 */
LineChart.prototype.registerLegendTooltipTriggerEventListeners = function() {
	var self = this;

	$( "text#legend-tooltip-trigger").click(function() {
		// Expand or collapse the legend tooltip and the trigger.
		self.triggerLegendTooltipExpandOrCollapse();
	});
};


/**
 * A default method to be executed whenever the legend's valueSet gets updated.
 */
LineChart.prototype.onLegendUpdate = function() {
	var legendValueSet = this.legend.getValueSet();

	// If the legend values exceed the amount of allowed values.
	if (legendValueSet.getLength() > this.getMaximumAllowedLegendValues()) {
		// Re-bind the data to the legend value groups and also clear the legend values.
		this.bindLegendValueGroupsData(true);

		// Show the legend tooltip trigger.
		this.showLegendTooltipTrigger();
	}
	else {
		// Collapse the legend tooltip.
		this.collapseLegendTooltip();
		// Hide the legend tooltip trigger.
		this.hideLegendTooltipTrigger();

		// Re-bind the data to the legend value groups.
		this.bindLegendValueGroupsData();
	}

	// Re-register the event listeners for changing the context.
	this.registerLegendContextSelectListeners();

	// Get the last known chart context property field name.
	var lastUsedContextMachineName = null;
	if (this.lastKnownLineChartContextProperties && this.lastKnownLineChartContextProperties.machineName) {
		lastUsedContextMachineName = this.lastKnownLineChartContextProperties.machineName;
	}

	// If the last used machine name for the context was not fetched successfully or it is no longer available.
	if (!lastUsedContextMachineName || !this.legendValueExists(lastUsedContextMachineName)) {
		// Get the default line chart context properties object.
		var contextProperties = this.getDefaultLineChartContextProperties();
		if (contextProperties) {
			// Apply the selected field to the context.
			this.useFieldInContext(contextProperties.machineName, contextProperties.color);
		}
	}
};


/**
 * Checks if a legend value element with a certain machine name exists.
 *
 * @param {String} machineName The machine name of the value field to be checked if it exists as a legend value element.
 * @return {Boolean} TRUE if the machine name can be found among the legendValuesData, or FALSE otherwise.
 */
LineChart.prototype.legendValueExists = function(machineName) {
	var legendValuesData = this.legendValuesData;
	if (legendValuesData) {
		for (var i = 0; i < legendValuesData.length; i++) {
			// Check if the machine name matches the one in this legend values data group.
			if (legendValuesData[i].machineName == machineName) {
				// The machine name was found.
				return true;
			}
		}
	}

	// This machine name was not found.
	return false;
};


/**
 * Hides the circle groups, effectively hiding also the circles in the chart.
 *
 * @param {[Boolean]} chartUpdating If set to true, it will be considered that the chart is being updated and so some events will not try to show the circle groups for now.
 */
LineChart.prototype.hideCircleGroups = function(chartUpdating) {
	this.circleGroups.style("display", "none");

	// If the chart is updating, mark in the event properties this status.
	if (chartUpdating) {
		this.eventProperty.chartUpdating = true;
	}
};


/**
 * Hides the axis grups.
 */
LineChart.prototype.hideAxisGroups = function() {
	this.axisGroup.x.style("display", "none");
	this.axisGroup.y.style("display", "none");
};


/**
 * Shows the axis groups.
 */
LineChart.prototype.showAxisGroups = function() {
	this.axisGroup.x.style("display", null);
	this.axisGroup.y.style("display", null);
};


/**
 * Updates (re-draws) the chart axes.
 */
LineChart.prototype.updateAxes = function() {
	this.updateXAxis();
	this.updateYAxis();
};

/**
 * Updates (re-draws) the chart x-axis.
 */
LineChart.prototype.updateXAxis = function() {
	this.axisGroup.x.call(this.axis.x);
};


/**
 * Updates (re-draws) the chart y-axis.
 */
LineChart.prototype.updateYAxis = function() {
	this.axisGroup.y.call(this.axis.y);
};


/**
 * Updates (re-draws) the lines in the chart.
 *
 * @param {[Boolean]} rebindData If set to true, the lines' data will be rebound. This is useful when the data changes. An example when this doesn't need to be done is a zoom behavior.
 */
LineChart.prototype.updateLines = function(rebindData) {
	var self = this;

	// If it was requested that the data for the lines should be re-bound, do that now.
	if (rebindData) {
		this.lines.data(this.dataByField, this.dataKey);
	}

	// Update the lines "d" attribute, essentially altering the path.
	this.lines.attr("d", function(d) {
		return self.lineGenerator(d.values);
	});
};


/**
 * Applies geometric scaling to certain chart elements, useful when zooming.
 * @param  {Number} scale The zoom scale to be applied.
 */
LineChart.prototype.applyGeometricScale = function(scale) {
	// Define a list of elements that need to be scaled.
	var elementsToScale = [
		this.mouseEventsArea,
		this.lines,
		this.circleWrappers
	];

	// Define a pattern for modifying the transform attributes of these elements.
	var pattern = /^(.*)(scale\(.*\))(.*)$/;

	// Go through each element that needs to be scaled.
	for (var i = 0; i < elementsToScale.length; i++) {
		var elementToScale = elementsToScale[i];
		// Get the existing transform attribute value for the element.
		var transform = elementToScale.attr("transform") || "";

		// Define the scale transform text to be applied. Use an empty value if the scale is 1.
		var scaleTransform = "";
		if (scale != 1) {
			scaleTransform = "scale(" + scale + ")";
		}

		// If the current transform does not contain scaling.
		if (transform.match(pattern) === null) {
			// Just append the scale transform.
			transform += scaleTransform;
		}
		else {
			// Replace the existing scale transform.
			transform = transform.replace(pattern, "$1" + scaleTransform + "$3");
		}

		// Apply the transform attribute to the element.
		elementToScale.attr("transform", transform);
	}
};


/**
 * Schedules a function execution that will hide certain elements from the chart due to the mouse leaving the events area.
 */
LineChart.prototype.scheduleMouseOutElementsHiding = function () {
	var self = this;

	// First, clear any previously set flag that elements hiding should be prevented.
	self.eventProperty.preventElementsHiding = false;

	// Delay the elements hiding for some time so that other events have time to prevent that.
	var delayInMilliseconds = 200;

	setTimeout(function() {
		// If no other event prevented the elements hiding, then hide the elements now.
		if (!self.eventProperty.preventElementsHiding) {
			// Hide the circle groups.
			self.circleGroups.style("display", "none");
			// Hide the measurement tooltip.
			self.tooltip.measurement.hide();
		}
	}, delayInMilliseconds);
};


/**
 * Schedules a function execution that will show certain elements from the chart due to the mouse entering the events area.
 */
LineChart.prototype.scheduleMouseOverElementsShowing = function () {
	var self = this;

	// Delay the elements showing for some time so that other events have time to prevent that.
	var delayInMilliseconds = 200;

	// Prevent chart elements hiding.
	self.preventMouseOutElementsHiding();

	setTimeout(function() {
		// Check if the chart is updating right now.
		if (self.eventProperty.chartUpdating) {
			// Mark that the chart updating status has been acknowledged.
			self.eventProperty.chartUpdating = false;
			// Do not do anything else for the current event.
			// There is no need to show the circle groups because their position needs to be recalculated based on the mouse move position.
		}
		// Otherwise if the chart is not updating.
		else {
			// Restore the default display style for the circle groups which were last visible when the mouse is over the mouse events area.
			self.circleGroups.select("circle.dot").each(function() {
				// Check if there is an applied transform to this circle.
				if (d3.select(this).attr("transform") !== null) {
					// Some transform is applied to this circle, meaning that it needs to be shown.

					// Show the parent circle-group element in case it was hidden.
					d3.select(this.parentNode).each(function() {
						d3.select(this.parentNode)
							.style("display", null);
					});
				}
			});
		}
	}, delayInMilliseconds);
};


/**
 * Prevents a scheduled mouse out function from hidimg chart elements.
 */
LineChart.prototype.preventMouseOutElementsHiding = function() {
	this.eventProperty.preventElementsHiding = true;
};


/**
 * @return {Boolean} true if the chart container's width is small enough for the geometric scaling to be the preferred scaling method when zooming, or false otherwise.
 */
LineChart.prototype.geometricScalingIsPreferred = function() {
	var containerWidth = this.getContainerWidth();
	// In bootstrap small devices are defined as >= 768px in width.
	var maxWidthForGeometricScaling = 768;
	var useGeometricScaling = true;
	if (containerWidth > maxWidthForGeometricScaling) {
		useGeometricScaling = false;
	}

	return useGeometricScaling;
};


/**
 * @return {Integer} The maximum amount of legend values that can be displayed in the top margin of the line chart.
 */
LineChart.prototype.getMaximumAllowedLegendValues = function() {
	return 3;
};


/**
 * @return {integer} The width of the line chart without the margins.
 */
LineChart.prototype.getWidth = function() {
	return this.dimension.width;
};


/**
 * @return {integer} The height of the line chart without the margins.
 */
LineChart.prototype.getHeight = function() {
	return this.dimension.height;
};


/**
 * @return {Object} The horizontal scale for the chart.
 */
LineChart.prototype.getXScale = function() {
	return this.scale.x;
};


/**
 * @return {Object} The vertical scale for the chart.
 */
LineChart.prototype.getYScale = function() {
	return this.scale.y;
};


/**
 * @return {Object} The horizontal axis for the chart.
 */
LineChart.prototype.getXAxis = function() {
	return this.axis.x;
};


/**
 * @return {Object} The vertical axis for the chart.
 */
LineChart.prototype.getYAxis = function() {
	return this.axis.y;
};


/**
 * @return {Number} The actual/real width of the chart container element, in pixels.
 */
LineChart.prototype.getContainerWidth = function() {
	var containerWidth = d3.select(this.containerId).style("width").replace("px", "");

	return containerWidth;
};


/**
 * @return {Object} A d3js selector for the SVG element.
 */
LineChart.prototype.getSvgElement = function() {
	return this.svg;
};
