<?php

// Include Feed hooks and functionality
require_once(drupal_get_path('module','spatial_import').'/spatial_import.feeds.inc');

// Include Table Wizard hooks and functionality
require_once(drupal_get_path('module','spatial_import').'/spatial_import.tw.inc');

/**
 * Process Shapefile
 *
 * This is where the action happens. Given a path, get all the data out of a
 * zipped shapefile.
 */
function spatial_import_process_shapefile($filepath, $spatial_field = 'geom', $field_type = 'wkt') {
    $now = time();
    $checkpath = realpath(file_directory_temp()).'/spatial_import/'.$now;
    file_check_directory($checkpath,1);
    file_copy($filepath, $checkpath, FILE_EXISTS_REPLACE);
    
    $zip = new ZipArchive;
    if (!is_readable($filepath) || ($zip->open($filepath) !== TRUE)) {
      drupal_set_message(t('zip file nonexistant or unreadable.'), 'error');
      return;
    }
    
    $zip->extractTo($checkpath);
    $zip->close();
    
    $raw_shapefiles = file_scan_directory($checkpath,'\.shp$|\.dbf$|\.shx$|\.prj$');
    $shapefiles = array();
    
    foreach ($raw_shapefiles as $shp) {
      $extension = substr($shp->filename, -3);
      $shapefiles[$shp->name][$extension] = $shp->basename;
    }
    
    // Try to use ogr2ogr to reproject them on the fly to EPSG:4326, and convert to GML
    if (exec('which ogr2ogr')) {
      $i = 0;
      foreach ($shapefiles as $basename => $shapefile) {
        exec('ogr2ogr -t_srs EPSG:4326 '.escapeshellarg($checkpath.'/'.'spatial_import_projected_'.$i.'.shp').' '.escapeshellarg($checkpath.'/'.$shapefile['shp']), $exec_out, $exec_non_sucess);
        $i++;
      }
      $raw_shapefiles = file_scan_directory($checkpath,'spatial_import_projected_[0-9]\.shp$|spatial_import_projected_[0-9]\.dbf$|spatial_import_projected_[0-9]\.shx$|spatial_import_projected_[0-9]\.prj$');
      $shapefiles = array();
      
      foreach ($raw_shapefiles as $shp) {
        $extension = substr($shp->filename, -3);
        $shapefiles[$shp->name][$extension] = $shp->basename;
      }
    }
    
    // We will kludge it big here, and re-zip it so we don't need to re-code what's below. I know this is lame, patches welcome! :-/
    // @@TODO: Don't use this re-zip kludge, actually process the files directly
    
    $zip = new ZipArchive;
    $zip->open($checkpath.'/spatial_import.zip',ZIPARCHIVE::CREATE);
    foreach($shapefiles as $shapefile) {
      foreach ($shapefile as $shapepart) {
        $zip->addFile($checkpath.'/'.$shapepart);
      }
    }
    $zip->close();
    
    $zip = zip_open($checkpath.'/spatial_import.zip');
    
    // Catalog the contents of the zip file.
    $files = array();
    
    while ($res = zip_read($zip)) {
      $ext = strtolower(substr(strrchr(zip_entry_name($res), '.'), 1));
      $files[$ext] = $res;
    }
  
    // The zip file must minimally contain a shp, dbf and shx file.
    if (!isset($files['shp']) || !isset($files['dbf']) || !isset($files['shx'])) {
      drupal_set_message(t('This does not appear to be an archive that contains valid shp data.'), 'error');
      return;
    }
  
    // Get headers and table definition from the dbf file.
    $schema = array('description' => t('Imported shapefile'), 'fields' => array());
    $headers = _spatial_import_dbf_headers($files['dbf'], $schema);
  
    // Shape file headers are stored in the first 100 bytes of the shp/shx files.
    $headers += _spatial_import_shp_headers(zip_entry_read($files['shp'], 100));
    
    // Apply 'direct to database' filters.
    // These are only used by TW Import
    $headers['field_filters'][$spatial_field] = '_spatial_import_shp_data_'.$field_type;
    
    // Get column names
    $columns = array_keys($headers['field_lengths']);

     // Add 'spatial_field' to the list of columns
    $columns[] = $spatial_field;
    
    // Add spatial_field to schema
    if ($field_type == 'geometry') {
      $schema['fields'][$spatial_field] = array (
        'type' => 'geometry',
        'mysql_type' => 'geometry',
        'pgsql_type' => 'GEOMETRY',
        'not null' => TRUE,
      );
    }
    
    if ($field_type == 'wkt') {
      $schema['fields'][$spatial_field] = array (
        'type' => 'text',
        'size' => 'big',
      );
    }
    
    $data = array();
    
    $row_count = 0;
    while ($shp = _spatial_import_shp_get_record($files['shp'])) {
      $values = array();
      $dbf_data = zip_entry_read($files['dbf'], $headers['record_size']);
      $dbf_data = substr($dbf_data, 1); // Remove "record deleted" flag.
      foreach($headers['field_lengths'] as $name => $length) {
        $value = substr($dbf_data, 0, $length);
        $dbf_data = substr($dbf_data, $length);
  
        // Use a predefined function to filter and process each value.
        $process = $headers['field_filters'][$name];
        $values[] = $value;
      }
  
      // Add the geometry text.
      if (is_array($shp['data'])) {
        $values[] = $shp['data']['wkt'];
      }
      else {
        $values[] = $shp['data'];
      }
      $data[] = $values;
      
      $row_count++;
    }
    
    return array(
      'schema' => $schema,
      'headers' => $headers,
      'columns' => $columns, 
      'shapecolumn' => $spatial_field,
      'shapetype' => $field_type,
      'data' => $data,
      'srid' => NULL, //@@TODO
    ); 
}


