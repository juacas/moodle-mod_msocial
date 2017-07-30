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
$string['modulename'] = 'Uso de redes sociales';
$string['modulenameplural'] = 'Usos de redes sociales';
$string['modulename_help'] = 'La actividad MSocial permite a los profesores definir una expresión de búsqueda en el
timeline de twitter, facebook, foros de Moodle y otras redes sociales y pedir a los alumnos que publiquen mensajes
con un determinado hashtag o término de búsqueda.
El módulo busca periódicamente en segundo plano la actividad en las redes sociales y hace una contabilidad de los
eventos recibidos por cada estudiante.
El módulo calcula una calificación mediante una fórmula definida por el profesor que combina estas estadísticas.
INSTRUCCIONES:
El profesor necesita tener una cuenta de cada red social que va a usar y conectar la actividad con su usuario de esa red social.';
$string['pluginname'] = 'Social activity count module';

$string['startdate'] = 'Momento en que se empiezan a contar las interacciones.';
$string['startdate_help'] = 'Las interacciones previas a la fecha de inicio no se incluirán en las estadísticas.';
$string['enddate'] = 'Momento en que terminan el consurso.';
$string['enddate_help'] = 'Las interacciones posteriores a la fecha de inicio no se incluirán en las estadísticas.';
$string['grade_expr'] = 'Formula para convertir las estadísticas en calificaciones.';
$string['grade_expr_help'] = 'La fórmula puede contener diversas variables que se calculan para cada usuario y una variedad de funciones como max, min, sum, average, etc. El punto decimal es \'.\' y el separador de variables es \',\' Ejemplo: \'=max(favs,retweets,1.15)\' Las variables cuyo nombre empieza por max contienen los valores máximos alcanzados entre todos los usuarios del concurso.';
$string['pluginadministration'] = 'Redes sociales';
$string['harvest_task'] = 'Planificador de recolección de interacciones sociales y análisis y cálculo de indicadores.';
// MainPage.
$string['mainpage'] = 'Portada del concurso Twitter';
$string['mainpage_help'] = 'Portada del concurso Twitter. Puede comprobar aquí sus logros en el concurso Twitter';
$string['module_connected_twitter'] = 'Modulo conectado con Twitter con el usuario "{$a}" ';
$string['module_not_connected_twitter'] = 'Modulo no conectado con Twitter.';

// Permissions.
$string['msocial:view'] = 'Ver información básica del módulo MSocial.';
$string['msocial:viewothers'] = 'Ver la actividad de los otros usuarios.';
$string['msocial:addinstance'] = 'Añadir una nueva actividad MSocial al curso.';
$string['msocial:manage'] = 'Change settings of a MSocial activity';
$string['msocial:view'] = 'View information of MSocial about me';
$string['msocial:viewothers'] = 'View all information collected by MSocial';
