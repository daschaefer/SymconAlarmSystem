Alarmanlagen Module für IP-Symcon
===
Dieses IP-Symcon PHP Modul dient zur Bereitstellung der Funktionalität einer Alarmanlage.

**Content**

1. [Funktionsumfang](#1-funktionsumfang)
2. [Anforderungen](#2-anforderungen)
3. [Variablen](#3-variablen)
6. [Funktionen](#4-funktionen)
6. [Webhook Parameter](#7-webhook-parameter)

## 1. Funktionsumfang  
In der Modulkonfiguration können beliebige Variablen von bspw. Sensoren, Reed-Kontakten, Bewegungsmeldern etc.pp. mit Auslösungswert eingetragen werden. Anhand dieser Konfiguration werden automatisch Ereignisse erzeugt die das Modul bei Auslösen ansprechen. Dieses Modul erzeugt dann wenn gewünscht eine Push Nachricht über IP-Symcon und setzt entsprechend eine ALARM Variable auf welche weitere Skripte "hören" können. Außerdem werden alle Auslösungen und Statusänderungen der Alarmanlage mit Timestamp protokolliert.

## 2. Anforderungen

- IP-Symcon 4.x installation (Linux / Windows)
- Unter IP-Symcon angelegte Sensoren die zum Auslösen verwendet werden können

## 3. Vorbereitung & Installation & Konfiguration

### Installation in IPS 4.x
Im "Module Control" (Kern Instanzen->Modules) die URL "https://github.com/daschaefer/SymconAlarmSystem.git" hinzufügen.  
Danach ist es möglich eine neue AlarmSystem Instanz innerhalb des Objektbaumes von IP-Symcon zu erstellen.

### Konfiguration innerhalb IPS
**Pushbenachrichtigung bei Alarm senden**

*Push Benachrichtigungen über IP-Symcon aktivieren/deaktivieren*

**Protokoll automatisch ausblenden**

*Beim Deaktivieren der Alarmanlage kann das Protokoll automatisch ausgeblendet werden.*

**Auslöser**

*Definition der Auslöser Variablen und deren Auslösewert*

## 3. Variablen
**ALARM**

*Wird TRUE wenn ein Alarm ausgelöst wurde. Ansonsten FALSE.*

**Protokoll**

*Protokoll für das Webinterface, und kann dort verlinkt werden.*

**Status**

*Status der Alarmanlage. Kann folgende Werte annehmen: Aus (0), Unscharf (1), Scharf (2), Alarm (3). Diese Variable sollte ebenfalls im Webinterface verlinkt werden.*

## 6. Funktionen

```php
ALRM_Arm(integer $InstanceID)
```
Schaltet die Alarmanlage auf Scharf.

---
```php
ALRM_Disarm(integer $InstanceID)
```
Schaltet die Alarmanlage auf Unscharf.

---
```php
ALRM_Trigger(integer $InstanceID, variant $identifier)
```
Alarmanlage auslösen, wobei $identifier irgendein String sein kann.

