# Tworzymy aplikację, narzędzie do zarządzania produktami na wielu sklepach Prestashop jednocześnie o nazwie Prestashop Product Manager. Aplikacja musi być najwyższej jakości klasy enterprise. Nie możemy sobie pozwolić na błędy i niedociągnięcia w kodzie. Nie możemy sobie pozwolić na upraszczanie i stosowanie skrótów aby rozwiązać przeszkodę czy problem. Musisz zaprojektować i zaplanować aplikację na podstawie globalnych Best Practice podobnych Aplikacji typu PIM jak i Best Practice pisania kodu.

- ## Budujemy Aplikację z wykorzystaniem:

- Backend: PHP 8.3 + Laravel 12.x

- UI: Blade + Livewire 3.x + Alpine.js (Vite tylko do buildów lokalnie)

- DB: MySQL SQL

- Cache/kolejki: Redis (jeśli włączysz w DirectAdmin) albo driver database

- Import XLSX: Laravel-Excel (PhpSpreadsheet pod spodem)

- Autoryzacja (na końcu projektu): Laravel Socialite (Google Workspace + Microsoft Entra ID)


* Musisz opierać się na oficjalnej dokumentacji oraz Best Practice dotyczących prestashop 8 i 9 z oficjalnej dokumentacji https://devdocs.prestashop-project.org/8/ https://devdocs.prestashop-project.org/9/ oraz z innych źródeł internetu

* Musisz opierać się na oficjalnej dokumentacji oraz Best Practice dotyczących prestashop 8 i 9 z oficjalnej dokumentacji https://www.insert.com.pl/dla_uzytkownikow/e-pomoc_techniczna.html?query=&program=2&category= oraz z innych źródeł internetu

* Musisz opierać się na oficjalnej dokumentacji oraz Best Practice dotyczących prestashop 8 i 9 z oficjalnej dokumentacji https://api.baselinker.com/ oraz z innych źródeł internetu

* Musisz opierać się na oficjalnej dokumentacji oraz Best Practice dotyczących prestashop 8 i 9 z oficjalnej dokumentacji https://learn.microsoft.com/en-us/dynamics365/business-central/ oraz z innych źródeł internetu

* Aplikacja musi działać online w przeglądarce.

* Aplikacja musi mieć uwierzytelnianie OAuth Google Workspace na własną domenę oraz Microsoft, uwierzytelnianie dodajemy w ostatnich etapach projektu aby nie zakłócać pracy nad zmieniającym się projektem *(do zrobienia jako jeden z ostatnich etapów aplikacji podstawowej)*

* Admin wysyła zaproszenia oraz ustawia role dla użytkowników. Do aplikacji nie można się zarejestrować, dostęp do niej mają wyłącznie adresy email wpisane przez admina na whiteliste. Przy pierwszym uruchomieniu aplikacji Admin zakłada profil podając dane logowania (email, hasło), firmę, dane firmy

* Musisz wybrać dokładnie przeanalizować możliwości serwera @dane_hosting.md i dobrać najbardziej odpowiedni framework pod ten Projekt.

