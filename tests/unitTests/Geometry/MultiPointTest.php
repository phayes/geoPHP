<?php

use \geoPHP\Geometry\Point;
use \geoPHP\Geometry\MultiPoint;

/**
 * Unit tests of MultiPoint geometry
 *
 * @group geometry
 *
 */
class MultiPointTest extends PHPUnit_Framework_TestCase {

    public function providerValidComponents() {
        return [
            [[]],
            [[new Point(1, 2)]],
            [[new Point(1, 2), new Point(3, 4)]],
            [[new Point(1, 2, 3, 4), new Point(5, 6, 7, 8)]],
        ];
    }

    /**
     * @dataProvider providerValidComponents
     *
     * @param $points
     */
    public function testValidComponents($points) {
        $this->assertNotNull(new MultiPoint($points));
    }

    public function providerInvalidComponents() {
        return [
            [[new Point()]],                        // empty component
            //[[\geoPHP\Geometry\LineString::fromArray([[1,2],[3,4]])]],  // wrong component type TODO implement this
        ];
    }

    /**
     * @dataProvider providerInvalidComponents
     *
     * @param $components
     */
    public function testConstructorWithInvalidComponents($components) {
        $this->setExpectedException('Exception');

        new MultiPoint($components);
    }

    public function testGeometryType() {
        $multiPoint = new MultiPoint();

        $this->assertEquals(\geoPHP\Geometry\Geometry::MULTI_POINT, $multiPoint->geometryType());

        $this->assertInstanceOf('\geoPHP\Geometry\MultiPoint', $multiPoint);
        $this->assertInstanceOf('\geoPHP\Geometry\MultiGeometry', $multiPoint);
        $this->assertInstanceOf('\geoPHP\Geometry\Geometry', $multiPoint);
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

    public function providerCentroid() {
        return [
            [[], []],
            [[[0, 0], [0, 10]], [0, 5]]
        ];
    }

    /**
     * @dataProvider providerCentroid
     *
     * @param $components
     * @param $centroid
     */
    public function testCentroid($components, $centroid) {
        $multiPoint = MultiPoint::fromArray($components);

        $this->assertEquals($multiPoint->centroid(), Point::fromArray($centroid));
    }

    public function providerIsSimple() {
        return [
            [[], true],
            [[[0, 0], [0, 10]], true],
            [[[1, 1], [2, 2], [1, 3], [1, 2], [2, 1]], true],
            [[[0, 10], [0, 10]], false],
        ];
    }

    /**
     * @dataProvider providerIsSimple
     *
     * @param $points
     * @param $result
     */
    public function testIsSimple($points, $result) {
        $multiPoint = MultiPoint::fromArray($points);

        $this->assertSame($multiPoint->isSimple(), $result);
    }

    /**
     * @dataProvider providerValidComponents
     *
     * @param $points
     */
    public function testNumPoints($points) {
        $multiPoint = new MultiPoint($points);

        $this->assertEquals($multiPoint->numPoints(), $multiPoint->numGeometries());
    }

    public function testTrivialAndNotValidMethods() {
        $point = new MultiPoint();

        $this->assertSame( $point->dimension(), 0 );

        $this->assertEquals( $point->boundary(), new \geoPHP\Geometry\GeometryCollection() );

        $this->assertNull( $point->explode());

        $this->assertTrue( $point->isSimple());
    }

}
