# Patch CommandQueue addCommandsByType Performance

Dieser Patch uebernimmt die Aenderungen aus PR #11469 zur Verbesserung der
Performance in `CommandQueue.addCommandsByType`.

## Patch-Markierungen

Patches wurden mit `databay-patch: begin ilserver-command-queue-sql` und
`databay-patch: end ilserver-command-queue-sql` markiert.

## Änderungen

Angepasst wurden im Rahmen der Funktionalitaet folgende Dateien:

* `components/ILIAS/WebServices/RPC/lib/src/main/java/de/ilias/services/lucene/index/CommandQueue.java`

Inhaltlich wurden folgende Punkte umgesetzt:

* `addCommandsByType()` wurde von `SELECT` + zeilenweisem `INSERT` auf
  `INSERT IGNORE ... SELECT` umgestellt.
* Die bisherige Java-seitige Schleife ueber ResultSets entfaellt, wodurch die
  Anzahl an DB-Roundtrips reduziert wird.
* Fuer den Join-Fall wurden die Bedingungen auf `ore.deleted` und `oda.type`
  explizit qualifiziert.

## Spezifikation

Siehe:

* https://github.com/ILIAS-eLearning/ILIAS/pull/11469/changes
