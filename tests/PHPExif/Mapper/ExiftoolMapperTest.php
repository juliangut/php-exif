<?php
/**
 * @covers \PHPExif\Mapper\Exiftool::<!public>
 */
class ExiftoolMapperTest extends \PHPUnit_Framework_TestCase
{
    protected $mapper;

    public function setUp()
    {
        $this->mapper = new \PHPExif\Mapper\Exiftool;
    }

    /**
     * @group mapper
     */
    public function testClassImplementsCorrectInterface()
    {
        $this->assertInstanceOf('\\PHPExif\\Mapper\\MapperInterface', $this->mapper);
    }

    /**
     * @group mapper
     * @covers \PHPExif\Mapper\Exiftool::mapRawData
     */
    public function testMapRawDataIgnoresFieldIfItDoesntExist()
    {
        $rawData = array('foo' => 'bar');
        $mapped = $this->mapper->mapRawData($rawData);

        $this->assertCount(0, $mapped);
    }

    /**
     * @group mapper
     * @covers \PHPExif\Mapper\Exiftool::mapRawData
     */
    public function testMapRawDataMapsFieldsCorrectly()
    {
        $reflProp = new \ReflectionProperty(get_class($this->mapper), 'map');
        $reflProp->setAccessible(true);
        $map = $reflProp->getValue($this->mapper);

        // ignore custom formatted data stuff:
        unset($map[\PHPExif\Mapper\Exiftool::APERTURE]);
        unset($map[\PHPExif\Mapper\Exiftool::APPROXIMATEFOCUSDISTANCE]);
        unset($map[\PHPExif\Mapper\Exiftool::CREATEDATE]);
        unset($map[\PHPExif\Mapper\Exiftool::EXPOSURETIME]);
        unset($map[\PHPExif\Mapper\Exiftool::FOCALLENGTH]);
        unset($map[\PHPExif\Mapper\Exiftool::GPSLATITUDE]);
        unset($map[\PHPExif\Mapper\Exiftool::GPSLONGITUDE]);

        // create raw data
        $keys = array_keys($map);
        $values = array();
        $values = array_pad($values, count($keys), 'foo');
        $rawData = array_combine($keys, $values);


        $mapped = $this->mapper->mapRawData($rawData);

        $i = 0;
        foreach ($mapped as $key => $value) {
            $this->assertEquals($map[$keys[$i]], $key);
            $i++;
        }
    }

    /**
     * @group mapper
     * @covers \PHPExif\Mapper\Exiftool::mapRawData
     */
    public function testMapRawDataCorrectlyFormatsAperture()
    {
        $rawData = array(
            \PHPExif\Mapper\Exiftool::APERTURE => 0.123,
        );

        $mapped = $this->mapper->mapRawData($rawData);

        $this->assertEquals('f/0.1', reset($mapped));
    }

    /**
     * @group mapper
     * @covers \PHPExif\Mapper\Exiftool::mapRawData
     */
    public function testMapRawDataCorrectlyFormatsFocusDistance()
    {
        $rawData = array(
            \PHPExif\Mapper\Exiftool::APPROXIMATEFOCUSDISTANCE => 50,
        );

        $mapped = $this->mapper->mapRawData($rawData);

        $this->assertEquals('50m', reset($mapped));
    }

    /**
     * @group mapper
     * @covers \PHPExif\Mapper\Exiftool::mapRawData
     */
    public function testMapRawDataCorrectlyFormatsCreationDate()
    {
        $rawData = array(
            \PHPExif\Mapper\Exiftool::CREATEDATE => '2015:04:01 12:11:09',
        );

        $mapped = $this->mapper->mapRawData($rawData);

        $result = reset($mapped);
        $this->assertInstanceOf('\\DateTime', $result);
        $this->assertEquals(
            reset($rawData), 
            $result->format('Y:m:d H:i:s')
        );
    }

    /**
     * @group mapper
     * @covers \PHPExif\Mapper\Exiftool::mapRawData
     */
    public function testMapRawDataCorrectlyFormatsExposureTime()
    {
        $rawData = array(
            \PHPExif\Mapper\Exiftool::EXPOSURETIME => 1/400,
        );

        $mapped = $this->mapper->mapRawData($rawData);

        $this->assertEquals('1/400', reset($mapped));
    }

    /**
     * @group mapper
     * @covers \PHPExif\Mapper\Exiftool::mapRawData
     */
    public function testMapRawDataCorrectlyFormatsFocalLength()
    {
        $rawData = array(
            \PHPExif\Mapper\Exiftool::FOCALLENGTH => '15 m',
        );

        $mapped = $this->mapper->mapRawData($rawData);

        $this->assertEquals(15, reset($mapped));
    }

    /**
     * @group mapper
     * @covers \PHPExif\Mapper\Exiftool::mapRawData
     */
    public function testMapRawDataCorrectlyFormatsGPSData()
    {
        $this->mapper->setNumeric(false);
        $result = $this->mapper->mapRawData(
            array(
                'GPSLatitude'     => '40 deg 20\' 0.42857" N',
                'GPSLatitudeRef'  => 'North',
                'GPSLongitude'    => '20 deg 10\' 2.33333" W',
                'GPSLongitudeRef' => 'West',
            )
        );

        $expected = '40.333452380556,-20.167314813889';
        $this->assertCount(1, $result);
        $this->assertEquals($expected, reset($result));
    }

    /**
     * @group mapper
     * @covers \PHPExif\Mapper\Exiftool::mapRawData
     */
    public function testMapRawDataCorrectlyFormatsNumericGPSData()
    {
        $result = $this->mapper->mapRawData(
            array(
                'GPSLatitude'     => '40.333452381',
                'GPSLatitudeRef'  => 'North',
                'GPSLongitude'    => '20.167314814',
                'GPSLongitudeRef' => 'West',
            )
        );

        $expected = '40.333452381,-20.167314814';
        $this->assertCount(1, $result);
        $this->assertEquals($expected, reset($result));
    }

    /**
     * @group mapper
     * @covers \PHPExif\Mapper\Exiftool::mapRawData
     */
    public function testMapRawDataCorrectlyIgnoresIncorrectGPSData()
    {
        $this->mapper->setNumeric(false);
        $result = $this->mapper->mapRawData(
            array(
                'GPSLatitude'     => '40.333452381',
                'GPSLatitudeRef'  => 'North',
                'GPSLongitude'    => '20.167314814',
                'GPSLongitudeRef' => 'West',
            )
        );

        $this->assertCount(0, $result);
    }

    /**
     * @group mapper
     * @covers \PHPExif\Mapper\Exiftool::mapRawData
     */
    public function testMapRawDataCorrectlyIgnoresIncompleteGPSData()
    {
        $result = $this->mapper->mapRawData(
            array(
                'GPSLatitude'     => '40.333452381',
                'GPSLatitudeRef'  => 'North',
            )
        );

        $this->assertCount(0, $result);
    }

    /**
     * @group mapper
     * @covers \PHPExif\Mapper\Exiftool::setNumeric
     */
    public function testSetNumericInProperty()
    {
        $reflProperty = new \ReflectionProperty(get_class($this->mapper), 'numeric');
        $reflProperty->setAccessible(true);

        $expected = true;
        $this->mapper->setNumeric($expected);

        $this->assertEquals($expected, $reflProperty->getValue($this->mapper));
    }
}
