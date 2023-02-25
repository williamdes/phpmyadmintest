<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Gis;

use PhpMyAdmin\Gis\GisGeometry;
use PhpMyAdmin\Gis\GisPolygon;
use PhpMyAdmin\Gis\ScaleData;
use PhpMyAdmin\Image\ImageWrapper;
use TCPDF;

/**
 * @covers \PhpMyAdmin\Gis\GisPolygon
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class GisPolygonTest extends GisGeomTestCase
{
    protected GisGeometry $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->object = GisPolygon::singleton();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->object);
    }

    /**
     * Provide some common data to data providers
     *
     * @return array common data for data providers
     */
    private static function getData(): array
    {
        return [
            'POLYGON' => [
                'no_of_lines' => 2,
                0 => [
                    'no_of_points' => 5,
                    0 => [
                        'x' => 35,
                        'y' => 10,
                    ],
                    1 => [
                        'x' => 10,
                        'y' => 20,
                    ],
                    2 => [
                        'x' => 15,
                        'y' => 40,
                    ],
                    3 => [
                        'x' => 45,
                        'y' => 45,
                    ],
                    4 => [
                        'x' => 35,
                        'y' => 10,
                    ],
                ],
                1 => [
                    'no_of_points' => 4,
                    0 => [
                        'x' => 20,
                        'y' => 30,
                    ],
                    1 => [
                        'x' => 35,
                        'y' => 32,
                    ],
                    2 => [
                        'x' => 30,
                        'y' => 20,
                    ],
                    3 => [
                        'x' => 20,
                        'y' => 30,
                    ],
                ],
            ],
        ];
    }

    /**
     * data provider for testGenerateWkt
     *
     * @return array data for testGenerateWkt
     */
    public static function providerForTestGenerateWkt(): array
    {
        $temp = [
            0 => self::getData(),
        ];

        $temp1 = $temp;
        unset($temp1[0]['POLYGON'][1][3]['y']);

        $temp2 = $temp;
        $temp2[0]['POLYGON']['no_of_lines'] = 0;

        $temp3 = $temp;
        $temp3[0]['POLYGON'][1]['no_of_points'] = 3;

        return [
            [
                $temp,
                0,
                null,
                'POLYGON((35 10,10 20,15 40,45 45,35 10),(20 30,35 32,30 20,20 30))',
            ],
            // values at undefined index
            [
                $temp,
                1,
                null,
                'POLYGON(( , , , ))',
            ],
            // if a coordinate is missing, default is empty string
            [
                $temp1,
                0,
                null,
                'POLYGON((35 10,10 20,15 40,45 45,35 10),(20 30,35 32,30 20,20 ))',
            ],
            // missing coordinates are replaced with provided values (3rd parameter)
            [
                $temp1,
                0,
                '0',
                'POLYGON((35 10,10 20,15 40,45 45,35 10),(20 30,35 32,30 20,20 0))',
            ],
            // should have at least one ring
            [
                $temp2,
                0,
                '0',
                'POLYGON((35 10,10 20,15 40,45 45,35 10))',
            ],
            // a ring should have at least four points
            [
                $temp3,
                0,
                '0',
                'POLYGON((35 10,10 20,15 40,45 45,35 10),(20 30,35 32,30 20,20 30))',
            ],
        ];
    }

    /**
     * data provider for testGenerateParams
     *
     * @return array data for testGenerateParams
     */
    public static function providerForTestGenerateParams(): array
    {
        return [
            [
                '\'POLYGON((35 10,10 20,15 40,45 45,35 10),(20 30,35 32,30 20,20 30))\',124',
                [
                    'srid' => 124,
                    0 => self::getData(),
                ],
            ],
        ];
    }

    /**
     * test for Area
     *
     * @param array $ring array of points forming the ring
     * @param float $area area of the ring
     *
     * @dataProvider providerForTestArea
     */
    public function testArea(array $ring, float $area): void
    {
        $this->assertEquals($area, $this->object->area($ring));
    }

    /**
     * data provider for testArea
     *
     * @return array data for testArea
     */
    public static function providerForTestArea(): array
    {
        return [
            [
                [
                    0 => [
                        'x' => 35,
                        'y' => 10,
                    ],
                    1 => [
                        'x' => 10,
                        'y' => 10,
                    ],
                    2 => [
                        'x' => 15,
                        'y' => 40,
                    ],
                ],
                -375.00,
            ],
            // first point of the ring repeated as the last point
            [
                [
                    0 => [
                        'x' => 35,
                        'y' => 10,
                    ],
                    1 => [
                        'x' => 10,
                        'y' => 10,
                    ],
                    2 => [
                        'x' => 15,
                        'y' => 40,
                    ],
                    3 => [
                        'x' => 35,
                        'y' => 10,
                    ],
                ],
                -375.00,
            ],
            // anticlockwise gives positive area
            [
                [
                    0 => [
                        'x' => 15,
                        'y' => 40,
                    ],
                    1 => [
                        'x' => 10,
                        'y' => 10,
                    ],
                    2 => [
                        'x' => 35,
                        'y' => 10,
                    ],
                ],
                375.00,
            ],
        ];
    }

    /**
     * test for isPointInsidePolygon
     *
     * @param array $point    x, y coordinates of the point
     * @param array $polygon  array of points forming the ring
     * @param bool  $isInside output
     *
     * @dataProvider providerForTestIsPointInsidePolygon
     */
    public function testIsPointInsidePolygon(array $point, array $polygon, bool $isInside): void
    {
        $this->assertEquals(
            $isInside,
            $this->object->isPointInsidePolygon($point, $polygon),
        );
    }

    /**
     * data provider for testIsPointInsidePolygon
     *
     * @return array data for testIsPointInsidePolygon
     */
    public static function providerForTestIsPointInsidePolygon(): array
    {
        $ring = [
            0 => [
                'x' => 35,
                'y' => 10,
            ],
            1 => [
                'x' => 10,
                'y' => 10,
            ],
            2 => [
                'x' => 15,
                'y' => 40,
            ],
            3 => [
                'x' => 35,
                'y' => 10,
            ],
        ];

        return [
            // point inside the ring
            [
                [
                    'x' => 20,
                    'y' => 15,
                ],
                $ring,
                true,
            ],
            // point on an edge of the ring
            [
                [
                    'x' => 20,
                    'y' => 10,
                ],
                $ring,
                false,
            ],
            // point on a vertex of the ring
            [
                [
                    'x' => 10,
                    'y' => 10,
                ],
                $ring,
                false,
            ],
            // point outside the ring
            [
                [
                    'x' => 5,
                    'y' => 10,
                ],
                $ring,
                false,
            ],
        ];
    }

    /**
     * test for getPointOnSurface
     *
     * @param array $ring array of points forming the ring
     *
     * @dataProvider providerForTestGetPointOnSurface
     */
    public function testGetPointOnSurface(array $ring): void
    {
        $point = $this->object->getPointOnSurface($ring);
        $this->assertIsArray($point);
        $this->assertTrue($this->object->isPointInsidePolygon($point, $ring));
    }

    /**
     * data provider for testGetPointOnSurface
     *
     * @return array data for testGetPointOnSurface
     */
    public static function providerForTestGetPointOnSurface(): array
    {
        $temp = self::getData();
        unset($temp['POLYGON'][0]['no_of_points']);
        unset($temp['POLYGON'][1]['no_of_points']);

        return [
            [
                $temp['POLYGON'][0],
            ],
            [
                $temp['POLYGON'][1],
            ],
        ];
    }

    /**
     * data provider for testScaleRow
     *
     * @return array data for testScaleRow
     */
    public static function providerForTestScaleRow(): array
    {
        return [
            [
                'POLYGON((123 0,23 30,17 63,123 0))',
                new ScaleData(123, 17, 63, 0),
            ],
            [
                'POLYGON((35 10,10 20,15 40,45 45,35 10),(20 30,35 32,30 20,20 30)))',
                new ScaleData(45, 10, 45, 10),
            ],
        ];
    }

    /** @requires extension gd */
    public function testPrepareRowAsPng(): void
    {
        $image = ImageWrapper::create(200, 124, ['red' => 229, 'green' => 229, 'blue' => 229]);
        $this->assertNotNull($image);
        $return = $this->object->prepareRowAsPng(
            'POLYGON((0 0,100 0,100 100,0 100,0 0),(10 10,10 40,40 40,40 10,10 10),(60 60,90 60,90 90,60 90,60 60))',
            'image',
            [176, 46, 224],
            ['x' => -56, 'y' => -16, 'scale' => 0.94, 'height' => 124],
            $image,
        );
        $this->assertEquals(200, $return->width());
        $this->assertEquals(124, $return->height());

        $fileExpected = $this->testDir . '/polygon-expected.png';
        $fileActual = $this->testDir . '/polygon-actual.png';
        $this->assertTrue($image->png($fileActual));
        $this->assertFileEquals($fileExpected, $fileActual);
    }

    /**
     * test case for prepareRowAsPdf() method
     *
     * @param string $spatial    GIS POLYGON object
     * @param string $label      label for the GIS POLYGON object
     * @param int[]  $color      color for the GIS POLYGON object
     * @param array  $scale_data array containing data related to scaling
     *
     * @dataProvider providerForPrepareRowAsPdf
     */
    public function testPrepareRowAsPdf(
        string $spatial,
        string $label,
        array $color,
        array $scale_data,
        TCPDF $pdf,
    ): void {
        $return = $this->object->prepareRowAsPdf($spatial, $label, $color, $scale_data, $pdf);

        $fileExpected = $this->testDir . '/polygon-expected.pdf';
        $fileActual = $this->testDir . '/polygon-actual.pdf';
        $return->Output($fileActual, 'F');
        $this->assertFileEquals($fileExpected, $fileActual);
    }

    /**
     * data provider for testPrepareRowAsPdf() test case
     *
     * @return array test data for testPrepareRowAsPdf() test case
     */
    public static function providerForPrepareRowAsPdf(): array
    {
        return [
            [
                'POLYGON((0 0,100 0,100 100,0 100,0 0),(10 10,10 40,40 40,40 10,10 10),(60 60,90 60,90 90,60 90,60 6'
                . '0))',
                'pdf',
                [176, 46, 224],
                ['x' => -8, 'y' => -32, 'scale' => 1.80, 'height' => 297],

                parent::createEmptyPdf('POLYGON'),
            ],
        ];
    }

    /**
     * test case for prepareRowAsSvg() method
     *
     * @param string $spatial   GIS POLYGON object
     * @param string $label     label for the GIS POLYGON object
     * @param int[]  $color     color for the GIS POLYGON object
     * @param array  $scaleData array containing data related to scaling
     * @param string $output    expected output
     *
     * @dataProvider providerForPrepareRowAsSvg
     */
    public function testPrepareRowAsSvg(
        string $spatial,
        string $label,
        array $color,
        array $scaleData,
        string $output,
    ): void {
        $svg = $this->object->prepareRowAsSvg($spatial, $label, $color, $scaleData);
        $this->assertEquals($output, $svg);
    }

    /**
     * data provider for testPrepareRowAsSvg() test case
     *
     * @return array test data for testPrepareRowAsSvg() test case
     */
    public static function providerForPrepareRowAsSvg(): array
    {
        return [
            [
                'POLYGON((123 0,23 30,17 63,123 0),(99 12,30 35,25 55,99 12))',
                'svg',
                [176, 46, 224],
                [
                    'x' => 12,
                    'y' => 69,
                    'scale' => 2,
                    'height' => 150,
                ],
                '<path d=" M 222, 288 L 22, 228 L 10, 162 Z  M 174, 264 L 36, 218 L 26, 178 Z " name="svg" id="svg12'
                . '34567890" class="polygon vector" stroke="black" stroke-width="0.5" fill="#b02ee0" fill-rule="evenod'
                . 'd" fill-opacity="0.8"/>',
            ],
        ];
    }

    /**
     * test case for prepareRowAsOl() method
     *
     * @param string $spatial GIS POLYGON object
     * @param int    $srid    spatial reference ID
     * @param string $label   label for the GIS POLYGON object
     * @param int[]  $color   color for the GIS POLYGON object
     * @param string $output  expected output
     *
     * @dataProvider providerForPrepareRowAsOl
     */
    public function testPrepareRowAsOl(
        string $spatial,
        int $srid,
        string $label,
        array $color,
        string $output,
    ): void {
        $ol = $this->object->prepareRowAsOl($spatial, $srid, $label, $color);
        $this->assertEquals($output, $ol);
    }

    /**
     * data provider for testPrepareRowAsOl() test case
     *
     * @return array test data for testPrepareRowAsOl() test case
     */
    public static function providerForPrepareRowAsOl(): array
    {
        return [
            [
                'POLYGON((123 0,23 30,17 63,123 0))',
                4326,
                'Ol',
                [176, 46, 224],
                'var feature = new ol.Feature(new ol.geom.Polygon([[[123,0],[23,30],[17,63],[123,0'
                . ']]]).transform(\'EPSG:4326\', \'EPSG:3857\'));feature.setStyle(new ol.style.Sty'
                . 'le({fill: new ol.style.Fill({"color":[176,46,224,0.8]}),stroke: new ol.style.St'
                . 'roke({"color":[0,0,0],"width":0.5}),text: new ol.style.Text({"text":"Ol"})}));v'
                . 'ectorSource.addFeature(feature);',
            ],
        ];
    }

    /**
     * test case for isOuterRing() method
     *
     * @param array $ring coordinates of the points in a ring
     *
     * @dataProvider providerForIsOuterRing
     */
    public function testIsOuterRing(array $ring): void
    {
        $this->assertTrue($this->object->isOuterRing($ring));
    }

    /**
     * data provider for testIsOuterRing() test case
     *
     * @return array test data for testIsOuterRing() test case
     */
    public static function providerForIsOuterRing(): array
    {
        return [
            [
                [
                    [
                        'x' => 0,
                        'y' => 0,
                    ],
                    [
                        'x' => 0,
                        'y' => 1,
                    ],
                    [
                        'x' => 1,
                        'y' => 1,
                    ],
                    [
                        'x' => 1,
                        'y' => 0,
                    ],
                ],
            ],
        ];
    }
}
