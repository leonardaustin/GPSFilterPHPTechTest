<?php
$cleanService = new CleanService(50);
$cleanService->start();
echo $cleanService->output();



/**
 * Service Class to clean a csv file of bad data 
 * @author Leonard Austin
 */
class CleanService {
    
    protected $file;
    protected $dataArray;
    protected $outputMethod;
    protected $cleanDataArray;
    protected $maxSpeed;
    
    const OUTPUT_METHOD_SAVE = 'save';
    const OUTPUT_METHOD_PRINT = 'print';
    
    /**
     *
     * @param int $maxSpeed The max speed you would like to filter out values
     */
    function __construct($maxSpeed) {
        $this->maxSpeed = $maxSpeed;
        
        $this->checkParamAndSetVar();
        $this->convertCsvToData();
    }
    
    /**
     * Check the user has provided a file and output param and is so set it to member var
     * 
     * @return CleanService
     * @return die()
     */
    protected function checkParamAndSetVar()
    {
        // Check for first param
        if (!isset($_SERVER["argv"][1])) {
            die('Missing Argument. (e.g. php clean.php data/points.csv save)' . "\n");
        }
        $this->setFile($_SERVER["argv"][1]);
        
        //Check for second param
        if (!isset($_SERVER["argv"][2])) {
            die('Missing 2nd Argument (e.g. php clean.php data/points.csv save)' . "\n");
        }else{
            if($_SERVER["argv"][2] != self::OUTPUT_METHOD_SAVE && $_SERVER["argv"][2] != self::OUTPUT_METHOD_PRINT){
                die('Second argument not recognised either use print or save (e.g. php clean.php data/points.csv save)' . "\n");
            }
        }
        $this->setOutputMethod($_SERVER["argv"][2]);

        
        return $this;
    }
    
    /**
     * Start the cleaning process and save finised product in cleanDataArray
     * 
     * @return CleanService
     *  
     */
    public function start()
    {
        $cleanDataArray = array();
        $previousPoint = null;
        foreach($this->getDataArray() as $point){
            
            if($previousPoint != null){
                $pointVector = new PointVector(
                        $previousPoint[0], $previousPoint[1], $previousPoint[2],
                        $point[0], $point[1], $point[2]);
                
                //If data is ok save into clean array otherwise skip to next pointer
                if($pointVector->getSpeed() < $this->getMaxSpeed()){
                    $cleanDataArray[] = array($previousPoint[0], $previousPoint[1], $previousPoint[2]);
                }else{
                    continue;
                }
            }

            $previousPoint = $point;
        }
        
        //Add the last point as will not be included using the above method
        $cleanDataArray[] = array($previousPoint[0], $previousPoint[1], $previousPoint[2]);
        
        $this->setCleanDataArray($cleanDataArray);
        
        return $this;
        
    }
    
    /**
     * Either saves a copy or prints the results
     * 
     * @return string A message to be shown to the user 
     * about the location or the file or the array 
     */
    public function output()
    {
        $output = '';
        foreach($this->getCleanDataArray() as $cleanPoint){
            $output .= $cleanPoint[0] . ', ' . $cleanPoint[1] . ', ' . $cleanPoint[2] . "\n";
        }
        
        if($this->getOutputMethod() == self::OUTPUT_METHOD_PRINT){
            echo $output;
        }else{
            //Generate new name for clean file output
            $fName = substr($this->getFile(), 0, -4) . '_clean.csv';
            file_put_contents($fName, $output);
            echo 'File saved to ' . $fName . "\n";
        }
        
    }
    
