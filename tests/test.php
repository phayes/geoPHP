<?php

// Uncomment to test
if (getenv("GEOPHP_RUN_TESTS") == 1) {
	run_test();
}
else {
	print "Skipping tests. Please set GEOPHP_RUN_TESTS=1 environment variable if you wish to run tests\n";
}

function run_test() {
	set_time_limit(0);

	set_error_handler("FailOnError");

	header("Content-type: text");

	include_once('../geoPHP.inc');

	if (geoPHP::geosInstalled()) {
		print "GEOS is installed.\n";
	} else {
		print "GEOS is not installed.\n";
	}

	print '---- Testing MetaData - load GPX, Convert to GeoJSON, compare '."\n";

	foreach (scandir('./input/gpx') as $file) {
		$parts = explode('.',$file);

		if ($parts[0]) {
			$format = $parts[1];

			$gpx_file = './input/gpx/' . $file;

			$value = file_get_contents( $gpx_file );
			print '---- Loading '.$gpx_file."\n";

			$geometry_gpx = geoPHP::load($value, 'gpx' );

			$geojson = $geometry_gpx->out('geojson');

			$geometry_json = geoPHP::load( $geojson, 'geojson' );

			// compare all metadata entries

			if ( ! compareMetaData( $geometry_gpx, $geometry_json ) ) {
				die( "bad comparison\n");
			}

		}

	} // end of foreach

	print '--- original geoPHP tests '.$file."\n";

	foreach (scandir('./input') as $file) {
		$parts = explode('.',$file);

		// avoid directories, swap files (leading dot), etc

		if (( count( $parts ) == 2 ) && ( $parts[0] ) && ( $parts[1] )) {
			$format = $parts[1];
			$value = file_get_contents('./input/'.$file);
			print '---- Testing '.$file."\n";
			$geometry = geoPHP::load($value, $format);

			test_adapters($geometry, $format, $value);

			print "after adapters\n";

			test_methods($geometry);

			print "after methods\n";

			test_geometry($geometry);

			print "after test_geometry\n";

			test_detection($value, $format, $file);

			print "after test_detection\n";

		}
	}

	print "\e[32m" . "PASS". "\e[39m\n";

} // end of run_tests()

/**
* compare metadata between two geometries
*/

function compareMetaData($geom1, $geom2) {

	print( "Comparing " . $geom1->getGeomType() . ' to ' . $geom2->getGeomType() . "\n" );

	if ( strtolower($geom1->getGeomType()) != strtolower( $geom2->getGeomType() ) ) {

		die( "bad geometry\n" );
	}

	$metadata1 = $geom1->getMetaData();
	$metadata2 = $geom2->getMetaData();

	if ( gettype( $metadata1 ) != gettype( $metadata2 ) ) {

		print( "Metadata1:\n" );
		print_r( $metadata1 );

		print( "Metadata2:\n" );
		print_r( $metadata2 );

		die( "meta data differences - type mismatch - metadata1 type " . gettype( $metadata1 ) . " metadata2 type " . gettype( $metadata2 ) . "\n" );
	}

	if (( $metadata1 != NULL ) && ( arrayRecursiveDiff( $metadata1, $metadata2 ))) {

		print( "Metadata1:\n" );
		print_r( $metadata1 );

		print( "Metadata2:\n" );
		print_r( $metadata2 );

		die( "meta data differences from arrayRecursiveDiff\n" );

	}

	switch ( strtolower( $geom1->getGeomType() )) {

		case 'point':

			return true;
			break;

		case 'linestring':
		case 'polygon':
		case 'multipoint':
		case 'multilinestring':
		case 'multipolygon':
		case 'geometrycollection':

			$components1 = $geom1->getComponents();
			$components2 = $geom2->getComponents();

			foreach ($components1 as $key => $comp) {

				if ( ! compareMetaData( $components1[ $key ], $components2[ $key ] ) ) {
					return false;
				}

			}

	}

	return true;

} // end of compareMetaData()

