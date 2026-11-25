<?php
if (!defined('FREEPBX_IS_AUTH')) {
    die('No direct script access allowed');
}
?>
<style>
#operator-panel{font-size:14px}
#operator-panel .op-card{background:#fff;border:1px solid #d9dee3;border-radius:8px;margin-bottom:16px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.05)}
#operator-panel .op-head{padding:14px 18px;border-bottom:1px solid #e6e9ec;display:flex;justify-content:space-between;align-items:center;gap:12px}
#operator-panel .op-head h2{margin:0;font-size:22px}
#operator-panel .op-sub{color:#6f7780;font-size:12px;margin-top:3px}
#operator-panel .op-body{padding:14px 16px}
#operator-panel .op-empty{text-align:center;color:#8a9299;padding:28px 12px}
#operator-panel .op-stat{background:#fff;border:1px solid #d9dee3;border-radius:8px;padding:14px 16px;margin-bottom:16px;min-height:82px;text-align:center;display:flex;flex-direction:column;align-items:center;justify-content:center}
#operator-panel .op-stat b{display:block;font-size:25px;line-height:1.1;margin-bottom:5px;text-align:center}
#operator-panel .op-stat small{color:#777;text-align:center;display:block}
#operator-panel .op-section-title{font-weight:700;font-size:15px}
#operator-panel .op-search{max-width:190px}

#operator-panel .op-extension-grid{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(245px,1fr));
  gap:12px;
  padding:14px;
}
#operator-panel .op-extension{
  position:relative;
  min-height:128px;
  border:1px solid #d9dee3;
  border-left:5px solid #a0a6ab;
  border-radius:8px;
  background:#fff;
  padding:14px 15px 12px;
  transition:transform .12s ease,box-shadow .12s ease,border-color .12s ease;
}
#operator-panel .op-extension:hover{transform:translateY(-1px);box-shadow:0 3px 10px rgba(0,0,0,.08)}
#operator-panel .op-extension.is-online{border-left-color:#38a169}
#operator-panel .op-extension.is-communication{border-left-color:#ed8936}
#operator-panel .op-extension.is-busy{border-left-color:#805ad5}
#operator-panel .op-extension.is-offline{border-left-color:#d9534f}
#operator-panel .op-ext-top{display:flex;align-items:center;gap:11px}
#operator-panel .op-ext-number{
  width:48px;height:48px;border-radius:10px;background:#f0f2f4;
  display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:700;color:#2d3338;
  flex:0 0 48px;
}
#operator-panel .is-online .op-ext-number{background:#e9f7ee;color:#27804b}
#operator-panel .is-communication .op-ext-number{background:#fff3e6;color:#c05621}
#operator-panel .is-offline .op-ext-number{background:#fdeeee;color:#bd3d3a}
#operator-panel .is-busy .op-ext-number{background:#f1ebff;color:#6841a8}
#operator-panel .op-ext-name{font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
#operator-panel .op-ext-tech{font-size:11px;color:#7b838a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:2px}
#operator-panel .op-ext-status{margin-top:13px;display:flex;align-items:center;justify-content:space-between;font-size:12px}
#operator-panel .op-state{display:inline-flex;align-items:center;font-weight:600}
#operator-panel .op-dot{width:9px;height:9px;border-radius:50%;background:#9aa0a6;margin-right:6px}
#operator-panel .op-dot.green{background:#38a169}
#operator-panel .op-dot.red{background:#d9534f}
#operator-panel .op-dot.orange{background:#ed8936}
#operator-panel .op-dot.purple{background:#805ad5}
#operator-panel .op-message-badge{cursor:pointer}
#operator-panel .op-message-badge{
  display:inline-flex;align-items:center;justify-content:center;
  min-width:25px;height:23px;padding:0 7px;border-radius:12px;
  background:#337ab7;color:#fff;font-size:11px;font-weight:700;
}
#operator-panel .op-message-badge.empty{background:#eef0f2;color:#8a9299}
#operator-panel .op-message-badge.hot{background:#d9534f}
#operator-panel .op-message-badge i{margin-right:4px}

