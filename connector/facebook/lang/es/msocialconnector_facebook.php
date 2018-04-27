<?php
// This file is part of FacebookCount activity for Moodle http://moodle.org/
//
// MSocial for Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// MSocial for Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.
/* ***************************
 * Module developed at the University of Valladolid
 * Designed and directed by Juan Pablo de Castro at telecommunication engineering school
 * Copyright 2017 onwards EdUVaLab http://www.eduvalab.uva.es
 * @author Juan Pablo de Castro
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package msocial
 * *******************************************************************************
 */
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
$string['pluginname'] = 'Conector para Facebook';
$string['fbgroup'] = 'Grupo de Facebook que se analizará';
$string['connectgroupinpage'] = 'Puede seleccionar los grupos a analizar en la página principal de la actividad';
$string['fbsearch'] = 'Search string to search for in Facebook';
$string['fbsearch_help'] = 'It can be any string as specified in Facebook search API. You can use this reference to find out how to compose your search string <a href="https://developers.facebook.com/docs/graph-api/using-graph-api/v1.0#searchtypes">https://developers.facebook.com/docs/graph-api/using-graph-api/v1.0#searchtypes</a>';
$string['harvest'] = 'Analizar los grupos de Facebook';

$string['no_facebook_name_advice'] = 'Desconectado de Facebook.';
$string['no_facebook_name_advice2'] = 'No conocemos la identidad de {$a->userfullname} en Facebook. Registrar pulsando en <a href="{$a->url}"><img src="{$a->pixurl}/loginwithfacebook.png" alt="Facebook login"/></a>';

$string['module_connected_facebook'] = 'Actividad conectada con Facebook con el usuario "{$a}" ';
$string['module_not_connected_facebook'] = 'Actividad desconectada de Facebook. No funcionará hasta que se conecte con un usuario de Facebook';
$string['selectthisgroup'] = 'Usar este grupo';

// SETTINGS.
$string['facebook_app_id'] = 'app_id';
$string['config_app_id'] = 'app_id según se indica en el FacebookAPI (<a href="https://developers.facebook.com/docs/apps/register" target="_blank" >https://developers.facebook.com/docs/apps/register</a>)';
$string['facebook_app_secret'] = 'app_secret';
$string['config_app_secret'] = 'app_secret según se indica en el FacebookAPI (<a href="https://developers.facebook.com/docs/apps/register" target="_blank" >https://developers.facebook.com/docs/apps/register</a>)';
$string['problemwithfacebookaccount'] = 'Los últimos intentos de cargar mensajes de Facebook generaron un error. Intente reconectar la actividad con Facebook con su usuario. Mensaje: {$a}';

$string['kpi_description_posts'] = 'Mensajes publicados en tablones y respuestas';
$string['kpi_description_replies'] = 'Comentarios recibidos';
$string['kpi_description_reactions'] = 'Reacciones recibidas (LIKE, LOVE, WOW, HAHA, SAD, ANGRY, THANKFUL)';
$string['kpi_description_likes'] = 'LIKEs recibidos';
$string['kpi_description_dislikes'] = 'Dis-LIKEs recibidos. (Reacciones de Tristeza SAD o Enfado ANGRY.)';