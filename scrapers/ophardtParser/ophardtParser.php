<?php

/**
 * Ophardt Parser
 * 
 * Extract the results data from the Ophardt / Fenctimelive web site
 * 
 * @author  Dan Kew <dan@epee.me>
 * @license http://opensource.org/licenses/gpl-3.0 GNU General Public License, version 3 (GPLv3)
 * @version v1.0.0
 */

namespace ophardt;

class OphardtParser
{
    // The URL of the ophardt results page
    protected $_urlInFull;

    // The raw HTML of the ophardt results page
    protected $_dataBody;

    // The header info for the results table
    protected $_resultsHeader;

    // The raw HTML of just the results table
    protected $_resultsBody;

    // Indexes for the results table
    protected $_rankingIndex;
    protected $_fullnameIndex;
    protected $_clubIndex;
    protected $_countryIndex;

    // All the values used to identify the text in the ophardt table header
    protected $_rankArray = array("Rank", "Ranking", "Rnk", "Rg", "Cl.", "&#160;");
    protected $_fullnameArray = array("Name", "Surname", "Nom", "Apellido-nom");
    protected $_clubArray = array("Club", "Egyesület");
    protected $_countryArray = array("Country", "Nation", "Nación");

    private $_olderVersion = 0;
    /**
     * Initialize the class and set its properties.
     * 
     * @param string $inUrl The URL of the ophardt results
     * 
     * @since 1.0.0
     */
    public function __construct($inUrl) 
    {        
        // So there is no need to pass the URL around, we fix it here
        $this->_urlInFull = $inUrl;

        // Fetch the page data ready for manipulation
        $this->getOphardtPage();
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

        // To accomodate hostoric data, we need to take into account
        // data being extracted from waybackmachine
        if (strpos($this->_dataBody, '<!-- END WAYBACK TOOLBAR INSERT -->') !== false) {
            $this->_resultsBody = stristr($this->_dataBody, '<!-- END WAYBACK TOOLBAR INSERT -->');
        }

        // Now there should be just the one <table tag which signifies
        // the start of the results data
        $this->_resultsBody = stristr(stristr($this->_resultsBody, '<table'), '</table>', true);

    }

    /**
     * Extract out the the header information for the results table. These can
     * vary depending on what the competition organiser has chosen to include.
     * Ordinarily it'll be something like;
     * 
     * Rank | Name | First Name | Club
     * 
     * Column headings should be in the first row, but if some spurious results 
     * start to appear then this will need to be looked at.
     *
     * @since 1.0.0
     * 
     * @return none
     */
    private function _prepareResultsTableHeader()
    {
        if (!(isset($this->_resultsBody))) {
            $this->_prepareResultsData();
        }
        
        $tableRows = explode('<tr', $this->_resultsBody);

        // Use regx if case sensitive option required
        // $tableRows = preg_split("/<tr/i", $this->_resultsBody);

        // First index removed as that's the residue of the <tr>
        $this->_resultsHeader = array_slice(array_map('trim', explode(PHP_EOL, str_replace('&nbsp;', '', strip_tags($tableRows[1])))), 1);
    }

    /**
     * Scans the returned HTML results table header and returns the correct 
     * position of the passed column label
     *
     * @param array $colArray predefined array of known column text labels
     * 
     * @return int index into the table header for the desired column
     */
    private function _setIndexColumn($colArray)
    {
        if (!(isset($this->_resultsHeader))) {
            $this->_prepareResultsTableHeader();
        }

        $i=0;        
        while ($i<count($this->_resultsHeader)) {
            if (array_search($this->_resultsHeader[$i], $colArray) !== false) {
                break;
            }
            $i++;
        }

        return $i<count($this->_resultsHeader) ? $i : -1;
    }

    /**
     * Loads all the column indexes from the HTML results table header so
     * we can be sure we are grabbing the right data from the right columns
     *
     * @return none
     */
    protected function _prepareResultsTableHeaderIndexes()
    {
        $this->_rankingIndex = $this->_setIndexColumn($this->_rankArray);
        $this->_fullnameIndex = $this->_setIndexColumn($this->_fullnameArray);
        $this->_clubIndex = $this->_setIndexColumn($this->_clubArray);
        $this->_countryIndex = $this->_setIndexColumn($this->_countryArray);
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
        
        $this->_prepareResultsTableHeaderIndexes();

        $tableRows = array_slice(preg_split("/<tr/i", $this->_resultsBody), 2);

        for ($i=0; $i < count($tableRows); $i++ ) {
            // Older versions use <td and not <th and also spill onto multiple lines
            // This fixes that and ensure we parse the results correctly.
            if ($this->_olderVersion == 1) {            
                $tableRows[$i] = str_replace("<td", "\n<td", str_replace(array("\n", "\r"), "", $tableRows[$i]));
            }
            // $tempArray = array_slice(array_map('trim', explode(PHP_EOL, str_replace('&nbsp;', '', strip_tags($tableRows[$i])))),1);
            $tempArray = array_slice($this->_myArrayFilter($tableRows[$i]),1);

            $rank = $this->_rankingIndex <> -1 ? $tempArray[$this->_rankingIndex] : '';
            $newNames = $this->_normalizeName($this->_fullnameIndex <> -1 ? $tempArray[$this->_fullnameIndex] : '');

            $club = $this->_clubIndex <> -1 ? $tempArray[$this->_clubIndex] : '';
            $country = $this->_countryIndex <> -1 ? $tempArray[$this->_countryIndex] : '';
            array_push($allResults, array($rank, $newNames[0], $newNames[1], $club, $country));
        }
        
        return $allResults;
    }

