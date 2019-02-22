<?php

// This example shows the output from getAllResults()

namespace ophardt;

header('Content-Type: text/html; charset=utf-8');

require '../ophardtParser.php';

$ophardtResultsURL = "http://fencingworldwide.com/en/competition/1380-2018/results/";

$e = new OphardtParser($ophardtResultsURL);

$allResults = $e->getAllResults();

// HTML TABLE used for simple display purposes only
echo "<TABLE BORDER=1 CELLPADDING=3 CELLSPACING=2>\n";
echo "<TR ALIGN=LEFT><TH>Rank</TH><TH>Surname</TH><TH>Forename</TH><TH>Club</TH><TH>Country</TH></TR>\n";

foreach ($allResults as $value) {
    echo "<TR><TD>".$value[0]."</TD><TD>".$value[1]."</TD><TD>".$value[2]."</TD><TD>".$value[3]."</TD><TD>".$value[4]."</TD></TR>\n";
}
echo "</TABLE>\n";

echo "<p>Extracted from <a href=\"".$ophardtResultsURL."\">".$ophardtResultsURL."</a></p>";