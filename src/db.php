<?php

$mongo = new MongoDB\Driver\Manager("mongodb://mongo:27017");
$redis = new Redis();
$redis->connect('redis', 6379);

?>
