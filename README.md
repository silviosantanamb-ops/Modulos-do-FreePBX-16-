# Operator Panel FreePBX 16 v16.3.3

Melhorias desta versão:
- Ramais apresentados em cartões retangulares responsivos, em grelha horizontal.
- Estado dos ramais por AMI/CoreShowHints + canais ativos:
  - verde = disponível;
  - laranja = em comunicação/a tocar;
  - roxo = ocupado;
  - vermelho = indisponível.
- Pesquisa por número ou nome.
- Filas removidas da interface.
- Conferências ConfBridge monitorizadas por AMI `ConfbridgeListRooms`.
- Conferências já detetadas ficam registadas no painel e passam para cinza/Disponível quando ficam sem participantes.
- Contagem de participantes da conferência.
- Área de notificações de mensagens em cada ramal e contador global no ícone Mensagens.
- Contador de mensagens: 1, 2, ... e `+10`.
- Cada notificação pode ser marcada como lida pelo operador; a ação `clear_messages` também fica disponível para integração com um módulo de mensagens.
- Estrutura de banco incluída no module.xml para conferências e contadores de mensagens.
- Ponto de integração PHP público `recordMessage($extension, $type)` para o futuro módulo de mensagens.
- Mantidas as operações Ligar, Transferir e Desligar.
- `doConfigPageInit()` permanece implementado.

Instalação/upgrade:
```bash
fwconsole ma upgrade control_panel
fwconsole reload
```

Se o FreePBX não aplicar automaticamente as novas tabelas do module.xml, desinstale/reinstale o módulo somente depois de fazer backup. Não apague manualmente as tabelas existentes.

Observação sobre mensagens:
o painel já possui a estrutura de contagem, mas o recebimento real de SMS/mensagens depende do módulo/serviço que futuramente receberá as mensagens. O painel não inventa mensagens; ele apenas reflete os contadores armazenados.