* ## Plik Źródłowy, wprowadzenie produktu do systemu

  * Przykładowa struktura pliku źródłowego w formacie XLXS z informacjami produktów z dostawy PLIK, poniżej objaśnienie nazw kolumn. **KRYTYCZNE** **Aplikacja powinna mieć możliwość mapowania kolumn xlsx z polami i  parametrami aplikacji:**

  * **Numer kontenera frachtowego / id_kontener** - tekst z oznaczeniem kontenera zazwyczaj wpisany w nazwie pliku źródłowego, lub wpisane ręcznie przez Menadżera, może być wpisane w późniejszym terminie niż ORDER, jeden kontener może zawierać kilka ORDER-ów. Kontener musi pokazywać jakie ordery i jakie produkty z tych orderów się w nim znajdują
    * Każdy kontener oprócz listy orders i produktów z tych orders powinien mieć możliwość przyjmowania i przechowywania dokumentów odprawy, w dowolnym momencie do wgrania i odczytania wyłącznie przez Menadżera i Admina (pliki: .zip, .xlsx, .pdf, .xml)


  ### Poniższe pozycje znajdują się w pliku źródłowym jako kolumny, które menadżer może zmapować w aplikacji:

  * **ORDER** - unikalny numer zamówienia, tekst, klucz, zawiera listę produktów w zamówieniu

    * **Model** - w przypadku części zamiennych, wskazuje że jest to oryginalna część do tego modelu pojazdu

    * **No.** - można zignorować

    * **Parts Name** - Nazwa produktu, części zamiennej

    * **U8 Code** - Symbol dostawcy

    * **Qty** - ilość produktów zamówiona

    * **Ctn no.** - numer / oznaczenie kartonu (dla magazynu)

    * **Size** - objętość w metrach sześciennych

    * **Gross Weight (KGS)** - Waga Brutto

    * **Net Weight (KGS)** - Waga Netto

    * **MRF CODE** - SKU (symbol) w naszej bazie (nazwa kolumny może sie różnić w zależności od dostawcy)

    * **MATERIAL** - Materiał dominujący

    * **REMARKS** - Można zignorować

    * **Type of vehicle** - Rodzaj pojazdu

    * **VIN no.** - numer VIN (tylko dla pojazdów)

    * **Engine No.** - numer silnika (tylko dla pojazdów i silników)

    * **Year of  Manufacturing** - Rok produkcji (tylko dla pojazdów i silników)

      ------

      Poniższe kolumny są dodawane ręcznie na potrzeby importu do obecnej struktury (jeszcze bez aplikacji), miej na uwadze, że część z nich się powtarza z powyższymi i powtórzenia nie będą potrzebne w aplikacji którą budujemy.

    * **Czy nowy produkt** - Tak / Nie - oznaczenie dla magazynu czy produkt istnieje w naszej bazie

    * **Symbol (SKU)** -  SKU (symbol) w naszej bazie 

    * **Symbol od dostawców** - Symbol dostawcy

    * **nazwa** - Nazwa produktu

    * **Real Qty** - Ilość rzeczywista która przyszła, uzupełnia magazyn po weryfikacji przyjęcia dostawy

    * **Uwagi** - pole tekstowe dla magazynu na wpisywanie uwag dotyczących produktów.

    * **Cena zakup netto za sztukę (kurs)** - kwota, kurs, taki jaki był w dniu zakupu

    * **Cena zakup brutto (kurs)** - kwota, kurs, taki jaki był w dniu zakupu

    * **Zakup brutto + 60% Marża** - kwota zwiększona o wartość marży (do ustawienia przez użytkownika)

    * **Cena detaliczna brutto** - Cena dla grupy cenowej Detaliczna dla klientów, kwota

    * **Marża od detalicznej** - wartość procentowa

    * **Cena Standard** - Cena dla grupy cenowej Dealer Standard, kwota

    * **Marża standard** - wartość procentowa

    * **Cena premium** - Cena dla grupy cenowej Dealer Premium, kwota

    * **Marża od premium** - wartość procentowa

    * **cena warsztat** - Cena dla grupy cenowej Warsztat, kwota

    * **Warsztat Premium** - Cena dla grupy cenowej Warsztat Premium, kwota

    * **Pracownik** - Cena dla grupy cenowej Pracownik

    * **Szkółka-Komis-Drop** - Cena dla grupy cenowej Szkółka-Komis-Drop

    * **Cena HuHa** - Cena dla grupy cenowej HuHa

  * Wprowadzając produkty z pliku źródłowego, należy podać datę dostawy dla tego id kontenera

    * Wszystkie produkty z tego pliku będą miały wpisane id kontenera oraz datę dostawy

  * Użytkownik przed impotem powinien mieć możliwość wybrania typu importowanego pliku, POJAZDY / CZĘŚCI a aplikacja powinna wczytać zapisane wcześniej przez użytkownika mapowanie kolumn zgodnie z wybranym szablonem. Jeżeli kolumny się nie zmapują poprawnie, aplikacja powinna poprosić użytkownika o zmapowanie lub zignorowanie kolumn. Użytkownik może edytować predefiniowane szablony mapowań oraz może dodawać własne

