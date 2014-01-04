#######################################################
###  nginx.conf site standard vhost include start
#######################################################

###
### Master location for subdir support (start)
###
location ^~ /<?php print $subdir; ?>/ {

  include       fastcgi_params;
  fastcgi_param db_type   <?php print urlencode($db_type); ?>;
  fastcgi_param db_name   <?php print urlencode($db_name); ?>;
  fastcgi_param db_user   <?php print urlencode($db_user); ?>;
  fastcgi_param db_passwd <?php print urlencode($db_passwd); ?>;
  fastcgi_param db_host   <?php print urlencode($db_host); ?>;
  fastcgi_param db_port   <?php print urlencode($db_port); ?>;
  root          <?php print "{$this->root}"; ?>;
  alias         <?php print "{$this->root}"; ?>;

set $nocache_details "Cache";

###
### Deny crawlers.
###
if ($is_crawler) {
  return 403;
}

###
### Include high load protection config if exists.
###
include /data/conf/nginx_high_load.c*;

###
### Deny not compatible request methods without 405 response.
###
if ( $request_method !~ ^(?:GET|HEAD|POST|PUT|DELETE|OPTIONS)$ ) {
  return 403;
}

###
### Deny listed requests for security reasons.
###
if ($is_denied) {
  return 403;
}

###
### Include high level local configuration override if exists.
###
include /data/disk/EDIT_USER/config/server_master/nginx/post.d/nginx_force_include*;

###
### HTTPRL standard support.
###
location ^~ /<?php print $subdir; ?>/httprl_async_function_callback {
  location ~* ^/<?php print $subdir; ?>/httprl_async_function_callback {
    access_log off;
    limit_conn limreq 88;
    add_header X-Header "HTTPRL 2.0";
    set $nocache_details "Skip";
    try_files  $uri @nobots;
  }
}

###
### HTTPRL test mode support.
###
location ^~ /<?php print $subdir; ?>/admin/httprl-test {
  location ~* ^/<?php print $subdir; ?>/admin/httprl-test {
    access_log off;
    limit_conn limreq 88;
    add_header X-Header "HTTPRL 2.1";
    set $nocache_details "Skip";
    try_files  $uri @nobots;
  }
}

###
### CDN Far Future expiration support.
###
location ^~ /<?php print $subdir; ?>/cdn/farfuture/ {
  tcp_nodelay   off;
  access_log    off;
  log_not_found off;
  etag          off;
  limit_conn limreq 88;
  gzip_http_version 1.0;
  if_modified_since exact;
  set $nocache_details "Skip";
  location ~* ^/<?php print $subdir; ?>/cdn/farfuture/.+\.(?:css|js|jpe?g|gif|png|ico|bmp|svg|swf|pdf|docx?|xlsx?|pptx?|tiff?|txt|rtf|class|otf|ttf|woff|eot|less)$ {
    expires max;
    add_header Access-Control-Allow-Origin *;
    add_header X-Header "CDN Far Future Generator 1.0";
    add_header Cache-Control "no-transform, public";
    add_header Last-Modified "Wed, 20 Jan 1988 04:20:42 GMT";
    rewrite ^/<?php print $subdir; ?>/cdn/farfuture/[^/]+/[^/]+/(.+)$ /<?php print $subdir; ?>/$1 break;
    try_files $uri @nobots;
  }
  location ~* ^/<?php print $subdir; ?>/cdn/farfuture/ {
    expires epoch;
    add_header Access-Control-Allow-Origin *;
    add_header X-Header "CDN Far Future Generator 1.1";
    add_header Cache-Control "private, must-revalidate, proxy-revalidate";
    rewrite ^/<?php print $subdir; ?>/cdn/farfuture/[^/]+/[^/]+/(.+)$ /<?php print $subdir; ?>/$1 break;
    try_files $uri @nobots;
  }
  try_files $uri @nobots;
}

###
### If favicon else return error 204.
###
location = /<?php print $subdir; ?>/favicon.ico {
  access_log    off;
  log_not_found off;
  expires       30d;
  try_files     /<?php print $subdir; ?>/sites/$server_name/files/favicon.ico $uri =204;
}

###
### Support for http://drupal.org/project/robotstxt module
### and static file in the sites/domain/files directory.
###
location = /<?php print $subdir; ?>/robots.txt {
  access_log    off;
  log_not_found off;
  try_files /<?php print $subdir; ?>/sites/$server_name/files/$host.robots.txt /<?php print $subdir; ?>/sites/$server_name/files/robots.txt $uri @cache;
}

###
### Allow local access to the FPM status page.
###
location = /<?php print $subdir; ?>/fpm-status {
  access_log   off;
  allow        127.0.0.1;
  deny         all;
  fastcgi_pass 127.0.0.1:9090;
}

###
### Allow local access to the FPM ping URI.
###
location = /<?php print $subdir; ?>/fpm-ping {
  access_log   off;
  allow        127.0.0.1;
  deny         all;
  fastcgi_pass 127.0.0.1:9090;
}

###
### Allow local access to support wget method in Aegir settings
### for running sites cron.
###
location = /<?php print $subdir; ?>/cron.php {
  set $real_fastcgi_script_name cron.php;
  fastcgi_param SCRIPT_FILENAME <?php print "{$this->root}"; ?>/$real_fastcgi_script_name;
  tcp_nopush   off;
  keepalive_requests 0;
  access_log   off;
  allow        127.0.0.1;
  deny         all;
  try_files    $uri =404;
  fastcgi_pass unix:cron:fastcgi.socket;
}

###
### Allow local access to support wget method in Aegir settings
### for running sites cron in Drupal 8.
###
location = /<?php print $subdir; ?>/core/cron.php {
  set $real_fastcgi_script_name "core/cron.php";
  fastcgi_param SCRIPT_FILENAME <?php print "{$this->root}"; ?>/$real_fastcgi_script_name;
  tcp_nopush   off;
  keepalive_requests 0;
  access_log   off;
  allow        127.0.0.1;
  deny         all;
  try_files    $uri =404;
  fastcgi_pass unix:cron:fastcgi.socket;
}

###
### Send search to php-fpm early so searching for node.js will work.
### Deny bots on search uri.
###
location ^~ /<?php print $subdir; ?>/search {
  location ~* ^/<?php print $subdir; ?>/search {
    if ($is_bot) {
      return 403;
    }
    try_files $uri @cache;
  }
}

###
### Support for http://drupal.org/project/js module.
###
location ^~ /<?php print $subdir; ?>/js/ {
  location ~* ^/<?php print $subdir; ?>/js/ {
    if ($is_bot) {
      return 403;
    }
    rewrite ^/<?php print $subdir; ?>/(.*)$ /<?php print $subdir; ?>/js.php?q=$1 last;
  }
}

###
### Upload progress support.
### http://drupal.org/project/filefield_nginx_progress
### http://github.com/masterzen/nginx-upload-progress-module
###
location ~ (?<upload_form_uri>.*)/x-progress-id:(?<upload_id>\d*) {
  access_log off;
  rewrite ^ $upload_form_uri?X-Progress-ID=$upload_id;
}
location ^~ /<?php print $subdir; ?>/progress {
  access_log off;
  upload_progress_json_output;
  report_uploads uploads;
}

###
### Deny cache details display.
###
location ^~ /<?php print $subdir; ?>/admin/settings/performance/cache-backend {
  access_log off;
  rewrite ^ $scheme://$host/<?php print $subdir; ?>/admin/settings/performance permanent;
}

###
### Deny cache details display.
###
location ^~ /<?php print $subdir; ?>/admin/config/development/performance/redis {
  access_log off;
  rewrite ^ $scheme://$host/<?php print $subdir; ?>/admin/config/development/performance permanent;
}

###
### Support for backup_migrate module download/restore/delete actions.
###
location ^~ /<?php print $subdir; ?>/admin {
  if ($is_bot) {
    return 403;
  }
  access_log off;
  set $nocache_details "Skip";
  try_files $uri @drupal;
}

###
### Avoid caching /civicrm* and protect it from bots.
###
location ^~ /<?php print $subdir; ?>/civicrm {
  if ($is_bot) {
    return 403;
  }
  set $nocache_details "Skip";
  try_files $uri @drupal;
}

###
### Support for audio module.
###
location ^~ /<?php print $subdir; ?>/audio/download {
  location ~* ^/<?php print $subdir; ?>/audio/download/.*/.*\.(?:mp3|mp4|m4a|ogg)$ {
    if ($is_bot) {
      return 403;
    }
    tcp_nopush off;
    access_log off;
    set $nocache_details "Skip";
    try_files $uri @drupal;
  }
}

###
### Deny listed requests for security reasons.
###
location ~* (/\..*|settings\.php$|\.(?:git|htaccess|engine|make|config|inc|ini|info|install|module|profile|pl|po|sh|.*sql|theme|tpl(?:\.php)?|xtmpl)$|^(?:Entries.*|Repository|Root|Tag|Template))$ {
  return 404;
}

###
### Deny listed requests for security reasons.
###
location ~* /(?:modules|themes|libraries)/.*\.(?:txt|md)$ {
  return 404;
}

###
### Deny some not supported URI like cgi-bin on the Nginx level.
###
location ~* (?:cgi-bin|vti-bin) {
  access_log off;
  return 404;
}

###
### Deny bots on some weak modules uri.
###
location ~* (?:validation|aggregator|vote_up_down|captcha|vbulletin|glossary/) {
  if ($is_bot) {
    return 403;
  }
  access_log off;
  try_files $uri @cache;
}

###
### Responsive Images support.
### http://drupal.org/project/responsive_images
###
location ~* \.r\.(?:jpe?g|png|gif) {
  if ( $http_cookie ~* "rwdimgsize=large" ) {
    rewrite ^/<?php print $subdir; ?>/(.*)/mobile/(.*)\.r(\.(?:jpe?g|png|gif))$ /<?php print $subdir; ?>/$1/desktop/$2$3 last;
  }
  rewrite ^/<?php print $subdir; ?>/(.*)\.r(\.(?:jpe?g|png|gif))$ /<?php print $subdir; ?>/$1$2 last;
  access_log off;
  add_header X-Header "RI Generator 1.0";
  set $nocache_details "Skip";
  try_files  $uri @drupal;
}

###
### Adaptive Image Styles support.
### http://drupal.org/project/ais
###
location ~* /<?php print $subdir; ?>/(?:.+)/files/styles/adaptive/(?:.+)$ {
  if ( $http_cookie ~* "ais=(?<ais_cookie>[a-z0-9-_]+)" ) {
    rewrite ^/<?php print $subdir; ?>/(.+)/files/styles/adaptive/(.+)$ /<?php print $subdir; ?>/$1/files/styles/$ais_cookie/$2 last;
  }
  access_log off;
  add_header X-Header "AIS Generator 1.0";
  set $nocache_details "Skip";
  try_files  $uri @drupal;
}

###
### Imagecache and imagecache_external support.
###
location ~* /(?:external|system|files/imagecache|files/styles)/ {
  access_log off;
  log_not_found off;
  expires    30d;
  # fix common problems with old paths after import from standalone to Aegir multisite
  rewrite    ^/<?php print $subdir; ?>/sites/(.*)/files/imagecache/(.*)/sites/default/files/(.*)$  /<?php print $subdir; ?>/sites/$server_name/files/imagecache/$2/$3 last;
  rewrite    ^/<?php print $subdir; ?>/sites/(.*)/files/imagecache/(.*)/files/(.*)$                /<?php print $subdir; ?>/sites/$server_name/files/imagecache/$2/$3 last;
  rewrite    ^/<?php print $subdir; ?>/files/imagecache/(.*)$                                      /<?php print $subdir; ?>/sites/$server_name/files/imagecache/$1 last;
  rewrite    ^/<?php print $subdir; ?>/files/styles/(.*)$                                          /<?php print $subdir; ?>/sites/$server_name/files/styles/$1 last;
  add_header X-Header "IC Generator 1.0";
  set $nocache_details "Skip";
  try_files  $uri @drupal;
}

###
### Deny direct access to backups.
###
location ~* ^/<?php print $subdir; ?>/sites/.*/files/backup_migrate/ {
  access_log off;
  deny all;
}

###
### Deny direct access to config files in Drupal 8.
###
location ~* ^/<?php print $subdir; ?>/sites/.*/files/config_.* {
  access_log off;
  deny all;
}

###
### Include local configuration override if exists.
###
include /data/disk/EDIT_USER/config/server_master/nginx/post.d/nginx_vhost_include*;

###
### Private downloads are always sent to the drupal backend.
### Note: this location doesn't work with X-Accel-Redirect.
###
location ~* ^/<?php print $subdir; ?>/sites/.*/files/private/ {
  if ($is_bot) {
    return 403;
  }
  access_log off;
  rewrite    ^/<?php print $subdir; ?>/sites/.*/files/private/(.*)$ $scheme://$host/<?php print $subdir; ?>/system/files/private/$1 permanent;
  add_header X-Header "Private Generator 1.0a";
  set $nocache_details "Skip";
  try_files  $uri @drupal;
}

###
### Deny direct access to private downloads in sites/domain/private.
### Note: this location works with X-Accel-Redirect.
###
location ~* ^/<?php print $subdir; ?>/sites/.*/private/ {
  if ($is_bot) {
    return 403;
  }
  access_log off;
  internal;
}

###
### Deny direct access to private downloads also for short, rewritten URLs.
### Note: this location works with X-Accel-Redirect.
###
location ~* /files/private/ {
  if ($is_bot) {
    return 403;
  }
  access_log off;
  internal;
}

###
### Wysiwyg Fields support.
###
location ~* wysiwyg_fields/(?:plugins|scripts)/.*\.(?:js|css) {
  access_log off;
  log_not_found off;
  try_files $uri @nobots;
}

###
### Advagg_css and Advagg_js support.
###
location ~* files/advagg_(?:css|js)/ {
  expires    max;
  access_log off;
  etag       off;
  limit_conn limreq 88;
  rewrite    ^/<?php print $subdir; ?>/files/advagg_(.*)/(.*)$ /<?php print $subdir; ?>/sites/$server_name/files/advagg_$1/$2 last;
  add_header Cache-Control "max-age=290304000, no-transform, public";
  add_header Access-Control-Allow-Origin *;
  add_header X-Header "AdvAgg Generator 2.0";
  set $nocache_details "Skip";
  try_files  $uri @nobots;
}

###
### Make css files compatible with boost caching.
###
location ~* \.css$ {
  if ( $request_method = POST ) {
    return 405;
  }
  if ( $cache_uid ) {
    return 405;
  }
  error_page  405 = @uncached;
  access_log  off;
  tcp_nodelay off;
  expires     max; #if using aggregator
  add_header  X-Header "Boost Citrus 2.1";
  try_files   /cache/perm/$host${uri}_.css $uri =404;
}

###
### Make js files compatible with boost caching.
###
location ~* \.(?:js|htc)$ {
  if ( $request_method = POST ) {
    return 405;
  }
  if ( $cache_uid ) {
    return 405;
  }
  error_page  405 = @uncached;
  access_log  off;
  tcp_nodelay off;
  expires     max; # if using aggregator
  add_header  X-Header "Boost Citrus 2.2";
  try_files   /cache/perm/$host${uri}_.js $uri =404;
}

###
### Support for static .json files with fast 404 +Boost compatibility.
###
location ~* ^/<?php print $subdir; ?>/sites/.*/files/.*\.json$ {
  if ( $cache_uid ) {
    return 405;
  }
  error_page  405 = @uncached;
  access_log  off;
  tcp_nodelay off;
  expires     max; ### if using aggregator
  add_header  X-Header "Boost Citrus 2.3";
  add_header  Access-Control-Allow-Origin *;
  try_files   /cache/normal/$host${uri}_.json $uri =404;
}

###
### Support for dynamic .json requests.
###
location ~* \.json$ {
  try_files $uri @cache;
}

###
### Helper location to bypass boost static files cache for logged in users.
###
location @uncached {
  access_log off;
  expires max; # max if using aggregator, otherwise sane expire time
}

###
### Map /files/ shortcut early to avoid overrides in other locations.
###
location ^~ /<?php print $subdir; ?>/files/ {
  location ~* ^.+\.(?:pdf|jpe?g|gif|png|ico|bmp|svg|swf|docx?|xlsx?|pptx?|tiff?|txt|rtf|cgi|bat|pl|dll|class|otf|ttf|woff|eot|less|avi|mpe?g|mov|wmv|mp3|ogg|ogv|wav|midi|zip|tar|t?gz|rar|dmg|exe|apk|pxl|ipa)$ {
    expires       30d;
    tcp_nodelay   off;
    access_log    off;
    log_not_found off;
    add_header  Access-Control-Allow-Origin *;
    rewrite  ^/<?php print $subdir; ?>/files/(.*)$  /<?php print $subdir; ?>/sites/$server_name/files/$1 last;
    try_files   $uri =404;
  }
  try_files $uri @cache;
}

###
### Map /downloads/ shortcut early to avoid overrides in other locations.
###
location ^~ /<?php print $subdir; ?>/downloads/ {
  location ~* ^.+\.(?:pdf|jpe?g|gif|png|ico|bmp|svg|swf|docx?|xlsx?|pptx?|tiff?|txt|rtf|cgi|bat|pl|dll|class|otf|ttf|woff|eot|less|avi|mpe?g|mov|wmv|mp3|ogg|ogv|wav|midi|zip|tar|t?gz|rar|dmg|exe|apk|pxl|ipa)$ {
    expires       30d;
    tcp_nodelay   off;
    access_log    off;
    log_not_found off;
    add_header  Access-Control-Allow-Origin *;
    rewrite  ^/<?php print $subdir; ?>/downloads/(.*)$  /<?php print $subdir; ?>/sites/$server_name/files/downloads/$1 last;
    try_files   $uri =404;
  }
  try_files $uri @cache;
}

###
### Serve & no-log static files & images directly,
### without all standard drupal rewrites, php-fpm etc.
###
location ~* ^.+\.(?:jpe?g|gif|png|ico|bmp|svg|swf|docx?|xlsx?|pptx?|tiff?|txt|rtf|cgi|bat|pl|dll|class|otf|ttf|woff|eot|less|mp3|wav|midi)$ {
  expires       30d;
  tcp_nodelay   off;
  access_log    off;
  log_not_found off;
  add_header  Access-Control-Allow-Origin *;
  rewrite     ^/<?php print $subdir; ?>/images/(.*)$  /<?php print $subdir; ?>/sites/$server_name/files/images/$1 last;
  rewrite     ^/<?php print $subdir; ?>/.+/sites/.+/files/(.*)$  /<?php print $subdir; ?>/sites/$server_name/files/$1 last;
  rewrite     ^/<?php print $subdir; ?>/odules/civicrm/(.*)$     /<?php print $subdir; ?>/sites/all/modules/civicrm/$1 last;
  try_files   $uri =404;
}

###
### Serve & log bigger media/static/archive files directly,
### without all standard drupal rewrites, php-fpm etc.
###
location ~* ^.+\.(?:avi|mpe?g|mov|wmv|ogg|ogv|zip|tar|t?gz|rar|dmg|exe|apk|pxl|ipa)$ {
  expires     30d;
  tcp_nodelay off;
  tcp_nopush  off;
  add_header  Access-Control-Allow-Origin *;
  rewrite     ^/<?php print $subdir; ?>/.+/sites/.+/files/(.*)$  /<?php print $subdir; ?>/sites/$server_name/files/$1 last;
  try_files   $uri =404;
}

###
### Serve & no-log some static files directly,
### but only from the files directory to not break
### dynamically created pdf files or redirects for
### legacy URLs with asp/aspx extension.
###
location ~* ^/<?php print $subdir; ?>/sites/.+/files/.+\.(?:pdf|aspx?)$ {
  expires       30d;
  tcp_nodelay   off;
  access_log    off;
  log_not_found off;
  add_header  Access-Control-Allow-Origin *;
  try_files   $uri =404;
}

###
### Pseudo-streaming server-side support for Flash Video (FLV) files.
###
location ~* ^.+\.flv$ {
  flv;
  add_header Access-Control-Allow-Origin *;
  tcp_nodelay off;
  tcp_nopush off;
  expires 30d;
  try_files $uri =404;
}

###
### Pseudo-streaming server-side support for H.264/AAC files.
###
location ~* ^.+\.(?:mp4|m4a)$ {
  mp4;
  add_header Access-Control-Allow-Origin *;
  mp4_buffer_size 1m;
  mp4_max_buffer_size 5m;
  tcp_nodelay off;
  tcp_nopush off;
  expires 30d;
  try_files $uri =404;
}

###
### Serve & no-log some static files as is, without forcing default_type.
###
location ~* /(?:cross-?domain)\.xml$ {
  access_log  off;
  tcp_nodelay off;
  expires     30d;
  add_header  X-Header "XML Generator 1.0";
  try_files   $uri =404;
}

###
### Allow some known php files (like serve.php in the ad module).
###
location ~* ^/<?php print $subdir; ?>/(.*/(?:modules|libraries)/(?:contrib/)?(?:ad|tinybrowser|f?ckeditor|tinymce|wysiwyg_spellcheck|ecc|civicrm|fbconnect|radioactivity)/.*\.php)$ {
  set $real_fastcgi_script_name $1;
  fastcgi_param SCRIPT_FILENAME <?php print "{$this->root}"; ?>/$real_fastcgi_script_name;
  tcp_nopush   off;
  keepalive_requests 0;
  access_log   off;
  if ($is_bot) {
    return 403;
  }
  try_files    $uri =404;
  fastcgi_pass 127.0.0.1:9090;
}

###
### Deny crawlers and never cache known AJAX and webform requests.
###
location ~* /(?:ahah|ajax|batch|autocomplete|webform|done|progress/|x-progress-id|js/.*) {
  if ($is_bot) {
    return 403;
  }
  access_log off;
  log_not_found off;
  set $nocache_details "Skip";
  try_files $uri @nobots;
}

###
### Serve & no-log static helper files used in some wysiwyg editors.
###
location ~* ^/<?php print $subdir; ?>/sites/.*/(?:modules|libraries)/(?:contrib/)?(?:tinybrowser|f?ckeditor|tinymce|flowplayer|jwplayer|videomanager)/.*\.(?:html?|xml)$ {
  if ($is_bot) {
    return 403;
  }
  access_log      off;
  tcp_nodelay     off;
  expires         30d;
  try_files $uri =404;
}

###
### Serve & no-log any not specified above static files directly.
###
location ~* ^/<?php print $subdir; ?>/sites/.*/files/ {
  access_log      off;
  tcp_nodelay     off;
  expires         30d;
  try_files $uri =404;
}

###
### Make feeds compatible with boost caching and set correct mime type.
###
location ~* \.xml$ {
  if ( $request_method = POST ) {
    return 405;
  }
  if ( $cache_uid ) {
    return 405;
  }
  error_page 405 = @drupal;
  access_log off;
  add_header Expires "Tue, 24 Jan 1984 08:00:00 GMT";
  add_header Cache-Control "must-revalidate, post-check=0, pre-check=0";
  add_header X-Header "Boost Citrus 2.4";
  charset    utf-8;
  types { }
  default_type text/xml;
  try_files /cache/normal/$host${uri}_.xml /cache/normal/$host${uri}_.html $uri @drupal;
}

###
### Deny bots on never cached uri.
###
location ~* ^/<?php print $subdir; ?>/(?:.*/)?(?:admin|user|cart|checkout|logout|comment/reply) {
  if ($is_bot) {
    return 403;
  }
  access_log off;
  set $nocache_details "Skip";
  try_files $uri @drupal;
}

###
### Protect from DoS attempts on never cached uri.
###
location ~* ^/<?php print $subdir; ?>/(?:.*/)?(?:node/[0-9]+/edit|node/add) {
  if ($is_bot) {
    return 403;
  }
  access_log off;
  set $nocache_details "Skip";
  try_files $uri @drupal;
}

###
### Protect from DoS attempts on never cached uri.
###
location ~* ^/<?php print $subdir; ?>/(?:.*/)?(?:node/[0-9]+/delete|approve) {
  if ($cache_uid = '') {
    return 403;
  }
  if ($is_bot) {
    return 403;
  }
  access_log off;
  set $nocache_details "Skip";
  try_files $uri @drupal;
}

###
### Support for ESI microcaching: http://groups.drupal.org/node/197478.
###
### This may enhance not only anonymous visitors, but also
### logged in users experience, as it allows you to separate
### microcache for ESI/SSI includes (valid for just 5 seconds)
### from both default Speed Booster cache for anonymous visitors
### (valid by default for 10s or 1h, unless purged on demand via
### recently introduced Purge/Expire modules) and also from
### Speed Booster cache per logged in user (valid for 10 seconds).
###
### Now you have three different levels of Speed Booster cache
### to leverage and deliver the 'live content' experience for
### all visitors, and still protect your server from DoS or
### simply high load caused by unexpected high traffic etc.
###
location ~ ^/<?php print $subdir; ?>/(?<esi>esi/.*)$ {
  ssi on;
  ssi_silent_errors on;
  internal;
  add_header    X-Device "$device";
  add_header    X-Speed-Micro-Cache "$upstream_cache_status";
  add_header    X-Speed-Micro-Cache-Expire "5s";
  add_header    X-NoCache "$nocache_details";
  add_header    X-GeoIP-Country-Code "$geoip_country_code";
  add_header    X-GeoIP-Country-Name "$geoip_country_name";
  add_header    X-This-Proto "$http_x_forwarded_proto";
  add_header    X-Server-Name "$server_name";
  ###
  ### Set correct, local $uri.
  ###
  fastcgi_param QUERY_STRING q=$esi;
  set $real_fastcgi_script_name index.php;
  fastcgi_param SCRIPT_FILENAME <?php print "{$this->root}"; ?>/$real_fastcgi_script_name;
  fastcgi_pass  127.0.0.1:9090;
  ###
  ### Use Nginx cache for all visitors.
  ###
  set $nocache "";
  if ( $http_cookie ~* "NoCacheID" ) {
    set $nocache "NoCache";
  }
  fastcgi_cache speed;
  fastcgi_cache_methods GET HEAD;
  fastcgi_cache_min_uses 1;
  fastcgi_cache_key "$is_bot$device$host$request_method$uri$is_args$args$cache_uid$http_x_forwarded_proto";
  fastcgi_cache_valid 200 301 404 5s;
  fastcgi_cache_valid 302 1m;
  fastcgi_ignore_headers Cache-Control Expires;
  fastcgi_pass_header Set-Cookie;
  fastcgi_pass_header X-Accel-Expires;
  fastcgi_pass_header X-Accel-Redirect;
  fastcgi_no_cache $cookie_NoCacheID $http_authorization $http_pragma $nocache;
  fastcgi_cache_bypass $cookie_NoCacheID $http_authorization $http_pragma $nocache;
  fastcgi_cache_use_stale error http_500 http_503 invalid_header timeout updating;
  tcp_nopush off;
  keepalive_requests 0;
  expires epoch;
}

###
### Rewrite legacy requests with /index.php to extension-free URL.
###
if ( $args ~* "^q=(?<query_value>.*)" ) {
  rewrite ^/<?php print $subdir; ?>/index.php$ $scheme://$host/<?php print $subdir; ?>/?q=$query_value? permanent;
}

###
### Catch all unspecified requests.
###
location /<?php print $subdir; ?>/ {
  if ( $http_user_agent ~* wget ) {
    return 403;
  }
  try_files $uri @cache;
}

###
### Boost compatible cache check.
###
location @cache {
  if ( $request_method = POST ) {
    set $nocache_details "Method";
    return 405;
  }
  if ( $args ~* "nocache=1" ) {
    set $nocache_details "Args";
    return 405;
  }
  if ( $sent_http_x_force_nocache = "YES" ) {
    set $nocache_details "Skip";
    return 405;
  }
  if ( $http_cookie ~* "NoCacheID" ) {
    set $nocache_details "AegirCookie";
    return 405;
  }
  if ( $cache_uid ) {
    set $nocache_details "DrupalCookie";
    return 405;
  }
  error_page 405 = @drupal;
  add_header Expires "Tue, 24 Jan 1984 08:00:00 GMT";
  add_header Cache-Control "must-revalidate, post-check=0, pre-check=0";
  add_header X-Header "Boost Citrus 1.9";
  charset    utf-8;
  try_files  /cache/normal/$host${uri}_$args.html @drupal;
}

###
### Send all not cached requests to drupal with clean URLs support.
###
location @drupal {
  error_page 418 = @nobots;
  if ($args) {
    return 418;
  }
  rewrite ^/<?php print $subdir; ?>/(.*)$  /<?php print $subdir; ?>/index.php?q=$1 last;
}

###
### Send all known bots to $args free URLs.
###
location @nobots {
  if ($is_bot) {
    rewrite ^ $scheme://$host$uri? permanent;
  }
  ###
  ### Return 404 on special PHP URLs to avoid revealing version used,
  ### even indirectly. See also: https://drupal.org/node/2116387
  ###
  if ( $args ~* "=PHP[A-Z0-9]{8}-" ) {
    return 404;
  }
  rewrite ^/<?php print $subdir; ?>/(.*)$  /<?php print $subdir; ?>/index.php?q=$1 last;
}

###
### Send all non-static requests to php-fpm, restricted to known php file.
###
location = /<?php print $subdir; ?>/index.php {
  internal;
  set $real_fastcgi_script_name index.php;
  fastcgi_param SCRIPT_FILENAME <?php print "{$this->root}"; ?>/$real_fastcgi_script_name;
  add_header    X-Device "$device";
  add_header    X-Speed-Cache "$upstream_cache_status";
  add_header    X-Speed-Cache-UID "$cache_uid";
  add_header    X-Speed-Cache-Key "$key_uri";
  add_header    X-NoCache "$nocache_details";
  add_header    X-GeoIP-Country-Code "$geoip_country_code";
  add_header    X-GeoIP-Country-Name "$geoip_country_name";
  add_header    X-This-Proto "$http_x_forwarded_proto";
  add_header    X-Server-Name "$server_name";
  tcp_nopush    off;
  keepalive_requests 0;
  try_files     $uri =404; ### check for existence of php file first
  fastcgi_pass  127.0.0.1:9090;
  track_uploads uploads 60s; ### required for upload progress
  ###
  ### Use Nginx cache for all visitors.
  ###
  set $nocache "";
  if ( $nocache_details ~ (?:AegirCookie|Args|Skip) ) {
    set $nocache "NoCache";
  }
  fastcgi_cache speed;
  fastcgi_cache_methods GET HEAD; ### Nginx default, but added for clarity
  fastcgi_cache_min_uses 1;
  fastcgi_cache_key "$is_bot$device$host$request_method$key_uri$cache_uid$http_x_forwarded_proto$sent_http_x_local_proto$cookie_respimg";
  fastcgi_cache_valid 200 10s;
  fastcgi_cache_valid 302 1m;
  fastcgi_cache_valid 301 403 404 5s;
  fastcgi_cache_valid 500 502 503 504 1s;
  fastcgi_ignore_headers Cache-Control Expires;
  fastcgi_pass_header Set-Cookie;
  fastcgi_pass_header X-Accel-Expires;
  fastcgi_pass_header X-Accel-Redirect;
  fastcgi_no_cache $cookie_NoCacheID $http_authorization $http_pragma $nocache;
  fastcgi_cache_bypass $cookie_NoCacheID $http_authorization $http_pragma $nocache;
  fastcgi_cache_use_stale error http_500 http_503 invalid_header timeout updating;
}

###
### Send other known php requests/files to php-fpm without any caching.
###
location ~* ^/<?php print $subdir; ?>/(?:core/)?(boost_stats|rtoc|xmlrpc|js)\.php$ {
  set $real_fastcgi_script_name $1.php;
  fastcgi_param SCRIPT_FILENAME <?php print "{$this->root}"; ?>/$real_fastcgi_script_name;
  if ($is_bot) {
    return 404;
  }
  tcp_nopush   off;
  keepalive_requests 0;
  access_log   off;
  try_files    $uri =404; ### check for existence of php file first
  fastcgi_pass 127.0.0.1:9090;
}

###
### Allow access to /authorize.php and /update.php only for logged in admin user.
###
location ~* ^/<?php print $subdir; ?>/(?:core/)?(authorize|update)\.php$ {
  set $real_fastcgi_script_name $1.php;
  fastcgi_param SCRIPT_FILENAME <?php print "{$this->root}"; ?>/$real_fastcgi_script_name;
  error_page 418 = @allowupdate;
  if ( $cache_uid ) {
    return 418;
  }
  return 404;
}

###
### Internal location for /authorize.php and /update.php restricted access.
###
location @allowupdate {
  tcp_nopush   off;
  keepalive_requests 0;
  access_log   off;
  try_files    $uri =404; ### check for existence of php file first
  fastcgi_pass 127.0.0.1:9090;
}

###
### Deny access to any not listed above php files with 404 error.
###
location ~* ^.+\.php$ {
  return 404;
}

}
###
### Master location for subdir support (end)
###

###
### Redirect for subdir support (start)
###
rewrite ^/(.*)$  /<?php print $subdir; ?>/$1 last;
###
### Redirect for subdir support (end)
###

#######################################################
###  nginx.conf site standard vhost include end
#######################################################