/**
* compare two nested arrays
*/

function arrayRecursiveDiff($aArray1, $aArray2) {
	$aReturn = array();

	foreach ($aArray1 as $mKey => $mValue) {
		if (array_key_exists($mKey, $aArray2)) {
			if (is_array($mValue)) {
				$aRecursiveDiff = arrayRecursiveDiff($mValue, $aArray2[$mKey]);

				if (count($aRecursiveDiff)) { $aReturn[$mKey] = $aRecursiveDiff; }

			} else {

				if ($mValue != $aArray2[$mKey]) {
					$aReturn[$mKey] = $mValue;
				}
			}
		} else {
			$aReturn[$mKey] = $mValue;
		}
	}

	return $aReturn;

} // end of compareRecursiveDiff()

function test_geometry($geometry) {

  // Test common functions
  $geometry->area();
  $geometry->boundary();
  $geometry->envelope();
  $geometry->getBBox();
  $geometry->centroid();
  $geometry->length();
  $geometry->greatCircleLength();
  $geometry->haversineLength();
  $geometry->y();
  $geometry->x();
  $geometry->numGeometries();
  $geometry->geometryN(1);
  $geometry->startPoint();
  $geometry->endPoint();
  $geometry->isRing();
  $geometry->isClosed();
  $geometry->numPoints();
  $geometry->pointN(1);
  $geometry->exteriorRing();
  $geometry->numInteriorRings();
  $geometry->interiorRingN(1);
  $geometry->dimension();
  $geometry->geometryType();
  $geometry->SRID();
  $geometry->setSRID(4326);

  // Aliases
  $geometry->getCentroid();
  $geometry->getArea();
  $geometry->getX();
  $geometry->getY();
  $geometry->getGeos();
  $geometry->getGeomType();
  $geometry->getSRID();
  $geometry->asText();
  $geometry->asBinary();

  // GEOS only functions
  $geometry->geos();
  $geometry->setGeos($geometry->geos());
  $geometry->pointOnSurface();
  $geometry->equals($geometry);
  $geometry->equalsExact($geometry);
  $geometry->relate($geometry);
  $geometry->checkValidity();
  $geometry->isSimple();
  $geometry->buffer(10);
  $geometry->intersection($geometry);
  $geometry->convexHull();
  $geometry->difference($geometry);
  $geometry->symDifference($geometry);
  $geometry->union($geometry);
  $geometry->simplify(0);// @@TODO: Adjust this once we can deal with empty geometries
  $geometry->disjoint($geometry);
  $geometry->touches($geometry);
  $geometry->intersects($geometry);
  $geometry->crosses($geometry);
  $geometry->within($geometry);
  $geometry->contains($geometry);
  $geometry->overlaps($geometry);
  $geometry->covers($geometry);
  $geometry->coveredBy($geometry);
  $geometry->distance($geometry);
  $geometry->hausdorffDistance($geometry);


  // Place holders
  $geometry->hasZ();
  $geometry->is3D();
  $geometry->isMeasured();
  $geometry->isEmpty();
  $geometry->coordinateDimension();
  $geometry->z();
  $geometry->m();
}

