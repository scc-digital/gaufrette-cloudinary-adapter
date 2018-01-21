<?php

declare(strict_types=1);

/*
 * This file is part of the Mall Digital Ecosystem (MDE) project.
 *
 * (c) <SCCD> <office@sccd.lu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Scc\Tests\Gaufrette\Adapter;

use Cloudinary as BaseCloudinary;
use Gaufrette\Filesystem;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Scc\Gaufrette\Cloudinary\Cloudinary;
use Scc\Tests\Gaufrette\Cloudinary\Traits\Method;
use Scc\Tests\Gaufrette\Cloudinary\Traits\Property;


class CloudinaryTest extends \PHPUnit_Framework_TestCase
{
    use Property, Method;

    /** @var Cloudinary */
    protected $adapter;

    /** @var Filesystem */
    protected $filesystem;

    const IMAGE_FILE = __DIR__ . '/../data/test-image.jpg';

    /**
     * @throws \ReflectionException
     */
    public function setUp()
    {
        $key = getenv('CLOUDINARY_API_KEY');
        $secret = getenv('CLOUDINARY_API_SECRET');
        $cloudname = getenv('CLOUDINARY_CLOUD_NAME');
        $cafile = getenv('CLOUDINARY_CAFILE');

        $this->adapter = new Cloudinary($cloudname, $key, $secret, $cafile);

        $api = $this->getMockBuilder(BaseCloudinary\Api::class)->getMock();
        self::setProperty($this->adapter, 'api', $api);

        $this->createFilesystem();
    }

    /**
     * @throws \ReflectionException
     */
    public function testConstruct()
    {
        self::assertInstanceOf(BaseCloudinary\Api::class, self::getProperty($this->adapter, 'api'));
    }

    /**
     * @throws \ReflectionException
     */
    public function testSetLogger()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $this->adapter->setLogger($logger);

        self::assertSame($logger, self::getProperty($this->adapter, 'logger'));
    }

    /**
     * @throws \ReflectionException
     */
    public function testRead()
    {
        // With Secure
        $api = self::getProperty($this->adapter, 'api');
        $api->expects(self::any())->method('resource')->will(self::returnValue([
            'secure_url' => self::IMAGE_FILE,
            'url' => self::IMAGE_FILE,
        ]));

        self::assertSame(sha1_file(self::IMAGE_FILE), sha1($this->adapter->read('test-image.jpg')));

        // Without Secure
        self::setProperty($this->adapter, 'cafile', null);
        self::assertSame(sha1_file(self::IMAGE_FILE), sha1($this->adapter->read('test-image.jpg')));
    }

    /**
     * @throws \ReflectionException
     */
    public function testReadError()
    {
        // With Secure
        $api = self::getProperty($this->adapter, 'api');
        $api->expects(self::any())->method('resource')->will(self::returnValue([
            'secure_url' => 'fake-image.jpg',
            'url' => self::IMAGE_FILE,
        ]));

        self::assertFalse($this->adapter->read('test-image.jpg'));
    }

    /**
     * @throws \ReflectionException
     */
    public function testWrite()
    {
        $api = self::getProperty($this->adapter, 'api');
        $api->expects(self::any())->method('resource')->will(self::returnValue([
            'secure_url' => self::IMAGE_FILE,
            'url' => self::IMAGE_FILE,
        ]));

        $this->adapter = $this->getMockBuilder(Cloudinary::class)->disableOriginalConstructor()
            ->setMethods(['callStatic'])
            ->getMock();

        $this->adapter
            ->method('callStatic')
            ->with(BaseCloudinary\Uploader::class, 'upload', self::anything(), self::anything())
            ->will(self::returnValue(['bytes' => filesize(self::IMAGE_FILE)]));
        self::assertSame(filesize(self::IMAGE_FILE), $this->adapter->write('test-image.jpg', file_get_contents(self::IMAGE_FILE)));
    }

    public function testWriteWithError()
    {
        $this->adapter = $this->getMockBuilder(Cloudinary::class)->disableOriginalConstructor()
            ->setMethods(['callStatic'])
            ->getMock();

        $this->adapter->setLogger(new NullLogger());

        $this->adapter
            ->method('callStatic')
            ->with(BaseCloudinary\Uploader::class, 'upload', self::anything(), self::anything())
            ->will($this->throwException(new BaseCloudinary\Error()));

        self::assertFalse($this->adapter->write('test-image.jpg', file_get_contents(self::IMAGE_FILE)));
    }

    public function testDelete()
    {
        $this->adapter = $this->getMockBuilder(Cloudinary::class)->disableOriginalConstructor()
            ->setMethods(['callStatic'])
            ->getMock();

        $this->adapter
            ->method('callStatic')
            ->with(BaseCloudinary\Uploader::class, 'destroy', self::anything(), self::anything())
        ->willReturn(['result' => 'ok']);
        self::assertTrue($this->adapter->delete('test-image.jpg'));
    }

    public function testDeleteWithError()
    {
        $this->adapter = $this->getMockBuilder(Cloudinary::class)->disableOriginalConstructor()
            ->setMethods(['callStatic'])
            ->getMock();

        $this->adapter
            ->method('callStatic')
            ->with(BaseCloudinary\Uploader::class, 'delete', self::anything(), self::anything())
            ->will($this->throwException(new \Exception()));

        self::assertFalse($this->adapter->delete('test-image.jpg'));
    }

    public function testRename()
    {
        $this->adapter = $this->getMockBuilder(Cloudinary::class)->disableOriginalConstructor()
            ->setMethods(['callStatic'])
            ->getMock();

        $this->adapter
            ->method('callStatic')
            ->with(BaseCloudinary\Uploader::class, 'rename', self::anything(), self::anything())
            ->willReturn(['public_id' => 'rename-image']);
        self::assertTrue($this->adapter->rename('test-image.jpg', 'rename-image.jpg'));
    }

    public function testRenameWithError()
    {
        $this->adapter = $this->getMockBuilder(Cloudinary::class)->disableOriginalConstructor()
            ->setMethods(['callStatic'])
            ->getMock();

        $this->adapter
            ->method('callStatic')
            ->with(BaseCloudinary\Uploader::class, 'rename', self::anything(), self::anything())
            ->will($this->throwException(new \Exception()));

        self::assertFalse($this->adapter->rename('test-image.jpg', 'rename-image.jpg'));
    }

    /**
     * @throws \ReflectionException
     */
    public function testMtime()
    {
        $time = '2018-01-21T09:21:16Z';
        $api = self::getProperty($this->adapter, 'api');
        $api->expects(self::once())->method('resource')->will(self::returnValue([
            'created_at' => $time,
        ]));

        $result = $this->adapter->mtime('test-image.jpg');
        self::assertInternalType('int', $result);
        self::assertSame(strtotime($time), $result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testMtimeWithError()
    {
        $api = self::getProperty($this->adapter, 'api');
        $api->expects(self::once())->method('resource')->will($this->throwException(new \Exception()));

        $result = $this->adapter->mtime('test-image.jpg');

        self::assertFalse($result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testExist()
    {
        // With Secure
        $api = self::getProperty($this->adapter, 'api');
        $api->expects(self::once())->method('resource')->will(self::returnValue([
            'secure_url' => self::IMAGE_FILE,
            'url' => self::IMAGE_FILE,
        ]));

        self::assertTrue($this->adapter->exists('test-image.jpg'));
    }

    /**
     * @throws \ReflectionException
     */
    public function testExistWithError()
    {
        $api = self::getProperty($this->adapter, 'api');
        $api->expects(self::once())->method('resource')->will($this->throwException(new \Exception()));

        self::assertFalse($this->adapter->exists('test-image.jpg'));
    }

    /**
     * @dataProvider computePathProvider
     *
     * @param $key
     * @param $expected
     * @throws \ReflectionException
     */
    public function testComputePath($key, $expected)
    {
        self::assertSame($expected, self::getMethod($this->adapter, 'computePath', [$key]));
    }

    /**
     * @dataProvider computeResourceTypeProvider
     *
     * @param $key
     * @param $expected
     * @throws \ReflectionException
     */
    public function testComputeResourceType($key, $expected)
    {
        self::assertSame($expected, self::getMethod($this->adapter, 'computeResourceType', [$key]));
    }

    private function createFilesystem(array $adapterOptions = [])
    {
        $this->filesystem = new Filesystem($this->adapter);
    }

    public function computePathProvider()
    {
        return [
            ['a/b/a.jpeg', 'a/b/a'],
            ['a/b/a', 'a/b/a'],
            ['abcd', 'abcd'],
            ['abcd.jpg.jpg', 'abcd.jpg'],
            ['abcd.jpg.jpg', 'abcd.jpg'],
        ];
    }

    public function computeResourceTypeProvider()
    {
        return [
            ['a/b/a.jpeg', 'image'],
            ['a/b/a', 'image'],
            ['abcd', 'image'],
            ['abcd.jpg.jpg', 'image'],
            ['abcd.jpg.mp3', 'video'],
            ['abcd.txt', 'raw'],
            ['abcd.mp4', 'video'],
            ['abcd.xls', 'raw'],
            ['abcd.flv', 'video'],
        ];
    }
}
