<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

const FILTER_PARAMS = "d0=26.05.2022&d1=19.08.2022&fkcat%5B%5D=1&l-fkcat%5B%5D=Gleitschirm&fkto%5B%5D=9543&l-fkto%5B%5D=Merkur%20DE&navpars=%7B%22start%22%3A0%2C%22limit%22%3A500%2C%22sort%22%3A%5B%7B%22field%22%3A%22BestTaskPoints%22%2C%22dir%22%3A1%7D%2C%7B%22field%22%3A%22BestTaskSpeed%22%2C%22dir%22%3A1%7D%5D%7D";


$arrContextOptions = array(
    "ssl" => array(
        "verify_peer" => false,
        "verify_peer_name" => false,
    ),
);

$allFlightsUrl = "https://de.dhv-xc.de/api/fli/flights?" . FILTER_PARAMS;
$allFlightsResponse = file_get_contents($allFlightsUrl, false, stream_context_create($arrContextOptions));

$allFlightsDataJson = json_decode($allFlightsResponse);
$flights = $allFlightsDataJson->data;

$participantDhvXcIds = [
    "11922", //Wölfle
    "135", //Winkler
    "11726", //Plank
    "7471", //Bachura
    "5108", //Demmert
    "1922", //burkart
    "37", //Ruf
    "9251", //Wolfgang Martin
    "13771", //Somkaite
    "14642", //Ossfeld
    "8872",  //Sudermann
    "7924", //Nusser
    "2231", //Keller
    "3629", //Schätzle
    "1367", //Scheurer
    "5995", //Zind
    "8870", //Karcher
    "3353", //Marioth
    "8123", //Krzemien
    "4282", //Grossner
    "8263", // Rüdiger BEcker
    "11827", //Jasper Williams
    "15194", //Bock
    "15038", //Drüen
    "14946", //Patrick Döring
    "1069", //Uwe Walter
    "5178", //Herling
    "8136", //Schmied
    "10446", //Wibke Ziegler
    "7542", //Kraft
    "566", //Kadalla


];

$pilots = [];


foreach ($flights as $flight) {


    $pilotId = $flight->{'FKPilot'};
    if (!in_array($pilotId, $participantDhvXcIds)) {
        continue;
    }

    if (!isset($pilots[$pilotId])) {
        $pilot = new Pilot($pilotId);
        $pilot->fullName = $flight->{'FirstName'} . " " . $flight->{'LastName'};
        $pilots[$pilotId] = $pilot;
    }
    /**
     * @var Pilot $pilots [$pilotId]
     */
    $currentFlight = new Flight($flight->{'IDFlight'});
    $currentFlight->duration = (int)$flight->{'FlightDuration'};
    $currentFlight->landingWaypoint = $flight->{'LandingWaypointName'};
    $currentFlight->calculateAirtimeScore();


    $pilots[$pilotId]->allFlights[] = $currentFlight;
}

foreach ($pilots as $pilot) {
    $pilot->getBestScoreCombo();
}

usort($pilots, function ($a, $b) {
    /**
     * @var Pilot $a
     * @var Pilot $b
     */
    return $b->totalScore - $a->totalScore;

});
$airtimeSymbol = "🕰️";
if (time() % 20 === 0) {
    $airtimeSymbol = "🥱";
}

