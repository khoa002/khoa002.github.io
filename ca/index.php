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
    private $headers;
    private $requiredFields;

    /**
    * Class constructor
    * The default constructor that will be called when a new object is instantiated
    * @param string $file a string of the target file path
    * @throws InvalidArgumentException if the arugment is not type string
    */
    function __construct($file)
    {
        if (empty($file)) {
            throw new Exception("No file specified");
        }
        if (!is_string($file)) {
            throw new Exception("Invalid input, only file paths accepted. Input was: " . gettype($file));
        }
        $this->csvFile = fopen($file, "r");
        if ($this->csvFile === FALSE) {
            throw new Exception("File cannot be open for read");
        }

        $this->requiredFields = ["cost", "price", "qty"];

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
        $this->headers = [];
        $row = 1;
        while (($line = fgetcsv($this->csvFile)) !== FALSE) {
            if ($row == 1) $this->headers = $line; // save the headers to be used for associative inner arrays
            else {
                $array = [];
                foreach ($line as $index => $value) {
                    $array[$this->headers[$index]] = $value;
                }
                $this->calcProfit($array);
                $this->csvArray[] = $array;
            }
            $row++;
        }
    }

    /**
    * Calculate the total profit based on buy and sell prices, and its profit margin
    * @param array $array this function expects an associative array that must contain
    * * at least the following keys: cost, price, qty
    * * the param is passed by reference so the original array will be modified
    * * the key 'profit' and 'profit_margin' will be overwritten if they exist in param
    * @throws InvalidArgumentException if the argument
    * * is not an array
    * * is missing the following keys: cost, price, qty
    * * cost, price, or qty is not a valid number
    */
    private function calcProfit(&$array) {
        if (!is_array($array)) {
            throw new InvalidArgumentException("Invalid input, only array is accepted. Input was: " . gettype($array));
        }
        if (count(array_intersect_key(array_flip($this->requiredFields), $array)) !== count($this->requiredFields)) {
            throw new InvalidArgumentException("Invalid input, the following information are required: cost, price, qty");
        }

        foreach ($this->requiredFields as $field) {
            if (!is_numeric($array[$field])) {
                throw new InvalidArgumentException("Value of '{$field}' is not a valid number");
            }
        }

        $total_cost = $array["cost"] * $array["qty"];
        $total_revenue = $array["price"] * $array["qty"];
        $profit = $total_revenue - $total_cost;
        $profit_margin = $profit / $total_revenue;
        $array["profit"] = $profit;
        $array["profit_margin"] = $profit_margin;
    }

    /**
    * Calculate the summary dataset to output, as specified
    * * average cost
    * * average price
    * * total qty
    * * average profit margin
    * * total profit
    * @param string $type - the type of data expected, accept values:
    * * array (default)
    * * json
    * @return mixed - the result data set
    */
    public function getResultSummary($type = "array") {
        $num_rows = count($this->csvArray);
        if ($num_rows == 0) {
            throw new Exception("No valid data found");
        }
        $result = ["sku" => "Summary"];
        $sum_cost = 0;
        $sum_price = 0;
        $sum_qty = 0;
        $sum_profit_margin = 0;
        $sum_profit = 0;
        foreach ($this->csvArray as $row) {
            $sum_cost += $row["cost"];
            $sum_price += $row["price"];
            $sum_qty += $row["qty"];
            $sum_profit_margin += $row["profit_margin"];
            $sum_profit += $row["profit"];
        }
        $result["cost"] = $sum_cost / $num_rows; // average cost
        $result["price"] = $sum_price / $num_rows; // average price
        $result["qty"] = $sum_qty; // total qty
        $result["profit_margin"] = $sum_profit_margin / $num_rows; // average profit margin
        $result["profit"] = $sum_profit; // total profit

        switch (strtolower($type)) {
            case "json":
                return json_encode($result);
            default:
                return $result;
        }
    }

    /**
    * Accessor function to control the output of the CSV array
    * Here we are not performing any output control
    * @return array the array containing parsed csv values
    */
    public function getResultArray() {
        return $this->csvArray;
    }

    /**
    * Access function to control the output of the CSV array in JSON format
    * Here we are not performing any output control
    * @return string the json formatted string containing parsed csv values
    */
    public function getResultJson() {
        return json_encode($this->csvArray);
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
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <style type="text/css">
        body {
            margin: 1em;
        }
    </style>
  </head>
  <body>
    <div class="container-fluid">
        <h3>Import File</h3>
        <p>Please select a valid CSV file for import and parsing.</p>
        <form class="form-inline" id="upload-form" name="upload-form"
              action="<?= basename(__FILE__); ?>" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="target-file">CSV File</label>
                <input class="form-control" type="file" name="target-file" id="target-file" />
            </div>
            <button type="submit" id="submit" name="submit" class="btn btn-success">Submit</button>
        </form>
    </div>
    <div class="container-fluid">
        <?php
        /**
        * If the form is submitted, upload the file and process its contents
        * then display the result table below the form
        */
        if (isset($_POST["submit"])) {
            try {
                $fileObj = new ProcessProductTable($_FILES["target-file"]["tmp_name"]);
                $data = $fileObj->getResultArray();
                if(empty($data)) {
                    echo '<div class="bg-warning" style="margin: 1em 0; padding: 1em;">No data</div>';
                } else {
                    /**
                    * outputting only the following columns and ignoring the rest, per specifications
                    * SKU, Cost, Price, QTY, Profit Margin, Total Profit.
                    */
                    $tableHtml  = '<table class="table table-hover table-bordered" style="margin: 1em 0;">';
                    $tableHtml .= "<tr><th class='text-left'>SKU</th><th class='text-right'>Cost</th><th class='text-right'>Price</th><th class='text-right'>QTY</th><th class='text-right'>Profit Margin</th><th class='text-right'>Total Profit</th></tr>";
                    foreach ($data as $row) {
                        $tableHtml .= "<tr>";
                        $tableHtml .= "<td class='text-left'>{$row["sku"]}</td>";
                        $tableHtml .= "<td class='text-right'>$" . number_format($row["cost"], 2) . "</td>";
                        $tableHtml .= "<td class='text-right'>$" . number_format($row["price"], 2) . "</td>";
                        $tableHtml .= "<td class='text-right " . ($row["qty"] >= 0 ? "text-success" : "text-danger") . "'>"  . number_format($row["qty"], 2) . "</td>";
                        $tableHtml .= "<td class='text-right " . ($row["profit_margin"] >= 0 ? "text-success" : "text-danger") . "'>"  . number_format(($row["profit_margin"] * 100), 2) . "%</td>";
                        $tableHtml .= "<td class='text-right " . ($row["profit"] >= 0 ? "text-success" : "text-danger") . "'>$" . number_format($row["profit"], 2) . "</td>";
                        $tableHtml .= "</tr>";
                    }
                    $summary = $fileObj->getResultSummary();

                    $tableHtml .= "<tr class='info'>";
                    $tableHtml .= "<td class='text-left'>{$summary["sku"]}</td>";
                    $tableHtml .= "<td class='text-right'>Avg: $" . number_format($summary["cost"], 2) . "</td>";
                    $tableHtml .= "<td class='text-right'>Avg: $" . number_format($summary["price"], 2) . "</td>";
                    $tableHtml .= "<td class='text-right " . ($summary["qty"] >= 0 ? "text-success" : "text-danger") . "'>Total: "  . number_format($summary["qty"], 2) . "</td>";
                    $tableHtml .= "<td class='text-right " . ($summary["profit_margin"] >= 0 ? "text-success" : "text-danger") . "'>Avg: "  . number_format(($summary["profit_margin"] * 100), 2) . "%</td>";
                    $tableHtml .= "<td class='text-right " . ($summary["profit"] >= 0 ? "text-success" : "text-danger") . "'>Total: $" . number_format($summary["profit"], 2) . "</td>";
                    $tableHtml .= "</tr>";

                    $tableHtml .= "</table>";

                    echo $tableHtml;
                }
            } catch (Exception $e) {
                echo '<div class="bg-danger" style="margin: 1em 0; padding: 1em;">' . $e->getMessage() . '</div>';
            }
        }?>
    </div>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
  </body>
</html>