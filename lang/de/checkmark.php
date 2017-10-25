<?php
// This file is part of mod_checkmark for Moodle - http://moodle.org/
//
// It is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// It is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for component 'mod_checkmark', language 'de'
 *
 * @package   mod_checkmark
 * @author    Philipp Hager
 * @copyright 2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['checkmark:addinstance'] = 'Kreuzerlübung anlegen';
$string['checkmark:grade'] = 'Kreuzerlübung benoten';
$string['checkmark:trackattendance'] = 'Anwesenheit erfassen';
$string['checkmark:gradepresentation'] = 'Tafelleistung benoten';
$string['checkmark:submit'] = 'Kreuzerlübung abgeben';
$string['checkmark:view'] = 'Zeige Kreuzerlübung';
$string['checkmark:view_preview'] = 'Vorschau ansehen';

$string['absent'] = 'Nicht anwesend';
$string['activateindividuals'] = 'Individualfunktionen aktivieren';
$string['strassignment'] = 'Aufgabe';
$string['all'] = 'Alle';
$string['all_absent'] = 'Alle abwesende Teilnehmer/innen';
$string['all_attendant'] = 'Alle anwesende Teilnehmer/innen';
$string['all_unknown'] = 'Alle mit unbekanntem Anwesenheitsstatus';
$string['alwaysshowdescription'] = 'Beschreibung immer anzeigen';
$string['alwaysshowdescription_help'] = 'Wenn diese Option deaktiviert ist, wird die Aufgabenbeschreibung für Teilnehmer/innen erst ab dem Abgabebeginn angezeigt.';
$string['attendance'] = 'Anwesenheit';
$string['attendance_help'] = 'In der Spalte wird die Anwesenheit der Teilnehmer/innen angezeigt:<br /><ul><li>? - unbekannt</li><li>✓ - anwesend</li><li>✖ - abwesend</li></ul>';
$string['attendance_not_set_grade_locked'] = 'Die Anwesenheit für {$a} konnte nicht gesetzt werden, da diese in den Bewertungen gesperrt oder überschrieben wurde.';
$string['attendancestatus'] = 'Aktueller Status ihrer Anwesenheit';
$string['attendancegradebook'] = 'Anwesenheit in Kursbewertung erfassen';
$string['attendancegradebook_help'] = 'Wenn Sie diese Funktion aktivieren, wird die Anwesenheit in den Kursbewertung als Bewertungsaspekt angezeigt: <ul><li>Leere Bewertung - wenn Status unbekannt</li><li>Wert 0 - wenn abwesend</li><li>Wert 1- wenn anwesend</li></ul>';
$string['attendancegradelink'] = 'Anwesenheit an automatische Bewertung koppeln';
$string['attendancegradelink_help'] = 'Aktivieren Sie diese Funktion, um die gespeicherten Anwesenheiten mit der automatischen Bewertung der Kreuzerlübung zu verknüpfen. Punkte der Kreuzerlübung werden nur dann aggregiert, wenn die Person als anwesend gekennzeichnet wurde.';
$string['attendancegradelink_hint'] = 'Hinweis: Automatische Bewertung ist an Anwesenheit gekoppelt.';
$string['attendant'] = 'Anwesend';
$string['autograde_strall'] = 'Alle Abgaben';
$string['autograde_strchanged'] = 'Im Zuge der automatischen Bewertung werden bei <strong>{$a}</strong> Teilnehmer/innen die Punkte geändert.';
$string['autograde_strmultiplesubmissions'] = '{$a} Abgaben';
$string['autograde_stronesubmission'] = '1 Abgabe';
$string['autograde_strreq'] = 'Abgaben, die ein Bewertungsupdate benötigen,';
$string['autogradebuttonstitle'] = 'Berechne automatisch Abgaben-Bewertungen f&uuml;r Kreuzerlübung {$a}';
$string['autograde_confirm'] = 'Sie sind dabei <strong>{$a}</strong> automatisch zu bewerten. Die bisherigen Bewertungen werden überschrieben!';
$string['autograde_confirm_continue'] = 'Sind Sie SICHER, dass Sie fortfahren wollen?';
$string['autograde_error'] = 'Ein Fehler ist während der automatischen Bewertung aufgetreten!';
$string['autograde_failed'] = 'Automatische Bewertung fehlgeschlagen!';
$string['autograde_one_success'] = 'Automatische Bewertung erfolgreich! 1 Abgabenbewertung wurde aktualisiert.';
$string['autograde_success'] = 'Automatische Bewertung erfolgreich! {$a} Abgabenbewertungen wurden aktualisiert.';
$string['autograde_users_with_unknown_attendance'] = 'ACHTUNG: {$a} Abgabe konnte NICHT automatisch bewertet werden, da die Anwesenheit noch nicht erfasst wurde!';
$string['autograde_notsupported'] = 'Diese Notenskala wird von der automatischen Bewertung nicht unterstützt.';
$string['autograde_non_numeric_grades'] = 'Die automatische Bewertung benötigt numerische Angaben für die Bewertung. Dies kann in den Instanzeinstellungen sichergestellt werden.';
$string['bulk_no_users_selected'] = 'Sie haben keine Teilnehmer/in ausgewählt. Wählen Sie die gewünschten Teilnehmer/innen über die Checkboxen der linken Tabellenspalte aus.';
$string['cantfixevent'] = 'Konnte Kalendereintrag mit der ID {$a->id}, Name {$a->name} nicht reparieren ({$a->matches} treffer).';
$string['cfg_nowarning'] = 'keine Warnung';
$string['cfg_pdfexampleswarning'] = 'Beispielanzahl-Limit für Warnung PDF Export';
$string['cfg_pdfexampleswarning_desc'] = 'Ab wie vielen Beispielen in einer Instanz eine Warnung gezeigt wird, dass kein ordentlicher PDF-Export mehr garantiert werden kann. Üblicherweise muss dieser Wert nicht verändert werden, es sei denn, man möchte diese Warnung gar nicht anzeigen, oder sie für die konkrete Installation etwas verfeinern.';
$string['checkbrokengradebookgrades'] = 'Auf fehlerhafte Gradebook-Einträge prüfen';
$string['checkbrokengradebookgrades_desc'] = 'Aufgrund eines Bugs in Version 2.9.1 wurden Bewertungen nicht korrekt ins Gradebook übertragen.
Die folgenden Abgaben sind davon betroffen:';
$string['checkbrokengradebookgrades_mail'] = 'Aufgrund eines Bugs in Version 2.9.1 wurden Bewertungen nicht korrekt ins Gradebook übertragen.
Die folgenden Abgaben waren davon betroffen und wurden automatisch repariert:';
$string['checkmark_overviewsummary'] = 'Sie haben {$a->checked_examples} / {$a->total_examples} Beispiele ({$a->checked_grade} / {$a->total_grade} Punkte) gekreuzt<br />{$a->grade}<br />';
$string['checkmark_overviewsummary_nograde'] = 'Sie haben {$a->checked_examples} / {$a->total_examples} Beispiele gekreuzt<br />{$a->grade}<br />';
$string['checkmark_summary'] = 'Sie haben <span id="examples">{$a->checked_examples}</span> von {$a->total_examples} Beispielen gekreuzt.<br />(<span id="grade">{$a->checked_grade}</span> von maximal {$a->total_grade} Punkten)';
$string['checkmarkstatstitle'] = 'Kursübersicht Kreuzerlübungen';
$string['choose'] = 'Mit Auswahl...';
$string['couldfixevent'] = 'Konnte Termin mit der ID {$a->id} und dem Namen {$a->name} erfolgreich reparieren.';
$string['count_individuals_mismatch'] = 'Die Anzahl der Beispielnamen({$a->namecount}) stimmt nicht mit der Anzahl der Beispielgewichtungen({$a->gradecount}) überein!';
$string['datasettingstitle'] = 'Dateneinstellungen';
$string['data_settings'] = 'Druckeinstellungen';
$string['data_preview_help'] = 'Klicken Sie in der Vorschau auf [+] und [-] um die zu druckenden Spalten ein bzw. auszuschalten!';
$string['data_preview'] = 'Datenvorschau';
$string['duedatevalidation'] = 'Das Abgabeende muss nach dem Abgabebeginn liegen.';
$string['element_disabled'] = 'Folgendes Element ist gesperrt, da bereits Abgaben getätigt wurden.';
$string['elements_disabled'] = 'Folgende Elemente sind gesperrt, da bereits Abgaben getätigt wurden.';
$string['eventgradeupdated'] = 'Note aktualisiert';
$string['eventsubmissionsexported'] = 'Abgaben Exportiert';
$string['eventsubmissionupdated'] = 'Abgabe aktualisiert';
$string['eventviewprintpreview'] = 'Printpreview angezeigt';
$string['eventviewsubmissions'] = 'Abgaben angezeigt';
$string['examplegrades'] = 'Beispielgewichtungen';
$string['examplegrades_help'] = 'Definiert die Wertigkeiten der einzelnen Beispiele. Diese werden mit einem Trennzeichen getrennt, welches in Klammern angegeben ist.';
$string['examplenames'] = 'Beispielnamen';
$string['examplenames_help'] = 'Definiert die Namen der einzelnen Beispiele. Diese werden mit einem Trennzeichen getrennt, welches in Klammern angegeben ist.';
$string['exampleprefix'] = 'Beispielpräfix';
$string['exampleprefix_help'] = 'Geben Sie hier optional ein Namenspräfix ein, das allen Beispielnamen automatisch vorangestellt werden. Dieses Feld kann auch leer sein.';
$string['example_preview_title'] = 'Beispielvorschau';
$string['example_preview_title_help'] = 'Die Beispielvorschau zeigt einen Überblick zu den anzukreuzenden Optionen.';
$string['filter'] = 'Filter';
$string['firstexamplenumber'] = 'Startnummer';
$string['firstexamplenumber_help'] = 'Nummer des ersten Beispieles. Die restlichen Beispiele werden laufend hochgezählt.';
$string['flexiblenaming'] = 'Individuelle Namen/Punkte';
$string['flexiblenaming_help'] = 'Wenn aktiviert, werden statt fortlaufend hochgezählten Beispielen, die im Textfeld angegebenen Bezeichnungen verwendet. Weiters werden die Punkte für einzelne Beispiele im darunter liegenden Textfeld angegeben. Die Namen und erreichbaren Punkte der Beispiele werden mittels Komma (",") getrennt.';
$string['format'] = 'Format';
$string['grade_automatically'] = 'automatisch bewerten';
$string['gradesum_mismatch'] = 'Die Summe der Beispielpunkte stimmt nicht mit der Punkteanzahl der Aufgabe überein ({$a->gradesum}/{$a->maxgrade})!';
$string['grade_mismatch'] = 'Die Punkte müssen ein ganzzahliges Vielfaches der Beispielanzahl sein!';
$string['grade'] = 'Bewertung';
$string['grade_help'] = 'Bei aktivierten Individualfunktionen muss die Gesamtbewertung gleich der Summe der Einzelbewertungen der Beispiele sein (max 100).<br />Bei Standardbeispielen ist jedes ganzzahlige Vielfache der Beispielanzahl zulässig. Die Punkte pro Beispiel werden dann in der Modulinstanz automatisch angepasst.<br />Bei aktiviertem Javascript wird die Punktesumme automatisch ausgewählt bzw. nur ganzzahlige Vielfache der Beispielanzahl akzeptiert.';
$string['gradingdue'] = 'Erinnerung zur Bewertung';
$string['gradingdue_help'] = 'Das erwartete Datum markiert den Abschluss der Abgabe. Dieses Datum wird verwendet, um Benachrichtigungen für Trainer/innen im Dashboard zu priorisieren.';
$string['informstudents'] = 'Sende Benachrichtigungen';
$string['informstudents_help'] = 'Wenn aktiviert, werden Teilnehmer/in über den Status ihrer Anwesenheit informiert.';
$string['nostudents'] = 'Es sind keine Teilnehmer/innen zur Anzeige vorhanden!';
$string['nostudentsmatching'] = 'Es sind keine Teilnehmer/innen vorhanden, die den aktuellen Einstellungen entsprechen!';
$string['nosubmission'] = 'Es ist noch keine Abgabe vorhanden.';
$string['nousers'] = 'Es sind keine Teilnehmer/innen zur Anzeige vorhanden!';
$string['numberofexamples'] = 'Beispielanzahl';
$string['numberofexamples_help'] = 'Anzahl der Beispiele';
$string['optimum'] = 'Optimal';
$string['presentationfeedback'] = 'Tafelleistungskommentar';
$string['presentationfeedback_table'] = 'Kommentar (Tafelleistung)';
$string['presentationgrade'] = 'Tafelleistung';
$string['presentationgrade_table'] = 'Bewertung (Tafelleistung)';
$string['presentationgrade_short'] = 'TL';
$string['presentationgradebook'] = 'Tafelleistung in Kursbewertungen erfassen';
$string['presentationgradebook_help'] = 'Wird diese Option aktiviert, so scheint die Tafelleistung als eigener Bewertungsaspekt in den Kursbewertungen auf.';
$string['presentationgrading'] = 'Tafelleistung erfassen';
$string['presentationgrading_help'] = 'Wird diese Option aktiviert, können Tafelleistungen als eigenständige Bewertungen in der Kreuzerlübung eingetragen werden.<br />Beachten Sie bitte, dass die Art der erfassbaren Information durch die nachfolgenden Elemente beeinflusst wird. Näheres in den Hilfen der einzelnen Punkte.';
$string['presentationgrading_grade'] = 'Bewertung der Tafelleistung';
$string['presentationgrading_grade_help'] = 'Diese Einstellungen kontrollieren, wie die Tafelleistung erfasst wird. Für den Typ haben Sie die auswahl zwischen: <ul><li><strong>Kein</strong> Es werden keine Noten aufgezeichnet, man hat jedoch die Möglichkeit ein Text-Feedback zu geben</li><li><strong>Skala</strong> Benutzen Sie eine zuvor definierte Skala, um die Tafelleistungen der Teilnehmer/innen zu bewerten</li><li><strong>Punkt</strong> benutzen Sie einen numerischen Wert, um die Tafelleistung der Teilnhemer/innen zu bewerten</li></ul>';
$string['presentationheader'] = 'Tafelleistung';
$string['printsettingstitle'] = 'Exporteinstellungen';
$string['search:activity'] = 'Kreuzerlübung - Aktivitätsinformation';
$string['sendnotifications'] = 'Sende Benachrichtigungen';
$string['select'] = 'Auswählen';
$string['selection'] = 'Auswahl';
$string['setabsent'] = 'als abwesend markieren';
$string['setabsentandgrade'] = 'als abwesend markieren und bewerten';
$string['setattendant'] = 'als anwesend markieren';
$string['setattendantandgrade'] = 'als anwesend markieren und bewerten';
$string['signature'] = 'Unterschrift';
$string['start'] = 'Start';
$string['strallononepage'] = 'Alles auf eine Seite';
$string['strautograded'] = '[automatisch bewertet]';
$string['strexample'] = 'Beispiel';
$string['strexamples'] = 'Beispiele';
$string['strlandscape'] = 'Querformat';
$string['strlarge'] = 'Groß';
$string['strmedium'] = 'Mittel';
$string['pdfpageorientation'] = 'Seitenausrichtung';
$string['pdfsettings'] = 'PDF Einstellungen';
$string['strpapersizes'] = 'Papierformat';
$string['strportrait'] = 'Hochformat';
$string['strpoint'] = 'Punkt';
$string['strpoints'] = 'Punkte';
$string['strprint'] = 'Datei herunterladen';
$string['pdfprintheader'] = 'Kopf-/Fußzeile';
$string['pdfprintheader_help'] = 'Drucke Kopfzeile wenn angehakt';
$string['pdfprintheaderlong'] = 'Inkludiere Kopf-/Fußzeilen';
$string['strprintpreview'] = 'Export';
$string['strprintpreviewtabalt'] = 'Export';
$string['strrefreshdata'] = 'Vorschau aktualisieren';
$string['strsmall'] = 'Klein';
$string['strstdexamplecount'] = 'Beispielanzahl';
$string['strstdexamplecountdesc'] = 'Ist die Anzahl der Beispiele, die standardmäßig eingestellt sind';
$string['strstdexamplestart'] = 'Beispielanfangsnummer';
$string['strstdexamplestartdesc'] = 'Ist die standardmäßige Nummer des ersten Beispiels';
$string['strstdnames'] = 'Individuelle Namen';
$string['strstdnamesdesc'] = 'Beispielnamen, wenn "Individuelle Namen" aktiviert ist';
$string['strstdgrades'] = 'Standardbewertungen';
$string['strstdgradesdesc'] = 'Beispielgewichtungen, wenn individuelle Namen aktiviert ist';
$string['strsubmissions'] = 'Abgaben';
$string['strsubmissionstabalt'] = 'Öffne Abgabenansicht';
$string['strsum'] = 'Summe';
$string['pdftextsize'] = 'Textgröße';
$string['strvalidmsgtime'] = 'Gültigkeitsdauer Benachrichtigung';
$string['strvalidmsgtimedesc'] = 'Anzahl der Tage, nach denen Benotungsbenachrichtigungen nicht mehr versandt werden';
$string['str_userid'] = 'Matrikelnummer';
$string['trackattendance'] = 'Anwesenheit erfassen';
$string['trackattendance_help'] = 'Aktivieren Sie diese Funktion, um die Anwesenheit bei Teilnehmer/innen zu protokollieren.';
$string['ungraded'] = 'Nicht bewertete';
$string['unknown'] = 'Unbekannt';
/*
 * End of Block for checkmark-specific strings
 */

