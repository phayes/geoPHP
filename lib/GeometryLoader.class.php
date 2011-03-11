<?php

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
