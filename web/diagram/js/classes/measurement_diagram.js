/**
 * Represents the whole measurement diagram, including the line chart and the tooltips.
 * 
 * @param {Array} dataSources The available data sources to be displayed in the dataSources filter.
 * @param {Array} valueTypes The available value types to be displayed in the values filter.
 * @param {Array} data
 */
function MeasurementDiagram(dataSources, valueTypes, data) {
	// Store the available data sources and value types to be displayed in the filters.
	this.dataSources = dataSources;
	this.valueTypes = valueTypes;
	// Store the data passed to the constructor.
	// The unfiltered data is the one that has not passed validations or filtering.
	this.unfilteredData = data;
	this.data = null;
	// Create a legend object for the diagram.
	this.legend = new Legend();

	// List of other properties being set mostly at the init phase.
	// They are just listed here to make it clearer that they exist.
	this.lineFieldsInfo = null;
	this.dataByField = null;
	this.colorScale = null;
	this.filters = null;
	this.chartContainerId = null;
	this.chart = null;
	this.tooltip = null;
	this.sampleRatesMenuEventListener = null;
	this.timesMenuEventListener = null;
}


/**
 * Returns a full list of possible values in the data that could be displayed in the diagram.
 * The full list does not consider any filters or missing fields from the data.
 *
 * @return {Array} An array of objects, each containing the following properties:
 *   'name' - The display (human-readable) name for the field.
 *   'field' - The machine name of the field, that could possibly be found among the data.
 */
MeasurementDiagram.prototype.getFullLineFieldsInfo = function() {
	// Define all the values that could be listed.
	var lineFieldsInfo = [];

	// Get the data source filters (if any).
	var dataSourceFilters;
	if (this.filters && 'dataSource' in this.filters) {
		// Get the machine names for the selected values.
		dataSourceFilters = this.filters.dataSource.getLastConfirmedSelection();
	}

	// Go through each of the data sources currently allowed in the filters.
	if (typeof dataSourceFilters !== 'undefined') {
		for (var i = 0; i < dataSourceFilters.length; i++) {
			dataSource = dataSourceFilters[i];
			// Add a separate line field info for each of the available diagram value types.
			var dataSourceLineFieldsInfo = this.valueTypes.map(function(valueType) {
				return {
					name: valueType + ' (' + dataSource + ')',
					field: valueType,
					dataSource: dataSource
				};
			});

			// Merge the result array into the final result.
			lineFieldsInfo = lineFieldsInfo.concat(dataSourceLineFieldsInfo);
		}
	}

	return lineFieldsInfo;
};

/**
 * Returns the sample rate filters' last confirmed selection.
 *
 * @return {Mixed} Array of machine names for the last confirmed selection, or null if no selection has been confirmed so far.
 */
MeasurementDiagram.prototype.getSelectedSampleRates = function() {
	if (this.filters.sampleRate) {
		var sampleRates = this.filters.sampleRate.getLastConfirmedSelection();
		if (!sampleRates) {
			return [];
		}
		else {
			// Translate the sample rates into numeric values.
			sampleRates = sampleRates.map(function(sampleRate) {
				switch(sampleRate) {
					case 'unmodified':
						return 0;
					case 'minute':
						return 1;
					case 'hour':
						return 2;
					case 'day':
						return 3;
					case 'month':
						return 4;
					default:
						return -1;
				}
			});
			return sampleRates;
		}
	}
	else {
		return [];
	}
};


/**
 * Validates and converts the data in the object.
 * Afterwards, initializes the line chart.
 *
 * @param {String} chartContainerId A string representing the HTML id of a container element for the line chart. This can optionally contain the '#' character in the beginning.
 */
MeasurementDiagram.prototype.init = function(chartContainerId) {
	// Store the chart container id as a property of the measurement diagram.
	this.chartContainerId = chartContainerId;

	// Initialize the selectable filter options.
	this.initFilters();
	// Initialize the field info depending on the values that need to be displayed in the diagram and the filters.
	this.initLineFieldsInfo();
	// Validate and convert some values in the data.
	this.validateAndConvertData();
	// Group the data by value and data source fields.
	this.groupDataByValueAndDataSource();
	// Initialize the color scale.
	this.initColorScale();
	// Create and init tooltips for the diagram.
	this.initTooltips();
	// Create and initialize the line chart.
	this.initChart();
};


