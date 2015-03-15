// A $( document ).ready() block.
// REFACTOR: Kommentoi mitä tapahtuu tarkemmin. Laita loogiseen järjestykseen.
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

    // Seuraavassa click-eventissä asetetaan, että viimeisen rivin valitsinta
    // painaessa, asetetaan muiden rivien valitsimien arvot samoiksi. Tämän
    // avulla voidaan valita tai poistaa valinta kaikista riveistä.
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
            
            var valintaElementti = $(this).find("td:first-child input[type=checkbox]");

            if (valintaElementti.attr("checked") === "checked") {
                var hintaElementti = $(this).find("td:nth-child(5) span.hinta");
                var keskihankintahinta = $(this).data("kehahinta");
                var flag = laskeUusiHinta(hintaElementti, keskihankintahinta, myyntikate);
                
                if(flag !== false)
                    $(this).find("td:nth-child(7) input").val(myyntikate);
            }
        });
    });

    /** 
     * Funktio merkitsee elementeille checked arvon.
     * 
     * Funktio ottaa parametrina input-elementtejä taulukossa. Input-elementtien
     * tyyppien pitää olla checkbox. 
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
        $(hintaElementti).empty().text(uusiHinta.toFixed(2));
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
        var hintaElementti = $(this).find("td:nth-child(5) span.hinta");
        var keskihankintahinta = $(this).data("kehahinta");
        var myyntikateElementti = $(this).find("td:nth-child(7) input");
        $(this).find("td:last-child a").on("click", function (event) {
            event.preventDefault();
            var myyntikate = myyntikateElementti.val();
            laskeUusiHinta(hintaElementti, keskihankintahinta, myyntikate);
        });
        $(myyntikateElementti).on("focusout", function (event) {
            event.preventDefault();
            var myyntikate = myyntikateElementti.val();
            laskeUusiHinta(hintaElementti, keskihankintahinta, myyntikate);
        });
    });
});