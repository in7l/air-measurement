/**
 * Initializes a measurement diagram and fetches data for it.
 *
 * @param {Array} dataSources The available data sources to be displayed in the dataSources filter.
 * @param {Array} valueTypes The available value types to be displayed in the values filter.
 * @param {String} backendUrl The relative url for fetching the measurement data.
 * @param {String} getParamsWithTokens Optional GET parameters' section of the backendUrl, containing replaceable [TOKENS].
 */
function createMeasurementDiagram(dataSources, valueTypes, backendUrl) {
	try {
		var chartData = [];

		var useRandomData = false;
		var updateIntervalMilliseconds = 30 * 1000;

		var dataFetcher = null;
		if (useRandomData) {
			// Use a generator for measurement data entries.
			dataFetcher = new DataGenerator();
		}
		else {
			// Create a measurement data fetcher.
			var measurementEntriesLimit = 300;
			dataFetcher = new DataFetcher(backendUrl, measurementEntriesLimit);
		}

		// Create the measurement diagram.
		// The object is needed at this stage for some of the listener functions.
		var measurementDiagram = new MeasurementDiagram(dataSources, valueTypes, chartData);

		// Register listeners to react to the data updating.
		// Successful data fetch listener.
		var onDataFetchSuccess = function () {
			try {
				// Get the last retrieved data.
				chartData = dataFetcher.getData();
				// Set the data to the measurement diagram.
				measurementDiagram.setData(chartData);
			}
			catch (err) {
				console.log("Failed to apply the fetched measurement data to the diagram.");
				console.log(err);
			}
		};
		dataFetcher.addDataUpdateListener(true, onDataFetchSuccess);

		if (!useRandomData) {
			// For non-random (actual) data, there is some extra functionality added.

			// Unsuccessful data fetch listener.
			var onDataFetchFailure = function (err) {
				console.log("Failed to fetch measurement data.");
				console.log(err);
			};
			dataFetcher.addDataUpdateListener(false, onDataFetchFailure);

			// Define the time filters update listener function.
			var dataFetcherDateSelectionChangeListener = dataFetcher.getDefaultDateSelectionChangeListener();
			// Define a time filters update callback function that essentially links
			// the data fetcher and the measurement diagram.
			var timeFiltersUpdateListener = function () {
				// Fetch the selected start and end date-time values.
				var startDate = measurementDiagram.getSelectedStartTimeValue();
				var endDate = measurementDiagram.getSelectedEndTimeValue();
				// Notify the data fetcher that the time range has changed
				// and that it needs to re-fetch the data.
				dataFetcherDateSelectionChangeListener(startDate, endDate);
			};
			// Assign the time filters update listener to the measurement diagram.
			measurementDiagram.setTimesMenuEventListener(timeFiltersUpdateListener);

			// Define the sample rate filters update listener function.
			var sampleRateChangeListener = dataFetcher.getDefaultSampleRateChangeListener();
			// Define a sample rate filters update callback function that essentially links
			// the data fetcher and the measurement diagram.
			var sampleRateFiltersUpdateListener = function () {
				// Fetch the selected sample rate values.
				var selectedSampleRates = measurementDiagram.getSelectedSampleRates();
				// Notify the data fetcher that the selected sample rates have changed
				// and that it needs to re-fetch the data.
				sampleRateChangeListener(selectedSampleRates);
			};
			// Assign the sample rate filters update listener to the measurement diagram.
			measurementDiagram.setSampleRatesMenuEventListener(sampleRateFiltersUpdateListener);
		}

		// Now that the listeners have been initialized in the correct order,
		// proceed with initializing the measurement diagram.
		var diagramContainerId = "diagram-container";
		measurementDiagram.init(diagramContainerId);
		measurementDiagram.displayChart();

		// Fetch the initial data and schedule continuous updating.
		dataFetcher.init(updateIntervalMilliseconds);

		// When the fit-to-screen button is pressed, the measurement diagram
		// should essentially be reset or re-created.
		$("#fitToScreen").click(function () {
			measurementDiagram.recreateChart();
		});
	}
	catch (err) {
		console.log(err);
	}
}
