<?php

$images = new Imagick(glob('/tmp/jgr25/RAC_017/*.tif'));

foreach($images as $image) {

    // Providing 0 forces thumbnailImage to maintain aspect ratio
    $image->thumbnailImage(128,0);

    $image->setImageFormat("png");

    break;

}

$images->writeImages('test.png');

?>
