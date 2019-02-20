<?php

// Sample script to get the results for an entire event, parse them
// and load them into the epee.me database

require '../lib/db.php';
require '../lib/pointsHandler.php';
require '../scrapers/engardeParser/engardeParser.php';

require 'coreSQL.php';

$db = new dbBF();
$points = new PointsHandler();

/**  
 *        u9   u10   u11   u12   u13   u14   u15   u16   u17   u18   u19   u20   u23
 * Boys   10   11    8     15    3     17    5     13    6     19    23    21    29
 * Girls  9    12    7     16    4     18    1     14    2     20    24    22    28
 */

// COMPETITION DATA 
$ageArray = array(26,0);
$eventID = 189;
$eventDate = '2019-02-09';
$eventYear = 2019;
$eventURL = "https://www.engarde-service.com/files/fce/ticb2019/";
// ENDS

// Set this to 1 if there is club and country data present but we want 
// just the country data as it's an International
$overRideClub = 0; 

// First insert the date for the event but make sure it doesn't exist already
$dateID = eventDates($db, $eventDate, $eventID, $eventYear);

// Now for the eventData which is often the time consuming manual part
eventData($db, $ageArray, $dateID, $eventID);

// Now clean out the engarde holding file so it's ready to take the new data

$s = $db -> prepare('DELETE FROM engarde');
$db -> execute($s);

// Grab all the data from the source URL and place it in the 
// now empty engarde table.

// example;
// http://www.engarde-service.com/files/leonpaulfencingcentre/lpjslonepee19/

// This is a <ul><li> based list so we can extract that easily into an array
// the link the results page is a simple <a href="uxx"> affair
// so that can be added to the base URl above and then the standard parser
// can deal with any framset stuff

$dataBody = file_get_contents($eventURL);
$resultsBody = $dataBody;
$resultsBody = stristr(stristr($resultsBody, '<ul>'), '</ul>', true);
$tableRows = array_slice(preg_split("/<li/i", $resultsBody), 1);

for ($i=0; $i < count($tableRows); $i++ ) {
    
    // Only get results if age category > 0. This allows team comps and
    // other irrelevent comps to be excluded.
    
    if ($ageArray[$i] > 0) {

        $engardeResultsURL = get_istring_between($tableRows[$i], '<a href="', '">');
        $e = new engarde\EngardeParser($eventURL.$engardeResultsURL."/index.php?page=clasfinal.htm");
        $allResults = $e->getAllResults();
    
        $numberOfEntries = 0;

        foreach ($allResults as $value) {
            $s  = $db -> prepare('INSERT INTO engarde (eventID, eventYear, eventCat, fencerFirstname, fencerSurname, fencerClub, eventPosition, lpjsPoints) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $db -> bind($s, 1, $eventID);      
            $db -> bind($s, 2, $eventYear);      
            $db -> bind($s, 3, $ageArray[$i]);        
            $db -> bind($s, 4, $value[2]);
            $db -> bind($s, 5, ucwords(strtolower($value[1]), " -'"));
            if (($value[3] !== '') && ($overRideClub == 0)) {
                // Remove multiple whitespace as this causes problems with string matching
                $db -> bind($s, 6, preg_replace('/\s+/', ' ', $value[3]));      
            } else {
                $db -> bind($s, 6, $value[4]);
            }

            $db -> bind($s, 7, getRank($value[0]));
            $db -> bind($s, 8, $points->getLPJSpoints(getRank($value[0])));      
            $db -> execute($s);

            $s  = $db -> prepare('SELECT LAST_INSERT_ID()');
            $db -> execute($s);    
            $resultID = $db -> getResult($s);
            if (($resultID === false) || ($resultID === 0)) {
                die("Unable to insert result data for ".$value[2]." ".$value[1].".");
            } 
            $numberOfEntries++;
        }    
    }

    // After each category has been updated we can now update the number
    // of entries in the eventData table.

    $s  = $db -> prepare('UPDATE eventData SET entries = (?) WHERE dateID = (?) AND catID = (?) AND eventID = (?)');
    $db -> bind($s, 1, $numberOfEntries);      
    $db -> bind($s, 2, $dateID);      
    $db -> bind($s, 3, $ageArray[$i]);        
    $db -> bind($s, 4, $eventID);
    $db -> execute($s);    

    unset($e);
}        

// Having loaded all the data, now try and assign it to existing data entries
// This will still require some manual checking for a variety of reasons, but
// for the most part this should do the job.

$s  = $db -> prepare('UPDATE engarde SET fencerID = (SELECT fencers.ID FROM fencers WHERE fencers.fencerFirstname = engarde.fencerFirstname AND fencers.fencerSurname = engarde.fencerSurname LIMIT 0,1)');
$db -> execute($s);

// And last but not least, handle the club data

$s  = $db -> prepare('UPDATE engarde SET fencerClubID = (SELECT clubsAlias.clubID FROM clubsAlias WHERE clubsAlias.alias = engarde.fencerClub LIMIT 0,1)');
$db -> execute($s);    

// And to round off, display the SQL to be run in the console to update
// and commit the manual changes.

echo "<P>Make any changes to fencers names and run this<BR><CODE>UPDATE engarde SET fencerID = (SELECT fencers.ID FROM fencers WHERE fencers.fencerFirstname = engarde.fencerFirstname AND fencers.fencerSurname = engarde.fencerSurname LIMIT 0,1)</CODE></P>";
echo '<hr>';
echo '<P>Add in new fencers<BR><CODE>INSERT INTO fencers (fencerFirstname, fencerSurname, fencerFullname) SELECT engarde.fencerFirstname, engarde.fencerSurname, CONCAT(engarde.fencerFirstname," ",engarde.fencerSurname) FROM engarde WHERE fencerID = 0</CODE></P>';
echo '<P>Alt with a sex specific & ranking inclusiion<BR><CODE>INSERT INTO fencers (fencerFirstname, fencerSurname, fencerFullname, sex, efr) SELECT engarde.fencerFirstname, engarde.fencerSurname, CONCAT(engarde.fencerFirstname," ",engarde.fencerSurname),"Male", 1 FROM engarde WHERE fencerID = 0</CODE></P>';
echo '<hr>';
echo '<P>Check clubs and add any new aliases<BR><CODE>UPDATE engarde SET fencerClubID = (SELECT clubsAlias.clubID FROM clubsAlias WHERE clubsAlias.alias = engarde.fencerClub LIMIT 0,1)</CODE></P>';
echo '<hr>';
echo '<P>Make live<BR><CODE>INSERT INTO results (eventID, dateID, eventCat, fencerID, fencerClubID, eventPosition, lpjsPoints) SELECT engarde.eventID, eventDates.ID , eventCat, fencerID, fencerClubID, eventPosition, lpjsPoints FROM engarde LEFT OUTER JOIN eventDates ON year = eventYear AND eventDates.eventID = engarde.eventID</CODE></P>';
echo '<hr>';

echo "<BR><BR><P><STRONG>ALL DONE</STRONG></P>";