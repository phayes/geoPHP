<?php
/**
 * PHP Geometry GeoHash encoder/decoder.
 *
 * @author prinsmc
 * @see http://en.wikipedia.org/wiki/Geohash
 *
 */
class GeoHash extends GeoAdapter{
  private $table = "0123456789bcdefghjkmnpqrstuvwxyz";

  /**
   * Convert the geohash to a Point. The point is 2-dimensional.
   * @return Point the converted geohash
   * @param string $hash a geohash
   * @see GeoAdapter::read()
   */
  public function read($hash, $as_grid = FALSE) {
    $ll = $this->decode($hash);
    if (!$as_grid) {
      return new Point($ll['medlon'], $ll['medlat']);
    }
    else {
      return new Polygon(array(
        new LineString(array(
          new Point($ll['minlon'], $ll['maxlat']),
          new Point($ll['maxlon'], $ll['maxlat']),
          new Point($ll['maxlon'], $ll['minlat']),
          new Point($ll['minlon'], $ll['minlat']),
          new Point($ll['minlon'], $ll['maxlat']),
        ))
      ));
    }
  }

  /**
   * Convert the geometry to geohash.
   * @return string the geohash or null when the $geometry is not a Point
   * @param Point $geometry
   * @see GeoAdapter::write()
   */
  public function write(Geometry $geometry, $precision = NULL){
    if($geometry->geometryType() === 'Point'){
      return $this->encodePoint($geometry, $precision);
    } else {
      // The geohash is the hash grid ID that fits the envelope
      $envelope = $geometry->envelope();
      $geohashes = array();
      $geohash = '';
      foreach ($envelope->getPoints() as $point) {
        $geohashes[] = $this->encodePoint($point, 0.0000001);
      }
      $i = 0;
      while ($i < strlen($geohashes[0])) {
        $char = $geohashes[0][$i];
        foreach ($geohashes as $hash) {
          if ($hash[$i] != $char) {
            return $geohash;
          }
        }
        $geohash .= $char;
        $i++;
      }
      return $geohash;
    }
  }

  /**
   * @return string geohash
   * @param Point $point
   * @author algorithm based on code by Alexander Songe <a@songe.me>
   * @see https://github.com/asonge/php-geohash/issues/1
   */
  private function encodePoint($point, $precision = NULL){
    if ($precision === NULL) {
      $lap = strlen($point->y())-strpos($point->y(),".");
      $lop = strlen($point->x())-strpos($point->x(),".");
      $precision = pow(10,-max($lap-1,$lop-1,0))/2;
    }

    $minlat =  -90;
    $maxlat =   90;
    $minlon = -180;
    $maxlon =  180;
    $latE   =   90;
    $lonE   =  180;
    $i = 0;
    $error = 180;
    $hash='';
    while($error>=$precision) {
      $chr = 0;
      for($b=4;$b>=0;--$b) {
        if((1&$b) == (1&$i)) {
          // even char, even bit OR odd char, odd bit...a lon
          $next = ($minlon+$maxlon)/2;
          if($point->x()>$next) {
            $chr |= pow(2,$b);
            $minlon = $next;
          } else {
            $maxlon = $next;
          }
          $lonE /= 2;
        } else {
          // odd char, even bit OR even char, odd bit...a lat
          $next = ($minlat+$maxlat)/2;
          if($point->y()>$next) {
            $chr |= pow(2,$b);
            $minlat = $next;
          } else {
            $maxlat = $next;
          }
          $latE /= 2;
        }
      }
      $hash .= $this->table[$chr];
      $i++;
      $error = min($latE,$lonE);
    }
    return $hash;
  }

  /**
   * @param string $hash a geohash
   * @author algorithm based on code by Alexander Songe <a@songe.me>
   * @see https://github.com/asonge/php-geohash/issues/1
   */
  private function decode($hash){
    $ll = array();
    $minlat =  -90;
    $maxlat =   90;
    $minlon = -180;
    $maxlon =  180;
    $latE   =   90;
    $lonE   =  180;
    for($i=0,$c=strlen($hash);$i<$c;$i++) {
      $v = strpos($this->table,$hash[$i]);
      if(1&$i) {
        if(16&$v)$minlat = ($minlat+$maxlat)/2; else $maxlat = ($minlat+$maxlat)/2;
        if(8&$v) $minlon = ($minlon+$maxlon)/2; else $maxlon = ($minlon+$maxlon)/2;
        if(4&$v) $minlat = ($minlat+$maxlat)/2; else $maxlat = ($minlat+$maxlat)/2;
        if(2&$v) $minlon = ($minlon+$maxlon)/2; else $maxlon = ($minlon+$maxlon)/2;
        if(1&$v) $minlat = ($minlat+$maxlat)/2; else $maxlat = ($minlat+$maxlat)/2;
        $latE /= 8;
        $lonE /= 4;
      } else {
        if(16&$v)$minlon = ($minlon+$maxlon)/2; else $maxlon = ($minlon+$maxlon)/2;
        if(8&$v) $minlat = ($minlat+$maxlat)/2; else $maxlat = ($minlat+$maxlat)/2;
        if(4&$v) $minlon = ($minlon+$maxlon)/2; else $maxlon = ($minlon+$maxlon)/2;
        if(2&$v) $minlat = ($minlat+$maxlat)/2; else $maxlat = ($minlat+$maxlat)/2;
        if(1&$v) $minlon = ($minlon+$maxlon)/2; else $maxlon = ($minlon+$maxlon)/2;
        $latE /= 4;
        $lonE /= 8;
      }
    }
    $ll['minlat'] = $minlat;
    $ll['minlon'] = $minlon;
    $ll['maxlat'] = $maxlat;
    $ll['maxlon'] = $maxlon;
    $ll['medlat'] = round(($minlat+$maxlat)/2, max(1, -round(log10($latE)))-1);
    $ll['medlon'] = round(($minlon+$maxlon)/2, max(1, -round(log10($lonE)))-1);
    return $ll;
  }
}
