<?php

// This example will trigger an automatic download of a CSV formatted results file

namespace lpjs;

require '../lpjsParser.php';

$lpjsResultsURL = "https://epee.me/liveResults/2018CadetBRCCadet/FTEvent1.htm";

$e = new lpjsFormatter($lpjsResultsURL);

$e->getAllResultsJSON();