/*
 * scripts.katelaskenta.php
 *
 * Tiedosto sis‰lt‰‰ javascript koodit k‰yttˆliittym‰n toimintoja varten,
 * jotka sijaitsevat template.katelaskenta.php tiedostossa. Tiedoston
 * alussa esitell‰‰n k‰ytett‰vi‰ muuttujia ja funktioita.
 */
$(document).ready(function () {

    // Esitell‰‰n muuttujat 
    var tuoterivitTaulukko; // koko taulukko
    var tuoterivit; // kaikki taulukon rivit
    var footerRivi; // taulukon footer rivi
    var tuoterivitCheckboxes; // tuoterivien checkboxit
    var footerCheckbox; // taulukon footer checkbox
    var footerLaskeKaikki;  // Footer osion "laske kaikki" nappi
    var kateMyyntihintaSarake;
    var kateMyymalahintaSarake;
    var kateNettohintaSarake;
    var myyntihintaSarake;
    var myymalahintaSarake;
    var nettohintaSarake;
    var tuoteriviCheckboxSarake;
    var tuoteriviLaskeNappiSarake;

    // Esitell‰‰n funktiot, toteutukset lˆytyv‰t alapuolelta.
    var alustaMuuttujat;
    var onkoVirheellinenMyyntikate;
    var lisaaHintaanKate;
    
    /**
     * Funtion toiminto on vain alustaa tavittavat muuttujat, joita
     * eri toiminnallisuuksissa k‰ytet‰‰n. N‰in elementtien hakuja
     * on helpompi muuttaa, jos k‰yttˆliittym‰ss‰ muuttuu jokin.
     */
    var alustaMuuttujat = function() {
        tuoterivitTaulukko = $("#katelaskenta-hakutulokset");
        tuoterivit = tuoterivitTaulukko.find("tbody tr");
        footerRivi = tuoterivitTaulukko.find("tfoot tr");
        tuoterivitCheckboxes = tuoterivitTaulukko.find("tbody tr td:nth-child(2) input[type=checkbox]");
        footerCheckbox = tuoterivitTaulukko.find("tfoot tr td:first-child input[type=checkbox]");
        footerLaskeKaikki = tuoterivitTaulukko.find("tfoot tr td:last-child a");
        
        kateMyyntihintaSarake = "td:nth-child(7) input";
        kateMyymalahintaSarake = "td:nth-child(9) input";
        kateNettohintaSarake = "td:nth-child(11) input";
        myyntihintaSarake = "td:nth-child(8) span.hinta";
        myymalahintaSarake = "td:nth-child(10) span.hinta";
        nettohintaSarake = "td:nth-child(12) span.hinta";
        tuoteriviCheckboxSarake = "td:nth-child(2) input[type=checkbox]";
        tuoteriviLaskeNappiSarake = "td:last-child a";
    }
    
    /**
     * Funktio tarkistaa annetun myyntikatteen, jotta laskutoimitukset
     * voidaan suorittaa.
     *
     * Palauttaa false, jos virhe lˆytyy.
     */
    var onkoVirheellinenMyyntikate = function(myyntikate) {
        if(myyntikate === "")
            return false;
        if (isNaN(myyntikate)) 
            return false;
        if (myyntikate >= 100 || myyntikate < 0) 
            return false;
        return true;
    };
    
    /**
     * Funktio lis‰‰ annettuun hintaan annetun katteen.
     *
     * Kate annetaan prosentteina, eik‰ desimaaleissa. Desimaaleja
     * voi k‰ytt‰‰ prosenteissa. Palauttaa hinnan laskutoimituksen
     * j‰lkeen. Jos syˆtetyiss‰ tiedoissa on virhe, palautetaan false
     * ja n‰ytet‰‰n alert-ikkuna.
     */
    var lisaaHintaanKate = function(keskihankintahinta, myyntikate) {
        var floatKeskihankintaHinta = parseFloat(keskihankintahinta);
        var floatMyyntikate = parseFloat(myyntikate);
        
        if (isNaN(floatKeskihankintaHinta)) {
            alert("Virheellinen keskihankintahinta.");
            return false;
        }
        
        if(!onkoVirheellinenMyyntikate(floatMyyntikate)) {
            alert("Virheellinen myyntikate.");
            return false;
        }

        return floatKeskihankintaHinta / (1 - (floatMyyntikate / 100));
    };
    
   
    
    /**
     * T‰st‰ l‰htien ohjelmakoodissa m‰‰ritell‰‰n elementeille niiden
     * toimintalogiikka aikaisemmin esitettyjen funktioiden avulla.
     * Kaikkien funktioiden ja yleisten muuttujien kuuluisi olla esitetty
     * ennen seuraavia toimenpiteit‰.
     */
    
    // Kutsutaan muuttujien alustus.
    alustaMuuttujat();
    
    // Lis‰t‰‰n jokaiselle tuoterivin laske-painikkeellle toimintalogiikka.
    // Laske painike laskee annetun kateprosentin mukaan uuden myyntihinnan.
    $.each(tuoterivit, function () {
        var keskihankintahinta = $(this).data("kehahinta");

        var myyntikate = $(this).find(kateMyyntihintaSarake);
        var myyntihintaElementti = $(this).find(myyntihintaSarake);
        
        var myymalakate = $(this).find(kateMyymalahintaSarake);
        var myymalahintaElementti = $(this).find(myymalahintaSarake);
        
        var nettokate = $(this).find(kateNettohintaSarake);
        var nettohintaElementti = $(this).find(nettohintaSarake);
        
        $(this).find(tuoteriviLaskeNappiSarake).on("click", function (event) {
            event.preventDefault();
            var myyntikateArvo = myyntikate.val();
            var uusiMyyntihinta = lisaaHintaanKate(keskihankintahinta, myyntikateArvo);
            var uusiMyymalahinta = lisaaHintaanKate(keskihankintahinta, myymalakate.val());
            var uusiNettohinta = lisaaHintaanKate(keskihankintahinta, nettokate.val());
            
            if(uusiMyyntihinta !== false) {
                var htmlUusiHinta = $("<font></font>")
                                        .css("color", "red")
                                        .css("font-weight", "bold")
                                        .text(uusiMyyntihinta.toFixed(2));
                $(myyntihintaElementti).empty().html(htmlUusiHinta);
            }
            
            if(uusiMyymalahinta !== false) {
                var htmlUusiHinta = $("<font></font>")
                                        .css("color", "red")
                                        .css("font-weight", "bold")
                                        .text(uusiMyymalahinta.toFixed(2));
                $(myymalahintaElementti).empty().html(htmlUusiHinta);
            }
            
            if(uusiNettohinta !== false) {
                var htmlUusiHinta = $("<font></font>")
                                        .css("color", "red")
                                        .css("font-weight", "bold")
                                        .text(uusiNettohinta.toFixed(2));
                $(nettohintaElementti).empty().html(htmlUusiHinta);
            }
        });
        // jos otetaan k‰yttˆˆn, pit‰‰ est‰‰, ettei 0 katteet p‰ivity aina.
        //$(myyntikate).on("focusout", function (event) {
        //    event.preventDefault();
        //    var myyntikateArvo = myyntikate.val();
        //    lisaaHintaanKate(hinta, keskihankintahinta, myyntikateArvo);
        //});
    });
    
    // Lis‰t‰‰n taulukon viimeisen rivin valintaruudulle toimintalogiikka.
    // Ruutua klikkaamalla joko valitaan kaikki tai poistetaan valinta
    // kaikista ruuduista.
    footerCheckbox.on("click", function (event) {
            if ($(this).attr("checked") === "checked") {
                 $.each(tuoterivitCheckboxes, function () {
                    $(this).prop("checked", true);
                });
            } else {
                 $.each(tuoterivitCheckboxes, function () {
                    $(this).prop("checked", false);
                });
            }
        });
    
    // Lis‰t‰‰n taulukon viimeisen rivin "laske kaikki" -painikkeelle
    // toimintalogiikka. Painiketta painaessa lasketaan viimeisen rivin
    // arvoilla uusi hinta ja samat arvot m‰‰ritet‰‰n taulukon kaikille
    // muille tuoteriveille.
     footerLaskeKaikki.on("click", function (event) {
        event.preventDefault();
        var myyntikate = footerRivi.find("td:nth-child(3) input").val();
        var laskentakomennot = footerRivi.find("td:nth-child(4) input").val();
        
        if(!onkoVirheellinenMyyntikate(myyntikate)) {
            return true;
        }
        
        // K‰yd‰‰n jokainen rivi l‰pi ja asetetaan uusi hinta, jos hinta
        // ei ole virheellinen.
        $.each(tuoterivit, function () {
            var valintaElementti = $(this).find("td:nth-child(2) input[type=checkbox]");

            if (valintaElementti.attr("checked") === "checked") {
                var hintaElementti = $(this).find("td:nth-child(6) span.hinta");
                var keskihankintahinta = $(this).data("kehahinta");
                var uusiHinta = lisaaHintaanKate(keskihankintahinta, myyntikate);
                if(uusiHinta !== false) {
                    // v‰ritet‰‰n uusi hinta eri v‰rill‰, jotta muutos erottuu.
                    var htmlUusiHinta = "<font style=\"color: red; font-weight: bold;\">" + uusiHinta.toFixed(2) + "</font>";
                    $(hintaElementti).empty().html(htmlUusiHinta);
                    $(this).find("td:nth-child(8) input").val(myyntikate);
                    $(this).find("td:nth-child(9) input").val(laskentakomennot);
                }
            }
        });
    });
    
});
