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
Dieses IP Symcon PHP Modul dient dem Einbinden von Milight Gateways/Controllern um die dort angemeldeten Milight-Lampen steuern zu können. Das Modul unterstützt nur die Milight Controller mit der Treiberversion 6, die am Markt unter verschiedenen Namen vertrieben werden, aber meistens am Zusatz "WIFI iBox" oder "WIFI iBox2" erkannt werden können.

Die Controller gibt es in zwei Bauformen, einmal als reine WLAN-Box "WIFI iBox" und einmal inklusive eingebauter Lampe als "iBox2". Soll diese Lampe selbst gesteuert werden, ist "BRIDGE" in der Konfiguration zu wählen.

An diesen Controllern können sowohl die aktuellen RGBWW (RGB+CCT) Milight-Lampen, als auch die älteren RGBW Lampen angelernt werden. Die neuen RBGWW Lampen haben den Vorteil, dass sie sowohl warm- als auch kaltweißes Licht haben und eine Einstellung der Lichttemperatur zwischen 2700 und 6500 Kelvin erlauben. Weiterhin kann bei ihnen,
im Gegensatz zu den älteren RGBW Lampen, auch die Farbsättigung (das Einmischen von weiß) eingestellt werden. Damit sind Pastellfarben darstellbar.

Sowohl für RGBWW als auch für RGBW sind je 4 Zonen (Gruppen) von Lampen anlernbar. Je Zone ist die Anzahl der Lampen nicht begrenzt.

Ältere Milight Gateways/Controller, welche eine Treiberversion 2 bis 5 verwenden, sind nicht ansprechbar, da sich das Protokoll deutlich verändert hat.

* [Beispiel dunkel](DOCS/MilightV6_Beispiel_Dark.png?raw=true "Beispeil dunkel")
* [Beispiel hell](DOCS/MilightV6_Beispiel_Light.png?raw=true "Beispiel hell")

Die Beispiele verwenden den Material Design Skin - mit normalen IPS Skin sieht es anders aus.

### 1. Funktionsumfang
* Ansteuerung der Milight Gateways/Controller mit der Treiberversion 6
* Unterstützung von 2x4 Zonen je Gateway (4 Zonen für RGBW und 4 Zonen für RGBWW Lampen )
* Unterstützung für "Alle Zonen" eines Milight Gateways/Controllers
* Beliebige Anzahl Lampen je Zonen
* Lampen sind über das Modul an die Milight Gateways/Controller an-/ablernbar
* Farb-Lampensteuerung mit
** Farbwert/Sättigung/Helligkeit (RGBWW) bzw. Farbwert/Helligkeit (RGBW)
** Rotwert/Grünwert/Blauwert
** Farbrad
* Farbeinstellungen direkt in der Instanz oder über Popup (ab IPS 4.1)
* Weiß-Lampensteuerung mit Lichttemperatur/Helligkeit (RGBWW) bzw. Helligkeit (RGBW)
* Unterstützung der Sonder-Modi "Nachtmodus" und "Discomodus"
* n-Presets zur eigenen Erstellung und schnellen Steuerung verschiedener Modi (Bsp: Warweiß 50%)


### 2. Systemanforderungen
* Milight Gateway/Controller "iBox"/"iBox2" mit Treiberversion 6
* IP-Symcon Version 4.0 oder 4.1


### 3. Installation
Im Objektbaum der IP Symcon Managment Console über die Kern-Instanz "Module" folgende URL hinzufügen:
`git://github.com/Uhula/IPSMilightV6.git` oder `https://github.com/Uhula/IPSMilightV6.git`.


### 4. Einrichten der Instanzen in IP-Symcon

Unter "Instanz hinzufügen" ist das 'MilightV6'-Modul unter dem Hersteller 'Milight' aufgeführt.  

[Eigenschaften](DOCS/MilightV6_Eigenschaften.png?raw=true "Eigenschaften")

