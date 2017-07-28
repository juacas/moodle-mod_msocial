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
$string['pluginname'] = 'Twitter connector for MSocial.';

$string['twfieldid'] = 'Campo que contiene el nombre de usuario de Tweeter';
$string['twfieldid_help'] = 'Este campo del perfil del usuario debe contener el identificador que se usa en twitter.';

$string['hashtag'] = 'Hashtag que se va a buscar en los tweets.';
$string['hashtag_help'] = 'Puede ser cualquier expresión permitida por el API de Twitter. Se puede usar esta herramienta para componer la cadena de búsqueda avanzada: <a href="https://twitter.com/search-advanced">https://twitter.com/search-advanced</a>';
$string['hashtag_missing'] = 'La cadena de búsqueda en twitter está vacía. Introduzcala en la configuración de la actividad.';
$string['hashtag_reminder'] = 'Se buscan tweets con el patrón: {$a}.';

$string['widget_id'] = 'Widget id que se va a incluir en la página principal.';
$string['widget_id_help'] = 'Tweeter obliga a crear manualmente un widget de búsqueda en su página. Entre en su cuenta de Twitter y cree un widget. Copie y pegue el WidgetId creado (Sólo el número que aparece en el código). Puede crear los widgets en <a href="https://twitter.com/settings/widgets">Create and manage yout Twitter Widgets</a>';

$string['pluginadministration'] = 'Twitter conquest';
$string['harvest_tweets'] = 'Search Twitter timeline for student activity';
// MainPage.
$string['mainpage'] = 'Portada del concurso Twitter';
$string['mainpage_help'] = 'Portada del concurso Twitter. Puede comprobar aquí sus logros en el concurso Twitter';
$string['module_connected_twitter'] = 'Modulo conectado con Twitter con el usuario "{$a}" ';
$string['module_not_connected_twitter'] = 'Modulo no conectado con Twitter.';
$string['no_twitter_name_advice'] = 'No hay nombre de Twitter. Introducir en el campo \'{$a->field}\' del <a href="../../user/edit.php?id={$a->userid}&course={$a->courseid}">perfil personal</a>';
$string['no_twitter_name_advice2'] = 'No hay nombre de Twitter. Introducir en el campo \'{$a->field}\' del <a href="../../user/edit.php?id={$a->userid}&course={$a->courseid}">perfil personal</a> o mediante <a href="{$a}"><img src="connector/twitter/pix/sign-in-with-twitter-gray.png" alt="Twitter login"/></a>';

// SETTINGS.
$string['msocial_oauth_access_token'] = 'oauth_access_token';
$string['config_oauth_access_token'] = 'oauth_access_token de acuerdo con TwitterAPI';
$string['msocial_oauth_access_token_secret'] = 'oauth_access_token_secret';
$string['config_oauth_access_token_secret'] = 'oauth_access_token_secret de acuerdo con TwitterAPI';
$string['msocial_consumer_key'] = 'consumer_key';
$string['config_consumer_key'] = 'consumer_key de acuerdo con TwitterAPI (<a href="https://apps.twitter.com" target="_blank" >https://apps.twitter.com</a>)';
$string['msocial_consumer_secret'] = 'consumer_secret';
$string['config_consumer_secret'] = 'consumer_secret de acuerdo con TwitterAPI (<a href="https://apps.twitter.com" target="_blank" >https://apps.twitter.com</a>)';
$string['problemwithtwitteraccount'] = 'Los últimos intentos de obtener los Tweets dieron un error. Intenta reconectar Twitter con tu usuario. Mensaje: {$a}';
$string['problemwithfacebookaccount'] = 'Los últimos intentos de obtener los Posts dieron un error. Intenta reconectar Facebook con tu usuario. Mensaje: {$a}';