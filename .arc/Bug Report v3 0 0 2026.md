# Bug Report v3.0.0 - 2026-01-13

## Bug #1: [Daten im Dashboard werden nicht aktualisiert]
**Priority:** MEDIUM
**Feature:** [Admin-Dashboard]

**Problem:**
Das gesamte Abschussplan-Dashboard im Admin-Backend ist wird nicht aktualisiert. Speziell die Felder "Dieser Monat", "Status nach Wildart" und "Shortcode-Referenz". Die referenz-Sektion muss mit beispielen aller möglichen shortcodes gefüllt werden (inkl. der neuen). Außerdem wünsche ich mir eine Sektion mit Versionsinformation des Plugins und Kontakt zum Github repository.

**Error Log:** Not available

## Bug #2: [Meldungen exportieren fehlerhaft]
**Priority:** HIGH
**Feature:** [Export]

**Problem:**
Beim Export werden CSVs exportiert. Diese enthalten weder die korrekte anzahl an spalten noch die aktuellen Abschüsse. Wichtig ist, dass der Export alle verfügbaren Informationen pro abschussmeldungen enthält. Es gibt mehrere Stellen an denen die Exportfunktionalität genutzt wird. Wichtig ist eine zentrale Export-Funktionalität, die an allen stellen verwendet wird, inkl. dem Export über die URL. DAbei sind auch die Filtermöglichkeiten nach wildart und DAtum wichtig.

**Error Log:** not available

## Bug #3: [Bearbeiten von bestehenden Meldungen]
**Priority:** HIGH
**Feature:** [Editieren bestehender Meldungen im Admin-Backend]

**Problem:**
Wenn bestehende Meldungen im Admin-Backend editiert werden sollen, können 1) nicht alle Felder bearbeitet werden. Aktuell nur KAtegorie, WUS-Nummer, interne Notiz, Bemerkung, Jagdbezirk und DAtum. Es fehlen noch die MEldegruppe, der ERfasser und das ERfassungsdatum (WObei die Wildart nicht editierbar sein darf, wie es aktuell bereits umgesetzt). 2) In den Editier-Feldern müssen die selben Dropdown-Beschränkungen wie in der Erfassung gelten (Kategorie, Jagdbezirk und Meldegruppe) 3) Das Layout der Edit-Form passt nicht in den vorhanden bildausschnitt. Das Layout muss sich an dem zur VErfügung stehenden PLatz orientieren. 

**Error Log:** not available

## Bug #4: [Obleute-Zuweisung fehlerhaft]
**Priority:** CRITICAL
**Feature:** [Zuweisen von Obleuten im Admin-Backend]

**Problem:**
Als Admin nehme ich die Zuweiseung von Obleuten zu Wildarten und Meldegruppen vor. Anscheinend passt nach der Umstellung auf Erfassung von Jagdbezirken als Zusatzinformation am Abschuss die Zuweisung von Obleuten nicht mehr. Die Zusammenhänge in der ZUweisung zum Datenmodell muss überpürft werden.

**Error Log:** not available

## Bug #5: [Migrationen-Tab falsch angeordnet]
**Priority:** LOW
**Feature:** [Datenbanken Migrationen]

**Problem:**
Das neue Feature der Datenbankmigrationen wird über einen neuen Tab im Admin-Backend gesteuert. Dieser Tab soll  innerhalb des Einstellung-Tabs angeordnet werden.

**Error Log:** not available

## Bug #6: [Datenbank Migration "Migrate existing data" fehlerhaft]
**Priority:** CRITICAL
**Feature:** [Datenbanken Migrationen]

**Problem:**
Die Funktionen zum Migrieren existierender Daten funktioniert nicht. Der Button jann betätigt werden, die Rückfragen zur Migration werden auch gestellt, aber die Migration wird nicht durchgeführt. Die Fehlermeldung ist: "[9:29:59 PM] ❌ ❌ Migration 2: migrate existing data - FAILED: Migration class AHGMH_Migration_002 not found"

**Error Log:** not available

## Bug #7: [Neues Tab "Wildarten" unvollständig funktional]
**Priority:** CRITICAL
**Feature:** [Wildarten Konfiguration]

