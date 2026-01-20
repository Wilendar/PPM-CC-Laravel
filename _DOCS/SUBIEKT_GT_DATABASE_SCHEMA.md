# Subiekt GT Database Schema (MPP_TRADE)

**Database:** MPP_TRADE
**Server:** 10.9.20.100
**Scanned:** 2026-01-20T14:46:22.088667
**Total Tables:** 1128

## Summary

| Category | Count |
|----------|-------|
| Product tables (tw*) | 88 |
| Customer tables (kh*) | 38 |
| Document tables (dok*) | 56 |
| Reference tables (sl_*) | 156 |

## Key Tables


### tw__Towar

**Rows:** 13948  
**Columns:** 101

| Column | Type | Nullable |
|--------|------|----------|
| `tw_Id` | int | No |
| `tw_Zablokowany` | bit | No |
| `tw_Rodzaj` | int | No |
| `tw_Symbol` | varchar(20) | No |
| `tw_Nazwa` | varchar(50) | No |
| `tw_Opis` | varchar(255) | No |
| `tw_IdVatSp` | int | Yes |
| `tw_IdVatZak` | int | Yes |
| `tw_JakPrzySp` | bit | No |
| `tw_JednMiary` | varchar(10) | No |
| `tw_PKWiU` | varchar(20) | No |
| `tw_SWW` | varchar(20) | No |
| `tw_IdRabat` | int | Yes |
| `tw_IdOpakowanie` | int | Yes |
| `tw_PrzezWartosc` | bit | No |
| `tw_IdPodstDostawca` | int | Yes |
| `tw_DostSymbol` | varchar(20) | No |
| `tw_CzasDostawy` | int | Yes |
| `tw_UrzNazwa` | varchar(50) | No |
| `tw_PLU` | int | Yes |
| `tw_PodstKodKresk` | varchar(20) | No |
| `tw_IdTypKodu` | int | Yes |
| `tw_CenaOtwarta` | bit | No |
| `tw_WagaEtykiet` | bit | Yes |
| `tw_KontrolaTW` | bit | No |
| `tw_StanMin` | money | Yes |
| `tw_JednStanMin` | varchar(10) | Yes |
| `tw_DniWaznosc` | int | Yes |
| `tw_IdGrupa` | int | Yes |
| `tw_WWW` | varchar(255) | No |
| `tw_SklepInternet` | bit | No |
| `tw_Pole1` | varchar(50) | No |
| `tw_Pole2` | varchar(50) | No |
| `tw_Pole3` | varchar(50) | No |
| `tw_Pole4` | varchar(50) | No |
| `tw_Pole5` | varchar(50) | No |
| `tw_Pole6` | varchar(50) | No |
| `tw_Pole7` | varchar(50) | No |
| `tw_Pole8` | varchar(50) | No |
| `tw_Uwagi` | varchar(255) | No |
| `tw_Logo` | binary(50) | Yes |
| `tw_Usuniety` | bit | No |
| `tw_Objetosc` | money | Yes |
| `tw_Masa` | money | Yes |
| `tw_Charakter` | text(2147483647) | Yes |
| `tw_JednMiaryZak` | varchar(10) | No |
| `tw_JMZakInna` | bit | No |
| `tw_KodTowaru` | varchar(20) | Yes |
| `tw_IdKrajuPochodzenia` | int | Yes |
| `tw_IdUJM` | int | Yes |
| `tw_JednMiarySprz` | varchar(10) | No |
| `tw_JMSprzInna` | bit | No |
| `tw_SerwisAukcyjny` | bit | No |
| `tw_IdProducenta` | int | Yes |
| `tw_SprzedazMobilna` | bit | No |
| `tw_IsFundPromocji` | bit | Yes |
| `tw_IdFundPromocji` | int | Yes |
| `tw_DomyslnaKategoria` | int | Yes |
| `tw_Wysokosc` | money | Yes |
| `tw_Szerokosc` | money | Yes |
| `tw_Glebokosc` | money | Yes |
| `tw_StanMaks` | money | Yes |
| `tw_Akcyza` | bit | No |
| `tw_AkcyzaZaznacz` | bit | No |
| `tw_AkcyzaKwota` | money | Yes |
| `tw_ObrotMarza` | bit | No |
| `tw_OdwrotneObciazenie` | bit | No |
| `tw_ProgKwotowyOO` | int | No |
| `tw_DodawalnyDoZW` | bit | No |
| `tw_isbn` | varchar(255) | Yes |
| `tw_bloz_7` | varchar(255) | Yes |
| `tw_bloz_12` | varchar(255) | Yes |
| `tw_KodUProducenta` | varchar(255) | Yes |
| `tw_Komunikat` | varchar(255) | Yes |
| `tw_KomunikatOd` | datetime | Yes |
| `tw_KomunikatDokumenty` | int | No |
| `tw_MechanizmPodzielonejPlatnosci` | bit | No |
| `tw_GrupaJpkVat` | int | No |
| `tw_OplCukrowaPodlega` | bit | No |
| `tw_OplCukrowaObj` | money | Yes |
| `tw_OplCukrowaZawartoscCukru` | money | Yes |
| `tw_OplCukrowaInneSlodzace` | bit | No |
| `tw_OplCukrowaSok` | bit | No |
| `tw_OplCukrowaKwota` | money | Yes |
| `tw_OplCukrowaKofeinaPodlega` | bit | No |
| `tw_OplCukrowaKofeinaKwota` | money | Yes |
| `tw_OplCukrowaNapojWeglElektr` | bit | No |
| `tw_OplCukrowaKwotaPowyzej` | money | Yes |
| `tw_DataZmianyVatSprzedazy` | datetime | Yes |
| `tw_MasaNetto` | money | Yes |
| `tw_IdKoduWyrobuAkcyzowego` | int | Yes |
| `tw_AkcyzaMarkaWyrobow` | varchar(350) | No |
| `tw_AkcyzaWielkoscProducenta` | varchar(15) | No |
| `tw_ZnakiAkcyzy` | int | Yes |
| `tw_WegielPodlegaOswiadczeniu` | bit | No |
| `tw_WegielOpisPochodzenia` | varchar(255) | No |
| `tw_PodlegaOplacieNaFunduszOchronyRolnictwa` | bit | No |
| `tw_ObjetySysKaucyjnym` | bit | No |
| `tw_SKRodzajOpakowania` | int | Yes |
| `tw_SKOpakowanieZwracane` | bit | Yes |
| `tw_SKIdOpakowania` | int | Yes |

### tw_Cena

**Rows:** 13948  
**Columns:** 85

| Column | Type | Nullable |
|--------|------|----------|
| `tc_Id` | int | No |
| `tc_IdTowar` | int | No |
| `tc_CenaNetto0` | money | Yes |
| `tc_CenaBrutto0` | money | Yes |
| `tc_WalutaId` | char(3) | Yes |
| `tc_IdWalutaKurs` | int | Yes |
| `tc_WalutaKurs` | money | Yes |
| `tc_CenaNettoWaluta` | money | Yes |
| `tc_CenaNettoWaluta2` | money | Yes |
| `tc_CenaWalutaNarzut` | money | Yes |
| `tc_WalutaJedn` | varchar(10) | No |
| `tc_PodstawaKC` | int | Yes |
| `tc_CenaNetto1` | money | Yes |
| `tc_CenaNetto2` | money | Yes |
| `tc_CenaNetto3` | money | Yes |
| `tc_CenaNetto4` | money | Yes |
| `tc_CenaNetto5` | money | Yes |
| `tc_CenaNetto6` | money | Yes |
| `tc_CenaNetto7` | money | Yes |
| `tc_CenaNetto8` | money | Yes |
| `tc_CenaNetto9` | money | Yes |
| `tc_CenaNetto10` | money | Yes |
| `tc_CenaBrutto1` | money | Yes |
| `tc_CenaBrutto2` | money | Yes |
| `tc_CenaBrutto3` | money | Yes |
| `tc_CenaBrutto4` | money | Yes |
| `tc_CenaBrutto5` | money | Yes |
| `tc_CenaBrutto6` | money | Yes |
| `tc_CenaBrutto7` | money | Yes |
| `tc_CenaBrutto8` | money | Yes |
| `tc_CenaBrutto9` | money | Yes |
| `tc_CenaBrutto10` | money | Yes |
| `tc_Zysk1` | money | Yes |
| `tc_Zysk2` | money | Yes |
| `tc_Zysk3` | money | Yes |
| `tc_Zysk4` | money | Yes |
| `tc_Zysk5` | money | Yes |
| `tc_Zysk6` | money | Yes |
| `tc_Zysk7` | money | Yes |
| `tc_Zysk8` | money | Yes |
| `tc_Zysk9` | money | Yes |
| `tc_Zysk10` | money | Yes |
| `tc_Narzut1` | money | Yes |
| `tc_Narzut2` | money | Yes |
| `tc_Narzut3` | money | Yes |
| `tc_Narzut4` | money | Yes |
| `tc_Narzut5` | money | Yes |
| `tc_Narzut6` | money | Yes |
| `tc_Narzut7` | money | Yes |
| `tc_Narzut8` | money | Yes |
| `tc_Narzut9` | money | Yes |
| `tc_Narzut10` | money | Yes |
| `tc_Marza1` | money | Yes |
| `tc_Marza2` | money | Yes |
| `tc_Marza3` | money | Yes |
| `tc_Marza4` | money | Yes |
| `tc_Marza5` | money | Yes |
| `tc_Marza6` | money | Yes |
| `tc_Marza7` | money | Yes |
| `tc_Marza8` | money | Yes |
| `tc_Marza9` | money | Yes |
| `tc_Marza10` | money | Yes |
| `tc_IdWaluta0` | char(3) | No |
| `tc_IdWaluta1` | char(3) | No |
| `tc_IdWaluta2` | char(3) | No |
| `tc_IdWaluta3` | char(3) | No |
| `tc_IdWaluta4` | char(3) | No |
| `tc_IdWaluta5` | char(3) | No |
| `tc_IdWaluta6` | char(3) | No |
| `tc_IdWaluta7` | char(3) | No |
| `tc_IdWaluta8` | char(3) | No |
| `tc_IdWaluta9` | char(3) | No |
| `tc_IdWaluta10` | char(3) | No |
| `tc_KursWaluty1` | int | Yes |
| `tc_KursWaluty2` | int | Yes |
| `tc_KursWaluty3` | int | Yes |
| `tc_KursWaluty4` | int | Yes |
| `tc_KursWaluty5` | int | Yes |
| `tc_KursWaluty6` | int | Yes |
| `tc_KursWaluty7` | int | Yes |
| `tc_KursWaluty8` | int | Yes |
| `tc_KursWaluty9` | int | Yes |
| `tc_KursWaluty10` | int | Yes |
| `tc_DataKursuWaluty` | datetime | Yes |
| `tc_BankKursuWaluty` | int | Yes |

### tw_Stan

**Rows:** 153428  
**Columns:** 6

| Column | Type | Nullable |
|--------|------|----------|
| `st_TowId` | int | No |
| `st_MagId` | int | No |
| `st_Stan` | money | No |
| `st_StanMin` | money | No |
| `st_StanRez` | money | No |
| `st_StanMax` | money | No |

