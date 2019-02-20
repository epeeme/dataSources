<?php

// This example shows the output from getAllResults()

namespace fencingtime;

header('Content-Type: text/html; charset=utf-8');

require '../fencingtimeParser.php';

$fencingtimeResultsURL = "https://web.archive.org/web/20111121134201/http://www.eliteepee.com/fencing/elite/EliteEpee.nsf/db3d5431cf917ba680256fe40066a592/86a2bbbe586da447c125716d00777d04/$FILE/ATT9PIV8/Under%2011%20Boys.html";

$e = new FencingtimeParser($fencingtimeResultsURL);

$allResults = $e->getAllResults();

// HTML TABLE used for simple display purposes only
echo "<TABLE BORDER=1 CELLPADDING=3 CELLSPACING=2>\n";
echo "<TR ALIGN=LEFT><TH>Rank</TH><TH>Surname</TH><TH>Forename</TH><TH>Club</TH><TH>Country</TH></TR>\n";

foreach ($allResults as $value) {
    echo "<TR><TD>".$value[0]."</TD><TD>".$value[1]."</TD><TD>".$value[2]."</TD><TD>".$value[3]."</TD><TD>".$value[4]."</TD></TR>\n";
}
echo "</TABLE>\n";

echo "<p>Extracted from <a href=\"".$fencingtimeResultsURL."\">".$fencingtimeResultsURL."</a></p>";