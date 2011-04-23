<?php

include_once('../geoPHP.inc');

if (geoPHP::geosInstalled()) {
  print "GEOS is installed. ";
}
else {
  print "GEOS is not installed. ";
}

foreach (scandir('./input') as $file) {
  $parts = explode('.',$file);
  if ($parts[0]) {
    $format = $parts[1];
    $value = file_get_contents('./input/'.$file);
    $geometry = geoPHP::load($value, $format);
    test_geometry($geometry);
  }
}

function test_geometry($geometry, $test_adapters = TRUE) {
  
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
  $geometry->getCoordinates();
  $geometry->getGeoInterface();
  
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

  // Test adapter output and input. Do a round-trip and re-test
  if ($test_adapters) {
    foreach (geoPHP::getAdapterMap() as $adapter_key => $adapter_class) {
      if ($adapter != 'google_geocode') { //Don't test google geocoder regularily. Uncomment to test
        $format = $geometry->out($adapter_key);
        $adapter_loader = new $adapter_class();
        $translated_geometry = $adapter_loader->read($format);
        #test_geometry($translated_geometry, FALSE);
      }
    }
  }
  
}

print "Done! Test passes!";