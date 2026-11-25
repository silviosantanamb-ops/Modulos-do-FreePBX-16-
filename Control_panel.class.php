<?php
namespace FreePBX\modules;

class Control_panel implements \BMO
{
    protected $FreePBX;

    public function __construct($freepbx = null)
    {
        if ($freepbx === null) {
            throw new \Exception("Not given a FreePBX Object");
        }
        $this->FreePBX = $freepbx;
    }

    /*
     * Required by FreePBX BMO. The panel itself is AJAX driven, so there
     * are no normal form actions to process here.
     */
    public function doConfigPageInit($page)
    {
        return true;
    }

    public function install()
    {
        $this->configureAmi();
        return true;
    }

    public function upgrade($current_version)
    {
        $this->configureAmi();
        return true;
    }

    public function uninstall()
    {
        $this->removeAmi();
        return true;
    }

    public function backup()
    {
        return array();
    }

    public function restore($backup)
    {
        return true;
    }

    public function ajaxRequest($command, &$settings)
    {
        $settings['authenticate'] = true;
        $settings['allowremote'] = false;

        return in_array((string)$command, array(
            'dashboard', 'hints', 'channels', 'conferences', 'messages', 'clear_messages', 'hangup', 'originate', 'redirect'
        ), true);
    }

    public function ajaxHandler()
    {
        $command = isset($_REQUEST['command']) ? trim((string)$_REQUEST['command']) : '';

        try {
            switch ($command) {
                case 'dashboard':
                    $hints = $this->amiList('CoreShowHints', 'Hint', 'CoreShowHintsComplete');
                    return array(
                        'status' => true,
                        'data' => array(
                            'extensions' => $this->getConfiguredExtensions(),
                            'hints' => $hints,
                            'channels' => $this->filterInternalCalls(
                                $this->amiList('CoreShowChannels', 'CoreShowChannel', 'CoreShowChannelsComplete'),
                                $this->getConfiguredExtensions()
                            ),
                            'conferences' => $this->getConferences(),
                            'messages' => $this->getMessageCounts(),
                            'endpoints' => $this->amiList('PJSIPShowEndpoints', 'EndpointList', 'EndpointListComplete')
                        )
                    );

                case 'hints':
                    return array('status' => true, 'data' => $this->amiList('CoreShowHints', 'Hint', 'CoreShowHintsComplete'));

                case 'channels':
                    return array('status' => true, 'data' => $this->amiList('CoreShowChannels', 'CoreShowChannel', 'CoreShowChannelsComplete'));

                case 'conferences':
                    return array('status' => true, 'data' => $this->getConferences());

                case 'messages':
                    return array('status' => true, 'data' => $this->getMessageCounts());

                case 'clear_messages':
                    $extension = $this->requestValue('extension');
                    if ($extension === '') {
                        return $this->fail('Ramal inválido.');
                    }
                    return $this->clearMessages($extension)
                        ? array('status' => true, 'data' => array('extension' => $extension))
                        : $this->fail('Não foi possível limpar as notificações do ramal.');

                case 'hangup':
                    $channel = $this->requestValue('channel');
                    if (!$this->validChannel($channel)) {
                        return $this->fail('Canal inválido.');
                    }
                    return $this->amiAction('Hangup', array('Channel' => $channel));

                case 'originate':
                    $channel = $this->requestValue('channel');
                    $exten = $this->requestValue('exten');
                    if (!$this->validChannel($channel)) {
                        return $this->fail('Canal inválido. Ex.: PJSIP/100.');
                    }
                    if (!$this->validDestination($exten)) {
                        return $this->fail('Destino inválido.');
                    }
                    return $this->amiAction('Originate', array(
                        'Channel' => $channel,
                        'Context' => 'from-internal',
                        'Exten' => $exten,
                        'Priority' => 1,
                        'Async' => 'true',
                        'CallerID' => 'Operator Panel'
                    ));

                case 'redirect':
                    $channel = $this->requestValue('channel');
                    $exten = $this->requestValue('exten');
                    if (!$this->validChannel($channel)) {
                        return $this->fail('Canal inválido.');
                    }
                    if (!$this->validDestination($exten)) {
                        return $this->fail('Destino inválido.');
                    }
                    return $this->amiAction('Redirect', array(
                        'Channel' => $channel,
                        'Exten' => $exten,
                        'Context' => 'from-internal',
                        'Priority' => 1
                    ));

                default:
                    return $this->fail('Comando não encontrado.');
            }
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    /** Keep only genuine PJSIP calls between two configured local extensions.
     * SIP MESSAGE channels (Message/ast_msg_queue), external trunks and other
     * non-extension channels are deliberately excluded from call counters.
     */
    private function filterInternalCalls($channels, $extensions)
    {
        $known = array();
        foreach ($extensions as $extension) {
            if (isset($extension['extension'])) {
                $known[(string)$extension['extension']] = true;
            }
        }
        $filtered = array();
        $seenPairs = array();
        foreach ((array)$channels as $channel) {
            $name = isset($channel['Channel']) ? (string)$channel['Channel'] : '';
            if (!preg_match('/^PJSIP\/([0-9A-Za-z_*#.-]+)-/i', $name, $match)) {
                continue;
            }
            $local = $match[1];
            if (!isset($known[$local])) { continue; }
            $peers = array();
            foreach (array('CallerIDNum', 'ConnectedLineNum', 'Exten') as $field) {
                if (!isset($channel[$field])) { continue; }
                $value = trim((string)$channel[$field]);
                if (isset($known[$value]) && $value !== $local) { $peers[$value] = true; }
            }
            if (!$peers) { continue; }
            $peer = (string)array_keys($peers)[0];
            $pair = array($local, $peer); sort($pair, SORT_STRING);
            $key = implode(':', $pair);
            if (isset($seenPairs[$key])) { continue; }
            $seenPairs[$key] = true;
            $filtered[] = $channel;
        }
        return $filtered;
    }

    /**
     * Returns active ConfBridge rooms and remembers rooms already seen.
     * ConfBridgeListRooms is the authoritative AMI source for active rooms.
     * A room that becomes empty is retained as "Disponível" so the operator
     * can still see the conference until it is explicitly removed from the DB.
     */
    private function getConferences()
    {
        $active = $this->amiList('ConfbridgeListRooms', 'ConfbridgeListRooms', 'ConfbridgeListRoomsComplete');
        $activeMap = array();

        foreach ($active as $room) {
            if (!isset($room['Conference']) || trim((string)$room['Conference']) === '') {
                continue;
            }
            $conference = trim((string)$room['Conference']);
            $parties = isset($room['Parties']) ? (int)$room['Parties'] : 0;
            $activeMap[$conference] = array(
                'conference' => $conference,
                'label' => 'Conferência ' . $conference,
                'parties' => max(0, $parties),
                'active' => $parties > 0 ? 1 : 0
            );
        }

        try {
            $db = $this->FreePBX->Database;

            foreach ($activeMap as $row) {
                $existing = $db->prepare("SELECT conference FROM operator_panel_conferences WHERE conference = :conference LIMIT 1");
                $existing->execute(array(':conference' => $row['conference']));
                if ($existing->fetchColumn()) {
                    $stmt = $db->prepare("UPDATE operator_panel_conferences SET parties = :parties, active = :active, last_seen = NOW() WHERE conference = :conference");
                    $stmt->execute(array(
                        ':parties' => $row['parties'],
                        ':active' => $row['active'],
                        ':conference' => $row['conference']
                    ));
                } else {
                    $stmt = $db->prepare("INSERT INTO operator_panel_conferences (conference,label,parties,active,last_seen) VALUES (:conference,:label,:parties,:active,NOW())");
                    $stmt->execute(array(
                        ':conference' => $row['conference'],
                        ':label' => $row['label'],
                        ':parties' => $row['parties'],
                        ':active' => $row['active']
                    ));
                }
            }

            if (!empty($activeMap)) {
                $ids = array_keys($activeMap);
                $marks = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $db->prepare("UPDATE operator_panel_conferences SET active = 0, parties = 0 WHERE conference NOT IN ($marks)");
                $stmt->execute($ids);
            } else {
                $db->query("UPDATE operator_panel_conferences SET active = 0, parties = 0");
            }

            $rows = $db->query("SELECT conference,label,parties,active,last_seen FROM operator_panel_conferences ORDER BY conference ASC")
                       ->fetchAll(\PDO::FETCH_ASSOC);
            return is_array($rows) ? $rows : array();
        } catch (\Throwable $e) {
            // If the DB table is not available yet, still show live rooms.
            return array_values($activeMap);
        }
    }

    /**
     * Message counters are intentionally kept separate from the AMI layer.
     * This prepares the panel for the future internal/SMS messaging service.
     */
    private function getMessageCounts()
    {
        try {
            $rows = $this->FreePBX->Database
                ->query("SELECT extension,sms_count,message_count,updated_at FROM operator_panel_messages ORDER BY extension ASC")
                ->fetchAll(\PDO::FETCH_ASSOC);
            $counts = array();
            foreach ((array)$rows as $row) { $counts[(string)$row['extension']] = $row; }
            // SIP Internal Messages stores unread counts in Asterisk AstDB.
            $output = array(); $status = 0;
            @exec('asterisk -rx "database show sipmessages/unread" 2>/dev/null', $output, $status);
            if ($status === 0) {
                foreach ($output as $line) {
                    if (preg_match('~sipmessages/unread/([0-9A-Za-z_*#.-]+)\s*:\s*(\d+)~i', $line, $m)) {
                        $ext = $m[1]; $count = (int)$m[2];
                        if (!isset($counts[$ext])) $counts[$ext] = array('extension'=>$ext,'sms_count'=>0,'message_count'=>0,'updated_at'=>null);
                        $counts[$ext]['message_count'] = $count;
                    }
                }
            }
            return array_values($counts);
        } catch (\Throwable $e) {
            return array();
        }
    }

    /**
     * Public integration point for a future messaging module.
     * $type may be "sms" or "message".
     */
    public function recordMessage($extension, $type = 'message')
    {
        $extension = trim((string)$extension);
        $type = strtolower(trim((string)$type));

        if ($extension === '' || !preg_match('/^[0-9A-Za-z_*#.\-]+$/', $extension)) {
            return false;
        }

        $isSms = ($type === 'sms') ? 1 : 0;

        try {
            $db = $this->FreePBX->Database;
            $stmt = $db->prepare("SELECT extension FROM operator_panel_messages WHERE extension = :extension LIMIT 1");
            $stmt->execute(array(':extension' => $extension));

            if ($stmt->fetchColumn()) {
                $sql = "UPDATE operator_panel_messages
                        SET sms_count = sms_count + :sms,
                            message_count = message_count + 1,
                            updated_at = NOW()
                        WHERE extension = :extension";
                $stmt = $db->prepare($sql);
                $stmt->execute(array(':sms' => $isSms, ':extension' => $extension));
            } else {
                $stmt = $db->prepare("INSERT INTO operator_panel_messages (extension,sms_count,message_count,updated_at) VALUES (:extension,:sms,1,NOW())");
                $stmt->execute(array(':extension' => $extension, ':sms' => $isSms));
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Public integration point to clear a ramal's notification counter.
     */
    public function clearMessages($extension)
    {
        $extension = trim((string)$extension);
        if ($extension === '') {
            return false;
        }

        try {
            $stmt = $this->FreePBX->Database->prepare(
                "DELETE FROM operator_panel_messages WHERE extension = :extension"
            );
            $stmt->execute(array(':extension' => $extension));
            if (!preg_match('/^[0-9A-Za-z_*#.-]+$/', $extension)) {
                return false;
            }

            // Remove the unread key instead of storing a literal zero. The
            // MESSAGE dialplan treats a missing key as zero and increments it
            // on the next successfully delivered message. This avoids stale
            // zero-valued entries masking subsequent notification state.
            $commands = array(
                'database del sipmessages unread/' . $extension,
                'database del sipmessages last_sender/' . $extension,
            );
            foreach ($commands as $command) {
                $output = array();
                $exitCode = 0;
                @exec('asterisk -rx ' . escapeshellarg($command) . ' 2>&1', $output, $exitCode);
                if ($exitCode !== 0) {
                    return false;
                }
            }
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /*
     * IMPORTANT: FreePBX Core is the source of truth for extensions.
     * AMI hints are used only to add live device/status information.
     */
    private function getConfiguredExtensions()
    {
        $result = array();

        if (function_exists('core_users_list')) {
            $users = core_users_list();
            if (is_array($users)) {
                foreach ($users as $user) {
                    if (!is_array($user) || !isset($user[0])) {
                        continue;
                    }
                    $ext = trim((string)$user[0]);
                    if ($ext === '') {
                        continue;
                    }
                    $result[$ext] = array(
                        'extension' => $ext,
                        'name' => isset($user[1]) ? (string)$user[1] : ''
                    );
                }
            }
        }

        if (empty($result)) {
            try {
                $core = \FreePBX::Core();
                if (is_object($core) && method_exists($core, 'listUsers')) {
                    $users = $core->listUsers();
                    if (is_array($users)) {
                        foreach ($users as $user) {
                            if (!is_array($user) || !isset($user[0])) {
                                continue;
                            }
                            $ext = trim((string)$user[0]);
                            if ($ext === '') {
                                continue;
                            }
                            $result[$ext] = array(
                                'extension' => $ext,
                                'name' => isset($user[1]) ? (string)$user[1] : ''
                            );
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Leave the list empty; AMI hints are still returned separately.
            }
        }

        return array_values($result);
    }

    private function amiList($action, $eventName, $completeEvent)
    {
        $messages = $this->amiRequest($action, array(), $completeEvent);
        $items = array();

        foreach ($messages as $message) {
            if (!is_array($message) || empty($message)) {
                continue;
            }

            if ($eventName === null) {
                if (isset($message['Event']) && $message['Event'] !== $completeEvent) {
                    $items[] = $message;
                }
            } elseif (isset($message['Event']) && $message['Event'] === $eventName) {
                $items[] = $message;
            }
        }

        return $items;
    }

    private function amiAction($action, $fields)
    {
        $messages = $this->amiRequest($action, $fields, null);

        foreach ($messages as $message) {
            if (isset($message['Response']) && strtolower($message['Response']) === 'error') {
                return $this->fail(isset($message['Message']) ? $message['Message'] : 'Asterisk recusou a operação.');
            }
        }

        return array('status' => true, 'data' => $messages);
    }

    private function amiRequest($action, $fields, $completeEvent)
    {
        $secret = $this->getAmiSecret();
        if ($secret === '') {
            throw new \Exception(
                'Conta AMI do Operator Panel não encontrada. Execute "fwconsole ma upgrade control_panel" e depois "fwconsole reload".'
            );
        }

        $errno = 0;
        $errstr = '';
        $fp = @fsockopen('127.0.0.1', 5038, $errno, $errstr, 2);

        if (!$fp) {
            throw new \Exception('Não foi possível ligar ao Asterisk AMI: ' . ($errstr ?: 'ligação recusada'));
        }

        stream_set_timeout($fp, 2);

        try {
            $this->sendAmi($fp, array(
                'Action' => 'Login',
                'Username' => 'controlpanel',
                'Secret' => $secret,
                'Events' => 'on'
            ));

            $login = $this->readMessages($fp, 1.5, null, false);
            if (!$this->successful($login)) {
                throw new \Exception('Falha no login AMI. Verifique a conta controlpanel no manager_custom.conf.');
            }

            $id = 'cp' . substr(md5(uniqid('', true)), 0, 12);
            $payload = array('Action' => $action, 'ActionID' => $id);
            foreach ($fields as $key => $value) {
                $payload[$key] = $value;
            }

            $this->sendAmi($fp, $payload);

            if ($completeEvent !== null) {
                $messages = $this->readMessages($fp, 4.0, $completeEvent, true);
            } else {
                $messages = $this->readMessages($fp, 2.0, null, false);
            }

            $this->sendAmi($fp, array('Action' => 'Logoff'));
            return $messages;
        } finally {
            fclose($fp);
        }
    }

    private function sendAmi($fp, $data)
    {
        foreach ($data as $key => $value) {
            fwrite($fp, $key . ': ' . $value . "\r\n");
        }
        fwrite($fp, "\r\n");
    }

    private function readMessages($fp, $timeout, $completeEvent, $listMode)
    {
        $messages = array();
        $started = microtime(true);
        $seenResponse = false;

        while ((microtime(true) - $started) < $timeout) {
            $message = $this->readMessage($fp, 0.5);
            if (empty($message)) {
                continue;
            }

            $messages[] = $message;

            if (isset($message['Response'])) {
                $seenResponse = true;
                if (strtolower($message['Response']) === 'error') {
                    break;
                }
                if (!$listMode) {
                    break;
                }
            }

            if ($listMode && isset($message['Event']) && $message['Event'] === $completeEvent) {
                break;
            }

            if ($listMode && $seenResponse &&
                isset($message['EventList']) &&
                strtolower($message['EventList']) === 'complete') {
                break;
            }
        }

        return $messages;
    }

    private function readMessage($fp, $timeout)
    {
        $message = array();
        $started = microtime(true);

        while ((microtime(true) - $started) < $timeout) {
            $line = fgets($fp);

            if ($line === false) {
                $meta = stream_get_meta_data($fp);
                if (!empty($meta['timed_out'])) {
                    break;
                }
                usleep(10000);
                continue;
            }

            $line = rtrim($line, "\r\n");

            if ($line === '') {
                return $message;
            }

            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $message[trim($key)] = ltrim($value);
            }
        }

        return $message;
    }

    private function successful($messages)
    {
        foreach ($messages as $message) {
            if (isset($message['Response']) && strtolower($message['Response']) === 'success') {
                return true;
            }
        }
        return false;
    }

    private function getAmiSecret()
    {
        $path = '/etc/asterisk/manager_custom.conf';
        if (!is_readable($path)) {
            return '';
        }

        $text = file_get_contents($path);

        if (preg_match('/^\[controlpanel\]\s*$(.*?)(?=^\[|\z)/ms', $text, $block) &&
            preg_match('/^\s*secret\s*=\s*(.+?)\s*$/mi', $block[1], $m)) {
            return trim($m[1]);
        }

        return '';
    }

    private function configureAmi()
    {
        $path = '/etc/asterisk/manager_custom.conf';
        $old = is_file($path) ? file_get_contents($path) : '';

        $secret = $this->getAmiSecret();
        if ($secret === '') {
            $bytes = function_exists('random_bytes') ? random_bytes(24) : openssl_random_pseudo_bytes(24);
            $secret = bin2hex($bytes);
        }

        $begin = '; BEGIN Operator Panel - FreePBX';
        $end = '; END Operator Panel - FreePBX';

        $block = $begin . "\n" .
            "[controlpanel]\n" .
            "secret=" . $secret . "\n" .
            "deny=0.0.0.0/0.0.0.0\n" .
            "permit=127.0.0.1/255.255.255.255\n" .
            "read=system,call,reporting,agent,user,dtmf\n" .
            "write=system,call,reporting,agent,user,dtmf\n" .
            $end . "\n";

        $pattern = '/\n?' . preg_quote($begin, '/') . '.*?' . preg_quote($end, '/') . '\n?/s';

        if (preg_match($pattern, $old)) {
            $new = preg_replace($pattern, "\n" . $block, $old, 1);
        } else {
            $new = rtrim($old) . "\n\n" . $block;
        }

        if (@file_put_contents($path, $new, LOCK_EX) === false) {
            throw new \Exception('Não foi possível escrever /etc/asterisk/manager_custom.conf.');
        }

        @chmod($path, 0640);
        return true;
    }

    private function removeAmi()
    {
        $path = '/etc/asterisk/manager_custom.conf';
        if (!is_file($path)) {
            return true;
        }

        $text = file_get_contents($path);
        $pattern = '/\n?; BEGIN Operator Panel - FreePBX.*?; END Operator Panel - FreePBX\n?/s';
        @file_put_contents($path, preg_replace($pattern, "\n", $text), LOCK_EX);
        return true;
    }

    private function requestValue($key)
    {
        return isset($_POST[$key]) ? trim((string)$_POST[$key]) : '';
    }

    private function validChannel($value)
    {
        return $value !== '' && preg_match('/^[A-Za-z0-9_\/.\-:@]+$/', $value);
    }

    private function validDestination($value)
    {
        return $value !== '' && preg_match('/^[0-9A-Za-z_*#.\-]+$/', $value);
    }

    private function fail($message)
    {
        return array('status' => false, 'message' => $message);
    }
}
