FROM php:7.1-apache
RUN docker-php-ext-install mysqli
RUN pecl install xdebug-2.6.0
RUN docker-php-ext-enable xdebug
RUN docker-php-ext-enable opcache
RUN echo '[xdebug]\n\
xdebug.remote_enable=1\n\
xdebug.remote_host = host.docker.internal\n\
[opcache]\n\
opcache.enable = 1\n\
opcache.memory_consumption = 128\n\
opcache.max_accelerated_files = 10000\n\
opcache.revalidate_freq = 60\n\
\n\
; Required for Moodle\n\
opcache.use_cwd = 1\n\
opcache.validate_timestamps = 1\n\
opcache.save_comments = 1\n\
opcache.enable_file_override = 0\n\
\n\
; If something does not work in Moodle\n\
;opcache.revalidate_path = 1 ; May fix problems with include paths\n\
;opcache.mmap_base = 0x20000000 ; (Windows only) fix OPcache crashes with event id 487\n\
\n\
; Experimental for Moodle 2.6 and later\n\
;opcache.fast_shutdown = 1\n\
;opcache.enable_cli = 1 ; Speeds up CLI cron\n\
;opcache.load_comments = 0 ; May lower memory use, might not be compatible with add-ons and other apps."'\
 >> /usr/local/etc/php/php.ini