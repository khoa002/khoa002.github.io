<?php
/**
* Class used to parse a CSV file product information
*
* This class should be included from a separate file, but was kept within the same file
* to meet with client specification.
*
* @author Khoa Nguyen <khoa002@gmail.com>
*/
class ProcessProductTable
{
    /**
    * Class variables
    */
    private $csvFile;
    private $csvArray;

    /**
    * Class constructor
    * The default constructor that will be called when a new object is instantiated
    * @param string $file a string of the target file path
    * @throws InvalidArgumentException if the arugment is not type string
    */
    function __construct($file)
    {
        if (!is_string($file)) {
            throw new InvalidArgumentException("Invalid argument, only file paths accepted. Input was: " . gettype($file));
        }
        $this->csvFile = fopen($file, "r");
        if ($this->csvFile === FALSE) {
            throw new Exception("File cannot be open for read");
        }

        $this->parseFileIntoArray();
    }

    /**
    * Class destructor
    * Destructor function that will be called when the object is no longer referenced.
    * This is to perform some manual clean up features, if needed.
    */
    function __destruct() {
        fclose($this->csvFile); // close the file handler
    }

    /**
    * Parse the content of a csv file into an array
    */
    private function parseFileIntoArray() {
        $this->csvArray = []; // instantiate the class variable
        $headerRow = [];
        $row = 1;
        while (($line = fgetcsv($this->csvFile)) !== FALSE) {
            if ($row == 1) $headerRow = $line; // save the headers to be used for associative inner arrays
            else {
                $array = [];
                foreach ($line as $index => $value) {
                    $array[$headerRow[$index]] = $value;
                }
                $this->calcProfit($array);
                $this->csvArray[] = $array;
            }
            $row++;
        }
    }

    /**
    * Calculate the total profit based on buy and sell prices
    * @param array $array this function expects an associative array that must contain
    *   at least the following keys: cost, price, qty
    *   !! if $array has the existing key 'profit', it will be replace !!
    *   this parameter is passed by reference, the original array will be modified
    * @throws InvalidArgumentException if the argument
    *   is not an array
    *   is missing the following keys: cost, price, qty
    *   cost, price, or qty is not a valid number
    */
    private function calcProfit(&$array) {
        if (!is_array($array)) {
            throw new InvalidArgumentException("Invalid argument, only array is accepted. Input was: " . gettype($array));
        }
        $requiredFields = ["cost", "price", "qty"];
        if (count(array_intersect_key(array_flip($requiredFields), $array)) !== count($requiredFields)) {
            throw new InvalidArgumentException("Invalid input array, the following keys are required: cost, price, qty");
        }

        foreach ($requiredFields as $field) {
            if (!is_numeric($array[$field])) {
                throw new InvalidArgumentException("Value of '{$field}' is not a valid number");
            }
        }
        
        $array["profit"] = ($array["price"] * $array["qty"]) - ($array["cost"] * $array["qty"]);
    }

    /**
    * Accessor function to control the output of the CSV array
    * Here we are not performing any output control
    */
    public function getResultArray() {
        return $this->csvArray;
    }

    /**
    * Access function to control the output of the CSV array in JSON format
    * Here we are not performing any output control
    */
    public function getResultJson() {
        return json_encode($this->csvArray);
    }
}





/**
* If the form is submitted, upload the file and process its contents
*/
if (isset($_POST["submit"])) {
    try {
        $fileObj = new ProcessProductTable($_FILES["target-file"]["tmp_name"]);
        var_dump($fileObj->getResultJson());
    } catch (Exception $e) {
        die('Error caught: ' . $e->getMessage());
    }
}

?>
<!DOCTYPE html>
<html>
  <head>
    <title>CSV Product Parser</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  </head>
  <body>
    <form id="upload-form" name="upload-form"
          action="<?= basename(__FILE__); ?>" method="POST" enctype="multipart/form-data">
        <input type="file" name="target-file" id="target-file" />
        <button type="submit" id="submit" name="submit">Submit</button>
    </form>
  </body>
</html>