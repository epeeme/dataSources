<?php

// This example will trigger an automatic download of a CSV formatted results file

namespace ophardt;

require '../ophardtParser.php';

$ophardtResultsURL = "https://fencing.ophardt.online/en/show-ranking/html/14422";

$e = new OphardtDataMine($ophardtResultsURL);

$allResults = $e->getAllResults();

// HTML TABLE used for simple display purposes only
echo "<TABLE BORDER=1 CELLPADDING=3 CELLSPACING=2>\n";
echo "<TR ALIGN=LEFT><TH>Surname</TH><TH>Forename</TH><TH>YoB</TH><TH>Country</TH></TR>\n";

foreach ($allResults as $value) {
    echo "<TR><TD>".$value[0]."</TD><TD>".$value[1]."</TD><TD>".$value[2]."</TD><TD>".$value[3]."</TD></TR>\n";
}
echo "</TABLE>\n";

echo "<p>Extracted from <a href=\"".$ophardtResultsURL."\">".$ophardtResultsURL."</a></p>";