# IPSymconWeatherman

[![IPS-Version](https://img.shields.io/badge/Symcon_Version-6.0+-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
![Code](https://img.shields.io/badge/Code-PHP-blue.svg)
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg)](https://creativecommons.org/licenses/by-nc-sa/4.0/)

## Dokumentation

**Inhaltsverzeichnis**

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Installation](#3-installation)
4. [Funktionsreferenz](#4-funktionsreferenz)
5. [Konfiguration](#5-konfiguration)
6. [Anhang](#6-anhang)
7. [Versions-Historie](#7-versions-historie)

## 1. Funktionsumfang

Übernahme aller Wetterdaten von der "do it yourself" Wetterstation _Weatherman_ von ([stall.biz](https://www.stall.biz/project/weatherman-die-perfekte-wetterstation-fuer-die-hausautomation)).

Getestet mit der Weatherman-Version **123**.

## 2. Voraussetzungen

 - IP-Symcon ab Version 6.0
 - eine Weatherman-Wetterstation

## 3. Installation

### a. Laden des Moduls

Die Webconsole von IP-Symcon mit _http://\<IP-Symcon IP\>:3777/console/_ öffnen.

Anschließend oben rechts auf das Symbol für den Modulstore (IP-Symcon > 5.1) klicken

![Store](docs/de/img/store_icon.png?raw=true "open store")

Im Suchfeld nun _Weatherman_ eingeben, das Modul auswählen und auf _Installieren_ drücken.

#### Alternatives Installieren über Modules Instanz (IP-Symcon < 5.1)

Die Webconsole von IP-Symcon mit _http://\<IP-Symcon IP\>:3777/console/_ aufrufen.

Anschließend den Objektbaum _öffnen_.

![Objektbaum](docs/de/img/objektbaum.png?raw=true "Objektbaum")

Die Instanz _Modules_ unterhalb von Kerninstanzen im Objektbaum von IP-Symcon mit einem Doppelklick öffnen und das  _Plus_ Zeichen drücken.

![Modules](docs/de/img/Modules.png?raw=true "Modules")

![Plus](docs/de/img/plus.png?raw=true "Plus")

![ModulURL](docs/de/img/add_module.png?raw=true "Add Module")

Im Feld die folgende URL eintragen und mit _OK_ bestätigen:

```
https://github.com/demel42/IPSymconWeatherman.git
```

Anschließend erscheint ein Eintrag für das Modul in der Liste der Instanz _Modules_.

### b. Einrichtung des Geräte-Moduls

In IP-Symcon nun unterhalb des Wurzelverzeichnisses die Funktion _Instanz hinzufügen_ (_CTRL+1_) auswählen, als Hersteller _stall.biz_ und als Gerät _Weatherman_ auswählen.
Es wird automatisch eine I/O-Instanz vom Type Server-Socket angelegt und das Konfigurationsformular dieser Instanz geöffnet.

Hier die Portnummer eintragen, an die der Weatherman Daten schicken soll und die Instanz aktiv schalten.

In dem Konfigurationsformular der Weatherman-Instanz kann man konfigurieren, welche Variablen übernommen werden sollen.

### c. Anpassung des Weatherman

Der Weatherman muss in zwei Punkten angepaast werden

- Einrichten der IP von IP-Symcon
```
http://<ip des Weatherman>/?ccu:<ip von IPS>:
```
- aktivieren der automatischen Übertragung
```
http://<ip des Weatherman>/?param:12:<port von IPS>:
```

damit schickt Weatherman minütlich die Daten sowie bei bestimmten Zuständen (Regen erkannt) eine ausserplanmässige Nachricht.

## 4. Funktionsreferenz

## 5. Konfiguration

#### Properties

| Eigenschaft                           | Typ      | Standardwert | Beschreibung |
| :------------------------------------ | :------  | :----------- | :----------- |
| Windgeschwindigkeit in km/h statt m/s | boolean  | false        | |
|                                       |          |              | |
| Höhe der Wetterstation über NN        | integer  | 0            | |
|                                       |          |              | |
| Hitzeindex                            | boolean  | false        | Hitzeindex berechnen |
| absoluter Luftdruck                   | boolean  | false        | lokalen Luftdruck berechnen  |
| Windstärke als Text                   | boolean  | false        | Windstärke als Text ausgeben |
| Niederschlag-Stufe                    | boolean  | false        | Niederschlag als text ausgeben |
|                                       |          |              | |
| Regensensor-Wert                      | integer  | 0            | Regensensor-Wert verwenden um Nieselregen zu erkennen |
|                                       |          |              | |

#### Variablenprofile

Es werden folgende Variablenprofile angelegt:

* Boolean<br>
Weatherman.RainDetector,
Weatherman.SunDetector

* Integer<br>
Weatherman.Azimut,
Weatherman.Elevation,
Weatherman.hour,
Weatherman.min,
Weatherman.PrecipitationLevel,
Weatherman.sec,
Weatherman.Wifi,
Weatherman.WindAngle,
Weatherman.WindStrength

* Float<br>
Weatherman.absHumidity,
Weatherman.Dewpoint,
Weatherman.Heatindex,
Weatherman.Humidity,
Weatherman.Lux,
Weatherman.Precipitation,
Weatherman.Pressure,
Weatherman.Rainfall,
Weatherman.RainStrength,
Weatherman.Temperatur,
Weatherman.UV-Index,
Weatherman.Windchill,
Weatherman.WindSpeed

* String<br>
Weatherman.WindDirection

## 6. Anhang

GUIDs
- Modul: `{8517502F-9707-2979-4A91-32D07CDD563D}`
- Instanzen:
  - Weatherman: `{8AB8B668-6300-0B27-DC40-E88F67805157}`

## 7. Versions-Historie

- 1.17 @ 02.01.2025 14:28
  - interne Änderung
  - update submodule CommonStubs

- 1.16 @ 06.02.2024 09:46
  - Verbesserung: Angleichung interner Bibliotheken anlässlich IPS 7
  - update submodule CommonStubs

- 1.15 @ 03.11.2023 11:06
  - Neu: Ermittlung von Speicherbedarf und Laufzeit (aktuell und für 31 Tage) und Anzeige im Panel "Information"
  - update submodule CommonStubs

- 1.14 @ 15.08.2023 10:36
  - Fix: Wertebereich diverser Variablenprofile angepasst
    - Weatherman.Temperatur: -10..30 -> -25..45 °C
    - Weatherman.Humidity: n/a -> 0..100 %
    - Weatherman.absHumidity: n/a -> 0..80 g/m³
    - Weatherman.Dewpoint: 0..30 -> -10..40 °C
    - Weatherman.Heatindex: 0..100 -> 0..60 °C
    - Weatherman.Windchill: 0..100 -> -30..45 °C
    - Weatherman.WindSpeed: 0..100 -> 0..150 km/h
  - update submodule CommonStubs

- 1.13 @ 04.07.2023 14:44
  - Vorbereitung auf IPS 7 / PHP 8.2
  - update submodule CommonStubs
    - Absicherung bei Zugriff auf Objekte und Inhalte

- 1.12.1 @ 07.10.2022 13:59
  - update submodule CommonStubs
    Fix: Update-Prüfung wieder funktionsfähig

- 1.12 @ 05.07.2022 16:57
  - Verbesserung: IPS-Status wird nur noch gesetzt, wenn er sich ändert

- 1.11.1 @ 22.06.2022 10:33
  - Fix: Angabe der Kompatibilität auf 6.2 korrigiert

- 1.11 @ 29.05.2022 14:55
  - update submodule CommonStubs
    Fix: Ausgabe des nächsten Timer-Zeitpunkts
  - einige Funktionen (GetFormElements, GetFormActions) waren fehlerhafterweise "protected" und nicht "private"
  - interne Funktionen sind nun entweder private oder nur noch via IPS_RequestAction() erreichbar

- 1.10.3 @ 17.05.2022 15:38
  - update submodule CommonStubs
    Fix: Absicherung gegen fehlende Objekte

- 1.10.2 @ 17.05.2022 10:31
  - fehlende Übersetzung ergänzt

- 1.10.1 @ 10.05.2022 15:06
  - update submodule CommonStubs

- 1.10 @ 06.05.2022 10:09
  - IPS-Version ist nun minimal 6.0
  - Anzeige der Modul/Bibliotheks-Informationen, Referenzen und Timer
  - Implememtierung einer Update-Logik
  - Überlagerung von Translate und Aufteilung von locale.json in 3 translation.json (Modul, libs und CommonStubs)
  - diverse interne Änderungen

- 1.9 @ 18.12.2020 14:57
  - PHP_CS_FIXER_IGNORE_ENV=1 in github/workflows/style.yml eingefügt

- 1.8 @ 12.09.2020 11:40
  - LICENSE.md hinzugefügt
  - lokale Funktionen aus common.php in locale.php verlagert
  - Traits des Moduls haben nun Postfix "Lib"
  - define's durch statische Klassen-Variablen ersetzt

- 1.7 @ 06.07.2020 09:18
  - Bugfix für "Weatherman-Edition"
    Windgeschwindigkeiten werden nun per "km/h" und nicht mehr als "m/s" übertragen; die übertragenen Einheit wird nun aus den Daten gelesen

- 1.6 @ 06.03.2020 18:41
  - Bugfix für "Weatherman-Edition"
    - Variable "w_regenstunden_heute" wird, je nach Version, als "w_rtest" oder "w_regen_stunden_heute" übertragen; wird gleich behandelt
    - Variable "w_wind_1min" hies fehlerhafterweise "w_wind_1m", "w_wind_10min" fehlte

- 1.5 @ 01.02.2020 09:07
  - Unterstützung des "Weatherman-Edition"

- 1.4 @ 30.12.2019 10:56
  - Anpassungen an IPS 5.3
    - Formular-Elemente: 'label' in 'caption' geändert
  - Fix in ReceiveData()

- 1.3 @ 15.12.2019 11:53
  - Debug erweitert
  - Variablenprofil 'Weatherman.Heatindex' fehlte
  - ReceiveData() umgebaut

- 1.2 @ 27.10.2019 06:19
  - Luftdruck-Trend ist jetzt ein normaler Text (Unterstriche durch Leerzeichen ersetzt)

- 1.1 @ 17.10.2019 17:31
  - für die Niederschlag-Stufe "Nieselregen" kann optional auch der Regensensor-Wert herangezogen werden
  - Anpassungen an IPS 5.2
    - IPS_SetVariableProfileValues(), IPS_SetVariableProfileDigits() nur bei INTEGER, FLOAT
	- Dokumentation-URL in module.json
  - Umstellung auf strict_types=1
  - Umstellung von StyleCI auf php-cs-fixer

- 1.0 @ 16.09.2019 17:34
  - Initiale Version
