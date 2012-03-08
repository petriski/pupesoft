<?php

	// nyt on kaikki tiedot rahtikirjaa varten haettu..
	//
	// arrayt:
	// toitarow, otsikot, pakkaus, kilot, kollit, kuutiot, lavametri, vakit
	// $rakir_row:sta l�ytyy asiakkaan tiedot ja $rivi:st� ytunnus
	//
	// muuttujat:
	// otunnukset, rahdinmaksaja, rahtihinta, pvm, toimitustapa, kolliyht, kilotyht, kuutiotyht, kirjoitin
	// jv tapauksissa on my�s yhteensa, summa, jvhinta, lasno ja viite muuttujat
	//
	// tulostetaan rahtikirja

	// Setataan tarvittavat muuttujat
	require_once("rahtikirja_postitarra_otsikkotiedot.inc");

	require_once("pdflib/phppdflib.class.php");

	// jos php-gd on installoitu niin loidataab barcode library
	if (in_array("gd", get_loaded_extensions())) {
		if (@include_once("viivakoodi/Barcode.php"));
		else include_once("Barcode.php");
	}

	//PDF parametrit
	$pdf = new pdffile;
	$pdf->set_default('margin-top', 	0);
	$pdf->set_default('margin-bottom', 	0);
	$pdf->set_default('margin-left', 	0);
	$pdf->set_default('margin-right', 	0);

	$rectparam["width"] 		= 0.3;

	$norm["height"] 			= 8.5;
	$norm["font"] 				= "Helvetica";

	$pieni["height"] 			= 6;
	$pieni["font"] 				= "Helvetica";

	$pienibold["height"] 		= 7;
	$pienibold["font"] 			= "Helvetica-Bold";

	$iso["height"] 				= 9;
	$iso["font"] 				= "Helvetica";

	$isobold["height"] 			= 10.5;
	$isobold["font"] 			= "Helvetica-Bold";

	$viivacourier["height"] 	= 10;
	$viivacourier["font"] 		= "Courier";

	$viivacourierbold["height"] = 10;
	$viivacourierbold["font"] 	= "Courier-Bold";

	$huge["height"] 			= 20;
	$huge["font"] 				= "Helvetica-Bold";

	$query_ale_lisa = generoi_alekentta('M');

	// Tilausken arvo
	$query = "  SELECT lasku.valkoodi, sum(round(tilausrivi.hinta * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * {$query_ale_lisa}, '$yhtiorow[hintapyoristys]')) rivihinta
				FROM tilausrivi
				JOIN lasku ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus)
				where tilausrivi.otunnus in ($otunnukset)
				and tilausrivi.yhtio = '$kukarow[yhtio]'
				and tilausrivi.var in ('','H')
				and tilausrivi.tyyppi in ('L','G')
				GROUP BY 1";
	$riresult = pupe_query($query);
	$rirow = mysql_fetch_assoc($riresult);

	// defaultteja
	$kala = 575;
	$lask = 1;
	$sivu = 1;

	$tulostakolli = $tulostuskpl;

	// tulostetaan niin monta lappua kun on kollejakin
	for ($tulostuskpl=1; $tulostuskpl<=$tulostakolli; $tulostuskpl++) {

		$juokseva = sprintf("%02d", $tulostuskpl); 		// laitetaan tulostuskpl alkuun niin jokalapulla on uniikki viiva

		if (substr($rivi["rahtikirjanro"], 0, 2) == "CE") {
			$oma = sprintf("%02d", substr($rivi["rahtikirjanro"], -5));
		}
		else {
			$oma = sprintf("%02d", substr($rtunnus, -2)); // 2, pitk� numero tulee rahtikirja taulun tunnuksesta, pit�� olaa uniikki
		}

		#L�hetystunnuksen tarkistenumeron laskentakaava (modulus 11):
		$viiva = mod11_tarkiste($toitarow['tunhalnro'].$oma.$juokseva);

		$viiva1   = "CE".$viiva."FI";
		$viivaedi = "CE".$viiva."FI";
		$viiva2   = "*CE".$viiva."FI*";

		// erotellaan spacella jokainen rahtikirjanumero
		$rahtikirjanro .= "$viiva1 ";

		// tehd��n pdf:n uusi sivu
		$firstpage = $pdf->new_page("a5");

		$data = file_get_contents($pupe_root_polku."/pics/Itella_priority.jpg");
		$image = $pdf->jfif_embed($data);
		$logoparam['scale'] = 0.105;
		$pdf->image_place($image, 570, 70, $firstpage, $logoparam);

		if (class_exists("Image_Barcode")) {
			// L�hetystunnus
			$data = viivakoodi($viiva1, "code39", 280, 70, "");
			$image = $pdf->jfif_embed($data);
			$logoparam['scale'] = 122/282;
			$pdf->image_place($image, 270, 30, $firstpage, $logoparam);

			// Tuotetunnus
			$nimi = "/tmp/".md5(uniqid(rand(),true)).".jpg";

			imagejpeg(Image_Barcode::draw($tuoteviiva1, 'code128', 'jpg', false, 3, 130), $nimi);

			$fh = fopen($nimi, "r");
			$data = fread($fh, filesize($nimi));
			fclose($fh);

			$image = $pdf->jfif_embed($data);

			$logoparam['scale'] = 122/282;
			$pdf->image_place($image, 540, 255, $firstpage, $logoparam);
			system("rm -f $nimi");

			// Sopimustunnus
			$nimi = "/tmp/".md5(uniqid(rand(),true)).".jpg";

			imagejpeg(Image_Barcode::draw("9V".$toitarow["sopimusnro"], 'code128', 'jpg', false, 3, 130), $nimi);

			$fh = fopen($nimi, "r");
			$data = fread($fh, filesize($nimi));
			fclose($fh);

			$image = $pdf->jfif_embed($data);

			$logoparam['scale'] = 122/282;
			$pdf->image_place($image, 20, 225, $firstpage, $logoparam);
			system("rm -f $nimi");
		}

		// sitten aletaan piirt�m��n itse PDF sis�lt��
		$pdf->draw_text(18,  550,  "Sender (name and address) / Exp�diteur (nom et adresse)", 					$firstpage, $pieni);
		$pdf->draw_text(18,  540, $postirow["yhtio_nimi"],	 					$firstpage, $norm);
		$pdf->draw_text(18,  530, $postirow["yhtio_osoite"],					$firstpage, $norm);
		$pdf->draw_text(18,  520, $postirow["yhtio_postino"]." ".$postirow["yhtio_postitp"],$firstpage, $norm);
		$pdf->draw_text(18,  510, $postirow["yhtio_maa"], 						$firstpage, $norm);

		$data = file_get_contents($pupe_root_polku."/pics/Itella_puhelin.jpg");
		$image = $pdf->jfif_embed($data);
		$logoparam['scale'] = 0.12;
		$pdf->image_place($image, 493, 18, $firstpage, $logoparam);
		$pdf->draw_text(35, 495,  $yhtiorow["puhelin"],	 					$firstpage, $iso);

		$pdf->draw_text(115,  500,  "Sender's business ID /",		$firstpage, $pieni);
		$pdf->draw_text(115,  495,  "No de T.V.A. de l'exp�diteur",		$firstpage, $pieni);
		$pdf->draw_text(185,  500,  $postirow["yhtio_maa"]."-".str_replace("0037", "", $postirow["yhtio_ovttunnus"]),		$firstpage, $norm);


		$pdf->draw_text(18,  485,  "Recipient (name and address) / Destinataire (nom et adresse)",		$firstpage, $pieni);
		$pdf->draw_text(120, 485,  $rakir_row["puhelin"],	 					$firstpage, $pieni);
		$pdf->draw_text(18,  475,  $rakir_row["toim_nimi"],	 					$firstpage, $iso);
		$pdf->draw_text(18,  465,  $rakir_row["toim_nimitark"], 				$firstpage, $iso);
		$pdf->draw_text(18,  455,  $rakir_row["toim_osoite"],	 				$firstpage, $iso);
		$pdf->draw_text(18,  445,  $rakir_row["toim_postino"]." ".$rakir_row["toim_postitp"],$firstpage, $iso);
		$pdf->draw_text(18,  435,  $rakir_row['toim_maa'],			$firstpage, $iso);

		$data = file_get_contents($pupe_root_polku."/pics/Itella_puhelin.jpg");
		$image = $pdf->jfif_embed($data);
		$logoparam['scale'] = 0.12;
		$pdf->image_place($image, 418, 18, $firstpage, $logoparam);

		$pdf->draw_text(35, 420,  $rakir_row["puhelin"],	 					$firstpage, $iso);

		$pdf->draw_text(115,  425,  "VAT No /",		$firstpage, $pieni);
		$pdf->draw_text(115,  420,  "No de T.V.A.",		$firstpage, $pieni);

		if ($rakir_row["ovttunnus"] != "") {
			$pdf->draw_text(155,  420,  $rakir_row['maa']."-".str_replace("0037", "", $rakir_row["ovttunnus"]),		$firstpage, $norm);
		}

		// tuoteviivakoodin alle
		$pdf->draw_text(310, 532,  $tuoteviiva1, 								$firstpage, $viivacourier);

		// jos k�ytet��n edi�
		if ($postiedihost != "" and $postiediuser != "" and $postiedipass != "" and $postiedipath != "") {
			$pdf->draw_text(345, 510,  "EDI",		 							$firstpage, $huge);
		}

		$pdf->draw_text(250, 490,  "Date of posting / Date du d�p�t", $firstpage, $pieni);
		$pdf->draw_text(250, 479,  date("d.m.Y"), 								$firstpage, $norm);

		$pdf->draw_rectangle(495,245,450,245, 									$firstpage, $rectparam);
		$pdf->draw_rectangle(476,245,476,400, 									$firstpage, $rectparam);
		$pdf->draw_rectangle(476,322,450,322, 									$firstpage, $rectparam);
		$pdf->draw_rectangle(450,245,450,400, 									$firstpage, $rectparam);
		$pdf->draw_rectangle(450,245,432,245, 									$firstpage, $rectparam);
		$pdf->draw_rectangle(432,245,432,400, 									$firstpage, $rectparam);
		$pdf->draw_rectangle(432,245,398,245, 									$firstpage, $rectparam);

		$pdf->draw_rectangle(430,247,420,258, 									$firstpage, $rectparam);
		$pdf->draw_rectangle(420,247,410,258, 									$firstpage, $rectparam);
		$pdf->draw_rectangle(410,247,400,258, 									$firstpage, $rectparam);
		$pdf->draw_rectangle(430,330,420,341, 									$firstpage, $rectparam);

		$pdf->draw_text(250, 469,  "Shipment weight /", $firstpage, $pieni);
		$pdf->draw_text(250, 463,  "Poids de l'envoi", $firstpage, $pieni);
		$pdf->draw_text(255, 453,  $kilotyht,	 								$firstpage, $norm);
		$pdf->draw_text(312, 453,  "kg",		 								$firstpage, $pieni);

		$pdf->draw_text(330, 469,  "Shipment volume /", $firstpage, $pieni);
		$pdf->draw_text(330, 463,  "Volume de l'envoi", $firstpage, $pieni);
		$pdf->draw_text(330, 453,  $kuutiotyht, 								$firstpage, $norm);
		$pdf->draw_text(390, 453,  "m3",		 								$firstpage, $pieni);

		$pdf->draw_text(250, 444,  "Pieces Pi�ces", $firstpage, $pieni);
		$pdf->draw_text(255, 435,  $kollityht,									$firstpage, $iso);


		$pdf->draw_text(260, 423,  "Sample/Enchatillon", 	$firstpage, $pieni);
		$pdf->draw_text(260, 414,  "Gift/Cadeau", 			$firstpage, $pieni);
		$pdf->draw_text(260, 405,  "Merchandise, value", 	$firstpage, $pieni);
		$pdf->draw_text(260, 400,  "Marchandises, valeur", 	$firstpage, $pieni);
		$pdf->draw_text(343, 423,  "Documents", 			$firstpage, $pieni);
		$pdf->draw_text(249,  401,  "X",					$firstpage, $isobold);
		$pdf->draw_text(329,  401,  $rirow["valkoodi"]." ".$rirow["rivihinta"],	$firstpage, $norm);

		// asiakkaan postitoimipaikan alle
		$pdf->draw_rectangle(398,18,398,400, 									$firstpage, $rectparam);
		$pdf->draw_text(18,  390,  "In case of non-delivery, please / En cas de non-livraison, veuillez",	$firstpage, $pieni);

		$pdf->draw_rectangle(385,18,375,28, 									$firstpage, $rectparam);
		$pdf->draw_text(30,  380,  "treat item as abandoned",					$firstpage, $pieni);
		$pdf->draw_text(30,  375,  "traiter l'envoi comme abandonn�",			$firstpage, $pieni);


		$pdf->draw_text(140,  380,  "return to the sender immediately",			$firstpage, $pieni);
		$pdf->draw_text(140,  375,  "renvoyer � l'exp�diteur imm�diatement",	$firstpage, $pieni);

		$pdf->draw_rectangle(385,250,375,260, 									$firstpage, $rectparam);
		$pdf->draw_text(263,  380,  "by air",		$firstpage, $pieni);
		$pdf->draw_text(263,  375,  "par avion",	$firstpage, $pieni);

		$pdf->draw_rectangle(385,300,375,310, 									$firstpage, $rectparam);
		$pdf->draw_text(313,  380,  "the most economical route",	$firstpage, $pieni);
		$pdf->draw_text(313,  375,  "par la route la plus �conomique",	$firstpage, $pieni);
		$pdf->draw_text(302,  376,  "X",	$firstpage, $isobold);

		// viiva viivakooditekstin yl� ja alapuolelle
		$pdf->draw_rectangle(260,18,260,400, 									$firstpage, $rectparam);
		$pdf->draw_rectangle(370,18,370,400, 									$firstpage, $rectparam);
		$pdf->draw_text(140, 262,  $viiva2,										$firstpage, $viivacourier);
		$pdf->draw_text(140, 362,  $viiva2,										$firstpage, $viivacourier);

		// viivakoodin alapuolella
		$pdf->draw_text(18,  250,  "Sender / Exp�diteur", 						$firstpage, $pieni);
		$pdf->draw_text(80,  250, $postirow["yhtio_nimi"]." ".$postirow["yhtio_osoite"]." ".$postirow["yhtio_postino"]." ".$postirow["yhtio_postitp"]." ".$postirow["yhtio_maa"], $firstpage, $norm);
		$pdf->draw_rectangle(245,18,245,400, 									$firstpage, $rectparam);

		$pdf->draw_text(18,  237,  "Recipient (name and address)", 	$firstpage, $pieni);
		$pdf->draw_text(18,  228,  "Destinataire(nometadresse)", 	$firstpage, $pieni);
		$pdf->draw_text(110,  237,  $rakir_row["toim_nimi"]." ".$rakir_row["toim_nimitark"], $firstpage, $norm);
		$pdf->draw_text(110,  228,  $rakir_row["toim_osoite"]." ".$rakir_row["toim_postino"]." ".$rakir_row["toim_postitp"]." ".strtoupper($rakir_row['toim_maa']), $firstpage, $norm);
		$pdf->draw_rectangle(225,18,225,400, 									$firstpage, $rectparam);

		$pdf->draw_text(18,  218,  "Description of contents", 	$firstpage, $pieni);
		$pdf->draw_text(18,  212,  "Description d�taill�e du contenu", 	$firstpage, $pieni);

		$pdf->draw_text(120,  218,  "Number", 	$firstpage, $pieni);
		$pdf->draw_text(120,  212,  "Nombre", 	$firstpage, $pieni);

		$pdf->draw_text(150,  218,  "Net weight", 	$firstpage, $pieni);
		$pdf->draw_text(150,  212,  "Poids net", 	$firstpage, $pieni);

		$pdf->draw_text(200,  218,  "Country of origin", 	$firstpage, $pieni);
		$pdf->draw_text(200,  212,  "Pays d'origine", 	$firstpage, $pieni);

		$pdf->draw_text(255,  218,  "Customs tariff No", 	$firstpage, $pieni);
		$pdf->draw_text(255,  212,  "No tarifaire", 	$firstpage, $pieni);

		$pdf->draw_text(320,  218,  "Value / Currency", 	$firstpage, $pieni);
		$pdf->draw_text(320,  212,  "Valeur / Monnaie", 	$firstpage, $pieni);
		$pdf->draw_rectangle(208,18,208,400, 									$firstpage, $rectparam);

		// Lis�tiedot
		$pdf->draw_rectangle(118,18,118,400, 									$firstpage, $rectparam);
		$pdf->draw_text(18, 110,  "Date",	$firstpage, $pieni);
		$pdf->draw_text(70, 110,  "Time/Heure",	$firstpage, $pieni);
		$pdf->draw_text(130, 110,  "Recipient's signature Signature du destinataire",	$firstpage, $pieni);

		$pdf->draw_text(18, 10,   t("Itella Green - Carbon neutral delivery"),					$firstpage, $iso);

		$pdf->draw_text(270, 10,  "9V".$toitarow['sopimusnro'],		$firstpage, $viivacourier);

		//l�hetet��n postin edisanoma
		if (strpos($_SERVER['SCRIPT_NAME'], "rahtikirja-kopio.php") === FALSE) {
			require("postiedi.inc");
		}
	}

	if ($tulostakolli > 0) {
		//keksit��n uudelle failille joku varmasti uniikki nimi:
		$pdffilenimi = "/tmp/rahtikirja-".md5(uniqid(rand(),true)).".pdf";

		//kirjoitetaan pdf faili levylle..
		$fh = fopen($pdffilenimi, "w");
		if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF kirjoitus ep�onnistui $pdffilenimi");
		fclose($fh);

		//tulostetaan PDF
		if ($kirjoitin == 'email') {
			$liite = $pdffilenimi;
			$kutsu = "Postitarra";

			require("inc/sahkoposti.inc");
		}
		elseif ($kirjoitin == 'PDF_RUUDULLE') {
			//Ty�nnet��n tuo pdf vaan putkeen!
			echo file_get_contents($pdffilenimi);
		}
		else {
			$line = exec("$kirjoitin $pdffilenimi");
		}

		//poistetaan tmp file samantien kuleksimasta...
		system("rm -f $pdffilenimi");
	}

?>