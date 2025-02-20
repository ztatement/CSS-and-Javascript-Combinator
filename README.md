# CSS und JavaScript Combinator

Der CSS und JavaScript Combinator ist ein leistungsfähiges Tool zur Optimierung der Ladezeit von Webseiten. Er kombiniert mehrere CSS- oder JavaScript-Dateien zu einer einzigen Datei, was die Anzahl der HTTP-Anfragen reduziert und die Leistung der Website verbessert.

Hauptfunktionen:
>Datei-Kombination:  Fasst mehrere CSS- oder JavaScript-Dateien zu einer einzigen Datei zusammen.
>Caching:  Speichert kombinierte Dateien zwischen, um wiederholte Verarbeitungen zu vermeiden.
>Kompression:  Unterstützt gzip und deflate Kompression für schnellere Übertragung.
>Conditional GET:  Implementiert ETag-basiertes Caching für effiziente Aktualisierungen.
>Sicherheit:  Überprüft Dateipfade und -typen, um unbefugten Zugriff zu verhindern.
>Browserkompatibilität:  Berücksichtigt ältere Versionen des Internet Explorers bei der Kompression.

Der Combinator wird über .htaccess-Regeln in die Webseite integriert und verarbeitet automatisch CSS- und JavaScript-Anfragen. Dies führt zu schnelleren Ladezeiten und einer verbesserten Benutzererfahrung, besonders bei Websites mit vielen externen Ressourcen.

Verwendungs-Beispiel:

<code>
RewriteEngine On
RewriteBase /
RewriteRule ^css/(.*\.css) /combine.php?type=css&files=$1
RewriteRule ^javascript/(.*\.js) /combine.php?type=javascript&files=$1
</code>