* ## Aplikacja musi mieć następujące poziomy użytkowników:

  * **Admin** - dostęp do wszystkich funkcji niższych poziomów a także możliwość dodawania, usuwania, edycji kont użytkowników, edycji ustawień aplikacji, dodawania, usuwania, edycji nowych sklepów prestashop, łączenia z bazami danych ERP takich jak Subiekt GT, czy Microsoft Dynamics
  * **Menadżer** - dostęp do funkcji niższych poziomów oraz może dodawać, usuwać, edytować produkty, kategorie, zdjęcia, opisy, warianty, cechy w bazie aplikacji, a także je eksportować na sklepy prestashop, może wgrywać masowo dane do bazy z pliku CSV, lub z ERP, baza może pozwolić na pominięcie importu niektórych wartości jak zdjęcia, opis itp. Ale musi wymagać podczas importu wymagane wartości jak Indeks (SKU) produktu, Nazwa czy Kategoria, oraz funkcje użytkownika
  * **Redaktor** - ma te same uprawnienia co użytkownik oraz może dodawać, edytować zdjęcia, nie może ich usuwać, może wyłączyć zdjęcia w produkcie. Może edytować opisy, nazwy, kategorie, warianty, cechy, dopasowania produktów i eksportować produkty na sklepy prestashop i do pliku CSV. Nie może dodawać i usuwać produktów, nie może eksportować do ERP.
  * **Magazynier** - ma te same uprawnienia co poniższe poziomy, ale bez rezerwacji towarów z kontenera. Ma dostęp do panelu dostaw. Może edytować niektóre elementy dostaw / kontenerów ( do dodania na etapie wdrażania systemu dostaw)
  * **Handlowiec** - ma te same uprawnienia co użytkownik oraz ma dostęp do panelu zamówień kontenerów  ( z ograniczeniami takimi jak brak widoczności widoku ceny zakupu i marży) oraz możliwość rezerwacji towarów z kontenera, czyli zanim pojawią się na stanach na sklepach i ERP. (do dodania na etapie tworzenia systemu dostaw)
  * **Reklamacje** - Do dodania na etapie tworzenia systemu reklamacji. Uprawnienia te same co użytkownik + dostęp do panelu reklamacje
  * **Użytkownik** - może odczytywać dane produktów, pojedynczo lub masowo, ma dostęp również do wyszukiwarki. Aplikacja nie pokazuje produktów dopóki nie zostanie wysłane zapytanie szukania przez użytkownika, typu wyszukaj po nazwie, indeksie, kategorii, na sklepie itp. Zanim zostanie wysłane zapytanie wyszukiwania produktów aplikacja pokazuje Statystyki w formie podsumowania z widocznym komunikatem Wyszukaj towar, aby zobaczyć szczegóły.
  * **Wyszukiwarka** powinna być intuicyjna, powinna pokazywać propozycje, przybliżone produkty, np. jak użytkownik wpisze "filtr do" to powinna podpowiadać dalszą treść jak w wyszukiwarce gogle. Wyszukiwarka powinna też zezwalać na błędy, literówki czy znaki puste jak spacja i inteligentnie wyszukiwać produkt np.. gdy użytkownik wpisze SKU "xxx-. Ale powinna mieć też filtry typu "Wyszukaj dokładnie" gdzie wyszukiwana wartość musi zgadać się w 100% z wartością w bazie