?><!doctype html>
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
        <th title="Airtime"><?php echo $airtimeSymbol; ?></th>
        <th title="Summe">Σ</th>
    </tr>
    <?php

    $flightUrl = "https://de.dhv-xc.de/flight/";
    $pilotRanking = 0;
    foreach ($pilots as $pilot) {
        $pilotRanking++;
        $displayedRanking = $pilotRanking;
        if ($pilotRanking === 1) {
            $displayedRanking = "🥇";
        } else if ($pilotRanking === 2) {
            $displayedRanking = "🥈";
        } else if ($pilotRanking === 3) {
            $displayedRanking = "🥉";
        }
        $totalScore = round($pilot->totalScore, 2);
        $name = $pilot->fullName;

        $airtimeScore = round($pilot->airtimeScore, 2);
        $triangleScore = round($pilot->triangleScore, 2);
        $distanceScore = round($pilot->freeDistanceScore, 2);

        $distanceUrl = "#";
        $triangleUrl = "#";
        $airtimeUrl = "#";

        if (!empty($pilot->bestDistanceFlightId)) {
            $distanceUrl = $flightUrl . $pilot->bestDistanceFlightId;
        }

        if (!empty($pilot->bestTriangleFlightId)) {
            $triangleUrl = $flightUrl . $pilot->bestTriangleFlightId;
        }

        if (!empty($pilot->bestAirtimeFlightId)) {
            $airtimeUrl = $flightUrl . $pilot->bestAirtimeFlightId;
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

        $allFlightsOfPilotUrl = "https://de.dhv-xc.de/flights?" . FILTER_PARAMS . "&fkpil=" . $pilot->dhvXcId;


        $out = <<<HEREDOC
<tr>
<td>$displayedRanking</td>
<td><a href="$allFlightsOfPilotUrl">$name</a></td>
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
<?php


class Pilot
{
    public function __construct($id)
    {
        $this->dhvXcId = $id;
        $this->allFlights = [];
    }

    public $dhvXcId;
    public $fullName;
    public $allFlights;

    public $airtimeScore = 0;
    public $freeDistanceScore = 0;
    public $triangleScore = 0;

    public $totalScore = 0;

    public $bestAirtimeFlightId = "";
    public $bestDistanceFlightId = "";
    public $bestTriangleFlightId = "";

    public function getBestScoreCombo()
    {

        $appliedScores = [0, 0, 0];
        $scoredDisciplines = ["", "", ""];
        $scoredFlights = ["", "", ""];


        /*
         * -Look at all flights and get the one with the highest score in any discipline --> 1st score
         * -Find highest score of remaining flights, but skip disciplines already used (2x)
         */
        for ($i = 0; $i < 3; $i++) {
            $currentBestFlightId = "";
            $currentBestDiscipline = "";
            $currentBestScore = 0;
            foreach ($this->allFlights as $flight) {
                /**
                 * @var Flight $flight
                 */

                if (in_array($flight->id, $scoredFlights)) {
                    continue;
                }

                foreach (["distancePoints", "trianglePoints", "airtimePoints"] as $discipline) {


                    if (in_array($discipline, $scoredDisciplines)) {
                        continue;
                    }

                    if ($flight->{$discipline} > $currentBestScore) {
                        $currentBestScore = $flight->{$discipline};
                        $currentBestDiscipline = $discipline;
                        $currentBestFlightId = $flight->id;
                    }
                }
            }
            $appliedScores[$i] = $currentBestScore;
            $scoredFlights[$i] = $currentBestFlightId;
            $scoredDisciplines[$i] = $currentBestDiscipline;
        }

        for ($i = 0; $i < 3; $i++) {
            $this->{$scoredDisciplines[$i]} = $appliedScores[$i];
            $this->{$scoredDisciplines[$i] . "Flight"} = $scoredFlights[$i];
        }

        $this->totalScore = array_sum($appliedScores);


    }


}

class Flight
{
    public $id;
    public $duration;

    public $airtimePoints;
    public $trianglePoints;
    public $distancePoints;
    public $landingWaypoint;

    public function __construct($id)
    {
        $arrContextOptions = array(
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
            ),
        );
        $this->id = $id;
        $apiTasksUrl = "https://de.dhv-xc.de/api/fli/tasks?fkflight=$id";
        $apiTasksContent = file_get_contents($apiTasksUrl, false, stream_context_create($arrContextOptions));
        $tasks = json_decode($apiTasksContent);
        foreach ($tasks->{'data'} as $task) {
            switch ($task->{'FKTaskType'}) {
                case "1":
                    $this->distancePoints = (float)$task->{'TaskPoints'};
                    break;

                case "2":
                    $this->trianglePoints = (float)$task->{'TaskPoints'};
                    break;

                case "3":
                    if (!isset ($this->trianglePoints) || (float)$task->{'TaskPoints'} > $this->trianglePoints) {
                        $this->trianglePoints = (float)$task->{'TaskPoints'};
                    }
            }
        }
    }

    public function calculateAirtimeScore()
    {

        if ($this->landingWaypoint !== "Merkur") { //Merkur landings only
            $this->airtimePoints = 0;
            return;
        }

        $seconds = $this->duration;

        if ($seconds >= 8 * 3600) {
            $points = 100;
        } elseif ($seconds >= 7 * 3600) {
            $points = 80;
        } elseif ($seconds >= 6 * 3600) {
            $points = 70;
        } elseif ($seconds >= 5 * 3600) {
            $points = 60;
        } elseif ($seconds >= 4 * 3600) {
            $points = 50;
        } elseif ($seconds >= 3 * 3600) {
            $points = 30;
        } elseif ($seconds >= 2 * 3600) {
            $points = 20;
        } elseif ($seconds >= 3600) {
            $points = 10;
        } elseif ($seconds >= 1800) {
            $points = 5;
        } else {
            $points = 0;
        }

        $this->airtimePoints = $points;

    }


}