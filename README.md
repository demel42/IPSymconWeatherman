# IPSymconWeatherman

[![IPS-Version](https://img.shields.io/badge/Symcon_Version-5.0+-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
![Module-Version](https://img.shields.io/badge/Modul_Version-1.1-blue.svg)
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

 - IP-Symcon ab Version 5<br>
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
| Windgeschwindigkeit in km/h statt m/s | boolean  | false        |              |
|                                       |          |              |              |

#### Variablenprofile

Es werden folgende Variablenprofile angelegt:
* Boolean<br>
Weatherman.RainDetector, Weatherman.SunDetector

* Integer<br>
Weatherman.Azimut, Weatherman.Elevation, Weatherman.hour, Weatherman.min, Weatherman.sec, Weatherman.Wifi, Weatherman.WindAngle, Weatherman.WindStrength

* Float<br>
Weatherman.absHumidity, Weatherman.Dewpoint, Weatherman.Humidity, Weatherman.Lux, Weatherman.Pressure, Weatherman.Rainfall, Weatherman.RainStrength,
Weatherman.Temperatur, Weatherman.UV-Index, Weatherman.Windchill, Weatherman.WindSpeed

* String<br>
Weatherman.WindDirection

## 6. Anhang

GUIDs
- Modul: `{8517502F-9707-2979-4A91-32D07CDD563D}`
- Instanzen:
  - Weatherman: `{8AB8B668-6300-0B27-DC40-E88F67805157}`

## 7. Versions-Historie

- 1.1 @ 17.10.2019 17:31<br>
  - für die Niederschlag-Stufe "Nieselregen" kann optional auch der Regensensor-Wert herangezogen werden
  - Anpassungen an IPS 5.2
    - IPS_SetVariableProfileValues(), IPS_SetVariableProfileDigits() nur bei INTEGER, FLOAT
	- Dokumentation-URL in module.json
  - Umstellung auf strict_types=1
  - Umstellung von StyleCI auf php-cs-fixer

- 1.0 @ 16.09.2019 17:34<br>
  Initiale Version
