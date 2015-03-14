<form id="lomake-katelaskenta-hakutulokset">
    <table>
        <tfoot>
            <tr>
                <td><input type="checkbox" checked="checked" name="valitutrivit[]" value="" /></td>
                <td colspan="5">&nbsp;</td>
                <td><input type="text" name="valitutkateprosentit[]" value="" /></td>
                <td><input type="text" name="valituthinnat[]" value="" /></td>
                <td><button>Laske kaikki</button></td>
            </tr>
        </tfoot>
        <tbody>
            <tr>
                <th>Valinta</th>
                <th>Tuoteno</th>
                <th>Nimitys</th>
                <th>Osasto<br>Try</th>
                <th>Hinta</th>
                <th>Myyt‰viss‰</th>
                <th>Kate %</th>         
                <th>Hinnat</th>                
                <th>&nbsp</th>
            </tr>
            <?php 
                // K‰yd‰‰n hakutulokset l‰pi.
                foreach ($rows as $row_key => &$row) { // $rows muuttuja tulee templaten ulkopuolelta 
                
                // REFACTOR: Mihin $vari muuttujaa k‰ytet‰‰n?
                $vari = "";
                
                // Laajennetaan tuotteen nimityst‰ "korvaa tuotteen" merkinn‰ll‰
                if ($verkkokauppa == "" and isset($row["mikakorva"])) {
                  $vari = 'spec';
                  $row["nimitys"] .= "<br> * ".t("Korvaa tuotteen").": $row[mikakorva]";
                }

                // Merkit‰‰n nimitykseen "poistuva"
                if ($hae_ja_selaa_row['selite'] != 'B' and $verkkokauppa == "" and strtoupper($row["status"]) == "P") {
                  $vari = "tumma";
                  $row["nimitys"] .= "<br> * ".t("Poistuva tuote");
                }

                // REFACTOR: Jos j‰‰ yli niin voi poistaa?
                if ($yhtiorow['livetuotehaku_poistetut'] == 'Y' and ($row["epakurantti25pvm"] != 0000-00-00 or $row["epakurantti50pvm"] != 0000-00-00 or $row["epakurantti75pvm"] != 0000-00-00 or $row["epakurantti100pvm"] != 0000-00-00)) {
                  $vari = 'spec';
                }
      
                /**
                 * Haetaan tuotenumeron perusteella tuotteen lis‰tiedot ja
                 * list‰‰n tulokset nimitykset sarakkeen arvoon. 
                 */
                $tuotteen_lisatiedot = tuotteen_lisatiedot($row["tuoteno"]);
                $row["nimitys"] .= $tuotteen_lisatiedot_arvo[kentta] . "&raquo;" . $tuotteen_lisatiedot_arvo[selite];
                
                /**
                 * Haetaan tuotteen hinta. 
                 * 
                 * Funktiokutsu sama kuin tuote_selaus_haku.php -tiedostossa 
                 * oleva piirra_hinta(). Muokattu funktiota siten, ett‰ 
                 * echotetun sarakkeen tilasta palauttaa hinnan.
                 * 
                 * hae_hinta() -funktio sijaitsee katelaskenta.php -tiedostossa. 
                 */
                $rowhinta = laske_hinta($row, $oleasrow, $valuurow, $vari, $classmidl, $hinta_rajaus, $poistetut,
  $lisatiedot);
                
            ?>
            <tr class="aktiivi">
                <td><input type="checkbox" checked="checked" name="valitutrivit[]" value="<?php echo $row["tuoteno"]; ?>" /></td>
                <td><?php echo $row["tuoteno"]; ?></td>
                <td><?php echo $row["nimitys"]; ?></td>
                <td><?php echo $row["osasto"] . "<br />" . $row["try"]; ?></td>
                <td><?php echo $rowhinta; ?></td>
                <?php hae_ja_piirra_saldo($row, $yhtiot, $oleasrow); // funktio katelaskenta.php -tiedostossa. ?>
                <td><input type="text" name="valitutkateprosentit[]" value="" /></td>
                <td><input type="text" name="valituthinnat[]" value="" /></td>
                <td><button>Laske</button></td>
            </tr>
            <?php } // Suljetaan tulosrivin foreach ?>
        </tbody>
    </table>
    <input type="submit" 
           name="submit-lomake-katelaskenta-hakutulokset" 
           id="submit-lomake-katelaskenta-hakutulokset" 
           value="Laske kaikki ja tallenna" />
</form>