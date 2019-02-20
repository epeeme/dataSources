<?php

// This example shows the output from getAllResults()

namespace lpjs;

header('Content-Type: text/html; charset=utf-8');

require '../lpjsParser.php';

$lpjsResultsURL = "https://leonpauljuniorseries.co.uk/results/view/LPJScorepee19/U13/boy/Epee";

$e = new LPJSParser($lpjsResultsURL);

$allResults = $e->getAllResults();

// HTML TABLE used for simple display purposes only
echo "<TABLE BORDER=1 CELLPADDING=3 CELLSPACING=2>\n";
echo "<TR ALIGN=LEFT><TH>Rank</TH><TH>Surname</TH><TH>Forename</TH><TH>Club</TH><TH>Country</TH></TR>\n";

foreach ($allResults as $value) {
    echo "<TR><TD>".$value[0]."</TD><TD>".$value[1]."</TD><TD>".$value[2]."</TD><TD>".$value[3]."</TD><TD>".$value[4]."</TD></TR>\n";
}
echo "</TABLE>\n";

echo "<p>Extracted from <a href=\"".$lpjsResultsURL."\">".$lpjsResultsURL."</a></p>";