function test_adapters($geometry, $format, $input) {
  // Test adapter output and input. Do a round-trip and re-test

  foreach (geoPHP::getAdapterMap() as $adapter_key => $adapter_class) {
    if ($adapter_key != 'google_geocode') { //Don't test google geocoder regularily. Uncomment to test

      print 'test_adapters - ' . $adapter_key . "\n";

      $output = $geometry->out($adapter_key);

      if ($output) {
        $adapter_loader = new $adapter_class();
        $test_geom_1 = $adapter_loader->read($output);
        $test_geom_2 = $adapter_loader->read($test_geom_1->out($adapter_key));

        if ($test_geom_1->out('wkt') != $test_geom_2->out('wkt')) {
          print "Mismatched adapter output in ".$adapter_class."\n";
        }
      }
      else {
        print "Empty output on "  . $adapter_key . "\n";
      }
    }
  }

  // Test to make sure adapter work the same wether GEOS is ON or OFF
  // Cannot test methods if GEOS is not intstalled
  if (!geoPHP::geosInstalled()) return;

  foreach (geoPHP::getAdapterMap() as $adapter_key => $adapter_class) {
    if ($adapter_key != 'google_geocode') { //Don't test google geocoder regularily. Uncomment to test
      // Turn GEOS on
      geoPHP::geosInstalled(TRUE);

      $output = $geometry->out($adapter_key);
      if ($output) {
        $adapter_loader = new $adapter_class();

        $test_geom_1 = $adapter_loader->read($output);

        // Turn GEOS off
        geoPHP::geosInstalled(FALSE);

        $test_geom_2 = $adapter_loader->read($output);

        // Turn GEOS back On
        geoPHP::geosInstalled(TRUE);

        // Check to make sure a both are the same with geos and without
        if ($test_geom_1->out('wkt') != $test_geom_2->out('wkt')) {
          print "Mismatched adapter output between GEOS and NORM in ".$adapter_class."\n";
        }
      }
    }
  }
}


function test_methods($geometry) {
  // Cannot test methods if GEOS is not intstalled
  if (!geoPHP::geosInstalled()) return;

  $methods = array(
    //'boundary', //@@TODO: Uncomment this and fix errors
    'envelope',   //@@TODO: Testing reveales errors in this method -- POINT vs. POLYGON
    'getBBox',
    'x',
    'y',
    'startPoint',
    'endPoint',
    'isRing',
    'isClosed',
    'numPoints',
  );

  foreach ($methods as $method) {
    // Turn GEOS on
    geoPHP::geosInstalled(TRUE);
    $geos_result = $geometry->$method();

    // Turn GEOS off
    geoPHP::geosInstalled(FALSE);
    $norm_result = $geometry->$method();

    // Turn GEOS back On
    geoPHP::geosInstalled(TRUE);

    $geos_type = gettype($geos_result);
    $norm_type = gettype($norm_result);

    if ($geos_type != $norm_type) {
      print 'Type mismatch on '.$method."\n";
      continue;
    }

    // Now check base on type
    if ($geos_type == 'object') {
      $haus_dist = $geos_result->hausdorffDistance(geoPHP::load($norm_result->out('wkt'),'wkt'));

      // Get the length of the diagonal of the bbox - this is used to scale the haustorff distance
      // Using Pythagorean theorem
      $bb = $geos_result->getBBox();
      $scale = sqrt((($bb['maxy'] - $bb['miny'])^2) + (($bb['maxx'] - $bb['minx'])^2));

      // The difference in the output of GEOS and native-PHP methods should be less than 0.5 scaled haustorff units
      if ($haus_dist / $scale > 0.5) {
        print 'Output mismatch on '.$method.":\n";
        print 'GEOS : '.$geos_result->out('wkt')."\n";
        print 'NORM : '.$norm_result->out('wkt')."\n";
        continue;
      }
    }

    if ($geos_type == 'boolean' || $geos_type == 'string') {
      if ($geos_result !== $norm_result) {
        print 'Output mismatch on '.$method.":\n";
        print 'GEOS : '.(string) $geos_result."\n";
        print 'NORM : '.(string) $norm_result."\n";
        continue;
      }
    }

    //@@TODO: Run tests for output of types arrays and float
    //@@TODO: centroid function is non-compliant for collections and strings
  }
}

function test_detection($value, $format, $file) {
  $detected = geoPHP::detectFormat($value);
  if ($detected != $format) {
    if ($detected) print 'detected as ' . $detected . "\n";
    else print "format not detected\n";
  }
  // Make sure it loads using auto-detect
  geoPHP::load($value);
}

function FailOnError($error_level, $error_message, $error_file, $error_line, $error_context) {
  echo "$error_level: $error_message in $error_file on line $error_line\n";
  echo "\e[31m" . "FAIL" . "\e[39m\n";
  exit(1);
}
