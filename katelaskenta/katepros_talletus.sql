
# -------------------------------------
#   Päivitetään tuote.katexxx kentät
#
#   käytetään katelaskentamodulissa
#
#   absol 6/2015
#
# -------------------------------------


update tuote
set myymalakate = absol.MARGIN_PROS(myymalahinta, kehahin)
;

update tuote
set myyntikate = absol.MARGIN_PROS(myyntihinta, kehahin)
;

update tuote
set nettokate = absol.MARGIN_PROS(nettohinta, kehahin)
;

