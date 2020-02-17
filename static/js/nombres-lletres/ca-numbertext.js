var rules_ca = `
^0 zero
#1$ u
1 un
2 dos
3 tres
4 quatre
5 cinc
6 sis
7 set
8 huit # [:ca-valencia:] [:ca-ES-valencia:]
8 vuit
9 nou
#10-19
10 deu
11 onze
12 dotze
13 tretze
14 catorze
15 quinze
16 setze
17 dèsset # [:ca-valencia:] [:ca-ES-valencia:]
17 desset # [:ca-balear:] [:ca-ES-balear:]
17 disset
18 devuit # [:ca-balear:] [:ca-ES-balear:]
18 díhuit # [:ca-valencia:] [:ca-ES-valencia:]
19 denou # [:ca-balear:] [:ca-ES-balear:]
19 dènou # [:ca-valencia:] [:ca-ES-valencia:]
1(\\d) di$1
# 20-29
20 vint
2(\\d) vint-i-$1
# 30, 40, 50, 60, 70, 80, 90
30 trenta
40 quaranta
50 cinquanta
60 seixanta
70 setanta
80 huitanta # [:ca-valencia:] [:ca-ES-valencia:]
80 vuitanta
90 noranta
(\\d)(\\d) $(\\10)-$2

#100-199
100 cent
1(\\d\\d) cent $1
#200-999
(\\d)00 $1-cents
(\\d)(\\d\\d) $1-cents $2

#1000-1999
1000 mil
1(\\d{3}) mil $1

#2000-999999
(\\d{1,3})000 $1 mil
(\\d{1,3})(\\d{3}) $1 mil $2

# our limit is number <10^606
(\\d{606,}) ""

# x-lions
# 10000000=10^6 -> un milió
1((0{6})+) un $(pre:$(count:\\1))lió
1((\\d{6})+) un $(pre:$(count:\\1))lió $1
# 2000000=2·10^6 -> dos milions
(\\d{1,6})((0{6})+) $1 $(pre:$(count:\\2))lions
(\\d{1,6})((\\d{6})+) $1 $(pre:$(count:\\2))lions $2


# count number of 10^6, usefull for x-lions, and x-liards prefixes.
count:.{0,5}? 0
count:.{6}.{0,5} 1
count:(.{12}).{0,5} 2
count:(.{18}).{0,5} 3
count:(.{24}).{0,5} 4
count:(.{30}).{0,5} 5
count:(.{36}).{0,5} 6
count:(.{42}).{0,5} 7
count:(.{48}).{0,5} 8
count:(.{54}).{0,5} 9
count:(.{60})(.{0,59}) 1|$(count:\\2)
count:(.{120})(.{0,59}) 2|$(count:\\2)
count:(.{180})(.{0,59}) 3|$(count:\\2)
count:(.{240})(.{0,59}) 4|$(count:\\2)
count:(.{300})(.{0,59}) 5|$(count:\\2)
count:(.{360})(.{0,59}) 6|$(count:\\2)
count:(.{420})(.{0,59}) 7|$(count:\\2)
count:(.{480})(.{0,59}) 8|$(count:\\2)
count:(.{540})(.{0,59}) 9|$(count:\\2)
count:(.{600})(.{0,5}) 10|$(count:\\2) # our limit is 10^606-1

# prefixes needed for x-lions and x-liards, up to 10^606-1
pre:1 mi
pre:2 bi
pre:3 tri
pre:4 quadri
pre:5 quinti
pre:6 sexti
pre:7 septi
pre:8 octi
pre:9 noni
pre:10 deci
pre:1(\\d) $(pre2:\\1)|deci
pre:(\\d)0 $(pre3:\\1)
pre:(\\d)(\\d) $(pre2:\\2)|$(pre3:\\1)
pre:100 centi

pre2:1 uno
pre2:2 duo
pre2:3 tre
pre2:4 quattour
pre2:5 quin
pre2:6 sex
pre2:7 septen
pre2:8 octo
pre2:9 novem

pre3:1 deci
pre3:2 viginti
pre3:3 triginti
pre3:4 quadraginti
pre3:5 quinquaginti
pre3:6 sexaginti
pre3:7 septuaginti
pre3:8 octoginti
pre3:9 nonoginti
pre3:10 centi

# negative number
[--](\\d+) menys |$1

# decimals
"([^,]*\\d)[.]((\\d{3})+)([,][^,.]*)?" $(\\1\\2\\4)
"([--]?\\d+)([,]0*)?" $1
"([--]?\\d+)[,](\\d*)" $(\\1·\\2)
"([--]?\\d+·0*)([^0]00?)0*" $1| |$2
"([--]?\\d+·0*)([^0])" $1| |$2
"([--]?\\d+·0*)([^0]\\d)" $1| |$2
"([--]?\\d+·0*)([^0]\\d\\d)" $1| |$2
"([--]?\\d+·0*)([^0]\\d\\d)0*" $1| |$2

"([--]?\\d+·0*)(([^0]|[^0]\\d*[^0]))0*" $1| $(read:\\2)
"([--]?\\d+)·(\\d*)(\\d)" $(\\1·\\2)| |$3
"([--]?\\d+)·" $1| coma

# used for decimal part
#read:(\\d*[^0])0*$ $(read:\\1)
read:(\\d*[1-9])(00+)([1-9]\\d*) $(read:\\1)| |$(read:\\2) |$(read:\\3)
read:(\\d$) $1
read:0(\\d+) $(read:0)| |$(read:\\1)
read:([1-9]\\d) $1
read:([1-9]\\d\\d) $1
read:(\\d\\d\\d) $1
read:(\\d\\d)((\\d\\d)+) $(read:\\1)| |$(read:\\2)
read:(\\d\\d)((\\d\\d)*)(\\d\\d\\d) $(read:\\1)| |$(read:\\2)| |$(read:\\4)


# convert masculine forms to feminine forms
# it can be run after: standard number conversion; and after ordinal, partitive functions.
## runned with feminine function.
f:(.*iliard)(.*) \\1$(f:\\2) # convert only <1,000,000,000
f:(.*ili)(.*) \\1$(f:\\2) # convert only <100,0000
f:(.*d)o(s[^èé]*) $(f:\\1ue\\2) # 2 -> dos -> dues
f:(.*cent)(s.*) $(f:\\1e\\2) # cents -> centes
f:(((.*)[^a-zèé]|))u$ \\1una # vint-i-u -> vint-i-una
## runned after ord function.
f:(.*[^0-9])n$ \\1na # segon -> segona
f:(.*[^0-9]r)$ \\1a # tercer -> tercera
f:(.*[^0-9]r)t$ \\1ta # quart -> quarta
f:(.*[^0-9])è$ \\1ena # sisè -> sisena
f:(.*[^0-9])é$ \\1ena # sisé -> sisena
## runned after ord2 function.
f:(.*[0-9])[nrtè]$ \\1a # 2n -> 2a
## runnded after part function.
f:(.*ter)ç$ \\1cera # terç -> tercera
f:(.*è[sc]i)m$ \\1ma # milionèsim -> milionèsima
f:(.*[^0-9]i)g$ \\1tja # mig -> mitja


no-centes:(.*)centes(.*) \\1cents\\2
no-centes:(.*) \\1

# convert ordinal numbers (1st, 2nd, 3rd,... nth) to partitive (1, 1/2, 1/3, .... 1/n)
p:(.*)primer$ \\1unitat
p:(.*)segon$ \\1mig
p:(.*)tercer$ \\1terç
p:(.*quart)$ \\1
p:(.*)des[èé]$ \\1dècim
p:((.*)cent)[èé]$ \\1èsim
p:((.*)mil)[èé]$ \\1·lèsim
p:((.*)ilion)[èé]$ \\1èsim
p:((.*)iliard)[èé]$ \\1èsim


# fallback, ignore 1-letter not-defined fuctions
.:(.*) \\1

# runned after ordinal and partitive fuctions
pl:(.*[^\\d][nrtnec])$ \\1s
pl:(.*[^\\d])ig$ \\1igs # mig -> mitjos
pl:(.*[^\\d])ja$ \\1ges
pl:(.*[^\\d])a$ \\1es
pl:(.*[^\\d])[èé]$ \\1ens
# after ord2: 1r->1rs, 2n->2ns, 5è->5ns, ...
pl:(\\d+[rnrt])$ \\1s # 1r -> 1rs, 2n -> 2ns, 4t -> 4ts
pl:(\\d+)[èé]$ \\1ns # 5è -> 5ns
pl:(\\d+)a$ \\1es # 2a -> 2es
# after partitive
pl:([^[0-9]*[sç])$ \\1os # dos -> dosos, terç > terços
pl:([^[0-9]*è[sc]im)$ \\1s # dècim -> dècims
#fallback
pl:(.*) \\1


# unit/subunit singular/plural
# million or greater part of the number name separated by "ili" pattern
# before masculine to feminine conversion
us(.).:([^,]*),([^,]*),([^,]*),([^,]*),([^,]*),([^,]*) $(\\1:\\7)| \\2
up(.).:([^,]*),([^,]*),([^,]*),([^,]*),([^,]*),([^,]*) $(\\1\\:\\7)| \\3
ud(.).:([^,]*),([^,]*),([^,]*),([^,]*),([^,]*),([^,]*) $(\\1\\:\\7)| \\4
ss.(.):([^,]*),([^,]*),([^,]*),([^,]*),([^,]*),([^,]*) $(\\1\\:\\7)| \\5
sp.(.):([^,]*),([^,]*),([^,]*),([^,]*),([^,]*),([^,]*) $(\\1\\:\\7)| \\6

# "mm" means masculine unit and masculine subunit
# Usually used by Catalan users
CHF:(.+),(.+) $(\\2mm: franc suís, francs suïssos, de francs suïssos, cèntim, cèntims, \\1)
EUR:(.+),(.+) $(\\2mm: euro, euros, d'euros, cèntim, cèntims, \\1)
GBP:(.+),(.+) $(\\2fm: lliura esterlina, lliures esterlines, de lliures esterlines, penic, penics, \\1)
JPY:(.+),(.+) $(\\2mm: ien, iens, de iens, sen, sen, \\1)
USD:(.+),(.+) $(\\2mm: dòlar dels EUA, dòlars dels EUA, de dòlars dels EUA, centau, centaus, \\1)
# ACTIVE ISO 4217 CODES--A--
AED:(.+),(.+) $(\\2mm: dírham dels Emirats Àrabs Units, dírhams dels Emirats Àrabs Units, de dírhams dels Emirats Àrabs Units, fils, fulús, \\1)
AFN:(.+),(.+) $(\\2mm: afgani, afganis, d'afganis, puli, puli, \\1)
ALL:(.+),(.+) $(\\2mm: lek, lekë, de lekë, qindarka, qindarka, \\1)
AMD:(.+),(.+) $(\\2mm: dram, drams, de drams, luma, luma, \\1)
ANG:(.+),(.+) $(\\2mm: florí de les Antilles Neerlandeses, florins de les Antilles Neerlandeses, de florins de les Antilles Neerlandeses, cèntim, cèntims, \\1)
AOA:(.+),(.+) $(\\2fm: kwanza, kwanzes, de kwanzes, cèntim cèntims, \\1)
ARS:(.+),(.+) $(\\2mm: peso argentí, pesos argentins, de pesos argentins, centau, centaus, \\1)
AUD(.+),(.+) $(\\2mm: dòlar australià, dòlars australians, de dòlars australians, centau, centaus, \\1)
AWG:(.+),(.+) $(\\2mm: florí d'Aruba, florins d'Aruba, de florins d'Aruba, cèntim, cèntims, \\1)
AZN:(.+),(.+) $(\\2mm: manat azerbaidjanès, manats azerbaidjanesos, de manats azerbaidjanesos, qəpik, qəpik, \\1)
# ACTIVE ISO 4217 CODES --X--
#XAF Franc CFA emès pel BEAC (Banc dels Estats de l'Àfrica Central)
XAG:(.+),(.+) $(\\2fm: unça de plata, unces de plata, d'unces de plata, cèntim, cèntims, \\1)
XAU:(.+),(.+) $(\\2fm: unça d'or, unces d'or, d'unces d'or, cèntim, cèntims, \\1)
#XBA Unitat compensatòria europea (EURCO) (unitat per al mercat d'obligacions)
#XBB Unitat monetària europea (EMU-6) (unitat per al mercat d'obligacions)
#XBC Unitat de compte europea 9 (EUA-9) (unitat per al mercat d'obligacions)
#XBD Unitat de compte europea 17 (EUA-17) (unitat per al mercat d'obligacions)
#XCD Dòlar del Carib Oriental
#XDR Drets especials de gir (del Fons Monetari Internacional)
#XFU Franc UIC (divisa especial)
#XOF Franc CFA emès pel BCEAO (Banc Central dels Estats de l'Àfrica Occidental)
XPD:(.+),(.+) $(\\2fm: unça de pal·ladi, unces de pal·ladi, d'unces de pal·ladi, cèntim, cèntims, \\1)
#XPF Franc CFP (per als territoris francesos del Pacífic)
XPT:(.+),(.+) $(\\2fm: unça de platí, unces de platí, d'unces de platí, cèntim, cèntims, \\1)
#XTS Codi reservat per a proves
#XXX Sense moneda, sense transacció monetària
# OBSOLETE ISO 4217 CODES --Replaced by EUR--
ADF:(.+),(.+) $(\\2mm: franc andorrà, francs andorrans, de francs andorrans, cèntim, cèntims, \\1)
ADP:(.+),(.+) $(\\2fm: pesseta andorrana, pessetes andorranes, de pessetes andorranes, cèntim, cèntims, \\1)
ATS:(.+),(.+) $(\\2mm: xíling austríac, xílings austríacs, de xílings austríacs, groschen, groschen, \\1)
BEF:(.+),(.+) $(\\2mm: franc belga, francs belgues, de francs belgues, cèntim, cèntims, \\1)
CYP:(.+),(.+) $(\\2mm: lliura xipriota, lliures xipriotes, de lliures xipriotes, cèntim, cèntims, \\1)
DEM:(.+),(.+) $(\\2mm: marc alemany, marcs alemanys, de marcs alemanys, penic, penics, \\1)
ESP:(.+),(.+) $(\\2fm: pesseta, pessetes, de pessetes, cèntim, cèntims, \\1)
FIM:(.+),(.+) $(\\2mm: marc finlandès, marcs finlandesos, de marcs finlandesos, penic, penics, \\1)
FRF:(.+),(.+) $(\\2mm: franc francès, francs francesos, de francs francesos, cèntim, cèntims, \\1)
GRD:(.+),(.+) $(\\2fm: dracma grega, dracmes gregues, leptó, leptà, \\1)
IEP:(.+),(.+) $(\\2fm: lliura irlandesa, lliures irlandeses, de lliures irlandeses, penic, penics, \\1)
ITL:(.+),(.+) $(\\2fm: lira italiana, lires italianes, de lires italianes, cèntim, cèntims, \\1)
LUF:(.+),(.+) $(\\2mm: franc luxemburguès, francs luxemburguesos, de francs luxemburguesos, cèntim, cèntims, \\1)
MCF:(.+),(.+) $(\\2mm: franc monegasc, francs monegascs, de francs monegascs, cèntim, cèntims, \\1)
MTL:(.+),(.+) $(\\2fm: lira maltesa, lires malteses, de lires malteses, cèntim, cèntims, \\1)
NLG:(.+),(.+) $(\\2mm: florí neerlandès, florins neerlandesos, de florins neerlandesos, cèntim, cèntims, \\1)
PTE:(.+),(.+) $(\\2mm: escut portuguès, escuts portuguesos, de escuts portuguesos, centau, centaus, \\1)
SIT:(.+),(.+) $(\\2mm: tolar eslovè, tolars eslovens, de tolars eslovens, stotin, stotinov, \\1)
SKK:(.+),(.+) $(\\2fm: corona eslovaca, corones eslovaques, de corones eslovaques, halier, halierov, \\1)
SML:(.+),(.+) $(\\2fm: lira de San Marino, lires de San Marino, de lires de San Marino, cèntim, cèntims, \\1)
VAL:(.+),(.+) $(\\2fm: lira vaticana, lires vaticanes, de lires vaticanes, cèntim, cèntims, \\1)
XEU:(.+),(.+) $(\\2mm: ecu, ecus, d'ecus, cèntim, cèntims, \\1)

#crypto-currencies
XMR:(.+),(.+) $(\\2mm: monero, moneros, de moneros, piconero, piconeros, \\1) #TODO: 1,000,000,000,000 piconeros = 1 monero
XBT:(.+),(.+) $(\\2mm: bitcoin, bitcoins, de bitcoins, satoshi, satoshis, \\1) # TODO: 10,000,000 satoshis = 1,000 millibitcoin = 1 bitcoin

# unknow currency
[A-Z]{3}:.* ""

"([A-Z]{3}) ([-−]?1)([.,]00?)?" $(\\1:|$2,us)
"([A-Z]{3}) ([-−]?\\d+0{6,})([.,]00?)?" $(\\1:|$2,ud)
"([A-Z]{3}) ([-−]?\\d+)([.,]00?)?" $(\\1:|$2,up)
"(([A-Z]{3}) [-−]?\\d+)[.,](01)" $1 amb $(\\2:un,ss)
"(([A-Z]{3}) [-−]?\\d+)[.,](\\d)" $1 amb $(\\2:|$(\\30),sp)
"(([A-Z]{3}) [-−]?\\d+)[.,](\\d\\d)" $1 amb $(\\2:|$3,sp)

# "([-−]?\\d+)([.,]\\d\\+)? ([A-Z]{3})$" $(\\3 \\1 \\2)
"([-−]?\\d+)([.,]\\d\\+)? ([A-Z]{3})" $(\\3 \\1\\2)


# currency symbols
"€[ ]?([^ ]*)" $(EUR \\1)
"£[ ]?([^ ]*)" $(GBP \\1)
"\\$[ ]?([^ ]*)" $(USD \\1)
"¥[ ]?([^ ]*)" $(JPY \\1)
"₩[ ]?([^ ]*)" $(KRW \\1)
"₽[ ]?([^ ]*)" $(RUB \\1)
"ɱ[ ]?([^ ]*)" $(XMR \\1)
"₿[ ]?([^ ]*)" $(XBT \\1)

"([^ ]+)[ ]?€$" $(EUR \\1)
"([^ ]+)[ ]?£$" $(GBP \\1)
"([^ ]+)[ ]?\\$$" $(USD \\1)
"([^ ]+)[ ]?¥$" $(JPY \\1)
"([^ ]+)[ ]?₩$" $(KRW \\1)
"([^ ]+)[ ]?₽$" $(RUB \\1)
"([^ ]+)[ ]?ɱ$" $(XMR \\1)
"([^ ]+)[ ]?₿$" $(XBT \\1)

== feminine ==

1 una
(.*) $(f:|$1)

== masculine ==

1 un
(.*) $1

== ordinal(-masculine)? ==

([-−]\\d+) ""
\\d+[,.] ""
0 zeroé # [:ca-valencia:] [:ca-ES-valencia:]
0 zeroè
1 primer
2 segon
3 tercer
4 quart
(\\d+)$ $(ordinal $2)
"un ([^ ]*(ilió|iliard))$" $(ordinal \\2)
(.*li)ó$ \\2oné # [:ca-valencia:] [:ca-ES-valencia:]
(.*li)ó$ \\2onè
(.*(cent|mil|ion|iliard))s?$ \\2é # [:ca-valencia:] [:ca-ES-valencia:]
(.*(cent|mil|ion|iliard))s?$ \\2è 
"(.* )u$" \\2uné # [:ca-valencia:] [:ca-ES-valencia:]
"(.* )u$" \\2unè
(.*-)u$ \\2uné # [:ca-valencia:] [:ca-ES-valencia:]
(.*-)u$ \\2unè
"u" primer
"un" primer
"dos" segon
"tres" terç
"quatre" quart
(.*)cinc$ \\2cinqué # [:ca-valencia:] [:ca-ES-valencia:]
(.*)cinc$ \\2cinquè
(.*)dènou$ \\2denové # [:ca-valencia:] [:ca-ES-valencia:]
(.*)nou$ \\2nové # [:ca-valencia:] [:ca-ES-valencia:]
(.*)nou$ \\2novè
(.*)deu$ \\2desé # [:ca-valencia:] [:ca-ES-valencia:]
(.*)deu$ \\2desè
(.*)dèsset$ \\2desseté # [:ca-valencia:] [:ca-ES-valencia:]
(.*)díhuit$ \\2dihuité # [:ca-valencia:] [:ca-ES-valencia:]
(.*)[ae]$ \\2é # [:ca-valencia:] [:ca-ES-valencia:]
(.*)[ae]$ \\2è
(.*\\D)$ \\2é # [:ca-valencia:] [:ca-ES-valencia:]
(.*\\D)$ \\2è

== ordinal-feminine ==
([-−]\\d+) ""
\\d+[,.] ""
(\\d+)$ $(no-centes:$(f:$(ordinal \\1)))

== ordinal-number(-masculine)? ==

#(\\d+) $(o:\\2)
1$ 1r
2$ 2n
3$ 3r
4$ 4t
(\\d+)$ \\2é # [:ca-valencia:] [:ca-ES-valencia:]
(\\d+)$ \\2è

== ordinal-number-feminine ==
(\\d+)$ \\1a

== partitive(-masculine)? ==
([--]?\\d+) $(p:$(ordinal \\2))

== partitive-feminine ==
([--]?\\d+) $(no-centes:$(f:$(p:$(ordinal \\1))))


== partitive(-masculine)?-plural ==
([--]?\\d+) $(pl:$(p:$(ordinal $2)))

== partitive-feminine-plural ==
([--]?\\d+) $(no-centes:$(pl:$(f:$(p:$(ordinal $1)))))

== fraction(-masculine)? ==
([--]?1)(/1)? $2
([--]?1)/([1-9]\\d*) $2| $(partitive \\3)
([--]?\\d+)(/1)? $2
([--]?\\d+)/([1-9]\\d*) $2| $(partitive-plural \\3)

== fraction-feminine ==
([--]?1)(/1)? $(f:$1)| unitat
([--]?1)/([1-9]\\d*) $(f:$1)| $(partitive-feminine \\2)| part
([--]?\\d+)(/1)? $(f:$1)| unitats
([--]?\\d+)/([1-9]\\d*) $(f:$1)| $(partitive-feminine-plural \\2)| parts

== collective ==
2 parell, parella o duo
3 tern, terna, tercet, trio, tríada o treset
4 qüern, tètrada, quartet, quatreta o quàdruple
5 quintern, quintet, cinquet o quíntuple
6 sextet, siset o sèxtuple
7 septet, setet o sèptuple
8 octet o òctuple
9 nònuple
10 dècada o dècuple
12 dotzena
100 centenar
144 grossa
1000 miler
10000 miríada

== years ==
2 bienni
3 trienni
4 quadrienni
5 quinquenni o lustre
6 sesenni
7 septenni
10 dècada o decenni
12 duodecenni
15 quindecenni
20 vintenni o vicenni
30 trentenni o tricenni
40 quarantenni
50 cinquantenni
60 seixantenni
70 setantenni
80 huitantenni [:ca-valencia:] [:ca-ES-valencia:]
80 vuitantenni
90 norantenni
100 segle o centenni
1000 mil·lenni

== multiplicative ==
2 doble o duple
3 triple
4 quàdruple
5 quíntuple
6 sèxtuple
7 sèptuple
8 òctuple
9 nònuple
10 dècuple
12 duodècuple
100 cèntuple
1/10 subdècuple
1/2 súbduple

== help ==

"" $(1)|, $(2), $(3)\\n$(help feminine)$(help masculine)$(help ordinal-number-masculine)$(help ordinal-number-feminine)$(help ordinal-feminine)$(help ordinal-masculine)
(feminine|masculine|ordinal(-number)?(-feminine|-masculine)?) \\1: $(\\1 1), $(\\1 2), $(\\1 3)\\n

`