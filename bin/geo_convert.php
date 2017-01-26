<?php

/**
* shapefile conversion utility
*
* This is a simple front end to the geoPHPwithFeatures library to allow for conversions between
* supported file types. 
*
* Usage:
*
* php geo_convert.php --input-format="gpx|geojson|..." --input-path="path to source file" --output-format="gpx|geojson|..." --output-path="target path"
*
* @license MIT
* @author Yermo Lamers, Flying Brick Software, LLC
*/

if (( @$_SERVER[ 'HTTP_HOST' ] != NULL ) || ( php_sapi_name() != 'cli' )) {
	die( "No\n" );
}

$options  = array(
	"help",			// display help.
	"input-format:",	// format of source file
	"input-path:",		// path of source file.
	"output-format:",	// format to generate
	"output-path:"		// target path
);

set_time_limit(0);

// set_error_handler( 'FailOnError' );

require_once( '../geoPHP.inc' );

$parsed_options = getopt( NULL, $options );

if ((( @$input_format = $parsed_options[ 'input-format' ]) == NULL ) ||
    (( @$input_path = $parsed_options[ 'input-path' ]) == NULL ) ||
    (( @$output_format = $parsed_options[ 'output-format' ]) == NULL ) ||
    (( @$output_path = $parsed_options[ 'output-path' ]) == NULL )) {

	display_help();

	die( -1 );
}

try {
	geo_convert( $input_format, $input_path, $output_format, $output_path );
} catch( Exception $e ) {

	print( "Error - {$e->__toString()}\n" );
	die( -1 );

}

// ------------------------------------------------------------------

/**
* displays supported formats
*/

function display_help() {

	print( "Usage: geo_convert.php --input-format=gpx|geojson|.. --input-path=filepath --output-format=gpx|geojson|... --output-path=filepath\n" );

	print( "Supported formats are:\n" );

	foreach (geoPHP::getAdapterMap() as $adapter_key => $adapter_class) {
		print( $adapter_key . "\n" );
	}

}

// ------------------------------------------------------------------

/**
* convert GIS file format
*
* Converts between GIS file formats.
*
* Supported formats are:
*
* wkt
* wkb
* ewkt
* ewkb
* kml
* geohash
* geojson
* georss
* gpx
*
* @param string $input_format format of source file
* @param string $intput_path path to input file
* @param string $output_format format of output file
* @param string $output_path path to output file
*
* @throws Exception
*/

function geo_convert( $input_format, $input_path, $output_format, $output_path ) {

	if ( ! array_key_exists( $input_format, geoPHP::getAdapterMap() ) ) { 
		throw new Exception( "Bad input format: '{$input_format}'\n" );
	}

	if ( ! array_key_exists( $output_format, geoPHP::getAdapterMap() ) ) { 
		throw new Exception( "Bad output format: '{$output_format}'\n" );
	}

	// does the input file exist?

	if ( ! file_exists( $input_path ) ) {
		throw new Exception( "Input file '{$input_path}' not found.\n" );
	}

	// if the output file exists, do not overwrite it.

	if ( file_exists( $output_path ) ) {
		throw new Exception( "Output file '{$output_path}' exists. Aborting\n" );
	}	

	// load the source file and convert into a geometry (possibly with included metadata)


	if ( ! $contents = file_get_contents( $input_path ) ) {
		throw new Exception( "Unable to read source file '{$input_path}'\n" );
	}

	$geometry = geoPHP::load( $contents, $input_format );

	// convert to the start format.

	$output_contents = $geometry->out( $output_format );

	if ( file_put_contents( $output_path, $output_contents ) === FALSE ) {
		throw new Exception( "Unable to write to file '{$output_path}'\n" );
	}

	exit( 0 );

} // end of geo_convert()

/**
* parsing error display function
*/

function FailOnError($error_level, $error_message, $error_file, $error_line, $error_context) {
	echo "$error_level: $error_message in $error_file on line $error_line\n";
	echo "\e[31m" . "FAIL" . "\e[39m\n";
	exit(1);
}

// END
