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
  public function read($hash) {
    $ll = $this->decode($hash);
    return new Point($ll['lon'], $ll['lat'], NULL);
  }

  /**
   * Convert the geometry to geohash.
   * @return string the geohash or null when the $geometry is not a Point
   * @param Point $geometry
   * @see GeoAdapter::write()
   */
  public function write(Geometry $geometry){
    if($geometry->geometryType()==='Point'){
      return $this->encode($geometry);
    } else {
      return NULL;
    }
  }

  /**
   * @return string geohash
   * @param Point $point
   * @author algorithm based on code by Alexander Songe <a@songe.me>
   * @see https://github.com/asonge/php-geohash/issues/1
   */
  private function encode($point){
    $lap = strlen($point->y())-strpos($point->y(),".");
    $lop = strlen($point->x())-strpos($point->x(),".");
    $precision = pow(10,-max($lap-1,$lop-1,0))/2;

    $minlat =  -90;
    $maxlat =   90;
    $minlng = -180;
    $maxlng =  180;
    $latE   =   90;
    $lngE   =  180;
    $i = 0;
    $error = 180;
    $hash='';
    while($error>=$precision) {
      $chr = 0;
      for($b=4;$b>=0;--$b) {
        if((1&$b) == (1&$i)) {
          // even char, even bit OR odd char, odd bit...a lng
          $next = ($minlng+$maxlng)/2;
          if($point->x()>$next) {
            $chr |= pow(2,$b);
            $minlng = $next;
          } else {
            $maxlng = $next;
          }
          $lngE /= 2;
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
      $error = min($latE,$lngE);
    }
    return $hash;
  }

  /**
   * @param string $hash a geohash
   * @author algorithm based on code by Alexander Songe <a@songe.me>
   * @see https://github.com/asonge/php-geohash/issues/1
   */
  private function decode($hash){
    $ll = array('lat'=>NULL,'lon'=>NULL);
    $minlat =  -90;
    $maxlat =   90;
    $minlng = -180;
    $maxlng =  180;
    $latE   =   90;
    $lngE   =  180;
    for($i=0,$c=strlen($hash);$i<$c;$i++) {
      $v = strpos($this->table,$hash[$i]);
      if(1&$i) {
        if(16&$v)$minlat = ($minlat+$maxlat)/2; else $maxlat = ($minlat+$maxlat)/2;
        if(8&$v) $minlng = ($minlng+$maxlng)/2; else $maxlng = ($minlng+$maxlng)/2;
        if(4&$v) $minlat = ($minlat+$maxlat)/2; else $maxlat = ($minlat+$maxlat)/2;
        if(2&$v) $minlng = ($minlng+$maxlng)/2; else $maxlng = ($minlng+$maxlng)/2;
        if(1&$v) $minlat = ($minlat+$maxlat)/2; else $maxlat = ($minlat+$maxlat)/2;
        $latE /= 8;
        $lngE /= 4;
      } else {
        if(16&$v)$minlng = ($minlng+$maxlng)/2; else $maxlng = ($minlng+$maxlng)/2;
        if(8&$v) $minlat = ($minlat+$maxlat)/2; else $maxlat = ($minlat+$maxlat)/2;
        if(4&$v) $minlng = ($minlng+$maxlng)/2; else $maxlng = ($minlng+$maxlng)/2;
        if(2&$v) $minlat = ($minlat+$maxlat)/2; else $maxlat = ($minlat+$maxlat)/2;
        if(1&$v) $minlng = ($minlng+$maxlng)/2; else $maxlng = ($minlng+$maxlng)/2;
        $latE /= 4;
        $lngE /= 8;
      }
    }
    $ll['lat'] = round(($minlat+$maxlat)/2, max(1, -round(log10($latE)))-1);
    $ll['lon'] = round(($minlng+$maxlng)/2, max(1, -round(log10($lngE)))-1);
    return $ll;
  }
}
