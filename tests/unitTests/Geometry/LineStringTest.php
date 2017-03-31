<?php

use \geoPHP\Geometry\Point;
use \geoPHP\Geometry\LineString;

/**
 * Unit tests of LineString geometry
 *
 * @group geometry
 *
 */
class LineStringTest extends PHPUnit_Framework_TestCase {

    private function createPoints($coordinateArray) {
        $points = [];
        foreach ($coordinateArray as $point) {
            $points[] = Point::fromArray($point);
        }
        return $points;
    }

    public function providerValidComponents() {
        return [
                [[]],                                       // Empty
                [[[0, 0], [1, 1]]],                         // LineString with two points
                [[[0, 0, 0], [1, 1, 1]]],                   // LineString Z
                [[[0, 0, null, 0], [1, 1, null, 1]]],       // LineString M
                [[[0, 0, 0, 0], [1, 1, 1, 1]]],             // LineString ZM
                [[[0, 0], [1, 1], [2, 2], [3, 3], [4, 4]]], // LineString with 5 points
        ];
    }

    /**
     * @dataProvider providerValidComponents
     *
     * @param $points
     */
    public function testConstructor($points) {
        $this->assertNotNull(new LineString($this->createPoints($points)));
    }

    /**
     *
     * @expectedException geoPHP\Exception\InvalidGeometryException
     */
    public function testConstructorInvalidComponentThrowsException() {
        // Empty point
        new LineString([new Point()]);
    }

    /**
     * @expectedException geoPHP\Exception\InvalidGeometryException
     */
    public function testConstructorSinglePointThrowsException() {
        new LineString([new Point(1, 2)]);
    }

    /**
     */
    public function testConstructorWrongComponentTypeThrowsException() {
        $this->assertTrue(true);
        //TODO implement this
        //$this->setExpectedException('geoPHP\Exception\InvalidGeometryException');
        //new LineString([new LineString([new Point(1,2), new Point(3,4)]), new LineString([new Point(5,6), new Point(7,8)])]);
    }

    public function testFromArray() {
        $this->assertEquals(
                LineString::fromArray([[1,2,3,4], [5,6,7,8]]),
                new LineString([new Point(1,2,3,4), new Point(5,6,7,8)])
        );
    }

    public function testGeometryType() {
        $line = new LineString();

        $this->assertEquals(LineString::LINE_STRING, $line->geometryType());

        $this->assertInstanceOf('\geoPHP\Geometry\LineString', $line);
        $this->assertInstanceOf('\geoPHP\Geometry\Curve', $line);
    }

    public function testIsEmpty() {
        $line1 = new LineString();
        $this->assertTrue($line1->isEmpty());

        $line2 = new LineString($this->createPoints([[1,2], [3,4]]));
        $this->assertFalse($line2->isEmpty());
    }

    public function testDimension() {
        $this->assertSame((new LineString())->dimension(), 1);
    }

    /**
     * @dataProvider providerValidComponents
     *
     * @param $points
     */
    public function testNumPoints($points) {
        $line = new LineString($this->createPoints($points));
        $this->assertEquals($line->numPoints(), count($points));
    }

    /**
     * @dataProvider providerValidComponents
     *
     * @param $points
     */    
    public function testPointN($points) {
        $components = $this->createPoints($points);
        $line = new LineString($components);

        $this->assertNull($line->pointN(0));

        for ($i=1; $i < count($components); $i++) {
            // positive n
            $this->assertEquals($components[$i-1], $line->pointN($i));

            // negative n
            $this->assertEquals($components[count($components)-$i], $line->pointN(-$i));
        }
    }

    public function providerCentroid() {
        return [
                [[], new Point()],                                  // empty linestring
                [[[0, 0], [0, 0]], new Point(0, 0)],                // null coordinates
                [[[0, 0], [1, 1]], new Point(0.5, 0.5)],            // base vectors
                [[[0, 0], [-1, -1]], new Point(-0.5, -0.5)],        // negative base vectors
                [[                                                  // random geographical coordinates
                        [20.0390625, -16.97274101999901],
                        [-11.953125, 17.308687886770034],
                        [0.703125, 52.696361078274485],
                        [30.585937499999996, 52.696361078274485],
                        [42.5390625, 41.77131167976407],
                        [-13.359375, 38.8225909761771],
                        [18.984375, 17.644022027872726]
                ], new Point(8.71798087550578, 31.1304531386738)],
                [[[170, 47], [-170, 47]], new Point(0, 47)]         // crossing the antimeridian
        ];
    }

