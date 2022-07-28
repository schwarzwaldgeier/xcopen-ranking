<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$participantIds = [
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

$filterAllPilots = "d0=26.05.2022&d1=19.08.2022&fkcat%5B%5D=1&l-fkcat%5B%5D=Gleitschirm&fkto%5B%5D=9543&l-fkto%5B%5D=Merkur%20DE&navpars=%7B%22start%22%3A0%2C%22limit%22%3A500%2C%22sort%22%3A%5B%7B%22field%22%3A%22BestTaskPoints%22%2C%22dir%22%3A1%7D%2C%7B%22field%22%3A%22BestTaskSpeed%22%2C%22dir%22%3A1%7D%5D%7D";
$filterParticipants = $filterAllPilots;

foreach ($participantIds as $pid){
    $filterParticipants .= "&fkpil%5B%5D=$pid";
}


$arrContextOptions = array(
    "ssl" => array(
        "verify_peer" => false,
        "verify_peer_name" => false,
    ),
);

$url = "https://de.dhv-xc.de/api/fli/flights?" . $filterParticipants;

$response = file_get_contents($url, false, stream_context_create($arrContextOptions));;

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
    $thisFlight->landing = $flight->{'LandingWaypointName'};
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
    if ($diff == 0.0){
        return 0;
    }

    else if ($diff > 0){
        return 1;
    }

    else {
        return -1;
    }

});

$pilots = array_reverse($pilots);

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
<?php


class Pilot
{
    public function __construct($id)
    {
        $this->id = $id;
        $this->flights = [];
    }

    public $id;
    public $name;
    public $flights;

    public $airtimePoints = 0;
    public $distancePoints = 0;
    public $trianglePoints = 0;

    public $totalPoints = 0;

    public $airtimePointsFlight = "";
    public $distancePointsFlight = "";
    public $trianglePointsFlight = "";

    public function getBestPoints()
    {

        $usedPoints = [0, 0, 0];
        $usedDisciplines = ["", "", ""];
        $usedFlights = ["", "", ""];


        for ($i = 0; $i < 3; $i++) {
            $usedFlight = "";
            $usedDiscipline = "";
            $usedPoint = 0;
            foreach ($this->flights as $flight) {
                /**
                 * @var Flight $flight
                 */

                if (in_array($flight->id, $usedFlights)) {
                    continue;
                }

                foreach (["distancePoints", "trianglePoints", "airtimePoints"] as $discipline) {


                    if (in_array($discipline, $usedDisciplines)) {
                        continue;
                    }

                    if ($flight->{$discipline} > $usedPoint) {
                        $usedPoint = $flight->{$discipline};
                        $usedDiscipline = $discipline;
                        $usedFlight = $flight->id;
                    }
                }
            }
            $usedPoints[$i] = $usedPoint;
            $usedFlights[$i] = $usedFlight;
            $usedDisciplines[$i] = $usedDiscipline;
        }

        for ($i = 0; $i < 3; $i++) {
            $this->{$usedDisciplines[$i]} = $usedPoints[$i];
            $this->{$usedDisciplines[$i] . "Flight"} = $usedFlights[$i];
        }

        $this->totalPoints = array_sum($usedPoints);


    }


}

class Flight
{
    public $id;
    public $flightDuration;

    public $airtimePoints;
    public $trianglePoints;
    public $distancePoints;
    public $landing;

    public function __construct($id)
    {
        $arrContextOptions = array(
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
            ),
        );
        $this->id = $id;
        $url = "https://de.dhv-xc.de/api/fli/tasks?fkflight=$id";
        $content = file_get_contents($url, false, stream_context_create($arrContextOptions));
        $contentJson = json_decode($content);
        foreach ($contentJson->{'data'} as $task) {
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

    public function calcAirtimePoints()
    {

        // Airtime is scored for Merkur landings only
        if ($this->landing !== "Merkur" && $this->landing !== "Baden Baden" && $this->landing !== "Baden-Baden") {
            $this->airtimePoints = 0;
            return;
        }

        $s = $this->flightDuration;

        $h = 3600;

        //5 points for 30 minutes
        if ($s >= 1800 && $s < $h) {
            $this->airtimePoints = 5;
            return;
        }

        //10 points per every completed hour
        $this->airtimePoints = floor($s / $h) * 10;

        //10 points extra if 4 hours or more
        if ($s >= 4 * $h){
            $this->airtimePoints += 10;
        }

        //Additional 10 extra for 8 hours
        if ($s >= 8 * $h){
            $this->airtimePoints += 10;
        }

        //score limit
        if ($this->airtimePoints > 100){
            $this->airtimePoints = 100;
        }
    }


}