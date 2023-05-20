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