/*
 * Block with standard-checkmark-strings (adopted to checkmark)
 */
$string['allowresubmit'] = 'Erneute Abgabe erlauben';
$string['allowresubmit_help'] = 'Wenn aktiviert, ist es Teilnehmer/innen erlaubt, nach der Benotung erneut (für eine erneute Benotung) abzugeben.';
$string['alreadygraded'] = 'Ihre Abgabe wurde bereits benotet und erneutes Abgeben ist daher nicht erlaubt.';
$string['bulk'] = 'Massenverarbeitung';
$string['bulk_help'] = 'Hier können Sie Aktionen wie das Setzen der Anwesenheit, automatische Bewertung oder Kombinationen daraus an mehreren Einträgen gleichzeitig ausführen.<br />
<ul>
<li><strong>als Anwesend markieren:</strong> Markiert die ausgewählten Teilnehmer/innen als Anwesend</li>
<li><strong>als Abwesend markieren:</strong> Markiert die ausgewählten Teilnehmer/innen als Abwesend</li>
<li><strong>automatisch bewerten:</strong> Errechnet und setzt die Bewertung für die ausgewählten Teilnehmer/innen automatisch</li>
<li><strong>als Anwesend markieren und bewerten:</strong> Markiert die ausgewählten Teilnehmer/innen als Anwesend und bewertet diese anschließend automatisch</li>
<li><strong>als Abwesend markieren und bewerten:</strong> Markiert die ausgewählten Teilnehmer/innen als Abwesend und bewertet diese anschließend automatisch</li>
</ul><br />
Achtung: Wenn die Anwesenheit an die Noten gekoppelt ist werden nur als Anwesend markierte Teilnehmer/innen mit Punkten für gekreuzte Beispiele bewertet.
Abwesende Teilnehmer/innen erhalten 0 Punkte und Teilnehmer/innen mit unbekanntem Anwesenheitsstatus werden übersprungen!';
$string['checkmarkdetails'] = 'Kreuzerlübungsdetails';
$string['checkmarkmail'] = '{$a->grader} hat eine Rückmeldung bezüglich ihrer
Kreuzerlübungsabgabe für \'{$a->checkmark}\' gepostet.

