<?php

use \geoPHP\Geometry\Point;

/**
 * Unit tests of Point geometry
 *
 * @group geometry
 *
 */
class PointTest extends PHPUnit_Framework_TestCase {

    public function providerValidCoordinatesXY() {
        return [
            [0, 0],
            [10, 20],
            [-10, -20],
            [47.1234056789, 19.9876054321], // WGS84
            [238084.12, 649977.59]          // HD72/EOV
        ];
    }

    /**
     * @dataProvider providerValidCoordinatesXY
     *
     * @param $x
     * @param $y
     */
    public function testValidCoordinatesXY($x, $y) {
        $point = new Point($x, $y);

        $this->assertEquals($x, $point->x());
        $this->assertEquals($y, $point->y());
        $this->assertInternalType('float', $point->x());
        $this->assertInternalType('float', $point->y());
    }

    public function providerValidCoordinatesXYZ_XYM() {
        return [
                [0, 0, 0],
                [10, 20, 30],
                [-10, -20, -30],
                [47.1234056789, 19.9876054321, 100.1],  //WGS84
                [238084.12, 649977.59, 56.38]           // HD72/EOV
        ];
    }

    /**
     * @dataProvider providerValidCoordinatesXYZ_XYM
     *
     * @param $x
     * @param $y
     * @param $z
     */
    public function testValidCoordinatesXYZ($x, $y, $z) {
        $point = new Point($x, $y, $z);

        $this->assertEquals($x, $point->x());
        $this->assertEquals($y, $point->y());
        $this->assertEquals($z, $point->z());
        $this->assertInternalType('float', $point->x());
        $this->assertInternalType('float', $point->y());
        $this->assertInternalType('float', $point->z());
    }

    /**
     * @dataProvider providerValidCoordinatesXYZ_XYM
     *
     * @param $x
     * @param $y
     * @param $m
     */
    function testValidCoordinatesXYM($x, $y, $m) {
        $point = new Point($x, $y, null, $m);

        $this->assertEquals($x, $point->x());
        $this->assertEquals($y, $point->y());
        $this->assertEquals($m, $point->m());
        $this->assertInternalType('float', $point->x());
        $this->assertInternalType('float', $point->y());
        $this->assertInternalType('float', $point->m());
    }

    public function providerValidCoordinatesXYZM() {
        return [
                [0, 0, 0, 0],
                [10, 20, 30, 40],
                [-10, -20, -30, -40],
                [47.1234056789, 19.9876054321, 100.1, 0.00001]
        ];
    }

    /**
     * @dataProvider providerValidCoordinatesXYZM
     *
     * @param $x
     * @param $y
     * @param $z
     * @param $m
     */
    public function testValidCoordinatesXYZM($x, $y, $z, $m) {
        $point = new Point($x, $y, $z, $m);

        $this->assertEquals($x, $point->x());
        $this->assertEquals($y, $point->y());
        $this->assertEquals($z, $point->z());
        $this->assertEquals($m, $point->m());
        $this->assertInternalType('float', $point->x());
        $this->assertInternalType('float', $point->y());
        $this->assertInternalType('float', $point->z());
        $this->assertInternalType('float', $point->m());
    }

    public function testConstructorWithoutParameters() {
        $point = new Point();

        $this->assertTrue($point->isEmpty());

        $this->assertNull($point->x());
        $this->assertNull($point->y());
        $this->assertNull($point->z());
        $this->assertNull($point->m());
    }

    public function providerEmpty() {
        return [
                [],
                [null, 20],
                [10, null],
                [null, null, 30],
                [null, null, null, 40],
                [null, null, 30, 40]
        ];
    }

    /**
     * @dataProvider providerEmpty
     *
     * @param $x
     * @param $y
     * @param $z
     * @param $m
     */
    public function testEmpty($x = null, $y = null, $z = null, $m = null) {
        $point = new Point($x, $y, $z, $m);

        $this->assertTrue($point->isEmpty());

        $this->assertNull($point->x());
        $this->assertNull($point->y());
        $this->assertNull($point->z());
        $this->assertNull($point->m());
    }

    public function providerInvalidCoordinates() {
        return [
                ['x', 'y'],
                [true, false],
                [1, 2, 'z'],
                [1, 2, 3, 'm'],
        ];
    }

    /**
     * @dataProvider providerInvalidCoordinates
     *
     * @param $x
     * @param $y
     * @param null $z
     * @param null $m
     */
    public function testConstructorWithInvalidCoordinates($x, $y, $z = null, $m = null) {
        $this->setExpectedException('Exception');

        new Point($x, $y, $z, $m);
    }

    public function testGeometryType() {
        $point = new Point();

        $this->assertEquals(\geoPHP\Geometry\Geometry::POINT, $point->geometryType());

        $this->assertInstanceOf('\geoPHP\Geometry\Point', $point);
        $this->assertInstanceOf('\geoPHP\Geometry\Geometry', $point);
    }

    public function testIs3D() {
        $this->assertTrue( (new Point(1, 2, 3))->is3D() );
        $this->assertTrue( (new Point(1, 2, 3, 4))->is3D() );
        $this->assertTrue( (new Point(null, null, 3, 4))->is3D() );
    }

    public function testIsMeasured() {
        $this->assertTrue( (new Point(1, 2, null, 4))->isMeasured() );
        $this->assertTrue( (new Point(null, null , null, 4))->isMeasured() );
    }

