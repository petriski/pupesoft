<?php if (!array_key_exists("ilmoitus", $template)) { ?>
    <form id="lomake-katelaskenta-hakutulokset">
        <table id="katelaskenta-hakutulokset">
            <!-- 
                TFOOT elementti taulukon viimeinen rivi, jossa toiminnot
                koko taulun tietojen käsittelemiseen yhtäaikaisesti.
            --> 
            <tfoot>
                <tr>
                    <td><input type="checkbox" checked="checked" name="valitutrivit[]" value="" /></td>
                    <td colspan="5">&nbsp;</td>
                    <td><input type="text" name="valitutkateprosentit[]" value="" /></td>
                    <td><input type="text" name="valituthinnat[]" value="" /></td>
                    <td><a href="#">Laske kaikki</a></td>
                </tr>
            </tfoot>

            <tbody>
                <tr>
                    <th>Valinta</th>
                    <th>Tuoteno</th>
                    <th>Nimitys</th>
                    <th>Osasto<br>Try</th>
                    <th>Myyntihinta</th>
                    <th>Myytävissä</th>
                    <th>Kate %</th>         
                    <th>Hinnat</th>                
                    <th>&nbsp</th>
                </tr>
                <?php
                // Käydään hakutulokset läpi.
                // $template muuttuja on alustettu tämän templaten ulkopuolella.
                foreach ($template["tuotteet"] as $avain => &$tuote) {
                    ?>

                    <tr class="aktiivi" id="rivi_<?php echo trim($tuote["tuoteno"]); ?>" data-kehahinta="<?php echo $tuote["kehahin"]; ?>">
                        <td><input type="checkbox" checked="checked" name="valitutrivit[]" value="<?php echo $tuote["tuoteno"]; ?>" /></td>
                        <td><?php echo $tuote["tuoteno"]; ?></td>
                        <td><?php echo $tuote["nimitys"]; ?></td>
                        <td><?php echo $tuote["osasto"] . "<br />" . $tuote["try"]; ?></td>
                        <td><span class="hinta" style="vertical-align: baseline;"><?php echo $tuote["myyntihinta"]; ?></span> <?php echo $template["yhtio"]["valkoodi"]; ?></td>
                        <?php hae_ja_piirra_saldo($tuote, $yhtiot, $oleastuote); // funktio katelaskenta.php -tiedostossa. ?>
                        <td><input type="text" name="valitutkateprosentit[]" value="<?php echo $tuote["myyntikate"]; ?>" /></td>
                        <td><input type="text" name="valituthinnat[]" value="<?php echo $tuote["katelaskenta"]; ?>" /></td>
                        <td><a href="#">Laske</a></td>
                    </tr>

                <?php } // Suljetaan tulosrivin foreach ?>

            </tbody>
        </table>

        <input type="submit" 
               name="submit-lomake-katelaskenta-hakutulokset" 
               id="submit-lomake-katelaskenta-hakutulokset" 
               value="Laske kaikki ja tallenna" />
    </form>
<?php } else { // array_key_exists() tarkistuksen else osio ?>
    <p><font class="message"><?php echo $template["ilmoitus"]; ?></font><p>
    <?php };  // array_key_exists() loppu ?>
    <script src="katelaskenta.js" type="text/javascript"></script>