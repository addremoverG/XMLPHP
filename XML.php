<?php

/* type declaration try
 * declare(strict_types=1);
 *
 *
 * try{
 * echo $this->test("test");
 * }catch(TypeError $e){
 * echo "error is ".$e->getMessage();
 * }
 *
 * private function test(int $int): int
 * {
 * return $int;
 * }
 *
 *
 */
//https://stackoverflow.com/questions/1835177/how-to-use-xmlreader-in-php
// https://www.php.net/manual/en/simplexml.examples-errors.php
// https://stackoverflow.com/questions/19561657/loop-through-an-xml-object-with-simplexml
// https://stackoverflow.com/questions/1835177/how-to-use-xmlreader-in-php
// https://riptutorial.com/php/example/2731/create-an-xml-file-using-xmlwriter

class XML
{
    
    // class attributes
    private $checkHour;
    
    private $day;
    
    private $xmlPathInput;
    
    private $xmlPathOutput;
    
    private $xmlReader;
    
    private $xmlWriter;
    
    private $active;
    
    private $paused;
    
    private $counterFail;
    
    private $counterMEArray;
    
    // class parameterized constructor
    public function __construct(string $checkHour, string $day, string $xmlIn, string $xmlOut)
    {
        $this->checkHour = $checkHour;
        $this->day = $day;
        $this->xmlPathInput = 'resources/' . $xmlIn;
        $this->xmlPathOutput = 'resources/' . $xmlOut;
        // XML stream reader obj Instantiation
        $this->xmlReader = new XMLReader();
        // XML stream writer obj Instantiation
        $this->xmlWriter = new XMLWriter();
        $this->counterFail = 0;
        $this->counterMEArray = 0;
    }
    
    // static function shared between classes don't require Instantiation of obj to use, for validation
    public static function validateTime(string $time): bool
    {
        return preg_match('/^(?:2[0-3]|[0-1][0-9]):[0-5][0-9]$/', $time);
    }
    
    //main method
    public function runParsingXML(): void
    {
        $this->startWrite();
        
        // check open file
        if (! $this->xmlReader->open($this->xmlPathInput)) {
            echo "\n" . "file can't be open to read check path" . "\n";
            exit();
        }
        
        // while loop for each read XML node
        while ($this->xmlReader->read()) {
            
            // condition to continue in iteration of the loop if fulfill not equal skips all other posibilities
            if ($this->xmlReader->nodeType != XMLReader::ELEMENT || $this->xmlReader->name != 'offer') {
                continue;
            }
            
            $this->xmlWriter->startElement($this->xmlReader->name);
            
            // read chunk of offer elelment as a string and parse it to xml obj in memory
            if (! $chunk = simplexml_load_string($this->xmlReader->readOuterXml())) {
                $this->counterFail ++;
                continue;
            }
            
            // foreach loop to iterate on chunk obj elements, if element 'opening_times' is encountered call validate method and pass element value as a parameter
            foreach ($chunk as $key => $value) {
                $this->xmlWriter->startElement($key);
                $this->xmlWriter->writeCdata($value);
                $this->xmlWriter->endElement();
                if ($key == 'opening_times' && ! $this->defineActive($value)) {
                    
                    $this->addActiveElementFalse();
                }
            }
            
            $this->xmlWriter->endElement();
            $this->counterFunction();
        }
        
        $this->endWriting();
        // printf("Active: %s Paused: %s\n", $this->active, $this->paused);
        echo "\nIs Active: " . $this->active . "\nPaused: " . $this->paused . "\nTotal: " . ($this->active + $this->paused) . "\nFailed chunks: " . $this->counterFail . "\nArrays with more then one element: " . $this->counterMEArray . "\n";
    }
    
    //method for start new xml document
    private function startWrite(): void
    {
        $this->xmlWriter->openUri($this->xmlPathOutput);
        $this->xmlWriter->startDocument('1.0', 'UTF-8');
        // pretty formatting
        $this->xmlWriter->setIndent(TRUE);
        $this->xmlWriter->startElement('offers');
    }
    
    //method to add FALSE element is_active if eny condition test fail
    private function addActiveElementFalse(): void
    {
        $this->paused ++;
        $this->xmlWriter->startElement('is_active');
        $this->xmlWriter->writeCdata('false');
        $this->xmlWriter->endElement();
    }
    
    //method for validation json element
    private function defineActive(SimpleXMLElement $outherChunk): bool
    {
        // decode json content
        $json = json_decode($outherChunk);
        
        if (empty($json) || empty($json->{$this->day})) {
            return FALSE;
        }
        
        $openingTimeRecord = $json->{$this->day};
        
        if (is_array($openingTimeRecord)) {
            if (count($openingTimeRecord) == 0) {
                return FALSE;
            }
            if (count($openingTimeRecord) > 1) {
                $this->counterMEArray ++;
            }
            $singleRecord = $openingTimeRecord[0];
        }
        
        if (empty($singleRecord->opening) || empty($singleRecord->closing)) {
            return FALSE;
        }
        
        if (! XML::validateTime($singleRecord->opening) || ! XML::validateTime($singleRecord->closing)) {
            return FALSE;
        }
        
        $open = strtotime($singleRecord->opening);
        $close = strtotime($singleRecord->closing);
        
        $check = $this->checkHour; // check date
        
        if ($close < $open || $close == $open) {
            $close = strtotime('+1 day', $close);
        }
        
        if ($check >= $open && $check <= $close) {
            $this->active ++;
            $this->xmlWriter->startElement('is_active');
            $this->xmlWriter->writeCdata('true');
            $this->xmlWriter->endElement();
            return TRUE;
        }
        return FALSE;
    }
    
    //method to close new xml element
    private function endWriting(): void
    {
        $this->xmlWriter->endElement();
        $this->xmlWriter->endDocument();
    }
    
    //method to cout validated records
    private function counterFunction(): void
    {
        if (($this->active + $this->paused) % 50000 == 0 && $this->active + $this->paused > 0) {
            echo 'Records processed: ' . ($this->active + $this->paused) . "\n";
        }
    }
}

?>
