<?php
/*
 * scripts.katelaskenta.php
 *
 * Tiedosto sis‰lt‰‰ javascript koodit k‰yttˆliittym‰n toimintoja varten,
 * jotka sijaitsevat template.katelaskenta.php tiedostossa.
 */
?>
 <script type="text/javascript">
$(document).ready(function () {

    // Haetaan muuttujaan hakutulosten taulukko
    var hakutuloksetTaulukko = $("#katelaskenta-hakutulokset");

    // Haetaan muuttujaan taulukon viimeisen rivin valitsin,
    // joka ajaa muutokset aina kaikkiin riveihin.
    var valitseKaikkiCheckbox = hakutuloksetTaulukko.find("tfoot tr td:first-child input[type=checkbox]");

    // Haetaan muuttujaan kaikkien tulosrivien valinta sarakkeen checkbox
    var kaikkiValitsimetCheckboxes = hakutuloksetTaulukko.find("tbody tr td:first-child input[type=checkbox]");

    var kaikkiTuoterivit = hakutuloksetTaulukko.find("tbody tr");    
    var viimeinenTaulukonRivi = hakutuloksetTaulukko.find("tfoot tr");
    var laskeKaikkiNappi = hakutuloksetTaulukko.find("tfoot tr td:last-child a");

    // Seuraavassa click-eventiss‰ asetetaan, ett‰ viimeisen rivin valitsinta
    // painaessa, asetetaan muiden rivien valitsimien arvot samoiksi. T‰m‰n
    // avulla voidaan valita tai poistaa valinta kaikista riveist‰.
    valitseKaikkiCheckbox.on("click", function (event) {
        if ($(this).attr("checked") === "checked") {
            merkitseKaikkiTuotteet(kaikkiValitsimetCheckboxes, true);
        } else {
            merkitseKaikkiTuotteet(kaikkiValitsimetCheckboxes, false);
        }

    });

    laskeKaikkiNappi.on("click", function (event) {
        event.preventDefault();
        var myyntikate = viimeinenTaulukonRivi.find("td:nth-child(3) input").val();
        
        if(onkoVirheellinenMyyntikate(myyntikate)) {
            return true;
        }
        
        $.each(kaikkiTuoterivit, function () {
            
            var valintaElementti = $(this).find("td:nth-child(2) input[type=checkbox]");

            if (valintaElementti.attr("checked") === "checked") {
                var hintaElementti = $(this).find("td:nth-child(6) span.hinta");
                var keskihankintahinta = $(this).data("kehahinta");
                var flag = laskeUusiHinta(hintaElementti, keskihankintahinta, myyntikate);
                
                if(flag !== false)
                    $(this).find("td:nth-child(8) input").val(myyntikate);
            }
        });
    });

    /** 
     * Funktio merkitsee elementeille checked arvon.
     * 
     * Funktio ottaa parametrina input-elementtej‰ taulukossa. Input-elementtien
     * tyyppien pit‰‰ olla checkbox. 
     * 
     * Toinen value parametri voi olla true tai false. Jos arvo on true, niin
     * kaikille elementeille asetetaan checked arvo.
     */
    var merkitseKaikkiTuotteet = function (elementit, value) {
        $.each(elementit, function () {
            $(this).prop("checked", value);
        });
    };

    var laskeUusiHinta = function (hintaElementti, keskihankintahinta, myyntikate) {
        var uusiHinta = 0;
        var floatKeskihankintaHinta = parseFloat(keskihankintahinta);
        var floatMyyntikate = parseFloat(myyntikate);
        
        if (isNaN(floatKeskihankintaHinta)) {
            alert("Virheellinen keskihankintahinta.");
            return false;
        }
        
        if(onkoVirheellinenMyyntikate(floatMyyntikate)) {
            alert("Virheellinen myyntikate.");
            return false;
        }
        uusiHinta = floatKeskihankintaHinta / (1 - (floatMyyntikate / 100));
        var htmlUusiHinta = "<font style=\"color: red; font-weight: bold;\">" + uusiHinta.toFixed(2) + "</font>";
        $(hintaElementti).empty().html(htmlUusiHinta);
        return true;
    };
    
    var onkoVirheellinenMyyntikate = function(myyntikate) {
        if(myyntikate === "")
            return true;
        if (isNaN(myyntikate)) 
            return true;
        if (myyntikate >= 100) 
            return true;
        return false;
    };


    $.each(kaikkiTuoterivit, function () {
        var hintaElementti = $(this).find("td:nth-child(6) span.hinta");
        var keskihankintahinta = $(this).data("kehahinta");
        var myyntikateElementti = $(this).find("td:nth-child(8) input");
        $(this).find("td:last-child a").on("click", function (event) {
            event.preventDefault();
            var myyntikate = myyntikateElementti.val();
            laskeUusiHinta(hintaElementti, keskihankintahinta, myyntikate);
        });
        // jos otetaan k‰yttˆˆn, pit‰‰ est‰‰, ettei 0 katteet p‰ivity aina.
        //$(myyntikateElementti).on("focusout", function (event) {
        //    event.preventDefault();
        //    var myyntikate = myyntikateElementti.val();
        //    laskeUusiHinta(hintaElementti, keskihankintahinta, myyntikate);
        //});
    });
});
</script>