* ## Kluczowe dane Produktów

  * **SKU (index)** - unikalna wartość, klucz do zależności w tabelach bazy danych (autokorekta pustych znaków spacji przed i po nazwie)
  * **Nazwa** - Pole tekstowe (autokorekta pustych znaków spacji przed i po nazwie)
  * **Kategoria** - w aplikacji pokazane w formie pól z listą rozwijaną z własnym polem szukaj (patrz obrazek katalog Referencje) Każdy poziom zagnieżdżenia kategorii ma własne pole dropdown Kategoria⬇️ / Kategoria1⬇️ / Kategoria2⬇️ / Kategoria3⬇️ / Kategoria4⬇️ (autokorekta pustych znaków spacji przed i po nazwie)
    * Możliwość wyboru kategorii w widoku drzewa kategorii
    * Oznaczenie kategorii domyślnej dla prestashop (id_category_default w tabeli ps_product) jeżeli nie będzie podana w pliku podczas importu aplikacja domyślnie oznacza najgłębszą wybraną kategorię jako "domyślną"
  * **Opis skrócony** (max 800 znaków) - Rozbudowany edytor HTML WYSIWYG („what you see is what you get”)
  * **Opis długi** (max 21844 znaków)  - Rozbudowany edytor HTML WYSIWYG („what you see is what you get”)
  * **Cena** z podziałem na grupy cenowe:
    * Detaliczna
    * Dealer Standard
    * Dealer Premium
    * Warsztat
    * Warsztat Premium
    * Szkółka-Komis-Drop
    * Pracownik
    * Wszystkie grupy cenowe (oprócz domyślnej detalicznej) są dodawane jako "specific_price" w Prestashop
    * Aplikacja powinna umożliwić adminowi mapowanie grup cenowych miedzy aplikacja a prestashop i ERP
  
  * **Stany magazynowe** z podziałem na magazyny:
    * MPPTRADE
    * Pitbike.pl
    * Cameraman
    * Otopit
    * INFMS
    * Reklamacje
    * i inne które admin może dodać, usuwać i edytować wedle uznania. Użytkownik określa z którego magazynu mają być synchronizowane stany na sklepie Prestashop. **WAŻNE** w ERP może być wiele magazynów, w prestashop tylko jeden. Aplikacja powinna umożliwić adminowi mapowanie magazynów między Aplikacją a ERP oraz umożliwić wybór magazynu dla wybranego sklepu Prestashop.
  
  
    * **Data dostawy** / **Data dostępności** (aplikacja powinna umożliwić adminowi wprowadzenie przesunięcia dat dostawy które ma być przesyłane na sklepy prestashop, np, plik źródłowy pokazuje datę dostawy 30-08-2025, admin ustawia przesunięcie +10 dni, więc na prestashop będzie wysyłana data 09-09-2025)
  
  
    * **Status dostawy:** Zamówione, Nie zamówione, Anulowany, W kontenerze (nr kontenera), Opóźnienie (nowe pole data dostawy oprócz oryginalnej z licznikiem dni opóźnienia na podstawie danych Kontenera (data dostawy kontenera, nr kontenera)), W trakcie przyjęcia (ustawia magazyn jednym kliknięciem dla wszystkich towarów z id kontenera)
  
  
    * **Status synchronizacji** -  monitorowanie rozbieżności między danymi w bazie aplikacji a danymi na sklepach prestashop / ERP
  
  
    * Ustawienie ostrzeżenia o **stanie minimalnym** (z możliwością wysłania email z powiadomieniem, adresy email wpisuje admin w panelu admina)
  
  
    * **Lokalizacja Towaru na magazynie**: Pole tekstowe do wpisania wielu wartości oddzielonych ";"  (autokorekta pustych znaków spacji przed i po nazwie)
  
  
    * **Symbol Dostawcy** - pole tekstowe podobne do SKU tyle, że od dostawcy (w przeciwieństwie do SKU to pole nie jest unikalne)
  
  
    * **Typ Produktu:** Pojazd, Część Zamienna, Odzież, Inne
  
  
    * **Producent**
  
  
    * **Waga**
  
  
    * **Wysokość**
  
  
    * **Szerokość**
  
  
    * **Długość**
  
  
    * **EAN**
  
        * **Warianty**
          * SKU wariantu
          * Grupy cenowe wariantu, domyślnie dziedziczone z produktu matki
          * Stany magazynowe wariantu (wiele magazynów)
          * Lokalizacja magazynowa wariantu
          * Zdjęcia wariantu (max 5, jpg, jpeg, png, webp)
          * EAN wariantu
          * Status dostawy wariantu
          * Label na którym sklepie dodany
  
  
  
    * **Zdjęcia** (max 20, jpg, jpeg, png, webp)
  
        * Dostępni przewoźnicy - pozycja dostępna wyłącznie w oknie modalnym z danymi ze sklepu Prestashop. Pokazuje aktualnie wybranych przewoźników dla danego produktu w danym sklepie prestashop. Zgodnie z zasadami prestashop nie wybranie żadnego przewoźnika = dostępni wszyscy.
  
          * Aplikacja powinna umożliwić masową edycję przewoźników dla wybranych przez użytkownika produktów dla wybranego sklepu Prestashop.
  
  
  
    * **Cechy**
  
      **Dla pojazdów:**
      * Cecha1: Wartość1
      * Cecha2: Wartość2
      * Cecha3: Wartość3
  
      **Dla Części zamiennych:**
  
      - Model: X
  
      - Model: Y
  
      - Model: Z
  
      - Model: A
  
      - Oryginał: X
  
      - Oryginał: Y
  
      - Oryginał: Z
  
      - Zamiennik: A
  
  
    * **Stawka opodatkowania:** Domyślnie 23% z możliwością zmiany
  
  
    * **Label** informujący na którym sklepie i ERP jest dodany produkt
  


