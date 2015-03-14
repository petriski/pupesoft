<?php

///* Tämä skripti käyttää slave-tietokantapalvelinta *///
$useslave = 1;

if (@include "../inc/parametrit.inc");
elseif (@include "parametrit.inc");
else exit;

/**
 * Seuraavat kaksi if-lausetta liittyvät poistetut valintaan tuotteita
 * hakiessa.
 */
if (!isset($poistetut)) {
  $poistetut = '';
}

if ($poistetut != "") {

  $poischeck = "CHECKED";
  $ulisa .= "&poistetut=checked";

  if ($kukarow["extranet"] != "" or $verkkokauppa != "") {
    // Näytetään vain poistettuja tuotteita
    $poislisa        = " AND tuote.status in ('P','X')
                  AND (SELECT sum(saldo)
                  FROM tuotepaikat
                  JOIN varastopaikat ON (varastopaikat.yhtio=tuotepaikat.yhtio
                  AND varastopaikat.tunnus = tuotepaikat.varasto
                  AND varastopaikat.tyyppi = '')
                  WHERE tuotepaikat.yhtio=tuote.yhtio
                  AND tuotepaikat.tuoteno=tuote.tuoteno
                  AND tuotepaikat.saldo > 0) > 0 ";
    if (($yhtiorow["yhtio"] == 'allr')) {
      $hinta_rajaus = " AND tuote.myymalahinta > tuote.myyntihinta ";
    }
    else {
      $hinta_rajaus = " ";
    }
    $poislisa_mulsel = " and tuote.status in ('P','X') ";
  }
  else {
    $poislisa = "";
    //$poislisa_mulsel  = "";
  }
}
else {
  $poislisa = " and (tuote.status not in ('P','X')
          or (SELECT sum(saldo)
              FROM tuotepaikat
              WHERE tuotepaikat.yhtio=tuote.yhtio
              AND tuotepaikat.tuoteno=tuote.tuoteno
              AND tuotepaikat.saldo > 0) > 0) ";
  //$poislisa_mulsel  = " and tuote.status not in ('P','X') ";
  $poischeck = "";
}

/**
 * Seuraavilla riveillä valitaan järjestys hakutuloksille.
 */
$jarjestys = "tuote.tuoteno";

$lisa            = "";
$ulisa           = "";
$toimtuotteet    = "";
$origtuotteet    = "";
$poislisa_mulsel = "";
$lisa_parametri  = "";
$hinta_rajaus    = "";

if (!isset($ojarj)) {
  $ojarj = '';
}

if (strlen($ojarj) > 0) {
  $ojarj = trim(mysql_real_escape_string($ojarj));

  if ($ojarj == 'tuoteno') {
    $jarjestys = 'tuote.tuoteno';
  }
  elseif ($ojarj == 'toim_tuoteno') {
    $jarjestys = 'tuote.tuoteno';
  }
  elseif ($ojarj == 'nimitys') {
    $jarjestys = 'tuote.nimitys';
  }
  elseif ($ojarj == 'osasto') {
    $jarjestys = 'tuote.osasto';
  }
  elseif ($ojarj == 'try') {
    $jarjestys = 'tuote.try';
  }
  elseif ($ojarj == 'hinta') {
    $jarjestys = 'tuote.myyntihinta';
  }
  elseif ($ojarj == 'nettohinta') {
    $jarjestys = 'tuote.nettohinta';
  }
  elseif ($ojarj == 'aleryhma') {
    $jarjestys = 'tuote.aleryhma';
  }
  elseif ($ojarj == 'status') {
    $jarjestys = 'tuote.status';
  }
  else {
    $jarjestys = 'tuote.tuoteno';
  }
}

/**
 * Seuraavat kaksi if-lausetta liittyvät "Piilota tuoteperheen lapset"
 * -valintaan tuotehaussa.
 */
