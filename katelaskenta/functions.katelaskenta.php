<?php
/**
 * Funktio laskee uuden hinnan kateprosentilla.
 *
 * Kateprosentti annetaan prosenteissa, ei desimaaleissa.
 * Voi olla 0-100 väliltä.
 */
function lisaa_hintaan_kate($keskihankintahinta, $kateprosentti) {

    $keskihankintahinta = (float)$keskihankintahinta;
    $kateprosentti = (float)$kateprosentti;

    return $keskihankintahinta / ( 1 - ( $kateprosentti / 100 ) );
}

/**
 * Funktio tarkistaa, että kateprosentti on sallitulla välillä.
 *
 * Kateprosentti ei voi olla yli 100 tai alle 0. Funktio palauttaa
 * true, jos prosentissa ei ole mitään vikaa.
 */
function tarkista_kateprosentti($kateprosentti) {
    if(!is_numeric($kateprosentti))
        return false;
    
    $kateprosentti = (float)$kateprosentti;
    if($kateprosentti >= 100 || $kateprosentti < 0)
        return false;
    
    return true;
}

/**
 * Funktio siivoaa annetut laskentakomennot.
 *
 * Annetut komennot käydään merkkikerralla läpi ja niistä siivotaan
 * ylimääräiset pois. Lisäksi tarkistetaan, että komennot ova olleet
 * sallittujen joukossa.
 *
 * Palautetaan komennot merkkijonoina.
 */
function siivoa_laskentakomennot($komennot) {
    // Määritetään ne komennot, jotka ovat sallittuja.
    $sallitut_komennot = array("m", "y", "n", 0);
    // Pilkotaan merkkijono taulukoksi, jossa jokainen
    // merkki on oma solunsa.
    $komennot = str_split($komennot);
    // Poistetaan taulukosta duplicaatit, jotta jokaista komentoa
    // jää vain yksi kpl.
    $komennot = array_unique($komennot);
    // Poistetaan komennoista merkit, joita ei tunnisteta
    // sallituiksi komennoiksi.
    $komennot = array_intersect($komennot, $sallitut_komennot);
    return join("", $komennot);
}

/**
 * Funktio tarkistaa syötteet, joita katelaskentaohjelma lähettää.
 *
 * Funktio on kooste pienemmistä toimenpiteistä. Jos virheitä ilmenee
 * tarkistusten aikana, lisätään ongelmarivit virhe-taulukkoon.
 * Lopuksi palautetaan taulukko, jossa on kaksi sisäkkäistä taulukkoa.
 * "kunnossa" -taulukko sisältää tarkistuksista läpäisseet syötteet
 * ja "Virheelliset" -taulukko ne rivit, joissa oli ongelmia.
 */
function tarkista_katelaskenta_syotteet($taulukko) {
    // Luodaan uusi virhe-taulukko, johon kerätään mahdolliset
    // virheelliset rivit.
    $virherivit = array();
    
    // Käydään läpi valitut rivit käyttäjän syötteistä.
    foreach($taulukko["kunnossa"] as &$rivi) {
        
        // Jos kateprosentti on virheellinen, lisätään rivi
        // virhe-taulukkoon ja hypätään seuraavaan riviin.
        if(!tarkista_kateprosentti($rivi[1])) {
            $virherivit["'" . $rivi[0] . "'"] = $rivi;
            continue;
        }
        
        // Siivotaan lasketakomennot ylimääräisistä merkeistä
        $rivi[3] = siivoa_laskentakomennot($rivi[3]);
        // Jos merkkijono siivoamisen jälkeen on tyhjä, lisätään
        // rivi virhe-taulukkoon.
        if(trim($rivi[3]) == "") {
            $virherivit["'" . $rivi[0] . "'"] = $rivi;
            continue;
        }
    }
    
    // Tallennetaan virheelliset rivit omaan alkioonsa.
    $taulukko["virheelliset"] = $virherivit;
    
    // Siivotaan alkuperäisistä riveistä virherivit
    $taulukko["kunnossa"] = array_diff_key($taulukko["kunnossa"], $virherivit);
    
    // Palautetaan taulukko, joka pitää sisällään kunnossa olevat
    // ja virheelliset rivit.
    return $taulukko;
}

