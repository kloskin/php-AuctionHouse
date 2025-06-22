# Aukcje24: System aukcyjny z MongoDB i Redis

## Spis treści

* [Opis projektu](#opis-projektu)
* [Wymagania](#wymagania)
* [Instalacja](#instalacja)
* [Struktura katalogów](#struktura-katalogów)
* [Konfiguracja](#konfiguracja)
* [Sposób działania](#sposób-działania)
* [API i routing](#api-i-routing)
* [Panel administracyjny](#panel-administracyjny)
* [Technologie](#technologie)
* [Autorzy](#autorzy)

---

## Opis projektu

Aukcje24 to lekki, skalowalny system aukcyjny napisany w czystym PHP, wykorzystujący:

* **MongoDB** do trwałego przechowywania użytkowników, aukcji i ofert,
* **Redis** do operacji w czasie rzeczywistym: rankingów ofert, liczników wyświetleń, cache’owania danych.

Użytkownicy mogą tworzyć i zarządzać aukcjami, składać oferty oraz śledzić historię. Administratorzy oraz moderatorzy mają wydzielone panele do zarządzania.

---

## Wymagania

* Docker & Docker Compose
* PHP 8.1+ z rozszerzeniem MongoDB oraz Redis
* Node.js (opcjonalnie, do assetów)

---

## Instalacja

1. Sklonuj repozytorium:

   ```bash
   git clone https://github.com/kloskin/php-AuctionHouse
   cd auctionhouse-php
   ```

2. Uruchom Docker Compose:

   ```bash
   docker-compose up -d --build
   ```

3. Zainicjalizuj bazy:

   ```bash
   docker exec -it auctionhouse-php-php-1 php /var/www/html/db/mongo-init.php
   docker exec -it auctionhouse-php-php-1 php /var/www/html/db/redis-init.php
   ```

4. Wejdź na `http://localhost:8000`.

---

## Struktura katalogów

```
/auctionhouse-php/
├── docker/               # Konfiguracja Docker
│   ├── Dockerfile
│   └── php.ini
├── db/                   # Skrypty inicjalizacyjne baz
│   ├── mongo-init.php
│   └── redis-init.php
├── src/                  # Logika aplikacji
│   ├── db.php
│   ├── auth.php
│   ├── auctions.php
│   ├── bids.php
│   └── utils.php
├── public/               # Publiczny folder WWW
│   ├── index.php         # Front controller + routing
│   ├── assets/           # Statyczne pliki (JS, CSS, obrazy)
│   └── ...               # reszta endpointów: login.php, auction.php, itp.
├── templates/            # Widoki wspólne
│   ├── header.php
│   └── footer.php
│
├── docker-compose.yml
└── README.md
```

## Sposób działania

1. **Routing**: `public/index.php` jako front-controller analizuje URI i wczytuje odpowiedni kontroler + szablon.
2. **Model**: `src/*` zawiera funkcje dostępu do MongoDB i Redis.
3. **Widoki**: `templates/` dla nagłówka/stopki, pliki w `public/` renderują strony.
4. **Cache**: szczegóły aukcji i listy są buforowane w Redis z TTL, ranking ofert w SortedSet.

---

## API i routing

Przykładowe endpointy:

| URI               | Metoda   | Opis                              |
| ----------------- | -------- | --------------------------------- |
| `/` lub `/home`   | GET      | Strona główna                     |
| `/auctions`       | GET      | Lista wszystkich aukcji           |
| `/auction/{id}`   | GET      | Detale aukcji                     |
| `/auction/{id}`   | POST     | Składanie oferty                  |
| `/login`          | GET/POST | Logowanie                         |
| `/register`       | GET/POST | Rejestracja                       |
| `/logout`         | GET      | Wylogowanie                       |
| `/create_auction` | GET/POST | Dodawanie aukcji (auth: user+)    |
| `/admin/users`    | GET/POST | Zarządzanie użytkownikami (admin) |
| `/admin/auctions` | GET/POST | Zarządzanie aukcjami (admin/mod)  |

---

## Panel administracyjny

Dostępny dla `admin` i `moderator`, pozwala na:

* Przegląd i modyfikację użytkowników (role, usuwanie).
* Przegląd i zmianę statusów aukcji.

---

## Technologie

* **PHP 8.1**
* **MongoDB** (PHP Driver)
* **Redis** (phpredis)
* **Bootstrap 5**
* **Docker / Docker Compose**

---

