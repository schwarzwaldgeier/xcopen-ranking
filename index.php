<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'Flight.php';
require_once 'Pilot.php';

$gistUrl = "https://gist.githubusercontent.com/sandornusser/dd4d7efae40f3c43a83a20509cbbddc2/raw/d54739f423b4bb6659d30353815725266fbf3882/gistfile1.txt";
$gist = file_get_contents($gistUrl);

if ($gist === false){
    die('Failed to retrieve participants');
}
$lines = explode("\n", $gist);
$participantIds = [];
foreach ($lines as $line) {
    $parts = preg_split('/\s+/', $line);
    $lastPart = end($parts);
    $participantIds[] = trim($lastPart);
}

$filterAllPilots = "d0=18.05.2023&d1=21.07.2023&fkcat%5B%5D=1&l-fkcat%5B%5D=Gleitschirm&fkto%5B%5D=9543&l-fkto%5B%5D=Merkur%20DE&navpars=%7B%22start%22%3A0%2C%22limit%22%3A500%2C%22sort%22%3A%5B%7B%22field%22%3A%22BestTaskPoints%22%2C%22dir%22%3A1%7D%2C%7B%22field%22%3A%22BestTaskSpeed%22%2C%22dir%22%3A1%7D%5D%7D";
$filterParticipants = $filterAllPilots;

foreach ($participantIds as $pid) {
    $filterParticipants .= "&fkpil%5B%5D=$pid";
}


$arrContextOptions = array(
    "ssl" => array(
        "verify_peer" => false,
        "verify_peer_name" => false,
    ),
);

$url = "https://de.dhv-xc.de/api/fli/flights?" . $filterParticipants;

$response = file_get_contents($url, false, stream_context_create($arrContextOptions));

$responseJson = json_decode($response);
$flights = $responseJson->data;


$pilots = [];


foreach ($flights as $flight) {


    $pilotId = $flight->{'FKPilot'};
    if (!in_array($pilotId, $participantIds)) {
        continue;
    }
    if (!isset($pilots[$pilotId])) {
        $pilot = new Pilot($pilotId);
        $pilot->name = $flight->{'FirstName'} . " " . $flight->{'LastName'};
        $pilots[$pilotId] = $pilot;
    }
    /**
     * @var Pilot $pilots [$pilotId]
     */
    $thisFlight = new Flight($flight->{'IDFlight'});
    $thisFlight->flightDuration = (int)$flight->{'FlightDuration'};

    $thisFlight->calcAirtimePoints();


    $pilots[$pilotId]->flights[] = $thisFlight;
}

foreach ($pilots as $pilot) {
    $pilot->getBestPoints();
}

usort($pilots, function ($a, $b) {
    /**
     * @var Pilot $a
     * @var Pilot $b
     */

    $diff = $a->totalPoints - $b->totalPoints;
    if ($diff == 0.0) {
        return 0;
    } else if ($diff > 0) {
        return 1;
    } else {
        return -1;
    }

});

$pilots = array_reverse($pilots);

?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex">
        <link rel="stylesheet" href="style.css">
        <title>XC open Ranking</title>
    </head>

    <body>
    <h2>XC Open 2022 Live Ranking</h2>


    <table>
        <tr>
            <th title="Platzierung">🏅</th>
            <th title="Pilot">🪂</th>
            <th title="One-Way">💨</th>
            <th title="Dreieck">📐</th>
            <th title="Airtime">🕰️</th>
            <th title="Summe">Σ</th>
        </tr>
        <?php

        $flightUrl = "https://de.dhv-xc.de/flight/";
        $rank = 0;
        foreach ($pilots as $pilot) {
            $rank++;
            $displayRank = $rank;
            if ($rank === 1) {
                $displayRank = "🥇";
            } else if ($rank === 2) {
                $displayRank = "🥈";
            } else if ($rank === 3) {
                $displayRank = "🥉";
            }
            $totalScore = round($pilot->totalPoints, 2);
            $name = $pilot->name;

            $airtimeScore = round($pilot->airtimePoints, 2);
            $triangleScore = round($pilot->trianglePoints, 2);
            $distanceScore = round($pilot->distancePoints, 2);
            $distanceUrl = "#";
            $triangleUrl = "#";
            $airtimeUrl = "#";

            if (!empty($pilot->distancePointsFlight)) {
                $distanceUrl = $flightUrl . $pilot->distancePointsFlight;
            }

            if (!empty($pilot->trianglePointsFlight)) {
                $triangleUrl = $flightUrl . $pilot->trianglePointsFlight;
            }

            if (!empty($pilot->airtimePointsFlight)) {
                $airtimeUrl = $flightUrl . $pilot->airtimePointsFlight;
            }

            $distanceCell = "<td><a href=\"$distanceUrl\">$distanceScore</a></td>";
            if ($distanceUrl === "#") {
                $distanceCell = "<td>$distanceScore</td>";
            }

            $triangleCell = "<td><a href=\"$triangleUrl\">$triangleScore</a></td>";

            if ($triangleUrl === "#") {
                $triangleCell = "<td>$triangleScore</td>";
            }
            $airtimeCell = "<td><a href=\"$airtimeUrl\">$airtimeScore</a></td>";

            if ($airtimeUrl === "#") {
                $airtimeCell = "<td>$airtimeScore</td>";
            }

            $pilotUrl = "https://de.dhv-xc.de/flights?" . $filterAllPilots . "&fkpil=" . $pilot->id;


            $out = <<<HEREDOC
<tr>
<td>$displayRank</td>
<td><a href="$pilotUrl">$name</a></td>
$distanceCell
$triangleCell
$airtimeCell

<td>$totalScore</td>
</tr>



HEREDOC;

            echo $out;


        }

        ?>
    </table>

    </body>
    </html>

