<?php
require_once "Debug.php";

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

    public $airtimePointsScore = 0;
    public $distancePointsScore = 0;
    public $trianglePointsScore = 0;

    public $totalPoints = 0;

    public $airtimePointsFlight = "";
    public $distancePointsFlight = "";
    public $trianglePointsFlight = "";

    private function isFlightUsed($flight, $combination)
    {
        if($flight['id'] === 0){
            return false;
        }
        foreach ($combination as $discipline => $usedFlight) {
            if ($usedFlight['id'] === $flight['id']) {
                return true;
            }
        }
        return false;
    }

    private function generateFlightCombinations($airtimeFlights, $distanceFlights, $triangleFlights): array
    {
        $combinations = [];

        foreach ($airtimeFlights as $airtimeFlight) {
            foreach ($distanceFlights as $distanceFlight) {
                foreach ($triangleFlights as $triangleFlight) {
                    // Check if any flight is used in multiple disciplines
                    if ($this->isFlightUsed($airtimeFlight, [$distanceFlight, $triangleFlight]) ||
                        $this->isFlightUsed($distanceFlight, [$airtimeFlight, $triangleFlight]) ||
                        $this->isFlightUsed($triangleFlight, [$airtimeFlight, $distanceFlight])) {
                        continue;
                    }

                    $combination = [
                        'airtime' => $airtimeFlight,
                        'distance' => $distanceFlight,
                        'triangle' => $triangleFlight
                    ];
                    $combinations[] = $combination;
                }
            }
        }

        return $combinations;
    }

    private function calculateTotalScore($combination)
    {
        $totalScore = 0;
        foreach ($combination as $discipline => $flight) {
            $totalScore += $flight['points'];
        }
        return $totalScore;
    }

    public function getBestPoints()
    {
        $emptyFlight = ["id" => 0, "points" => 0];
        $airtimeFlights = [
            $emptyFlight,
        ];
        $distanceFlights = [
            $emptyFlight,
        ];
        $triangleFlights = [
            $emptyFlight,
        ];

        foreach ($this->flights as $flight) {
            /**
             * @var Flight $flight
             */
            Debug::log("Checking flight $flight->id");
            Debug::log("Airtime: $flight->airtimePoints");
            Debug::log("Distance: $flight->distancePoints");
            Debug::log("Triangle: $flight->trianglePoints");


            $airtimeFlights[] = ["id" => $flight->id, "points" => $flight->airtimePoints];
            $distanceFlights[] = ["id" => $flight->id, "points" => $flight->distancePoints];
            $triangleFlights[] = ["id" => $flight->id, "points" => $flight->trianglePoints];
        }


        $flightCombinations = $this->generateFlightCombinations($airtimeFlights, $distanceFlights, $triangleFlights);
        $maxScore = 0;
        $bestCombination = null;
        foreach ($flightCombinations as $combination) {
            $totalScore = $this->calculateTotalScore($combination);
            if ($totalScore > $maxScore) {
                $maxScore = $totalScore;
                $bestCombination = $combination;
            }
        }

        $bestAirtimeFlight = $bestCombination['airtime'] ?? 0;
        $bestDistanceFlight = $bestCombination['distance'] ?? 0;
        $bestTriangleFlight = $bestCombination['triangle'] ?? 0;


        $this->airtimePointsFlight = $bestAirtimeFlight['id'] ?? "";
        $this->distancePointsFlight = $bestDistanceFlight['id'] ?? "";
        $this->trianglePointsFlight = $bestTriangleFlight['id'] ?? "";

        $this->airtimePointsScore =  $bestAirtimeFlight['points'] ?? 0;
        $this->distancePointsScore = $bestDistanceFlight['points'] ?? 0;
        $this->trianglePointsScore = $bestTriangleFlight['points'] ?? 0;


        $this->totalPoints = $this->airtimePointsScore + $this->distancePointsScore + $this->trianglePointsScore;
    }
}