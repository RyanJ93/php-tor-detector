<?php
require dirname(__FILE__) . '/php-tor-detector.php';

$address = '89.234.157.254';

//Set the list path.
PHPTorDetector\PHPTorDetector::setListPath(dirname(__FILE__) . '/nodes.txt');

//Update the content fo the list (or create the list if the file doesn't exist or is empty).
PHPTorDetector\PHPTorDetector::updateFile();

//Check if this IP address is a Tor exit point.
$result = PHPTorDetector\PHPTorDetector::isTor($address);
echo 'Is this client (' . $address . ') part of the Tor network? ' . ( $result === true ? 'Yes' : 'No' ) . '.' . PHP_EOL;