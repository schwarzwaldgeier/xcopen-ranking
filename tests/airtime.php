<?php
require_once __DIR__ . "/../Flight.php";

$mockFlight = new Flight(1);
$mockFlight->flightDuration = 3600*2;
$mockFlight->calcAirtimePoints();
echo $mockFlight->airtimePoints;