<?php
// ssTest.php - test sharedshelf object
require_once('SharedShelfService.php');

try {
  $ss = new SharedShelfService();

}
catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}
