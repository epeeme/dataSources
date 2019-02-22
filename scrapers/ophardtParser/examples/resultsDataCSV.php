<?php

// This example will trigger an automatic download of a CSV formatted results file

namespace ophardt;

require '../ophardtParser.php';

$ophardtResultsURL = "https://epee.me/liveResults/2018CadetBRCCadet/FTEvent1.htm";

$e = new OphardtFormatter($ophardtResultsURL);

$e->getAllResultsCSV();