Sie können sie als Anhang an Ihre Abgabe einsehen:

    {$a->url}';
$string['checkmarkmailhtml'] = '{$a->grader} hat eine Rückmeldung zu ihrer
Kreuzerlübungsabgabe für \'<i>{$a->checkmark}</i>\' gegeben.<br /><br />
Sie können sie als Anhang an ihre <a href="{$a->url}">Abgabe</a> einsehen.';
$string['checkmarkmailsmall'] = '{$a->grader} hat eine Rückmeldung zu ihrer
Kreuzerlübungsabgabe für \'{$a->checkmark}\' gegeben. Sie können sie als Anhang an ihre Abgabe einsehen.';
$string['checkmarkname'] = 'Name der Kreuzerlübung';
$string['checkmarks'] = 'Kreuzerl';
$string['checkmarksubmission'] = 'Kreuzerlübungsabgaben';
$string['checksummary'] = 'Kreuzerlzusammenfassung';
$string['availabledate'] = 'Abgabebeginn';
$string['availabledate_help'] = 'Beginn der Abgabeperiode. Nach diesem Datum ist es Teilnehmer/innen möglich Abgaben zu tätigen.';
$string['cannotviewcheckmark'] = 'Sie können diese Kreuzerlübung nicht betrachten';
$string['comment'] = 'Kommentar';
$string['configshowrecentsubmissions'] = 'Jeder kann Benachrichtigungen über Abgaben in den letzen Aktivitäten sehen.';
$string['coursemisconf'] = 'Kurs ist falsch konfiguriert';
$string['currentgrade'] = 'Aktuelle Note im Gradebook';
$string['custom_settings'] = 'benutzerdefinierte Einstellungen';
$string['cutoffdate'] = 'Letzter Abgabetermin';
$string['cutoffdate_help'] = 'Wenn aktiviert markiert dieses Datum das Ende der Abgabeperiode. Nach diesem Datum können Teilnehmer/innen nichts mehr abgeben. Wenn deaktivert können Teilnehmer/innen auch nach dem Abgabeende weiter Abgaben tätigen, welche als zu spät gekennzeichnet werden.';
$string['deleteallsubmissions'] = 'Alle Abgaben löschen';
$string['description'] = 'Beschreibung';
$string['downloadall'] = 'Alle Kreuzerlübungen als .zip herunterladen';
$string['due'] = 'Kreuzerlübungsabgabe';
$string['duedate'] = ' Abgabeende';
$string['duedate_help'] = 'Ende der nominellen Abgabeperiode. Nach diesem Datum ist es Teilnehmer/innen weiterhin möglich abzugeben, wobei ihre Abgaben als zu spät gekennzeichnet werden.';
$string['duedateno'] = 'Kein Abgabeende';
$string['early'] = 'Noch {$a}';
$string['editmysubmission'] = 'Abgabe bearbeiten';
$string['addsubmission'] = 'Abgabe hinzufügen';
$string['emailstudents'] = 'E-Mail Benachrichtigung an Teilnehmer/innen';
$string['emailteachermail'] = '{$a->username} hat eine Kreuzerlübungsabgabe für
 \'{$a->checkmark}\' am {$a->dayupdated} um {$a->timeupdated} aktualisiert.

