# gaufrette-cloudinary-adapter

## Unit tests

```bash
  composer install -d /var/www/cdn/engine/vendor/scc/gaufrette-cloudinary-adapter
  vendor/scc/gaufrette-cloudinary-adapter/vendor/bin/phpunit -c vendor/scc/gaufrette-cloudinary-adapter/phpunit.xml.dist
```

## Limitations

* Work only for: images, videos and audios files at the moment.
    > _NOTE:_ The identifier of the uploaded image.
    > Note: The public ID value for images and resources should not include a file extension. Include the file extension for raw files only.

* The "keys" method does not really work because Cloudinary separate the storage of files on 3 pools: 1 for media, 1 for audio/video and all others files are store in a raw directory.
  I must found a workaround to unify all these 3 structures (impossible ?) or find a tip to indicate which of these directories are considered.