__Konfigurationsseite__:

#### IP
URL/IP-Adresse des Milight V6 Controllers

#### Port
Portnummer, Standard 5987

#### Lampentyp
Art der zu steuernden Lampe (RGBWW, BRIDGE, RGBW). Es ist wichtig den korrekten Lampen-Typ anzugeben, da hiervon die zu sendenden UDP-Befehle abhängen.
RGBWW (RGB+CCT) = Neue Lampen mit RGB/Warmweiß/Kaltweiß
RGBW = Ältere Lampen mit RGB/Warmweiß
BRIDGE = Lampe im Controller

#### Lampenzone
Zone1 .. Zone4, Alle

#### Farbeingabe über
Art, wie die Farbeingabe vorgenommen werden soll. Entweder üder HSB-Slider, RGB-Slider,
Farbrad oder alle.

#### Erlaubte Modi
Hierüber wird festgelgt, welche Auswahlmöglichkeiten zum Setzen der Modi der Lampen angeboten werden sollen. Es ist die Summe aus folgenden Möglichkeiten zu erfassen:
1 = farbig
2 = weiß
4 = Nachtmodus
8 = Discomodus
16 = Anlernen
32 = Ablernen
Bsp: Es soll "farbig", "weiß", "Anlernen" und "Ablernen" erlaubt sein -> 1+2+16+32 = 51

#### Lichtvorlagen
Je Instanz können individuelle Vorgaben für Lichtsituationen gegeben werden. Hierfür wird dann je Instanz ein eigenes Variablenprofil erzeugt, so dass die Vorgaben im WebFront leicht auswählbar sind. Die Vorgaben sind als JSON-Array in die Presets-Eigenschaft der Instanzen einzugeben. Siehe "Statusvariablen" für die möglichen Einträge.

Formatbeispiel:

      [{"id":10,"name":"Rot","Mode":1,"ColorH":0,"ColorS":100,"ColorV":100}
      {"id":11,"name":"Grün","Mode":1,"ColorR":0,"ColorG":255,"ColorB":0}
      {"id":20,"name":"Warmweiß 25%","Mode":2,"WhiteT":2700,"WhiteV":25}]


#### Einstellungen als Popup
Markiert: Die Farb-/Helligkeitseinstellungen werden nicht direkt in der Instanz vorgenommen, sondern es wird eine Kategorie-Instanz erzeugt, welche dann als Popup angezeigt wird. Geht erst ab IPS V4.1.



### 5. Statusvariablen und Profile

Die Statusvariablen/Kategorien werden automatisch angelegt. Das Löschen kann zu Fehlfunktionen führen.

##### Statusvariablen

ID           | Name            | Typ | Profil                | Beschreibung
------------ | --------------- | --- | --------------------- | ---------------------------------
Mode         | Modus           | int | MILIGHTV6.Mode(1)       | Aus / Farbig / Weiß / Nacht ...
ColorH       | Farbwert        | int | MILIGHTV6.360         | Enthält den aktuellen Farbwert (Hue, 0..360)
ColorB       | Farbhelligkeit  | int | MILIGHTV6.100         | Aktuelle Helligkeitseinstellung 0..100%
ColorV       | Farbsättigung   | int | MILIGHTV6.100         | Aktuelle Sättigungseinstellung bei Farben 0..100%
ColorR       | Rotwert         | int | MILIGHTV6.255         | Aktueller Rotwert 0..255 (0x00..0xFF)
ColorG       | Grünwert        | int | MILIGHTV6.255         | Aktueller Grünwert 0..255 (0x00..0xFF)
ColorB       | Blauwert        | int | MILIGHTV6.255         | Aktueller Blauwert 0..255 (0x00..0xFF)
Color        | Farbe           | int | ~HexColor             | Enthält die aktuelle Farbe
WhiteT       | Farbtemp.weiß   | int | MILIGHTV6.100         | Farbtemperatur für weißes Licht 2700-6500k
WhiteV       | Farbhell.weiß   | int | MILIGHTV6.100         | Helligkeitseinstellung weißes Licht 0..100%
DiscoProgram | Disco-Programm  | int | MILIGHTV6.DiscoProgram| Disco-Programm 0..9
Preset       | Lichtvorlage    | int | MILIGHTV6.Presets(1)  | Ausgewählte Lichtvorlage