function luo_katelaskenta_update_komennot($taulukko) {
    // Luodaan update-komennoille taulukko, johon kaikki komennot
    // kootaan.
    $update_komennot = array();
    $sql_komento_alku = "UPDATE tuote SET ";
    $sql_komento_loppu = "WHERE tunnus = ";
    
    // Käydään läpi jokainen valittu tuoterivi ja muodostetaan
    // ehtojen mukaan oikeanlainen update-komento.
    foreach($taulukko as $rivi) {
        
        $rivin_tunnus = $rivi[0];
        $rivin_kateprosentti = $rivi[1];
        $rivin_keskihankintahinta = $rivi[2];
        $rivin_komennot = $rivi[3];
        
        $update_kysely = "";
        $update_kysely .= $sql_komento_alku;
        
        // Jos komennossa on 0 merkki jossakin kohti, ei hintamuutoksia
        // tehdä. Tallennetaan vain komento talteen tietokantaan.
        if(mb_strrchr($rivin_komennot, 0)) {
            $update_kysely .= "tuote.katelaskenta = {$rivin_komennot} ";    
        } else {
            // Jos komennossa m, lasketaan myyntihinta.
            if(mb_strrchr($rivin_komennot, "m")) {
                $uusi_hinta = lisaa_hintaan_kate($rivin_keskihankintahinta, $rivin_kateprosentti);
                $update_kysely .= "tuote.myyntihinta = {$uusi_hinta}, ";
            }
            // Jos komennossa y, lasketaan myymalahinta.
            if(mb_strrchr($rivin_komennot, "y")) {
                $uusi_hinta = lisaa_hintaan_kate($rivin_keskihankintahinta, $rivin_kateprosentti);
                $update_kysely .= "tuote.myymalahinta = {$uusi_hinta}, ";
            }
            // Jos komennossa n, lasketaan nettohinta.
            if(mb_strrchr($rivin_komennot, "n")) {
                $uusi_hinta = lisaa_hintaan_kate($rivin_keskihankintahinta, $rivin_kateprosentti);
                $update_kysely .= "tuote.nettohinta = {$uusi_hinta}, ";
            }
            
            // Lisätään kyselyyn pakolliset kentät, jotka tulee jokaiseen
            // komennon lopuksi mukaan.
            $update_kysely .= "tuote.katelaskenta = {$rivin_komennot}, ";
            $update_kysely .= "tuote.myyntikate = {$rivin_kateprosentti}, ";
            $update_kysely .= "tuote.hintamuutospvm = NOW() ";
        }
        // Kyselyn where -ehdon lisääminen.
        $update_kysely .= $sql_komento_loppu . $rivin_tunnus;
        
        // Lisätään valmisteltu kysely taulukkoon.
        array_push($update_komennot, $update_kysely);
    }
    
    return $update_komennot;
}

function tallenna_valitut_katemuutokset($data) {
    
    // Luodaan yhdistelmätaulukko, jossa eritellään virheelliset
    // rivit ja kunnossa olevat. Virheelliset taulukkoon lisätään
    // ne rivit, joiden syötteissä prosessin aikana ilmenee ongelmia.
    $yhdistetyt_tuoterivit = array();
    $yhdistetyt_tuoterivit["virheelliset"] = array();
    $yhdistetyt_tuoterivit["kunnossa"] = array();

    // Siivotaan valitut tuoterivit tyhjistä avain => arvo pareista
    $valitut_tuoterivit = array_filter($data["valitutrivit"]);
    
    // Siivotaan valitut kateprosentit valittujen tuoterivien
    // perusteella. Jäljelle jää vain valitut tuotteet taulukosta.
    $valitut_tuoterivit_kateprosentit = array_intersect_key($data["valitutkateprosentit"], $valitut_tuoterivit);
    
    // Siivotaan valitut keskihankintahinnat valittujen tuoterivien
    // perusteella. Jäljelle jää vain valitut tuotteet taulukosta.
    $valitut_tuoterivit_keskihankintahinnat = array_intersect_key($data["valitutkeskihankintahinnat"], $valitut_tuoterivit);    
    
    // Siivotaan valitut keskihankintahinnat valittujen tuoterivien
    // perusteella. Jäljelle jää vain valitut tuotteet taulukosta.
    $valitut_tuoterivit_laskentakomennot = array_intersect_key($data["valituthinnat"], $valitut_tuoterivit);
    
    // Array_merge_recursive -funktiolla taulut yhdistetään yhdeksi kokonaisuudeksi.
    // Funktio käyttää taulukon avaimia, joilla tiedot koostetaan yksinkertaisemmaksi
    // taulukoksi.
    //
    // Tulevan taulun rakenne on seuraava:
    // [avain] => [tunnus, kateprosentti, keskihankintahinta, komento]
    $yhdistetyt_tuoterivit["kunnossa"] = array_merge_recursive($valitut_tuoterivit,
                                                   $valitut_tuoterivit_kateprosentit,
                                                   $valitut_tuoterivit_keskihankintahinnat,
                                                   $valitut_tuoterivit_laskentakomennot);
    

    // Tarkistetaan syötteet ja funktio palauttaa taulukon, jossa
    // on eritelty kunnossa olleet rivit virheellisistä.
    $yhdistetyt_tuoterivit = tarkista_katelaskenta_syotteet($yhdistetyt_tuoterivit);
    
    // Update_komennot -taulukko sisältää valmistellus update -sql komennot
    // Komennot luodaan erillisessä funktiossa, jonne annetaan parametrina
    // jo tarkistetut tiedot. Virheellisille riveille ei tehdä mitään.
    $update_komennot = array();
    $update_komennot = luo_katelaskenta_update_komennot($yhdistetyt_tuoterivit["kunnossa"]);
    
    // Ajetaan päivityskomennot tietokantaan.
    foreach($updatekomennot as $updatesql) {
        pupe_query($updatesql);        
    }
    
    // Palautetaan virheelliset tuotteet.
    // count() == 0 jos virheitä ei ollut.
    return $yhdistetyt_tuoterivit["virheelliset"];
}