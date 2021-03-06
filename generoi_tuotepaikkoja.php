<?php

// Kutsutaanko CLI:st�
$php_cli = php_sapi_name() == 'cli';

if (!$php_cli) {
  echo "T�t� scripti� voi ajaa vain komentorivilt�!";
  exit(1);
}

if (!isset($argv[1]) or $argv[1] == '') {
  echo "Anna yhti�!!!\n";
  exit(1);
}
else {
  $yhtio = $argv[1];
}

// otetaan includepath aina rootista
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(__FILE__));
error_reporting(E_ALL);
ini_set("display_errors", 1);

// otetaan tietokanta connect
require "inc/connect.inc";
require "inc/functions.inc";

// Generoidaan jokaiselle yhti�n tuotteelle tuotepaikka jokaiseen yhti�n varastoon
$query = "SELECT *
          FROM varastopaikat
          WHERE yhtio = '$yhtio'";
$varastoresult = pupe_query($query);

// Kaikki tuotteet
$query = "SELECT tuoteno
          FROM tuote
          WHERE yhtio     = '$yhtio'
          AND ei_saldoa   = ''
          AND tuotetyyppi not in ('A','B')";
$tuoteresult = pupe_query($query);

while ($tuoterow = mysql_fetch_assoc($tuoteresult)) {

  while ($varastorow = mysql_fetch_assoc($varastoresult)) {
    // Onko tuotteella jo paikka t�ss� varastossa
    $query = "SELECT *
              FROM tuotepaikat
              WHERE yhtio = '$yhtio'
              AND tuoteno = '$tuoterow[tuoteno]'
              AND varasto = '$varastorow[tunnus]'";
    $paikkaresult = pupe_query($query);

    if (mysql_num_rows($paikkaresult) == 0) {
      lisaa_tuotepaikka($tuoterow["tuoteno"], $varastorow["alkuhyllyalue"], $varastorow["hyllynro"], '0', '0', 'Lis�ttiin tuotepaikka generoinnissa');
    }
  }

  mysql_data_seek($varastoresult, 0);
}
