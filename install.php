<?php
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

$asterisk = '/etc/asterisk';
$custom = $asterisk . '/extensions_custom.conf';
$pjsip_post = $asterisk . '/pjsip.endpoint_custom_post.conf';
$state = '/var/lib/asterisk/sipmessages';
if (!is_dir($state)) {
    @mkdir($state, 0770, true);
    @chown($state, 'asterisk');
    @chgrp($state, 'asterisk');
}

$markerStart = '; BEGIN SIPMESSAGES MODULE';
$markerEnd = '; END SIPMESSAGES MODULE';

$dialplan = $markerStart . "\n" . <<<'DIALPLAN'
[messages]
; Route local SIP MESSAGE requests to the PJSIP endpoint named by the dialed extension.
; The notification state is kept in Asterisk AstDB so the Operator Panel can read it
; without changing or interrupting the actual SIP MESSAGE delivery.
exten => _X!,1,NoOp(SIP MESSAGE to ${EXTEN})
 same => n,Set(SM_FROM=${MESSAGE(from)})
 same => n,Set(SM_TO=${MESSAGE(to)})
 same => n,Set(SM_BODY=${MESSAGE(body)})
 same => n,GotoIf($["${SM_BODY:0:12}"="SIPMSG-READ:"]?read:deliver)
 same => n(read),Set(SM_OLD_UNREAD=${DB_DELETE(sipmessages/unread/${EXTEN})})
 same => n,Set(SM_OLD_SENDER=${DB_DELETE(sipmessages/last_sender/${EXTEN})})
 same => n,Hangup()
 same => n(deliver),NoOp(SIP MESSAGE delivery)
 same => n,Set(SM_FROM_URI=${CUT(SM_FROM,@,1)})
 same => n,Set(SM_FROM_EXT=${REPLACE(SM_FROM_URI,pjsip:,)})
 same => n,Set(SM_FROM_EXT=${REPLACE(SM_FROM_EXT,sip:,)})
 same => n,Set(SM_FROM_EXT=${REPLACE(SM_FROM_EXT,sips:,)})
 same => n,Set(SM_TARGET=pjsip:${EXTEN})
 same => n,MessageSend(${SM_TARGET},${SM_FROM},${SM_TO})
 same => n,NoOp(SIP MESSAGE status=${MESSAGE_SEND_STATUS})
 same => n,ExecIf($["${MESSAGE_SEND_STATUS}"="SUCCESS"]?Set(SM_UNREAD=${DB(sipmessages/unread/${EXTEN})}))
 same => n,ExecIf($["${MESSAGE_SEND_STATUS}"="SUCCESS" & "${SM_UNREAD}"=""]?Set(SM_UNREAD=0))
 same => n,ExecIf($["${MESSAGE_SEND_STATUS}"="SUCCESS"]?Set(SM_UNREAD=$[${SM_UNREAD}+1]))
 same => n,ExecIf($["${MESSAGE_SEND_STATUS}"="SUCCESS"]?Set(DB(sipmessages/unread/${EXTEN})=${SM_UNREAD}))
 same => n,ExecIf($["${MESSAGE_SEND_STATUS}"="SUCCESS"]?Set(DB(sipmessages/last_sender/${EXTEN})=${SM_FROM_EXT}))
 same => n,Hangup()
DIALPLAN
. "\n" . $markerEnd . "\n";


$existing = file_exists($custom) ? file_get_contents($custom) : '';
if (strpos($existing, $markerStart) === false) {
    file_put_contents($custom, "\n".$dialplan, FILE_APPEND | LOCK_EX);
} else {
    $pattern = '/'.preg_quote($markerStart, '/').'.*?'.preg_quote($markerEnd, '/').'/s';
    $existing = preg_replace($pattern, trim($dialplan), $existing, 1);
    file_put_contents($custom, $existing, LOCK_EX);
}

$endpoints = array();
if (function_exists('core_users_list')) {
    foreach ((array)core_users_list() as $user) { if (is_array($user) && isset($user[0]) && preg_match('/^[0-9A-Za-z_*#.-]+$/', (string)$user[0])) $endpoints[] = (string)$user[0]; }
}
$endpoints = array_values(array_unique($endpoints));
$p = file_exists($pjsip_post) ? file_get_contents($pjsip_post) : '';
foreach ($endpoints as $ext) {
    $block = "[{$ext}](+type=endpoint)\nmessage_context = messages\n";
    $pattern = '/\[' . preg_quote($ext, '/') . '\]\(\+type=endpoint\)[^\n]*(?:\n(?!\[).*)*/';
    if (preg_match($pattern, $p)) {
        $p = preg_replace($pattern, $block, $p, 1);
    } else {
        $p .= "\n; SIPMESSAGES endpoint {$ext}\n".$block;
    }
}
file_put_contents($pjsip_post, $p, LOCK_EX);

@exec('asterisk -rx "dialplan reload" 2>&1', $o1, $r1);
@exec('asterisk -rx "pjsip reload" 2>&1', $o2, $r2);
return true;
?>
