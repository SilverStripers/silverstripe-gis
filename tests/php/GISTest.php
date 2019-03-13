<?php

namespace Smindel\GIS\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\DB;
use Smindel\GIS\GIS;

class GISTest extends SapphireTest
{
    public function setUp()
    {
        Config::modify()->set(GIS::class, 'default_srid', 4326);
        Config::modify()->set(GIS::class, 'projections', [
            2193 => '+proj=tmerc +lat_0=0 +lon_0=173 +k=0.9996 +x_0=1600000 +y_0=10000000 +ellps=GRS80 +towgs84=0,0,0,0,0,0,0 +units=m +no_defs',
        ]);
        parent::setUp();
    }

    public function testGetType()
    {
        $this->assertEquals('Point', GIS::get_type('SRID=4326;POINT(30 10)'));
        $this->assertEquals('Point', GIS::get_type([30, 10]));
        $this->assertEquals('LineString', GIS::get_type([[30, 10], [10, 30], [40, 40]]));
        $this->assertEquals('Polygon', GIS::get_type([[[30, 10], [40, 40], [20, 40], [10, 20], [30, 10]]]));
        $this->assertEquals('MultiPolygon', GIS::get_type([[[[30, 20], [45, 40], [10, 40], [30, 20]]],[[[15, 5], [40, 10], [10, 20], [5, 10], [15, 5]]]]));
    }

    public function testToEwkt()
    {
        $wkt = GIS::to_ewkt(['srid' => 4326, 'type' => 'Point', 'coordinates' => [30, 10]]);
        $this->assertEquals($wkt, 'SRID=4326;POINT(30 10)', 'Point');

        $wkt = GIS::to_ewkt(['srid' => 4326, 'type' => 'LineString', 'coordinates' => [[30, 10], [10, 30], [40,40]]]);
        $this->assertEquals($wkt, 'SRID=4326;LINESTRING(30 10,10 30,40 40)', 'LineString');

        $wkt = GIS::to_ewkt(['srid' => 4326, 'type' => 'Polygon', 'coordinates' => [[[35, 10], [45, 45], [15, 40], [10, 20], [35, 10]], [[20, 30], [35, 35], [30, 20], [20, 30]]]]);
        $this->assertEquals($wkt, 'SRID=4326;POLYGON((35 10,45 45,15 40,10 20,35 10),(20 30,35 35,30 20,20 30))', 'Polygon');

        $wkt = GIS::to_ewkt(['srid' => 4326, 'type' => 'MultiPoint', 'coordinates' => [[10, 40], [40, 30], [20, 20], [30, 10]]]);
        $this->assertEquals($wkt, 'SRID=4326;MULTIPOINT(10 40,40 30,20 20,30 10)', 'MultiPoint');

        $wkt = GIS::to_ewkt(['srid' => 4326, 'type' => 'MultiLineString', 'coordinates' => [[[10, 10], [20, 20], [10, 40]], [[40, 40], [30, 30], [40, 20], [30, 10]]]]);
        $this->assertEquals($wkt, 'SRID=4326;MULTILINESTRING((10 10,20 20,10 40),(40 40,30 30,40 20,30 10))', 'MultiLineString');

        $wkt = GIS::to_ewkt(['srid' => 4326, 'type' => 'MultiPolygon', 'coordinates' => [[[[40, 40], [20, 45], [45, 30], [40, 40]]], [[[20, 35], [10, 30], [10, 10], [30, 5], [45, 20], [20, 35]], [[30, 20], [20, 15], [20, 25], [30, 20]]]]]);
        $this->assertEquals($wkt, 'SRID=4326;MULTIPOLYGON(((40 40,20 45,45 30,40 40)),((20 35,10 30,10 10,30 5,45 20,20 35),(30 20,20 15,20 25,30 20)))', 'MultiPolygon');
    }

    public function testLineWktFromArray()
    {
        $wkt = GIS::to_ewkt([
            'srid' => 4326,
            'type' => 'LineString',
            'coordinates' => [[174.5,-41.3], [175.5,-42.3]],
        ]);
        $this->assertEquals($wkt, 'SRID=4326;LINESTRING(174.5 -41.3,175.5 -42.3)');
    }

    public function testPolygonWktFromArray()
    {
        $wkt = GIS::to_ewkt([
            'srid' => 4326,
            'type' => 'Polygon',
            'coordinates' => [[
                [-10,40],
                [ -8,40],
                [ -8,35],
                [-10,35],
                [-10,40],
        ]]]);
        $this->assertEquals($wkt, 'SRID=4326;POLYGON((-10 40,-8 40,-8 35,-10 35,-10 40))');
    }

    public function testPointArrayFromWkt()
    {
        $array = GIS::to_array('SRID=4326;POINT(174.5 -41.3)');
        $this->assertEquals($array['coordinates'], [174.5,-41.3]);
    }

    public function testLineArrayFromWkt()
    {
        $array = GIS::to_array('SRID=4326;LINESTRING(174.5 -41.3,175.5 -42.3)');
        $this->assertEquals($array['coordinates'], [[174.5,-41.3], [175.5,-42.3]]);
    }

    public function testPolygonArrayFromWkt()
    {
        $array = GIS::to_array('SRID=4326;POLYGON((-10 40,-8 40,-8 35,-10 35,-10 40))');
        $this->assertEquals($array['coordinates'], [[
            [-10,40],
            [ -8,40],
            [ -8,35],
            [-10,35],
            [-10,40],
        ]]);
    }

    public function testReprojection()
    {
        $array = GIS::to_array('SRID=4326;POINT(174.5 -41.3)');
        $this->assertEquals([1725580.442709817, 5426854.149476525], GIS::reproject($array, 2193)['coordinates']);

        $array = GIS::to_array('SRID=2193;POINT(1753000	5432963)');
        $this->assertEquals([174.82583517653558, -41.240268094959326], GIS::reproject($array, 4326)['coordinates']);
    }

    public function testDistance()
    {
        $geo1 = GIS::to_ewkt([10,53.5]);
        $geo2 = GIS::to_ewkt([-9.1,38.7]);
        $distance = round(GIS::distance($geo1, $geo2) * 111195 / 1000);

        $this->assertEquals(2687, $distance);
    }
}
