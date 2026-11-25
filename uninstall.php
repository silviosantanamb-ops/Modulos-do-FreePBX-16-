<?php
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

$asterisk = '/etc/asterisk';
foreach (array($asterisk.'/extensions_custom.conf', $asterisk.'/pjsip.endpoint_custom_post.conf') as $file) {
    if (!file_exists($file)) continue;
    $s = file_get_contents($file);
    $s = preg_replace('/\n?; BEGIN SIPMESSAGES MODULE.*?; END SIPMESSAGES MODULE\n?/s', "\n", $s);
    $s = preg_replace('/\n?; SIPMESSAGES endpoint (100|200|300|400)\n\[\1\]\(\+type=endpoint\)\nmessage_context = messages\n?/s', "\n", $s);
    file_put_contents($file, $s, LOCK_EX);
}
@exec('asterisk -rx "dialplan reload" 2>&1');
@exec('asterisk -rx "database deltree sipmessages/unread" 2>&1');
@exec('asterisk -rx "database deltree sipmessages/last_sender" 2>&1');
@exec('asterisk -rx "pjsip reload" 2>&1');
return true;
?>
