<?php

/**
 * FIE Parser
 * 
 * Extract the results data from the https://fie.org/ site
 * 
 * @author  Dan Kew <dan@epee.me>
 * @license http://opensource.org/licenses/gpl-3.0 GNU General Public License, version 3 (GPLv3)
 * @version v1.0.0
 */

namespace fie;

class FIEParser
{
    // The URL of the FIE results page
    protected $_urlInFull;

    // The raw HTML of the FIE results page
    protected $_dataBody;

    // The header info for the results table
    private $_resultsHeader;

    // The raw HTML of just the results table
    private $_resultsBody;

    /**
     * Initialize the class and set its properties.
     * 
     * @param string $inUrl The URL of the FIE results
     * 
     * @since 1.0.0
     */
    public function __construct($inUrl) 
    {        
        // So there is no need to pass the URL around, we fix it here
        $this->_urlInFull = $inUrl;

        // Fetch the page data ready for manipulation
        $this->getFIEPage();
    }


    /**
     * Checks that the supplied URL exists and is accessible
     *
     * @since 1.0.0
     * 
     * @return boolean $exists
     */
    private function _URLexists()
    {
        $urlHeaders = get_headers($this->_urlInFull);
        if (!$urlHeaders || $urlHeaders[0] == 'HTTP/1.1 404 Not Found') {
            $exists = false;
        } else {
            $exists = true;
        }      
        return $exists;
    }

    /**
     * Ready the HTML table of results for parsing and load into _resultsBody
     *
     * @since 1.0.0
     * 
     * @return none
     */
    private function _prepareResultsData()
    {
        $this->_resultsBody = $this->_dataBody;

        // The new FIE site has tabbed data which can include more than one result set, 
        // so in this case we only want to extract the current active tab data - and
        // as luck would have it, this is stored as JSON data in the source

        $this->_resultsBody = stristr(stristr(stristr($this->_resultsBody, 'window._athletes = '), ';', true), '[');
    }
    
    /**
     * Extracts all the results from the HTML results table, cleans and loads 
     * them into an array ready for manipulation
     *
     * @return array
     */
    public function getAllResults()
    {
        $allResults = array();

        if (!(isset($this->_resultsBody))) {
            $this->_prepareResultsData();
        }

        // As we can extract JSON data directly from the FIE source we can work
        // with that directly. Example;

        /*
        [0] => stdClass Object
        (
            [overallRanking] => 276
            [overallPoints] => 3
            [rank] => 1
            [points] => 32
            [fencer] => stdClass Object
                (
                    [id] => 37970
                    [name] => BAUDUNOV Khasan
                    [country] => UZBEKISTAN
                    [date] => 2001-02-22
                    [flag] => UZ
                    [countryCode] => UZB
                    [age] => 18
                )

        )
        */

        $this->_resultsBody = json_decode($this->_resultsBody);
        foreach ($this->_resultsBody AS $result) {
            $newNames = $this->_normalizeName($result->{'fencer'}->{'name'});
            array_push($allResults, array($result->{'rank'}, $newNames[0], $newNames[1], '', $result->{'fencer'}->{'countryCode'}));
        }

        return $allResults;
    }

    /**
     * Name format is at present just camel-caps space delimited
     *      
     * @param string $inName raw fencer name
     *
     * @return array parsed forename & surname strings
     */
    private function _normalizeName($inName)
    {
        $forename = $surname = '';

        $removeThisText = array("(None)", "(V)", "(C)", "(J)");
             
        // Remove anything that's not needed

        $inNameParse = preg_replace('/[\s]+/mu', ' ', str_ireplace($removeThisText, '', $inName));
        if (strpos($inNameParse, ',') !== false) {
            $inNameParse = explode(",", $inNameParse);
            $surname = $inNameParse[0];
            $forename = $inNameParse[1];
        } else {         
            $inNameParse = explode(" ", $inNameParse);
            for ($i=0; $i < count($inNameParse); $i++) {
                mb_strtoupper($inNameParse[$i], 'utf-8') == $inNameParse[$i] ? $surname .= " ".$inNameParse[$i] :  $forename .=  " ".$inNameParse[$i];
            }
        }    
        return array(trim($surname), trim($forename));
    }

    /**
     * Gets the HTML from the source URL and loads it in its raw form 
     * into _dataBody
     * 
     * Can be called directly
     * 
     * @return string $this->_dataBody
     */
    public function getFIEPage()
    {
        if ($this->_URLexists($this->_urlInFull)) {
            $this->_dataBody = file_get_contents($this->_urlInFull);
            if ($this->_dataBody === false) {
                $this->_handleError('Unable to read data from supplied URL.');
            }
        } else {
            $this->_handleError('Unable to locate supplied URL');
        }
        return $this->_dataBody;
    } 

    /**
     * Simple error handler to display message when something doesn't
     * go to plan and stop executing the script!
     * 
     * @param string $inError message indicating the error
     * 
     * @return none
     */
    private function _handleError($inError)
    {
        die($inError);
    }
}

class FIEFormatter extends FIEParser
{
    public function getAllResultsCSV()
    {
        $allResultsArray = $this->getAllResults();

        // Handles names and clubs with accents correctly
        header('Content-Type: text/html; charset=utf-8');

        // If UTF8 encoding being used, you may have to manually select
        // that when opening the CSV file in your CSV reader
        header("Content-Disposition: attachment; filename=fie.csv");
        header("Content-Type: application/vnd.ms-excel");
            
        $fp = fopen('php://output', 'w');
        fputcsv($fp, array('Rank', 'Surname', 'Forename', 'Club', 'Country'));
        foreach ($allResultsArray as $value) {
            fputcsv($fp, $value);
        }
        fclose($fp);
    }

    public function getAllResultsJSON()
    {
        $allResultsArray = $this->getAllResults();

        // Handles names and clubs with accents correctly
        header('Content-Type: text/html; charset=utf-8');
        
        header('Content-Type: application/json');
        
        echo json_encode($allResultsArray);
    }
}