* Aplikacja powinna mieć możliwość mapowania Magazynów w bazie aplikacji z magazynami w ERP, (np. w Subiekt GT jest to Symbol magazynu). 

* Aplikacja powinna umożliwić dodanie kilku magazynów z ERP do jednego magazynu w Aplikacji i umożliwić adminowi wybór czy sumować dane z magazynów czy nie, jeżeli nie to wybrać magazyn źródłowy

* Aplikacja musi weryfikować poprawność produktów przed eksportem na prestashop

* Aplikacja powinna mieć czytelny i przejrzysty interface zgodny ze współczesnymi trendami, a także oferować tryb ciemny i jasny oraz posiadać elementy interaktywne z nowoczesnymi animacjami.

* Jako punkt wyjścia do poprawnego dodawania produktów na sklep prestashop możesz się sugerować inną aplikacją "D:\\OneDrive - MPP TRADE\\Skrypty\\Presta\_Sync" do synchronizacji danych między dwoma prestami, tam dodawanie produktów ze starej na nową prestę odbywało się w sposób prawidłowy.

* Aplikacja musi podczas eksportu zdjęć tworzyć strukturę katalogów do zdjęć zgodnie z zasadami prestashop

* Aplikacja musi działać szybko i operować na dużej liczbie danych

* Aplikacja musi Tworzyć własną lokalną bazę zdjęć gotową do eksportu na prestashop, zgodnie z zasadami dodawania zdjęć na prestashop do produktów

* Aplikacja musi mieć możliwość wybrania sklepu prestashop przez użytkownika na który ma być eksportowany produkt

* Aplikacja musi weryfikować dane między aplikacją, a docelowym sklepem prestashop i przekazywać w sposób czytelny i wizualny użytkownikowi różnice i rozbieżności danych czy też zdjęć

* Aplikacja musi wiedzieć na jaki sklep jaki produkt został wysłany, edytowany, czy też usunięty i pokazywać to wizualnie użytkownikowi

* Każdy sklep prestashop może mieć inne produkty, ale też mogą być takie które występują na kilku sklepach, aplikacja musi mieć możliwość tworzenia oddzielnych tytułów, opisów i zdjęć produktów na różne sklepy prestashop i przechowywać te informacje, które potem może wykorzystać przy werfikacji pobrania danych ze sklepu w celu znalezienia rozbiżności

* Aplikacja musi mieć możliwość wpisywania opisów produktów w html zgodnym z prestashop, oraz musi dawać użytkownikowi zstaw narzędzi do formatowania tekstu