### kh__Kontrahent

**Rows:** 14751  
**Columns:** 158

| Column | Type | Nullable |
|--------|------|----------|
| `kh_Id` | int | No |
| `kh_Symbol` | varchar(20) | No |
| `kh_Rodzaj` | int | No |
| `kh_REGON` | varchar(20) | No |
| `kh_IdOdbiorca` | int | Yes |
| `kh_Kontakt` | varchar(60) | No |
| `kh_PESEL` | varchar(11) | No |
| `kh_NrDowodu` | varchar(20) | No |
| `kh_DataWyd` | datetime | Yes |
| `kh_OrganWyd` | varchar(255) | No |
| `kh_CentrumAut` | bit | No |
| `kh_InstKredytowa` | bit | No |
| `kh_PrefKontakt` | varchar(50) | No |
| `kh_WWW` | varchar(255) | No |
| `kh_EMail` | varchar(255) | No |
| `kh_IdGrupa` | int | Yes |
| `kh_IdFormaP` | int | Yes |
| `kh_Cena` | int | Yes |
| `kh_PlatOdroczone` | bit | No |
| `kh_OdbDet` | bit | No |
| `kh_IdRabat` | int | Yes |
| `kh_MaxDokKred` | int | No |
| `kh_MaxWartDokKred` | money | No |
| `kh_MaxWartKred` | money | No |
| `kh_MaxDniSp` | int | No |
| `kh_NrAnalitykaD` | varchar(5) | No |
| `kh_NrAnalitykaO` | varchar(5) | No |
| `kh_Uwagi` | varchar(255) | No |
| `kh_ZgodaDO` | bit | No |
| `kh_IdOsobaDO` | int | Yes |
| `kh_ZgodaMark` | bit | No |
| `kh_ZgodaEMail` | bit | No |
| `kh_CzyKomunikat` | bit | No |
| `kh_Komunikat` | varchar(255) | No |
| `kh_KomunikatZawsze` | bit | No |
| `kh_KomunikatOd` | datetime | Yes |
| `kh_Grafika` | image(2147483647) | Yes |
| `kh_Pole1` | varchar(50) | No |
| `kh_Pole2` | varchar(50) | No |
| `kh_Pole3` | varchar(50) | No |
| `kh_Pole4` | varchar(50) | No |
| `kh_Pole5` | varchar(50) | No |
| `kh_Pole6` | varchar(50) | No |
| `kh_Pole7` | varchar(50) | No |
| `kh_Pole8` | varchar(50) | No |
| `kh_Jednorazowy` | bit | No |
| `kh_Pracownik` | varchar(60) | No |
| `kh_Zablokowany` | bit | No |
| `kh_AdresKoresp` | bit | No |
| `kh_UpowaznienieVAT` | bit | No |
| `kh_DataVAT` | datetime | Yes |
| `kh_OsobaVAT` | int | Yes |
| `kh_ProcKarta` | money | No |
| `kh_ProcKredyt` | money | No |
| `kh_ProcGotowka` | money | No |
| `kh_ProcPozostalo` | money | No |
| `kh_IdKategoriaKH` | int | Yes |
| `kh_IdEwVATSp` | int | Yes |
| `kh_EwVATSpMcOdliczenia` | int | No |
| `kh_IdEwVATSpKateg` | int | Yes |
| `kh_IdEwVATZak` | int | Yes |
| `kh_EwVATZakRodzaj` | int | No |
| `kh_EwVATZakSposobOdliczenia` | int | No |
| `kh_EwVATZakMcOdliczenia` | int | No |
| `kh_IdEwVATZakKateg` | int | Yes |
| `kh_IdRachKategPrzychod` | int | Yes |
| `kh_IdRachKategRozchod` | int | Yes |
| `kh_TransakcjaVATSp` | int | No |
| `kh_TransakcjaVATZak` | int | No |
| `kh_PodVATZarejestrowanyWUE` | bit | No |
| `kh_DataWaznosciVAT` | datetime | Yes |
| `kh_OpisOperacji` | varchar(255) | No |
| `kh_PlatPrzelew` | bit | No |
| `kh_GaduGadu` | varchar(20) | No |
| `kh_Skype` | varchar(255) | No |
| `kh_Powitanie` | varchar(255) | No |
| `kh_AdresDostawy` | bit | No |
| `kh_IdRodzajKontaktu` | int | Yes |
| `kh_IdPozyskany` | int | Yes |
| `kh_IdBranza` | int | Yes |
| `kh_IdRegion` | int | Yes |
| `kh_LiczbaPrac` | int | Yes |
| `kh_IdOpiekun` | int | Yes |
| `kh_Imie` | varchar(20) | No |
| `kh_Nazwisko` | varchar(51) | No |
| `kh_CRM` | bit | No |
| `kh_Potencjalny` | bit | No |
| `kh_IdDodal` | int | Yes |
| `kh_IdZmienil` | int | Yes |
| `kh_DataDodania` | datetime | Yes |
| `kh_DataZmiany` | datetime | Yes |
| `kh_ProcPrzelew` | money | No |
| `kh_DataOkolicznosciowa` | datetime | Yes |
| `kh_Osoba` | bit | No |
| `kh_IdRachunkuWirtualnego` | int | Yes |
| `kh_KRS` | varchar(255) | Yes |
| `kh_Domena` | varchar(50) | Yes |
| `kh_Akcyza` | int | No |
| `kh_EFakturyZgoda` | bit | No |
| `kh_EFakturyData` | datetime | Yes |
| `kh_MetodaKasowa` | bit | No |
| `kh_Lokalizacja` | nvarchar(256) | Yes |
| `kh_StatusAkcyza` | int | No |
| `kh_CzynnyPodatnikVAT` | bit | No |
| `kh_KorygowanieKUP` | bit | Yes |
| `kh_KorygowanieVATSp` | bit | Yes |
| `kh_KorygowanieVATZak` | bit | Yes |
| `kh_WzwIdFS` | int | No |
| `kh_WzwIdWZ` | int | No |
| `kh_WzwIdWZVAT` | int | No |
| `kh_WzwIdZK` | int | No |
| `kh_WzwIdZKZAL` | int | No |
| `kh_ZgodaNewsletterVendero` | bit | No |
| `kh_KlientSklepuInternetowego` | bit | No |
| `kh_WzwIdZD` | int | No |
| `kh_WzwIdCrmTransakcja` | int | No |
| `kh_StawkaVATPrzychod` | int | Yes |
| `kh_StawkaVATWydatek` | int | Yes |
| `kh_MalyPojazd` | int | Yes |
| `kh_StosujRabatWMultistore` | bit | No |
| `kh_CelZakupu` | int | No |
| `kh_StosujIndywidualnyCennikWSklepieInternetowym` | bit | No |
| `kh_OdbiorcaCesjaPlatnosci` | bit | No |
| `kh_IdNabywca` | int | Yes |
| `kh_IdOstatniWpisWeryfikacjiStatusuVAT` | int | Yes |
| `kh_BrakPPDlaRozrachunkowAuto` | bit | No |
| `kh_DomyslnaWaluta` | char(3) | Yes |
| `kh_DomyslnyTypCeny` | int | Yes |
| `kh_DomyslnaTransVATSprzedaz` | int | Yes |
| `kh_DomyslnaTransVATSprzedazFW` | int | Yes |
| `kh_DomyslnaTransVATZakup` | int | Yes |
| `kh_DomyslnaTransVATZakupFW` | int | Yes |
| `kh_DomyslnyRachBankowyId` | int | Yes |
| `kh_IdOstatniWpisWeryfikacjiStatusuVIES` | int | Yes |
| `kh_DomyslnaWalutaMode` | bit | Yes |
| `kh_DomyslnyRachBankowyIdMode` | bit | Yes |
| `kh_PrzypadekSzczegolnyPIT` | int | Yes |
| `kh_WartoscNettoCzyBrutto` | int | Yes |
| `kh_StosujSzybkaPlatnosc` | bit | No |
| `kh_IdOstatniWpisWeryfikacjiWykazPodatnikowVAT` | int | Yes |
| `kh_OstrzezenieTerminPlatnosciPrzekroczony` | int | No |
| `kh_PodatekCukrowyNaliczaj` | int | No |
| `kh_NrAkcyzowy` | varchar(13) | Yes |
| `kh_WzwIdKFS` | int | No |
| `kh_KsefEksportObslugaPolaDodatkoweInformacje` | int | Yes |
| `kh_KsefImportObslugaPolaDodatkoweInformacje` | int | Yes |
| `kh_KsefMagazynDlaEFaktur` | int | Yes |
| `kh_KsefDodatkoweInfoPoprzedzEtykietaPola` | bit | No |
| `kh_KsefPoleDodatkoweInformacjeEksportSql` | text(2147483647) | Yes |
| `kh_KsefPoleDodatkoweInformacjeEksportNaPodstSql` | bit | No |
| `kh_DomyslnaFormaDokumentowaniaSprzedaz` | int | Yes |
| `kh_KsefDomyslnyEtapPrzetwarzania` | int | Yes |
| `kh_VatRozliczanyPrzezUslugobiorce` | bit | No |
| `kh_VatRozliczanyPrzezUslugobiorceFW` | bit | No |
| `kh_ProducentRolny` | bit | No |
| `kh_NaliczajOplSpec` | bit | No |
| `kh_RolaOdbiorcyKSeF` | int | No |
| `kh_SKPodmiotReprezentujacy` | bit | No |

### dok__Dokument

**Rows:** 207957  
**Columns:** 169

