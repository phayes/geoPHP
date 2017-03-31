<?php

require '../vendor/autoload.php';

use \geoPHP\geoPHP;

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

  if (geoPHP::geosInstalled()) {
    print "GEOS is installed.\n";
  }
  else {
    print "GEOS is not installed.\n";
  }

  $start = microtime(true);
  foreach (scandir('./input') as $file) {
    $parts = explode('.',$file);
    if ($parts[0]) {
      $format = $parts[1];
      $value = file_get_contents('./input/'.$file);
      print '---- Testing ' . $file . "\n";
      $geometry = geoPHP::load($value, $format);
      if (getenv("VERBOSE") == 1) {
        echo "-- Adapters\n";
        test_adapters($geometry, $format, $value);
        echo "-- Methods\n";
        test_methods($geometry);
        echo "-- Geometry\n";
        test_geometry($geometry);
        echo "-- Detection\n";
        test_detection($value, $format, $file);
      } else {
        test_adapters($geometry, $format, $value);
        test_methods($geometry);
        test_geometry($geometry);
        test_detection($value, $format, $file);
      }
    }
  }
  print "\nSuccessfully completed under " . sprintf('%.3f', microtime(true) - $start)
          . " seconds, using maximum " . sprintf('%.3f', memory_get_peak_usage() /1024/1024) . " MB\n";
  print "\e[32m" . "PASS". "\e[39m\n";
}

/**
 * @param \geoPHP\Geometry\Geometry $geometry
 */
function test_geometry($geometry) {
  // Test common functions
  $geometry->area();
  try {
    $geometry->boundary();
  } catch (\geoPHP\Exception\UnsupportedMethodException $e) {
    // TODO remove this once Polygon::boundary() get implemented
    if (getenv("VERBOSE") == 1) {
      print "\e[33m\t" . $e->getMessage() . "\e[39m\n";
    }
  }
  $geometry->envelope();
  $geometry->getBBox();
  $geometry->centroid();
  $geometry->length();
  $geometry->greatCircleLength();
  $geometry->haversineLength();
  $geometry->x();
  $geometry->y();
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
  $geometry->coordinateDimension();
  $geometry->geometryType();
  $geometry->SRID();
  $geometry->setSRID(4326);
  $geometry->hasZ();
  $geometry->isMeasured();
  $geometry->isEmpty();
  $geometry->coordinateDimension();

  // Aliases
  $geometry->getCentroid();
  $geometry->getArea();
  $geometry->getX();
  $geometry->getY();
  $geometry->geos();
  $geometry->getSRID();
  $geometry->asText();
  $geometry->asBinary();

  // GEOS only functions
  try {
    $geometry->isSimple();
    $geometry->contains($geometry);
    $geometry->distance($geometry);
    $geometry->overlaps($geometry);
    $geometry->getGeos();
    $geometry->setGeos($geometry->getGeos());
    $geometry->pointOnSurface();
    $geometry->equals($geometry);
    $geometry->equalsExact($geometry);
    $geometry->relate($geometry);
    $geometry->checkValidity();
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
    $geometry->covers($geometry);
    $geometry->coveredBy($geometry);
    $geometry->hausdorffDistance($geometry);
  } catch (\Exception $e) {
    if (getenv("VERBOSE") == 1) {
      print "\e[33m\t" . $e->getMessage() . "\e[39m\n";
    }
  }

}

/**
 * @param \geoPHP\Geometry\Geometry $geometry
 * @param string $format
 * @param string $input
 */