* Każdy sklep prestashop może mieć inne kategorie, aplikacja musi w sposób czytelny pozwalać użytkownikowi wybierać kategorie dostępne na sklepie prestashop, a także podawać sugestię kategorii na podstawie innych podobnych produktów

* Aplikacja powinna oferować możliwość upload wielu zdjęć jednocześnie

* Aplikacja powinna mieć możliwość eksportu wielu produktów jednocześnie

* Aplikacja powinna mieć możliwość oznaczenia na jakie sklepy jakie produkty mają być eksportowane i wyeksportować je jednocześnie na różne sklepy zgodnie z oznaczeniem

* Aplikacja będzie działać pod adresem ppm.mpptrade.pl ma być hubem produktów w organizacji, z niej będą wysyłane produkty a różne sklepy Prestashop

* Aplikacja będzie działać na hostingu współdzielonym hostido https://seohost.pl/hosting

* Aplikacja będzie oparta na bazie danych MySQL mySQL

* Tworzenie aplikacji będzie się odbywać lokalnie, jednak testy zmian mają być widoczne od razu na serwerze, każda zmiana, czy też nowa funkcja musi być skompilowana i wysłana na serwer w celu przeprowadzenia testów.

* w pliku "dane\_hostingu.md" masz wszystkie potrzebne dane do samodzielnej pracy nad aplikacją

* w folderze @/References znajdują się zdjęcia docelowego layoutu aplikacji

* Wszystkie dane produktowe w aplikacji należy uznać jako dane źródłowe, każda rozbieżność danych między aplikacją a prestashop musi być jasno komunikowana dla użytkownika

* Każdy produkt ma swoje unikalne SKU które jest głównym indeksem pozwalającym zarządzać produktami, na każdej preście produkt może mieć inne ID (prestowe) ale zawsze będzie mieć ten sam SKU

* Każda prestashop może mieć inny opis, kategorię, cechy i nazwę produktu. Ceny, stock i warianty są zawsze takie same niezależnie od sklepu. Panel Produktu w aplikacji musi umożliwiać użytkowikowi dostosowanie produktu pod określony sklep prestashop

* **Integracja ERP** Aplikacja musi mieć możliwość integracji z różnymi systemami ERP takimi jak Subiekt GT czy Baselinker, czy Microsoft Dynamics. Musi mieć możliwość synchronizacji produktów, importu produktów i eksportu produktów na ERP wraz ze wszystkimi danymi które dany ERP obsługuje.

* Aplikacja musi mieć system dopasowań części do modeli pojazdów, ten system będzie przekazywać to do prestashop w formie cech dla produktów z kategorii określonej przez admina.

* ## System dopasowań części do pojazdów:

  * Przykładowy System dopasowań części wygląda następująco:
  * (Cecha) Model: (Wartość) X,Y,Z - Model pojazdu wskazuje warość z Oryginał i Zamiennik
  * (Cecha) Oryginał: (Wartość) X,Y,Z - może zawierać wiele pojazdów, ale każdy unikalny
  * (Cecha) Zamiennik: (Wartość) X,Y,Z - może zawierać wiele pojazdów, ale każdy unikalny
  * Na preście musi to być zapisane w następujący sposób, przykład:
    * Model: X
    * Model: Y
    * Model: Z
    * Model: A
    * Oryginał: X
    * Oryginał: Y
    * Oryginał: Z
    * Zamiennik: A

  * System musi poprawnie eksportować dopasowania do Prestashop zgodnie z zasadami i strukturą bazy danych prestashop

* **KRYTYCZNE** musisz stosować się do zasad bazy danych prestashop, korzystając ze wszystkich zależności i id między kolumnami i tabelami presty cech. Prze każdym tworzeniem czy edycją kodu związanym z eksportem produktów do prestashop musisz koniecznie zweryfikować rzeczywistą strukturę bazy danych w Prestashop https://github.com/PrestaShop/PrestaShop/blob/8.3.x/install-dev/data/db_structure.sql oraz https://github.com/PrestaShop/PrestaShop/blob/9.0.x/install-dev/data/db_structure.sql

