Musimy przebudować całkowicie IMPORT Panel w PPM https://ppm.mpptrade.pl/admin/products/import oraz napisać całkowicie nowy PLAN @ETAP\_06\_Import\_Export na podstawie poniższego Opisu!
IMPORT TO PPM and EXPORT TO PRESTASHOP WORKFLOW

\- Aplikacja PPM Musi umożliwiać masowy import produktów na wiele sposobów

-- import z ERP (do zrobienia po implementacji @ETAP\_08\_ERP\_Integracje)

-- Import z CSV/excel na podstawie predefiniowanych kolumn, PPM generuje wzorce kolumny na podstawie wyboru typu importu przez uzytkownika

-- Bezpośrednio w PPM <- Kluczowa funkcja, musi być doskonale zaprojektowana, umożliwiająca importowanie masowej ilości produktów przez różne działy/osoby

* Oto podstawowe funkcje/możliwości jakie powinien oferować PPM podczas importu:

\- Panel Importu, dedykowany panel importu oferujące różne możliwości importu, posiadający listę produktów "niekompletnych" które nie mogą się jeszcze znaleźć w ProductList z racji braku kompletu podstawowych danych.

* PODSTAWOWE dane produktu bez których jego pojawienie się w ProductList nie będzie możliwe są następujące:

\- SKU <- KRYTYCZNE KONIECZNE bez tego produkt nie istnieje

\- Nazwa

\- Kategoria - Kategorie na liście Muszą być dodawane w następujący sposób: Kategorie BAZA (L1) -> Wszystko (L2) są niewidoczne dla uzytkownika w tym panelu ale automatycznie oznaczanie przy kliknięciu "GOTOWE" w momencie dodawnia produktu do ProductList, uzytkownik w Panelu importu dodaje kategorie wybierając je z dropdown odpowiedającemu zagnieżdzeniu zaczynając od poziomu czyli:



/Kategoria L3🔽 / Kategoria L4🔽❌ / Kategoria L5🔽❌ / Kategoria L6 i 7🔽❌ -> Pojawia się wyłącznie jeżeli kategoria L5 ma przypisane podkategorie L6>7. Użytkownik ma możliwość zakończenia na kategorii L3 lub L4 klikając ❌ przy kolejnych poziomach kategorii. Aplikacja powinna inteligentnie sugerować kategorie każdego poziomu z pośród dostępnych w PPM na podstawie nazwy produktu. Użytkownik powinien mieć możliwość oprócz wyboru kategorii z danego poziomu z listy dropdown też odfiltrowanie kategorii z danego poziomu po jej nazwie (searchbar w dropdown)

* Czy produkt wariantowy? Jeżeli TAK -> przycisk "Dodaj warianty" <- otwiera się modal tworzenia wariantów podobny do tego z ProductForm (bez zdjęć na tym etapie)

\- Typ Produktu (Część zamienna, Pojazd, Akcesoria, Odzież, Inne) <- dropdown z dostępnych opcji. ODKRYCIE, nie mamy zdefiniowanego miejsca konfiguracji typów produktów (dodawanie, usuwanie, zmiana)

\- Cechy techniczne dla produktów typu "Pojazd" <- modal gdzie można wczytać szablon zdefiniowany w https://ppm.mpptrade.pl/admin/features/vehicles wczytać z innego pojazdu (wyszukaj pojazd po SKU,Nazwa) lub dodać indywidualnie

\- Dopasowania dla produktów typu "Część zamienna". Przycisk "utwórz dopasowania" per produkt oraz masowo dla wszystkich zaznaczonych checkboxem. Produkty zaznaczone checkboxem po kliknięciu w "utwórz dopasowania" otwiera albo modal który jest kopią https://ppm.mpptrade.pl/admin/compatibility z listą zaznaczonych części, albo otwiera /admin/compatibility w nowej karcie z odfiltrowanymi produktami z checkboxów panelu importu, gdzie zapisanie zmian automatycznie zapisuje je i pokazuje w panelu importu, nie wiem jak wydajnościowo czy tez funkcjonalnie będzie korzystniej, musisz się głęboko zastanowić jak wdrożyć interface dopasowań do interface importu. Możliwa publikacja bez dopasowania po kliknięciu "Brak dopasowań"

* Zdjęcia (przynajmniej jedno) <- Modal z polem drag and drop zdjęć, opcją wczytaj z innego produktu (wyszukiwarka SKU,Nazwa) Opcja wybrania zdjęcia głównego, Jeżeli Produkt oznaczony jako wariantowy to po uploadowaniu zdjęć możliwe wybranie zdjęć dla wariantów. możliwa publikacja bez zdjęcia po potwierdzeniu, zatwierdzenie publikacji bez zdjęcia zapisane w logach przez jakiego użytkownika. Na liście w Panelu importu pojawia się zdjęcie główne plus znacznik "+X" dla dodatkowych zdjęć jak to jest zrobione w Warianty tab w ProductForm

\- Na jaki sklep prestashop ma iść? Mini kafelki do zaznaczenia (nie checkboxy) automatycznej publikacji na wybrane prestashop po spełnieniu powyższych wymagań.

\- Przycisk "Publikuj" w kolumnie "GOTOWE" pojawiający się jako aktywny wyłącznie po spełnieniu powyższych wymaganych punktów, przycisk dodaje produkt do listy ProductList gdzie następnie jest tworzony automatycznie JOB eksportu na prestashop na podstawie danych dziedziczonych z "Dane domyślne", uwzględniając walidacja/filtrowanie per shop dopasowań.

* Panel powinien umożliwiać "wklejenie" listy SKU (jedna kolumna) oraz SKU + Nazwa (dwie kolumny), powinien też inteligentnie rozpoznawać znaki nowego wiersza, znaki oddzielające jak "średnik" czy "przecinek" jeżeli użytkownik wklei jako jeden wiersz i podzielić te produktu na wiersze w podglądzie.
* Następnie lista jest wyświetlana w panelu importu produktów "oczekujących" na uzupełnienie danych podstawowych, lista powinna składać się z kolumn:
  ✅ | Zdjęcie | SKU | Nazwa | TYP PRODUKTU | KATEGORIE | MASTER/WARIANT | CECHY/DOPASOWANIA | SKLEP | GOTOWE
* Panel informuje wizualnie uzytkownika które dane w kolumnach produktu należy uzupełnić przed publikacją
* Panel powinien posiadać przycisk "zaznacz/odznacz" wszystkie, oraz powinien umożliwiać użytkownikowi indywidualne oznaczanie checkbox-ów w celu akcji masowych takich jak: dopasowania (opisane wyżej), wybór sklepów, publikacja, kategoria L3/L4/L5/L6/L7, dopisz prefix/suffix do Nazwy/SKU, wybierz Typ produktu
* Publikacja powinna być zintegrowana z obecnymi systemami eksportu/aktualizacji prestashop, oraz systemami walidacji produktów. Powinno to działać tak: 

1. uzupełniony importowany produkt
2. użytkownik wybiera sklepy do publikacji
3. użytkownik klika "publikuj"
4. PPM tworzy produkty w swojej bazie i umieszcza je na ProductList
5. PPM automatycznie przypisuje produkty do sklepów określonych w panelu importu
6. PPM uruchamia istniejące mechanizmy filtracji dopasowań
7. PPM przydziela odpowiednie dopasowania do odpowiednich sklepów prestashop
8. PPM Tworzy JOB-y eksportu produktów na wybrane sklepy prestashop (oddzielne na każdy sklep)
9. Jeżeli SUKCES to Produkty znikają z listy produktów do importu i przechodzą do "Historii Importu"
