### IP Symcon Milight V6 Modul

**Inhaltsverzeichnis**

1. [Funktionsumfang](#1-funktionsumfang)
2. [Systemanforderungen](#2-systemanforderungen)
3. [Installation](#3-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [WebFront](#6-webfront)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)
8. [Changelog](#8-changelog)
9. [Sonstiges](#9-sonstiges)


### 1. Funktionsumfang
Dieses IP Symcon PHP Modul dient dem Einbinden von Milight Version 6 Controllern
um die dort angemeldeten Milight-Lampen steuern zu können. Das Modul unterstützt
nur die Milight Controller Version 6, die am Markt unter verschiedenen Namen
vertrieben werden, aber meistens am Zusatz "WIFI iBox" oder "WIFI iBox2" erkannt
werden können.

Die Controller gibt es in zwei Bauformen, einmal als reine WLAN-Box "WIFI iBox" und
einmal inklusive eingebauter Lampe als "iBox2". Soll diese Lampe selbst gesteuert
werden, ist "BRIDGE" in der Konfiguration zu wählen.

An diesen Controllern können sowohl die aktuellen RGBWW Milight-Lampen, als automatisch
die älteren RGBW Lampen angelernt werden. Die neuen RBGWW Lampen haben den Vorteil,
dass sie sowohl warm- als auch kaltweißes Licht haben und eine Einstellung der
Lichttemperatur zwischen 2700 und 6500 Kelvin erlauben. Weiterhin kann bei ihnen,
im Gegensatz zu den älteren RGBW Lampen, auch die Farbsättigung (das Einmischen von weiß)
eingestellt werden. Damit sind Pastellfarben darstellbar.

Sowohl für RGBWW als auch für RGBW sind je 4 Zonen (Gruppen) von Lampen anlernbar.
Je Zone ist die Anzahl der Lampen nicht begrenzt.


* [Beispiel grau_blau_schatten](docs/grau_blau_schatten.png?raw=true "grau_blau_schatten")
* [Beispiel grau_blau](docs/grau_blau.png?raw=true "Beispiel grau_blau")


### 2. Systemanforderungen
* IP-Symcon Version 4.1
*


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
Type          | Art der zu steuernden Lampe (RGBWW, BRIDGE, RGBW)
Zone          | Zone1 .. Zone4


### 5. Statusvariablen und Profile

Die Statusvariablen/Kategorien werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

##### Statusvariablen

Name          | Typ            | Beschreibung
------------- | -------------- | ---------------------------------
Hue           | integer        | Enthält den aktuellen Farbwert (Hue, 0..360)
Brightness    | integer        | Aktuelle Helligkeitseinstellung 0..100%
Saturation    | integer        | Aktuelle Sättigungseinstellung bei Farben 0..100%
Mode          | MILIGHTV6.Mode | Off / Farbig / Weiß / Nacht ...

##### Profile:

Name           | Typ         | Beschreibung
-------------- | ----------- | ---------------------------------
MILIGHTV6.Mode | integer     | 

### 6. WebFront


### 7. PHP-Befehlsreferenz

Alle PHP-Befehle erhalten den Prefix MILIGHTV6_

##### boolean MILIGHTV6_setHue( integer $hue );  
Setzt die Farbe $hue (0..360) und schaltet die Lampen farbig ein 
Liefert bei Erfolg true, sonst false.  
Beispiel:  
`MILIGHTV6_setHue( 0xff ); // rot`



### 8. Changelog
Siehe [:link:ChangeLog](./CHANGELOG.md).

### 9. Sonstiges
Verwendung auf eigene Gefahr, der Autor übernimmt weder Gewähr noch Haftung.

:copyright:2016ff Uhula