* Aplikacja musi inteligentnie odfiltrowywać Cechy dopasowań pojazdów w zależności od tego na jaki sklep prestashop ma być eksportowana. Np. Admin powinien mieć możliwość zdefiniowania Wszystkich modeli globalnie, manualnie lub przez import csv, a następnie "zbanować" (domyślnie w karcie produktu w aplikacji wpisuje się globalne dane na każdy sklep, chyba, że zostały zdefiniowane dane do wybrany sklep) wybrane modele na wybrany sklep prestashop, dzięki czemu podczas eksportu produktu na ten sklep Cechy (dopasowania) będą się automatycznie dostosowywać podczas eksportu a dany sklep prestashop.

* ## System Dostaw (plan dalszy do wdrożenia po opublikowaniu aplikacji w wersji produkcyjnej)

  Musisz zaprojektować i zaplanować profesjonalny system dostaw jakości enterprise na podstawie globalnych best practice.

  - **Docelowy schemat procesu zakupowego** w organizacji MPP TRADE masz opisany dokładnie w tym pliku [ Schematy_procesów_zakupowych_MPP_Trade_(stan_docelowy).pdf](References\Schematy_procesów_zakupowych_MPP_Trade_(stan_docelowy).pdf) . Musisz opracować cały proces wewnątrz aplikacji na podstawie załączonego schematu z pliku PDF. **KRYTYCZNE** nie możesz pominąć żadnego Etapu, ani diagramu (schematu blokowego) w procesie planowania i wdrożenia sekcji systemu dostaw. Jeżeli plik PDF jest za duży do odczytania to podziel go sobie na części i odczytuj kawałkami, tworząc spójny plan.
  - Menadżer składa zamówienie do dostawcy, od którego otrzymuje plik XLSX z potwierdzeniem zamówieniowych towarów, ich ilości i terminem dostawy
  - Następnie menadżer importuje ten plik do bazy jako plik źródłowy. 
  - Każde zamówienie wraz z id kontenera jest widoczne w dedykowanym panelu "Dostawy"
  - Aplikacja w tym momencie tworzy zamówienie w Subiekt GT (lub Microsoft Dynamics). Zamówienie otrzymuje numer w ERP np. ZD 5/08/2025 który następnie jest pobierany przez aplikację do wcześniej zaimportowanego pliku.
  - Gdy dostawca potwierdzi wysyłkę Menadżer otrzymuje numer kontenera i wpisuje go do zamówienia

  * Musisz zaprojektować prostą, mało wymagającą aplikację Android dla magazynu pozwalającą skanować / wpisywać towar z dostawy podczas przyjęcia, tak aby magazyn potwierdzał faktyczne ilości które przyszły w porównaniu z tym co jest na dokumencie dostawy.
    * aplikacja magazynowa powinna mieć możliwość wybrania id kontenera w którym będzie się znajdować lista produktów wprowadzona w pliku źródłowym XLSX
    * aplikacja powinna mieć możliwość skanowania kodów kreskowych i QR dla optymalizacji pracy
    * aplikacja powinna umożliwiać ręczne wpisanie kodu w przypadku gdy skaner nie będzie mógł go odczytać
    * aplikacja powinna umożliwiać zarządzanie ilością towaru podczas przyjęcia dostawy w następujący sposób:
      * Zgodne / Niezgodne - jeżeli zgodne aplikacja ustawia rzeczywistą ilość produktu na tą z zamówienia, jeżeli niezgodne, Ręczne wprowadzenie
      * Ręczne wprowadzanie - kontrolki +/- oraz pole wpisania liczby (tylko w przypadku gdy niezgodne)
  * użytkownik magazynu powinien mieć możliwość dodania zdjęcia i uwag do każdej pozycji z zamówienia / kontenera. Uwagi te powinny być zapisane w zamówieniu / id kontenera
  * Menadżer może "zamknąć" dostawę / kontener klikając przycisk "Zamykam". Kliknięcie tego przycisku powinno wywołać zapytanie czy użytkownik na pewno chce zamknąć dostawę. Po potwierdzeniu zamówienie jest realizowane w Subiekt GT (lub Microsoft Dynamics) jako "zrealizuj bez dokumentu"