/**
 * Creates a line chart for this diagram and initializes it.
 */
MeasurementDiagram.prototype.initChart = function() {
	// Create a line chart that will present the measurement data.
	// Pass a reference to the current object to it so it can access some relevant properties.
	this.chart = new LineChart(this);

	// Initialize the chart.
	this.chart.init(this.chartContainerId);
};


/**
 * Displays (renders) the chart and registers event listeners for it.
 */
MeasurementDiagram.prototype.displayChart = function() {
	// Render the elements representing the data.
	this.chart.renderData();
	// Register event listeners.
	this.chart.registerEvents();
};


/**
 * Creates a tooltip for this diagram and initializes it.
 */
MeasurementDiagram.prototype.initTooltips = function() {
	var self = this;
	var containerId = "diagram-container";

	// Create tooltips for the diagram.
	this.tooltip = {
		// Tooltip that shows the measurement values.
		measurement: new MeasurementTooltip(containerId, "measurement-tooltip"),
		// Tooltip that shows the values legend.
		legend: new LegendTooltip(self.legend, containerId, "legend-tooltip", null, null, false)
	};

	// Initialize the tooltips.
	for (var tooltipName in this.tooltip) {
		this.tooltip[tooltipName].init();
	}
};


/**
 * Filters out data without a valid timestamp and creates a Date object for each valid data entry.
 */
MeasurementDiagram.prototype.validateAndConvertData = function() {
	// Get the data sources that are supposed to be shown in the diagram.
	var dataSources = [];
	for (var i = 0; i < this.lineFieldsInfo.length; i++) {
		var dataSource = this.lineFieldsInfo[i].dataSource;
		if (dataSources.indexOf(dataSource) < 0) {
			// This data source is not part of the dataSources array yet. Add it there.
			dataSources.push(dataSource);
		}
	}

	// Filter out data that does not have a valid timestamp,
	// or is from a data source that isn't supposed to be visualized in the diagram currently.
	var data = this.unfilteredData.filter(function(d) {
		if (isNaN(d.measurementTime)) {
			// The measurement time is not a unix timestamp.
			return false;
		}
		else if (dataSources.indexOf(d.source) < 0) {
			// Not a relevant data source.
			return false;
		}
		else {
			return true;
		}
	});

	// Add a date field to the data based on the timestamp.
	data = data.map(function(d) {
		// Convert timestamps to Date objects.
		// Calculate the timestamp in milliseconds.
		var timestampInMilliseconds = d.measurementTime * 1000;
		// Create a date object from the timestamp.
		d.date = new Date(timestampInMilliseconds);

		return d; 
	});

	this.data = data;
};


/**
 * Initializes a property defining which fields should be taken into consideration from the available data.
 */
MeasurementDiagram.prototype.initLineFieldsInfo = function() {
	// Get all the values that could be listed.
	var lineFieldsInfo = this.getFullLineFieldsInfo();

	// Get the value filters (if any).
	var valueFilters;
	if (this.filters && 'value' in this.filters) {
		// Get the machine names for the selected values.
		valueFilters = this.filters.value.getLastConfirmedSelection();
	}

	// If some filters were specified.
	if (typeof valueFilters !== 'undefined') {
		// Filter out objects in the lineFieldsInfo array that are not listed among the specified filters.
		lineFieldsInfo = lineFieldsInfo.filter(function(lineFieldInfo) {
			// If this lineFieldInfo is not among the desired valueFilters, filter it out.
			if (valueFilters.indexOf(lineFieldInfo.name) < 0) {
				return false;
			}
			else {
				return true;
			}
		});
	}

	this.lineFieldsInfo = lineFieldsInfo;
};


/**
 * Initializes the color scale for the values to be displayed in the diagram.
 */
