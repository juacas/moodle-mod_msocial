# mod_msocial
Moodle module for integrating Social networks into classroom.

The activity allow the teacher to collect the student's activity at the social networks and compute statistics and analyse visually the performance of the users.

MSocial need plugins for connecting to social networks and for generating visualizations of the interactions.
## Built-in subplugins
* Social network connectors:
  * Moodle Forums: collect the interactions of the users in the forums of the course.
  * Questournament: connect to the contest-based game [Questournament|https://github.com/juacas/moodle-mod_quest] activity.
* Visualizations:
  * Drops: overview of interactions density over time.
  * Table: summary table with PKI (primary key indicator) calculated by the module.
  * Graph: graph-oriented analysis and visuallizations. Compute centralities and shows the activity in matrix and graph diagrams.
  * Timeline: Shows interactions ordered in a timeline.

## Optional plugins

Some of the optional sub-plugins are:
* Social network connectors:
  * Facebook: connect to Facebook and explore a group. (Need API keys).
  * Twitter: connect to users' timelines and search for tags. (Need Facebook's API keys).
  * Instagram: connect to users' posts and search for tags. (Need Instagram's API keys).
  * Pinterest: connect to a set of pinboards. (Need Pinterest's API keys).
* Visualizations:
  * Sequence: Sequence diagram with all interchanges of messages (more useful for small sets).
  * Timeglide: Shows interaction ordered in a timeline using Timeglide visualization.

# Overview

The students need to register themselves in the activity with their social network account to establish the linking between their social identity and the moodle indentity.

The teacher need to register himself in the activity to activate the module and allow the server to connect periodically to the external services on his behalf (with the permissions granted by the teacher).
 
## Configuration

Important: Some connectors need to have a valid, approved, API key provided by the service provider (i.e. Facebook).
Any subplugin can be disabled per-instance.

# Confidentiality

Permissions asked by the connectors allow the server to collect the content posted by the users that meets the criteria set by the teacher. In example, only posts that contains a set of hashtags are retrieved and stored in the course. Every other content are ignored and not stored.

In some social networks the content can come from private groups but others only support publicly readable content. Consult each connector details if you are concerned by those issues.

## Credits 

Developed at http://www.eduvalab.uva.es a research group of the University of Valladolid, Spain.
Copyright 2017 Juan Pablo de Castro jpdecastro@tel.uva.es