Sie ist hier verfügbar:

    {$a->url}';
$string['emailteachermailhtml'] = '{$a->username} hat eine Kreuzerlübungsabgabe
für <i>\'{$a->checkmark}\' am {$a->dayupdated} um {$a->timeupdated}</i> aktualisiert<br /><br />
Sie ist <a href="{$a->url}">auf dieser Website verfügbar</a>.';
$string['emailteachers'] = 'E-Mail Benachrichtigung an Trainer/innen';
$string['emailteachers_help'] = 'Wenn aktiviert, bekommen Trainer/innen eine E-Mail-Benachrichtigung, sobald Teilnehmer/innen eine Kreuzerlübung abgeben bzw. ihre Kreuzerlübungsabgaben aktualisieren.

Nur Trainer/innen, die berechtigt sind, diese spezielle Kreuzerlübung zu bewerten, werden benachrichtigt. Zum Beispiel werden, wenn der Kurs separate Gruppen verwendet, Trainer/innen, die auf bestimmte Gruppen beschränkt sind, nur über Teilnehmer/innen in diesen Gruppen informiert, nicht jedoch über Teilnehmer/innen anderer Gruppen.';
$string['emptysubmission'] = 'Sie haben noch nichts abgegeben.';
$string['enablenotification'] = 'Sende Benachrichtigungen';
$string['enablenotification_help'] = 'Wenn aktiviert, werden Teilnehmer/innen benachrichtigt, sobald ihre Abgaben (oder zugehörige Tafelleistungen) bewertet oder ihre Anwesenheit gesetzt wurde.';
$string['errornosubmissions'] = 'Es sind keine Abgaben zum Download vorhanden';
$string['export'] = 'exportieren';
$string['exporttemplates'] = 'Vorlage';
$string['exporttemplates_help'] = 'Schneller Export mit Vorlagen<br />
<ul>
    <li><strong>benutzerdefinierte Einstellungen</strong><br />
        es wird kein Template verwendet, alle Einstellungen müssen manuell vorgenommen werden</li>
    <li><strong>Bewertungsliste einfach</strong><br />
        Hochformat, kleine Schrift, mit Kopf/Fußzeile, Namen einzeilig<br />
        enthält folgende Spalten: [Nachname, Vorname, Beispiele, Bewertung]</li>
    <li><strong>Bewertungsliste erweitert</strong><br />
        Querformat, kleine Schrift, mit Kopf/Fußzeile, Namen einzeilig<br />
        enthält folgende Spalten: [Nachname, Vorname, Matrikelnummer, Beispiele, Kreuzerl, Bewertung]</li>
    <li><strong>Unterschriftenliste einfach</strong><br />
        Hochformat, kleine Schrift, mit Kopf/Fußzeile, Namen einzeilig<br />
        enthält folgende Spalten: [Nachname, Vorname, Beispiele, Unterschrift]</li>
    <li><strong>Unterschriftenliste erweitert</strong><br />
        Querformat, kleine Schrift, mit Kopf/Fußzeile, Namen einzeilig<br />
        enthält folgende Spalten: [Nachname, Vorname, Matrikelnummer, Beispiele, Kreuzerl, Bewertung, Unterschrift]</li>
