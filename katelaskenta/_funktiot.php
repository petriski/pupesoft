<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Funktio lis‰‰ tuotteisiin vastaavat ja korvaavat tuotteet.
 * 
 * Funktio luotu jo aikaisemmasta koodista, joka on sitten
 * laitettu uuden funktion sis‰‰n.
 */
function lisaa_vastaavat_ja_korvaavat_tuotteet($result, $rows, $haetaan_perheet) {
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
                            // poimitaan is‰tuotteet
                            $krow["vastaavamaara"] = $vastaavamaara;
                            unset($vastaavamaara);
                        } else {
                            $krow["mikavastaava"] = $mrow["tuoteno"];
                        }

                        if (!isset($rows[$mrow["vastaavat"] . $krow["tuoteno"]]))
                            $rows[$mrow["vastaavat"] . $krow["tuoteno"]] = $krow;
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

                // Korvaavan is‰tuotetta ei listata uudestaan jos se on jo listattu vastaavaketjussa
                if (!isset($rows[$mrow["korvaavat"] . $mrow["tuoteno"]]))
                    $rows[$mrow["korvaavat"] . $mrow["tuoteno"]] = $mrow;

                while ($krow = mysql_fetch_assoc($kores)) {
                    $krow["mikakorva"] = $mrow["tuoteno"];

                    if (!isset($rows[$mrow["korvaavat"] . $krow["tuoteno"]]))
                        $rows[$mrow["korvaavat"] . $krow["tuoteno"]] = $krow;
                }
            }
            else {
                $rows[$mrow["tuoteno"]] = $mrow;
            }
        }

        if ($mrow["korvaavat"] == $mrow["tuoteno"] and $mrow["vastaavat"] == $mrow["tuoteno"]) {
            $rows[$mrow["tuoteno"]] = $mrow;

            if ($mrow["tuoteperhe"] == $mrow["tuoteno"] and $haetaan_perheet) {
                $riikoko = 1;
                $isat_array = array();
                $kaikki_array = array($mrow["tuoteno"]);

                for ($isa = 0; $isa < $riikoko; $isa++) {
                    list($isat_array, $kaikki_array, $rows) = tuoteselaushaku_tuoteperhe($mrow["tuoteno"], $kaikki_array[$isa], $isat_array, $kaikki_array, $rows, 'P');

                    if ($yhtiorow["rekursiiviset_tuoteperheet"] == "Y") {
                        $riikoko = count($kaikki_array);
                    }
                }
            }

            if ($mrow["osaluettelo"] == $mrow["tuoteno"] and $haetaan_perheet) {
                //$mrow["osaluettelo"] == $mrow["tuoteno"]
                $riikoko = 1;
                $isat_array = array();
                $kaikki_array = array($mrow["tuoteno"]);

                for ($isa = 0; $isa < $riikoko; $isa++) {
                    list($isat_array, $kaikki_array, $rows) = tuoteselaushaku_tuoteperhe($mrow["tuoteno"], $kaikki_array[$isa], $isat_array, $kaikki_array, $rows, 'V');

                    if ($yhtiorow["rekursiiviset_tuoteperheet"] == "Y") {
                        $riikoko = count($kaikki_array);
                    }
                }
            }
        }
    }
    return $rows;
}

/**
 * Funktiota k‰ytet‰‰n "lisaa_vastaavat_ja_korvaavat_tuotteet" -funktion
 * tulosten hakemiseen. 
 * 
 * @global type $kukarow
 * @global type $kieltolisa
 * @global type $poislisa
 * @global string $hinta_rajaus
 * @global type $extra_poislisa
 * @param type $tvk_taulu
 * @param type $tvk_korvaavat
 * @param type $tvk_tuoteno
 * @return type
 */
function tuoteselaushaku_vastaavat_korvaavat($tvk_taulu, $tvk_korvaavat, $tvk_tuoteno) {
    global $kukarow, $kieltolisa, $poislisa, $hinta_rajaus, $extra_poislisa;

    if ($tvk_taulu != "vastaavat")
        $kyselylisa = " and {$tvk_taulu}.tuoteno != '$tvk_tuoteno' ";
    else
        $kyselylisa = "";

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
                tuote.kehahin,
                tuote.myyntikate,
                tuote.katelaskenta,
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

/**
 * Funktiota k‰ytet‰‰n "lisaa_vastaavat_ja_korvaavat_tuotteet" -funktion
 * tulosten hakemiseen. 
 * 
 * @global type $kukarow
 * @global type $kieltolisa
 * @global type $poislisa
 * @global string $hinta_rajaus
 * @global type $extra_poislisa
 * @param type $esiisatuoteno
 * @param type $tuoteno
 * @param type $isat_array
 * @param type $kaikki_array
 * @param type $rows
 * @param type $tyyppi
 * @return type
 */
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
                  tuote.kehahin,
                  tuote.myyntikate,
                  tuote.katelaskenta,
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

            $rows[$krow["tuoteperhe"] . $krow["tuoteno"]] = $krow;
            $kaikki_array[] = $krow["tuoteno"];
        }
    }

    return array($isat_array, $kaikki_array, $rows);
}

/**
 * Funktio valmistelee hakutulokset templatea varten.
 * 
 * Palauttaa muokatun hakutulostaulukon.
 * @param type $tuotteet
 * @param type $verkkokauppa
 * @param type $hae_ja_selaa_row
 */
