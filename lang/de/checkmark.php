<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
defined('MOODLE_INTERNAL') || die;

/**
 * Strings for component 'mod_checkmark', language 'de', branch 'MOODLE_21_STABLE'
 *
 * @package       mod
 * @subpackage    checkmark
 * @author        Philipp Hager
 * @copyright     2011 Philipp Hager
 * @since         Moodle 2.1
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/*
 * Block for checkmark-specific strings
 */
$string['activateindividuals'] = 'Individualfunktionen aktivieren';
$string['strassignment'] = 'Aufgabe';
$string['autograde_all'] = 'Alle Abgaben bewerten';
$string['autograde_custom'] = 'Ausgewählte bewerten';
$string['autograde_req'] = 'Nichtbewertete bewerten';
$string['autograde_str'] = 'Automatische Bewertung';
$string['autograde_str_help'] = 'Die automatische Bewertung berechnet die Noten der Abgaben anhand der Punkte der gekreuzten Beispiele.<br />'.
                                '<ul><li>Alle Abgaben bewerten - hier werden alle bereits übermittelten Abgaben bewertet</li>'.
                                    '<li>Nichtbewertete Bewerten - hier werden alle Abgaben bewertet, deren Abgabedatum aktueller als die letzte Bewertung ist</li>'.
                                    '<li>Ausgewählte bewerten - hier werden all jene Datensätze bewertet, die links per Häckchen ausgewählt wurden. Falls noch keine Abgabe vorhanden ist,'.
                                        ' wird eine leere Abgabe hinzugefügt.</li></ul><br />'.
                                    'Die Punkteberechnung erfolgt aufgrund der eingestellten Beispielbewertungen sowie der angekreuzten Beispiele:'.
                                    '<ul><li>Standard-Bewertungen: hier wird jedes Beispiel gleich gewichtet (ganzzahlige Punkteanzahl pro Beispiel).'.
                                            ' Die Bewertung ergibt sich aus der Summe der gekreuzten Beispiele multipliziert mit dem Quotienten aus Gesamtbewertung und Beispielanzahl.</li>'.
                                        '<li>individuelle Beispielgewichtungen: Für die Bewertungsberechnung wird für jedes gekreuzte Beispiel die dem Beispiel zugeordnete Punkteanzahl aufsummiert.</li></ul>';