(1) Da diese Profile je Instanz unterschiedlich sind, wird je Instanz ein eigenes Profil angelegt, welches im Namen mit der InstanzID ergänzt wird.

##### Profile:

Name                     | Typ            | Beschreibung
------------------------ | -------------- | ---------------------------------
MILIGHTV6.Preset(1)      | int 0..n       | Enthält die Namen der Vorgaben
MILIGHTV6.Mode(1)        | int 0..6       | Namen der Modi Aus/Farig/Weiß/Nacht/DiscoProgram/Anlernen/Ablernen
MILIGHTV6.360            | int 0..360%    | Hue-Farbwert in ° im Farbmodell HSV
MILIGHTV6.100            | int 0..100%    | Wert 0..100
MILIGHTV6.255            | int 0..255%    | Wert 0..255
MILIGHTV6.ColorTemp      | int 2700.6500  | Lichttemperatur in Kelvin
MILIGHTV6.ColorTempSet   | int 0..4       | Lichttemperatur, Schnellwahl von 5 Werten
MILIGHTV6.DiscoProgram   | int 0..9       | "None","Color-Fade","White-Fade","RGB-Fade","Color-Flash","Color-Brightness-Flash","Red-Fade-Flash","Green-Fade-Flash","Blue-Fade-Flash","White-Fade-Flash"

(1) Da diese Profile je Instanz unterschiedlich sind, wird je Instanz ein eigenes Profil angelegt, welches im Namen mit der InstanzID ergänzt wird.


### 6. WebFront

Je nach gewähltem Modus werden im WebFront die einzelnen, der Instanz direkt untergeordneten Controls sichtbar/unsichtbar geschaltet. Im Popup allerdings nicht.

### 7. PHP-Befehlsreferenz

Alle PHP-Befehle erhalten den Prefix MILIGHTV6_

##### boolean MILIGHTV6.SetMode( int $mode );  
Schaltet den Modus $mode ein. Die zur Verfügung stehenden Modi stehen als class-Konstante
zur Verfügung :
MILIGHTV6::MODE_OFF = 0;
MILIGHTV6::MODE_COLOR = 1;
MILIGHTV6::MODE_WHITE = 2;
MILIGHTV6::MODE_NIGHT = 4;
MILIGHTV6::MODE_DISCO = 8;
MILIGHTV6::MODE_LINK = 16;
MILIGHTV6::MODE_UNLINK = 32;
Beispiel:  
`MILIGHTV6_SetMode( MILIGHTV6::MODE_WHITE ); // weiß ein`

##### boolean MILIGHTV6.SetColorH( int $value );  
Setzt den Farbwert. Der Farbwert wird als Hue-Angabe 0..360° nach dem HSV Farbraum erwartet. Befindet sich die Instanz im Modus MODE_COLOR, wird die Änderung sofort zu den Lampen durchgereicht. Liefert bei Erfolg true, sonst false.  
Beispiel:  
`MILIGHTV6_SetColorH( 120 ); // `

##### boolean MILIGHTV6.SetColorS( int $value );  
Setzt die Farbsättigung. Die Farbsättigung wird als Prozentwert 0..100% erwartet. Befindet sich die Instanz im Modus MODE_COLOR, wird die Änderung sofort zu den Lampen durchgereicht. Liefert bei Erfolg true, sonst false.  
Beispiel:  
`MILIGHTV6_SetColorS( 50 ); // `

