<?php
include_once 'XML.php';

// https://stackoverflow.com/questions/27131527/php-check-if-time-is-between-two-times-regardless-of-date
// https://stackoverflow.com/questions/3964972/validate-that-input-is-in-this-time-format-hhmm

// check if first cli parameter stored in array argv[1] is empty
if (empty($argv[1])) {
    // assign timestamp of local current date in format H:i
    $checkTime = strtotime(date('H:i'));
} else {
    // using preg_match call as static method from XML class to validate cli parameter with H:i parameter
    if (XML::validateTime($argv[1])) {
        // convert to timestamp
        $checkTime = strtotime($argv[1]);
    } else {
        echo "\n" . 'first parameter have to be in hour format --:--' . "\n";
        exit(); // die();
    }
}

if (empty($argv[2])) {
    // condition if second cli parameter is empty, create instance on new DateTime obj
    $defaultDate = new DateTime();
    // format current date obj to get day number with format 1-7
    $checkDay = $defaultDate->format('N');
    // first approach //$checkDay=(date("now")["mday"]%7)+1;
} else {
    // filter second parameter if is NOT in range 1-7
    if (! filter_var($argv[2], FILTER_VALIDATE_INT, array(
        'options' => array(
            'min_range' => 1,
            'max_range' => 7
        )
    ))) {
        echo "\n" . 'second parameter have to be in day range 1-7' . "\n";
        exit(); // die();
    } else {
        $checkDay = $argv[2];
    }
}

// ternary assign of third parameter
$inputPath = empty($argv[3]) ? 'feed_sample.xml' : $argv[3];

// ternary assign with fourth parameter
$outputPath = empty($argv[4]) ? 'feed_out.xml' : $argv[4];

// Object Instantiation of class XML
$xmlObj = new XML($checkTime, $checkDay, $inputPath, $outputPath);
// call method from object
$xmlObj->runParsingXML();
?>