| Column | Type | Nullable |
|--------|------|----------|
| `dok_Id` | int | No |
| `dok_Typ` | int | No |
| `dok_Podtyp` | int | No |
| `dok_MagId` | int | Yes |
| `dok_Nr` | int | Yes |
| `dok_NrRoz` | char(3) | Yes |
| `dok_NrPelny` | varchar(30) | No |
| `dok_NrPelnyOryg` | varchar(30) | No |
| `dok_DoDokId` | int | Yes |
| `dok_DoDokNrPelny` | varchar(30) | No |
| `dok_DoDokDataWyst` | datetime | Yes |
| `dok_MscWyst` | varchar(40) | No |
| `dok_DataWyst` | datetime | No |
| `dok_DataMag` | datetime | No |
| `dok_DataOtrzym` | datetime | Yes |
| `dok_PlatnikId` | int | Yes |
| `dok_PlatnikAdreshId` | int | Yes |
| `dok_OdbiorcaId` | int | Yes |
| `dok_OdbiorcaAdreshId` | int | Yes |
| `dok_PlatId` | int | Yes |
| `dok_PlatTermin` | datetime | Yes |
| `dok_Wystawil` | varchar(40) | No |
| `dok_Odebral` | varchar(40) | No |
| `dok_PersonelId` | int | Yes |
| `dok_CenyPoziom` | int | Yes |
| `dok_CenyTyp` | int | Yes |
| `dok_CenyKurs` | money | Yes |
| `dok_CenyNarzut` | money | No |
| `dok_RabatProc` | money | No |
| `dok_WartUsNetto` | money | No |
| `dok_WartUsBrutto` | money | No |
| `dok_WartTwNetto` | money | No |
| `dok_WartTwBrutto` | money | No |
| `dok_WartOpZwr` | money | No |
| `dok_WartOpWyd` | money | No |
| `dok_WartMag` | money | No |
| `dok_WartMagP` | money | No |
| `dok_WartMagR` | money | No |
| `dok_WartNetto` | money | No |
| `dok_WartVat` | money | No |
| `dok_WartBrutto` | money | No |
| `dok_KwWartosc` | money | Yes |
| `dok_KwGotowka` | money | Yes |
| `dok_KwKarta` | money | Yes |
| `dok_KwDoZaplaty` | money | Yes |
| `dok_KwKredyt` | money | Yes |
| `dok_KwReszta` | money | Yes |
| `dok_Waluta` | char(3) | Yes |
| `dok_WalutaKurs` | money | No |
| `dok_Uwagi` | varchar(500) | No |
| `dok_KatId` | int | Yes |
| `dok_Tytul` | varchar(50) | No |
| `dok_Podtytul` | varchar(50) | No |
| `dok_Status` | int | No |
| `dok_StatusKsieg` | int | No |
| `dok_StatusFiskal` | int | No |
| `dok_StatusBlok` | bit | No |
| `dok_JestTylkoDoOdczytu` | bit | No |
| `dok_JestRuchMag` | bit | No |
| `dok_JestZmianaDatyDokKas` | bit | No |
| `dok_JestHOP` | bit | No |
| `dok_JestVatNaEksport` | bit | No |
| `dok_JestVatAuto` | bit | No |
| `dok_Algorytm` | int | No |
| `dok_KartaId` | int | Yes |
| `dok_KredytId` | int | Yes |
| `dok_RodzajOperacjiVat` | int | No |
| `dok_KodRodzajuTransakcji` | int | Yes |
| `dok_StatusEx` | int | Yes |
| `dok_ObiektGT` | int | Yes |
| `dok_Rozliczony` | bit | No |
| `dok_RejId` | int | Yes |
| `dok_TerminRealizacji` | datetime | Yes |
| `dok_WalutaLiczbaJednostek` | int | No |
| `dok_WalutaRodzajKursu` | int | Yes |
| `dok_WalutaDataKursu` | datetime | Yes |
| `dok_WalutaIdBanku` | int | Yes |
| `dok_CenyLiczbaJednostek` | int | No |
| `dok_CenyRodzajKursu` | int | Yes |
| `dok_CenyDataKursu` | datetime | Yes |
| `dok_CenyIdBanku` | int | Yes |
| `dok_KwPrzelew` | money | Yes |
| `dok_KwGotowkaPrzedplata` | money | Yes |
| `dok_KwPrzelewPrzedplata` | money | Yes |
| `dok_DefiniowalnyId` | int | Yes |
| `dok_TransakcjaId` | int | Yes |
| `dok_TransakcjaSymbol` | varchar(30) | Yes |
| `dok_TransakcjaData` | datetime | Yes |
| `dok_PodsumaVatFSzk` | int | Yes |
| `dok_ZlecenieId` | int | Yes |
| `dok_NaliczajFundusze` | bit | No |
| `dok_PrzetworzonoZKwZD` | bit | Yes |
| `dok_VatMarza` | bit | Yes |
| `dok_DstNr` | int | Yes |
| `dok_DstNrRoz` | char(3) | Yes |
| `dok_DstNrPelny` | varchar(30) | Yes |
| `dok_ObslugaDokDost` | int | Yes |
| `dok_AkcyzaZwolnienieId` | int | Yes |
| `dok_ProceduraMarzy` | int | No |
| `dok_FakturaUproszczona` | bit | No |
| `dok_DataZakonczenia` | datetime | Yes |
| `dok_MetodaKasowa` | bit | No |
| `dok_TypNrIdentNabywcy` | int | No |
| `dok_NrIdentNabywcy` | varchar(20) | Yes |
| `dok_AdresDostawyId` | int | Yes |
| `dok_AdresDostawyAdreshId` | int | Yes |
| `dok_VenderoId` | int | Yes |
| `dok_VenderoSymbol` | varchar(30) | Yes |
| `dok_VenderoData` | datetime | Yes |
| `dok_SelloId` | int | Yes |
| `dok_SelloSymbol` | varchar(100) | Yes |
| `dok_SelloData` | datetime | Yes |
| `dok_TransakcjaJednolitaId` | int | Yes |
| `dok_PodpisanoElektronicznie` | bit | Yes |
| `dok_UwagiExt` | varchar(3500) | No |
| `dok_VenderoStatus` | int | Yes |
| `dok_ZaimportowanoDoEwidencjiAkcyzowej` | bit | Yes |
| `dok_TermPlatStatus` | int | Yes |
| `dok_TermPlatTransId` | nvarchar(128) | Yes |
| `dok_DokumentFiskalnyDlaPodatnikaVat` | bit | No |
| `dok_CesjaPlatnikOdbiorca` | bit | No |
| `dok_WartOplRecykl` | money | No |
| `dok_TermPlatIdKonfig` | int | Yes |
| `dok_TermPlatIdZadania` | int | Yes |
| `dok_PromoZenCardStatus` | int | Yes |
| `dok_NrRachunkuBankowegoPdm` | int | Yes |
| `dok_SzybkaPlatnosc` | bit | No |
| `dok_MechanizmPodzielonejPlatnosci` | bit | No |
| `dok_IdSesjiKasowej` | int | Yes |
| `dok_KwKartaPrzedplata` | money | Yes |
| `dok_IdPanstwaRozpoczeciaWysylki` | int | Yes |
| `dok_IdPanstwaKonsumenta` | int | Yes |
| `dok_InformacjeDodatkowe` | varchar(255) | Yes |
| `dok_ZnacznikiGTUNaPozycji` | bit | No |
| `dok_TypDatyUjeciaKorekty` | int | Yes |
| `dok_DataUjeciaKorekty` | datetime | Yes |
| `dok_IdPrzyczynyZwolnieniaZVAT` | int | Yes |
| `dok_NumerKSeF` | varchar(64) | Yes |
| `dok_DataNumeruKSeF` | datetime | Yes |
| `dok_DoNumerKSeF` | varchar(64) | Yes |
| `dok_KodRodzajuTransportu` | int | Yes |
| `dok_DodatkoweInfoRodzajuTransportu` | varchar(350) | No |
| `dok_CzasWysylkiTransportu` | varchar(8) | No |
| `dok_CzasPrzewozuTransportu` | varchar(3) | No |
| `dok_StatusKSeF` | int | No |
| `dok_KorektaDanychNabywcy` | bit | No |
| `dok_WegielNumerOswiadczenia` | varchar(50) | Yes |
| `dok_SesjaKSeF` | varchar(64) | Yes |
| `dok_IdPrzetwarzaniaKSeF` | varchar(64) | Yes |
| `dok_SrodowiskoKSeF` | int | Yes |
| `dok_BladKSeF` | varchar(-1) | Yes |
| `dok_DataRozpoczeciaPrzetwarzaniaKSeF` | datetime | Yes |
| `dok_XmlHashKSeF` | varchar(64) | Yes |
| `dok_TermPlatTerminalId` | nvarchar(40) | Yes |
| `dok_FiskalizacjaNumer` | nvarchar(60) | Yes |
| `dok_FiskalizacjaData` | datetime | Yes |
| `dok_FiskalizacjaIdUrzadzenia` | nvarchar(40) | Yes |
| `dok_DataWystawieniaKSeF` | datetime | Yes |
| `dok_FormaDokumentowania` | int | No |
| `dok_CzekaNaKSeF` | bit | No |
| `dok_VatRozliczanyPrzezUslugobiorce` | bit | No |
| `dok_PodlegaOplSpec` | bit | No |
| `dok_DataNumeruKSeFOryg` | varchar(100) | Yes |
| `dok_RolaOdbiorcyKSeF` | int | No |
| `dok_VatMetodaLiczenia` | int | Yes |
| `dok_TerminWysylkiDoKSeF` | datetime | Yes |
| `dok_ZrealizowaneZRezerwacja` | bit | Yes |
| `dok_NumerKSeFId` | int | Yes |
| `dok_DoNumerKSeFId` | int | Yes |

### dok_Pozycja

**Rows:** 924962  
**Columns:** 58

| Column | Type | Nullable |
|--------|------|----------|
| `ob_Id` | int | No |
| `ob_DoId` | int | Yes |
| `ob_Znak` | smallint | No |
| `ob_Status` | int | Yes |
| `ob_DokHanId` | int | Yes |
| `ob_DokMagId` | int | Yes |
| `ob_TowId` | int | Yes |
| `ob_TowRodzaj` | int | No |
| `ob_Opis` | varchar(255) | Yes |
| `ob_DokHanLp` | int | Yes |
| `ob_DokMagLp` | int | Yes |
| `ob_Ilosc` | money | No |
| `ob_IloscMag` | money | No |
| `ob_Jm` | varchar(10) | Yes |
| `ob_CenaMag` | money | No |
| `ob_CenaWaluta` | money | No |
| `ob_CenaNetto` | money | No |
| `ob_CenaBrutto` | money | No |
| `ob_Rabat` | money | No |
| `ob_WartMag` | money | No |
| `ob_WartNetto` | money | No |
| `ob_WartVat` | money | No |
| `ob_WartBrutto` | money | No |
| `ob_VatId` | int | Yes |
| `ob_VatProc` | money | No |
| `ob_Termin` | datetime | Yes |
| `ob_MagId` | int | Yes |
| `ob_NumerSeryjny` | varchar(40) | Yes |
| `ob_KategoriaId` | int | Yes |
| `ob_Akcyza` | bit | Yes |
| `ob_AkcyzaKwota` | money | Yes |
| `ob_AkcyzaWartosc` | money | Yes |
| `ob_CenaNabycia` | money | Yes |
| `ob_WartNabycia` | money | Yes |
| `ob_PrzyczynaKorektyId` | int | Yes |
| `ob_CenaPobranaZCennika` | int | Yes |
| `ob_TowPkwiu` | varchar(20) | Yes |
| `ob_TowKodCN` | varchar(10) | Yes |
| `ob_SyncId` | varchar(255) | Yes |
| `ob_OplCukrowaPodlega` | bit | Yes |
| `ob_OplCukrowaObj` | money | Yes |
| `ob_OplCukrowaKwCukier` | money | Yes |
| `ob_OplCukrowaKwKofeina` | money | Yes |
| `ob_OplCukrowaKwSuma` | money | Yes |
| `ob_OplCukrowaWartCukier` | money | Yes |
| `ob_OplCukrowaWartKofeina` | money | Yes |
| `ob_OplCukrowaWartSuma` | money | Yes |
| `ob_OplCukrowaKwCukierEx` | money | Yes |
| `ob_OplCukrowaParametry` | int | Yes |
| `ob_OplCukrowaCukierZawartoscEx` | money | Yes |
| `ob_OznaczenieGTU` | int | Yes |
| `ob_KsefUUID` | varchar(50) | Yes |
| `ob_WegielOpisPochodzenia` | varchar(255) | No |
| `ob_WegielDataWprowadzeniaLubNabycia` | varchar(255) | No |
| `ob_IdOpakowanieKaucyjne` | int | Yes |
| `ob_KaucjaRodzajOpakowania` | int | Yes |
| `ob_KaucjaOpakowanieZwracane` | bit | Yes |
| `ob_DodatkoweInformacjeSystemuKaucyjnego` | varchar(14) | Yes |

