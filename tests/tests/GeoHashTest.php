<?php

namespace GeoPHPTests;

use GeoPHP\Adapter\GeoHash;

class GeoHashTest extends \PHPUnit_Framework_TestCase
{
    /**
     * test cases for adjacent geohashes.
     */
    public function testAdjacent()
    {
        $geoHash = new Geohash();
        $this->assertEquals(
            'xne',
            $geoHash->adjacent('xn7', 'top'),
            'Did not find correct top adjacent geohash for xn7'
        );

        $this->assertEquals(
            'xnk',
            $geoHash->adjacent('xn7', 'right'),
            'Did not find correct right adjacent geohash for xn7'
        );

        $this->assertEquals(
            'xn5',
            $geoHash->adjacent('xn7', 'bottom'),
            'Did not find correct bottom adjacent geohash for xn7'
        );

        $this->assertEquals(
            'xn6',
            $geoHash->adjacent('xn7', 'left'),
            'Did not find correct left adjacent geohash for xn7'
        );

        $this->assertEquals(
            'xnd',
            $geoHash->adjacent($geoHash->adjacent('xn7', 'left'), 'top'),
            'Did not find correct top-left adjacent geohash for xn7'
        );
    }
}