if (!isset($piilota_tuoteperheen_lapset)) { 
    $piilota_tuoteperheen_lapset = ''; 
}
if ($piilota_tuoteperheen_lapset != '') {
  $ptlcheck = "CHECKED";
  $ulisa .= "&piilota_tuoteperheen_lapset=checked";
}
else {
  $ptlcheck = "";
}


/**
 * Seuraavat kaksi if-lausetta liittyvät "Näytä vain saldolliset tuotteet"
 * -valintaan tuotehaussa.
 */
if (!isset($saldotonrajaus)) { 
    $saldotonrajaus = '';
}
if ($saldotonrajaus != '') {
  $saldotoncheck = "CHECKED";
  $ulisa .= "&saldotonrajaus=checked";
}
else {
  $saldotoncheck = "";
}

/**
 * Seuraavat kaksi if-lausetta liittyvät "Lisätiedot"
 * -valintaan tuotehaussa.
 */
if (!isset($lisatiedot)) {
  $lisatiedot = '';
}
if ($lisatiedot != "") {
  $lisacheck = "CHECKED";
  $ulisa .= "&lisatiedot=checked";
}
else {
  $lisacheck = "";
}

/**
 * Seuraavat kaksi if-lausetta liittyvät "Nimitys"-hakuehtoon.
 */
if (!isset($nimitys)) {
  $nimitys = '';
}
if (trim($nimitys) != '') {
  $nimitys = mysql_real_escape_string(trim($nimitys));
  $lisa .= " and tuote.nimitys like '%$nimitys%' ";
  $ulisa .= "&nimitys=$nimitys";
}

/**
 * Seuraavat kaksi if-lausetta liittyvät "Tuotenumero"-hakuehtoon.
 */
if (!isset($tuotenumero)) {
  $tuotenumero = '';
}
if (trim($tuotenumero) != '') {
  $tuotenumero = mysql_real_escape_string(trim($tuotenumero));

  if (isset($alkukoodilla) and $alkukoodilla != "") {
    $lisa .= " and tuote.tuoteno like '$tuotenumero%' ";
  }
  else {
    $lisa .= " and tuote.tuoteno like '%$tuotenumero%' ";
  }
  $ulisa .= "&tuotenumero=$tuotenumero";
}

/**
 * Seuraavat kaksi if-lausetta liittyvät "Toimittajan tuotenumero"-hakuehtoon.
 */
if (!isset($toim_tuoteno)) {
  $toim_tuoteno = '';
}
if (trim($toim_tuoteno) != '') {
  $toim_tuoteno = mysql_real_escape_string(trim($toim_tuoteno));

  // Katsotaan löytyykö tuotenumero toimittajan vaihtoehtoisista tuotenumeroista
  $query = "SELECT GROUP_CONCAT(DISTINCT toim_tuoteno_tunnus SEPARATOR ',') toim_tuoteno_tunnukset
            FROM tuotteen_toimittajat_tuotenumerot
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tuoteno = '{$toim_tuoteno}'";
  $vaih_tuoteno_res = pupe_query($query);
  $vaih_tuoteno_row = mysql_fetch_assoc($vaih_tuoteno_res);

  $vaihtoehtoinen_tuoteno_lisa = $vaih_tuoteno_row['toim_tuoteno_tunnukset'] != '' ? " OR tunnus IN ('{$vaih_tuoteno_row['toim_tuoteno_tunnukset']}')" : "";

  //Otetaan konserniyhtiöt hanskaan
  $query  = "SELECT DISTINCT tuoteno
             FROM tuotteen_toimittajat
             WHERE yhtio = '{$kukarow['yhtio']}'
             AND (toim_tuoteno LIKE '%{$toim_tuoteno}%' $vaihtoehtoinen_tuoteno_lisa)
             LIMIT 500";
  $pres = pupe_query($query);

  while ($prow = mysql_fetch_assoc($pres)) {
    $toimtuotteet .= "'".$prow["tuoteno"]."',";
  }

  $toimtuotteet = substr($toimtuotteet, 0, -1);

  if ($toimtuotteet != "") {
    $lisa .= " and tuote.tuoteno in ($toimtuotteet) ";
  }

  $ulisa .= "&toim_tuoteno=$toim_tuoteno";
}

