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

namespace Scc\Tests\Gaufrette\Cloudinary;

use Gaufrette\Exception\FileNotFound;
use Gaufrette\Filesystem;
use Scc\Gaufrette\Cloudinary\Cloudinary;
use Scc\Tests\Gaufrette\Cloudinary\Traits\Method;
use Scc\Tests\Gaufrette\Cloudinary\Traits\Property;

class CloudinaryRemoteTest extends \PHPUnit_Framework_TestCase
{
    use Property, Method;

    /** @var Cloudinary */
    protected $adapter;

    /** @var Filesystem */
    protected $filesystem;

    const IMAGE_FILE = __DIR__ . '/../data/test-image.jpg';

    public function setUp()
    {
        $key = getenv('CLOUDINARY_API_KEY');
        $secret = getenv('CLOUDINARY_API_SECRET');
        $cloudname = getenv('CLOUDINARY_CLOUD_NAME');
        $cafile = getenv('CLOUDINARY_CAFILE');

        if (empty($key) || empty($secret) || empty($cloudname) || empty($cafile)) {
            $this->markTestSkipped('Either CLOUDINARY_API_KEY, CLOUDINARY_API_SECRET, CLOUDINARY_CLOUD_NAME and/or CLOUDINARY_CAFILE env variables are not defined.');
        }

        $this->adapter = new Cloudinary($cloudname, $key, $secret, $cafile);

        $this->createFilesystem();
    }

    public function tearDown()
    {
        foreach ($this->filesystem->keys() as $key) {
            $this->filesystem->delete($key);
        }
    }

    /**
     * @group remote
     */
    public function testRead()
    {
        $this->filesystem->write('test-image.jpg', file_get_contents(self::IMAGE_FILE));

        self::assertSame(sha1_file(self::IMAGE_FILE), sha1($this->filesystem->read('test-image.jpg')));
    }

    /**
     * @group remote
     */
    public function testWrite()
    {
        $this->assertEquals(filesize(self::IMAGE_FILE), $this->filesystem->write('test-image.jpg', file_get_contents(self::IMAGE_FILE)));
    }

    /**
     * @group remote
     */
    public function testRename()
    {
        $this->filesystem->write('test-image.jpg', file_get_contents(self::IMAGE_FILE));
        $this->filesystem->rename('test-image.jpg', 'rename-image.jpg');

        self::assertTrue($this->filesystem->has('rename-image.jpg'));
        self::assertFalse($this->filesystem->has('test-image.jpg'));
    }

    /**
     * @group remote
     */
    public function testKeys()
    {
        $this->filesystem->write('test-image.jpg', file_get_contents(self::IMAGE_FILE));
        $this->assertEquals(['test-image.jpg'], $this->filesystem->keys());
    }

    /**
     * @group remote
     */
    public function testDelete()
    {
        $this->filesystem->write('test-image.jpg', file_get_contents(self::IMAGE_FILE));
        self::assertTrue($this->filesystem->delete('test-image.jpg'));

        $this->expectException(FileNotFound::class);
        self::assertFalse($this->filesystem->delete('fake-image.jpg'));
    }

    /**
     * @group remote
     */
    public function testMTime()
    {
        $this->filesystem->write('test-image.jpg', file_get_contents(self::IMAGE_FILE));
        $res = $this->filesystem->mtime('test-image.jpg');
        self::assertInternalType('int', $res);
        self::assertGreaterThan(0, $res);
    }

    private function createFilesystem(array $adapterOptions = [])
    {
        $this->filesystem = new Filesystem($this->adapter);
    }
}
