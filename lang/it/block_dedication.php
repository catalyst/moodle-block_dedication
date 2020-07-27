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

/**
 * Strings for component 'block_dedication', language 'it', branch 'MOODLE_33_STABLE'
 *
 * @package   block_dedication
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['access_button'] = 'Vai allo strumento';
$string['access_info'] = 'Solo per docenti:';
$string['connectionratiorow'] = 'Connessioni al giorno';
$string['dedication:addinstance'] = 'Aggiungere blocco Tempo dedicato al corso';
$string['dedicationall'] = 'Tempo dedicato al corso da tutti i partecipanti. Seleziona un utente per vedere il dettaglio del suo tempo dedicato al corso.';
$string['dedication_estimation'] = 'Il tempo stimato che hai dedicato al corso è';
$string['dedicationgroup'] = 'Tempo dedicato al corso da tutti i membri del gruppo <em>{$a}</em>. Seleziona un membro per vedere il dettaglio del suo tempo dedicato al corso.';
$string['dedicationrow'] = 'Tempo dedicato al corso';
$string['dedication:use'] = 'Utilizzare blocco Tempo dedicato al corso';
$string['form'] = 'Configurazione blocco Tempo dedicato al corso';
$string['form_help'] = 'Il tempo viene stimato in base ai concetti di sessione e di durata della sessione applicata ai log nel corso.

<strong>Click:</strong> ogni volta che un utente accede a una pagina di Moodle viene registrata una linea di log.

<strong>Sessione:</strong> un insieme di due o più click tra i quali il tempo trascorso non eccede il tempo limite stabilito

<strong>Durata della sessione:</strong> il tempo trascorso tra il primo e l\'ultimo click della sessione.

<strong>Tempo dedicato al corso:</strong> la somma della durata di tutte le sessioni di un utente.';
$string['form_text'] = 'Seleziona l\'intervallo delle date e definisci il tempo massimo che deve intercorrere tra i click della stessa sessione.';
$string['limit'] = 'Tempo limite tra i click (in minuti)';
$string['limit_help'] = 'Il tempo limite tra i click definisce se i click fanno parte o meno della stessa sessione';
$string['maxtime'] = 'Fine del periodo';
$string['maxtime_help'] = 'Considera soltanto i log precedenti a questa data';
$string['meandedication'] = '<strong>Media del tempo dedicato:</strong> {$a}';
$string['mintime'] = 'Inizio del periodo';
$string['mintime_help'] = 'Considera soltanto i log successivi a questa data';
$string['pagetitle'] = '{$a}: tempo dedicato';
$string['period'] = 'Periodo da <em>{$a->mintime}</em> a <em>{$a->maxtime}</em>';
$string['perioddiff'] = '<strong>Tempo trascorso:</strong>  {$a}';
$string['perioddiffrow'] = 'Tempo trascorso';
$string['pluginname'] = 'Tempo dedicato al corso';
$string['sessionduration'] = 'Durata';
$string['sessionstart'] = 'Inizio sessione';
$string['show_dedication'] = 'Visualizza agli studenti il tempo dedicato al corso';
$string['show_dedication_help'] = 'Di default, il tempo dedicato al corso può essere visualizzato soltanto dai docenti. Questa impostazione consente agli studenti di visualizzare nel blocco il proprio tempo dedicato al corso.';
$string['sincerow'] = 'Da';
$string['submit'] = 'Calcola';
$string['torow'] = 'A';
$string['totaldedication'] = '<strong>Tempo dedicato totale:</strong> {$a}';
$string['userdedication'] = 'Dettaglio del tempo dedicato di <em>{$a}</em>.';
$string['endtime'] = 'Visualizza la fine della sessione';
$string['sessionend'] = 'Fine sessione';