##### boolean MILIGHTV6.SetColorV( int $value );  
Setzt die Farbhelligkeit. Die Farbhelligkeit wird als Prozentwert 0..100% erwartet. Befindet sich die Instanz im Modus MODE_COLOR, wird die Änderung sofort zu den Lampen durchgereicht. Liefert bei Erfolg true, sonst false.  
Beispiel:  
`MILIGHTV6_SetColorV( 50 ); // `

##### boolean MILIGHTV6.SetColorR( int $value );  
##### boolean MILIGHTV6.SetColorG( int $value );  
##### boolean MILIGHTV6.SetColorB( int $value );  
Setzt die Farbwert für den Rot-/Grün-/Blauanteil (0..255 bzw. 0x00..0xFF). Befindet sich die Instanz im Modus MODE_COLOR, wird die Änderung sofort zu den Lampen durchgereicht. Liefert bei Erfolg true, sonst false.  
Beispiel:  
`MILIGHTV6_SetColorR( 0xFF ); // `

##### boolean MILIGHTV6.SetColor( int $value );  
Setzt die Farbe. Die Farbe wird als integer-Wert 0xRRGGBB erwartet. Es findet intern eine Umrechnung in den HSV/HSB Farbraum statt. Befindet sich die Instanz im Modus MODE_COLOR, wird die Änderung sofort zu den Lampen
durchgereicht. Liefert bei Erfolg true, sonst false.  
Beispiel:  
`MILIGHTV6_SetColor( 0x00FF00 ); // `

##### boolean MILIGHTV6.SetWhiteV( int $value );  
Setzt die Weißhelligkeit. Die Weißhelligkeit wird als Prozentwert 0..100% erwartet. Befindet sich die Instanz im Modus MODE_WHITE, wird die Änderung sofort zu den Lampen durchgereicht. Liefert bei Erfolg true, sonst false.  
Beispiel:  
`MILIGHTV6_SetWhiteV( 20 ); // `

##### boolean MILIGHTV6.SetWhiteT( int $value );  
Setzt die Lichttemperatur. Die Lichttemperatur wird als Kelvinwert 2700K..6500K erwartet. 2700K = warmweiß, 6500K = kaltweiß. Befindet sich die Instanz im Modus MODE_WHITE, wird die Änderung sofort zu den Lampen
durchgereicht. Liefert bei Erfolg true, sonst false.  
Beispiel:  
`MILIGHTV6_SetWhiteT( 2700 ); // `

##### boolean MILIGHTV6.SetPreset( int $value );  
Wendet die Vorgabe $value an und setzt die Lampenwerte entsprechend mit denen aus der Instanzeigenschaft "Presets".
Liefert bei Erfolg true, sonst false.  
Beispiel:  
`MILIGHTV6_SetPreset( 2 ); // `

##### boolean MILIGHTV6.SetDiscoProgram( int $value );  
Setzt das Disco-Programm. Das Disco-Programm wird als Wert 0..9 erwartet. Befindet sich die Instanz im Modus MODE_DISCO, wird die Änderung sofort zu den Lampen durchgereicht. Liefert bei Erfolg true, sonst false.  
Beispiel:  
`MILIGHTV6_SetDiscoProgram( 2 ); // `

##### boolean MILIGHTV6.SetDiscoSpeed( int $value );  
Erhöht ($value=0)/verringert ($value=1) die Ausführgeschwindigkeit des Disco-Programms. Befindet sich die Instanz im Modus MODE_DISCO, wird die Änderung sofort zu den Lampen durchgereicht. Liefert bei Erfolg true, sonst false.  
Beispiel:  
`MILIGHTV6_IncDiscoSpeed( 0 ); // `


### 8. Changelog
Siehe [:link:ChangeLog](./CHANGELOG.md).

### 9. Sonstiges
Verwendung auf eigene Gefahr, der Autor übernimmt weder Gewähr noch Haftung.

:copyright:2017ff Uhula