#operator-panel .op-list{max-height:360px;overflow:auto}
#operator-panel .op-row{display:flex;align-items:center;gap:10px;padding:10px 12px;border-bottom:1px solid #eee}
#operator-panel .op-row:last-child{border-bottom:0}
#operator-panel .op-avatar{width:38px;height:38px;border-radius:50%;background:#eee;display:flex;align-items:center;justify-content:center;font-weight:bold;flex:0 0 38px}
#operator-panel .op-main{flex:1;min-width:0}
#operator-panel .op-meta{font-size:11px;color:#777;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
#operator-panel .op-call-time{font-size:11px;color:#777;margin-top:3px}
#operator-panel .op-actions{display:flex;gap:5px;flex-wrap:wrap}
#operator-panel .op-conference-grid{
  display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:12px;padding:14px;
}
#operator-panel .op-conference{
  border:1px solid #d9dee3;border-left:5px solid #a0a6ab;border-radius:8px;padding:14px;background:#fff;
}
#operator-panel .op-conference.is-active{border-left-color:#38a169}
#operator-panel .op-conf-top{display:flex;justify-content:space-between;gap:10px}
#operator-panel .op-conf-name{font-weight:700}
#operator-panel .op-conf-number{font-size:11px;color:#777;margin-top:3px}
#operator-panel .op-conf-state{font-size:11px;font-weight:700}
#operator-panel .op-conf-state.green{color:#27804b}
#operator-panel .op-conf-state.gray{color:#777}
#operator-panel .op-conf-parties{margin-top:13px;font-size:13px}
#operator-panel .op-dial{display:grid;grid-template-columns:repeat(6,1fr);gap:5px}
@media(max-width:767px){
  #operator-panel .op-extension-grid,#operator-panel .op-conference-grid{grid-template-columns:1fr}
  #operator-panel .op-dial{grid-template-columns:repeat(3,1fr)}
  #operator-panel .op-head{align-items:flex-start;flex-wrap:wrap}
  #operator-panel .op-search{max-width:none;width:100%!important}
}

