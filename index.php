<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$arrContextOptions=array(
    "ssl"=>array(
        "verify_peer"=>false,
        "verify_peer_name"=>false,
    ),
);

$url = "https://de.dhv-xc.de/api/fli/flights?d0=26.05.2022&d1=19.08.2022&fkcat%5B%5D=1&l-fkcat%5B%5D=Gleitschirm&fkto%5B%5D=9543&l-fkto%5B%5D=Merkur%20DE&navpars=%7B%22start%22%3A0%2C%22limit%22%3A500%2C%22sort%22%3A%5B%7B%22field%22%3A%22BestTaskPoints%22%2C%22dir%22%3A1%7D%2C%7B%22field%22%3A%22BestTaskSpeed%22%2C%22dir%22%3A1%7D%5D%7D";
$response = file_get_contents($url, false, stream_context_create($arrContextOptions));

$responseJson = json_decode($response);
$flights = $responseJson->data;

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
    $thisFlight->duration = (int)$flight->{'FlightDuration'};
    $thisFlight->landing = $flight->{'LandingWaypointName'};
    $thisFlight->calcDurationPoints();


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
    return $b->totalPoints - $a->totalPoints;

});
?>
    <!doctype html>

    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="style.css">
        <title>XC open</title>


    </head>

    <body>
    <h2>XC Open 2022 Live Ranking</h2>
    <table>
        <tr>
            <th>#</th>
            <th>Name</th>
            <th>Freie Strecke</th>
            <th>Dreieck</th>
            <th>Airtime</th>
            <th>Gesamt</th>
        </tr>
        <?php

        $rank = 0;
        foreach ($pilots as $pilot) {
            $rank++;
            $total = $pilot->totalPoints;
            $name = $pilot->name;
            $duration = $pilot->durationPoints;
            $triangle = $pilot->trianglePoints;
            $distance = $pilot->distancePoints;

            $out = <<<HEREDOC
<tr>
<td>$rank</td>
<td>$name</td>
<td>$distance</td>
<td>$triangle</td>
<td>$duration</td>
<td>$total</td>
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

    public  $id;
    public  $name;
    public  $flights;

    public  $durationPoints = 0;
    public  $distancePoints = 0;
    public  $trianglePoints = 0;

    public  $totalPoints = 0;

    public  $durationPointsFlight = "";
    public  $distancePointsFlight = "";
    public  $trianglePointsFlight = "";

    public function getBestPoints()
    {

        $usedPoints = [0, 0, 0];
        $usedDisciplines = ["", "", ""];
        $usedFlights = ["", "", ""];


        for ($i = 0; $i < 3; $i++) {
            foreach ($this->flights as $flight) {
                /**
                 * @var Flight $flight
                 */

                if (in_array($flight->id, $usedFlights)) {
                    continue;
                }

                foreach (["durationPoints", "distancePoints", "trianglePoints"] as $discipline) {


                    if (in_array($discipline, $usedDisciplines)) {
                        continue;
                    }



                    if ($flight->{$discipline} > $usedPoints[$i]) {
                        $usedPoints[$i] = $flight->{$discipline};
                        $usedDisciplines[$i] = $discipline;
                        $usedFlights[$i] = $flight->id;
                    }
                }
            }
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
    public  $id;
    public  $duration;

    public  $durationPoints;
    public  $trianglePoints;
    public  $distancePoints;
    public $landing;

    public function __construct($id)
    {
        $arrContextOptions=array(
            "ssl"=>array(
                "verify_peer"=>false,
                "verify_peer_name"=>false,
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

    public function calcDurationPoints()
    {

        if ($this->landing !== "Merkur"){ //Merkur landings only
            $this->durationPoints = 0;
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

        $this->durationPoints = $points;

    }


}