    /**
     * Handles the parsing of the results table HTML
     * 
     * @param string $inString chunkc of HTML to parse containing the result
     * 
     * @return array
     */
    protected function _myArrayFilter($inString)
    {           
        // and prevent empty TD cells from affecting the array index        
        $inString = preg_replace('~\></td>~', '>-</td>', $inString);

        return array_values(array_filter(array_map('trim', explode("|", strip_tags(str_replace(">", ">|", $inString)))), 'strlen'));

//        return array_slice(array_values(array_filter(explode("|", strip_tags(str_replace(">", ">|", $inString))), 'is_not_null')), 1);
    }
    
    /**
     * Parses the text header from the ophardt file and extracts all the supplied 
     * information into an array. NOTE - this header area varies depending on how
     * little on how much information the event organiser supplied when setting
     * up their competition. Data positions do not remain static.
     * 
     * The header block format appears to have remained constant across all versions
     *
     * @since 1.0.0
     * 
     * @return array 
     */
    public function parseHeaderBlock()
    {        
        return array_map('trim', explode(PHP_EOL, trim(strip_tags(stristr(stristr($this->_dataBody, '<h1>'), "</h1>", true)))));
    }

    /**
     * Gets the HTML from the source URL and loads it in its raw form 
     * into _dataBody
     * 
     * Can be called directly
     * 
     * @return string $this->_dataBody
     */
    public function getOphardtPage()
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
     * Name format is commonly (UPERCASESURNAME Forename) or 
     * (UPERCASESURNAME, Forename) other random words can also appear 
     * in the text when competition organisers over-ride the standard 
     * name protocol.  However, older versions of fencingtime do not 
     * follow this approach.
     *      
     * @param string $inName raw fencer name
     *
     * @return array parsed forename & surname strings
     */
    protected function _normalizeName($inName)
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

class OphardtFormatter extends OphardtParser
{
    public function getAllResultsCSV()
    {
        $allResultsArray = $this->getAllResults();

        // Handles names and clubs with accents correctly
        header('Content-Type: text/html; charset=utf-8');

        // If UTF8 encoding being used, you may have to manually select
        // that when opening the CSV file in your CSV reader
        header("Content-Disposition: attachment; filename=ophardt.csv");
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

class OphardtDataMine extends OphardtParser
{
    // Extract year of birth and country data from the rankings tables

    private $_yobIndex;
    private $_yobArray = array("YOB", "Egyesület");

    private function _prepareResultsTableHeader()
    {
        if (!(isset($this->_resultsBody))) {
            $this->_prepareResultsData();
        }
        
        $tableRows = spliti("  <tr>    ", $this->_resultsBody); 

        // First index removed as that's the residue of the <tr>
        $this->_resultsHeader = array_slice(array_map('trim', explode(PHP_EOL, str_replace('&nbsp;', '', strip_tags($tableRows[0])))), 2);
    }
    
    private function _prepareResultsData()
    {
        $this->_resultsBody = $this->_dataBody;
        $this->_resultsBody = stristr(stristr($this->_resultsBody, '<table class="table table-striped table-sm rankingbody'), 'all assigned competitions', true);
    }


    protected function _prepareResultsTableHeaderIndexes()
    {
        $this->_yobIndex = $this->_setIndexColumn($this->_yobArray);
        parent::_prepareResultsTableHeaderIndexes();
    }

    private function _setIndexColumn($colArray)
    {
        if (!(isset($this->_resultsHeader))) {
            $this->_prepareResultsTableHeader();
        }

        $i=0;        
        while ($i<count($this->_resultsHeader)) {
            if (array_search($this->_resultsHeader[$i], $colArray) !== false) {
                break;
            }
            $i++;
        }

        return $i<count($this->_resultsHeader) ? $i : -1;
    }

    public function getAllResults()
    {
        $allResults = array();

        if (!(isset($this->_resultsBody))) {
            $this->_prepareResultsData();
        }
        
        $this->_prepareResultsTableHeaderIndexes();

        $tableRowsMain = spliti("  <tr>    ", $this->_resultsBody); 

        for ($j = 1; $j < count($tableRowsMain); $j++)
        {
            $tr1 = stristr($tableRowsMain[$j], '<div class="modal-body>">', true);
            $tr2 = stristr(stristr($tableRowsMain[$j], '<div class="modal-body>">'), '</div>');

            $tableRows = $tr1.$tr2;

            $tempArray = $this->_myArrayFilter($tableRows);
            $country = $this->_countryIndex <> -1 ? $tempArray[$this->_countryIndex+3] : '';
            $yob = $this->_yobIndex <> -1 ? $tempArray[$this->_yobIndex+3] : '';
            $newNames = $this->_normalizeName($this->_fullnameIndex <> -1 ? $tempArray[$this->_fullnameIndex-1] : '');
            array_push($allResults, array($newNames[0], $newNames[1], $yob, $country));
        } 
        return $allResults;
    }

}