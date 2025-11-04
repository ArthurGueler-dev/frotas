# 🕐 Configuração do Agendador de Tarefas do Windows

Este guia mostra como configurar a atualização automática diária de quilometragem usando o Agendador de Tarefas do Windows.

## 📋 Pré-requisitos

- ✅ Node.js instalado
- ✅ Servidor FleetFlow funcionando
- ✅ Proxy Ituran rodando (localhost:8888)
- ✅ Arquivos prontos:
  - `cron-update-km.js` (script de atualização)
  - `update-km-daily.bat` (arquivo de lote)
  - Pasta `logs` criada

## 🚀 Passo a Passo

### 1. Abrir o Agendador de Tarefas

1. Pressione `Win + R`
2. Digite: `taskschd.msc`
3. Pressione Enter

### 2. Criar Nova Tarefa

1. No painel direito, clique em **"Criar Tarefa..."** (não "Criar Tarefa Básica")
2. Preencha a aba **"Geral"**:
   - **Nome**: `FleetFlow - Atualização de Quilometragem`
   - **Descrição**: `Atualiza dados de quilometragem diária de todos os veículos da frota`
   - **Opções de segurança**:
     - ☑ Executar estando o usuário conectado ou não
     - ☐ Executar com privilégios mais altos (não necessário)
     - ☑ Oculto (opcional)

### 3. Configurar Gatilho (Quando Executar)

1. Vá para a aba **"Gatilhos"**
2. Clique em **"Novo..."**
3. Configure:
   - **Iniciar a tarefa**: Segundo um agendamento
   - **Configurações**: Diariamente
   - **Iniciar**: Escolha uma data de início (hoje)
   - **Hora**: `00:30:00` (meia-noite e meia)
   - **Recorrência**: A cada 1 dias
   - **Ativado**: ☑
4. Clique em **OK**

### 4. Configurar Ação (O Que Executar)

1. Vá para a aba **"Ações"**
2. Clique em **"Novo..."**
3. Configure:
   - **Ação**: Iniciar um programa
   - **Programa/script**: `C:\Users\SAMSUNG\Desktop\frotas\update-km-daily.bat`
   - **Iniciar em (opcional)**: `C:\Users\SAMSUNG\Desktop\frotas`
4. Clique em **OK**

### 5. Configurar Condições

1. Vá para a aba **"Condições"**
2. Configure:
   - **Energia**:
     - ☐ Iniciar tarefa apenas se o computador estiver usando alimentação CA (desmarcar)
     - ☑ Interromper se o computador alternar para alimentação da bateria (opcional)
   - **Rede**:
     - ☐ Iniciar apenas se a seguinte conexão de rede estiver disponível (desmarcar)

### 6. Configurar Configurações

1. Vá para a aba **"Configurações"**
2. Configure:
   - ☑ Permitir que a tarefa seja executada sob demanda
   - ☑ Executar tarefa assim que possível após uma inicialização agendada ter sido perdida
   - ☐ Se a tarefa falhar, reiniciar a cada: (desmarcar)
   - ☑ Interromper a tarefa se ela estiver sendo executada por mais de: `1 hora`
   - **Se a tarefa já estiver em execução, aplicar a seguinte regra**: Não iniciar uma nova instância

### 7. Salvar e Testar

1. Clique em **OK** para salvar a tarefa
2. Digite sua senha do Windows se solicitado

## 🧪 Testar a Tarefa

### Teste Manual

Na lista de tarefas, encontre "FleetFlow - Atualização de Quilometragem":

1. Clique com botão direito
2. Selecione **"Executar"**
3. Aguarde a execução

### Verificar Logs

Abra o arquivo de log para ver os resultados:

```
C:\Users\SAMSUNG\Desktop\frotas\logs\km-updates.log
```

Você verá algo como:

```
═══════════════════════════════════════════════════════════
📊 Iniciando atualização automática de quilometragem
═══════════════════════════════════════════════════════════
🕐 Horário: 03/11/2025, 00:30:00

📅 Atualizando dados de: 2025-11-02

✅ ATUALIZAÇÃO CONCLUÍDA COM SUCESSO!

📊 Total de veículos: 10
✅ Sucessos: 10
❌ Falhas: 0
```

## 📊 Monitoramento

### Ver Histórico de Execuções

1. No Agendador de Tarefas, selecione a tarefa
2. Na parte inferior, clique na aba **"Histórico"**
3. Você verá todas as execuções com:
   - Data/hora de execução
   - Resultado (sucesso/falha)
   - Código de retorno

### Verificar Status

- **Última execução**: Mostrado na lista de tarefas
- **Próxima execução**: Mostrado na lista de tarefas
- **Status**: Pronto / Em execução / Desabilitado

## 🔧 Solução de Problemas

### A tarefa não executa

1. **Verificar privilégios**: A tarefa precisa das permissões corretas
2. **Verificar caminhos**: Confirme que todos os caminhos estão corretos
3. **Verificar Node.js**: Teste manualmente: `node -v`
4. **Verificar proxy**: O proxy Ituran precisa estar rodando

### Logs não são gerados

1. Verificar se a pasta `logs` existe
2. Verificar permissões de escrita na pasta
3. Executar manualmente: `update-km-daily.bat`

### Tarefa falha sempre

1. Abrir o arquivo de log: `logs\km-updates.log`
2. Procurar por erros
3. Verificar se o proxy Ituran está rodando: `http://localhost:8888`
4. Verificar conexão com o banco MySQL

## 🎯 Dicas

### Alterar Horário de Execução

1. Abra a tarefa (duplo clique)
2. Vá para aba **"Gatilhos"**
3. Edite o gatilho existente
4. Altere a hora desejada
5. Clique em OK

### Desabilitar Temporariamente

1. Clique com botão direito na tarefa
2. Selecione **"Desabilitar"**

### Reabilitar

1. Clique com botão direito na tarefa
2. Selecione **"Habilitar"**

### Excluir Tarefa

1. Clique com botão direito na tarefa
2. Selecione **"Excluir"**
3. Confirme

## ✅ Verificação Final

Após configurar, verifique:

- [ ] Tarefa criada e aparece na lista
- [ ] Próxima execução agendada está correta (00:30)
- [ ] Teste manual executou com sucesso
- [ ] Log foi gerado em `logs\km-updates.log`
- [ ] Dados foram salvos no banco (verifique no dashboard)

## 📞 Suporte

Se tiver problemas:

1. Verifique o arquivo de log: `logs\km-updates.log`
2. Execute manualmente: `node cron-update-km.js`
3. Verifique o proxy: `http://localhost:8888`
4. Consulte o arquivo `INTEGRA_QUILOMETRAGEM.md`

---

**Última atualização:** 03/11/2025
