<?php

// Uncomment to test
# run_test();

function run_test() {
  header("Content-type: text");
  
  include_once('../geoPHP.inc');
  
  if (geoPHP::geosInstalled()) {
    print "GEOS is installed.\n";
  }
  else {
    print "GEOS is not installed.\n";
  }
  
  foreach (scandir('./input') as $file) {
    $parts = explode('.',$file);
    if ($parts[0]) {
      $format = $parts[1];
      $value = file_get_contents('./input/'.$file);
      print '---- Testing '.$file."\n";
      $geometry = geoPHP::load($value, $format);
      test_adapters($geometry, $format, $value);
      test_methods($geometry);
      test_geometry($geometry);
    }
  }
  print "Testing Done!";
}

function test_geometry($geometry) {
  
  // Test common functions
  $geometry->area();
  $geometry->boundary();
  $geometry->envelope();
  $geometry->getBBox();
  $geometry->centroid();
  $geometry->length();
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
      $output = $geometry->out($adapter_key);
      if ($output) {
        $adapter_loader = new $adapter_class();
        $test_geom_1 = $adapter_loader->read($output);
        $test_geom_2 = $adapter_loader->read($test_geom_1->out($adapter_key));
        
        // Check to make sure a round-trip results in the same geometry
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
      var_dump($geos_type);
      var_dump($norm_type);
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
