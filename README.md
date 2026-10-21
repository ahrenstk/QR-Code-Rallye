# 📍 QR-Code-Rallye

Eine leichtgewichtige, dateibasierte Schnitzeljagd- und Geocaching-Webanwendung auf Basis von PHP. Teilnehmer erkunden Stationen in der realen Welt, scannen QR-Codes oder geben zeitbasierte Codes ein und sammeln so entdeckte Orte in ihrem persönlichen Profil – ganz ohne schwerfällige Datenbank.

---

## 🎯 Über das Projekt

Die **QR-Code-Rallye** ist konzipiert für Stadtfeste, Bildungspfade, Teamevents oder Geocaching-Aktionen. 

Das Besondere: Um Manipulationen (z. B. das einfache Weiterleiten oder Notieren von Codes) zu verhindern, nutzt das System **zeitbasierte, rotierende 4-stellige Codes** im 10-Minuten-Takt, die über eine modulare mathematische Prüfsumme abgesichert sind.

---

## ✨ Hauptfunktionen

* **Dynamische Zeit-Codes (10-Minuten-Intervall):** Jeder Ort generiert alle 10 Minuten einen neuen, 4-stelligen Code.
* **Täglicher Shuffle-Algorithmus:** Durch ein tägliches Salt-Verfahren (`dynamic_shuffle`) ändern sich die Codemuster täglich.
* **Anti-Verwechslungs-Alphabet:** Die Code-Generierung schließt leicht verwechselbare Zeichen aus (z. B. `0` vs. `O`, `1` vs. `I`, `B` vs. `8`, `S` vs. `5`).
* **Manipulationssichere 7-Zeichen-Tokens:** Über QR-Codes wird ein 7-stelliger Prüfcode übergeben, der Hexadezimalwerte mit einer Modulo-Prüfziffer kombiniert.
* **Persönliches Teilnehmerprofil:**
  * Login rein über frei wählbaren Benutzernamen (keine E-Mail- oder Passwort-Registrierung nötig).
  * Übersicht aller bereits besuchten Orte inklusive Zeitstempel.
  * Liste noch fehlender Stationen mit direkter Navigation (Google Maps, OpenStreetMap/Geo-URI).
* **Flat-File-Architektur:** Speicherung erfolgt komplett dateibasiert (Textdateien) – keine MySQL- oder PostgreSQL-Datenbank erforderlich.
* **XSS- & Script-Schutz:** Automatische Bereinigung von HTML-Inhalten aus den Stationsdateien.
* **Admin-Tools:**
  * Übersicht aller Stationen und QR-Codes.
  * Simulation und Überprüfung von Codes für vergangene und zukünftige Zeitfenster.
  * Direkte Kartenvorschau für jede Station.

---

## ⚙️ Wie es funktioniert

### 1. Codegenerierung & Entschlüsselung
Ein Zustand einer Station setzt sich zusammen aus:
$$\text{Station-ID (0–89)} \quad|\quad \text{Aktionstag} \quad|\quad \text{Stunde (0–23)} \quad|\quad \text{10-Minuten-Block (0–5)}$$

Daraus wird eine eindeutige Ganzzahl berechnet:
$$\text{Code-Basis} = 9000 \times \text{Station} + 1500 \times \text{10-Min-Block} + \text{Tag} \times 24 + \text{Stunde}$$

* Dieser Wert wird im **30er-Zahlensystem (Base30)** kodiert.
* Anschließend erfolgt eine Permutation der Zeichen (`[0, 3, 2, 1]`) und ein dynamischer Versatz anhand des aktuellen Datums und eines geheimen Schlüssels (`secret`).
* Das Ergebnis ist ein 4-stelliger Buchstabencode (z. B. `A7XK`), der vor Ort angezeigt oder eingegeben wird.

### 2. QR-Code-Validierung (`seven`-Code)
Wird ein QR-Code gescannt, enthält die URL einen 7-stelligen Token:
* 5 Hex-Zeichen für den Zustand.
* 2 Prüfziffern, basierend auf einer Modulo-Operation (`$sevenmodulo`).
* Erst bei erfolgreicher Modulo-Validierung wird die Station als besucht gewertet und im Profil hinterlegt.

---

## 📁 Projektstruktur

