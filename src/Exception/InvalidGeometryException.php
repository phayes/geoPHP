<?php

namespace geoPHP\Exception;

/**
 * Class InvalidGeometryException
 * Invalid geometry means that it doesn't meet the basic requirements to be valid
 * Eg. a LineString with only one point
 *
 * @package geoPHP\Exception
 */
class InvalidGeometryException extends \Exception {
}
