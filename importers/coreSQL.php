<?php

// These functions are global to all of the importers

function eventDates($db, $eventDate, $eventID, $eventYear)
{
    $s = $db -> prepare('SELECT ID FROM eventDates WHERE fullDate = ? AND eventID = ? LIMIT 0,1');
    $db -> bind($s, 1, $eventDate);
    $db -> bind($s, 2, $eventID);      
    $db -> execute($s);
    $dateQuery = $db -> getResult($s);
    if ($dateQuery === false) {
        $s  = $db -> prepare('INSERT INTO eventDates (fullDate, year, eventID) VALUES (?, ?, ?)');
        $db -> bind($s, 1, $eventDate);
        $db -> bind($s, 2, $eventYear);
        $db -> bind($s, 3, $eventID);
        $db -> execute($s);
    
        $s  = $db -> prepare('SELECT LAST_INSERT_ID()');
        $db -> execute($s);    
        $dateID = $db -> getResult($s);
        if (($dateID === false) || ($dateID === 0)) {
            die("Unable to set event date.");
        }
    } else {
        $dateID = $dateQuery;
    }
    return $dateID;
}

function eventData($db, $ageArray, $dateID, $eventID)
{
    foreach ($ageArray as $age) {
        if ($age > 0) {
            $s = $db -> prepare('SELECT ID FROM eventData WHERE dateID = ? AND catID = ? AND eventID = ? LIMIT 0,1');
            $db -> bind($s, 1, $dateID);
            $db -> bind($s, 2, $age);      
            $db -> bind($s, 3, $eventID);      
            $db -> execute($s);
            $dataQuery = $db -> getResult($s);
            if ($dataQuery === false) {
                $s  = $db -> prepare('INSERT INTO eventData (dateID, catID, eventID) VALUES (?, ?, ?)');
                $db -> bind($s, 1, $dateID);
                $db -> bind($s, 2, $age);      
                $db -> bind($s, 3, $eventID);      
                $db -> execute($s);
        
                $s  = $db -> prepare('SELECT LAST_INSERT_ID()');
                $db -> execute($s);    
                $dataID = $db -> getResult($s);
                if (($dataID === false) || ($dataID === 0)) {
                    die("Unable to set event data for age category ".$age.".");
                } 
            }    
        }    
    }    
}

function get_istring_between($string, $start, $end) 
{
    $ini = (stripos($string, $start) + strlen($start));
    return $ini !== stripos($string, $start) ? substr($string, $ini, (stripos($string, $end, $ini) - $ini)) : '';
}

function getRank($inRank) 
{    
    // Sanitize rank - remove Ts, DNS, DNF, etc and set to 9999 if applicable
    $eventPosition = (int) filter_var($inRank, FILTER_SANITIZE_NUMBER_INT);
    return $eventPosition > 0 ? $eventPosition : 9999;
}