$string['autograde_strall'] = 'Alle Abgaben';
$string['autograde_strreq'] = 'Abgaben, die ein Bewertungsupdate benötigen,';
$string['autograde_stronesubmission'] = '1 Abgabe';
$string['autograde_strmultiplesubmissions'] = '{$a} Abgaben';
$string['autogradebuttonstitle'] = 'Berechne Abgaben-Bewertungen f&uuml;r Kreuzerlübung {$a}:';
$string['autograde_confirm'] = 'Automatische Bewertung erfolgreich!';
$string['autograde_error'] = 'Ein Fehler ist während der automatischen Bewertung aufgetreten!';
$string['autograde_failed'] = 'Automatische Bewertung fehlgeschlagen!';
$string['autograde_success'] = 'Automatische Bewertung erfolgreich! {$a} Abgabenbewertungen wurden aktualisiert.';
$string['autograde_confirm'] = 'Sie sind dabei {$a} automatisch zu bewerten. Die bisherigen Bewertungen werden überschrieben! Sind Sie SICHER, dass Sie fortfahren wollen?';
$string['autograde_notsupported'] = 'Diese Notenskala wird von der automatischen Bewertung nicht unterstützt.';
$string['checkmark_overviewsummary'] = 'Sie haben {$a->checked_examples} / {$a->max_checked_examples} Beispiele ({$a->checked_grades} / {$a->max_checked_grades} Punkte) gekreuzt<br />{$a->grade}<br />';
$string['checkmark_summary'] = 'Sie haben <span id="examples">{$a->checked}</span> von {$a->total} Beispielen gekreuzt.<br />(<span id="grade">{$a->checkedgrade}</span> von maximal {$a->maxgrade} Punkten)';
$string['checkmarkstatstitle'] = 'Kursübersicht Kreuzerlübungen';
$string['checkmark:view_preview'] = 'Vorschau ansehen';
$string['count_individuals_mismatch'] = 'Die Anzahl der Beispielnamen({$a->namecount}) stimmt nicht mit der Anzahl der Beispielgewichtungen({$a->gradecount}) überein!';
$string['datasettingstitle'] = 'Dateneinstellungen';
$string['data_settings'] = 'Druckeinstellungen';
$string['data_preview_help'] = 'Klicken Sie in der Vorschau auf [+] und [-] um die zu druckenden Spalten ein bzw. auszuschalten!';
$string['data_preview'] = 'Datenvorschau';
$string['element_disabled'] = 'Folgendes Element ist gesperrt, da bereits Abgaben getätigt wurden.';
$string['elements_disabled'] = 'Folgende Elemente sind gesperrt, da bereits Abgaben getätigt wurden.';
$string['examplegrades'] = 'Beispielgewichtungen';
$string['examplegrades_help'] = 'Definiert die Wertigkeiten der einzelnen Beispiele. Diese werden mit einem Trennzeichen getrennt, welches in Klammern angegeben ist.';
$string['examplenames'] = 'Beispielnamen';
$string['examplenames_help'] = 'Definiert die Namen der einzelnen Beispiele. Diese werden mit einem Trennzeichen getrennt, welches in Klammern angegeben ist.';
$string['example_preview_title'] = 'Beispielvorschau';
$string['example_preview_title_help'] = 'Sie sehen hier als Vorschau eine leere Abgabe.';
$string['firstexamplenumber'] = 'Startnummer';
$string['firstexamplenumber_help'] = 'Nummer des ersten Beispieles. Die restlichen Beispiele werden laufend hochgezählt.';
$string['flexiblenaming'] = 'individuelle Namen/Punkte';
$string['flexiblenaming_help'] = 'Wenn aktiviert, werden statt fortlaufend hochgezählten Beispielen, die im Textfeld angegebenen Bezeichnungen verwendet. Weiters werden die Punkte für einzelne Beispiele im darunter liegenden Textfeld angegeben. Die Namen und erreichbaren Punkte der Beispiele werden mittels Komma (",") getrennt.';
$string['gradesum_mismatch'] = 'Die Summe der Beispielpunkte stimmt nicht mit der Punkteanzahl der Aufgabe überein ({$a->gradesum}/{$a->maxgrade})!';
$string['grade_mismatch'] = 'Die Punkte müssen ein ganzzahliges Vielfaches der Beispielanzahl sein!';
$string['grade'] = 'Bewertung';
$string['grade_help'] = 'Bei aktivierten Individualfunktionen muss die Gesamtbewertung gleich der Summe der Einzelbewertungen der Beispiele sein (max 100).<br />Bei Standardbeispielen ist jedes ganzzahlige Vielfache der Beispielanzahl zulässig. Die Punkte pro Beispiel werden dann in der Modulinstanz automatisch angepasst.<br />Bei aktiviertem Javascript wird die Punktesumme automatisch ausgewählt bzw. nur ganzzahlige Vielfache der Beispielanzahl akzeptiert.';
$string['nostudents'] = 'Es sind keine eingeschriebenen Studierende zur Anzeige vorhanden!';
$string['nostudentsmatching'] = 'Es sind keine Benutzer/innen vorhanden, die den aktuellen Einstellungen entsprechen!';
$string['nosubmission'] = 'Es ist noch keine Abgabe vorhanden.';
$string['nousers'] = 'Es sind keine Benutzer/innen zur Anzeige vorhanden!';
$string['numberofexamples'] = 'Beispielanzahl';
$string['numberofexamples_help'] = 'Anzahl der Beispiele';
$string['optimum'] = 'optimal';
$string['printsettingstitle'] = 'Druckeinstellungen';
$string['strallononepage'] = 'alles auf eine Seite';
$string['strautograded'] = '[automatisch bewertet]';
$string['strexample'] = 'Beispiel';
$string['strexamples'] = 'Beispiele';
$string['strlandscape'] = 'Querformat';
$string['strlarge'] = 'groß';
$string['strmedium'] = 'mittel';
$string['strpageorientation'] = 'Seitenausrichtung';
$string['strpapersizes'] = 'Papierformat';
$string['strportrait'] = 'Hochformat';
$string['strpoint'] = 'Punkt';
$string['strpoints'] = 'Punkte';
$string['strprint'] = 'PDF erstellen';
$string['strprintheader'] = 'Kopf-/Fußzeile';
$string['strprintheader_help'] = " drucke Kopfzeile wenn angehakt";
$string['strprintheaderlong'] = 'inkludiere Kopf-/Fußzeilen';
$string['strprintpreview'] = 'Druckvorschau';
$string['strprintpreviewtabalt'] = 'öffne Druckansicht';
$string['strrefreshdata'] = 'Vorschau aktualisieren';
$string['strsmall'] = 'klein';
$string['strstdexamplecount'] = 'Beispielanzahl';
$string['strstdexamplecountdesc'] = 'ist die Anzahl der Beispiele, die standardmäßig eingestellt sind';
$string['strstdexamplestart'] = 'Beispielanfangsnummer';
$string['strstdexamplestartdesc'] = 'ist die standardmäßige Nummer des ersten Beispiels';
$string['strstdnames'] = 'Individuelle Namen';
$string['strstdnamesdesc'] = 'Beispielnamen, wenn "Individuelle Namen" aktiviert ist';
$string['strstdgrades'] = 'Standardbewertungen';
$string['strstdgradesdesc'] = 'Beispielgewichtungen, wenn individuelle Namen aktiviert ist';
$string['strsubmissions'] = 'Abgaben';
$string['strsubmissionstabalt'] = 'öffne Abgabenansicht';
$string['strsum'] = 'Summe';
$string['strtextsize'] = 'Textgröße';
$string['strvalidmsgtime'] = 'Gültigkeitsdauer Benachrichtigung';
$string['strvalidmsgtimedesc'] = 'Anzahl der Tage, nach denen Benotungsbenachrichtigungen nicht mehr versandt werden';
$string['str_user_id'] = 'Matrikelnummer';
/*
 * End of Block for checkmark-specific strings
 */


