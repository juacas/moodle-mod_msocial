<?php
defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
	
     $settings->add(new admin_setting_heading('mod_tcount_tweeter_header', 'Tweeter API', 'Keys for Tweeter API Access.'));

//      'oauth_access_token' => "255700071-aawZtTtbqMX8XfcpLJVdvW7EmlOkJlGril9c8aof",
//    'oauth_access_token_secret' => "SzS8PvIcnVi5HowdvZGG7kukIaMDmrmUOmLyfbAcrHmzy",
//    'consumer_key' => "wT08DqV9NPacunLmxvZvd5JI8",
//    'consumer_secret' => "Vb1lKLqYMnF0KtCnjRH4xFuZhNC2zy9YmssBM3wgX0PDkn9iII"
//      $settings->add(new admin_setting_configtext('mod_tcount_oauth_access_token',
//			get_string('tcount_oauth_access_token', 'tcount'),
//			get_string('config_oauth_access_token', 'tcount'),
//			'',PARAM_RAW_TRIMMED));
//    $settings->add(new admin_setting_configtext('mod_tcount_oauth_access_token_secret',
//			get_string('tcount_oauth_access_token_secret', 'tcount'),
//			get_string('config_oauth_access_token_secret', 'tcount'),
//			'',PARAM_RAW_TRIMMED));
    $settings->add(new admin_setting_configtext('mod_tcount_consumer_key',
			get_string('tcount_consumer_key', 'tcount'),
			get_string('config_consumer_key', 'tcount'),
			'',PARAM_RAW_TRIMMED));
    $settings->add(new admin_setting_configtext('mod_tcount_consumer_secret',
			get_string('tcount_consumer_secret', 'tcount'),
			get_string('config_consumer_secret', 'tcount'),
			'',PARAM_RAW_TRIMMED));

}
