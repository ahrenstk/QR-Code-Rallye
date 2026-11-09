# 📍 QR-Code-Rallye

QR-Code-Rallye ist eine leichtgewichtige, dateibasierte Schnitzeljagd- und Geocaching-Webanwendung auf Basis von PHP. Teilnehmer erkunden Stationen in der realen Welt, scannen QR-Codes oder  und sammeln so entdeckte Orte in Form von 4-stelligen digitalen Stempeln entweder auf Papeier oder in ihrem persönlichen Profil – ganz ohne schwerfällige Datenbank.

---

## 🎯 Entstehungsgeschichte

Das [Kath. Jugendbüro des Dekanats Emsland-Nord](https://www.kjb-emsland-nord.de) organisiert gemeinsam mit vielen ehrenamtlich Engagierten traditionell am dritten Fastensonntag den [MISEREOR Fastenmarsch](https://www.kjb-emsland-nord.de/veranstaltungen-1/misereor-fastenmarsch) zugunsten der [MISEREOR Fatenaktion](https://www.misereor.de/mitmachen/fastenaktion). Im Jahr 2021 wurde der Fastenmarsch aufgrund der Corona-Pandemie digital durchgeführt. So könnten die Teilnehmer während der Fastenzeit per QR-Code digital vierstellige Stempel sammeln. Schau gerne auf der [Projektseite Fastenmarsch 2021](https://boje-verbund.de/fastenmarsch2021) vorbei. 


## 🚀 Live-Demo

Du kannst dir das Live-Demo [QR-Code-Rallye](https://ahrens.tk/QR-Code-Rallye) anschauen. Stell dir vor du würdest die [Festung Bourtange](https://www.bourtange.de) besuchen und es dort vor Ort spielen. Auch kannst du dir wie oben beschrieben nachträglich den [Fastenmarsch 2021](https://boje-verbund.de/fastenmarsch2021) anschauen. Es wurden jedoch eine Funktionen zwischenzeitlich optimiert. Die Spielweise ist jedoch identisch geblieben. Die Berechnung der vierstelligen Zeitstempel hat sich jedoch verändert. So wird jetzt eine [Feistelchiffre](https://de.wikipedia.org/wiki/Feistelchiffre) und keine einfache [Caesar-Verschlüsselung](https://de.wikipedia.org/wiki/Caesar-Verschl%C3%BCsselung) verwendet.

---

## 🎯 Über das Projekt

Die **QR-Code-Rallye** ist konzipiert für Stadtfeste, Bildungspfade, Teamevents oder Geocaching-Aktionen. Das System nutzt **zeitbasierte, ortsbasierte rotierende 4-stellige Codes** im 10-Minuten-Takt. Diese Zeitstempel können dann online gespeichert oder auf Papier notiert werden, um den tatsächlichen Besuch der Station zu einem festen Zeitpunkt zu dokumentieren. Beim Fastenmarsch 2021 galt der auf Papier aufgeschriebene Zeitstempel als Nachweis für den Besuch der Station gegenüber den Sponsoren siehe [Beispiele](https://boje-verbund.de/fastenmarsch2021/Stationen).

---

## ✨ Hauptfunktionen

* **Dynamische Zeit-Codes (10-Minuten-Intervall):** Jeder Ort generiert alle 10 Minuten einen neuen, 4-stelligen Code, maximaler Aktionszeitraum ist 61 Tage
* **Persönliches Teilnehmerprofil:**
  * Login rein über frei wählbaren Benutzernamen (keine E-Mail- oder Passwort-Registrierung nötig).
  * Übersicht aller bereits besuchten Orte inklusive Zeitstempel.
  * Liste noch fehlender Stationen mit direkter Navigation (Google Maps, OpenStreetMap/Geo-URI).
  * durch Änderung im Code kann die Benutzerverwaltung leicht deaktiviert werden (beim Fastenmarsch 2021 war sie nicht verfügbar)
* **Flat-File-Architektur:** Speicherung erfolgt komplett dateibasiert (Textdateien) – keine MySQL- oder PostgreSQL-Datenbank erforderlich.
* **Web-Framework:** Verwendung des einfachen Web-Frameworks [W3.CSS](https://www.w3schools.com/w3css)
* **Admin-Tools:**
  * Übersicht aller Stationen und QR-Codes.
  * Simulation und Überprüfung von Codes für vergangene und zukünftige Zeitfenster.
  * Direkte Kartenvorschau für jede Station.

---

## 🛠️ Konfigurationen

Die Konfigurationen erfolgen in den Ordnern `orte`, `bilder`sowie `config`. Zur Verwendung der Testversion mit **Festung Bourtange** passe in `config/variablen.txt` das Zeitintervall entsprechend an. Nur im angegebenen Aktionszeitraum werden 4-stellige Stempel erzeugt.



---

## 🔒 Sicherheit & Datenschutz

* **Keine Tracking-Cookies:** Die Anwendung setzt keine Cookies zur Profilverfolgung ein.
* **Pseudonymisierte Profile:** Teilnehmer wählen einen Benutzernamen zur serverseitigen Speicherung ihrer Zeitstempel aus (optionnal)
* **Hinweis auf Drittanbieter-Webseiten** In der Konfiguration kann der Hinweis auf OpenStreetMap und Esri aktiviert / deaktiviert werden
  
---


## 📄 Lizenz & wichtige Ausnahme für deine Quizdaten

Dieses Projekt ist unter der **GNU Affero General Public License v3 (AGPL v3)** lizenziert – jedoch mit einer speziellen Ausnahme für deine kreativen Inhalte.

### ⚠️ Was bedeutet das für dich und deine Quizdaten?

Um dir, deiner Schule oder deinem Unternehmen absolute Sicherheit und Freiheit beim Erstellen von Touren zu garantieren, haben wir den **Software-Code** strikt von deinen **kreativen Inhalten** getrennt:

1. **Das Software-Framework (AGPL v3):** 

   Der eigentliche Code von QR-Code-Rallye (PHP, HTML, JavaScript, CSS) ist Open-Source. Wenn du oder deine Entwickler das Kern-Framework verändern, verbessern oder erweitern, müssen diese Code-Änderungen unter der AGPL v3 ebenfalls Open-Source bleiben. Da QR-Code-Rallye eine Webanwendung ist, gilt dies ausdrücklich auch dann, wenn du die modifizierte Version auf einem Server hostest und Nutzern über ein Netzwerk (z. B. das Internet) bereitstellst – der modifizierte Quellcode muss in diesem Fall den Nutzern der Anwendung zugänglich gemacht werden.

2. **Deine Tour-Daten & Medien (von der AGPL v3 AUSGESCHLOSSEN):** 

   Alle deine individuellen Tour-Inhalte, Mediendateien, ... sind **ausdrücklich von der AGPL v3 ausgeschlossen**. Das betrifft im Speziellen:

   * Deine Konfigurationen unter `config`
   * Alle hochgeladenen Fotos, QR-Codes, ...
   * Alle geschriebenen Texte, ..

   **Diese Daten bleiben zu 100 % dein exklusives geistiges Eigentum.** Du bist NICHT verpflichtet, deine QR-Codes oder Bilder offenzulegen, zu teilen oder unter eine freie Lizenz zu stellen. Du kannst deine fertigen Touren völlig frei für kommerzielle Events nutzen, sie privat halten oder verkaufen.


---

## 📄 Lizenz

Dieses Projekt ist unter der **GNU Affero General Public License v3.0 (AGPL-3.0)** lizenziert. Weitere Details findest du in der Datei `LICENSE`.

---


---
