<?php
// This file is part of MSocial activity for Moodle http://moodle.org/
//
// MSocial for Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// MSocial for Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with MSocial for Moodle.  If not, see <http://www.gnu.org/licenses/>.

$string['pluginname'] = 'Conector para Twitter';

$string['hashtag'] = 'Expresión de búsqueda (Hashtags) para buscar tweets';
$string['hashtag_help'] = 'Puede ser una lista de tags combinados con AND y OR. OR tiene precedencia por la izquierda. Ejemplo:"#uva AND #msocial OR #m_social"';

$string['hashtag_missing'] = 'No hay una cadena de búsqueda de Hashtags. Configúrelo en la <a href="../../course/modedit.php?update={$a->cmid}&return=1">configuración de la actividad</a>.';
$string['hashtag_reminder'] = 'Se busca en Twitter con el filtro: <a href="https://twitter.com/search?q={$a->hashtagscaped}">{$a->hashtag}</a>';

$string['widget_id'] = 'Widget id to be embedded in the main page.';
$string['widget_id_help'] = 'weeter API forces to create mannually a search widget in your twitter account to be embedded in any page. Create one and copy and paste the WidgetId created. You can create the widgets at <a href="https://twitter.com/settings/widgets">Create and manage yout Twitter Widgets</a>';


$string['harvest_tweets'] = 'Buscar en el "timeline" de Twitter la actividad de los estudiantes';
// MainPage.
$string['module_connected_twitter'] = 'Actividad conectada a Twitter con el usuario "{$a}" ';
$string['module_not_connected_twitter'] = 'Actividad desconectada de Twitter. No se buscarán "tweets" hasta que se reconecte.';
$string['no_twitter_name_advice'] = 'No hay un nombre de Twitter. </a>';
$string['no_twitter_name_advice2'] = 'No se conoce la identidad de {$a->userfullname} en Twitter. Identifíquese con Twitter en <a href="{$a->url}"><img src="{$a->pixurl}/sign-in-with-twitter-gray.png" alt="Twitter login"/></a>';


// SETTINGS.
$string['msocial_oauth_access_token'] = 'oauth_access_token';
$string['config_oauth_access_token'] = 'oauth_access_token de acuerdo con TwitterAPI';
$string['msocial_oauth_access_token_secret'] = 'oauth_access_token_secret';
$string['config_oauth_access_token_secret'] = 'oauth_access_token_secret de acuerdo con TwitterAPI';
$string['msocial_consumer_key'] = 'consumer_key';
$string['config_consumer_key'] = 'consumer_key according to TwitterAPI (<a href="https://apps.twitter.com" target="_blank" >https://apps.twitter.com</a>)';
$string['msocial_consumer_secret'] = 'consumer_secret';
$string['config_consumer_secret'] = 'consumer_secret according to TwitterAPI (<a href="https://apps.twitter.com" target="_blank" >https://apps.twitter.com</a>)';
$string['problemwithtwitteraccount'] = 'Los últimos intentos de obtener Tweets provocaron errores. Intente reconectar con Twitter con su cuenta. Mensajes: {$a}';

$string['pki_description_tweets'] = 'Tweets publicados';
$string['pki_description_twmentions'] = 'Menciones recibidas';
$string['pki_description_favs'] = 'Marcas "Favoritas" recibidas';
$string['pki_description_retweets'] = 'Retweets obtenidos';
