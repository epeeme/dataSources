<?php

/**
 * Script to convert the old clubs.csv system to the one that now
 * resides in MySQL as the table clubsAlias.
 * 
 * Initially the table was created without an index to the main
 * clubs table. The following query was run after the data was
 * imported to link the 2 together.
 * 
 * update clubsAlias SET clubID = (select ID from clubs where clubsAlias.actual = clubs.clubName LIMIT 0,1)
 * 
 * Data imported - 18/2/2019
 */


require 'db.php';

$db = new dbBF();

$csVFile = "/home/u534143343/public_html/clubs.csv";

if (($handle = fopen($csVFile, "r")) !== false) {

    while (($data = fgetcsv($handle, 4096, ",")) !== false) {
      
        $data = array_map("utf8_encode", $data);

        $s  = $db -> prepare('INSERT INTO clubsAlias (alias, actual) VALUES (?, ?)');
        $db -> bind($s, 1, $data[0]);      
        $db -> bind($s, 2, $data[1]);      
        $db -> execute($s);    
    }

    fclose($handle);
}