/*
 * Block with standard-checkmark-strings (adopted to checkmark)
 */
$string['allowresubmit'] = 'Erneute Abgabe erlauben';
$string['allowresubmit_help'] = 'Wenn aktiviert, ist es Studierenden erlaubt, nach der Benotung erneut (für eine erneute Benotung) abzugeben.';
$string['alreadygraded'] = 'Ihre Abgabe wurde bereits benotet und erneutes Abgeben ist daher nicht erlaubt.';
$string['checkmarkdetails'] = 'Kreuzerlübungsdetails';
$string['checkmark:grade'] = 'Kreuzerlübung benoten';
$string['checkmarkmail'] = '{$a->teacher} hat eine Rückmeldung bezüglich ihrer
Kreuzerlübungsabgabe für \'{$a->checkmark}\' gepostet.

Sie können sie als Anhang an Ihre Abgabe einsehen:

    {$a->url}';
$string['checkmarkmailhtml'] = '{$a->teacher} hat eine Rückmeldung zu ihrer
Kreuzerlübungsabgabe für \'<i>{$a->checkmark}</i>\' gegeben.<br /><br />
Sie können sie als Anhang an ihre <a href="{$a->url}">Abgabe</a> einsehen.';
$string['checkmarkmailsmall'] = '{$a->teacher} hat eine Rückmeldung zu ihrer
Kreuzerlübungsabgabe für \'{$a->checkmark}\' gegeben. Sie können sie als Anhang an ihre Abgabe einsehen.';
$string['checkmarkname'] = 'Kreuzerlübung';
$string['checkmark:submit'] = 'Kreuzerlübung abgeben';
$string['checkmarksubmission'] = 'Kreuzerlübungsabgaben';
$string['checkmark:view'] = 'Zeige Kreuzerlübung';
$string['availabledate'] = 'Verfügbar von';
$string['cannotviewcheckmark'] = 'Sie können diese Kreuzerlübung nicht betrachten';
$string['comment'] = 'Kommentar';
$string['configshowrecentsubmissions'] = 'Jeder kann Benachrichtigungen über Abgaben in den letzen Aktivitäten sehen.';
$string['coursemisconf'] = 'Kurs ist falsch konfiguriert';
$string['currentgrade'] = 'Aktuelle Note im Gradebook';
$string['deleteallsubmissions'] = 'Alle Abgaben löschen';
$string['description'] = 'Beschreibung';
$string['downloadall'] = 'Alle Kreuzerlübungen als .zip herunterladen';
$string['due'] = 'Kreuzerlübungsabgabe';
$string['duedate'] = 'Abgabedatum';
$string['duedateno'] = 'Kein Abgabedatum';
$string['early'] = 'noch {$a}';
$string['editmysubmission'] = 'Abgabe bearbeiten';
$string['addsubmission'] = 'Abgabe hinzufügen';
$string['emailstudents'] = 'E-Mail Benachrichtigung an Studierende';
$string['emailteachermail'] = '{$a->username} hat eine Kreuzerlübungsabgabe für
 \'{$a->checkmark}\' um {$a->timeupdated} aktualisiert.