function valmistele_hakutulokset($tuotteet, $verkkokauppa, $hae_ja_selaa_row) {

    foreach ($tuotteet as $avain => $arvo) { // $rows muuttuja tulee templaten ulkopuolelta 
        // Laajennetaan tuotteen nimityst‰ "korvaa tuotteen" merkinn‰ll‰
        if ($verkkokauppa == "" and isset($arvo["mikakorva"])) {
            $tuotteet[$avain]["nimitys"] .= "<br> * " . t("Korvaa tuotteen") . ": $arvo[mikakorva]";
        }

        // Merkit‰‰n nimitykseen "poistuva"
        if ($hae_ja_selaa_row['selite'] != 'B' and
                $verkkokauppa == "" and
                strtoupper($arvo["status"]) == "P") {
            $tuotteet[$avain]["nimitys"] .= "<br> * " . t("Poistuva tuote");
        }

        $tuotteet[$avain]["myyntihinta"] = hintapyoristys($arvo["myyntihinta"], 2);

        // Laske myyntihinta keskihankintahinnasta ja tallenna se
        // uuteen sarakkeeseen myˆhemp‰‰ k‰yttˆ‰ varten.
        // 
        // Jos katelaskenta-sarake on tyhj‰, ei myyntihintaa lasketa.
        // T‰m‰ vaikuttaa siis tuotteisiin, johon sit‰ ei ole m‰‰ritetty
        // tietokantamuutosten j‰lkeen tai kate on merkitty, ettei sit‰
        // ylip‰‰t‰ns‰ laskenta.
        // REFACTOR: Saattoi j‰‰d‰ ylim‰‰r‰iseksi. Katso myˆhemmin kun on
        // saatu palautetta.
//        if ($arvo["katelaskenta"] != '0' || $arvo["katelaskenta"] != NULL) {
//            $keskihankintahinta = (float) $arvo["kehahin"];
//            $myyntikate = (float) $arvo["myyntikate"];
//
//            $laskettu_myyntihinta = $keskihankintahinta / ( 1 - ( $myyntikate / 100 ) );
//            $tuotteet[$avain]["laskettu_myyntihinta"] = hintapyoristys($laskettu_myyntihinta, 2);
//        }
    }

    return $tuotteet;
}

/**
 * Funktio hakee tietokannasta yhtiˆt. 
 * 
 * Kysely tehd‰‰n yhtio -taululle. Hakuehdoksi m‰‰ritet‰‰n konserni sarake.
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
    } else {
        $yhtiot = array();
        $yhtiot[] = $kukarow["yhtio"];
        return $yhtiot;
    }
}

/**
 * Funktio hakee tuotteelle hinnan ja palauttaa sen ilman muotoiluja.
 * 
 * Funktio on muokattu tuote_selaus_haku.php sijaitsevasta piirra_hinta()
 * -funktiosta. T‰m‰ ei vain lis‰‰ hinnan mukana mit‰‰n muotoiluja vaan
 * pelk‰n hinnan ja valuutan symbolin.
 * 
 * @global type $kukarow
 * @global type $yhtiorow
 * @global type $verkkokauppa
 * @param type $row
 * @param type $oleasrow
 * @param type $valuurow
 * @param type $vari
 * @param type $classmidl
 * @param type $hinta_rajaus
 * @param type $poistetut
 * @param type $lisatiedot
 * @return string
 */