function test_adapters($geometry, $format, $input) {
  // Test adapter output and input. Do a round-trip and re-test
  foreach (geoPHP::getAdapterMap() as $adapter_key => $adapter_class) {
    if ($adapter_key == 'google_geocode') {
      //Don't test google geocoder regularily. Uncomment to test
      continue;
    }
    if (getenv("VERBOSE") == 1) {
      print ' ' . $adapter_class . "\n";
    }
    $output = $geometry->out($adapter_key);
    if ($output) {
      $adapter_name = 'geoPHP\\Adapter\\' . $adapter_class;
      /** @var \geoPHP\Adapter\GeoAdapter $adapter_loader */
      $adapter_loader = new $adapter_name();
      $test_geom_1 = $adapter_loader->read($output);
      $test_geom_2 = $adapter_loader->read($test_geom_1->out($adapter_key));

      if ($test_geom_1->out('wkt') != $test_geom_2->out('wkt')) {
        print "\e[33m" . "\tMismatched adapter output in " . $adapter_class . "\e[39m\n";
      }
    }
    else {
      print "\e[33m" . "\tEmpty output on "  . $adapter_key . "\e[39m\n";
    }
  }

  // Test to make sure adapter work the same wether GEOS is ON or OFF
  // Cannot test methods if GEOS is not intstalled
  if (!geoPHP::geosInstalled()) return;
  if (getenv("VERBOSE") == 1) {
    echo "Testing with GEOS\n";
  }
  foreach (geoPHP::getAdapterMap() as $adapter_key => $adapter_class) {
    if ($adapter_key == 'google_geocode') {
      //Don't test google geocoder regularily. Uncomment to test
      continue;
    }

    if (getenv("VERBOSE") == 1) {
      echo ' ' . $adapter_class . "\n";
    }
    // Turn GEOS on
    geoPHP::geosInstalled(TRUE);

    try {
      $output = $geometry->out($adapter_key);
      if ($output) {
        $adapter_name = 'geoPHP\\Adapter\\' . $adapter_class;
        /** @var \geoPHP\Adapter\GeoAdapter $adapter_loader */
        $adapter_loader = new $adapter_name();

        $test_geom_1 = $adapter_loader->read($output);

        // Turn GEOS off
        geoPHP::geosInstalled(false);

        $test_geom_2 = $adapter_loader->read($output);

        // Turn GEOS back On
        geoPHP::geosInstalled(true);

        // Check to make sure a both are the same with geos and without
        if ($test_geom_1->out('wkt') != $test_geom_2->out('wkt')) {
          //var_dump($test_geom_1->out('wkt'), $test_geom_2->out('wkt'));

          $f = fopen('test_geom1.wkt', 'w+');
          fwrite($f, $test_geom_1->out('wkt'));
          fclose($f);
          $f = fopen('test_geom2.wkt', 'w+');
          fwrite($f, $test_geom_2->out('wkt'));
          fclose($f);
          print "Mismatched adapter output between GEOS and NORM in " . $adapter_class . "\n";
        }
      }
    } catch (\geoPHP\Exception\UnsupportedMethodException $e) {
      if (getenv("VERBOSE") == 1) {
        print "\e[33m\t" . $e->getMessage() . "\e[39m\n";
      }
    }
  }
}


function test_methods($geometry) {
  // Cannot test methods if GEOS is not intstalled
  if (!geoPHP::geosInstalled()) return;

  $methods = array(
    'boundary',
    'envelope',
    'getBoundingBox',
    'x',
    'y',
    'z',
    'm',
    'startPoint',
    'endPoint',
    'isRing',
    'isClosed',
    'numPoints',
    'centroid',
    'length',
    'isEmpty',
    'isSimple'
  );

  foreach ($methods as $method) {
    try {
      // Turn GEOS on
      geoPHP::geosInstalled(TRUE);
      /** @var \geoPHP\Geometry\Geometry $geos_result */
      $geos_result = $geometry->$method();

      // Turn GEOS off
      geoPHP::geosInstalled(FALSE);

      /** @var \geoPHP\Geometry\Geometry $norm_result */
      $norm_result = $geometry->$method();

      // Turn GEOS back On
      geoPHP::geosInstalled(TRUE);

      $geos_type = gettype($geos_result);
      $norm_type = gettype($norm_result);

      if ($geos_type != $norm_type) {
        print "\e[33m" . "Type mismatch on " . $method . "\e[39m\n";
        continue;
      }

      // Now check base on type
      if ($geos_type == 'object') {
        $haus_dist = $geos_result->hausdorffDistance(geoPHP::load($norm_result->out('wkt'),'wkt'));

        // Get the length of the diagonal of the bbox - this is used to scale the haustorff distance
        // Using Pythagorean theorem
        $bb = $geos_result->getBoundingBox();
        $scale = sqrt((($bb['maxy'] - $bb['miny'])^2) + (($bb['maxx'] - $bb['minx'])^2));

        // The difference in the output of GEOS and native-PHP methods should be less than 0.5 scaled haustorff units
        if ($haus_dist / $scale > 0.5) {
          print "\e[33m" . "Output mismatch on " . $method . "\e[39m\n";
          print 'GEOS : '.$geos_result->out('wkt')."\n";
          print 'NORM : '.$norm_result->out('wkt')."\n";
          continue;
        }
      }

      if ($geos_type == 'boolean' || $geos_type == 'string') {
        if ($geos_result !== $norm_result) {
          print "\e[33m" . "Output mismatch on " . $method . "\e[39m\n";
          print 'GEOS : '.(string) $geos_result."\n";
          print 'NORM : '.(string) $norm_result."\n";
          continue;
        }
      }
    } catch (\geoPHP\Exception\UnsupportedMethodException $e) {
      if (getenv("VERBOSE") == 1) {
        print "\e[33m\t" . $e->getMessage() . "\e[39m\n";
      }
    }

    //@@TODO: Run tests for output of types arrays and float
    //@@TODO: centroid function is non-compliant for collections and strings
  }
}

function test_detection($value, $format, $file) {
  $detected = geoPHP::detectFormat($value);
  if ($detected != $format) {
    if ($detected) {
      print 'detected as ' . $detected . "\n";
    } else {
      print "format not detected\n";
    }
  }
  // Make sure it loads using auto-detect
  geoPHP::load($value);
}

function FailOnError($error_level, $error_message, $error_file, $error_line, $error_context) {
  echo "$error_level: $error_message in $error_file on line $error_line\n";
  echo "\e[31m" . "FAIL" . "\e[39m\n";
  exit(1);
}
