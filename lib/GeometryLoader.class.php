<?php
/*
 * (c) Patrick Hayes 2011
 *
 * This code is open-source and licenced under the Modified BSD License.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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