/**
 * REFACTOR: Alkuperäisnumero? Mihin liittyy.
 */
if (!isset($alkuperaisnumero)) {
  $alkuperaisnumero = '';
}

if (trim($alkuperaisnumero) != '') {
  $alkuperaisnumero = mysql_real_escape_string(trim($alkuperaisnumero));

  $query  = "SELECT distinct tuoteno
             FROM tuotteen_orginaalit
             WHERE yhtio      = '$kukarow[yhtio]'
             AND orig_tuoteno like '$alkuperaisnumero%'
             LIMIT 500";
  $pres = pupe_query($query);

  while ($prow = mysql_fetch_assoc($pres)) {
    $origtuotteet .= "'".$prow["tuoteno"]."',";
  }

  $origtuotteet = substr($origtuotteet, 0, -1);

  if ($origtuotteet != "") {
    $lisa .= " and tuote.tuoteno in ($origtuotteet) ";
  }

  $ulisa .= "&alkuperaisnumero=$alkuperaisnumero";
}

/**
 * REFACTOR: Originaalit? Mihin liittyy.
 */
$orginaaalit = FALSE;
if (table_exists("tuotteen_orginaalit")) {
  $query  = "SELECT tunnus
             FROM tuotteen_orginaalit
             WHERE yhtio = '$kukarow[yhtio]'
             LIMIT 1";
  $orginaaleja_res = pupe_query($query);

  if (mysql_num_rows($orginaaleja_res) > 0) {
    $orginaaalit = TRUE;
  }
}

/**
 * REFACTOR: Mikä kumma on verkkokauppa?
 * 
 * Seuraava if-lohko tulostaa lomakkeen hakukyselyn tekemistä varten.
 */