function laske_hinta($row, $oleasrow, $valuurow, $vari, $classmidl, $hinta_rajaus, $poistetut, $lisatiedot) {
    global $kukarow, $yhtiorow, $verkkokauppa;

    if ($kukarow['hinnat'] >= 0 and ( $verkkokauppa == "" or $kukarow["kuka"] != "www")) {
        $myyntihinta = hintapyoristys($row["myyntihinta"]) . " $yhtiorow[valkoodi]";

        if ($kukarow["extranet"] != "" and $kukarow["naytetaan_asiakashinta"] != "") {
            list($hinta,
                    $netto,
                    $ale_kaikki,
                    $alehinta_alv,
                    $alehinta_val) = alehinta($oleasrow, $row, 1, '', '', '');

            $myyntihinta_echotus = $hinta * generoi_alekentta_php($ale_kaikki, 'M', 'kerto');
            $myyntihinta = hintapyoristys($myyntihinta_echotus) . " $alehinta_val";
        } elseif ($kukarow["extranet"] != "") {
            // jos kyseess‰ on extranet asiakas yritet‰‰n n‰ytt‰‰ kaikki hinnat oikeassa valuutassa
            if ($oleasrow["valkoodi"] != $yhtiorow["valkoodi"]) {

                $myyntihinta = hintapyoristys($row["myyntihinta"]) . " $yhtiorow[valkoodi]";

                $query = "SELECT *
                  FROM hinnasto
                  WHERE yhtio  = '{$kukarow["yhtio"]}'
                  AND tuoteno  = '{$row["tuoteno"]}'
                  AND valkoodi = '{$oleasrow["valkoodi"]}'
                  AND laji     = ''
                  AND (
                    (alkupvm <= current_date and if(loppupvm = '0000-00-00',
                                                    '9999-12-31',
                                                    loppupvm) >= current_date)
                    or (alkupvm = '0000-00-00' and loppupvm = '0000-00-00'))
                  ORDER BY ifnull(to_days(current_date) - to_days(alkupvm), 9999999999999)
                  LIMIT 1";

                $olhires = pupe_query($query);

                if (mysql_num_rows($olhires) == 1) {
                    $olhirow = mysql_fetch_assoc($olhires);
                    $myyntihinta = hintapyoristys($olhirow["hinta"]) . " $olhirow[valkoodi]";
                } elseif ($valuurow["kurssi"] != 0) {
                    $myyntihinta = hintapyoristys(laskuval($row["myyntihinta"], $valuurow["kurssi"])) .
                            " $oleasrow[valkoodi]";
                }
            }
        } else {
            $query = "SELECT DISTINCT valkoodi,
                maa
                FROM hinnasto
                WHERE yhtio = '$kukarow[yhtio]'
                AND tuoteno = '$row[tuoteno]'
                AND laji    = ''
                ORDER BY maa, valkoodi";

            $hintavalresult = pupe_query($query);

            while ($hintavalrow = mysql_fetch_assoc($hintavalresult)) {
                // katotaan onko tuotteelle valuuttahintoja
                $query = "SELECT *
                  FROM hinnasto
                  WHERE yhtio  = '$kukarow[yhtio]'
                  AND tuoteno  = '$row[tuoteno]'
                  AND valkoodi = '$hintavalrow[valkoodi]'
                  AND maa      = '$hintavalrow[maa]'
                  AND laji     = ''
                  AND (
                    (alkupvm <= current_date and if(loppupvm = '0000-00-00',
                                                    '9999-12-31',
                                                    loppupvm) >= current_date)
                    or (alkupvm = '0000-00-00' and loppupvm = '0000-00-00'))
                  ORDER BY ifnull(to_days(current_date) - to_days(alkupvm), 9999999999999)
                  LIMIT 1";

                $hintaresult = pupe_query($query);

                while ($hintarow = mysql_fetch_assoc($hintaresult)) {
                    $myyntihinta .= "<br>$hintarow[maa]: " .
                            hintapyoristys($hintarow["hinta"]) .
                            " $hintarow[valkoodi]";
                }
            }
        }


        if ($hinta_rajaus != "") {
            return hintapyoristys($row["myymalahinta"]) . " " . $yhtiorow["valkoodi"];
        }

        if (($poistetut != "" and $kukarow["extranet"] != "")) {
            return $myyntihinta;
        } else {
            return $myyntihinta;
        }

        if ($lisatiedot != "" and $kukarow["extranet"] == "") {
            return hintapyoristys($row["nettohinta"]) . " " . $yhtiorow["valkoodi"];
        }
    }
}

/**
 * Funktio hakee ja piirt‰‰ tuotteen saldon.
 * 
 * Aivan sama kuin tuote_selaus_haku.php tiedostossa. Koska funktio myˆs
 * echottelee html -koodia, on siit‰ vaikea saada yht‰ arvoa takaisin.
 * 
 * REFACTOR: Funktio kaipaa refaktorointia, jotta sit‰ voitaisiin k‰ytt‰‰ molemmissa
 * tiedostoissa.
 * 
 * @global type $toim_kutsu
 * @global type $verkkokauppa
 * @global type $kukarow
 * @global type $verkkokauppa_saldotsk
 * @global type $laskurow
 * @global type $saldoaikalisa
 * @global type $yhtiorow
 * @global type $rivin_yksikko
 * @global type $vari
 * @global type $classrigh
 * @global string $hinta_rajaus
 * @global type $ostoskori
 * @global type $yht_i
 * @global type $lisatiedot
 * @global type $hae_ja_selaa_row
 * @param type $row
 * @param type $yhtiot
 * @param type $oleasrow
 */
