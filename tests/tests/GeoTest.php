<?php

namespace GeoPHPTests;

use GeoPHP\GeoPHP;

class GeoTest extends \PHPUnit_Framework_TestCase
{

    public function testGeos()
    {
        if (!GeoPHP::geosInstalled()) {
            echo 'Skipping GEOS -- not installed';

            return;
        }
        foreach (scandir(__DIR__ . '/../input') as $file) {
            $parts = explode('.', $file);
            if ($parts[0]) {
                $format = $parts[1];
                $value = file_get_contents(__DIR__ . '/../input/' . $file);
                echo "\nloading: " . $file . " for format: " . $format;
                $geometry = GeoPHP::load($value, $format);

                $geosMethods = [
                    ['name' => 'geos'],
                    ['name' => 'setGeos', 'argument' => $geometry->geos()],
                    ['name' => 'PointOnSurface'],
                    ['name' => 'equals', 'argument' => $geometry],
                    ['name' => 'equalsExact', 'argument' => $geometry],
                    ['name' => 'relate', 'argument' => $geometry],
                    ['name' => 'checkValidity'],
                    ['name' => 'isSimple'],
                    ['name' => 'buffer', 'argument' => '10'],
                    ['name' => 'intersection', 'argument' => $geometry],
                    ['name' => 'convexHull'],
                    ['name' => 'difference', 'argument' => $geometry],
                    ['name' => 'symDifference', 'argument' => $geometry],
                    ['name' => 'union', 'argument' => $geometry],
                    ['name' => 'simplify', 'argument' => '0'],
                    ['name' => 'disjoint', 'argument' => $geometry],
                    ['name' => 'touches', 'argument' => $geometry],
                    ['name' => 'intersects', 'argument' => $geometry],
                    ['name' => 'crosses', 'argument' => $geometry],
                    ['name' => 'within', 'argument' => $geometry],
                    ['name' => 'contains', 'argument' => $geometry],
                    ['name' => 'overlaps', 'argument' => $geometry],
                    ['name' => 'covers', 'argument' => $geometry],
                    ['name' => 'coveredBy', 'argument' => $geometry],
                    ['name' => 'distance', 'argument' => $geometry],
                    ['name' => 'hausdorffDistance', 'argument' => $geometry],
                ];

                foreach ($geosMethods as $method) {
                    $argument = null;
                    $method_name = $method['name'];
                    if (isset($method['argument'])) {
                        $argument = $method['argument'];
                    }

                    switch ($method_name) {
                        case 'isSimple':
                        case 'equals':
                        case 'geos':
                            if ($geometry->geometryType() == 'Point') {
                                $this->assertNotNull(
                                    $geometry->$method_name($argument),
                                    'Failed on ' . $method_name . ' (test file: ' . $file . ')'
                                );
                            }
                            if ($geometry->geometryType() == 'LineString') {
                                $this->assertNotNull(
                                    $geometry->$method_name($argument),
                                    'Failed on ' . $method_name . ' (test file: ' . $file . ')'
                                );
                            }
                            if ($geometry->geometryType() == 'MultiLineString') {
                                $this->assertNotNull(
                                    $geometry->$method_name($argument),
                                    'Failed on ' . $method_name . ' (test file: ' . $file . ')'
                                );
                            }
                            break;
                        default:
                            if ($geometry->geometryType() == 'Point') {
                                $this->assertNotNull(
                                    $geometry->$method_name($argument),
                                    'Failed on ' . $method_name . ' (test file: ' . $file . ')'
                                );
                            }
                            if ($geometry->geometryType() == 'LineString') {
                                $this->assertNotNull(
                                    $geometry->$method_name($argument),
                                    'Failed on ' . $method_name . ' (test file: ' . $file . ')'
                                );
                            }
                            if ($geometry->geometryType() == 'MultiLineString') {
                                $this->assertNull(
                                    $geometry->$method_name($argument),
                                    'Failed on ' . $method_name . ' (test file: ' . $file . ')'
                                );
                            }
                            break;
                    }
                }
            }
        }
    }
}