if ($verkkokauppa == "") {

  if ($hae_ja_selaa_row['selite'] == 'B') {
    echo "<div>";
  }

  echo "<form action = '?toim_kutsu=$toim_kutsu' method = 'post'>";
  echo "<input type='hidden' name='tilausnumero' value='$kukarow[kesken]'>";
  echo "<input type='hidden' name='ostoskori' value='$ostoskori'>";
  echo "<input type='hidden' name='valittu_tarjous_tunnus' value='$valittu_tarjous_tunnus'>";

  if (!isset($tultiin)) {
    $tultiin = '';
  }

  if ($tultiin == "futur") {
    echo " <input type='hidden' name='tultiin' value='$tultiin'>";
  }

  echo "<input type='hidden' name='ostoskori' value='$ostoskori'>";

  if ($hae_ja_selaa_row['selite'] == 'B') {
    echo "<fieldset>";
    echo "<legend>", t("Pikahaku"), "</legend>";
  }

  echo "<table style='display:inline-table; padding-right:4px; padding-top:4px;' valign='top'>";

  if ($hae_ja_selaa_row['selite'] == 'B') {
    echo "<tr><th>".t("Tuotenumero")."</th><td><input type='text' size='25' name='tuotenumero' id='tuotenumero' value = '$tuotenumero'></td>";
    echo "<th>".t("Toim tuoteno")."</th><td><input type='text' size='25' name = 'toim_tuoteno' id='toim_tuoteno' value = '$toim_tuoteno'></td>";

    if ($kukarow["extranet"] != "") {
      echo "<th>".t("Tarjoustuotteet")."</th>";
    }
    else {
      echo "<th>".t("Poistetut")."</th>";
    }
    echo "<td><input type='checkbox' name='poistetut' id='poistetut' $poischeck></td>";

    if ($kukarow["extranet"] != "" and $kukarow['asema'] == "NE") {
      echo "<th>".t("Näytä poistetut")."</th><td><input type='checkbox' name='extrapoistetut' id='extrapoistetut' $extrapoischeck></td>";
    }

    echo "</tr>";

    echo "<tr><th>".t("Nimitys")."</th><td><input type='text' size='25' name='nimitys' id='nimitys' value = '$nimitys'></td>";

    if ($orginaaalit) {
      echo "<th>".t("Alkuperäisnumero")."</th><td><input type='text' size='25' name = 'alkuperaisnumero' id='alkuperaisnumero' value = '$alkuperaisnumero'></td>";
    }
    else {
      echo "<th>&nbsp;</th><td>&nbsp;</td>";
    }

    echo "<th>".t("Lisätiedot")."</th><td><input type='checkbox' name='lisatiedot' id='lisatiedot' $lisacheck></td>";
    echo "</tr>";
  }
  else {
    echo "<tr><th>".t("Tuotenumero")."</th><td><input type='text' size='25' name='tuotenumero' id='tuotenumero' value = '$tuotenumero'></td></tr>";
    echo "<tr><th>".t("Toim tuoteno")."</th><td><input type='text' size='25' name = 'toim_tuoteno' id='toim_tuoteno' value = '$toim_tuoteno'></td></tr>";

    if ($orginaaalit) {
      echo "<tr><th>".t("Alkuperäisnumero")."</th><td><input type='text' size='25' name = 'alkuperaisnumero' id='alkuperaisnumero' value = '$alkuperaisnumero'></td></tr>";
    }

    echo "<tr><th>".t("Nimitys")."</th><td><input type='text' size='25' name='nimitys' id='nimitys' value = '$nimitys'></td></tr>";
    if ($kukarow["extranet"] != "") {
      echo "<tr><th>".t("Tarjoustuotteet")."</th>";
    }
    else {
      echo "<tr><th>".t("Poistetut")."</th>";
    }
    echo "<td><input type='checkbox' name='poistetut' id='poistetut' $poischeck></td></tr>";
    echo "<tr><th>".t("Lisätiedot")."</th><td><input type='checkbox' name='lisatiedot' id='lisatiedot' $lisacheck></td></tr>";
  }

  if ($kukarow['extranet'] == "" and $verkkokauppa == "") {
    echo "<tr>";
    echo "<th>".t("Piilota tuoteperherakenne")."</th>";
    echo "<td><input type='checkbox' name='piilota_tuoteperheen_lapset' $ptlcheck></td>";
    echo "</tr>";
    echo "<tr>";
    echo "<th>".t("Näytä vain saldolliset tuotteet")."</th>";
    echo "<td><input type='checkbox' name='saldotonrajaus' $saldotoncheck></td>";
    echo "</tr>";
  }

  echo "</table><br/>";

  if ($hae_ja_selaa_row['selite'] == 'B') {
    echo "</fieldset>";

    echo "<fieldset>";
    echo "<legend>", t("Rajaa tuotteita"), "</legend>";
    echo "<span class='info'>", t("Aloita valitsemalla osasto / tuoteryhmä"), "</span>";
  }

  echo "<br/>";

  // Monivalintalaatikot (osasto, try tuotemerkki...)
  // Määritellään mitkä latikot halutaan mukaan
  if (trim($hae_ja_selaa_row['selitetark']) != '') {
    $monivalintalaatikot = explode(",", $hae_ja_selaa_row['selitetark']);

    if (trim($hae_ja_selaa_row['selitetark_2'] != '')) {
      $monivalintalaatikot_normaali = explode(",", $hae_ja_selaa_row['selitetark_2']);
    }
    else {
      $monivalintalaatikot_normaali = array();
    }
  }
  else {
    // Oletus
    $monivalintalaatikot = array("OSASTO", "TRY", "TUOTEMERKKI", "MALLI", "MALLI/MALLITARK", "<br>DYNAAMINEN_TUOTE");
    $monivalintalaatikot_normaali = array();
  }

  require "monivalintalaatikot.inc";

  if ($hae_ja_selaa_row['selite'] == 'B') {
    echo "</fieldset>";
  }

  echo "<input type='submit' name='submit_button' id='submit_button' class='hae_btn' value = '".t("Etsi")."'></form>";
  echo "&nbsp;<form action = '?toim_kutsu=$toim_kutsu' method = 'post'>
      <input type='hidden' name='tilausnumero' value='$kukarow[kesken]'>
      <input type='hidden' name='valittu_tarjous_tunnus' value='$valittu_tarjous_tunnus'>
      <input type='submit' name='submit_button2' id='submit_button2' value = '".t("Tyhjennä")."'>
      </form>";

  if ($hae_ja_selaa_row['selite'] == 'B') {
    echo "</div>";
  }
}