### sl_Magazyn

**Rows:** 11  
**Columns:** 11

| Column | Type | Nullable |
|--------|------|----------|
| `mag_Id` | int | No |
| `mag_Symbol` | varchar(3) | No |
| `mag_Nazwa` | varchar(50) | No |
| `mag_Status` | int | No |
| `mag_Opis` | varchar(255) | Yes |
| `mag_Analityka` | varchar(5) | Yes |
| `mag_Glowny` | bit | No |
| `mag_POS` | bit | No |
| `mag_POSIdent` | uniqueidentifier | Yes |
| `mag_POSNazwa` | varchar(255) | Yes |
| `mag_POSAdres` | varchar(82) | Yes |

### sl_StawkaVAT

**Rows:** 41  
**Columns:** 16

| Column | Type | Nullable |
|--------|------|----------|
| `vat_Id` | int | No |
| `vat_Nazwa` | varchar(50) | No |
| `vat_Stawka` | money | No |
| `vat_Symbol` | varchar(20) | No |
| `vat_CzySystemowa` | bit | No |
| `vat_CzyWidoczna` | bit | No |
| `vat_Pozycja` | int | No |
| `vat_PozSprzedaz` | int | No |
| `vat_PozZakup` | int | No |
| `vat_PozRR` | int | No |
| `vat_PozDomyslna` | int | No |
| `vat_Rodzaj` | int | No |
| `vat_StawkaZagraniczna` | bit | No |
| `vat_StawkaZagranicznaPdst` | bit | Yes |
| `vat_IdPanstwo` | int | Yes |
| `vat_UePanstwo` | bit | Yes |

## All Tables