    /**
     * @dataProvider providerValidCoordinatesXYZM
     *
     * @param $x
     * @param $y
     * @param $z
     * @param $m
     */
    public function testInvertXY($x, $y, $z, $m) {
        $point = new Point($x, $y, $z, $m);
        $point->invertXY();

        $this->assertEquals($x, $point->y());
        $this->assertEquals($y, $point->x());
        $this->assertEquals($z, $point->z());
        $this->assertEquals($m, $point->m());
    }

    public function testCentroid() {
        $point = new Point(1, 2, 3, 4);
        $this->assertSame($point, $point->centroid());
    }

    public function testBBox() {
        $point = new Point(1, 2);
        $this->assertSame($point->getBBox(), [
                'maxy' => 2.0,
                'miny' => 2.0,
                'maxx' => 1.0,
                'minx' => 1.0,
        ]);
    }

    public function testAsArray() {
        $pointAsArray = (new Point())->asArray();
        $this->assertTrue(count($pointAsArray) && is_nan($pointAsArray[0]) && is_nan($pointAsArray[1]));

        $pointAsArray = (new Point(1, 2))->asArray();
        $this->assertSame($pointAsArray, [1.0, 2.0]);

        $pointAsArray = (new Point(1, 2, 3))->asArray();
        $this->assertSame($pointAsArray, [1.0, 2.0, 3.0]);

        $pointAsArray = (new Point(1, 2, null, 3))->asArray();
        $this->assertSame($pointAsArray, [1.0, 2.0, null, 3.0]);

        $pointAsArray = (new Point(1, 2, 3, 4))->asArray();
        $this->assertSame($pointAsArray, [1.0, 2.0, 3.0, 4.0]);
    }

    public function testBoundary() {
        $this->assertEquals((new Point())->boundary(), new \geoPHP\Geometry\GeometryCollection());
    }

    public function testEquals() {
        $this->assertTrue((new Point())->equals(new Point()));

        $point = new Point(1, 2, 3, 4);
        $this->assertTrue($point->equals(new Point(1, 2, 3, 4)));

        $this->assertTrue($point->equals(new Point(1.0000000001, 2.0000000001, 3, 4)));
        $this->assertTrue($point->equals(new Point(0.9999999999, 1.9999999999, 3, 4)));

        $this->assertFalse($point->equals(new Point(1.000000001, 2.000000001, 3, 4)));
        $this->assertFalse($point->equals(new Point(0.999999999, 1.999999999, 3, 4)));

        $this->assertFalse($point->equals(new \geoPHP\Geometry\GeometryCollection()));
    }

    public function testFlatten() {
        $point = new Point(1, 2, 3, 4);
        $point->flatten();

        $this->assertFalse($point->is3D());
        $this->assertFalse($point->isMeasured());
        $this->assertNull($point->z());
        $this->assertNull($point->m());
    }

    public function providerDistance() {
        return [
                [new Point(), null],
                [new Point(0, 0), 14.142135623730951],
                [new Point(10, 20), 10.0],
                [\geoPHP\Geometry\LineString::fromArray([[0,0], [10,10]]), 0.0],    // line endpoint equals to point
                [\geoPHP\Geometry\LineString::fromArray([[0,10], [0,10]]), 10.0],   // line segment vertices are identical
                [\geoPHP\Geometry\LineString::fromArray([[0,0], [0,20]]), 10.0],    // closest point is not a vertex
                [\geoPHP\Geometry\MultiPoint::fromArray([[0,0], [0,10]]), 10.0],     // finds the closest multi geometry component
                //[new \geoPHP\Geometry\GeometryCollection([new Point(0,10), new Point()]), 10.0] // finds the closest multi geometry component
                // FIXME: this geometry collection crashes GEOS
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
        $point = new Point(10, 10);

        $this->assertSame($point->distance($otherGeometry), $expectedDistance);
    }

    /**
     * @dataProvider providerDistance
     *
     * @param $otherGeometry
     */
    public function testDistanceEmpty($otherGeometry) {
        $point = new Point();

        $this->assertNull($point->distance($otherGeometry));
    }

    public function testTrivialMethods() {
        $point = new Point(1, 2, 3, 4);

        $this->assertSame( $point->dimension(), 0 );

        $this->assertSame( $point->numPoints(), 1 );

        $this->assertSame( $point->getPoints(), [$point] );

        $this->assertTrue( $point->isSimple());
    }

    public function testMinMaxMethods() {
        $point = new Point(1, 2, 3, 4);

        $this->assertEquals($point->minimumZ(), 3);
        $this->assertEquals($point->maximumZ(), 3);
        $this->assertEquals($point->minimumM(), 4);
        $this->assertEquals($point->maximumM(), 4);
    }

    public function providerMethodsNotValidForPointReturnsNull() {
        return [
                ['zDifference'],
                ['elevationGain'],
                ['elevationLoss'],
                ['numGeometries'],
                ['geometryN'],
                ['startPoint'],
                ['endPoint'],
                ['isRing'],
                ['isClosed'],
                ['pointN'],
                ['exteriorRing'],
                ['numInteriorRings'],
                ['interiorRingN'],
                ['explode']
        ];
    }

    /**
     * @dataProvider providerMethodsNotValidForPointReturnsNull
     *
     * @param $methodName
     */
    public function testPlaceholderMethodsReturnsNull($methodName) {
        $this->assertNull( (new Point())->$methodName(null) );
    }

    public function providerMethodsNotValidForPointReturns0() {
        return [['area'], ['length'], ['length3D'], ['greatCircleLength'], ['haversineLength']];
    }

    /**
     * @dataProvider providerMethodsNotValidForPointReturns0
     *
     * @param $methodName
     */
    public function testPlaceholderMethods($methodName) {
        $this->assertEquals( (new Point())->$methodName(null), 0 );
    }

}