/**
 * Generate the Drupal-relative path for storing imported files (creating the spatial_imports
 * directory if necessary).
 */
function _spatial_import_file_name($file) {
  $dir = str_replace('\\', '/', getcwd()) . '/' . file_directory_path() . '/spatial_imports';
  if (file_check_directory($dir, TRUE)) {
    return $dir . '/' . $file;
  }
  else {
    return FALSE;
  }
}

function _spatial_import_shp_geo_types($key = null) {
  $geo_types = array(
    0 => 'none',
    1 => 'point',
    3 => 'linestring',  //TODO this is specified as 'polyline' in the standard.
    5 => 'polygon',
    8 => 'multipoint',
    11 => 'pointz',
    13 => 'polylinez',
    15 => 'polygonz',
    18 => 'multipointz',
    21 => 'pointm',
    23 => 'polylinem',
    25 => 'polygonm',
    28 => 'multipointm',
    31 => 'multipatch',
  );

  if ($key) return $geo_types[$key];
  return $geo_types;
}

function _spatial_import_dbf_headers(&$fp, &$schema) {

  // Crack open the dbf file for processing.
  $data = zip_entry_read($fp, 32);

  // Set basic headers.
  $headers = unpack('H2db_id/Cy/Cm/Cd/Lcount/Sheader_size/Srecord_size', $data);
  $date = $headers['m'] .'/'. $headers['d'] .'/'. $headers['y'] + 1900;
  $headers['date'] = strtotime($date);

  // Get the remainder of the dbf headers.
  $field_data = zip_entry_read($fp, $headers['header_size'] - 32);

  $type_map = array(
    'C' => 'char',
    'N' => 'int',
    'L' => 'int',
    'D' => 'date',
    'M' => 'text',
    'F' => 'float',
    'B' => 'blob',
    'Y' => 'decimal',
    'P' => 'blob',
    'I' => 'int',
    'Y' => 'decimal',
    'T' => 'datetime',
    'V' => 'varchar',
    'X' => 'varchar',
    '@' => 'timestamp',
    '0' => 'decimal',
    '+' => 'serial',
  );

  // Gather the column definitions from the field headers.
  $mask = 'A11name/Atype/x4/Clength/Cprecision';
  while (strlen($field_data) >= 32) {
    $d = unpack($mask, $field_data);
    $field_data = substr($field_data, 32);

    // Field name.
    $name = drupal_convert_to_utf8(strtolower(trim($d['name'])), 'ascii');
    $name = preg_replace('/[^a-z0-9_]/', '', $name);

    // Datatype.
    $type = isset($type_map[$d['type']]) ? $type_map[$d['type']] : 'varchar';
    if ($type == 'char' && $d['length'] > 3) $type = 'varchar';

    // Default data processing function, which will escape and UTF8 convert.
    $filter = '_spatial_import_shp_data_text';

    // Use all-purpose PHP functions for standard numeric types.
    if (in_array($type, array('float', 'decimal'))) $filter = 'floatval';
    if (in_array($type, array('int', 'timestamp', 'serial'))) $filter = 'intval';
    if ($type == 'date') $filter = '_spatial_import_shp_data_date';

    // Special case for boolean, convert to int.
    if ($d['type'] == 'L') $filter = '_spatial_import_shp_data_bool';

    $headers['field_filters'][$name] = $filter;

    // Datatype futzing.
    $schema['fields'][$name] = array('type' => $type, 'length' => $d['length']);
    if ($d['precision']) {
      $schema['fields'][$name]['not null'] = TRUE;
      $schema['fields'][$name]['default'] = 0.0;
    }

    // Remove precision from simple types.
    if (in_array($type, array('int', 'float', 'real', 'timestamp'))) {
      unset($schema['fields'][$name]['length']);
    }

    // Store field length for processing.
    $headers['field_lengths'][$name] = $d['length'];
  }

  unset($headers['y'], $headers['m'], $headers['d'], $headers['header_size']);
  return $headers;
}


// 'Direct to Datbase' format processors.  These functions escape and format
// data for direct deposit into the database. They should be depreciated
// as they are wide-open to SQL Injection attacks.  They are currently only
// used by the TW importer, which must write it's own database records.

function _spatial_import_shp_data_geometry($value) {
  $value = db_escape_string(trim(drupal_convert_to_utf8($value, 'ascii')));
  return "GeomFromText('$value')";
}