| Table | Columns |
|-------|---------|
| APPLOG | 12 |
| LEO_Aplikacje_Cele | 7 |
| LEO_Aplikacje_LicenceInfo | 4 |
| LEO_Aplikacje_Lista | 3 |
| LEO_Aplikacje_Zgody | 7 |
| LEO_Aplikacje_Zgody_Historia | 8 |
| LEO_Synchronizator_Shoper_Blokada | 2 |
| LEO_Synchronizator_Shoper_Grupy | 2 |
| LEO_Synchronizator_Shoper_Konfiguracja | 2 |
| LEO_Synchronizator_Shoper_NrListuPrzewozowegoZK | 3 |
| LEO_Synchronizator_Shoper_StatusyZK | 3 |
| LEO_Synchronizator_Shoper_Uprawnienia | 2 |
| LEO_Synchronizator_Shoper_Uprawnienie_Grupa | 3 |
| LEO_Synchronizator_Shoper_Uzytkownik_Grupa | 3 |
| LEO_Synchronizator_Shoper_ZmienioneTw | 1 |
| LEO_Synchronizator_Shoper_ZmienioneZdjecia | 5 |
| LEO_Synchronizator_Shoper___KAYO_Blokada | 2 |
| LEO_Synchronizator_Shoper___KAYO_Grupy | 2 |
| LEO_Synchronizator_Shoper___KAYO_Konfiguracja | 2 |
| LEO_Synchronizator_Shoper___KAYO_NrListuPrzewozowegoZK | 3 |
| LEO_Synchronizator_Shoper___KAYO_StatusyZK | 3 |
| LEO_Synchronizator_Shoper___KAYO_Uprawnienia | 2 |
| LEO_Synchronizator_Shoper___KAYO_Uprawnienie_Grupa | 3 |
| LEO_Synchronizator_Shoper___KAYO_Uzytkownik_Grupa | 3 |
| LEO_Synchronizator_Shoper___KAYO_ZmienioneTw | 1 |
| LEO_Synchronizator_Shoper___KAYO_ZmienioneZdjecia | 5 |
| LEO_Synchronizator_Shoper___RXF_Blokada | 2 |
| LEO_Synchronizator_Shoper___RXF_Grupy | 2 |
| LEO_Synchronizator_Shoper___RXF_Konfiguracja | 2 |
| LEO_Synchronizator_Shoper___RXF_NrListuPrzewozowegoZK | 3 |
| LEO_Synchronizator_Shoper___RXF_StatusyZK | 3 |
| LEO_Synchronizator_Shoper___RXF_Uprawnienia | 2 |
| LEO_Synchronizator_Shoper___RXF_Uprawnienie_Grupa | 3 |
| LEO_Synchronizator_Shoper___RXF_Uzytkownik_Grupa | 3 |
| LEO_Synchronizator_Shoper___RXF_ZmienioneTw | 1 |
| LEO_Synchronizator_Shoper___RXF_ZmienioneZdjecia | 5 |
| MPP_BaseLinkerSku | 4 |
| MPP_CiagPolaczeniowySubiekt | 5 |
| MPP_DaneIdentyfikacyjneZeSkanowania | 14 |
| MPP_DystrybutorSprzedazTowaru | 17 |
| MPP_DystrybutorSprzedazTowaruCertyfikat | 11 |
| MPP_FlagaZK | 8 |
| MPP_KompletacjaWydania | 5 |
| MPP_Odprawy | 2 |
| MPP_OdprawyVIN | 3 |
| MPP_OutApiMap | 25 |
| MPP_Palety | 8 |
| MPP_PotwierdzeniaStanuMagazynowego | 11 |
| MPP_RaportSprzedazyPlanMiesiaca | 7 |
| MPP_RealizacjaZK | 18 |
| MPP_Sklepy | 6 |
| MPP_SklepyStanMagazynuDokumenty | 12 |
| MPP_SklepyStanyMagazynu | 11 |
| MPP_SlownikiRaportKategoria | 2 |
| MPP_SlownikiRaportOpiekun | 2 |
| MPP_TowarDystrybutoraZaznaczenie | 6 |
| MPP_Uzytkownicy | 22 |
| MPP_UzytkownikMagazyny | 4 |
| MPP_UzytkownikOpiekunowie | 3 |
| MPP_ZdarzeniaMagazynowe | 10 |
| PejczykGT_history | 14 |
| ZYX_GEES_EC_ATR | 7 |
| ZYX_GEES_EC_CATY | 10 |
| ZYX_GEES_EC_KTH | 20 |
| ZYX_GEES_EC_PRODUCTS | 28 |
| ZYX_GEES_EC_PRODUCTS_CTG | 4 |
| ZYX_GEES_EC_PRODUKTY | 22 |
| ZYX_GEES_EC_PRODUKTY_OPISY | 11 |
| ZYX_GEES_EC_RELACJE | 7 |
| ZYX_GEES_EC_SERWISY | 13 |
| ZYX_GEES_EC_SLOWNIKI | 9 |
| ZYX_GEES_EC_UZ | 4 |
| ZYX_GEES_EC_ZAMOWIENIA | 28 |
| ZYX_GEES_EC_ZAMOWIENIA_POZ | 19 |
| ZYX_GEES_PARAMETRY | 5 |
| ZYX_GEES_UMOWA | 26 |
| ZYX_GEES_WYSYLKA | 26 |
| ZYX_GEES_WYSYLKA_BOOK | 17 |
| __BeforeDropOldTables | 2 |
| __EFMigrationsHistory | 2 |
| __Modyfikacja | 4 |
| __NoCheckAddConstraint | 2 |
| __PostUpdate | 2 |
| __Slowniki | 4 |
| __Tabele | 7 |
| __Update | 2 |
| __atiScheduler | 24 |
| __ati_IParametry | 5 |
| __ati_ImpNalLog | 24 |
| __ati_Licencje | 6 |
| __ati_ReportSendDok | 2 |
| __ati_SendStatusW | 8 |
| __ati_SzablonKryteriow | 23 |
| __ati_SzablonRapWew | 8 |
| _ati_SzablonyWindykacja | 27 |
| ab_AktualizacjeBiznesowe | 16 |
| ab_Licznik | 5 |
| adr_Email | 7 |
| adr_Historia | 28 |
| adr__Ewid | 29 |
| ap_Log | 10 |
| ap_LogOpis | 4 |
| ap_Zapisy | 11 |
| ap__AP | 13 |
| bib_Dokument | 21 |
| bib_ZawartoscPliku | 4 |
| ca_ClientAccountParams | 3 |
| cen_CennikCecha | 3 |
| cen_CennikDokument | 3 |
| cen_CennikElement | 3 |
| cen_CennikGrupa | 3 |
| cen_CennikKolumna | 8 |
| cen_CennikSzablon | 17 |
| cent_Parametr | 14 |
| cert_Info | 2 |
| cp__CelPrzetwarzania | 8 |
| crm_Parametr | 5 |
| cs_Skrypt | 15 |
| ctx_Grupa | 4 |
| ctx__Konfiguracja | 3 |
| dekl_CukierDokument | 8 |
| dekl_CukierDokumentTow | 3 |
| dekl_DeklVIUDO | 3 |
| dekl_IntrastatPole | 15 |
| dekl_JpkV7Pole | 5 |
| dekl_Parametr | 4 |
| dekl_Pfron | 19 |
| dekl_PitZdPole | 19 |
| dekl_Plik | 13 |
| dekl_Pole | 9 |
| dekl_PoleVIUDO | 9 |
| dekl_VatUePole | 10 |
| dekl_VatZdPole | 9 |
| dekl_WersjaLatest | 51 |
| dekl_WersjaOld1 | 40 |
| dekl_Zus | 6 |
| dekl__Ewid | 26 |
| dekl_eDekl | 18 |
| dekl_eDeklLog | 4 |
| dekp__Naliczenie | 6 |
| dekz_Deklaracja | 5 |
| dekz_Pozycja | 5 |
| dekz_PozycjaOpis | 9 |
| dekz__Naliczenie | 7 |
| dfw_Pozycja | 35 |
| dfw_Vat | 7 |
| dfw__FakturyWewnetrzne | 46 |
| dg_NieobsluzoneZdarzenia | 6 |
| dkr_Automat | 9 |
| dkr_AutomatPozycja | 13 |
| dkr_BilansOtwarcia | 10 |
| dkr_BilansOtwarciaDostawy | 9 |
| dkr_BilansOtwarciaZmiana | 6 |
| dkr_BilansOtwarciaZmianaSzczegoly | 7 |
| dkr_DokImportowany | 4 |
| dkr_Parametr | 35 |
| dkr_ParametrDokDoDekretacji | 14 |
| dkr_ParametrDziennika | 3 |
| dkr_PieczecKsiegowa | 3 |
| dkr_PieczecKsiegowaPozycja | 7 |
| dkr_Pozycja | 24 |
| dkr_PozycjaWydatekNaPojazd | 2 |
| dkr_RoznicaKursowa | 12 |
| dkr_RoznicaKursowaPozycja | 4 |
| dkr_SladRewizyjny | 9 |
| dkr_WydatekNaPojazd | 10 |
| dkr_Wzorzec | 19 |
| dkr_WzorzecPozycja | 12 |
| dkr__Dokument | 47 |
| dks_HomeBanking | 3 |
| dks_Kasa | 16 |
| dks_KasaBO | 4 |
| dks_KasaProfil | 2 |
| dks_KasaTransferProfil | 2 |
| dks_ParametrCesja | 9 |
| dks_ParametrFinansowy | 23 |
| dnk_NotaDokKorygowane | 3 |
| dnk__NotaKorygujaca | 27 |
| dok_DokBiblioteka | 10 |
| dok_DokCzas | 4 |
| dok_DokumentDefiniowalny | 15 |
| dok_DokumentDefiniowalnyOperacja | 3 |
| dok_DokumentDefiniowalnyWzw | 4 |
| dok_DokumentKonwersja131SP2 | 11 |
| dok_DokumentTechniczny | 2 |
| dok_FunduszPromocji | 7 |
| dok_MagDysp | 7 |
| dok_MagRuch | 11 |
| dok_MagWart | 4 |
| dok_MagWartKKDR | 4 |
| dok_OstrzezeniePoKonwTabVat81 | 2 |
| dok_OznaczeniaJpkVat | 34 |
| dok_Parametr | 155 |
| dok_ParametrDF | 6 |
| dok_ParametrTP | 6 |
| dok_PowiazanieFSdPA | 3 |
| dok_Pozycja | 58 |
| dok_Promocja | 32 |
| dok_PromocjaKontrahent | 3 |
| dok_PromocjaTowar | 3 |
| dok_StatusWydruku | 4 |
| dok_SzczegolyTransportu | 3 |
| dok_UzytePromocje | 2 |
| dok_Vat | 10 |
| dok__Dokument | 169 |
| dp_Etykieta | 5 |
| dp_Parametr | 6 |
| dp_PasujaceSchematy | 4 |
| dp_Plik | 8 |
| dp__Dokument | 40 |
| dw_Parametr | 13 |
| dw_Pozycja | 7 |
| dw__Dokument | 15 |
| ecp_Absencja | 28 |
| ecp_AbsencjaPrzedKorekta | 22 |
| ecp_Blokada | 7 |
| ecp_Ekwiwalent | 8 |
| ecp_Godzina | 6 |
| ecp_GodzinaPrzedKorekta | 5 |
| ecp_Obecnosc | 13 |
| ecp_ObecnoscPrzedKorekta | 12 |
| ecp_OdprawaEmerytalna | 5 |
| ecp__Zapis | 12 |
| ed_ZFSS | 9 |
| edd_EDokDostawy | 11 |
| edd_Parametr | 16 |
| edd_ReceivedStatusUpdate | 8 |
| em_AccVisibleTo | 3 |
| em_Account | 36 |
| em_AccountFolder | 5 |
| em_Archive | 3 |
| em_Attachment | 4 |
| em_Properties | 11 |
| em_Rule | 14 |
| em_RuleWord | 4 |
| em_SearchContent | 3 |
| em_SendersList | 4 |
| em_Signature | 6 |
| em_Source | 6 |
| em_Template | 15 |
| em__Email | 42 |
| es_SprawozdanieFinElement | 7 |
| es_SprawozdanieFinNaglowek | 15 |
| es_WysylkaElektroniczna | 10 |
| es_WysylkaElektronicznaLog | 4 |
| ewa__EwidencjeAkcyzowe | 37 |
| ex_SciezkaDomyslna | 5 |
| fl_Grupy | 2 |
| fl_Wartosc | 7 |
| fl__Flagi | 9 |
| fnx_CechaTwSynch | 7 |
| fnx_CenaTwSynch | 16 |
| fnx_DocSynch | 14 |
| fnx_FirmaSynch | 6 |
| fnx_GrupaTwSynch | 6 |
| fnx_JednostkaMiaryTwSynch | 7 |
| fnx_KategoriaSynch | 6 |
| fnx_KlientSynch | 3 |
| fnx_KontrahentSklepuSynch | 6 |
| fnx_KontrahentSynch | 6 |
| fnx_MagazynSynch | 7 |
| fnx_Newsletter | 6 |
| fnx_ParametrSynch | 15 |
| fnx_PlatnoscSynch | 6 |
| fnx_PoleWlasneSynch | 6 |
| fnx_PromocjaPoziomCenSynch | 7 |
| fnx_PromocjaSynch | 6 |
| fnx_PromocjaTowarSynch | 7 |
| fnx_SlCechaTwSynch | 6 |
| fnx_SlModelTwSynch | 6 |
| fnx_SlRabatSynch | 6 |
| fnx_SlWalutaSynch | 6 |
| fnx_SlWlasciwoscCechaTwSynch | 6 |
| fnx_SlWlasciwoscTwSynch | 6 |
| fnx_StanSynch | 7 |
| fnx_StawkaVatSynch | 6 |
| fnx_TowarKodySynch | 6 |
| fnx_TowarSynch | 8 |
| fnx_ZdjecieTwSynch | 7 |
| fnx__Feniks | 19 |
| fo_ObiektDefinicja | 27 |
| fo_ObiektPole | 36 |
| fo_ObiektZakladka | 7 |
| fo_ZakladkaWlasnaPowiazanie | 4 |
| getit_LogKomunikacji | 14 |
| getit_konfiguracja | 43 |
| gr_FiltrWartosc | 5 |
| gr_FiltrWlasny | 4 |
| gr_Formatowanie | 8 |
| gr_FormatowanieUzytkownik | 2 |
| gr_FormatowanieWarunek | 7 |
| gr_GridWlasny | 9 |
| gr_KomponentKalendarzowyGodzinyPracy | 4 |
| gr_Miniaturka | 4 |
| gr_Nazwa | 2 |
| gr_WyszukiwanieWlasne | 7 |
| gr__Grid | 13 |
| gr__Konfiguracja | 7 |
| gr__KonfiguracjaEx | 10 |
| gt_Atrybut | 5 |
| gt_Definicja | 4 |
| gt_Plik | 3 |
| gt_TransObiekt | 3 |
| gt_TransRodzaj | 2 |
| gt_Transformacja | 9 |
| gt_TransformacjaProfil | 2 |
| gt__Obiekt | 4 |
| hb_EBankParam | 2 |
| hb_Ident | 3 |
| hb_Login | 6 |
| hb_NaglowekIStopka | 7 |
| hb_Parser | 5 |
| hb_PowiazanieTransakcji | 3 |
| hb_PrzedrostekHist | 3 |
| hb_Raport | 9 |
| hb_SynchronizacjaRachunkuBankowego | 3 |
| hb_ToParamZnakSpecjalny | 7 |
| hb_Transakcja | 25 |
| hb_TransakcjaElektroniczna | 3 |
| hb_TransakcjaOczekujaca | 43 |
| hb_TransakcjaOczekujacaObiekt | 8 |
| hb_TransakcjaOczekujacaParam | 3 |
| hb_Usluga | 5 |
| icen_CennikCechaKh | 3 |
| icen_CennikGrupaKh | 3 |
| icen_CennikKontrahent | 3 |
| icen_CennikMagazyn | 3 |
| icen_CennikTowar | 5 |
| icen_CennikiParametr | 2 |
| icen_CennikiParametrTyp | 9 |
| icen__CennikiIndywidualne | 28 |
| idx_tw__Towar | 3 |
| im_ImportLog | 11 |
| im_ImportPrzeprowadzony | 14 |
| im_SchematImportu | 118 |
| im_SchematImportuAnalitykiKP | 7 |
| im_SchematImportuCechaKontrahenta | 3 |
| im_SchematImportuEtykiety | 3 |
| im_SchematImportuGrupaKontrahenta | 3 |
| im_SchematImportuKategoria | 3 |
| im_SchematImportuKontrahent | 3 |
| im_SchematImportuKwotyUzytkownika | 4 |
| im_SchematImportuOpisy | 7 |
| im_SchematImportuOznaczeniaJpkVat | 4 |
| im_SchematImportuPozDekretu | 19 |
| im_SchematImportuPozycjaRach | 4 |
| im_SchematImportuRodzajFaktury | 3 |
| im_SchematImportuTypTransakcji | 3 |
| im_SchematImportuZPiK | 7 |
| ink_ZlecenieWindykacji | 5 |
| ins_Slad | 6 |
| ins_Szpieg | 13 |
| ins_SzpiegParametr | 2 |
| ins_SzpiegParametrTyp | 6 |
| ins_blokada | 7 |
| ins_ident | 3 |
| insx_Parametr | 19 |
| int_ParametryIntrastat | 9 |
| is_RodzajInstytucji | 2 |
| is__Instytucja | 15 |
| iw_Cechy | 3 |
| iw_Dyspozycja | 9 |
| iw_Flagi | 3 |
| iw_Grupy | 3 |
| iw_Powiazania | 3 |
| iw_Pozycja | 21 |
| iw_Rozbicie | 12 |
| iw__Dokument | 45 |
| jpk_Paczka | 10 |
| jpk_Parametr | 18 |
| jpk_Plik | 21 |
| jpk_PolaNiestandardowe | 6 |
| jpk_Typ | 4 |
| jpk_Wersja | 9 |
| jpk_WysylkaElektroniczna | 9 |
| jpk_WysylkaElektronicznaLog | 4 |
| kfg_PasekSkrotow | 7 |
| kh_AdresyDostawy | 3 |
| kh_CechaKh | 3 |
| kh_CechaPrac | 3 |
| kh_Dokument | 10 |
| kh_KategoriaDokumentu | 5 |
| kh_Lista | 6 |
| kh_ListaFiltr | 3 |
| kh_ListaKh | 3 |
| kh_OznaczeniaJpkVat | 30 |
| kh_Parametr | 123 |
| kh_ParametrG | 10 |
| kh_Pracownik | 34 |
| kh_RachunkiBankoweHistoriaWeryfikacjiBialaLista | 9 |
| kh_TransakcjaJednolita | 7 |
| kh_Vies | 11 |
| kh_WeryfikacjaNIP | 12 |
| kh_WeryfikacjaWykazPodatnikowVAT | 15 |
| kh_Zgody | 9 |
| kh__Kontrahent | 158 |
| kk_KodKreskowyParam | 7 |
| kom_FrParametr | 8 |
| kom_KomPrzeprowadzona | 9 |
| kom_KomunikacjaLog | 7 |
| kom_Parametr | 38 |
| kor_Pozycja | 13 |
| kor__KorektaKosztow | 31 |
| kp_Akord | 13 |
| kp_KomornikPozyczkaDefinicja | 20 |
| kp_NaliczeniePotracenie | 11 |
| kp_PozycjaDefinicji | 10 |
| kp_Prowizja | 13 |
| kpr_Parametr | 32 |
| kpr__Ksiega | 64 |
| ks_Klasyfikatory | 6 |
| ks_KlasyfikatoryUklad | 7 |
| ks_PrzypisaniaWlasne | 4 |
| ksef_AutomatWysylkiHarmonogram | 3 |
| ksef_Certyfikat | 4 |
| ksef_Etykieta | 3 |
| ksef_Faktury | 26 |
| ksef_FakturyHandel | 4 |
| ksef_FakturyKsiegowosc | 22 |
| ksef_HistoriaStatusow | 11 |
| ksef_NumerKSeF | 4 |
| ksef_Parametry | 90 |
| ksef_ParametryAlgorytmyMapowaniaTw | 6 |
| ksef_ParametryAutomatWysylki | 8 |
| ksef_PasujaceSchematy | 4 |
| ksef_Pliki | 3 |
| ksef_Podglad | 6 |
| ksef_Token | 4 |
| ksef_UPO | 4 |
| ksef_WysylkaDokumentow | 2 |
| kw_Parametr | 54 |
| kw_Pozycja | 15 |
| kw__Karta | 3 |
| lab_Params | 17 |
| len_tw__Towar | 2 |
| log_Logowanie | 13 |
| log_OdrzLicencje | 34 |
| log_Parametry | 8 |
| logksef_LogowanieKSeF | 8 |
| lsp__LinkDoSzybkiejPlatnosci | 12 |
| mi_KhPomijany | 3 |
| mi_MapaAsortyment | 7 |
| mi_Sprzedaz | 25 |
| mi_SprzedazAdres | 6 |
| mi_SprzedazParametr | 17 |
| mi_SprzedazPozycja | 10 |
| mj_MapowanieJednostek | 3 |
| mk_Korekta | 10 |
| mk_Koszt | 19 |
| mk_Przesuniecie | 7 |
| mk_Rata | 9 |
| nav_Parametry | 10 |
| net_Info | 19 |
| net_Parametr | 6 |
| net_ParametrInd | 11 |
| net_ParametrInst | 10 |
| net_ParametrInstKomputer | 2 |
| net_ParametrKomputer | 2 |
| net_Wiadomosc | 9 |
| net_WiadomoscBufor | 5 |
| net_WiadomoscCache | 5 |
| net_WiadomoscFlaga | 6 |
| net_WiadomoscWersja | 4 |
| net_WiadomoscZalacznik | 4 |
| nk_PoleSzablonu | 21 |
| nk_Szablon | 14 |
| nk_UkladWzorcowy | 12 |
| nk_ZestawDanych | 9 |
| nk_ZrodloDanych | 11 |
| not_Notatka | 8 |
| nr_NrRez | 7 |
| nr_NrStart | 9 |
| nr_NrStartDkr | 6 |
| nr_NrStartRK | 5 |
| nr_Parametr | 10 |
| nr_ParametrDkr | 5 |
| nz_Cesja | 7 |
| nz_CesjaParametr | 10 |
| nz_CesjaParametrTP | 6 |
| nz_CesjaParametrySzybkiePlatnosci | 5 |
| nz_FinanseHistoriaWindykacji | 5 |
| nz_FinanseNota | 8 |
| nz_FinanseNotaPozycja | 8 |
| nz_FinanseSplata | 25 |
| nz_FinanseSplataVat | 6 |
| nz_Kompensata | 16 |
| nz_KompensataPozycja | 4 |
| nz_KorektaPIT | 10 |
| nz_KorektaZaliczenie | 9 |
| nz_OdsetkiKarne | 3 |
| nz_PowiaznaniePP | 3 |
| nz_RaportKasowy | 14 |
| nz_RaportKasowyDokumentKasowy | 5 |
| nz_RaportKasowyStan | 5 |
| nz_RozDekret | 5 |
| nz_RozniceLog | 3 |
| nz_SposobNaliczeniaOdsetek | 7 |
| nz_SyncHistoriaRozliczenia | 63 |
| nz_SyncHistoriaRozliczeniaPozostalo | 5 |
| nz_SyncHistoriaWiarygodnoscPlatnicza | 12 |
| nz_SyncRozrachunkiKontrahenta | 21 |
| nz_SyncRozrachunkiKontrahentaRazem | 9 |
| nz_TypZdarzeniaWindykacyjnego | 3 |
| nz_WizardPP | 3 |
| nz_WyciagBankowy | 17 |
| nz_WyciagBankowyOperacjaBankowa | 4 |
| nz__Finanse | 98 |
| ob_Powiazane | 10 |
| odw_OdWykSzablon | 6 |
| odw_OdWykTemp | 3 |
| omr_ObjMetadataRecords | 8 |
| oss_DaneVAT | 12 |
| oss_Parametr | 28 |
| oss_Pozycja | 6 |
| oss__Ewid | 61 |
| par_EwidProfil | 2 |
| par_Typ | 10 |
| par_TypProfil | 2 |
| par__Ewid | 5 |
| pb_KatalogBlokada | 4 |
| pb_Parametr | 4 |
| pb_Plik | 9 |
| pb__Dokument | 30 |
| pd_Blokada | 9 |
| pd_BlokadaObiekt | 9 |
| pd_BlokadaObiektGrupa | 4 |
| pd_Dokument | 10 |
| pd_Fobos | 6 |
| pd_KonwersjaInfo | 5 |
| pd_KonwersjaOdlaczanie | 7 |
| pd_Odlaczanie | 3 |
| pd_Okres | 6 |
| pd_Ostrzezenia | 4 |
| pd_Parametr | 11 |
| pd_ParametrHaslo | 9 |
| pd_ParametrHist | 10 |
| pd_PodmiotInfo | 4 |
| pd_Produkt | 4 |
| pd_ProduktProfil | 2 |
| pd_RokObrotowy | 19 |
| pd_RozszerzeniaUzytkownika | 7 |
| pd_Sesja | 10 |
| pd_Statystyka | 4 |
| pd_Ulubione | 4 |
| pd_Uprawnienie | 9 |
| pd_UzytkMagazyn | 2 |
| pd_UzytkModulHist | 10 |
| pd_UzytkOkres | 2 |
| pd_UzytkParam | 26 |
| pd_UzytkRok | 2 |
| pd_UzytkUpraw | 3 |
| pd_UzytkUprawMag | 4 |
| pd_Uzytkownik | 36 |
| pd_UzytkownikOddzial | 3 |
| pd_Wspolnik | 65 |
| pd_WspolnikPodatek | 8 |
| pd_WspolnikPodstawa | 5 |
| pd_WspolnikSkladka | 50 |
| pd_WspolnikSposobSkladka | 21 |
| pd_WspolnikSwiadczenie | 9 |
| pd_WspolnikUlgiPIT0 | 9 |
| pd__Podmiot | 94 |
| pgn_PluginSql | 5 |
| pk_DomyslnaKlasyfikacjaKont | 2 |
| pk_Kartoteka | 15 |
| pk_KartotekaPozycja | 4 |
| pk_KlasyfikacjaKont | 7 |
| pk_KontoZnaczniki | 5 |
| pk_Parametry | 37 |
| pk_PlanKont | 35 |
| pk_Wzorzec | 27 |
| pk_Znaczniki | 21 |
| pl_ListaPlac | 14 |
| pl_ParametrUmowyCP | 13 |
| pl_ParametrUmowyOPrace | 5 |
| pl_RachunekDoUmowyCP | 60 |
| pl_Tag | 6 |
| pl_UmowaCP | 29 |
| pl_UmowaOPrace | 24 |
| pl_UmowaOPraceSkladnik | 8 |
| pl_Wyplata | 94 |
| pl_WyplataDataZaliczki | 5 |
| pl_WyplataSkladnik | 6 |
| plb_EcpParametr | 24 |
| plb_GodzinyNormatywne | 5 |
| plb_ListaPlac | 20 |
| plb_Parametr | 29 |
| plb_ParametrWyplaty | 6 |
| plb_PracownikZespol | 3 |
| plb_RachunekDoUmowyCP | 112 |
| plb_Skladnik | 40 |
| plb_SkladnikAbsencja | 8 |
| plb_SkladnikDefinicja | 11 |
| plb_SkladnikDefinicjaKlocek | 7 |
| plb_SkryptParametr | 8 |
| plb_SzablonLP | 12 |
| plb_SzablonZP | 23 |
| plb_SzablonZPSkladnik | 3 |
| plb_Umowa | 69 |
| plb_UmowaCP | 65 |
| plb_UmowaCPGodzPrzepr | 6 |
| plb_UmowaCPHarmonogram | 31 |
| plb_UmowaCPPrzelewy | 7 |
| plb_UmowaCP_Parametry | 2 |
| plb_UmowaCP_Parametry_Zestaw | 38 |
| plb_UmowaDzialStanowisko | 13 |
| plb_UmowaKalendarz | 9 |
| plb_UmowaParametr | 14 |
| plb_UmowaPrzelewy | 7 |
| plb_UmowaSkladnik | 4 |
| plb_UmowaWyjatekCzasuPracy | 12 |
| plb_UmowaWyjatekCzasuPracyOkres | 6 |
| plb_UmowaZestaw | 24 |
| plb_UmowaZestawSkladnik | 3 |
| plb_Wyplata | 172 |
| plb_WyplataSkladnik | 23 |
| plb_WyrokTrybunalu | 3 |
| po__ParametryOnline | 8 |
| poj_Eksploatacja | 33 |
| poj_KosztyEksploatacji | 14 |
| poj_KosztyEksploatacjiWpis | 9 |
| poj_Ksiegowanie | 5 |
| poj_Parametr | 11 |
| poj_Pojazd | 15 |
| pos_Params | 10 |
| ppk_Parametry | 6 |
| ppr_Absencja | 3 |
| ppr_Blokada | 5 |
| ppr_Obecnosc | 3 |
| ppr_Parametry | 10 |
| ppr_PlanPracy | 10 |
| pr_BO | 129 |
| pr_BadaniaOkresowe | 5 |
| pr_CechaPr | 3 |
| pr_Dokument | 10 |
| pr_GIODO | 5 |
| pr_InneDochody | 8 |
| pr_Jezyki | 4 |
| pr_KosztyZPrawAutorskich | 6 |
| pr_Kursy | 5 |
| pr_KursyBHP | 5 |
| pr_Motywacja | 5 |
| pr_OkresyUrlopowBezplatnych | 4 |
| pr_Organizacje | 6 |
| pr_PPK | 10 |
| pr_Parametry | 7 |
| pr_ParametryWplatPPK | 7 |
| pr_Pracownik | 73 |
| pr_RezygnacjaPPK | 7 |
| pr_Rodzina | 32 |
| pr_Ulgi | 5 |
| pr_Uprawnienia | 5 |
| pr_Urlopy | 5 |
| pr_Zatrudnienie | 22 |
| pr_Zdjecie | 4 |
| pr_ZwolnienieZPodatku | 4 |
| prm_PromocjeAkcja | 4 |
| prm_PromocjeKontrahent | 4 |
| prm_PromocjeMagazyn | 3 |
| prm_PromocjeParametr | 2 |
| prm_PromocjeParametrTyp | 11 |
| prm_PromocjeTowar | 4 |
| prm__Promocje | 77 |
| prz_DanePrzychodu | 4 |
| prz_ObnizkaPodatku | 11 |
| prz_OdliczenieDoliczenie | 12 |
| prz_Parametr | 20 |
| prz__Przychod | 33 |
| prz__StawkaRyczaltu | 7 |
| push_ProcessedNotifications | 3 |
| pw_Dane | 64 |
| pw_Pole | 18 |
| pw_RelacjaDokDef | 3 |
| rb_RachBankowyHistoria | 9 |
| rb_RachBankowyPersonel | 13 |
| rb_RachBankowyProfil | 2 |
| rb_RachBankowySynchDomyslne | 3 |
| rb_RachBankowySynchronizacja | 3 |
| rb_RachBankowyWirtualny | 4 |
| rb_RachBankowyWirtualnyInstytucji | 3 |
| rb_RachBankowyWirtualnyKontrahenta | 3 |
| rb_RachBankowyWirtualnyPracownika | 3 |
| rb_RachBankowyWirtualnyWspolnika | 3 |
| rb__RachBankowy | 33 |
| rcp_RejestrCzynnosciPrzetwarzania | 9 |
| rcp_RejestrCzynnosciPrzetwarzaniaCele | 3 |
| rem_Pozycja | 12 |
| rem__Ewid | 9 |
| rew_Parametr | 3 |
| rf_Pozycja | 10 |
| rf_Vat | 7 |
| rf__RaportyFiskalne | 25 |
| rodo_OchronaDanychParametr | 23 |
| roz_PlikRozszerzenia | 7 |
| roz_RozszerzenieSql | 8 |
| roz_ZestKontekstowe | 5 |
| roz_ZestKontekstoweZakladka | 5 |
| roz__Rozszerzenie | 7 |
| sf2_KomponentSferyczny | 18 |
| sf2_KontrolaRozszerzen | 3 |
| sf2_OperacjaSferyczna | 10 |
| sf2_RozszerzenieGrida | 3 |
| sf2_Skrypt | 3 |
| sf_Definicja | 7 |
| sf_DefinicjaWzorzec | 6 |
| sf_Pole | 14 |
| sf_PoleNiewidoczne | 2 |
| sf_PozycjaRzs | 6 |
| sf_Rzs | 7 |
| sf_Sprawozdanie | 16 |
| sf_SprawozdanieNiewidoczne | 2 |
| sf_SprawozdanieZmiany | 4 |
| sk_Parametr | 9 |
| sk_ParametrSesjaDla | 5 |
| sk_ParametrySystemKaucyjny | 5 |
| sk_Sesja | 13 |
| sk_Stan | 7 |
| sl_AtrybutGodzinowy | 3 |
| sl_BadanieOkresowe | 2 |
| sl_Bank | 2 |
| sl_BibObiekt | 6 |
| sl_CRMPodTypZadania | 3 |
| sl_CROpis | 4 |
| sl_CechaKh | 2 |
| sl_CechaPr | 2 |
| sl_CechaTw | 2 |
| sl_CechaZs | 2 |
| sl_CrKolor | 2 |
| sl_CrmBranza | 2 |
| sl_CrmDzial | 2 |
| sl_CrmEtap | 7 |
| sl_CrmGrupaTransakcji | 2 |
| sl_CrmGrupaWiadomosci | 2 |
| sl_CrmPowitanie | 1 |
| sl_CrmRegion | 2 |
| sl_CrmScenariusz | 2 |
| sl_CrmTransakcjaNieudana | 2 |
| sl_CrmTransakcjaPozyskana | 2 |
| sl_CrmZrodloPozyskania | 2 |
| sl_Dystrybutor | 3 |
| sl_Dzial | 2 |
| sl_Etykieta | 4 |
| sl_EwidVatOss | 13 |
| sl_FormaDzialaniaWindykacyjnego | 4 |
| sl_FormaPlatnosci | 12 |
| sl_FormatNumeracji | 3 |
| sl_FormatNumeracjiElement | 12 |
| sl_Gmina | 6 |
| sl_GratAtrybutGodzinDni | 3 |
| sl_GratPrzyczynaRozwUmowy | 3 |
| sl_GratTrescDok | 6 |
| sl_GrupaBlokadyObiektu | 2 |
| sl_GrupaDokumentow | 6 |
| sl_GrupaKh | 2 |
| sl_GrupaPrac | 2 |
| sl_GrupaTw | 3 |
| sl_GrupaUz | 2 |
| sl_HarmonogramUmowyCP | 17 |
| sl_InformacjeOPodatkuAkcyzowym | 3 |
| sl_KalendCykl | 3 |
| sl_KalendDzien | 10 |
| sl_KalendGodzina | 6 |
| sl_KalendWyjGodzina | 6 |
| sl_KalendWyjatek | 12 |
| sl_Kalendarz | 4 |
| sl_Kategoria | 4 |
| sl_KategoriaDokumentu | 5 |
| sl_KategoriaPozycjiDekretu | 2 |
| sl_KategoriaZestawien | 3 |
| sl_KategorieSMS | 3 |
| sl_KodCN | 4 |
| sl_KodJednostkiTransportowejEDD | 3 |
| sl_KodPKD | 6 |
| sl_KodPocztowy | 7 |
| sl_KodPodstawyPrawnejRozwiazaniaStosunkuPracy | 3 |
| sl_KodPrzyczynyWyrejestrowania | 3 |
| sl_KodRodzajuOpakowanTwAkcyzowych | 4 |
| sl_KodRodzajuTransakcji | 4 |
| sl_KodRodzajuTransportuEDD | 3 |
| sl_KodSwiadczenia | 3 |
| sl_KodWygasnieciaStosunkuPracy | 3 |
| sl_KodWyrobuAkcyzowego | 4 |
| sl_KrajPochodzenia | 3 |
| sl_KursBHP | 2 |
| sl_LicznikNumeracji | 5 |
| sl_LicznikNumeracjiRok | 4 |
| sl_Magazyn | 11 |
| sl_MagazynProfil | 2 |
| sl_MagazynPrzesunieciaProfil | 2 |
| sl_MagazynZamowieniaMMProfil | 2 |
| sl_ModelTowar | 3 |
| sl_ModelTw | 2 |
| sl_Obywatelstwo | 3 |
| sl_OddzialKasa | 3 |
| sl_OddzialMagazyn | 3 |
| sl_OddzialNFZ | 3 |
| sl_Oddzialy | 9 |
| sl_Opis | 2 |
| sl_OpisAbsencji | 2 |
| sl_PKWiU | 5 |
| sl_PanstwaMiejscaProwadzeniaDzialalnosci | 4 |
| sl_Panstwo | 5 |
| sl_PojCel | 3 |
| sl_PojTrasa | 3 |
| sl_PojTypStawka | 4 |
| sl_Pokrewienstwo | 3 |
| sl_PowodRozwiazaniaUmowy | 2 |
| sl_PracaSzczegolnyCharakter | 3 |
| sl_PrawoDoEmerytury | 3 |
| sl_PrzyczynaKorekty | 2 |
| sl_Rabat | 4 |
| sl_RejestrKsiegowy | 25 |
| sl_RodzajDowoduKsiegowego | 3 |
| sl_RodzajKontaktu | 2 |
| sl_RodzajObnizki | 3 |
| sl_RodzajOdliczenia | 3 |
| sl_RodzajZasobu | 9 |
| sl_RodzajZasobuProfil | 2 |
| sl_StanCywilny | 2 |
| sl_Stanowisko | 4 |
| sl_StawkaAkordowa | 5 |
| sl_StawkaAkordowaProg | 5 |
| sl_StawkaProwizyjna | 4 |
| sl_StawkaProwizyjnaProg | 5 |
| sl_StawkaVAT | 16 |
| sl_StawkaZaszeregowania | 9 |
| sl_StawkiOplatSpecjalnych | 6 |
| sl_StopienNiepelnosprawnosci | 3 |
| sl_StopienNiezdolnosciDoPracy | 3 |
| sl_SubKonto | 4 |
| sl_SymbolDeklaracjiUSEBank | 5 |
| sl_SzablonDzialania | 24 |
| sl_SzablonRachunku | 3 |
| sl_SzablonUmowyCP | 3 |
| sl_SzablonUmowyOPrace | 4 |
| sl_SzybkiePlatnosci | 11 |
| sl_Tresc | 2 |
| sl_TrescDokRODO | 5 |
| sl_TrybDostawyTwAkcyzowych | 3 |
| sl_TrybOdroczonyWysylkiEDD | 3 |
| sl_TrybZakonczeniaDostawyEDD | 3 |
| sl_TypAbsencji | 4 |
| sl_TypDniaWolnego | 3 |
| sl_TypEwidVAT | 16 |
| sl_TypIdentyfikatora | 3 |
| sl_TypObecnosci | 7 |
| sl_TypPodmiotuAkcyzowego | 3 |
| sl_TypWplaty | 3 |
| sl_TytulUbezpieczenia | 3 |
| sl_Uprawnienie | 2 |
| sl_UrzadCelny | 3 |
| sl_UrzadSkarbowy | 3 |
| sl_VatOznaczeniaJPKSprzedaz | 31 |
| sl_VatOznaczeniaJPKZakup | 5 |
| sl_Waluta | 5 |
| sl_WalutaBank | 3 |
| sl_WalutaKurs | 8 |
| sl_WalutaNominal | 5 |
| sl_WalutaTabelaKursow | 5 |
| sl_WlasciwCechTw | 2 |
| sl_WlasciwoscCecha | 3 |
| sl_Wlasny | 3 |
| sl_Wojewodztwo | 2 |
| sl_Wyksztalcenie | 3 |
| sl_WzorzecSkladnikaPlacowego | 12 |
| sl_Zawod | 4 |
| sl_ZespolPrac | 2 |
| sl_ZestawAkordowy | 2 |
| sl_ZestawAkordowyAkord | 3 |
| sl_ZwolnienieZAkcyzy | 3 |
| sl_ZwolnienieZVAT | 7 |
| sl__Slownik | 6 |
| sl__SlownikProfil | 2 |
| sms_Messages | 33 |
| sms_Params | 21 |
| sms_TariffHist | 8 |
| sms_Templates | 8 |
| sp_Plik | 5 |
| sp_Transakcja | 9 |
| st_KST | 5 |
| st_KST2016 | 4 |
| st_MPK | 4 |
| st_Operacja | 49 |
| st_Parametr | 3 |
| st_ParametryCzasowe | 4 |
| st_PlanAMRok | 6 |
| st_PlanAMRozbicie | 6 |
| st_SrodekTrwaly | 25 |
| st_SrodekTrwalyDane | 16 |
| st_SrodekTrwalyPlanAM | 6 |
| st_Wyposazenie | 11 |
| stt_tw__Towar | 1 |
| su_Parametr | 78 |
| sublinker__blapiblockmechanism | 5 |
| sublinker__blorderproducts | 18 |
| sublinker__conversionrates | 8 |
| sublinker__idsync | 4 |
| sublinker__orderjournal | 3 |
| sublinker__sendfs | 2 |
| sublinker__statuschangeboflag | 3 |
| sublinker__sync_p_19d7bc7a62af4b5dad2aaa057633a81d | 2 |
| sublinker__sync_p_2bc31efc8cfa4c589284e9fc5286afa1 | 2 |
| sublinker__sync_p_2c1c20f49a87440fab6d7bf99fdfcff5 | 2 |
| sublinker__sync_p_362f279ddfd14e53a86f9718642c0f63 | 2 |
| sublinker__sync_p_40371b3f77d14c5e8e078983855d4788 | 2 |
| sublinker__sync_p_4a332ba509cb48e4b30b167bd628d203 | 2 |
| sublinker__sync_p_779b7f60601c458288444eeabf9d9ccd | 2 |
| sublinker__sync_p_7b888470f17644389550482e660a046e | 2 |
| sublinker__sync_p_826d33ff99e64cd4b9e167c464398801 | 2 |
| sublinker__sync_p_9dd8a2a3a0d94763813a59d77c5d6c28 | 2 |
| sublinker__sync_p_b662e5d843af42b7a0d119df2fe45c7a | 2 |
| sublinker__sync_p_bde123cc509a45e69365e6d6d6c59185 | 2 |
| sublinker__sync_p_c07f00f387084c5dbf910d85602c23b1 | 2 |
| sublinker__sync_p_ceb2bcb24abe44a3b177e299567f757f | 2 |
| sublinker__sync_p_dc19f18a0e29486cb9f2b6a0dc5c0220 | 2 |
| sublinker__sync_p_eee2972f803a4e4cbd1ce2c91f05f889 | 2 |
| sublinker__sync_s_10085dcbc27e4197a4b98aca6f7f92c7 | 2 |
| sublinker__sync_s_26af39eb0d824725966117f5314d3492 | 2 |
| sublinker__sync_s_379cc02b6ce348999a4f24a43b183edb | 2 |
| sublinker__sync_s_430b0d31142f4c43b46c1e8fd49fc064 | 2 |
| sublinker__sync_s_50d39f74040b46409738ed7af744ebc1 | 2 |
| sublinker__sync_s_7a8ed5dfcf514e04a7c914cc676a37e7 | 2 |
| sublinker__sync_s_7dd5da7574f2452aa69bcca3e2e3e80d | 2 |
| sublinker__sync_s_80720364e4cd4920ab8dc6cf98b16b0e | 2 |
| sublinker__sync_s_834bd084d12f48eca6c6a76f1806b1b3 | 2 |
| sublinker__sync_s_a1df3ab4c66b4a3da9a57e8406d9962d | 2 |
| sublinker__sync_s_a2e07222dfaa46218b4cd38e7d982b95 | 2 |
| sublinker__sync_s_a91b07be10e74953a21f7fc7b68c4443 | 2 |
| sublinker__sync_s_dc03ab130cd04bbabdca7c5e474b21bd | 2 |
| sublinker__sync_s_e686b80711354d60b5942b7e8f2cc3fb | 2 |
| sublinker__sync_s_eb9ee91aefec49e8bd36af932f7c5f73 | 2 |
| sublinker__sync_s_ebeb413d77cb4b059c16527b2390834e | 2 |
| sublinker__variantsbase | 3 |
| sublinker__variantsconnection | 5 |
| sublinker__variantsselect | 2 |
| svsd_Parametr | 9 |
| sy_SyncCmdResponse | 5 |
| sy_SyncObjectChange | 10 |
| sy_SyncObjectChangeStatus | 8 |
| sy_SyncParams | 51 |
| sy_SyncReceived | 16 |
| tel__Ewid | 8 |
| tel__Obiekt | 6 |
| tmpKSeFAfter | 4 |
| tmpKSeFBefore | 4 |
| tr_Historia | 5 |
| tr_Opis | 4 |
| tr_Parametr | 14 |
| tr__Transakcja | 43 |
| trm_tw__Towar | 3 |
| tw_CechaTw | 3 |
| tw_Cena | 85 |
| tw_CenaHistoria | 88 |
| tw_Dokument | 10 |
| tw_JednMiary | 5 |
| tw_KodKreskowy | 4 |
| tw_KodyDlaSK | 3 |
| tw_Komplet | 4 |
| tw_KreatorPKWiU2015 | 1 |
| tw_KreatorPKWiU2015_RegulyStawekVat | 3 |
| tw_NarzutTw | 6 |
| tw_OpakowaniaAkcyzowe | 3 |
| tw_PKWiU2008_2015 | 2 |
| tw_PKWiU2015_CN2020 | 2 |
| tw_Parametr | 123 |
| tw_PowiazaniaKsef | 9 |
| tw_Stan | 6 |
| tw_StanOddzial | 5 |
| tw_TypKodu | 2 |
| tw_ZdjecieTw | 5 |
| tw_Zmiana | 15 |
| tw_ZmianaTw | 3 |
| tw__Towar | 101 |
| twsf_Licencja | 2 |
| twsf_LicencjaCzas | 2 |
| twsf_MailerWyslanePrzypomnienia | 2 |
| twsf_MultiSGT_TowaryDoSynchronizacji | 1 |
| twsf_MultiSync_Cenniki | 4 |
| twsf_MultiSync_Magazyny | 7 |
| twsf_MultiSync_MagazynyWirtualne | 3 |
| twsf_MultiSync_Magazyny_Kolejnosc | 3 |
| twsf_MultiSync_Podmioty | 12 |
| twsf_MultiSync_ZrodlaDlaWirtualnego | 2 |
| twsf_Pitbike_KorektaN | 3 |
| twsf_UniwersalneUstawienia | 3 |
| uf_Administracja | 9 |
| uf_Cena | 3 |
| uf_DzialGrupa | 4 |
| uf_Inne | 3 |
| uf_Instalator | 2 |
| uf_JednostkaMiary | 3 |
| uf_Konfiguracja | 17 |
| uf_KonfiguracjaProfil | 3 |
| uf_Operacja | 2 |
| uf_Plu | 5 |
| uf_Sterownik | 8 |
| uf_SynchTemp | 4 |
| uf_SynchroKodyKresk | 3 |
| uf_Synchronizacja | 64 |
| uf_Transmisja | 9 |
| uf_TransmisjaKontekst | 5 |
| uf_TransmisjaSzczegol | 6 |
| uf_Urzadzenie | 5 |
| uf_Vat | 3 |
| uf_Zadanie | 11 |
| uf_ZadanieTowar | 2 |
| ui_Filtr | 4 |
| ui_FiltrDeklaracje | 3 |
| ui_FiltrModul | 4 |
| ui_Funkcja | 14 |
| ui_Ikona | 6 |
| ui_Kolor | 5 |
| ui_Modul | 19 |
| ui_ModulPOS | 17 |
| ui_ModulPOSDok | 8 |
| ui_ModulPOSXaml | 8 |
| ui_ModulPOSZakladka | 9 |
| ui_ModulPowiazany | 7 |
| ui_ModulProfil | 2 |
| ui_Operacja | 12 |
| ui_OperacjaModul | 3 |
| ui_Podwidok | 5 |
| ui_SkrotModul | 8 |
| ui_Zasob | 3 |
| ui__Kompozycja | 7 |
| vat_DaneVAT | 8 |
| vat_KorektaProporcji | 9 |
| vat_MetodaRozl | 8 |
| vat_MetodaRozlUE | 5 |
| vat_OznaczeniaJPKSprzedaz | 31 |
| vat_OznaczeniaJPKZakup | 5 |
| vat_Parametr | 115 |
| vat_Pozycja | 6 |
| vat_ProporcjaBazowa | 11 |
| vat_Przypomnienia | 4 |
| vat_WizTowar | 10 |
| vat_WizTowar2013 | 10 |
| vat_Wizzard | 4 |
| vat_Wizzard2013 | 4 |
| vat_Wizzard2014 | 4 |
| vat__EwidVAT | 70 |
| vw__Konfiguracja | 5 |
| wind_Parametr | 5 |
| wiz_Wizard2017 | 6 |
| wy_DrukarkaDomyslna | 4 |
| wy_Grid | 5 |
| wy_Grupa | 8 |
| wy_KodSterujacy | 45 |
| wy_Naglowek | 15 |
| wy_NumerKSeF | 6 |
| wy_Plik | 7 |
| wy_PrzelewParam | 41 |
| wy_StatusWydruku | 5 |
| wy_TekstowyParam | 10 |
| wy_Typ | 3 |
| wy_WydrukParam | 9 |
| wy_WzDomyslny | 4 |
| wy_WzPowiazany | 22 |
| wy_Wzorzec | 17 |
| wy_WzorzecMagazyn | 3 |
| wy_WzorzecProfil | 2 |
| wy_WzorzecUzytkownik | 3 |
| xem_Ewid | 5 |
| xem_Szum | 2 |
| xin_Ewid | 5 |
| xin_Szum | 2 |
| xkh_Ewid | 5 |
| xkh_Szum | 2 |
| xpk_Ewid | 5 |
| xpk_Szum | 2 |
| xpr_Ewid | 5 |
| xpr_Szum | 2 |
| xtw_Ewid | 5 |
| xtw_Szum | 2 |
| xwl_Ewid | 5 |
| xwl_Szum | 2 |
| yfi_AssistantProductDescriptions | 7 |
| yfi_Binaries | 9 |
| yfi_Categories | 15 |
| yfi_CategoryTranslationRules | 11 |
| yfi_ChangeBuffer | 7 |
| yfi_Customers | 33 |
| yfi_Log | 8 |
| yfi_ObjectLinks | 12 |
| yfi_OrderAddresses | 33 |
| yfi_OrderItems | 56 |
| yfi_OrderStatusHistory | 15 |
| yfi_OrderStatusTranslations | 9 |
| yfi_Orders | 71 |
| yfi_ParameterItems | 11 |
| yfi_Parameters | 7 |
| yfi_ProductAttributes | 16 |
| yfi_ProductCategories | 10 |
| yfi_ProductImageLinks | 12 |
| yfi_ProductImages | 15 |
| yfi_ProductLinks | 16 |
| yfi_ProductPrices | 16 |
| yfi_ProductVariations | 38 |
| yfi_Products | 46 |
| yfi_SendBuffer | 10 |
| zd_AlarmyRezerwacja | 4 |
| zd_FaksParametr | 29 |
| zd_Historia | 5 |
| zd_ListParametr | 29 |
| zd_NotatkaParametr | 17 |
| zd_Opis | 4 |
| zd_RozmowaIntParametr | 31 |
| zd_Rozrachunek | 3 |
| zd_SpotkanieParametr | 31 |
| zd_TelefonParametr | 31 |
| zd_Uczestnik | 7 |
| zd_ZadanieParametr | 31 |
| zd__Zadanie | 54 |
| zlp_ParametryZlecen | 2 |
| zlp__Zlecenie | 14 |
| zpk_Parametr | 15 |
| zpk__Ksiega | 39 |
| zs_CechaZs | 3 |
| zs_Rezerwacja | 10 |
| zs_RezerwacjaUczestnik | 3 |
| zs_ZdjecieZs | 4 |
| zs__Zasob | 10 |
| zst_EwidProfil | 2 |
| zst_Temp | 2 |
| zst_WlasneXML | 10 |
| zst__Ewid | 14 |
| zw_Rozrachunek | 16 |
| zw__ZdarzenieWindykacyjne | 14 |