MeasurementDiagram.prototype.initColorScale = function() {
	// Basic color sets taken from http://colorbrewer2.org/
	// This is the 12-class Paired set that is reordered so that similar colors do not appear often next to each other.
	// Some colors have been modified for better visibility.
	var colors = [
		// Darker color versions.
		'#1f78b4',
		'#33a02c',
		'#ff7f00',
		'#e31a1c',
		'#6a3d9a',
		'#b15928',
		// Lighter color versions.
		'#a6cee3',
		'#b2df8a',
		'#fdbf6f',
		'#fb9a99',
		'#cab2d6',
		'#d0c929'
	];

	this.colorScale = d3.scale.ordinal()
		.range(colors);

	// this.colorScale = d3.scale.category10();
};


/**
 * Initializes the selectable options in the filters.
 */
MeasurementDiagram.prototype.initFilters = function() {
	this.filters = {};

	// Initialize the data source filters.
	var dataSourceFilterOptions = this.dataSources.map(function (dataSource) {
		var filterOption = {
			machineName: dataSource,
			name: dataSource,
			// Make the options unchecked by default.
			checked: false
		};

		return filterOption;
	});
	// Make the first data source filter selected.
	if (dataSourceFilterOptions.length > 0) {
		dataSourceFilterOptions[0].checked = true;
	}
	this.filters.dataSource = new CheckboxFilters("dataSourcesMenu", dataSourceFilterOptions);
	this.filters.dataSource.init();

	// Initialize the value filters.
	this.initValuesFilters();

	// Initialize the sample rate filters.
	$sampleRateFilterOptions = [
		{
			machineName: 'unmodified',
			name: 'unmodified',
			checked: true
		},
		{
			machineName: 'minute',
			name: 'minute',
			checked: false
		},
		{
			machineName: 'hour',
			name: 'hour',
			checked: false
		},
		{
			machineName: 'day',
			name: 'day',
			checked: false
		},
		{
			machineName: 'month',
			name: 'month',
			checked: false
		}
	];
	this.filters.sampleRate = new CheckboxFilters("sampleRatesMenu", $sampleRateFilterOptions);
	this.filters.sampleRate.init();

	// Initialize the time filters.
	var timeFilterOptions = [
		{
			machineName: 'start-date',
			name: 'Start'
		},
		{
			machineName: 'end-date',
			name: 'End'
		}
	];
	this.filters.time = new TimeFilters("timesMenu", timeFilterOptions);
	this.filters.time.init();

	// Register listener for the dataSources filter.
	this.filters.dataSource.addSelectionUpdateListener(this.getDataSourcesMenuEventListener(), this);
	// Register listener for the values filter.
	this.filters.value.addSelectionUpdateListener(this.getValuesMenuEventListener(), this);
	// Register listener for the sample rate filter.
	this.filters.sampleRate.addSelectionUpdateListener(this.getSampleRatesMenuEventListener(), this);
	// Register listener for the time filter.
	this.filters.time.addSelectionUpdateListener(this.getTimesMenuEventListener(), this);
};

/**
 * Initializes the selectable options in the values filters.
 *
 * NOTE: It is expected that the data sources filter is already initialized.
 * This ensures that the lineFieldsInfo will be correct.
 *
 * @param {Boolean} updateOptions If set to true the filter options will be updated, instead of creating a totally new filter.
 */
MeasurementDiagram.prototype.initValuesFilters = function(updateOptions) {
	// Get all the values that should be listed as value filters.
	var lineFieldsInfo = this.getFullLineFieldsInfo();

	// Initialize the value filters.
	var valueFilterOptions = lineFieldsInfo.map(function (lineFieldInfo) {
		var filterOption = {
			machineName: lineFieldInfo.name, // use the name as machine name too, since it's a combination of valueType + dataSource
			name: lineFieldInfo.name
		};

		return filterOption;
	});

	if (updateOptions) {
		// The options should be updated.
		// Get the values which were selected last.
		var selectedValues = this.filters.value.getLastConfirmedSelection();
		if (selectedValues && selectedValues.length > 0) {
			// Some values were selected in the past.
			// Make sure the selection is kept as close as possible to last confirmed selection.
			for (var i = 0; i < valueFilterOptions.length; i++) {
				var valueFilterOption = valueFilterOptions[i];
				if (selectedValues.indexOf(valueFilterOption.name) < 0) {
					// This value should be unchecked.
					valueFilterOption.checked = false;
				}
			}
		}

		// Update the options in the filter.
		this.filters.value.setOptions(valueFilterOptions, true);
		// Update the confirmed selection stored in memory.
		this.filters.value.setLastConfirmedSelection();
		// Register a listener for the confirmation button.
		this.filters.value.registerConfirmationEventListener();
	}
	else {
		// The filter should be initialized instead of updated.
		this.filters.value = new CheckboxFilters("valuesMenu", valueFilterOptions);
		this.filters.value.init();
	}
}