/* Identidade visual moderna do Painel do Operador */
#operator-panel{color:#243247}
#operator-panel>.op-card:first-child{border:0;border-radius:14px;overflow:hidden;box-shadow:0 8px 24px rgba(19,52,80,.12)}
#operator-panel>.op-card:first-child .op-head{padding:22px 24px;background:linear-gradient(120deg,#123c59 0%,#176b83 58%,#20a4a1 100%);color:#fff;border:0;min-height:100px}
#operator-panel>.op-card:first-child .op-head h2{font-size:25px;font-weight:700;letter-spacing:.1px;color:#fff}
#operator-panel>.op-card:first-child .op-head h2 i{margin-right:8px}
#operator-panel>.op-card:first-child .op-sub{color:rgba(255,255,255,.82);font-size:13px;margin-top:7px}
#operator-panel>.op-card:first-child .op-head .btn{border:1px solid rgba(255,255,255,.45);background:rgba(255,255,255,.13);color:#fff;border-radius:8px;padding:7px 12px}
#operator-panel>.op-card:first-child .op-head .btn:hover{background:rgba(255,255,255,.24)}
#operator-panel>.op-card:first-child .op-head .badge{background:#fff;color:#176b83}
#operator-panel>.op-card:first-child .op-body{padding:14px 20px;background:#f7fbfd}
#operator-panel .op-stat{border:1px solid #e1eaf0;border-radius:13px;min-height:106px;padding:17px;background:linear-gradient(145deg,#fff,#f7fbfd);box-shadow:0 5px 16px rgba(30,64,90,.06);transition:transform .15s ease,box-shadow .15s ease}
#operator-panel .op-stat:hover{transform:translateY(-2px);box-shadow:0 9px 22px rgba(30,64,90,.1)}
#operator-panel .op-stat b{font-size:30px;color:#176b83;margin-bottom:7px}
#operator-panel .op-stat small{font-size:12px;font-weight:600;color:#617386;letter-spacing:.2px}
#operator-panel>.op-card:not(:first-child),#operator-panel .row>.col-md-6 .op-card{border:1px solid #e0e8ee;border-radius:12px;box-shadow:0 4px 14px rgba(30,64,90,.055)}
#operator-panel .op-head{padding:15px 18px}
#operator-panel .op-section-title,#operator-panel .op-head strong{color:#23445b}
#operator-panel .op-extension{border-radius:11px;box-shadow:0 2px 8px rgba(25,55,75,.04)}
#operator-panel .op-extension:hover{box-shadow:0 7px 17px rgba(25,55,75,.11)}
#operator-panel .op-message-badge{box-shadow:0 2px 6px rgba(20,70,100,.12)}
#operator-panel .op-conference{border-radius:10px}
@media(max-width:767px){#operator-panel>.op-card:first-child .op-head{padding:18px;gap:14px}#operator-panel>.op-card:first-child .op-head h2{font-size:21px}}

</style>

<div class="container-fluid" id="operator-panel">
  <div class="op-card">
    <div class="op-head">
      <div>
        <h2><i class="fa fa-headset"></i> Painel do Operador</h2>
        <div class="op-sub">Monitorização em tempo real do Asterisk / FreePBX</div>
      </div>
      <div style="display:flex;gap:8px;align-items:center"><button class="btn btn-default btn-sm" id="op-messages-top" title="Ver notificações"><i class="fa fa-envelope"></i> Mensagens <span class="badge" id="op-message-total">0</span></button><button class="btn btn-default btn-sm" id="op-refresh"><i class="fa fa-refresh"></i> Atualizar</button></div>
    </div>
    <div class="op-body">
      <div id="op-alert" class="alert alert-info">A ligar ao Asterisk...</div>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-4"><div class="op-stat"><b id="stat-total">0</b><small>Ramais configurados</small></div></div>
    <div class="col-sm-4"><div class="op-stat"><b id="stat-online">0</b><small>Disponíveis</small></div></div>
    <div class="col-sm-4"><div class="op-stat"><b id="stat-calls">0</b><small>Chamadas ativas</small></div></div>
  </div>

  <div class="op-card">
    <div class="op-head">
      <div>
        <div class="op-section-title"><i class="fa fa-users"></i> Ramais</div>
        <div class="op-sub">Verde = disponível · Laranja = em comunicação · Roxo = ocupado · Vermelho = indisponível</div>
      </div>
      <input id="op-search" class="form-control input-sm op-search" placeholder="Pesquisar ramal ou nome">
    </div>
    <div id="op-extensions" class="op-extension-grid">
      <div class="op-empty">A carregar ramais...</div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-6">
      <div class="op-card">
        <div class="op-head">
          <strong><i class="fa fa-phone-square"></i> Chamadas ativas</strong>
          <span class="badge" id="calls-badge">0</span>
        </div>
        <div class="op-list" id="op-calls"><div class="op-empty">Nenhuma chamada ativa.</div></div>
      </div>
    </div>

    <div class="col-md-6">
      <div class="op-card">
        <div class="op-head">
          <strong><i class="fa fa-users"></i> Conferências</strong>
          <span class="badge" id="conference-badge">0</span>
        </div>
        <div id="op-conferences" class="op-conference-grid">
          <div class="op-empty">Nenhuma conferência registada.</div>
        </div>
      </div>
    </div>
  </div>

</div>

<script>
(function($){
'use strict';
var state={extensions:[],hints:[],endpoints:[],channels:[],conferences:[],messages:[],timer:null};

function esc(v){return String(v==null?'':v).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c];});}
function api(command,data){
  var d=data||{}; d._=Date.now();
  return $.ajax({url:'ajax.php?module=control_panel&command='+encodeURIComponent(command),method:data?'POST':'GET',data:d,dataType:'json',cache:false});
}
function alertBox(ok,msg){
  $('#op-alert').removeClass('alert-info alert-success alert-danger').addClass(ok?'alert-success':'alert-danger').html((ok?'✓ ':'✕ ')+esc(msg));
}
function normalizeHint(v){
  v=String(v||'');
  if(v.indexOf('@')>=0)v=v.split('@')[0];
  v=v.replace(/^(PJSIP|SIP|IAX2)\//i,'');
  return v;
}
function normalizeChannel(v){
  v=String(v||'');
  return normalizeHint(v);
}
function channelStateText(c){
  return String(c.StateDesc||c.StateText||c.State||'').toLowerCase();
}
function channelInvolves(c,ext){
  var e=String(ext);
  var values=[
    c.Channel,c.ChannelStateDesc,c.CallerIDNum,c.CallerIDName,
    c.ConnectedLineNum,c.ConnectedLineName,c.Exten,c.Context
  ];
  for(var i=0;i<values.length;i++){
    var v=String(values[i]||'');
    if(normalizeChannel(v)===e)return true;
    if(v.indexOf('/'+e+'-')>=0 || v.indexOf('/'+e+'@')>=0 || v.indexOf(':'+e+'@')>=0)return true;
  }
  return false;
}
function endpointForExtension(ext){
  var e=String(ext), found=null;
  (state.endpoints||[]).forEach(function(ep){
    var name=String(ep.ObjectName||ep.Endpoint||ep.AOR||'');
    if(name===e || normalizeHint(name)===e) found=ep;
  });
  return found||{};
}
function hasEndpointContact(ep){
  var c=ep.Contacts;
  if(c==null)return false;
  if(Array.isArray(c))return c.length>0;
  var s=String(c).trim().toLowerCase();
  if(!s || s==='0' || s==='none' || s==='unknown' || s==='unavailable')return false;
  return true;
}
function extensionState(ext,h,ep){
  var device=String(ep.DeviceState||ep.State||'').toLowerCase();
  var contact=hasEndpointContact(ep);
  var hintText=String(h.StatusText||h.StatusDesc||'').toLowerCase();
  var n=String(h.Status==null?'':h.Status);

  // Para o painel, "disponível" significa que o endpoint PJSIP está registado.
  // O contacto PJSIP é a fonte principal; CoreShowHints sozinho pode devolver
  // UNKNOWN/UNAVAILABLE mesmo quando o softphone está registado.
  if(device.indexOf('busy')>=0)return ['busy','purple','Ocupado'];
  if(contact)return ['online','green','Disponível'];
  if(device.indexOf('in use')>=0 && !device.indexOf('not in use')>=0)return ['busy','purple','Ocupado'];
  if(hintText.indexOf('unavailable')>=0||hintText.indexOf('unknown')>=0||hintText.indexOf('invalid')>=0||device.indexOf('unavailable')>=0||device.indexOf('unknown')>=0||device.indexOf('invalid')>=0||n==='0'||n==='8'||n==='16'){
    return ['offline','red','Indisponível'];
  }
  if(hintText.indexOf('busy')>=0||hintText.indexOf('in use')>=0||n==='4'||n==='2'||n==='3'){
    return ['busy','purple','Ocupado'];
  }
  if(hintText.indexOf('available')>=0||hintText.indexOf('idle')>=0||hintText.indexOf('not in use')>=0||n==='1'){
    return ['online','green','Disponível'];
  }
  return ['offline','red','Indisponível'];
}
function statusForExtension(ext,h){
  var ep=endpointForExtension(ext);
  var st=extensionState(ext,h,ep);
  var communicating=false;
  var ringing=false;
  state.channels.forEach(function(c){
    if(!channelInvolves(c,ext))return;
    var t=channelStateText(c);
    if(t.indexOf('ring')>=0 || t.indexOf('ringing')>=0) ringing=true;
    else communicating=true;
  });
  if(communicating)return ['communication','orange','Em comunicação'];
  if(ringing)return ['communication','orange','A tocar'];
  return st;
}
function messageLabel(m,ext){
  var count=parseInt(m.message_count||0,10);
  if(count<=0)return '<button type="button" class="op-message-badge empty" data-ext="'+esc(ext)+'" title="Sem notificações"><i class="fa fa-envelope-o"></i> 0</button>';
  var label=count>10?'+10':String(count);
  return '<button type="button" class="op-message-badge hot" data-ext="'+esc(ext)+'" title="Marcar mensagens do ramal como lidas"><i class="fa fa-envelope"></i> '+esc(label)+'</button>';
}
function render(){
  var map={}; state.hints.forEach(function(h){if(h.Hint)map[normalizeHint(h.Hint)]=h;});
  var msgMap={}; state.messages.forEach(function(m){msgMap[String(m.extension)]=m;});
  var list=state.extensions.map(function(e){var ext=String(e.extension);var h=map[ext]||{};return {ext:ext,name:e.name||'',h:h,m:msgMap[ext]||{}};});
  if(!list.length)state.hints.forEach(function(h){var ext=normalizeHint(h.Hint);list.push({ext:ext,name:'',h:h,m:msgMap[ext]||{}});});
  var q=String($('#op-search').val()||'').toLowerCase();
  list=list.filter(function(x){return (String(x.ext)+' '+x.name).toLowerCase().indexOf(q)>=0;});
  $('#stat-total').text(list.length);

  var online=0,html='';
  list.forEach(function(x){
    var st=statusForExtension(x.ext,x.h);
    if(st[0]==='online')online++;
    var ep=endpointForExtension(x.ext);
    var tech=hasEndpointContact(ep)?'PJSIP registado':(x.h.Devices||x.h.Presence||'Sem dispositivo registado');
    html+='<div class="op-extension is-'+st[0]+'">'+
      '<div class="op-ext-top">'+
        '<div class="op-ext-number">'+esc(x.ext)+'</div>'+
        '<div style="min-width:0;flex:1"><div class="op-ext-name">'+esc(x.name||('Ramal '+x.ext))+'</div>'+
        '<div class="op-ext-tech">'+esc(tech)+'</div></div>'+
      '</div>'+
      '<div class="op-ext-status">'+
        '<span class="op-state"><span class="op-dot '+st[1]+'"></span>'+esc(st[2])+'</span>'+
        messageLabel(x.m,x.ext)+
      '</div>'+
    '</div>';
  });
  $('#stat-online').text(online);
  var totalMessages=0;
  state.messages.forEach(function(m){
    totalMessages += parseInt(m.message_count||0,10);
  });
  $('#op-message-total').text(totalMessages>10?'+10':totalMessages);
  $('#op-extensions').html(html||'<div class="op-empty">Nenhum ramal configurado no FreePBX Core.</div>');

  $('#stat-calls,#calls-badge').text(state.channels.length);
  html='';
  state.channels.forEach(function(c){
    var ch=c.Channel||'', from=c.CallerIDNum||c.CallerIDName||'Chamada', to=c.Exten||c.ConnectedLineNum||'';
    var app=c.Application||'', data=c.ApplicationData||'';
    html+='<div class="op-row"><div class="op-avatar"><i class="fa fa-phone"></i></div><div class="op-main"><b>'+esc(from)+' → '+esc(to)+'</b><div class="op-meta">'+esc(ch)+(app?' · '+esc(app):'')+(data?' · '+esc(data):'')+'</div></div><button class="btn btn-xs btn-danger op-hangup" data-channel="'+esc(ch)+'">Desligar</button></div>';
  });
  $('#op-calls').html(html||'<div class="op-empty">Nenhuma chamada ativa.</div>');

  var conferences=state.conferences||[];
  $('#conference-badge').text(conferences.length);
  html='';
  conferences.forEach(function(c){
    var active=parseInt(c.parties||0,10)>0;
    html+='<div class="op-conference '+(active?'is-active':'')+'">'+
      '<div class="op-conf-top"><div><div class="op-conf-name">'+esc(c.label||('Conferência '+c.conference))+'</div><div class="op-conf-number">Sala '+esc(c.conference)+'</div></div>'+
      '<div class="op-conf-state '+(active?'green':'gray')+'">'+(active?'● Em andamento':'● Disponível')+'</div></div>'+
      '<div class="op-conf-parties"><i class="fa fa-users"></i> '+esc(active?String(c.parties)+' participante(s)':'0 participantes')+'</div>'+
      '</div>';
  });
  $('#op-conferences').html(html||'<div class="op-empty">Nenhuma conferência registada.</div>');
}
function refresh(){
  return api('dashboard').done(function(r){
    if(!r||r.status===false)throw new Error(r&&r.message?r.message:'Resposta inválida');
    var d=r.data||{};
    state.extensions=d.extensions||[];
    state.hints=d.hints||[];
    state.endpoints=d.endpoints||[];
    state.channels=d.channels||[];
    state.conferences=d.conferences||[];
    state.messages=d.messages||[];
    render();
    alertBox(true,'Asterisk AMI ligado. Ramais e conferências sincronizados com o FreePBX/Asterisk.');
    $('#op-alert').append(' <small>'+new Date().toLocaleTimeString()+'</small>');
  }).fail(function(xhr){
    var m=(xhr.responseJSON&&xhr.responseJSON.message)?xhr.responseJSON.message:'Falha no AJAX do módulo. HTTP '+xhr.status;
    alertBox(false,m);
  });
}
function operation(cmd){
  var ch=$.trim($('#op-channel').val()), ex=$.trim($('#op-destination').val());
  if(!ch||!ex){alertBox(false,'Preencha canal/origem e destino.');return;}
  api(cmd,{channel:ch,exten:ex}).done(function(r){if(r&&r.status!==false){alertBox(true,'Comando enviado ao Asterisk.');refresh();}else alertBox(false,r.message||'Operação recusada.');}).fail(function(xhr){alertBox(false,(xhr.responseJSON&&xhr.responseJSON.message)||'Falha na operação.');});
}
$(function(){
  $('#op-refresh').on('click',refresh);
  $('#op-search').on('input',render);
  $('#op-originate').on('click',function(){operation('originate');});
  $('#op-redirect').on('click',function(){operation('redirect');});
  $('#op-dial').on('click','.op-key',function(){$('#op-destination').val($('#op-destination').val()+$(this).data('key'));});
  $('#op-calls').on('click','.op-hangup',function(){
    var ch=$(this).data('channel');
    api('hangup',{channel:ch}).done(function(){refresh();}).fail(function(xhr){alertBox(false,(xhr.responseJSON&&xhr.responseJSON.message)||'Falha ao desligar.');});
  });
  $('#op-extensions').on('click','.op-message-badge',function(){
    var ext=String($(this).data('ext')||'');
    if(!ext)return;
    api('clear_messages',{extension:ext}).done(function(r){
      if(r&&r.status!==false){
        alertBox(true,'Notificações do ramal '+ext+' marcadas como lidas.');
        refresh();
      }else alertBox(false,(r&&r.message)||'Não foi possível limpar as notificações.');
    }).fail(function(xhr){
      alertBox(false,(xhr.responseJSON&&xhr.responseJSON.message)||'Falha ao limpar notificações.');
    });
  });
  $('#op-messages-top').on('click',function(){
    var pending=[];
    $('#op-extensions .op-message-badge.hot').each(function(){
      var ext=String($(this).data('ext')||'');
      if(ext && pending.indexOf(ext)<0)pending.push(ext);
    });
    if(!pending.length){alertBox(true,'Não existem notificações de mensagens pendentes.');return;}
    var requests=pending.map(function(ext){return api('clear_messages',{extension:ext});});
    $.when.apply($,requests).done(function(){refresh();alertBox(true,'Notificações das mensagens recebidas foram limpas.');})
      .fail(function(){alertBox(false,'Não foi possível limpar todas as notificações.');refresh();});
  });
  refresh();
  state.timer=setInterval(refresh,3000);
});
})(jQuery);
</script>