Sie ist hier verfügbar:

    {$a->url}';
$string['emailteachermailhtml'] = '{$a->username} hat eine Kreuzerlübungsabgabe
für <i>\'{$a->checkmark}\'  um {$a->timeupdated}</i>  aktualisiert<br /><br />
Sie ist <a href="{$a->url}">auf dieser Website verfügbar</a>.';
$string['emailteachers'] = 'E-Mail Benachrichtigung an Lehrende';
$string['emailteachers_help'] = 'Wenn aktiviert, bekommen Lehrende eine E-Mail-Benachrichtigung, sobald Studierende eine Kreuzerlübung abgeben bzw. ihre Kreuzerlübungsabgaben aktualisieren.

Nur Lehrende, die berechtigt sind, diese spezielle Kreuzerlübung zu bewerten, werden benachrichtigt. Zum Beispiel werden, wenn der Kurs separate Gruppen verwendet, Lehrende, die auf bestimmte Gruppen beschränkt sind, nur über Studierende in diesen Gruppen informiert, nicht jedoch über Studierende anderer Gruppen.';
$string['emptysubmission'] = 'Sie haben noch nichts abgegeben.';
$string['enablenotification'] = 'Sende Benachrichtigungen';
$string['enablenotification_help'] = 'Wenn aktiviert, werden Studierende benachrichtigt, sobald Ihre Abgaben bewertet wurden.';
$string['errornosubmissions'] = 'Es sind keine Abgaben zum Download vorhanden';
$string['failedupdatefeedback'] = 'Aktualisierung der Rückmeldung für Benutzer/in {$a} gescheitert';
$string['feedback'] = 'Rückmeldung';
$string['feedbackfromteacher'] = 'Rückmeldung von {$a}';
$string['feedbackupdated'] = 'Abgaberückmeldung für {$a} Personen aktualisiert';
$string['graded'] = 'Benotet';
$string['guestnosubmit'] = 'Gästen ist es nicht erlaubt Kreuzerlübungen abzugeben. Sie müssen sich zuerst registrieren/einloggen, bevor Sie abgeben können.';
$string['guestnoupload'] = 'Gästen ist es nicht erlaubt hochzuladen';
$string['hideintro'] = 'Verstecke Beschreibung vor dem Verfügbarkeitsdatum';
$string['hideintro_help'] = 'Wenn aktiviert, wird die Kreuzerlübungsbeschreibung vor dem Verfügbarkeitsdatum versteckt. Nur der Kreuzerlübungsname wird dann angezeigt.';
$string['invalidcheckmark'] = 'Fehlerhafte Kreuzerlübung';
$string['invalidid'] = 'Kreuzerlübungs-ID war fehlerhaft';
$string['invaliduserid'] = 'Ungültige Benutzer/innen-ID';
$string['itemstocount'] = 'Zählen';
$string['lastgrade'] = 'Letzte Bewertung';
$string['late'] = '{$a} zu spät';
$string['maximumgrade'] = 'Höchstbewertung';
$string['messageprovider:checkmark_updates'] = 'Kreuzerlübungsbenachrichtigungen';
$string['modulename'] = 'Kreuzerlübung';
$string['modulename_help'] = 'Kreuzerlübungen erlauben es dem Lehrenden eine Aufgabe zu spezifizieren, in der Studierende Beispiele kreuzen müssen, die danach bewertet werden können.';
$string['modulenameplural'] = 'Kreuzerlübungen';
$string['newsubmissions'] = 'Kreuzerlübungen wurden eingereicht';
$string['nocheckmarks'] = 'Es ist derzeit keine Kreuzerlübung vorhanden';
$string['noattempts'] = 'Es wurde noch kein Versuch bei dieser Kreuzerlübung getätigt';
$string['nomoresubmissions'] = 'Es sind keine weiteren Abgaben erlaubt.';
$string['nonnegativeintrequired'] = 'Muss größer oder gleich null (>= 0) und ganzzahlig sein.';
$string['notavailableyet'] = 'Diese Kreuzerlübung ist leider noch nicht verfügbar.<br />Die Kreuzerlübungsanweisungen werden hier ab dem unten gegebenen Datum gezeigt.';
$string['notgradedyet'] = 'Noch nicht bewertet';
$string['norequiregrading'] = 'Es sind keine Kreuzerlübungen vorhanden, die eine Benotung erfordern';
$string['nosubmisson'] = 'Es wurden keine Kreuzerlübungen abgegeben';
$string['notsubmittedyet'] = 'Noch nichts abgegeben';
$string['operation'] = 'Operation';
$string['optionalsettings'] = 'Optionale Einstellungen';
$string['page-mod-checkmark-x'] = 'Jede Kreuzerlübungsmodulseite';
$string['page-mod-checkmark-view'] = 'Kreuzerlübungsmodul Haupseite';
$string['page-mod-checkmark-submissions'] = 'Kreuzerlübungsmodul Abgabenseite';
$string['pagesize'] = 'Abgaben pro Seite';
$string['popupinnewwindow'] = 'Öffne Pop-up in neuem Fenster';
$string['posintrequired'] = 'Muss größer als 0 (> 0) und ganzzahlig sein';
$string['pluginadministration'] = 'Kreuzerlübungsverwaltung';
$string['pluginname'] = 'Kreuzerlübung';
$string['preventlate'] = 'Verspätete Abgaben verhindern';
$string['quickgrade'] = 'Schnelle Bewertung erlauben';
$string['quickgrade_help'] = 'Wenn aktiviert, können mehrere Abgaben auf einer Seite bewertet werden. Wählen Sie zuerst Ihre Bewertungen aus und fügen Sie Kommentare hinzu. Danach klicken Sie auf "Speichere all meine Rückmeldungen" um alle Änderungen zu speichern.';
$string['requiregrading'] = 'Bewertung erforderlich';
$string['reviewed'] = 'Angesehen';
$string['saveallfeedback'] = 'Speichere all meine Rückmeldungen';
$string['showrecentsubmissions'] = 'Zeige die letzten Abgaben';
$string['submission'] = 'Abgabe';
$string['submissionfeedback'] = 'Abgabekommentar';
$string['submissions'] = 'Abgaben';
$string['submissionsamount'] = '{$a->submitted} von {$a->total} Studierenden haben abgegeben';
$string['submissionsaved'] = 'Ihre Änderungen wurden gespeichert';
$string['submissionsgraded'] = '{$a->graded} von {$a->all} Bewertungen erledigt';
$string['submissionsnotgraded'] = '{$a->reqgrading} von {$a->all} Bewertungen offen';
$string['submitcheckmark'] = 'Geben Sie Ihre Kreuzerlübung mit diesem Formular ab';
$string['submitted'] = 'Abgegeben';
$string['usermisconf'] = 'Benutzer/in ist falsch konfiguriert';
$string['usernosubmit'] = 'Es ist Ihnen nicht erlaubt abzugeben.';
$string['viewfeedback'] = 'Zeige Kreuzerlübungsnote und Kommentar';
$string['viewmysubmission'] = 'Zeige meine Abgabe';
$string['viewsubmissions'] = 'Zeige {$a} abgegebene Kreuzerlübungen';
$string['yoursubmission'] = 'Ihre Abgabe';
/*
 * End of block with standard-strings
 */
