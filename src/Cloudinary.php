<?php
/*
 * This file is part of the Mall Digital Ecosystem (MDE) project.
 *
 * (c) <SCCD> <office@sccd.lu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Scc\Gaufrette\Cloudinary;

use Cloudinary as BaseCloudinary;
use Gaufrette\Adapter;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Scc\Gaufrette\Cloudinary\Traits\StaticCalling;

/**
 * Class Cloudinary.
 *
 * @author Jérôme Fix <jerome.fix@sccd.lu>
 */
class Cloudinary implements Adapter
{
    use StaticCalling;

    /** @var string */
    protected $cafile;

    /** @var LoggerInterface */
    protected $logger;

    /** @var string */
    protected $cloud_name;

    /** @var string */
    protected $api_key;

    /** @var string */
    protected $api_secret;

    /** @var BaseCloudinary/Api */
    protected $api;

    private static $mime_types = [
        'txt' => 'raw',
        'htm' => 'raw',
        'html' => 'raw',
        'php' => 'raw',
        'css' => 'raw',
        'js' => 'raw',
        'json' => 'raw',
        'xml' => 'raw',
        'swf' => 'raw',
        'flv' => 'video',

        // images
        'png' => 'image',
        'jpe' => 'image',
        'jpeg' => 'image',
        'jpg' => 'image',
        'gif' => 'image',
        'bmp' => 'image',
        'ico' => 'image',
        'tiff' => 'image',
        'tif' => 'image',
        'svg' => 'image',
        'svgz' => 'image',

        // archives
        'zip' => 'raw',
        'rar' => 'raw',
        'exe' => 'raw',
        'msi' => 'raw',
        'cab' => 'raw',

        // video
        'mp3' => 'video',
        'qt' => 'video',
        'mov' => 'video',
        'mp4' => 'video',

        // adobe
        'pdf' => 'raw',
        'psd' => 'image',
        'ai' => 'raw',
        'eps' => 'raw',
        'ps' => 'raw',

        // ms office
        'doc' => 'raw',
        'docx' => 'raw',
        'rtf' => 'raw',
        'xls' => 'raw',
        'xlsx' => 'raw',
        'ppt' => 'raw',
        'pptx' => 'raw',

        // open office
        'odt' => 'raw',
        'ods' => 'raw',
    ];

    public function __construct(string $cloud_name, string $api_key, string $api_secret, string $cafile = null)
    {
        $this->cloud_name = $cloud_name;
        $this->api_key = $api_key;
        $this->api_secret = $api_secret;
        $this->cafile = $cafile;

        $this->initConnection();
        $this->initAPI();

        $this->logger = new NullLogger();
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function read($key)
    {
        if (null !== $this->cafile) {
            $context = [
                'ssl' => [
                    'cafile' => $this->cafile,
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                ],
            ];
            $method = 'secure_url';
        } else {
            $context = null;
            $method = 'url';
        }

        try {
            $result = $this->api->resource($this->computePath($key));

            return file_get_contents($result[$method], false, stream_context_create($context));
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function write($key, $content)
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'cloudinary-');
        file_put_contents($tmpFile, $content);

        try {
            $response = $this->callStatic(
                BaseCloudinary\Uploader::class,
                'upload',
                $tmpFile,
                ['resource_type' => 'auto', 'public_id' => $this->computePath($key)]
            );
        } catch (BaseCloudinary\Error $e) {
            $this->logger->error($e->getMessage());

            return false;
        }

        return $response['bytes'];
    }

    /**
     * {@inheritdoc}
     */
    public function exists($key)
    {
        try {
            $this->api->resource($this->computePath($key), ['resource_type' => $this->computeResourceType($key)]);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function keys()
    {
        $results = [];
        $cursor = null;
        try {
            do {
                $resources = $this->api->resources(array_merge(['next_cursor' => $cursor, 'max_results' => 200], ['resource_type' => 'image']));

                $cursor = $resources['next_cursor'] ?? false;

                foreach ($resources->getIterator()['resources'] as $item) {
                    $results[] = $item['public_id'] . '.' . $item['format'];
                }
            } while (false !== $cursor);

            return $results;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return [];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function mtime($key)
    {
        try {
            $result = $this->api->resource($this->computePath($key), ['resource_type' => $this->computeResourceType($key)]);

            return strtotime($result['created_at']);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        try {
//            BaseCloudinary\Uploader::destroy($this->computePath($key), ['invalidate' => true]);
            $response = $this->callStatic(
                BaseCloudinary\Uploader::class,
                'destroy',
                $this->computePath($key),
                ['invalidate' => true]
            );

            return 'ok' === $response['result'];
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rename($sourceKey, $targetKey)
    {
        try {
            // BaseCloudinary\Uploader::rename($this->computePath($sourceKey), $this->computePath($targetKey), ['invalidate' => true]);
            $response = $this->callStatic(
                BaseCloudinary\Uploader::class,
                'rename',
                $this->computePath($sourceKey),
                $this->computePath($targetKey),
                ['invalidate' => true]
            );

            return isset($response['public_id']) && $response['public_id'] === $this->computePath($targetKey);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isDirectory($key)
    {
        try {
            $resources = $this->api->resources(['type' => 'upload', 'prefix' => rtrim($this->computePath($key), '/') . '/']);

            return \count($resources['resources']) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function initConnection()
    {
        BaseCloudinary::config([
            'cloud_name' => $this->cloud_name,
            'api_key' => $this->api_key,
            'api_secret' => $this->api_secret,
        ]);
    }

    private function initAPI()
    {
        if (!$this->api instanceof BaseCloudinary\Api) {
            $this->api = new BaseCloudinary\Api();
        }
    }

    /**
     * @return string
     */
    private function computeResourceType($key): string
    {
        $ext = pathinfo($key, PATHINFO_EXTENSION);
        if (array_key_exists($ext, self::$mime_types)) {
            return self::$mime_types[$ext];
        }

        return 'image';
    }

    /**
     * @param $key
     *
     * @return string
     */
    private function computePath($key): string
    {
        if ('.' === pathinfo($key, PATHINFO_DIRNAME)) {
            return pathinfo($key, PATHINFO_FILENAME);
        }

        return sprintf('%s/%s', pathinfo($key, PATHINFO_DIRNAME), pathinfo($key, PATHINFO_FILENAME));
    }
}