* ## System Reklamacji Dostaw (do zaplanowania po systemie dostaw)

- **UWAGA WAŻNE** Działamy hybrydowo, lokalnie tworzymy i edytujemy pliki, po czym wysyłamy je na serwer aby zweryfikować zmiany na publicznym adresie www ppm.mpptrade.pl

- ****

  Budowa Aplikacji w wersji podstawowej, funkcje wersji podstawowej:

  - Admin Panel
    - Admin Dashboard![Dashboard_admin](D:\OneDrive - MPP TRADE\Skrypty\PPM2\References\Dashboard_admin.png)
    - User Management
    - Shop Management
      - Dashboard![Prestashop Config](D:\OneDrive - MPP TRADE\Skrypty\PPM2\References\Prestashop Config.png)
      - Konfiguracja połączenia - dodaj, usuń, edytuj
      - Status synchronizacji, manualne wymuszenie synchronizacji, ustawienia częstotliwości synchronizacji
      - Import / Eksport Produktów - pobieranie wysyłanie wybranych/wszystkich produktów lub kategorii
      - Ustawienia Integracji
    - ERP Integration
      - Dashboard![ERP_Dashboard](D:\OneDrive - MPP TRADE\Skrypty\PPM2\References\ERP_Dashboard.png)
      - Konfiguracja połączenia - dodaj, usuń, edytuj![ERP_Config](D:\OneDrive - MPP TRADE\Skrypty\PPM2\References\ERP_Config.png)
      - Status synchronizacji, manualne wymuszenie synchronizacji, ustawienia częstotliwości synchronizacji
      - Import / Eksport Produktów - pobieranie wysyłanie wybranych/wszystkich produktów lub kategorii
      - Ustawienia Integracji![ERP_Settings](D:\OneDrive - MPP TRADE\Skrypty\PPM2\References\ERP_Settings.png)
    - System Settings![System_settings](D:\OneDrive - MPP TRADE\Skrypty\PPM2\References\System_settings.png)
    - Logs & Monitoring
      - Error Logs
      - Activity Logs
    - Maintenance
      - Backup Management - integracja z Google Drive / Sharepoint / NAS Synology![Admin_maintenance](D:\OneDrive - MPP TRADE\Skrypty\PPM2\References\Admin_maintenance.png)
      - Security Check![Admin_Security_check](D:\OneDrive - MPP TRADE\Skrypty\PPM2\References\Admin_Security_check.png)
      - Maintenance Tasks![Admin_maintenance_tasks](D:\OneDrive - MPP TRADE\Skrypty\PPM2\References\Admin_maintenance_tasks.png)

  

- **Przykładowa kolejność budowy aplikacji:**

  - Zbudowanie backend gotowy pod wszystkie funkcje frontendu
  - Zbudowanie frontend Dashboard oraz Produkty
  - Zbudowanie Panelu admina posiadającym kontrolę nad całą aplikacją
  - Integracja z ERP Baselinker (pozostałe w dalszym planie)
    - Import produktów do aplikacji PPM z ERP wraz ze zdjęciami i opisami i pozostałymi parametrami
    - Synchronizacja danych takich jak ceny i stany magazynowe
    - Import wariantów
    - Import i synchronizacja "Lokalizacji magazynowej"
  - zbudowanie połączenia z API Prestashop opartym o prawdziwy klucz API
  - zbudowanie pobrania danych produktów z prestashop
  - zbudowanie działającego front end z prawdziwymi danymi z prestashop: dashboard oraz panel produktów
  - pozostałe funkcje wersji podstawowej
  - System Dostaw
  - System Reklamacji dostaw
