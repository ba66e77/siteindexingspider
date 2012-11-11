<?php

require_once('SiteIndexingSpider.php');

$start_url = "http://d6.localhost"; 

$spider = new SiteIndexingSpider();
$spider->loggingLevel = 1;
$spider->spider($start_url);

foreach ($spider->assets as $url => $links) {
  echo "$url linked to from " . count($links) . " locations" . PHP_EOL;
}
?>