</ul>';
$string['exporttemplate_grades'] = 'Bewertungsliste einfach';
$string['exporttemplate_grades_extended'] = 'Bewertungsliste erweitert';
$string['exporttemplate_signature'] = 'Unterschriftenliste einfach';
$string['exporttemplate_signature_extended'] = 'Unterschriftenliste erweitert';
$string['failedupdatefeedback'] = 'Aktualisierung der Rückmeldung für Teilnehmer/in {$a} gescheitert';
$string['feedback'] = 'Rückmeldung';
$string['feedbackfromteacher'] = 'Rückmeldung von {$a}';
$string['feedbackupdated'] = 'Abgaberückmeldung für {$a} Personen aktualisiert';
$string['forcesinglelinenames'] = 'Erzwinge einzeilige Namen';
$string['forcesinglelinenames_help'] = '<p>Erzwingt die Darstellung der Namen in einer Zeile im PDF.</p>
<p><i>Achtung:</i> bei zu vielen Spalten oder wenn der Name zu lang ist, wird dieser gestaucht und kann unleserlich werden. Blenden Sie nicht benötigte Spalten aus um mehr Platz zu haben, falls dies passiert.</p>';
$string['graded'] = 'Benotet';
$string['guestnosubmit'] = 'Gästen ist es nicht erlaubt Kreuzerlübungen abzugeben. Sie müssen sich zuerst registrieren/einloggen, bevor Sie abgeben können.';
$string['guestnoupload'] = 'Gästen ist es nicht erlaubt hochzuladen';
$string['hideintro'] = 'Verstecke Beschreibung vor dem Verfügbarkeitsdatum';
$string['hideintro_help'] = 'Wenn aktiviert, wird die Kreuzerlübungsbeschreibung vor dem Verfügbarkeitsdatum versteckt. Nur der Kreuzerlübungsname wird dann angezeigt.';
$string['invalidcheckmark'] = 'Fehlerhafte Kreuzerlübung';
$string['invalidid'] = 'Kreuzerlübungs-ID war fehlerhaft';
$string['invaliduserid'] = 'Ungültige Teilnehmer/innen-ID';
$string['itemstocount'] = 'Zählen';
$string['lastgrade'] = 'Letzte Bewertung';
$string['late'] = '{$a} zu spät';
$string['manycolumnsinpdfwarning'] = 'Achtung: bei hoher Beispielanzahl kann aus Platzgründen kein vernünftiger Export als PDF mehr garantiert werden. Versuchen Sie unwichtige Spalten auszublenden oder verwenden sie stattdessen XLSX oder ODS Export und passen sie das Layout in Ihrem Tabellenkalkulationsprogramm an.';
$string['maximumgrade'] = 'Höchstbewertung';
$string['messageprovider:checkmark_updates'] = 'Kreuzerlübungsbenachrichtigungen';
$string['modulename'] = 'Kreuzerlübung';
$string['modulename_help'] = 'Kreuzerlübungen erlauben es Trainer/innen eine Aufgabe zu spezifizieren, in der Teilnehmer/innen Beispiele kreuzen müssen, die danach bewertet werden können.';
$string['modulenameplural'] = 'Kreuzerlübungen';
$string['newsubmissions'] = 'Kreuzerlübungen wurden eingereicht';
$string['nocheckmarks'] = 'Es ist derzeit keine Kreuzerlübung vorhanden';
$string['noattempts'] = 'Es wurde noch kein Versuch bei dieser Kreuzerlübung getätigt';
$string['nomoresubmissions'] = 'Es sind keine weiteren Abgaben erlaubt.';
$string['nonnegativeintrequired'] = 'Muss größer oder gleich null (>= 0) und ganzzahlig sein!';
$string['notavailableyet'] = 'Diese Kreuzerlübung ist leider noch nicht verfügbar.<br />Die Kreuzerlübungsanweisungen werden hier ab dem unten gegebenen Datum gezeigt.';
$string['notgradedyet'] = 'Noch nicht bewertet';
$string['norequiregrading'] = 'Es sind keine Kreuzerlübungen vorhanden, die eine Benotung erfordern';
$string['nosubmisson'] = 'Es wurden keine Kreuzerlübungen abgegeben';
$string['notsubmittedyet'] = 'Noch nichts abgegeben';
$string['notactive'] = 'Nicht aktiv';
$string['operation'] = 'Operation';
$string['optionalsettings'] = 'Optionale Einstellungen';
$string['page-mod-checkmark-x'] = 'Jede Kreuzerlübungsmodulseite';
$string['page-mod-checkmark-view'] = 'Kreuzerlübungsmodul Haupseite';
$string['page-mod-checkmark-submissions'] = 'Kreuzerlübungsmodul Abgabenseite';
$string['pagesize'] = 'Abgaben pro Seite';
$string['pagesize_help'] = 'Wenn in Ihrem Kurs sehr viele Teilnehmer/innen eingeschrieben sind, können Sie mittels der Einstellung "Optimal" die Aufteilung der Listeneinträge pro Seite entsprechend der gewählten Schriftgröße und Seitenausrichtung optimieren.';
$string['pdfpagesize'] = 'Abgaben pro Seite';
$string['pdfpagesize_help'] = 'Wenn in Ihrem Kurs sehr viele Teilnehmer/innen eingeschrieben sind, können Sie mittels der Einstellung "Optimal" die Aufteilung der Listeneinträge pro Seite entsprechend der gewählten Schriftgröße und Seitenausrichtung optimieren.';
$string['popupinnewwindow'] = 'Öffne Pop-up in neuem Fenster';
$string['posintrequired'] = 'Muss größer als 0 (> 0) und ganzzahlig sein!';
$string['posintst100required'] = 'Muss im Intervall [0,100] liegen (0 <= X <= 100) sowie ganzzahlig sein!';
$string['pluginadministration'] = 'Kreuzerlübungsverwaltung';
$string['pluginname'] = 'Kreuzerlübung';
$string['quickgrade'] = 'Schnelle Bewertung erlauben';
$string['quickgrade_help'] = 'Wenn aktiviert, können mehrere Abgaben auf einer Seite bewertet werden. Wählen Sie zuerst Ihre Bewertungen aus und fügen Sie Kommentare hinzu. Danach klicken Sie auf "Speichere all meine Rückmeldungen" um alle Änderungen zu speichern.';
$string['requiregrading'] = 'Bewertung erforderlich';
$string['reviewed'] = 'Angesehen';
$string['saveallfeedback'] = 'Speichere all meine Rückmeldungen';
$string['showrecentsubmissions'] = 'Zeige die letzten Abgaben';
$string['submission'] = 'Abgabe';
$string['submissionfeedback'] = 'Abgabekommentar';
$string['submissions'] = 'Abgaben';
$string['submissionsamount'] = '{$a->submitted} von {$a->total} Teilnehmer/innen haben abgegeben';
$string['submissionsaved'] = 'Ihre Änderungen wurden gespeichert';
$string['submissionsgraded'] = '{$a->graded} von {$a->all} Bewertungen erledigt';
$string['submissionsnotgraded'] = '{$a->reqgrading} von {$a->all} Bewertungen offen';
$string['submitcheckmark'] = 'Geben Sie Ihre Kreuzerlübung mit diesem Formular ab';
$string['submitted'] = 'Abgegeben';
$string['submitted_entries'] = 'Abgegebene';
$string['summary_abs'] = 'X/Y Beispiele gekreuzt';
$string['summary_rel'] = '% der Beispiele gekreuzt';
$string['usermisconf'] = 'Benutzer/in ist falsch konfiguriert';
$string['usernosubmit'] = 'Es ist Ihnen nicht erlaubt abzugeben.';
$string['viewfeedback'] = 'Zeige Kreuzerlübungsnote und Kommentar';
$string['viewmysubmission'] = 'Zeige meine Abgabe';
$string['viewsubmissions'] = 'Zeige {$a} abgegebene Kreuzerlübungen';
$string['yoursubmission'] = 'Ihre Abgabe';
/*
 * End of block with standard-strings
 */

