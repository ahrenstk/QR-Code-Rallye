# 📍 QR-Code-Rallye

Eine leichtgewichtige, dateibasierte Schnitzeljagd- und Geocaching-Webanwendung auf Basis von PHP. Teilnehmer erkunden Stationen in der realen Welt, scannen QR-Codes oder geben zeitbasierte Codes ein und sammeln so entdeckte Orte in ihrem persönlichen Profil – ganz ohne schwerfällige Datenbank.

---

## 🎯 Entstehungsgeschichte

Das [Kath. Jugendbüro des Dekanats Emsland-Nord](https://www.kjb-emsland-nord.de) organisiert gemeinsam mit vielen ehrenamtlich Engagierten traditionell am dritten Fastensonntag den [Misereor Fastenmarsch](https://www.kjb-emsland-nord.de/veranstaltungen-1/misereor-fastenmarsch) zugunsten der [MISEREOR Fatenaktion](https://www.misereor.de/mitmachen/fastenaktion). Im Jahr 2021 wurde der Fastenmarsch aufgrund der Corona-Pandemie digital durchgeführt. So könnten die Teilnehmer während der Fastenzeit per QR-Code digital vierstellige Stempel sammeln. Schau gerne auf der [Projektseite Fastenmarsch 2021](https://boje-verbund.de/fastenmarsch2021) vorbei. 


## 🎯 Über das Projekt

Die **QR-Code-Rallye** ist konzipiert für Stadtfeste, Bildungspfade, Teamevents oder Geocaching-Aktionen. Das System nutzt **zeitbasierte, ortsbasierte rotierende 4-stellige Codes** im 10-Minuten-Takt, die über eine modulare mathematische Prüfsumme abgesichert sind. Diese Zeitstempel können dann online gespeichert oder auf Papier notiert werden, um den tatsächlichen Besuch der Station zu einem festen Zeitpunkt zu beweisen.

---

## ✨ Hauptfunktionen

* **Dynamische Zeit-Codes (10-Minuten-Intervall):** Jeder Ort generiert alle 10 Minuten einen neuen, 4-stelligen Code.
* **Persönliches Teilnehmerprofil:**
  * Login rein über frei wählbaren Benutzernamen (keine E-Mail- oder Passwort-Registrierung nötig).
  * Übersicht aller bereits besuchten Orte inklusive Zeitstempel.
  * Liste noch fehlender Stationen mit direkter Navigation (Google Maps, OpenStreetMap/Geo-URI).
  * durch Änderung im Code kann diese Funktion leicht deaktiviert werden (beim Fastenmarsch 2021 war es nicht verfügbar)
* **Flat-File-Architektur:** Speicherung erfolgt komplett dateibasiert (Textdateien) – keine MySQL- oder PostgreSQL-Datenbank erforderlich.
* **Web-Framework:** Verwendung des einfachen Web-Frameworks [W3.CSS](https://www.w3schools.com/w3css)
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

## 🛠️ Konfigurationen

Die Konfigurationen erfolgen in den Ordnern `orte`, `bilder`sowie `config`



---

## 🔒 Sicherheit & Datenschutz

* **Keine Tracking-Cookies:** Die Anwendung setzt keine Cookies zur Profilverfolgung ein.
* **Pseudonyme Profile:** Teilnehmer wählen einen Benutzernamen zur serverseitigen Speicherung ihrer Zeitstempel aus (optionnal)
* 
---

## 📄 Lizenz

This project is licensed under the **MIT License**. You can find the licensing details in the `LICENSE` file.

---
