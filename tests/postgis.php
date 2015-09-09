<?php
// Uncomment to test
# run_test();

function run_test() {

  header("Content-type: text");
  
  include_once('../geoPHP.inc');
  
  // Your database test table should contain 3 columns: name (text), type (text), geom (geometry)
  $host =     'localhost';
  $database = 'phayes';
  $table =    'test';
  $column =   'geom';
  $user =     'phayes';
  $pass =     'supersecret';
  
  $connection = pg_connect("host=$host dbname=$database user=$user password=$pass");
  
  // Truncate
  pg_query($connection, "DELETE FROM $table");
  
  // Working with PostGIS and EWKB
  // ----------------------------
  
  foreach (scandir('./input') as $file) {
    $parts = explode('.',$file);
    if ($parts[0]) {
      $name = $parts[0];
      $format = $parts[1];
      $value = file_get_contents('./input/'.$file);
      print '---- Testing '.$file."\n";
      flush();
      $geometry = geoPHP::load($value, $format);
      test_postgis($name, $format, $geometry, $connection, 'wkb');
      $geometry->setSRID(4326);
      test_postgis($name, $format, $geometry, $connection, 'ewkb');
    }
  }
  print "Testing Done!";
}

function test_postgis($name, $type, $geom, $connection, $format) {
  global $table;
  
  // Let's insert into the database using GeomFromWKB
  $insert_string = pg_escape_bytea($geom->out($format));
  pg_query($connection, "INSERT INTO $table (name, type, geom) values ('$name', '$type', GeomFromWKB('$insert_string'))");
  
  // SELECT using asBinary PostGIS
  $result = pg_fetch_all(pg_query($connection, "SELECT asBinary(geom) as geom FROM $table WHERE name='$name'"));
  foreach ($result as $item) {
    $wkb = pg_unescape_bytea($item['geom']); // Make sure to unescape the hex blob
    $geom = geoPHP::load($wkb, $format); // We now a full geoPHP Geometry object
  }
  
  // SELECT and INSERT directly, with no wrapping functions
  $result = pg_fetch_all(pg_query($connection, "SELECT geom as geom FROM $table WHERE name='$name'"));
  foreach ($result as $item) {
    $wkb = pack('H*',$item['geom']);   // Unpacking the hex blob
    $geom = geoPHP::load($wkb, $format); // We now have a geoPHP Geometry
  
    // Let's re-insert directly into postGIS
    // We need to unpack the WKB
    $unpacked = unpack('H*', $geom->out($format));
    $insert_string = $unpacked[1];
    pg_query($connection, "INSERT INTO $table (name, type, geom) values ('$name', '$type', '$insert_string')");
  }

  // SELECT and INSERT using as EWKT (ST_GeomFromEWKT and ST_AsEWKT)
  $result = pg_fetch_all(pg_query($connection, "SELECT ST_AsEWKT(geom) as geom FROM $table WHERE name='$name'"));
  foreach ($result as $item) {
    $wkt = $item['geom']; // Make sure to unescape the hex blob
    $geom = geoPHP::load($wkt, 'ewkt'); // We now a full geoPHP Geometry object

    // Let's re-insert directly into postGIS
    $insert_string = $geom->out('ewkt');
    pg_query($connection, "INSERT INTO $table (name, type, geom) values ('$name', '$type', ST_GeomFromEWKT('$insert_string'))");
  }
}