// Deprecated since Moodle 3.1!
$string['autograde_all'] = 'Alle Abgaben bewerten';
$string['autograde_custom'] = 'Ausgewählte bewerten';
$string['autograde_no_users_selected'] = 'Sie haben keine Teilnehmer/in zur Bewertung ausgewählt. Wählen Sie die gewünschten Teilnehmer/innen über die Checkboxen der linken Tabellenspalte aus.';
$string['autograde_req'] = 'Nichtbewertete bewerten';
$string['autograde_str'] = 'Automatische Bewertung';
$string['autograde_str_help'] = 'Die automatische Bewertung berechnet die Noten der Abgaben anhand der Punkte der gekreuzten Beispiele.<br /><ul><li>Ausgewählte bewerten - hier werden all jene Datensätze bewertet, die links per Häckchen ausgewählt wurden. Falls noch keine Abgabe vorhanden ist, wird eine leere Abgabe hinzugefügt.</li><li>Nichtbewertete Bewerten - hier werden alle Abgaben bewertet, deren Abgabedatum aktueller als die letzte Bewertung ist</li><li>Alle Abgaben bewerten - hier werden alle bereits übermittelten Abgaben bewertet</li></ul><br />Die Punkteberechnung erfolgt aufgrund der eingestellten Beispielbewertungen sowie der angekreuzten Beispiele:<ul><li>Standard-Bewertungen: hier wird jedes Beispiel gleich gewichtet (ganzzahlige Punkteanzahl pro Beispiel). Die Bewertung ergibt sich aus der Summe der gekreuzten Beispiele multipliziert mit dem Quotienten aus Gesamtbewertung und Beispielanzahl.</li><li>individuelle Beispielgewichtungen: Für die Bewertungsberechnung wird für jedes gekreuzte Beispiel die dem Beispiel zugeordnete Punkteanzahl aufsummiert.</li></ul>';

// Deprecated since Moodle 3.3!
$string['end_of_submission_for'] = 'Ende des Abgabezeitraums von {$a}';
