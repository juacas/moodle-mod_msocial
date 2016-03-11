<?php
// This file is part of TwitterCount activity for Moodle http://moodle.org/
//
// Questournament for Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Questournament for Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with TwitterCount for Moodle.  If not, see <http://www.gnu.org/licenses/>.
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

$string['modulename'] = 'Concurso de posteo de Twitters';
$string['modulenameplural'] = 'Concursos de posteo de Twitters';
$string['modulename_help'] = 'La actividad TCount permite a los profesores definir una expresión de búsqueda en el timeline de twitter y pedir a los alumnos que publiquen mensajes con un determinado hashtag o términos.
El módulo busca periódicamente en segundo plano la actividad en twitter y hace una contabilidad de los Tweets, los Eetweets y FAVS recibidos por cada estudiante.
El módulo calcula una calificación mediante una fórmula definida por el profesor que combina estas estadísticas.
INSTRUCCIONES:
El profesor necesita tener una cuenta de Twitter y conectar la actividad con su usuario de Twitter.
Adicionalmente, el profesor puede insertar un Widget de twitter para mostrat el timeline en la página principal de la actividad.';
$string['pluginname'] = 'Twitter count module';

$string['fieldid'] = 'Campo que contiene el nombre de usuario de Tweeter';
$string['fieldid_help'] = 'Este campo del perfil del usuario debe contener el identificador que se usa en twitter.';

$string['hashtag'] = 'Hashtag que se va a buscar en los tweets.';
$string['hashtag_help'] = 'Puede ser cualquier expresión permitida por el API de Twitter. Se puede usar esta herramienta para componer la cadena de búsqueda avanzada: <a href="https://twitter.com/search-advanced">https://twitter.com/search-advanced</a>';
$string['widget_id'] = 'Widget id que se va a incluir en la página principal.';
$string['widget_id_help'] = 'Tweeter obliga a crear manualmente un widget de búsqueda en su página. Entre en su cuenta de Twitter y cree un widget. Copie y pegue el WidgetId creado (Sólo el número que aparece en el código). Puede crear los widgets en <a href="https://twitter.com/settings/widgets">Create and manage yout Twitter Widgets</a>';

$string['counttweetsfromdate'] = 'Momento en que se empiezan a contar los Tweets.';
$string['counttweetsfromdate_help'] = 'Los tweets previos a la fecha de inicio no se incluirán en las estadísticas.';
$string['counttweetstodate'] = 'Momento en que terminan el consurso.';
$string['counttweetstodate_help'] = 'Los tweets posteriores a la fecha de inicio no se incluirán en las estadísticas.';
$string['grade_expr'] = 'Formula para convertir las estadísticas en calificaciones.';
$string['grade_expr_help'] = 'La fórmula puede contener las siguiente variables que se calculan para cada usuario: favs, tweets, retweets, maxfavs, maxtweets, maxretweets y una variedad de funciones como max, min, sum, average, etc. El punto decimal es \'.\' y el separador de variables es \',\' Ejemplo: \'=max(favs,retweets,1.15)\' Las variables maxfavs, maxtweets y maxretweets contienen los valores máximos alcanzados entre todos los usuarios del concurso.';

$string['pluginadministration'] = 'Twitter conquest';
$string['harvest_tweets'] = 'Search Twitter timeline for student activity';
// MainPage.
$string['mainpage'] = 'Portada del concurso Twitter';
$string['mainpage_help'] = 'Portada del concurso Twitter. Puede comprobar aquí sus logros en el concurso Twitter';
$string['module_connected'] = 'Modulo conectado con Twitter con el usuario {$a} ';
$string['module_not_connected'] = 'Modulo no conectado con Twitter.';
$string['no_twitter_name_advice'] = 'No hay nombre de Twitter. Introducir en el campo \'{$a->field}\' del <a href="http://localhost/moodle2/user/edit.php?id={$a->userid}&course={$a->courseid}">perfil personal</a>';

// SETTINGS.
$string['tcount_oauth_access_token'] = 'oauth_access_token';
$string['config_oauth_access_token'] = 'oauth_access_token de acuerdo con TwitterAPI';
$string['tcount_oauth_access_token_secret'] = 'oauth_access_token_secret';
$string['config_oauth_access_token_secret'] = 'oauth_access_token_secret de acuerdo con TwitterAPI';
$string['tcount_consumer_key'] = 'consumer_key';
$string['config_consumer_key'] = 'consumer_key de acuerdo con TwitterAPI (<a href="https://apps.twitter.com" target="_blank" >https://apps.twitter.com</a>)';
$string['tcount_consumer_secret'] = 'consumer_secret';
$string['config_consumer_secret'] = 'consumer_secret de acuerdo con TwitterAPI (<a href="https://apps.twitter.com" target="_blank" >https://apps.twitter.com</a>)';
