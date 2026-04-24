# Patch ilServer Ignore .git

Dieser Patch stellt sicher, dass im ilServer-Kontext das Verzeichnis
`.git` ignoriert wird (anstatt `.svn`).

## Patch-Markierungen

Patches wurden mit `databay-patch: begin ilserver-ignore-git-dir` und
`databay-patch: end ilserver-ignore-git-dir` markiert.

## Änderungen

Angepasst wurden im Rahmen der Funktionalitaet folgende Dateien:

* `components/ILIAS/WebServices/RPC/lib/src/main/java/de/ilias/services/object/DirectoryDataSource.java`
* `components/ILIAS/WebServices/RPC/lib/src/main/java/de/ilias/services/object/ObjectDefinitionReader.java`

Inhaltlich wurden folgende Punkte umgesetzt:

* Verzeichnisfilter von `.svn` auf `.git` umgestellt.
* Dadurch werden Git-Metadatenverzeichnisse beim Traversieren
  nicht mehr verarbeitet.

## Spezifikation

Siehe:

* https://github.com/ILIAS-eLearning/ILIAS/pull/11465
