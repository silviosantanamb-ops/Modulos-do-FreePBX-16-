<?php
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

$moduleVersion = '1.0.5';
$endpoints = array();
if (function_exists('core_users_list')) {
    foreach ((array)core_users_list() as $user) {
        if (is_array($user) && isset($user[0]) && preg_match('/^[0-9A-Za-z_*#.-]+$/', (string)$user[0])) {
            $endpoints[] = (string)$user[0];
        }
    }
}
$endpoints = array_values(array_unique($endpoints));
$names = array();
if (function_exists('core_users_list')) {
    $users = core_users_list();
    if (is_array($users)) {
        foreach ($users as $user) {
            if (is_array($user) && isset($user[0])) {
                $names[(string)$user[0]] = isset($user[1]) ? (string)$user[1] : '';
            }
        }
    }
}
?>
<style>
#sipmessages-panel { color:#263445; }
#sipmessages-panel .sm-hero { display:flex; align-items:center; justify-content:space-between; gap:18px; padding:22px 26px; margin:8px 0 20px; border-radius:10px; background:linear-gradient(120deg,#123d54,#176b70); color:#fff; box-shadow:0 5px 18px rgba(24,55,75,.14); }
#sipmessages-panel .sm-title { margin:0 0 6px; color:#fff; font-size:25px; font-weight:650; letter-spacing:.1px; }
#sipmessages-panel .sm-subtitle { margin:0; color:rgba(255,255,255,.83); font-size:14px; }
#sipmessages-panel .sm-version { flex:none; padding:7px 12px; border:1px solid rgba(255,255,255,.35); border-radius:20px; color:#fff; font-size:12px; }
#sipmessages-panel .sm-status { display:flex; align-items:flex-start; gap:13px; padding:15px 18px; margin-bottom:20px; border:1px solid #b9dfc7; border-radius:8px; background:#f0faf3; color:#245b39; }
#sipmessages-panel .sm-status-icon { display:flex; align-items:center; justify-content:center; width:34px; height:34px; flex:none; border-radius:50%; background:#d5f0df; font-size:18px; }
#sipmessages-panel .sm-status strong { display:block; margin:0 0 3px; font-size:15px; }
#sipmessages-panel .sm-status p { margin:0; color:#467456; }
#sipmessages-panel .sm-section { overflow:hidden; margin-bottom:20px; border:1px solid #dce4eb; border-radius:9px; background:#fff; box-shadow:0 2px 7px rgba(24,48,70,.045); }
#sipmessages-panel .sm-section-head { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:15px 19px; border-bottom:1px solid #e6edf2; }
#sipmessages-panel .sm-section-head h3 { margin:0; color:#263c4e; font-size:17px; font-weight:600; }
#sipmessages-panel .sm-count { display:inline-block; padding:4px 10px; border-radius:15px; background:#eaf3f5; color:#23666b; font-size:12px; font-weight:600; }
#sipmessages-panel .sm-table-wrap { overflow-x:auto; }
#sipmessages-panel table { width:100%; margin:0; border-collapse:collapse; }
#sipmessages-panel thead th { padding:12px 18px; border-bottom:1px solid #dce8e9; background:#f3f8f8; color:#526575; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.45px; white-space:nowrap; }
#sipmessages-panel tbody td { padding:13px 18px; border-top:1px solid #edf1f4; vertical-align:middle; }
#sipmessages-panel tbody tr:first-child td { border-top:0; }
#sipmessages-panel tbody tr:hover { background:#f8fbfc; }
#sipmessages-panel .sm-ext { display:inline-block; min-width:44px; padding:6px 9px; border-radius:6px; background:#edf5f6; color:#17656c; font-weight:700; text-align:center; }
#sipmessages-panel .sm-person { color:#293d4d; font-weight:600; }
#sipmessages-panel .sm-destination { color:#5e7080; font-family:monospace; font-size:13px; }
#sipmessages-panel .sm-note { display:flex; gap:12px; align-items:flex-start; padding:16px 19px; background:#f4f8fc; color:#52697c; }
#sipmessages-panel .sm-note strong { color:#344f66; }
#sipmessages-panel .sm-note p { margin:0; line-height:1.55; }
#sipmessages-panel .sm-empty { padding:24px 18px; color:#718292; text-align:center; }
#sipmessages-panel .sm-footer-note { margin:5px 2px 0; color:#617283; font-size:13px; line-height:1.6; }
@media(max-width:600px){ #sipmessages-panel .sm-hero{align-items:flex-start;padding:18px;flex-direction:column;} #sipmessages-panel .sm-title{font-size:21px;} #sipmessages-panel .sm-section-head{padding:13px;} #sipmessages-panel thead th,#sipmessages-panel tbody td{padding:11px 12px;} }
</style>
<div id="sipmessages-panel" class="container-fluid">
  <div class="sm-hero">
    <div>
      <h2 class="sm-title">Painel de Mensagens</h2>
      <p class="sm-subtitle">Mensagens internas entre ramais PJSIP</p>
    </div>
    <span class="sm-version">Versão <?php echo htmlspecialchars($moduleVersion, ENT_QUOTES, 'UTF-8'); ?></span>
  </div>

  <div class="sm-status" role="status">
    <div class="sm-status-icon" aria-hidden="true">✓</div>
    <div>
      <strong>Mensagens SIP activas</strong>
      <p>O módulo utiliza o suporte nativo PJSIP Messaging do Asterisk para encaminhar mensagens internas entre ramais.</p>
    </div>
  </div>

  <section class="sm-section" aria-labelledby="sm-endpoints-title">
    <div class="sm-section-head">
      <h3 id="sm-endpoints-title">Ramais configurados</h3>
      <span class="sm-count"><?php echo count($endpoints); ?> ramais</span>
    </div>
    <?php if (count($endpoints) > 0): ?>
      <div class="sm-table-wrap">
        <table class="table table-striped">
          <thead><tr><th>Ramal</th><th>Nome registado</th><th>Destino SIP</th></tr></thead>
          <tbody>
          <?php foreach ($endpoints as $ext): ?>
            <tr>
              <td><span class="sm-ext"><?php echo htmlspecialchars($ext, ENT_QUOTES, 'UTF-8'); ?></span></td>
              <td><span class="sm-person"><?php echo htmlspecialchars(isset($names[$ext]) && $names[$ext] !== '' ? $names[$ext] : ('Ramal '.$ext), ENT_QUOTES, 'UTF-8'); ?></span></td>
              <td><span class="sm-destination">pjsip:<?php echo htmlspecialchars($ext, ENT_QUOTES, 'UTF-8'); ?></span></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="sm-empty">Não foram encontrados ramais configurados.</div>
    <?php endif; ?>
  </section>

  <div class="sm-section">
    <div class="sm-section-head"><h3>Como testar</h3></div>
    <div class="sm-note">
      <div aria-hidden="true">✉</div>
      <p><strong>Envie uma mensagem de um Zoiper ou PortSIP para outro ramal.</strong><br>O Asterisk encaminha o SIP MESSAGE através do contexto <code>messages</code>.</p>
    </div>
  </div>
  <p class="sm-footer-note">As notificações não lidas são reflectidas no Painel do Operador. O número do ramal é mantido como identificador técnico, associado ao nome registado no FreePBX.</p>
</div>
