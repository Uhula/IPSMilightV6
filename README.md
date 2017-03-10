### IP Symcon Milight V6 Modul

**Inhaltsverzeichnis**

0. [Vorbemerkungen](#1-vorbemerkungen)
1. [Funktionsumfang](#1-funktionsumfang)
2. [Systemanforderungen](#2-systemanforderungen)
3. [Installation](#3-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [WebFront](#6-webfront)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)
8. [Changelog](#8-changelog)
9. [Sonstiges](#9-sonstiges)


### 0. Vorbemerkungen
Dieses IP Symcon PHP Modul dient dem Einbinden von Milight Gateways/Controllern
um die dort angemeldeten Milight-Lampen steuern zu können. Das Modul unterstützt
nur die Milight Controller mit der Treiberversion 6, die am Markt unter verschiedenen Namen
vertrieben werden, aber meistens am Zusatz "WIFI iBox" oder "WIFI iBox2" erkannt
werden können.

Die Controller gibt es in zwei Bauformen, einmal als reine WLAN-Box "WIFI iBox" und
einmal inklusive eingebauter Lampe als "iBox2". Soll diese Lampe selbst gesteuert
werden, ist "BRIDGE" in der Konfiguration zu wählen.

An diesen Controllern können sowohl die aktuellen RGBWW Milight-Lampen, als auch
die älteren RGBW Lampen angelernt werden. Die neuen RBGWW Lampen haben den Vorteil,
dass sie sowohl warm- als auch kaltweißes Licht haben und eine Einstellung der
Lichttemperatur zwischen 2700 und 6500 Kelvin erlauben. Weiterhin kann bei ihnen,
im Gegensatz zu den älteren RGBW Lampen, auch die Farbsättigung (das Einmischen von weiß)
eingestellt werden. Damit sind Pastellfarben darstellbar.

Sowohl für RGBWW als auch für RGBW sind je 4 Zonen (Gruppen) von Lampen anlernbar.
Je Zone ist die Anzahl der Lampen nicht begrenzt.

Ältere Milight Gateways/Controller, welche eine Treiberversion 2 bis 5 verwenden,
sind nicht ansprechbar, da sich das Protokoll deutlich verändert hat.

* [Beispiel grau_blau_schatten](docs/grau_blau_schatten.png?raw=true "grau_blau_schatten")
* [Beispiel grau_blau](docs/grau_blau.png?raw=true "Beispiel grau_blau")

### 1. Funktionsumfang
* Ansteuerung der Milight Gateways/Controller mit der Treiberversion 6
* Unterstützung von 2x4 Zonen je Gateway (4 Zonen für RGBW und 4 Zonen für RGBWW Lampen )
* Unterstützung für "Alle Zonen" eines Milight Gateways/Controllers
* Beliebige Anzahl Lampen je Zonen
* Lampen sind über das Modul an die Milight Gateways/Controller an-/ablernbar
* Farb-Lampensteuerung mit Farbwert/Sättigung/Helligkeit (RGBWW) bzw. Farbwert/Helligkeit (RGBW)
* Weiß-Lampensteuerung mit Lichttemperatur/Helligkeit (RGBWW) bzw. Helligkeit (RGBW)
* Unterstützung der Sonder-Modi "Nachtmodus" und "Discomodus"
* n-Presets zur eigenen Erstellung und schnellen Steuerung verschiedener Modi (Bsp: Warweiß 50%)


### 2. Systemanforderungen
* Milight Gateway/Controller "iBox2" mit Treiberversion 6
* IP-Symcon Version 4.0 oder 4.1



### 3. Installation
Im Objektbaum der IP Symcon Managment Console über die Kern-Instanz "Module" folgende URL hinzufügen:
`git://github.com/Uhula/IPSMilightV6.git` oder `https://github.com/Uhula/IPSMilightV6.git`.


### 4. Einrichten der Instanzen in IP-Symcon

Unter "Instanz hinzufügen" ist das 'MilightV6'-Modul unter dem Hersteller 'Milight' aufgeführt.  

__Konfigurationsseite__:

Name          | Beschreibung
------------- | ---------------------------------
URL           | URL/IP-Adresse des Milight V6 Controllers
Port          | Portnummer, Standard 5987
Typ           | Art der zu steuernden Lampe (RGBWW, BRIDGE, RGBW)
Zone          | Zone1 .. Zone4, Alle
Presets       | Vorgaben für Lichtsituationen

#### Typ
Es ist wichtig den korrekten Lampen-Typ anzugeben, da hiervon die zu sendenden
UDP-Befehle abhängen.
RGBWW = Neue Lampen mit RGB/Warmweiß/Kaltweiß
RGBW = Ältere Lampen mit RGB/Warmweiß
BRIDGE = Lampe im Controller

#### Presets
Je Instanz können individuelle Vorgaben für Lichtsituationen gegeben werden.
Hierfür wird dann je Instanz ein eigenes Variablenprofil erzeugt, so dass die
Vorgaben im WebFront leicht auswählbar sind. Die Vorgaben sind als JSON-Array
in die Presets-Eigenschaft der Instanzen einzugeben.

Formatbeispiel:

      [{"id":10,"name":"Rot","Mode":1,"ColorHue":0,"ColorSaturation":100,"ColorBrightness":100}
      {"id":11,"name":"Grün","Mode":1,"ColorHue":120,"ColorSaturation":100,"ColorBrightness":100}
      {"id":20,"name":"Warmweiß 25%","Mode":2,"WhiteTemperature":2700,"WhiteBrightness":25}]



### 5. Statusvariablen und Profile

Die Statusvariablen/Kategorien werden automatisch angelegt. Das Löschen kann zu
Fehlfunktionen führen.

##### Statusvariablen

ID               | Name            | Typ | Profil                | Beschreibung
---------------- | --------------- | --- | --------------------- | ---------------------------------
Presets          | Vorgaben        | int | MILIGHTV6.Preset*     | Zur Verfügung stehende Vorgaben
Mode             | Modus           | int | MILIGHTV6.Mode|Link   | Aus / Farbig / Weiß / Nacht ...
ColorHue         | Farbwert        | int | MILIGHTV6.Hue         | Enthält den aktuellen Farbwert (Hue, 0..360)
ColorBrightness  | Farbhelligkeit  | int | MILIGHTV6.Brightness  | Aktuelle Helligkeitseinstellung 0..100%
ColorSaturation  | Farbsättigung   | int | MILIGHTV6.Saturation  | Aktuelle Sättigungseinstellung bei Farben 0..100%
Color            | Farbe           | int | ~HexColor             | Enthält die aktuelle Farbe
WhiteTemperature | Farbtemp.weiß   | int | MILIGHTV6.Temperature | Farbtemperatur für weißes Licht 2700-6500k
WhiteBrightness  | Farbhell.weiß   | int | MILIGHTV6.Brightness  | Helligkeitseinstellung weißes Licht 0..100%
DiscoProgram     | Disco-Programm  | int | MILIGHTV6.DiscoProgram| Disco-Programm 0..9


##### Profile:

Name                     | Typ            | Beschreibung
------------------------ | -------------- | ---------------------------------
MILIGHTV6.Preset*        | int 0..n       | Enthält die Namen der Vorgaben, wird für jede Instanz individuell erzeugt, da jede andere Vorgaben haben kann
MILIGHTV6.Mode           | int 0..4       | Namen der Modi Aus/Farig/Weiß/Nacht/DiscoProgram
MILIGHTV6.Link           | int 5..6       | Namen der Modi Link/Unlink (Lampe anlernen/ablernen)
MILIGHTV6.Hue            | int 0..360°    | Hue-Farbwert im Farbmodell HSV
MILIGHTV6.Brightness     | int 0..100     | Helligkeitswert
MILIGHTV6.Saturation     | int 0..100     | Sättigungswert
MILIGHTV6.Temperature    | int 2700-6500K | Lichttemperatur
MILIGHTV6.TemperatureSet | int 0..4       | Lichttemperatur, Schnellwahl von 5 Werten
MILIGHTV6.DiscoProgram   | int 0..9       | "None","Color-Fade","White-Fade","RGB-Fade","Color-Flash","Color-Brightness-Flash","Red-Fade-Flash","Green-Fade-Flash","Blue-Fade-Flash","White-Fade-Flash"


### 6. WebFront

Je nach gewähltem Modus werden im WebFront die einzelnen untergeordneten Controls
sichtbar/unsichtbar geschaltet.

### 7. PHP-Befehlsreferenz

Alle PHP-Befehle erhalten den Prefix MILIGHTV6_

##### boolean MILIGHTV6.SetMode( int $mode );  
Schaltet den Modus $mode ein. Die zur Verfügung stehenden Modi stehen als class-Konstante
zur Verfügung (MILIGHTV6::) :
MODE_OFF = 0; MODE_COLOR = 1; MODE_WHITE = 2; MODE_NIGHT = 3; MODE_DISCO = 4; MODE_LINK = 5; MODE_UNLINK = 6;
Liefert bei Erfolg true, sonst false.  
Beispiel:  
`MILIGHTV6_SetMode( MILIGHTV6::MODE_WHITE ); // weiß ein`

##### boolean MILIGHTV6.SetColorHue( int $value );  
Setzt den Farbwert. Der Farbwert wird als Hue-Angabe 0..360° nach dem HSV Farbraum erwartet.
Befindet sich die Instanz im Modus MODE_COLOR, wird die Änderung sofort zu den Lampen
durchgereicht.
Liefert bei Erfolg true, sonst false.  
Beispiel:  
`MILIGHTV6_SetColorHue( 120 ); // `

##### boolean MILIGHTV6.SetColorSaturation( int $value );  
Setzt die Farbsättigung. Die Farbsättigung wird als Prozentwert 0..100% erwartet.
Befindet sich die Instanz im Modus MODE_COLOR, wird die Änderung sofort zu den Lampen
durchgereicht.
Liefert bei Erfolg true, sonst false.  
Beispiel:  
`MILIGHTV6_SetColorSaturation( 50 ); // `

##### boolean MILIGHTV6.SetColorBrightness( int $value );  
Setzt die Farbhelligkeit. Die Farbhelligkeit wird als Prozentwert 0..100% erwartet.
Befindet sich die Instanz im Modus MODE_COLOR, wird die Änderung sofort zu den Lampen
durchgereicht.
Liefert bei Erfolg true, sonst false.  
Beispiel:  
`MILIGHTV6_SetColorBrightness( 50 ); // `

##### boolean MILIGHTV6.SetColor( int $value );  
Setzt die Farbe. Die Farbe wird als integer-Wert 0xRRGGBB erwartet. Es findet
intern eine Umrechnung in den HSV/HSB Farbraum statt.
Befindet sich die Instanz im Modus MODE_COLOR, wird die Änderung sofort zu den Lampen
durchgereicht.
Liefert bei Erfolg true, sonst false.  
Beispiel:  
`MILIGHTV6_SetColor( 0x00FF00 ); // `

##### boolean MILIGHTV6.SetWhiteBrightness( int $value );  
Setzt die Weißhelligkeit. Die Weißhelligkeit wird als Prozentwert 0..100% erwartet.
Befindet sich die Instanz im Modus MODE_WHITE, wird die Änderung sofort zu den Lampen
durchgereicht.
Liefert bei Erfolg true, sonst false.  
Beispiel:  
`MILIGHTV6_SetWhiteBrightness( 20 ); // `

##### boolean MILIGHTV6.SetWhiteTemperature( int $value );  
Setzt die Lichttemperatur. Die Lichttemperatur wird als Kelvinwert 2700K..6500K erwartet.
2700K = warmweiß, 6500K = kaltweiß.
Befindet sich die Instanz im Modus MODE_WHITE, wird die Änderung sofort zu den Lampen
durchgereicht.
Liefert bei Erfolg true, sonst false.  
Beispiel:  
`MILIGHTV6_SetWhiteTemperature( 2700 ); // `

##### boolean MILIGHTV6.SetPreset( int $value );  
Wendet die Vorgabe $value an und setzt die Lampenwerte entsprechend mit denen aus der Instanzeigenschaft "Presets".
Liefert bei Erfolg true, sonst false.  
Beispiel:  
`MILIGHTV6_SetPreset( 2 ); // `

##### boolean MILIGHTV6.SetDiscoProgram( int $value );  
Setzt das Disco-Programm. Das Disco-Programm wird als Wert 0..9 erwartet.
Befindet sich die Instanz im Modus MODE_DISCO, wird die Änderung sofort zu den Lampen
durchgereicht.
Liefert bei Erfolg true, sonst false.  
Beispiel:  
`MILIGHTV6_SetDiscoProgram( 2 ); // `

##### boolean MILIGHTV6.SetDiscoSpeed( int $value );  
Erhöht ($value=0)/verringert ($value=1) die Ausführgeschwindigkeit des Disco-Programms.
Befindet sich die Instanz im Modus MODE_DISCO, wird die Änderung sofort zu den Lampen
durchgereicht.
Liefert bei Erfolg true, sonst false.  
Beispiel:  
`MILIGHTV6_IncDiscoSpeed( 0 ); // `


### 8. Changelog
Siehe [:link:ChangeLog](./CHANGELOG.md).

### 9. Sonstiges
Verwendung auf eigene Gefahr, der Autor übernimmt weder Gewähr noch Haftung.

:copyright:2017ff Uhula
