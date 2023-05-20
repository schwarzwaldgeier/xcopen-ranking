<?php

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

    public function calcAirtimePoints() {
        $this->airtimePoints = 0;
        $breakpoints = [
            "0.5" => 5,
            "1" => 10,
            "1.5" => 25,
            "2" => 15,
            "2.5" => 35,
            "3" => 50,
            "3.5" => 60,
            "4" => 30,
            "4.5" => 65,
            "5" => 75,
            "5.5" => 45,
            "6" => 85,
            "6.5" => 100,
            "7" => 110,
            "7.5" => 70,
            "8" => 115,
            "8.5" => 130,
        ];

        $seconds = (float)$this->flightDuration;
        foreach ($breakpoints as $breakpoint => $points) {
            $breakpoint = floatval($breakpoint);
            if ($seconds >= $breakpoint * 3600) {
                $this->airtimePoints = $points;
            }
        }
    }
}