```text
qr-code-rallye/
├── index.php                      # Hauptanwendungslogik & Controller
├── config/
│   ├── variablen.txt              # Globale Einstellungen (Zeitraum, Secrets, Passwörter)
│   ├── startseite-header1.txt     # HTML-Header Teil 1
│   ├── startseite-header2.txt     # HTML-Header Teil 2
│   ├── startseite-header3.txt     # Navigation & Menübar
│   ├── startseite-inhalt1.txt     # Content-Bereich Startseite (oben)
│   ├── startseite-inhalt2.txt     # Content-Bereich Startseite (unten)
│   ├── startseite-admin.txt       # Admin-Bereich Ergänzungen
│   ├── benutzername.txt           # Profil-Hinweise
│   └── startseite-footer.txt      # HTML-Footer
├── locations/                     # (Alternativ: orte/)
│   ├── locations.txt              # CSV-Liste aller Stationen (ID;Name;Lat;Lon;Beschreibung)
│   ├── 00.txt                     # HTML-Inhalt für Startstation 00
│   └── 01.txt ... 89.txt          # HTML-Inhalte der einzelnen Stationen
├── profile/                       # Dynamisch generierte Benutzerprofile (Flat-File)
│   └── [xx]/[md5-hash].txt
└── bilder/                        # Icons, Pins und Kartengrafiken
```

---

## 🚀 Installation & Voraussetzungen

### Voraussetzungen
* Webserver (Apache, Nginx oder Caddy)
* PHP 8.0 oder höher
* Aktivierte PHP-Erweiterungen: `mbstring`, `date`

### Setup in 3 Schritten

1. **Repository klonen:**
   ```bash
   git clone https://github.com/DEIN-BENUTZERNAME/QR-Code-Rallye.git
   cd QR-Code-Rallye
   ```

2. **Dateirechte setzen:**
   Der Webserver benötigt Schreibrechte für den `profile/`-Ordner:
   ```bash
   chmod -R 775 profile/
   # Bzw. sicherstellen, dass www-data / http Zugriff hat
   ```

3. **Konfiguration anpassen:**
   Passe die Datei `config/variablen.txt` an (siehe nächster Abschnitt).

---

## 🛠️ Konfiguration (`config/variablen.txt`)

Die Datei enthält Schlüssel-Wert-Paare:

| Parameter | Beschreibung | Standardbeispiel |
| :--- | :--- | :--- |
| `titelkurz` | Kurztitel in der mobilen Menüleiste | `Rallye 2024` |
| `titellang` | Voller Titel für Desktop-Bildschirme | `Große Stadt-Rallye 2024` |
| `secret` | Geheimer Salt für Hashes & Shuffles | `MeinSuperGeheimesSecret!` |
| `adminpasswort` | Passwort für das Admin-Interface | `geheimes_admin_passwort` |
| `beginnaktion` | Startdatum der Rallye (`TT.MM.JJJJ`) | `01.09.2024` |
| `endeaktion` | Enddatum der Rallye (`TT.MM.JJJJ`) | `31.10.2024` |
| `stationoffset` | Offset für Stationsberechnungen | `42` |
| `sevenmodulo` | Prüfziffern-Modulo für 7er-Codes | `100` |
| `aktionsende` | Schaltet die Aktion manuell ab (`true`/`false`) | `false` |

---

## 📍 Stationen anlegen (`locations/locations.txt`)

Jede Zeile repräsentiert eine Station und ist mit Semikolons (`;`) getrennt:

```text
00;Startpunkt Rathaus;51.5074;-0.1278;Hier startet das Abenteuer.
01;Historischer Marktplatz;51.5080;-0.1285;Suche nach dem QR-Code am Brunnen.
02;Stadtpark Ost;51.5095;-0.1290;Schau auf die Rückseite der Infotafel.
```

* **Feld 1:** Stationsnummer (`00` bis `89`)
* **Feld 2:** Name der Station
* **Feld 3:** Breitengrad (Latitude)
* **Feld 4:** Längengrad (Longitude)
* **Feld 5:** Beschreibung / Zusatztext

Für jede Station kann zusätzlich eine optionale Detaildatei `locations/{ID}.txt` angelegt werden, deren Inhalt den Teilnehmern nach dem Finden angezeigt wird.

---

## 🔒 Sicherheit & Datenschutz

* **Keine Tracking-Cookies:** Die Anwendung setzt keine Cookies zur Profilverfolgung ein.
* **Pseudonyme Profile:** Teilnehmer wählen einen Benutzernamen, aus dem ein Hash (`md5($username . ';' . $secret)`) erzeugt wird. Die Klarnamen werden nicht in Klartext-Dateien gesammelt.
* **HTML-Sanitization:** Stationsbeschreibungen werden über `sanitize_html_content()` von JavaScript und unerwünschten Event-Handlern gereinigt.

---

## 📄 Lizenz

Dieses Projekt ist unter der **MIT-Lizenz** lizenziert. Weitere Details siehe [LICENSE](LICENSE).
