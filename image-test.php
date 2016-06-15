<?php

ini_set('memory_limit', '64M');

try {

  $images = new Imagick(glob('/tmp/jgr25/image-test/*.tif'));

  foreach($images as $image) {

      // Providing 0 forces thumbnailImage to maintain aspect ratio
      $image->thumbnailImage(128,0);

      //$image->setImageFormat("png");

      break;

  }
  //$images->writeImages('test.png');
}

catch (Exception $e) {
  $error = 'Caught exception: ' . $e->getMessage() . "\n";
  echo $error;
  exit (1);
}
exit (0);


?>