function _spatial_import_shp_data_wkt($value) {
  $value = db_escape_string(trim(drupal_convert_to_utf8($value, 'ascii')));
  return "'$value'";
}

function _spatial_import_shp_data_text($value) {
  $value = db_escape_string(trim(drupal_convert_to_utf8($value, 'ascii')));
  return "'$value'";
}

function _spatial_import_shp_data_bool($value) {
  return (int) in_array($value, array('t', 'T', 'y', 'Y'));
}

function _spatial_import_shp_data_date($value) {
  // Per the spec, we get the data in YYYYMMDD format.
  $y = (int) substr($value, 0, 4);
  $m = (int) substr($value, 4, 2);
  $d = (int) substr($value, 6, 2);
  return "'$y-$m-$d'";
}



function _spatial_import_shp_headers($data) {
  $mask = 'x28/iversion/igeo_type/dmin_x/dmax_x/dmin_y/dmax_y/dmin_z/dmax_z/dmin_m/dmax_m';
  $headers = unpack($mask, $data);
  $headers['geo_type'] = _spatial_import_shp_geo_types($headers['geo_type']);
  return $headers;
}

function _spatial_import_shp_get_record(&$fp) {
  if (!$row_header = zip_entry_read($fp, 12)) return FALSE;

  $row = unpack('Nnumber/Nlength/igeo_type', $row_header);
  
  $row['geo_type'] = _spatial_import_shp_geo_types($row['geo_type']);
  
  // Look for _spatial_import_shp_get_point(), _spatial_import_shp_get_linestring(), etc.
  
  if (!function_exists($func = '_spatial_import_shp_get_'. $row['geo_type'])) {
    drupal_set_message(t('Unsupported geo type %type found in file.', array('%type' =>  $row['geo_type'])), 'error');
    return FALSE;
  }

  $row['data'] = $func($fp);
  return $row;
}

function _spatial_import_shp_get_point_data(&$fp) {
  $data = unpack('dx/dy', zip_entry_read($fp, 16));
  return (float) $data['x'] .' '. (float) $data['y'];
}

function _spatial_import_shp_get_point(&$fp) {
  return 'POINT('. _spatial_import_shp_get_point_data($fp) .')';
}

function _spatial_import_shp_get_linestring(&$fp) {
  $data = array(
    'bbox'  => unpack('dmin_x/dmin_y/dmax_x/dmax_y', zip_entry_read($fp, 32)),
    'part_count' => current(unpack('i', zip_entry_read($fp, 4))),
    'point_count' => current(unpack('i', zip_entry_read($fp, 4))),
  );

  $data['wkt'] = 'LINESTRING(';
  // Seek to the next index point.
  $part_start = current(unpack('i', zip_entry_read($fp, 4)));

  // Go through all of the points and collect them.
  for ($i = 1; $i <= $data['point_count']; $i++) {
    $data['wkt'] .= _spatial_import_shp_get_point_data($fp) .',';
  }
  $data['wkt'] = substr($data['wkt'], 0, -1) . ')';

  return $data;
}

function _spatial_import_shp_get_polygon(&$fp) {
  $data = array(
    'bbox'  => array(
      'min_x' => _spatial_import_shp_data_fetch('d', $fp),
      'min_y' => _spatial_import_shp_data_fetch('d', $fp),
      'max_x' => _spatial_import_shp_data_fetch('d', $fp),
      'max_y' => _spatial_import_shp_data_fetch('d', $fp),
    ),
    'part_count' => _spatial_import_shp_data_fetch('i', $fp),
    'point_count' => _spatial_import_shp_data_fetch('i', $fp),
  );

  $wkt = '';

  $points_counted = 0; $point_count; $last_offset = 0;
  $parts = array();
  for ($i = 0; $i < ($data['part_count']); $i++) {
    $parts[] = _spatial_import_shp_data_fetch('i', $fp);
  }

  $points_counted = 0;
  foreach ($parts as $i => $offset) {
    if ($next_offset = $parts[$i+1]) {
      $points_counted += $point_count = ($next_offset - $offset);
    }
    else {
      $point_count = $data['point_count'] - $points_counted;
    }

    if (!$point_count) continue;

    // Gather the point data for each part segment.
    $wkt .= '(';
    for ($j = 1; $j <= $point_count; $j++) {
      $wkt .= _spatial_import_shp_get_point_data($fp) .',';
    }
    $wkt = substr($wkt, 0, -1) . '),';
  }
  $data['wkt'] = 'POLYGON('. substr($wkt, 0, -1) .')';
  return $data;
}

function _spatial_import_shp_data_fetch($type, &$fp, $offset = null) {
  if (in_array($type, array('i', 'N'))) {
    $length = 4;
  }
  elseif (in_array($type, array('d' ))) {
    $length = 8;
  }
  if (is_string($fp)) {
    return current(unpack($type, substr($fp, $offset, $length)));
  }
  return current(unpack($type, zip_entry_read($fp, $length)));
}