    /**
     * Check to see if the file exists
     * @return bolean 
     */
    protected function checkFileExists()
    {
        if (file_exists($this->getFile())) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Convert CSV to data and set as member var dataArray
     * 
     * @return CleanService
     */
    protected function convertCsvToData()
    {
        $fileEx = $this->checkFileExists();
        
        if(!$fileEx){
            die("Sorry but we can't find the file you specified, please check and try again!\n");
        }
        if (($handle = fopen($this->getFile(), "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {
                $this->dataArray[] = $data;
            }
            fclose($handle);
        }
        return $this;
    }
    

    public function getFile() {
        return $this->file;
    }

    public function setFile($file) {
        $this->file = $file;
    }

    public function getDataArray() {
        return $this->dataArray;
    }

    public function setDataArray($dataArray) {
        $this->dataArray = $dataArray;
    }

    public function getOutputMethod() {
        return $this->outputMethod;
    }

    public function setOutputMethod($outputMethod) {
        $this->outputMethod = $outputMethod;
    }

    public function getCleanDataArray() {
        return $this->cleanDataArray;
    }

    public function setCleanDataArray($cleanDataArray) {
        $this->cleanDataArray = $cleanDataArray;
    }

    public function getMaxSpeed() {
        return $this->maxSpeed;
    }

    public function setMaxSpeed($maxSpeed) {
        $this->maxSpeed = $maxSpeed;
    }


    
}


/**
 * The vector between two lat/long points
 * 
 * @author Leonard Austin 
 */
class PointVector {

    protected $latA;
    protected $longA;
    protected $timestampA;
    protected $latB;
    protected $longB;
    protected $timestampB;
    protected $distance;
    protected $speed;

    /**
     *
     * @param str $latA
     * @param str $longA
     * @param str $timestampA
     * @param str $latB
     * @param str $longB
     * @param str $timestampB 
     */
    function __construct($latA, $longA, $timestampA, $latB, $longB, $timestampB){
        $this->latA = $latA;
        $this->longA = $longA;
        $this->timestampA = $timestampA;
        $this->latB = $latB;
        $this->longB = $longB;
        $this->timestampB = $timestampB;
        
        $this->calculateSpeed();
    }

    /**
     * Calulate the speed in mph based on time taken and distance traveled
     * 
     * @return PointVector
     */
    protected function calculateSpeed()
    {
        $this->calculateDistance();
        
        $duration = ($this->getTimestampB() - $this->getTimestampA()) / 3600;
        $this->setSpeed($this->getDistance() / $duration);
        
        return $this;

    }
    
    /**
     * Calulate the distance between to two points. Set the distance member var
     * 
     * @link http://de.60.5646.static.theplanet.com/samples/distance.php.html
     * 
     * @return PointVector
     */
    protected function calculateDistance()
    {
        $theta = $this->getLongA() - $this->getLongB();
        $dist = sin(deg2rad($this->getLatA())) 
        * sin(deg2rad($this->getLatB())) 
        + cos(deg2rad($this->getLatA())) 
        * cos(deg2rad($this->getLatB())) 
        * cos(deg2rad($theta));
        
        $dist = acos($dist);
        $dist = rad2deg($dist);
        
        $this->distance = $dist * 60 * 1.1515;
        
        return $this;
    }
    
    
    public function getLatA() {
        return $this->latA;
    }

    public function setLatA($latA) {
        $this->latA = $latA;
    }

    public function getLongA() {
        return $this->longA;
    }

    public function setLongA($longA) {
        $this->longA = $longA;
    }

    public function getTimestampA() {
        return $this->timestampA;
    }

    public function setTimestampA($timestampA) {
        $this->timestampA = $timestampA;
    }

    public function getLatB() {
        return $this->latB;
    }

    public function setLatB($latB) {
        $this->latB = $latB;
    }

    public function getLongB() {
        return $this->longB;
    }

    public function setLongB($longB) {
        $this->longB = $longB;
    }

    public function getTimestampB() {
        return $this->timestampB;
    }

    public function setTimestampB($timestampB) {
        $this->timestampB = $timestampB;
    }

    public function getDistance() {
        return $this->distance;
    }

    public function setDistance($distance) {
        $this->distance = $distance;
    }

    public function getSpeed() {
        return $this->speed;
    }

    public function setSpeed($speed) {
        $this->speed = $speed;
    }




}