function hae_ja_piirra_saldo($row, $yhtiot, $oleasrow) {
    global $toim_kutsu, $verkkokauppa, $kukarow, $verkkokauppa_saldotsk, $laskurow,
    $saldoaikalisa, $yhtiorow, $rivin_yksikko, $vari, $classrigh, $hinta_rajaus, $ostoskori,
    $yht_i, $lisatiedot, $hae_ja_selaa_row;

    if ($toim_kutsu != "EXTENNAKKO" and ( $verkkokauppa == "" or ( $verkkokauppa != "" and $kukarow["kuka"] != "www" and $verkkokauppa_saldotsk))) {
        // Tuoteperheen is‰t, mutta ei sarjanumerollisisa isi‰ (Normi, Extranet ja Verkkokauppa)
        if ($row["tuoteperhe"] == $row["tuoteno"] and $row["sarjanumeroseuranta"] != "S") {
            // Extranet ja verkkokauppa
            if ($kukarow["extranet"] != "" or $verkkokauppa != "") {

                $saldot = tuoteperhe_myytavissa($row["tuoteno"], "KAIKKI", "", 0, "", "", "", "", "", $laskurow["toim_maa"], $saldoaikalisa);

                $kokonaismyytavissa = 0;

                foreach ($saldot as $varasto => $myytavissa) {
                    $kokonaismyytavissa += $myytavissa;
                }

                if ($yhtiorow["extranet_nayta_saldo"] == "Y") {
                    $naytettava_saldo = sprintf("%.2f", $kokonaismyytavissa) . " {$rivin_yksikko}";
                    $_vari = "";
                } elseif ($kokonaismyytavissa > 0) {
                    $naytettava_saldo = t("On");
                    $_vari = "green";
                } else {
                    $naytettava_saldo = t("Ei");
                    $_vari = "red";
                }

                echo "<td valign='top' class='$vari' $classrigh>";
                echo "<font class='$_vari'>";

                if ($hinta_rajaus != "") {
                    echo t("P‰‰varasto") . ": ";
                }

                echo $naytettava_saldo;
                echo "</font>";
                echo "</td>";
            }
            // Normipupe
            else {
                $saldot = tuoteperhe_myytavissa($row["tuoteno"], "", "KAIKKI", 0, "", "", "", "", "", $laskurow["toim_maa"], $saldoaikalisa);

                $classrighx = substr($classrigh, 0, -2) . " padding: 0px;' ";

                echo "<td valign='top' class='$vari' $classrighx>";
                echo "<table style='width:100%;'>";

                $ei_tyhja = "";

                foreach ($saldot as $varaso => $saldo) {
                    if ($saldo != 0) {
                        $ei_tyhja = 'yes';
                        $_saldo = sprintf("%.2f", $saldo);

                        echo "<tr class='aktiivi'>";
                        echo "<td class='$vari' nowrap>$varaso</td>";
                        echo "<td class='$vari' align='right' nowrap>{$_saldo} {$rivin_yksikko}</td>";
                        echo "</tr>";
                    }
                }

                if ($ei_tyhja == '') {
                    echo "<tr class='aktiivi'><td class='$vari' nowrap colspan='2'><font class='red'>" . t("Tuote loppu") . "</font></td></tr>";
                }

                echo "</table></td>";
            }
        }
        // Saldottomat tuotteet (Normi, Extranet ja Verkkokauppa)
        elseif ($row['ei_saldoa'] != '') {
            if ($kukarow["extranet"] != "" or $verkkokauppa != "") {
                echo "<td valign='top' class='$vari' $classrigh><font class='green'>" . t("On") . "</font></td>";
            } else {
                echo "<td valign='top' class='$vari' $classrigh><font class='green'>" . t("Saldoton") . "</font></td>";
            }
        }
        // Sarjanumerolliset tuotteet ja sarjanumerolliset is‰t (Normi, Extranet)
        elseif ($verkkokauppa == "" and ( $row["sarjanumeroseuranta"] == "S" and ( $row["tuoteperhe"] == "" or $row["tuoteperhe"] == $row["tuoteno"]) and $row["osaluettelo"] == "")) {
            if ($kukarow["extranet"] != "") {
                echo "<td valign='top' class='$vari' $classrigh>$row[sarjanumero] ";
            } else {
                echo "<td valign='top' class='$vari' $classrigh><a onClick=\"javascript:sarjanumeronlisatiedot_popup('$row[sarjatunnus]')\">$row[sarjanumero]</a> ";
            }

            if (!isset($row["sarjadisabled"]) and $row["sarjayhtio"] == $kukarow["yhtio"] and ( $kukarow["kuka"] != "" or is_numeric($ostoskori))) {
                echo "<input type='hidden' name='tiltuoteno[$yht_i]' value = '$row[tuoteno]'>";
                echo "<input type='hidden' name='tilsarjatunnus[$yht_i]' value = '$row[sarjatunnus]'>";
                echo "<input type='checkbox' name='tilkpl[$yht_i]' value='1'> ";
                $yht_i++;
            }

            echo "</td>";

            if ($lisatiedot != "" and $kukarow["extranet"] == "") {
                echo "<td class='$vari' $classrigh></td>";
            }
        }
        // Normaalit saldolliset tuotteet (Extranet ja Verkkokauppa)
        elseif ($kukarow["extranet"] != "" or $verkkokauppa != "") {
            piirra_extranet_saldo($row, $oleasrow);
        }
        // Normaalit saldolliset tuotteet (Normi)
        else {

            $sallitut_maat_lisa = "";

            if ($laskurow["toim_maa"] != '') {
                $sallitut_maat_lisa = " and (varastopaikat.sallitut_maat like '%$laskurow[toim_maa]%' or varastopaikat.sallitut_maat = '') ";
            }

            // K‰yd‰‰n l‰pi tuotepaikat
            if ($row["sarjanumeroseuranta"] == "E" or $row["sarjanumeroseuranta"] == "F" or $row["sarjanumeroseuranta"] == "G") {
                $query = "SELECT tuote.yhtio, tuote.tuoteno, tuote.ei_saldoa, varastopaikat.tunnus varasto, varastopaikat.tyyppi varastotyyppi, varastopaikat.maa varastomaa,
                  tuotepaikat.oletus, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso,
                  sarjanumeroseuranta.sarjanumero era,
                  concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'),lpad(upper(tuotepaikat.hyllyvali), 5, '0'),lpad(upper(tuotepaikat.hyllytaso), 5, '0')) sorttauskentta,
                  varastopaikat.nimitys, if (varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi
                   FROM tuote
                  JOIN tuotepaikat ON (tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno)
                  JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio
                  $sallitut_maat_lisa
                  AND varastopaikat.tunnus                  = tuotepaikat.varasto)
                  JOIN sarjanumeroseuranta ON sarjanumeroseuranta.yhtio = tuote.yhtio
                  AND sarjanumeroseuranta.tuoteno           = tuote.tuoteno
                  AND sarjanumeroseuranta.hyllyalue         = tuotepaikat.hyllyalue
                  AND sarjanumeroseuranta.hyllynro          = tuotepaikat.hyllynro
                  AND sarjanumeroseuranta.hyllyvali         = tuotepaikat.hyllyvali
                  AND sarjanumeroseuranta.hyllytaso         = tuotepaikat.hyllytaso
                  AND sarjanumeroseuranta.myyntirivitunnus  = 0
                  AND sarjanumeroseuranta.era_kpl          != 0
                  WHERE tuote.yhtio                         in ('" . implode("','", $yhtiot) . "')
                  and tuote.tuoteno                         = '$row[tuoteno]'
                  GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15
                  ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta";
            } else {
                $query = "SELECT tuote.yhtio, tuote.tuoteno, tuote.ei_saldoa, varastopaikat.tunnus varasto, varastopaikat.tyyppi varastotyyppi, varastopaikat.maa varastomaa,
                  tuotepaikat.oletus, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso,
                  concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0')) sorttauskentta,
                  varastopaikat.nimitys, if (varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi
                  FROM tuote
                  JOIN tuotepaikat ON (tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno)
                  JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio
                  $sallitut_maat_lisa
                  AND varastopaikat.tunnus = tuotepaikat.varasto)
                  WHERE tuote.yhtio        in ('" . implode("','", $yhtiot) . "')
                  AND tuote.tuoteno        = '$row[tuoteno]'
                  ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta";
            }
            $varresult = pupe_query($query);

            $classrighx = substr($classrigh, 0, -2) . " padding: 0px;' ";

            echo "<td valign='top' class='$vari' $classrighx>";
            echo "<table style='width:100%;'>";

            $loytyko = false;
            $loytyko_normivarastosta = false;
            $myytavissa_sum = 0;

            if (mysql_num_rows($varresult) > 0) {
                $hyllylisa = "";

                // katotaan jos meill‰ on tuotteita varaamassa saldoa joiden varastopaikkaa ei en‰‰ ole olemassa...
                list($saldo, $hyllyssa, $orvot) = saldo_myytavissa($row["tuoteno"], 'ORVOT', '', '', '', '', '', '', '', $saldoaikalisa);
                $orvot *= -1;

                while ($saldorow = mysql_fetch_assoc($varresult)) {

                    if (!isset($saldorow["era"]))
                        $saldorow["era"] = "";

                    list($saldo, $hyllyssa, $myytavissa, $sallittu) = saldo_myytavissa($saldorow["tuoteno"], '', '', $saldorow["yhtio"], $saldorow["hyllyalue"], $saldorow["hyllynro"], $saldorow["hyllyvali"], $saldorow["hyllytaso"], $laskurow["toim_maa"], $saldoaikalisa, $saldorow["era"]);

                    //  Listataan vain varasto jo se ei ole kielletty
                    if ($sallittu === true) {
                        // hoidetaan pois problematiikka jos meill‰ on orpoja (tuotepaikattomia) tuotteita varaamassa saldoa
                        if ($orvot > 0) {
                            if ($myytavissa >= $orvot and $saldorow["yhtio"] == $kukarow["yhtio"]) {
                                // poistaan orpojen varaamat tuotteet t‰lt‰ paikalta
                                $myytavissa = $myytavissa - $orvot;
                                $orvot = 0;
                            } elseif ($orvot > $myytavissa and $saldorow["yhtio"] == $kukarow["yhtio"]) {
                                // poistetaan niin paljon orpojen saldoa ku voidaan
                                $orvot = $orvot - $myytavissa;
                                $myytavissa = 0;
                            }
                        }

                        if ($myytavissa != 0 or ( $lisatiedot != "" and $hyllyssa != 0)) {
                            $id2 = md5(uniqid());

                            echo "<tr>";
                            echo "<td class='$vari' nowrap>";
                            echo "<a class='tooltip' id='$id2'>$saldorow[nimitys]</a> $saldorow[tyyppi]";
                            echo "<div id='div_$id2' class='popup' style='width: 300px'>($saldorow[hyllyalue]-$saldorow[hyllynro]-$saldorow[hyllyvali]-$saldorow[hyllytaso])</div>";
                            echo "</td>";

                            echo "<td class='$vari' align='right' nowrap>";

                            if ($hae_ja_selaa_row['selite'] == 'B') {
                                echo "<font class='green'>";
                            }

                            echo sprintf("%.2f", $myytavissa) . " " . $rivin_yksikko;

                            if ($hae_ja_selaa_row['selite'] == 'B') {
                                echo "</font>";
                            }

                            echo "</td></tr>";
                        }

                        if ($myytavissa > 0) {
                            $loytyko = true;
                        }

                        if ($myytavissa > 0 and $saldorow["varastotyyppi"] != "E") {
                            $loytyko_normivarastosta = true;
                        }

                        if ($lisatiedot != "" and $hyllyssa != 0) {
                            $hyllylisa .= "  <tr class='aktiivi'>
                          <td class='$vari' align='right' nowrap>" . sprintf("%.2f", $hyllyssa) . "</td>
                          </tr>";
                        }

                        if ($saldorow["tyyppi"] != "E") {
                            $myytavissa_sum += $myytavissa;
                        }
                    }
                }
            }

            $tulossalisat = hae_tuotteen_saapumisaika($row['tuoteno'], $row['status'], $myytavissa_sum, $loytyko, $loytyko_normivarastosta);

            foreach ($tulossalisat as $tulossalisa) {
                list($o, $v) = explode("!°!", $tulossalisa);
                echo "<tr><td>$o</td><td>$v</td></tr>";
            }

            echo "</table></td>";

            if ($lisatiedot != "") {
                echo "<td valign='top' $classrigh class='$vari'>";

                if (mysql_num_rows($varresult) > 0 and $hyllylisa != "") {

                    echo "<table width='100%'>";
                    echo "$hyllylisa";
                    echo "</table></td>";
                }
                echo "</td>";
            }
        }
    }
}