/**
 * Groups the available data by value and data source fields (depending on which fields need to be displayed in the diagram).
 */
MeasurementDiagram.prototype.groupDataByValueAndDataSource = function() {
	var self = this;

	// Extract the available data sources.
	var dataSources = [];
	for (var i = 0; i < this.lineFieldsInfo.length; i++) {
		var dataSource = this.lineFieldsInfo[i].dataSource;
		if (dataSources.indexOf(dataSource) < 0) {
			// This data source is not part of the dataSources array yet. Add it there.
			dataSources.push(dataSource);
		}
	}

	// Go through each of the data sources and group the data by that.
	var dataByField = [];
	for (i = 0; i < dataSources.length; i++) {
		var dataSource = dataSources[i];

		// Get only the data objects that have this data source.
		var dataForDataSource = self.data.filter(function(d) {
			if (d.source == dataSource) {
				return true;
			}
			else {
				return false;
			}
		});

		// Get line fields info that are relevant for this data source.
		var lineFieldsInfoForDataSource = self.lineFieldsInfo.filter(function(lineFieldInfo) {
			if (lineFieldInfo.dataSource == dataSource) {
				return true;
			}
			else {
				return false;
			}
		});

		// Go through each of the fields that need to be displayed and group the values from the available data.
		var dataByValue = lineFieldsInfoForDataSource.map(function(fieldInfo) {
			// Define an object that represents the field group.
			var fieldGroup = {
				name: fieldInfo.name,
				field: fieldInfo.field,
				dataSource: fieldInfo.dataSource
			};

			// Get only the relevant field value out of the data objects.
			fieldGroup.values = dataForDataSource.map(function(d) {
				// Define an object that has the date for this datum and the measurement id.
				var datum = {
					date: d.date,
					measurement_id: d.id
				};

				// Also add the value of the field that we're grouping by.
				// If this field is not defined then fallback to null.
				if (fieldInfo.field in d) {
					// Make sure the value is parsed as a number by adding a '+' in the front.
					datum.fieldValue = +d[fieldInfo.field];
				}
				else {
					datum.fieldValue = null;
				}

				return datum;
			});

			return fieldGroup;
		});

		// Merge the data into the results array.
		dataByField = dataByField.concat(dataByValue);
	}

	this.dataByField = dataByField;
};


/**
 * @return {Function Object} A listener function for dataSources filter selection update.
 */
MeasurementDiagram.prototype.getDataSourcesMenuEventListener = function() {
	var self = this;

	var dataSourcesMenuEventListener = function() {
		self.initValuesFilters(true);
		self.update();
	};

	return dataSourcesMenuEventListener;
};


/**
 * @return {Function Object} A listener function for values filter selection update.
 */
MeasurementDiagram.prototype.getValuesMenuEventListener = function() {
	var self = this;

	var valuesMenuEventListener = function() {
		self.update();
	};

	return valuesMenuEventListener;
};

/**
 * @return {Function Object} A listener function for sampleRate filter selection update.
 */
MeasurementDiagram.prototype.getSampleRatesMenuEventListener = function() {
	var self = this;

	if (this.sampleRatesMenuEventListener) {
		// There was an assigned event listener that needs to be used.
		return this.sampleRatesMenuEventListener;
	}

	// There is no other assigned event listener. Use the default one.
	var sampleRatesMenuEventListener = function() {
		// console.log(self.filters.time.getSelectedValuesAsUtcStrings());
	};

	return sampleRatesMenuEventListener;
};

/**
 * @return {Function Object} A listener function for times filter selection update.
 */