    /**
     * @dataProvider providerCentroid
     *
     * @param $points
     * @param $centroidPoint
     */
    public function testCentroid($points, $centroidPoint) {
        $line = LineString::fromArray($points);
        $centroid = $line->centroid();
        $centroid->setGeos(null);

        $this->assertEquals($centroidPoint, $centroid);
    }

    public function providerIsSimple() {
        return [
                [[[0, 0], [0, 10]], true],
                [[[1, 1], [2, 2], [2, 3.5], [1, 3], [1, 2], [2, 1]], false],
        ];
    }

    /**
     * @dataProvider providerIsSimple
     *
     * @param $points
     * @param $result
     */
    public function testIsSimple($points, $result) {
        $line = LineString::fromArray($points);

        $this->assertSame($line->isSimple(), $result);
    }

    public function providerLength() {
        return [
                [[[0, 0], [10, 0]], 10.0],
                [[[1, 1], [2, 2], [2, 3.5], [1, 3], [1, 2], [2, 1]], 6.44646111349608],
        ];
    }

    /**
     * @dataProvider providerLength
     *
     * @param $points
     * @param $result
     */
    public function testLength($points, $result) {
        $line = LineString::fromArray($points);

        $this->assertSame($line->length(), $result);
    }

    public function providerLength3D() {
        return [
                [[[0, 0, 0], [10, 0, 10]], 14.142135623731],
                [[[1, 1, 0], [2, 2, 2], [2, 3.5, 0], [1, 3, 2], [1, 2, 0], [2, 1, 2]], 11.926335310544],
        ];
    }

    /**
     * @dataProvider providerLength3D
     *
     * @param $points
     * @param $result
     */
    public function testLength3D($points, $result) {
        $line = LineString::fromArray($points);

        $this->assertSame($line->length3D(), $result);
    }

    public function providerLengths() {
        return [
                [[[0, 0], [0, 0]], [
                        'greatCircle' => 0.0,
                        'haversine'   => 0.0,
                        'vincenty'    => 0.0,
                        'PostGIS'     => 0.0
                ]],
                [[[0, 0], [10, 0]], [
                        'greatCircle' => 1113194.9079327357,
                        'haversine'   => 1113194.9079327371,
                        'vincenty'    => 1113194.9079322326,
                        'PostGIS'     => 1113194.90793274
                ]],
                [[[0, 0, 0], [10, 0, 5000]], [
                        'greatCircle' => 1113206.136817154,
                        'haversine'   => 1113194.9079327371,
                        'vincenty'    => 1113194.9079322326,
                        'PostGIS'     => 1113194.90793274
                ]],
                [[[0, 47], [10, 47]], [
                        'greatCircle' => 758681.06593496865,
                        'haversine'   => 758681.06593497901,
                        'vincenty'    => 760043.0186457854,
                        'postGIS'     => 760043.018642104
                ]],
                [[[1, 1, 0], [2, 2, 2], [2, 3.5, 0], [1, 3, 2], [1, 2, 0], [2, 1, 2]], [
                        'greatCircle' => 717400.38999229996,
                        'haversine'   => 717400.38992081373,
                        'vincenty'    => 714328.06433538091,
                        'postGIS'     => 714328.064406871
                ]],
                [[[19, 47], [19.000001, 47], [19.000001, 47.000001], [19.000001, 47.000002], [19.000002, 47.000002]], [
                        'greatCircle' => 0.37447839912084818,
                        'haversine'   => 0.36386002147417207,
                        'vincenty'    => 0.37445330532190713,
                        'postGIS'     => 0.374453678675281
                ]]
        ];
    }