/**
 * Funktio hakee annettujen parametrien mukaan tuotteet.
 * 
 * @param type $tuotenumero
 * @param type $lisa_parametri
 * @param type $kukarow
 * @param type $kieltolisa
 * @param type $lisa
 * @param type $extra_poislisa
 * @param type $poislisa
 * @param type $hinta_rajaus
 * @param type $jarjestys
 * @param type $sort
 * @return type
 */
function hae_tuotteet_kysely($args) {

    $tuotenumero = ($args["tuotenumero"] != "" or $args["tuotenumero"] != NULL ? $args["tuotenumero"] : "");
    $lisa_parametri = $args["lisa_parametri"];
    $kukarow = $args["kukarow"];
    $kieltolisa = $args["kieltolisa"];
    $lisa = $args["lisa"];
    $extra_poislisa = $args["extra_poislisa"];
    $poislisa = $args["poislisa"];
    $hinta_rajaus = $args["hinta_rajaus"];
    $jarjestys = ($args["jarjestys"] != "" or $args["jarjestys"] != NULL ? $args["jarjestys"] : "");
    $sort = $args["sort"];


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
            tuote.kehahin,
            tuote.myyntikate,
            tuote.katelaskenta,
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

    return pupe_query($query);
}

function piirra_tuotehaku(
$verkkokauppa, $hae_ja_selaa_row, $toim_kutsu, $kukarow, $ostoskori, $valittu_tarjous_tunnus, $tultiin, $tuotenumero, $toim_tuoteno, $poischeck, $extrapoischeck, $nimitys, $alkuperaisnumero, $lisacheck, $orginaaalit, $ptlcheck, $saldotoncheck) {

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
            echo "<tr><th>" . t("Tuotenumero") . "</th><td><input type='text' size='25' name='tuotenumero' id='tuotenumero' value = '$tuotenumero'></td>";
            echo "<th>" . t("Toim tuoteno") . "</th><td><input type='text' size='25' name = 'toim_tuoteno' id='toim_tuoteno' value = '$toim_tuoteno'></td>";

            if ($kukarow["extranet"] != "") {
                echo "<th>" . t("Tarjoustuotteet") . "</th>";
            } else {
                echo "<th>" . t("Poistetut") . "</th>";
            }
            echo "<td><input type='checkbox' name='poistetut' id='poistetut' $poischeck></td>";

            if ($kukarow["extranet"] != "" and $kukarow['asema'] == "NE") {
                echo "<th>" . t("N‰yt‰ poistetut") . "</th><td><input type='checkbox' name='extrapoistetut' id='extrapoistetut' $extrapoischeck></td>";
            }

            echo "</tr>";

            echo "<tr><th>" . t("Nimitys") . "</th><td><input type='text' size='25' name='nimitys' id='nimitys' value = '$nimitys'></td>";

            if ($orginaaalit) {
                echo "<th>" . t("Alkuper‰isnumero") . "</th><td><input type='text' size='25' name = 'alkuperaisnumero' id='alkuperaisnumero' value = '$alkuperaisnumero'></td>";
            } else {
                echo "<th>&nbsp;</th><td>&nbsp;</td>";
            }

            echo "<th>" . t("Lis‰tiedot") . "</th><td><input type='checkbox' name='lisatiedot' id='lisatiedot' $lisacheck></td>";
            echo "</tr>";
        } else {
            echo "<tr><th>" . t("Tuotenumero") . "</th><td><input type='text' size='25' name='tuotenumero' id='tuotenumero' value = '$tuotenumero'></td></tr>";
            echo "<tr><th>" . t("Toim tuoteno") . "</th><td><input type='text' size='25' name = 'toim_tuoteno' id='toim_tuoteno' value = '$toim_tuoteno'></td></tr>";

            if ($orginaaalit) {
                echo "<tr><th>" . t("Alkuper‰isnumero") . "</th><td><input type='text' size='25' name = 'alkuperaisnumero' id='alkuperaisnumero' value = '$alkuperaisnumero'></td></tr>";
            }

            echo "<tr><th>" . t("Nimitys") . "</th><td><input type='text' size='25' name='nimitys' id='nimitys' value = '$nimitys'></td></tr>";
            if ($kukarow["extranet"] != "") {
                echo "<tr><th>" . t("Tarjoustuotteet") . "</th>";
            } else {
                echo "<tr><th>" . t("Poistetut") . "</th>";
            }
            echo "<td><input type='checkbox' name='poistetut' id='poistetut' $poischeck></td></tr>";
            echo "<tr><th>" . t("Lis‰tiedot") . "</th><td><input type='checkbox' name='lisatiedot' id='lisatiedot' $lisacheck></td></tr>";
        }

        if ($kukarow['extranet'] == "" and $verkkokauppa == "") {
            echo "<tr>";
            echo "<th>" . t("Piilota tuoteperherakenne") . "</th>";
            echo "<td><input type='checkbox' name='piilota_tuoteperheen_lapset' $ptlcheck></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<th>" . t("N‰yt‰ vain saldolliset tuotteet") . "</th>";
            echo "<td><input type='checkbox' name='saldotonrajaus' $saldotoncheck></td>";
            echo "</tr>";
        }

        echo "</table><br/>";

        if ($hae_ja_selaa_row['selite'] == 'B') {
            echo "</fieldset>";

            echo "<fieldset>";
            echo "<legend>", t("Rajaa tuotteita"), "</legend>";
            echo "<span class='info'>", t("Aloita valitsemalla osasto / tuoteryhm‰"), "</span>";
        }

        echo "<br/>";

        // Monivalintalaatikot (osasto, try tuotemerkki...)
        // M‰‰ritell‰‰n mitk‰ latikot halutaan mukaan
        if (trim($hae_ja_selaa_row['selitetark']) != '') {
            $monivalintalaatikot = explode(",", $hae_ja_selaa_row['selitetark']);

            if (trim($hae_ja_selaa_row['selitetark_2'] != '')) {
                $monivalintalaatikot_normaali = explode(",", $hae_ja_selaa_row['selitetark_2']);
            } else {
                $monivalintalaatikot_normaali = array();
            }
        } else {
            // Oletus
            $monivalintalaatikot = array("OSASTO", "TRY", "TUOTEMERKKI", "MALLI", "MALLI/MALLITARK", "<br>DYNAAMINEN_TUOTE");
            $monivalintalaatikot_normaali = array();
        }

        /**
         * REFACTOR: Include tiedosto joudutaan hakemaan toisesta kansiosta.
         */
        require "../tilauskasittely/monivalintalaatikot.inc";

        if ($hae_ja_selaa_row['selite'] == 'B') {
            echo "</fieldset>";
        }

        echo "<input type='submit' name='submit_button' id='submit_button' class='hae_btn' value = '" . t("Etsi") . "'></form>";
        echo "&nbsp;<form action = '?toim_kutsu=$toim_kutsu' method = 'post'>
      <input type='hidden' name='tilausnumero' value='$kukarow[kesken]'>
      <input type='hidden' name='valittu_tarjous_tunnus' value='$valittu_tarjous_tunnus'>
      <input type='submit' name='submit_button2' id='submit_button2' value = '" . t("Tyhjenn‰") . "'>
      </form>";

        if ($hae_ja_selaa_row['selite'] == 'B') {
            echo "</div>";
        }
    }
}