/**
 * REFACTOR: Mikä kumma on verkkokauppa?
 */
if ($verkkokauppa != "") {
  if ($osasto != "") {
    $lisa .= "and tuote.osasto = '$osasto' ";
    $ulisa .= "&osasto=$osasto";
  }
  if ($try != "") {
    $lisa .= "and tuote.try = '$try' ";
    $ulisa .= "&try=$try";
  }
  if ($tuotemerkki != "") {
    $lisa .= "and tuote.tuotemerkki = '$tuotemerkki' ";
    $ulisa .= "&tuotemerkki=$tuotemerkki";
  }
}

$yhtiot = hae_yhtiot();

if (isset($sort) and $sort != '') {
  $sort = trim(mysql_real_escape_string($sort));
}

if (!isset($sort)) {
  $sort = '';
}

if ($sort == 'asc') {
  $sort = 'desc';
  $edsort = 'asc';
}
else {
  $sort = 'asc';
  $edsort = 'desc';
}

if (!isset($submit_button)) {
  $submit_button = '';
}

/**
 * Seuraava if lähettää hakukyselyn tietokantaan.
 * 
 * Lisäksi if-lohkon sisällä käsitellään tuotteiden tulostus omassa
 * if-lohkossa. Jos tuotteita ei löydy yhtään, tulostetaan siitä ilmoitus.
 */
