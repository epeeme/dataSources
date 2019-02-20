<?php

// This example will trigger an automatic download of a CSV formatted results file

namespace lpjs;

require '../lpjsParser.php';

$lpjsResultsURL = "https://leonpauljuniorseries.co.uk/results/view/LPJScorepee19";

$e = new lpjsFormatter($lpjsResultsURL);

$e->getAllResultsCSV();