function lisaa_hintaan_kate($keskihankintahinta, $kateprosentti) {

    $keskihankintahinta = (float)$keskihankintahinta;
    $kateprosentti = (float)$kateprosentti;

    return $keskihankintahinta / ( 1 - ( $kateprosentti / 100 ) );
}

function tallenna_katemuutokset($data) {
    
    
    # Luodaan virhe -taulukko viestej‰ varten
    # Tarkistetaan ja siivotaan valitutrivit taulukko
    # Tarkistetaan ja siivotaan annetut kateprosentit
    # -> Poistetaan taulusta kaikki ylim‰‰r‰iset paitsi valitut rivit
    # -> Tarkistetaan, ett‰ kateprosentti on 0-100 v‰lill‰
    # Tarkistetaan ja siivotaan keskihankintahinnat
    # -> Tarkistetaan, ett‰ vain valitut rivit j‰‰v‰t
    # Tarkistetaan ja siivotaan annetut katelaskentakomennot
    # -> Suodatetaan valitut rivit j‰ljelle
    # -> Tarkistetaan, ett‰ komennot ovat sallittujen listalla
    # -> Siivotaan ylim‰‰r‰iset merkit
    # -> Siivotaan duplikaattimerkit
    # -> Palautetaan siivottu taulukko
    
    # Rakennetaan sql update kysely
    # -> Aloitetaaj UPDATE -komennolla
    # -> luodaan komento myyntikatteen, hintamuutospvm ja katelaskentakomentojen p‰ivitt‰miseen
    # -> jos katelaskentakomennoissa ei ole 0(nollaa) niin lis‰t‰‰n seuraavat p‰ivityskomennot
    # ---> myyntihinta, myym‰l‰hinta, nettohinta
    # -> Lis‰t‰‰n komentoon where ehto
    # -> Palautetaan rakennetut komennot taulukosssa.
    
    # Lis‰t‰‰n virheet... jos ongelmia, palautetaan aina virhetaulu, jossa ei saa olla
    # merkintˆj‰ lopussa.
    
    # Ajetaan komennot tietokantaan pupe_queryll‰.
    //$virheet = array();
    //
    //echo '<pre>' . print_r($data, 1) . '</pre>';
    //
    //// Sijoitetaan tarvittavat kent‰t muuttujiin.
    //$valitutrivit = (array_key_exists("valitutrivit", $data) ? $data["valitutrivit"] : NULL);
    //$valitutkateprosentit = (array_key_exists("valitutkateprosentit", $data) ? $data["valitutkateprosentit"] : NULL);
    //$valituthinnat = (array_key_exists("valituthinnat", $data) ? $data["valituthinnat"] : NULL);
    //$valitutkeskihankintahinnat = (array_key_exists("valitutkeskihankintahinnat", $data) ? $data["valitutkeskihankintahinnat"] : NULL);
    //
    // 
    //// Tarkistetaan tyhjien taulujen varalta ja keskeytet‰‰n suoritus.
    //if ($valitutrivit == NULL || $valitutkateprosentit == NULL || $valituthinnat == NULL) {
    //    $virheet["virheilmoitus"] = "Tietojen l‰hett‰misess‰ ilmeentyi ongelmia. Muutoksia ei tehty.";
    //    return false;
    //}
    //
    //// Suodatetaan pois tyhj‰t, jos formissa on ylim‰‰r‰isi‰ kentti‰
    //$valitutrivit = array_filter($valitutrivit);
    //// Suodatetaan kateprosenteista pelk‰st‰‰n valitut rivit
    // $valitutkateprosentit = array_intersect_key($valitutkateprosentit, $valitutrivit);
    // // Suodatetaan katelaskennan m‰‰ritteist‰ vain valitut rivit
    // $valituthinnat = array_intersect_key($valituthinnat, $valitutrivit);
    // 
    // // Suodatetaan katelaskennan m‰‰ritteist‰ vain valitut rivit
    // $valitutkeskihankintahinnat = array_intersect_key($valitutkeskihankintahinnat, $valitutrivit);
    // 
    // //echo '<pre>' . print_r($valitutkateprosentit, 1) . '</pre>';
    // //echo '<pre>' . print_r($valituthinnat, 1) . '</pre>';
    // //echo '<pre>' . print_r($valitutrivit, 1) . '</pre>';
    // 
    //// M‰‰ritet‰‰n katelaskenta-arvoille oikeat tuote-taulun
    //// sarakkeiden nimet, joihin hinnat lasketaan.
    //$sallitut_katelaskentakomennot = array("m", "y", "n", "0");
    //$katelaskentakomentoja_vastaavat_taulut = array("m" => "myyntihinta",
    //                                                "y" => "myymalahinta",
    //                                                "n" => "nettohinta");
    //
    //  
    //// Luodaan update komennot
    //$updatekomennot = array();
    //
    //foreach($valitutrivit as $rivitunnus) {
    //    $update = "UPDATE tuote SET ";
    //    $rivitunnus = "'$rivitunnus'";
    //    
    //    /**
    //     * Seuraavat katelaskentakomentorivit on tehty tarkistamaan
    //     * mit‰ komentoja on annettu ja mihin riveille hinnat lasketaan.
    //     * Jos on nolla tai tyhj‰ nii skipataan.
    //     */
    //    $katelaskentakomennot = str_split($valituthinnat[$rivitunnus]);
    //    $katelaskentakomennot = array_unique($katelaskentakomennot);
    //    $katelaskentakomennot = array_filter($katelaskentakomennot, function($val) {return trim($val); });
    //    // $katelaskentakomennot = array_intersect($katelaskentakomennot, $sallitut_katelaskentakomennot);
    //     echo '<pre>' . print_r($katelaskentakomennot, 1) . '</pre>';
    //    //if(count($katelaskentakomennot) <= 0) {
    //    //    $virheet["virheilmoitus"] = "Tietoja ei p‰ivitetty, virheellinen katelaskentakomento.";
    //    //    continue;
    //    //}
    //    
    //    if(!in_array("0", $katelaskentakomennot)) {
    //      
    //        
    //        // Tarkistetaan ett‰ kateprosentti on annettu
    //        $annettu_kateprosentti = $valitutkateprosentit[$rivitunnus];
    //        
    //        // Tarkistetaan ett‰ kateprosentissa on jokin luku, muuten skipataan rivi
    //        if(count($annettu_kateprosentti) <= 0 ) {
    //            $virheet["virheilmoitus"] = "Tietoja ei p‰ivitetty, virheellinen kateprosentti.";
    //            continue;
    //        }
    //        
    //        // Tarkistetaan ett‰ kateprosentti ei ole negatiivinen, muuten skipataan.
    //         if($annettu_kateprosentti < 0 ) {
    //            $virheet["virheilmoitus"] = "Tietoja ei p‰ivitetty, negatiivinen kateprosentti.";
    //            continue;
    //         }
    //         
    //         // Tarkistetaan ett‰ kateprosentti ei ole negatiivinen, muuten skipataan.
    //         if($annettu_kateprosentti >= 100 ) {
    //            $virheet["virheilmoitus"] = "Tietoja ei p‰ivitetty, kateprosentti ei voi olla 100 tai enemm‰n.";
    //            continue;
    //         }
    //        
    //        foreach($katelaskentakomennot as $komennot) {
    //            $uusihinta = lisaa_hintaan_kate($valitutkeskihankintahinnat[$rivitunnus], $annettu_kateprosentti);
    //            $update .= "{$katelaskentakomentoja_vastaavat_taulut[$komennot]} = {$uusihinta}, ";
    //        }
    //    }
    //    
    //    $katelaskentakomennot_yhdistetty = join($katelaskentakomennot);
    //    
    //    $update .= "katelaskenta = '{$katelaskentakomennot_yhdistetty}', ";
    //    $update .= "myyntikate = '{$valitutkateprosentit[$rivitunnus]}', ";
    //    $update .= "hintamuutospvm = NOW() ";
    //    $update .= "WHERE tunnus = {$rivitunnus}";
    //    array_push($updatekomennot, $update);
    //}
    //
    //echo '<pre>' . print_r($updatekomennot, 1) . '</pre>';
    //
    //foreach($updatekomennot as $updatesql) {
    //    pupe_query($updatesql);        
    //}
    //
    return $virheet;
}



