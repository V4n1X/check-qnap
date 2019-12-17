<?php
/* 
QNAP-NAS Plugin/Check for Nagios/Icinga2

Version: master
V4n1X (C)2019

 */
$host = $argv[1];
$community = "public";

$critical = false;
$warning = false;

$output = "";

try {

$modelName = @snmpget($host, $community, ".1.3.6.1.4.1.24681.1.2.12.0");
$modelName = str_replace(array("STRING: ", "\"", "\r", "\n"), '', $modelName);

if(!$modelName) {
    fwrite(STDOUT, "Verbindung zur QNAP-SNMP Schnittstelle konnte nicht hergestellt werden.");
  	exit(2);
}

$hostname = snmpget($host, $community, ".1.3.6.1.4.1.24681.1.2.13.0");
$hostname = str_replace(array("STRING: ", "\"", "\r", "\n"), '', $hostname);

/* System-Temperatur */

$systemTemperature = snmpget($host, $community, ".1.3.6.1.4.1.24681.1.2.6.0");
$systemTemperature = str_replace("STRING: ", "", $systemTemperature);
$systemTemperature = explode(' C/', $systemTemperature);
$systemTemperature = $systemTemperature[0];

if($systemTemperature > 45) {
  $critical = true;
  $output .= "Temperatur: " . $systemTemperature . "Â°C" . " - ";
}

/* Festplatten-Status */

$diskIndex = snmpget($host, $community, "iso.3.6.1.4.1.24681.1.4.1.1.1.1.5.1.0");
$diskIndex = str_replace("INTEGER: ", "", $diskIndex);

for($i = 1; $i <= $diskIndex; $i++) {

   $diskStatus = snmpget($host, $community, "iso.3.6.1.4.1.24681.1.4.1.1.1.1.5.2.1.4." . $i);
   $diskStatus = str_replace(array("STRING: ", "\"", "\r", "\n"), '', $diskStatus);

   if($diskStatus != "Good") {
     $critical = true;
     $output .= "Festplatte (" . $i . "): " . $diskStatus . " - ";
   }
}

/* RAID-Status */

$raidIndex = snmpget($host, $community, "iso.3.6.1.4.1.24681.1.4.1.1.1.2.1.1.0");
$raidIndex = str_replace("INTEGER: ", "", $raidIndex);

for($i = 1; $i <= $raidIndex; $i++) {

   $raidStatus = snmpget($host, $community, "iso.3.6.1.4.1.24681.1.4.1.1.1.2.1.2.1.5." . $i);
   $raidStatus = str_replace(array("STRING: ", "\"", "\r", "\n"), '', $raidStatus);

   if($raidStatus != "Ready" && $raidStatus != "Synchronizing") {
     $critical = true;
     $output .= "RAID (" . $i . "): " . $raidStatus . " - ";
   }
}

/* Volume-Status */

$volumeIndex = snmpget($host, $community, "iso.3.6.1.4.1.24681.1.4.1.1.1.2.3.1.0");
$volumeIndex = str_replace("INTEGER: ", "", $volumeIndex);

for($i = 1; $i <= $raidIndex; $i++) {

   $volumeStatus = snmpget($host, $community, "iso.3.6.1.4.1.24681.1.4.1.1.1.2.3.2.1.5." . $i);
   $volumeStatus = str_replace(array("STRING: ", "\"", "\r", "\n"), '', $volumeStatus);

   $volumeName = snmpget($host, $community, "iso.3.6.1.4.1.24681.1.4.1.1.1.2.3.2.1.8." . $i);
   $volumeName = str_replace(array("STRING: ", "\"", "\r", "\n"), '', $volumeName);

   if($volumeStatus != "Ready") {
     $critical = true;
     $output .= "VOLUME (" . $volumeName . "): " . $volumeStatus . " - ";
   }
}

$output = rtrim($output, " - ");

if($critical) {
  fwrite(STDOUT, $output);
	exit(2);
}

if($warning) {
  fwrite(STDOUT, $output);
	exit(1);
}

fwrite(STDOUT, "Modell: " . $modelName . " - Hostname: " . $hostname);
exit(0);

} catch (Exception $e) {
  fwrite(STDOUT, "Verbindung zur QNAP-SNMP Schnittstelle konnte nicht hergestellt werden.");
	exit(2);
}


function getBatteryStatus($code) {

  $status = "";

  switch ($code) {
    case 1:
    $status = "Unbekannt";
    break;

  }

  return $status;

}

?>
