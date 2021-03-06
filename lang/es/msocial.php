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
$string['anonymizeviews'] = 'Anonimiza los usuarios en las visualizaciones';
$string['anonymizeviews_help'] = 'Se ocultan los nombres reales en las vistas. ' .
                                'Los usuarios sin permisos específicos verán los nombres como "Anonymous-XX".';
$string['socialconnectors'] = 'Conectores a redes sociales.';
$string['socialviews'] = 'Visualizaciones de actividad.';
$string['modulename'] = 'Uso de redes sociales';
$string['modulenameplural'] = 'Usos de redes sociales';
$string['modulename_help'] = 'La actividad MSocial permite a los profesores definir una expresión de búsqueda en el ' .
'timeline de twitter, facebook, foros de Moodle y otras redes sociales y pedir a los alumnos que publiquen mensajes ' .
'con un determinado hashtag o término de búsqueda. ' .
'El módulo busca periódicamente en segundo plano la actividad en las redes sociales y hace una contabilidad de los ' .
'eventos recibidos por cada estudiante. ' .
'El módulo calcula una calificación mediante una fórmula definida por el profesor que combina estas estadísticas. ' .
'INSTRUCCIONES: ' .
'El profesor necesita tener una cuenta de cada red social que va a usar y conectar la actividad con su usuario de esa red social.';
$string['pluginname'] = 'Actividad en redes sociales';
$string['harvestedtimeago'] = 'Refrescado hace {$a->interval}';
$string['startdate'] = 'Comienzo de seguimiento MSocial.';
$string['startdate_help'] = 'Las interacciones previas a la fecha de inicio no se incluirán en las estadísticas.';
$string['enddate'] = 'Final de seguimiento MSocial.';
$string['enddate_help'] = 'Las interacciones posteriores a la fecha de inicio no se incluirán en las estadísticas.';
$string['enddate_error'] = 'La fecha de final de monitoriazación debe ser posterior a la de inicio de monitorización.';
$string['msocial:daterange'] = 'Rastreo de actividad desde {$a->startdate} hasta {$a->enddate}';
$string['grade_expr'] = 'Formula para convertir las estadísticas en calificaciones.';
$string['grade_expr_help'] = 'La fórmula puede contener diversas variables que se calculan para cada usuario y una variedad de funciones como max, min, sum, average, etc. El punto decimal es \'.\' y el separador de variables es \',\' Ejemplo: \'=max(favs,retweets,1.15)\' Las variables cuyo nombre empieza por max contienen los valores máximos alcanzados entre todos los usuarios del concurso.';
$string['pluginadministration'] = 'Redes sociales';
$string['recalc_kpis'] = 'Recalcular y limpiar registros';
$string['harvest_task'] = 'Planificador de recolección de interacciones sociales y análisis y cálculo de indicadores.';
// MainPage.
$string['mainpage'] = 'Portada del concurso Twitter';
$string['mainpage_help'] = 'Portada de redes sociales. Esta actividad registra periódicamente la actividad de todos en las redes sociales. ' .
        'Para poder leer los mensajes de Facebook, Twitter, Pinterest, Instagram, es necesario que sepamos qué usuario usa cada estudiante en esas redes. Para ello hay que pulsar en los enlaces y botones que aparecen en la cabecera.' .
        'Confidencialidad: No se guarda ninguna credencial personal, sólo el identificador del usuario. En la actividad recopilamos los mensajes públicos que se generan en las redes sociales de acuerdo a las instrucciones del profesor. Esos mensajes sólo se usan para hacer estadísticas y generar los análisis que se muestran en esta página.' .
        'Cada red social marca de una manera las publicaciones para que podamos identificarlas. En la cabecera aparecen qué hashtag, tablones, o grupos deben tener los mensajes de la actividad en cada red social.';
$string['mapunknownsocialusers'] = 'Seleccione un usuario del curso para asociarlo a la cuenta "{$a->link}" del {$a->source}';
// Filter form.
$string['collapse'] = 'Simplicar interacciones';
$string['datesrange'] = 'Rango de fechas';
$string['yesterday'] = 'Ayer';
$string['last7days'] = '7 últimos días';
$string['lastweekmosu'] = 'La semana pasada';
$string['monthtodate'] = 'Durante este mes';
$string['prevmonth'] = 'El mes pasado';
$string['yeartodate'] = 'Este año';
$string['fromactivitystart'] = 'Durante esta actividad';
$string['receivedbyteacher'] = 'Recibidas por profesores';
$string['pureexternal'] = 'Entre desconocidos';
$string['unknownusers'] = 'De desconocidos';
$string['fromidfilter'] = 'Autor:';
$string['posts'] = 'Posts';
$string['replies'] = 'Replies';
$string['reactions'] = 'Reacciones';
$string['mentions'] = 'Menciones';
$string['interactionstoshow'] = 'Mostrar interacciones de:';
$string['socialnetworktoshow'] = 'Mostrar las redes sociales:';
$string['unlinksocialaccount'] = 'Elimina la relación entre el estudiante y su cuenta de la red social';
$string['resetdone'] = '{$a} se ha reseteado';
$string['gradesdeleted'] = 'Calificaciones eliminadas';
$string['instancesreset'] = 'Se han reseteado las instancias de MSOCIAL y se han borrado los KPIs.';

// Permissions.
$string['msocial:view'] = 'Ver información básica del módulo MSocial.';
$string['msocial:viewothers'] = 'Ver la actividad de los otros usuarios.';
$string['msocial:alwaysviewothersnames'] = 'Ver los nombres completos de los usuarios aunque el módulo esté anonimizado.';
$string['msocial:addinstance'] = 'Añadir una nueva actividad MSocial al curso.';
$string['msocial:mapaccounts'] = 'Cambiar o asignar una cuenta de red social a un estudiante.';
$string['msocial:manage'] = 'Configurar la actividad MSocial';
$string['msocial:exportkpis'] = 'Descargar Key Performance Indicators (KPIs) calculados por MSocial';
$string['msocial:exportinteractions'] = 'Descargar las interacciones en bruto';