if ($submit_button != '' and ($lisa != '' or $lisa_parametri != '')) {

  $tuotekyslinkki = "";

  if ($kukarow["extranet"] == "") {
    $query = "SELECT tunnus from oikeu where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]' and nimi='tuote.php' LIMIT 1";
    $tarkres = pupe_query($query);

    if (mysql_num_rows($tarkres) > 0) {
      $tuotekyslinkki = "tuote.php";
    }
    else {
      $query = "SELECT tunnus from oikeu where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]' and nimi='tuvar.php' LIMIT 1";
      $tarkres = pupe_query($query);

      if (mysql_num_rows($tarkres) > 0) {
        $tuotekyslinkki = "tuvar.php";
      }
      else {
        $tuotekyslinkki = "";
      }
    }
  }

  if (!function_exists("tuoteselaushaku_vastaavat_korvaavat")) {
    function tuoteselaushaku_vastaavat_korvaavat($tvk_taulu, $tvk_korvaavat, $tvk_tuoteno) {
      global $kukarow, $kieltolisa, $poislisa, $hinta_rajaus, $extra_poislisa;

      if ($tvk_taulu != "vastaavat") $kyselylisa = " and {$tvk_taulu}.tuoteno != '$tvk_tuoteno' ";
      else $kyselylisa = "";

      $query = "SELECT
                '' tuoteperhe,
                {$tvk_taulu}.id {$tvk_taulu},
                tuote.tuoteno,
                tuote.nimitys,
                tuote.osasto,
                tuote.try,
                tuote.myyntihinta,
                tuote.myymalahinta,
                tuote.nettohinta,
                tuote.aleryhma,
                tuote.status,
                tuote.ei_saldoa,
                tuote.yksikko,
                tuote.tunnus,
                tuote.epakurantti25pvm,
                tuote.epakurantti50pvm,
                tuote.epakurantti75pvm,
                tuote.epakurantti100pvm,
                (SELECT group_concat(distinct tuotteen_toimittajat.toim_tuoteno ORDER BY tuotteen_toimittajat.tunnus separator '<br>') FROM tuotteen_toimittajat use index (yhtio_tuoteno) WHERE tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno) toim_tuoteno,
                tuote.sarjanumeroseuranta
                FROM {$tvk_taulu}
                JOIN tuote ON (tuote.yhtio={$tvk_taulu}.yhtio and tuote.tuoteno={$tvk_taulu}.tuoteno $hinta_rajaus)
                WHERE {$tvk_taulu}.yhtio = '$kukarow[yhtio]'
                and {$tvk_taulu}.id = '$tvk_korvaavat'
                $kyselylisa
                $kieltolisa
                $poislisa
                $extra_poislisa
                ORDER BY if({$tvk_taulu}.jarjestys=0, 9999, {$tvk_taulu}.jarjestys), {$tvk_taulu}.tuoteno";
      $kores = pupe_query($query);

      return $kores;
    }
  }

  if (!function_exists("tuoteselaushaku_tuoteperhe")) {
    function tuoteselaushaku_tuoteperhe($esiisatuoteno, $tuoteno, $isat_array, $kaikki_array, $rows, $tyyppi = "P") {
      global $kukarow, $kieltolisa, $poislisa, $hinta_rajaus, $extra_poislisa;

      if (!in_array($tuoteno, $isat_array)) {
        $isat_array[] = $tuoteno;

        $query = "SELECT
                  '$esiisatuoteno' tuoteperhe,
                  tuote.tuoteno korvaavat,
                  tuote.tuoteno vastaavat,
                  tuote.tuoteno,
                  tuote.nimitys,
                  tuote.osasto,
                  tuote.try,
                  tuote.myyntihinta,
                  tuote.myymalahinta,
                  tuote.nettohinta,
                  tuote.aleryhma,
                  tuote.status,
                  tuote.ei_saldoa,
                  tuote.yksikko,
                  tuote.tunnus,
                  tuote.epakurantti25pvm,
                  tuote.epakurantti50pvm,
                  tuote.epakurantti75pvm,
                  tuote.epakurantti100pvm,
                  (SELECT group_concat(distinct tuotteen_toimittajat.toim_tuoteno order by tuotteen_toimittajat.tunnus separator '<br>') FROM tuotteen_toimittajat use index (yhtio_tuoteno) WHERE tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno) toim_tuoteno,
                  tuote.sarjanumeroseuranta,
                  tuoteperhe.tyyppi
                  FROM tuoteperhe
                  JOIN tuote ON (tuote.yhtio = tuoteperhe.yhtio and tuote.tuoteno = tuoteperhe.tuoteno $hinta_rajaus)
                  WHERE tuoteperhe.yhtio    = '$kukarow[yhtio]'
                  and tuoteperhe.isatuoteno = '$tuoteno'
                  AND tuoteperhe.tyyppi     = '$tyyppi'
                  $kieltolisa
                  $poislisa
                  $extra_poislisa
                  ORDER BY tuoteperhe.tuoteno";
        $kores = pupe_query($query);

        while ($krow = mysql_fetch_assoc($kores)) {

          unset($krow["pjarjestys"]);

          $rows[$krow["tuoteperhe"].$krow["tuoteno"]] = $krow;
          $kaikki_array[]  = $krow["tuoteno"];
        }
      }

      return array($isat_array, $kaikki_array, $rows);
    }
  }

  $query = "SELECT
            if (tuote.tuoteno = '$tuotenumero', 1, if(left(tuote.tuoteno, length('$tuotenumero')) = '$tuotenumero', 2, 3)) jarjestys,
            ifnull((SELECT isatuoteno FROM tuoteperhe use index (yhtio_tyyppi_isatuoteno) where tuoteperhe.yhtio=tuote.yhtio and tuoteperhe.tyyppi = 'P' and tuoteperhe.isatuoteno=tuote.tuoteno LIMIT 1), '') tuoteperhe,
            ifnull((SELECT isatuoteno FROM tuoteperhe use index (yhtio_tyyppi_isatuoteno) where tuoteperhe.yhtio=tuote.yhtio and tuoteperhe.tyyppi = 'V' and tuoteperhe.isatuoteno=tuote.tuoteno LIMIT 1), '') osaluettelo,
            ifnull((SELECT id FROM korvaavat use index (yhtio_tuoteno) where korvaavat.yhtio=tuote.yhtio and korvaavat.tuoteno=tuote.tuoteno LIMIT 1), tuote.tuoteno) korvaavat,
            ifnull((SELECT group_concat(id) FROM vastaavat use index (yhtio_tuoteno) where vastaavat.yhtio=tuote.yhtio and vastaavat.tuoteno=tuote.tuoteno LIMIT 1), tuote.tuoteno) vastaavat,
            tuote.tuoteno,
            tuote.nimitys,
            tuote.osasto,
            tuote.try,
            tuote.myyntihinta,
            tuote.myymalahinta,
            tuote.nettohinta,
            tuote.aleryhma,
            tuote.status,
            tuote.ei_saldoa,
            tuote.yksikko,
            tuote.tunnus,
            tuote.epakurantti25pvm,
            tuote.epakurantti50pvm,
            tuote.epakurantti75pvm,
            tuote.epakurantti100pvm,
            (SELECT group_concat(distinct tuotteen_toimittajat.toim_tuoteno order by tuotteen_toimittajat.tunnus separator '<br>') FROM tuotteen_toimittajat use index (yhtio_tuoteno) WHERE tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno) toim_tuoteno,
            tuote.sarjanumeroseuranta,
            tuote.status
            FROM tuote use index (tuoteno, nimitys)
            $lisa_parametri
            WHERE tuote.yhtio     = '$kukarow[yhtio]'
            and tuote.tuotetyyppi NOT IN ('A', 'B')
            $kieltolisa
            $lisa
            $extra_poislisa
            $poislisa
            $hinta_rajaus
            ORDER BY jarjestys, $jarjestys $sort
            LIMIT 500";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {
     $rows = array();
    $haetaan_perheet = ($piilota_tuoteperheen_lapset == "") ? TRUE : FALSE;

    // Rakennetaan array ja laitetaan vastaavat ja korvaavat mukaan
    while ($mrow = mysql_fetch_assoc($result)) {

      if ($mrow["vastaavat"] != $mrow["tuoteno"]) {

        // Tuote voi olla useammassa vastaavuusketjussa
        $vastaavat = explode(',', $mrow['vastaavat']);

        foreach ($vastaavat as $mrow['vastaavat']) {

          $kores = tuoteselaushaku_vastaavat_korvaavat("vastaavat", $mrow["vastaavat"], $mrow["tuoteno"]);

          if (mysql_num_rows($kores) > 0) {

            $vastaavamaara = mysql_num_rows($kores);

            while ($krow = mysql_fetch_assoc($kores)) {

              if (isset($vastaavamaara)) {
                // poimitaan isätuotteet
                $krow["vastaavamaara"] = $vastaavamaara;
                unset($vastaavamaara);
              }
              else {
                $krow["mikavastaava"] = $mrow["tuoteno"];
              }

              if (!isset($rows[$mrow["vastaavat"].$krow["tuoteno"]])) $rows[$mrow["vastaavat"].$krow["tuoteno"]] = $krow;
            }
          }
          else {
            $rows[$mrow["tuoteno"]] = $mrow;
          }
        }
      }

      if ($mrow["korvaavat"] != $mrow["tuoteno"]) {
        $kores = tuoteselaushaku_vastaavat_korvaavat("korvaavat", $mrow["korvaavat"], $mrow["tuoteno"]);

        if (mysql_num_rows($kores) > 0) {

          // Korvaavan isätuotetta ei listata uudestaan jos se on jo listattu vastaavaketjussa
          if (!isset($rows[$mrow["korvaavat"].$mrow["tuoteno"]])) $rows[$mrow["korvaavat"].$mrow["tuoteno"]] = $mrow;

          while ($krow = mysql_fetch_assoc($kores)) {
            $krow["mikakorva"] = $mrow["tuoteno"];

            if (!isset($rows[$mrow["korvaavat"].$krow["tuoteno"]])) $rows[$mrow["korvaavat"].$krow["tuoteno"]] = $krow;
          }
        }
        else {
          $rows[$mrow["tuoteno"]] = $mrow;
        }
      }

      if ($mrow["korvaavat"] == $mrow["tuoteno"] and $mrow["vastaavat"] == $mrow["tuoteno"]) {
        $rows[$mrow["tuoteno"]] = $mrow;

        if ($mrow["tuoteperhe"] == $mrow["tuoteno"] and $haetaan_perheet) {
          $riikoko     = 1;
          $isat_array   = array();
          $kaikki_array   = array($mrow["tuoteno"]);

          for ($isa=0; $isa < $riikoko; $isa++) {
            list($isat_array, $kaikki_array, $rows) = tuoteselaushaku_tuoteperhe($mrow["tuoteno"], $kaikki_array[$isa], $isat_array, $kaikki_array, $rows, 'P');

            if ($yhtiorow["rekursiiviset_tuoteperheet"] == "Y") {
              $riikoko = count($kaikki_array);
            }
          }
        }

        if ($mrow["osaluettelo"] == $mrow["tuoteno"] and $haetaan_perheet) {
          //$mrow["osaluettelo"] == $mrow["tuoteno"]
          $riikoko     = 1;
          $isat_array   = array();
          $kaikki_array   = array($mrow["tuoteno"]);

          for ($isa=0; $isa < $riikoko; $isa++) {
            list($isat_array, $kaikki_array, $rows) = tuoteselaushaku_tuoteperhe($mrow["tuoteno"], $kaikki_array[$isa], $isat_array, $kaikki_array, $rows, 'V');

            if ($yhtiorow["rekursiiviset_tuoteperheet"] == "Y") {
              $riikoko = count($kaikki_array);
            }
          }
        }
      }
    }
    
    var_dump($rows);
  }
  else {
    echo "<br/>", t("Yhtään tuotetta ei löytynyt"), "!";
  }

  if (mysql_num_rows($result) == 500) {
    echo "<br><br><font class='message'>".t("Löytyi yli 500 tuotetta, tarkenna hakuasi")."!</font>";
  }
  
}


/**
 * Tulostetaan sivuston footer osio.
 */
if ($verkkokauppa == "") {
  if (@include "inc/footer.inc");
  elseif (@include "footer.inc");
  else exit;
}

/**
 * Funktio hakee tietokannasta yhtiöt. 
 * 
 * Kysely tehdään yhtio -taululle. Hakuehdoksi määritetään konserni sarake.
 * 
 * @global type $yhtiorow
 * @global type $kukarow
 * @return type
 */
function hae_yhtiot() {
  global $yhtiorow, $kukarow;

  $query = "SELECT *
            FROM yhtio
            WHERE konserni='$yhtiorow[konserni]' and konserni != ''";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0 and $yhtiorow["haejaselaa_konsernisaldot"] != "") {
    $yhtiot = array();

    while ($row = mysql_fetch_assoc($result)) {
      $yhtiot[] = $row["yhtio"];
    }
    return $yhtiot;
  }
  else {
    $yhtiot = array();
    $yhtiot[] = $kukarow["yhtio"];
    return $yhtiot;
  }
}
