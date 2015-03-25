/*
 * scripts.katelaskenta.php
 *
 * Tiedosto sisältää javascript koodit käyttöliittymän toimintoja varten,
 * jotka sijaitsevat template.katelaskenta.php tiedostossa. Tiedoston
 * alussa esitellään käytettäviä muuttujia ja funktioita.
 */
$(document).ready(function () {

    // Esitellään muuttujat.
    // Kaikki muuttujat alustetaan funktiossa myöhemmässä
    // vaiheessa. 
    var tuoterivitTaulukko; // koko taulukko
    var tuoterivit; // kaikki taulukon rivit
    var tuoterivitCheckboxes; // tuoterivien checkboxit
    var footerRivi; // taulukon footer rivi
    var footerCheckbox; // taulukon footer checkbox
    var footerLaskeKaikki;  // Footer osion
    
    // Kaikki sarake-päätteiset muuttujat ovat jqueryn
    // selectoreita kertomaan, missä oikea sarake on
    // mikäli käyttöliittymää mennään muuttamaan.
    var footerKateMyyntihintaSarake; 
    var footerKateMyymalahintaSarake; 
    var footerKateNettohintaSarake; 
    var tuoteriviCheckboxSarake;
    var tuoteriviLaskeNappiSarake;
    var kateMyyntihintaSarake;
    var kateMyymalahintaSarake;
    var kateNettohintaSarake;
    var myyntihintaSarake;
    var myymalahintaSarake;
    var nettohintaSarake;
    
    
    // Esitellään funktiot.
    // Toteutukset löytyvät alapuolelta.
    var alustaMuuttujat;
    var onkoVirheellinenMyyntikate;
    var lisaaHintaanKate;
    var asetaUusiHinta;
    
    /**
     * Funtion toiminto on vain alustaa tavittavat muuttujat, joita
     * eri toiminnallisuuksissa käytetään. Näin elementtien hakuja
     * on helpompi muuttaa, jos käyttöliittymässä muuttuu jokin.
     */
    var alustaMuuttujat = function() {
        tuoterivitTaulukko = $("#katelaskenta-hakutulokset");
        tuoterivit = tuoterivitTaulukko.find("tbody tr");
        tuoterivitCheckboxes = tuoterivitTaulukko.find("tbody tr td:nth-child(2) input[type=checkbox]");
        footerRivi = tuoterivitTaulukko.find("tfoot tr");
        footerCheckbox = tuoterivitTaulukko.find("tfoot tr td:first-child input[type=checkbox]");
        footerLaskeKaikki = tuoterivitTaulukko.find("tfoot tr td:last-child a");
        
        footerKateMyyntihintaSarake = "td:nth-child(4) input";
        footerKateMyymalahintaSarake = "td:nth-child(6) input";
        footerKateNettohintaSarake = "td:nth-child(8) input";
        kateMyyntihintaSarake = "td:nth-child(8) input";
        kateMyymalahintaSarake = "td:nth-child(10) input";
        kateNettohintaSarake = "td:nth-child(12) input";
        myyntihintaSarake = "td:nth-child(7) span.hinta";
        myymalahintaSarake = "td:nth-child(9) span.hinta";
        nettohintaSarake = "td:nth-child(11) span.hinta";
        tuoteriviCheckboxSarake = "td:nth-child(2) input[type=checkbox]";
        tuoteriviLaskeNappiSarake = "td:last-child a";
    }
    
    
    /**
     * Funktio tarkistaa annetun myyntikatteen, jotta laskutoimitukset
     * voidaan suorittaa.
     *
     * Palauttaa false, jos virhe löytyy.
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
     * Funktio lisää annettuun hintaan annetun katteen.
     *
     * Kate annetaan prosentteina, eikä desimaaleissa. Desimaaleja
     * voi käyttää prosenteissa. Palauttaa hinnan laskutoimituksen
     * jälkeen. Jos syötetyissä tiedoissa on virhe, palautetaan false
     * ja näytetään alert-ikkuna.
     */
    var lisaaHintaanKate = function(keskihankintahinta, myyntikate) {
        var floatKeskihankintaHinta = parseFloat(keskihankintahinta);
        var floatMyyntikate = parseFloat(myyntikate);
        
        if (isNaN(floatKeskihankintaHinta)) {
            alert("Virheellinen keskihankintahinta.");
            return false;
        }
        
        if(!onkoVirheellinenMyyntikate(floatMyyntikate)) {
            alert("Virheellinen kate. Katekenttä ei voi olla tyhjä ja katteen pitää olla 0-100 välillä.");
            return false;
        }

        return floatKeskihankintaHinta / (1 - (floatMyyntikate / 100));
    };
    
    /**
     * Funktio asettaa elementtiin uuden hinnan.
     *
     * Hintaan määritetään käärin elementti eli tässä tapauksessa
     * <font>. Hinta värjätään punaiseksi ja asetetaan annettuun
     * elementtiin.
     */
    var asetaUusiHinta = function(hinta, kohdeElementti) {
        var htmlUusiHinta = $("<font></font>")
                                .css("color", "red")
                                .css("font-weight", "bold")
                                .text(hinta.toFixed(2));
        $(kohdeElementti).empty().html(htmlUusiHinta);
    };
    
    /**
     * Tästä lähtien ohjelmakoodissa määritellään elementeille niiden
     * toimintalogiikka aikaisemmin esitettyjen funktioiden avulla.
     * Kaikkien funktioiden ja yleisten muuttujien kuuluisi olla esitetty
     * ennen seuraavia toimenpiteitä.
     */
    
    // Kutsutaan muuttujien alustus.
    alustaMuuttujat();
    
    // Lisätään jokaiselle tuoterivin laske-painikkeellle toimintalogiikka.
    // Laske painike laskee annetun kateprosentin mukaan uuden hinnan.
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
            var uusiMyyntihinta = lisaaHintaanKate(keskihankintahinta, myyntikate.val());
            var uusiMyymalahinta = lisaaHintaanKate(keskihankintahinta, myymalakate.val());
            var uusiNettohinta = lisaaHintaanKate(keskihankintahinta, nettokate.val());
            
            if(uusiMyyntihinta !== false && myyntikate.val() > 0) {
                asetaUusiHinta(uusiMyyntihinta, myyntihintaElementti);
            }
            
            if(uusiMyymalahinta !== false && myymalakate.val() > 0) {
                asetaUusiHinta(uusiMyymalahinta, myymalahintaElementti);
            }
            
            if(uusiNettohinta !== false && nettokate.val() > 0) {
                asetaUusiHinta(uusiNettohinta, nettohintaElementti);
            }
        });
    });
    
    // Lisätään taulukon viimeisen rivin valintaruudulle toimintalogiikka.
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
    
    // Lisätään taulukon viimeisen rivin "laske kaikki" -painikkeelle
    // toimintalogiikka. Painiketta painaessa lasketaan uusi hinta ja
    // samat arvot määritetään taulukon kaikille muille tuoteriveille.
     footerLaskeKaikki.on("click", function (event) {
        event.preventDefault();
        
        var myyntikate = tuoterivitTaulukko.find("tfoot tr").find(footerKateMyyntihintaSarake).val();
        var myymalakate = tuoterivitTaulukko.find("tfoot tr").find(footerKateMyymalahintaSarake).val();
        var nettokate = tuoterivitTaulukko.find("tfoot tr").find(footerKateNettohintaSarake).val();
        
        if(!onkoVirheellinenMyyntikate(myyntikate)) {
            alert("Virheellinen kate. Katekenttä ei voi olla tyhjä ja katteen pitää olla 0-100 välillä.");
            return true;
        }
        if(!onkoVirheellinenMyyntikate(myymalakate)) {
            alert("Virheellinen kate. Katekenttä ei voi olla tyhjä ja katteen pitää olla 0-100 välillä.");
            return true;
        }
        if(!onkoVirheellinenMyyntikate(nettokate)) {
            alert("Virheellinen kate. Katekenttä ei voi olla tyhjä ja katteen pitää olla 0-100 välillä.");
            return true;
        }
        
        // Käydään jokainen rivi läpi ja asetetaan uusi hinta, jos hinta
        // ei ole virheellinen.
        $.each(tuoterivit, function () {
            var valintaElementti = $(this).find(tuoteriviCheckboxSarake);

            if (valintaElementti.attr("checked") === "checked") {
                var myyntihintaElementti = $(this).find(myyntihintaSarake);
                var myymalahintaElementti = $(this).find(myymalahintaSarake);
                var nettohintaElementti = $(this).find(nettohintaSarake);
                var keskihankintahinta = $(this).data("kehahinta");
                
                var uusiMyyntihinta = lisaaHintaanKate(keskihankintahinta, myyntikate);
                var uusiMyymalahinta = lisaaHintaanKate(keskihankintahinta, myymalakate);
                var uusiNettohinta = lisaaHintaanKate(keskihankintahinta, nettokate);
               
                if(uusiMyyntihinta !== false && myyntikate > 0) {
                    asetaUusiHinta(uusiMyyntihinta, myyntihintaElementti);
                }
                
                if(uusiMyymalahinta !== false && myymalakate > 0) {
                    asetaUusiHinta(uusiMyymalahinta, myymalahintaElementti);
                }
                
                if(uusiNettohinta !== false && nettokate > 0) {
                    asetaUusiHinta(uusiNettohinta, nettohintaElementti);
                }
                
                $(this).find(kateMyyntihintaSarake).val(myyntikate);
                $(this).find(kateMyymalahintaSarake).val(myymalakate);
                $(this).find(kateNettohintaSarake).val(nettokate);


            }
        });
    });
    
});