**Problem:**
Die neue Maske der Wildarten-Konfiguration weißt mehrere Fehler auf und muss nochmal überarbeitet werden. einige Fehler die mir aufgefallen sind: Erstellen neuer Meldegruppen speichert wiederholt gruppen von Meldegruppen und führt zur merhfachen Nennung der Gruppen, Obmannzuweisung ist auf der selben SEite (soll aber in einem eigenen Tab (besteht schon) untergebracht werden). Die wildartenkonfiguration kommen ebenfalls zweimal auf der selben seite vor. Die Limits der Abschüsse und die Vorauswahl der Limit Konfiguration lassen sich nicht festschreiben.

**Error Log:** not available

## Bug #8: [Bestehendes Tab "Wildarten" unter Einstellungen fehlerhaft]
**Priority:** CRITICAL
**Feature:** [Wildarten Konfiguration]

**Problem:**
Der bestehende Einstellungsdialog für die Konfigurationen der Wildarten ist fehlerhaft. Als Lösung wäre entweder 1) die Verschiebung der Wildartenkonfiguration in einen eigenen Tab. Dann muss dieser fehlerfrei sein (Siehe Bug 7) oder 2) Die wildarten Konfiguration bleibt unter Einstellungen dann muss sie natürlich mit dem neuen Dastenmodell zusammenpassen.  

**Error Log:** not available

## Bug #9: [Jagdbezirk in Abschussmeldungsmaske]
**Priority:** HIGH
**Feature:** [Shortcode abschuss_form]

**Problem:**
In dem Formular hinter dem Shortcode abschuss_form findet sich kein Feld für den Jagdbezirk. Dieses muss ein Drop Downfeld sein und nur die Jagdbezirke enthalten, die einer Meldegruppe zugewiesen wurden. Da es abhängig von der Meldegruppe ist, sollte das Auswahlfeld nach der Wahl der Melegruppe eingefügt werden.

**Error Log:** not available

## Bug #10: [Jagdbezirkzuweisung zu Meldegruppe]
**Priority:** CRITICAL
**Feature:** [Jagdbezirkerfassung]

**Problem:**
Im Admin-Backend fehlt eine Möglichkeit den Meldegruppen Jagdbezirke zuzuordnen. DAzu benötige ich einen eigenen Tab unter den Einstellungen um die ERstellung, Beabrietung, Löschung und Zuordnung der Jagdbezirke zu den Meldegruppen vorzunehmen.

**Error Log:** not available

## Bug #11: [abschuss_form_public ist nicht verfügbar]
**Priority:** CRITICAL
**Feature:** [Neue Erfassungsmethode Abschuss Form Public]

**Problem:**
Das Einbinden der neuen Erfassung public ist fehlerhaft und sperrt die Seite auf der der Shortcode eingebunden ist. Der Bug muss gefixt werden, wenn die Funktionalität eingebunden werden soll. 

**Error Log:** not available

## Bug #12: [Tabelle wp_ahgmh_activity_log nicht verfügbar]
**Priority:** CRITICAL
**Feature:** [Activitiy Log]

**Problem:**
Die Tabelle wp_ahgmh_activity_log wird nicht angelegt. Damit kann diese auch nicht gefüllt werden. 

**Error Log:** not available

## Bug #13: [abschuss_table zeigt falsche Spaltenbezeichnung]
**Priority:** CRITICAL
**Feature:** [abschuss_table]

**Problem:**
Die Übersicht hinter dem Shortcode abschuss_table zeigt eine falsche Spaltenüberschrift. Es steht akutell "jagdbezirk" und nicht "Meldegruppe" in der Übersicht. Ich benötige beide Felder Jagdbezirk und Meldegruppe (siehe Bug 9). 

**Error Log:** not available

## Bug #13: [abschuss_summary darf erst nach genehmigtem Abschuss aktualisiert werden]
**Priority:** CRITICAL
**Feature:** [abschuss_summary]

**Problem:**
Erst wenn der Obmann die Abschussmeldung genehmigt hat, darf die Abschusssummary aktualisiert werden. Bis dahin darf die öffentlich verfügbare Abschuss Summary nicht aktualisiert werden. DEn FEhler zu beheben ist wichtig für die gesamte 

**Error Log:** not available

## Bug #14: [abschuss_summary darf nur bei genehmigten Meldungen aktualisiert werden]
**Priority:** CRITICAL
**Feature:** [abschuss_summary]

**Problem:**
Wenn eine Abschussmeldung rejected wurde. Darf die Summary nicht aktualisiert werden. nur genehmigte Meldungen dürfen in der Summary_table landen.  

**Error Log:** not available