    /**
     * @dataProvider providerLengths
     *
     * @param $points
     * @param $result
     */
    public function testGreatCircleLength($points, $result) {
        $line = LineString::fromArray($points);

        $this->assertEquals($line->greatCircleLength(), $result['greatCircle'], '', 1e-8);
    }

    /**
     * @dataProvider providerLengths
     *
     * @param $points
     * @param $result
     */
    public function testHaversineLength($points, $result) {
        if(defined('HHVM_VERSION')) {
            $this->markTestSkipped('HHVM\'s float precision is crappy can\'t test haversineLength()');
        }

        $line = LineString::fromArray($points);

        $this->assertEquals($line->haversineLength(), $result['haversine'], '', 1e-7);
    }

    /**
     * @dataProvider providerLengths
     *
     * @param $points
     * @param $result
     */
    public function testVincentyLength($points, $result) {
        $line = LineString::fromArray($points);

        $this->assertEquals($line->vincentyLength(), $result['vincenty'], '', 1e-8);
    }

    public function testVincentyLengthAndipodalPoints() {
        $line = LineString::fromArray([[-89.7, 0], [89.7, 0]]);

        $this->assertNull($line->vincentyLength());
    }

    public function testExplode() {
        $point1 = new Point(1, 2);
        $point2 = new Point(3, 4);
        $point3 = new Point(5, 6);
        $line = new LineString([$point1, $point2, $point3]);

        $this->assertEquals($line->explode(), [new LineString([$point1, $point2]), new LineString([$point2, $point3])]);

        $this->assertSame($line->explode(true), [[$point1, $point2], [$point2, $point3]]);

        $this->assertSame((new LineString())->explode(), []);

        $this->assertSame((new LineString())->explode(true), []);
    }

    public function providerDistance() {
        return [
                [new Point(10, 10), 10.0],
                [new Point(0, 10), 0.0],
                [LineString::fromArray([[10, 10], [20, 20]]), 10.0],
                [new \geoPHP\Geometry\GeometryCollection([LineString::fromArray([[10, 10], [20, 20]])]), 10.0],
                // TODO: test other types
        ];
    }

    /**
     * @dataProvider providerDistance
     *
     * @param $otherGeometry
     * @param $expectedDistance
     */
    public function testDistance($otherGeometry, $expectedDistance) {
        $line = LineString::fromArray([[0, 0], [0, 10]]);

        $this->assertSame($line->distance($otherGeometry), $expectedDistance);
    }

    public function testMinimumAndMaximumZAndMAndDifference() {
        $line = LineString::fromArray([[0, 0, 100.0, 0.0], [1, 1, 50.0, -0.5], [2, 2, 150.0, -1.0], [3, 3, 75.0, 0.5]]);

        $this->assertSame($line->minimumZ(), 50.0);
        $this->assertSame($line->maximumZ(), 150.0);

        $this->assertSame($line->minimumM(), -1.0);
        $this->assertSame($line->maximumM(), 0.5);

        $this->assertSame($line->zDifference(), 25.0);
        $this->assertSame(LineString::fromArray([[0, 1], [2, 3]])->zDifference(), null);
    }

    public function providerElevationGainAndLoss() {
        return [
                [null, 50.0, 30.0],
                [0, 50.0, 30.0],
                [5, 48.0, 28.0],
                [15, 36.0, 16.0]
        ];
    }

    /**
     * @dataProvider providerElevationGainAndLoss
     *
     * @param $tolerance
     * @param $gain
     * @param $loss
     */
    public function testElevationGainAndLoss($tolerance, $gain, $loss) {
        $line = LineString::fromArray(
                [[0, 0, 100], [0, 0, 102], [0, 0, 105], [0, 0, 103], [0, 0, 110], [0, 0, 118],
                [0, 0, 102], [0, 0, 108], [0, 0, 102], [0, 0, 108], [0, 0, 102], [0, 0, 120] ]
        );

        $this->assertSame($line->elevationGain($tolerance), $gain);

        $this->assertSame($line->elevationLoss($tolerance), $loss);
    }

}