MeasurementDiagram.prototype.getTimesMenuEventListener = function() {
	var self = this;

	if (this.timesMenuEventListener) {
		// There was an assigned event listener that needs to be used.
		return this.timesMenuEventListener;
	}

	// There is no other assigned event listener. Use the default one.
	var timesMenuEventListener = function() {
		// console.log(self.filters.time.getSelectedValuesAsUtcStrings());
	};

	return timesMenuEventListener;
};


/**
 * Fetches the selected start time from the time range filter.
 *
 * @return {String|null} A UTC date time string or null.
 */
MeasurementDiagram.prototype.getSelectedStartTimeValue = function() {
	var selectedDateValues = this.filters.time.getSelectedValuesAsUtcStrings();

	var result = selectedDateValues['start-date'] || null;
	return result;
};


/**
 * Fetches the selected end time from the time range filter.
 *
 * @return {String|null} A UTC date time string or null.
 */
MeasurementDiagram.prototype.getSelectedEndTimeValue = function() {
	var selectedDateValues = this.filters.time.getSelectedValuesAsUtcStrings();

	var result = selectedDateValues['end-date'] || null;
	return result;
};


/**
 * Assigns an event listener function to react to changes in time range selection.
 *
 * Note that this function should be assigned before the measurement diagram's init phase.
 *
 * @param {Function Object} callback A listener function to be called on times selection update.
 */
MeasurementDiagram.prototype.setTimesMenuEventListener = function(callback) {
	this.timesMenuEventListener = callback;
};

/**
 * Assigns an event listener function to react to changes in sample rates selection.
 *
 * Note that this function should be assigned before the measurement diagram's init phase.
 *
 * @param {Function Object} callback A listener function to be called on sample rates selection update.
 */
MeasurementDiagram.prototype.setSampleRatesMenuEventListener = function(callback) {
	this.sampleRatesMenuEventListener = callback;
};


/**
 * Updates the measurement diagram. This is useful when certain filters are applied or when the data gets updated.
 */
MeasurementDiagram.prototype.update = function() {
	// Initialize the new line fields info based on the filters.
	this.initLineFieldsInfo();
	// Validate and convert some values in the data.
	this.validateAndConvertData();
	// Group the data by value and data source fields.
	this.groupDataByValueAndDataSource();
	// Re-draw the data in the line chart.
	this.chart.updateChartData();
};


MeasurementDiagram.prototype.recreateChart = function() {
	this.chart.clear();
	this.initChart();
	this.displayChart();
};


/**
 * Sets data to the measurement diagram and updates the displayed data.
 * @param {Array} data The data to be displayed by the measurement diagram.
 */
MeasurementDiagram.prototype.setData = function(data) {
	// Check if the old data differs from the new one.
	if (Util.areArraysEqual(this.unfilteredData, data) === false) {
		// The new data differs frm the old one.
		// Assign it as a property.
		this.unfilteredData = data;

		// Update the measurement diagram to reflect the new data.
		this.update();
	}
};



/**
 * @return {LineChart Object} The line chart object for this diagram.
 */
MeasurementDiagram.prototype.getChart = function() {
	return this.chart;
};


/**
 * @return {Array} The measurement data for this diagram.
 */
MeasurementDiagram.prototype.getData = function() {
	return this.data;
};


/**
 * @return {Array} The measurement data for this diagram, grouped by value field.
 */
MeasurementDiagram.prototype.getDataByField = function() {
	return this.dataByField;
};


/**
 * @return {Object} An ordinal scale for the colors in the diagram.
 */
MeasurementDiagram.prototype.getColorScale = function() {
	return this.colorScale;
};


/**
 * @return {Tooltip Object} The measurement tooltip for this diagram.
 */
MeasurementDiagram.prototype.getMeasurementTooltip = function() {
	return this.tooltip.measurement;
};


/**
 * @return {Tooltip Object} The legend tooltip for this diagram.
 */
MeasurementDiagram.prototype.getLegendTooltip = function() {
	return this.tooltip.legend;
};


/**
 * @return {Legend Object} The legend for this diagram.
 */
MeasurementDiagram.prototype.getLegend = function() {
	return this.legend;
};
