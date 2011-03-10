<?php

include_once("lib/GeoJSON/GeoJSON.class.php");
include_once("lib/GeoJSON/adapters/GeoJSON_Adapter.class.php");
include_once("lib/GeoJSON/adapters/z_dependant/GeoJSON_Adapter_Doctrine.class.php");
include_once("lib/GeoJSON/WKT/WKT.class.php");
include_once("lib/GeoJSON/lib/Feature.class.php");
include_once("lib/GeoJSON/lib/FeatureCollection.class.php");
include_once("lib/GeoJSON/lib/Geometry/Geometry.class.php");
include_once("lib/GeoJSON/lib/Geometry/Point.class.php");
include_once("lib/GeoJSON/lib/Geometry/Collection.class.php");
include_once("lib/GeoJSON/lib/Geometry/LineString.class.php");
include_once("lib/GeoJSON/lib/Geometry/MultiPoint.class.php");
include_once("lib/GeoJSON/lib/Geometry/LinearRing.class.php");
include_once("lib/GeoJSON/lib/Geometry/Polygon.class.php");
include_once("lib/GeoJSON/lib/Geometry/MultiLineString.class.php");
include_once("lib/GeoJSON/lib/Geometry/MultiPolygon.class.php");
include_once("lib/GeoJSON/lib/Geometry/GeometryCollection.class.php");

class GeometryLoader {
  
  function load($data, $type = 'autodetect') {
    $type_map = array (
      'wkt' => 'WKT',
      'json' => 'GeoJSON',
    );
    
    $processor_type = $type_map[$type];
    $processor = new $processor_type();
    
    return $processor->read($data);
  }
}
