# Air Measurement

A project, based on Symfony framework, that I built for my final year's thesis on the topic of "Data consolidation for Data Sources of Non-Uniform Sample Rate".

Additionally, there is a frontend that creates a diagram using D3.js library.

## Backend

In brief the idea is that there were air quality measurements taken from different sensors
and the frequency with which they make measurements was not guaranteed to stay the same.
Additionally, some sensors could be of a different model than others, and so it could be
technically impossible that all sensors are configured to the same sampling rate.

This project consolidates data from multiple different sources and makes it uniform,
meaning that all measurements in the time-series data are spaced equally between each other.

## Frontend

The frontend uses the D3.js library to create a custom-made SVG diagram, which supports zooming,
highlighting certain areas of the time-series, a "legend" that tells the meaning of different colors,
and tooltip info when hovering over each measurement point.

Check out the D3.js code in the [web/diagram/js](web/diagram/js) folder.

## Disclaimer

Initially this was started as a project that a certain customer could use,
so the code contained the customer name in certain module namings.
This has undergone a mass clean-up in order to not associate this with that customer.
This software is not and has not been in use by any company.
