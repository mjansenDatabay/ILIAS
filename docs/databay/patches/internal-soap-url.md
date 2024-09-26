# Performance-Optimierung für Soap Aufrufe vom Server zum Server. 

Dieser Patch fügt neue ILIAS-Setup-Optionen für die Webservices hinzu, um eine URL zu konfigurieren, die für SOAP-Aufrufe vom Server zum Server verwendet werden sollen.
Siehe: https://gitlab.databay.de/ilias-hosting/ilias/-/issues/8

## Patch-Markierungen

Patches wurden mit `databay-patch: begin internal soap url` und `databay-patch: end internal soap url` markiert.

## Änderungen

Angepasst wurden im Rahmen der Funktionalität folgende Dateien:

* Services/WebServices/SOAP/classes/class.ilSoapClient.php
* Services/WebServices/classes/Setup/class.ilWebServicesConfigStoredObjective.php
* Services/WebServices/classes/Setup/class.ilWebServicesSetupAgent.php
* Services/WebServices/classes/Setup/class.ilWebServicesSetupConfig.php
* webservice/soap/classes/class.ilNusoapUserAdministrationAdapter.php
* webservice/soap/lib/nusoap.php
* webservice/soap/server.php

Folgende Optionen wurden für die config.json des ILIAS Setup hinzugefügt:

```json
{
    ...
    "webservices": {
        ...
        "soap_internal_wsdl_path": "https://foo",
        "soap_internal_wsdl_verify_peer": true,
        "soap_internal_wsdl_verify_peer_name": true,
        "soap_internal_wsdl_allow_self_signed": false
    }
}
```

Die Optionen:

* `soap_internal_wsdl_verify_peer`
* `soap_internal_wsdl_verify_peer_name`
* `soap_internal_wsdl_allow_self_signed`

setzen die entsprechenden SSL Optionen wenn `soap_internal_wsdl_path` gesetzt ist (Siehe: https://www.php.net/manual/en/function.stream-context-create.php).

## Spezifikation

Der Patch setzt folgenden FR um: https://docu.ilias.de/go/wiki/wpage_8363_1357
