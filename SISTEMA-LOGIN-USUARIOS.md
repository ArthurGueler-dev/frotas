# Sistema de Login e Cadastro de Usuários

**Data**: 2025-12-19
**Status**: ✅ Criado - Aguardando upload e testes

---

## 📁 ARQUIVOS CRIADOS

### 1. `cpanel-api/create-table-users.php`
**Função**: Script para criar a tabela `FF_Users` no banco de dados

**O que faz**:
- Cria tabela `FF_Users` com estrutura simplificada
- Cria usuário admin padrão (login: `admin`, senha: `admin123`)
- Mostra estrutura da tabela criada

**Como usar**:
1. Fazer upload para o cPanel em `/cpanel-api/`
2. Acessar: `https://floripa.in9automacao.com.br/create-table-users.php`
3. Verificar se tabela foi criada com sucesso

---

### 2. `cpanel-api/users-frotas-api.php`
**Função**: API completa de usuários

**Endpoints disponíveis**:

#### `POST /users-frotas-api.php?action=login`
Fazer login no sistema
```json
Request:
{
    "username": "admin",
    "password": "admin123"
}

Response (sucesso):
{
    "success": true,
    "token": "abc123...",
    "user": {
        "id": 1,
        "username": "admin",
        "full_name": "Administrador",
        "email": "admin@...",
        "user_type": "admin",
        "status": "ativo"
    }
}

Response (precisa criar senha):
{
    "success": false,
    "needs_password": true,
    "message": "Você precisa criar uma senha...",
    "user": {
        "id": 2,
        "username": "joao",
        "full_name": "João Silva"
    }
}
```

#### `POST /users-frotas-api.php?action=set_password`
Definir senha no primeiro acesso
```json
Request:
{
    "user_id": 2,
    "new_password": "minhasenha123"
}

Response:
{
    "success": true,
    "message": "Senha definida com sucesso!"
}
```

#### `POST /users-frotas-api.php?action=register`
Cadastrar novo usuário
```json
Request:
{
    "username": "joao.silva",
    "full_name": "João Silva",
    "email": "joao@exemplo.com",
    "password": "senha123",      // opcional
    "user_type": "usuario"       // admin ou usuario
}

Response:
{
    "success": true,
    "message": "Usuário cadastrado com sucesso",
    "user": {
        "id": 3,
        "username": "joao.silva",
        "full_name": "João Silva",
        "status": "ativo"  // ou "pendente" se não tiver senha
    }
}
```

#### `GET /users-frotas-api.php?action=list`
Listar todos os usuários
```json
Response:
{
    "success": true,
    "count": 2,
    "data": [
        {
            "id": 1,
            "username": "admin",
            "full_name": "Administrador",
            "email": "admin@...",
            "user_type": "admin",
            "status": "ativo",
            "last_login_at": "2025-12-19 14:30:00",
            "created_at": "2025-12-19 10:00:00"
        },
        ...
    ]
}
```

#### `PUT /users-frotas-api.php?id=2`
Atualizar usuário
```json
Request:
{
    "full_name": "João Pedro Silva",
    "email": "joaopedro@exemplo.com",
    "user_type": "admin",
    "status": "ativo"
}
```

#### `DELETE /users-frotas-api.php?id=3`
Deletar usuário (não permite deletar admin ID 1)

---

## 🗃️ ESTRUTURA DA TABELA FF_Users

```sql
CREATE TABLE FF_Users (
    id INT PRIMARY KEY AUTO_INCREMENT,

    -- Autenticação
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NULL,  -- NULL = precisa definir senha

    -- Dados Básicos
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NULL,

    -- Controle de Acesso
    user_type ENUM('admin', 'usuario') DEFAULT 'usuario',
    status ENUM('ativo', 'pendente', 'inativo') DEFAULT 'pendente',

    -- Informações de Login
    last_login_at DATETIME NULL,
    password_changed_at DATETIME NULL,

    -- Timestamps
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**Tipos de usuário**:
- `admin`: Acesso total ao sistema
- `usuario`: Acesso operacional

**Status**:
- `pendente`: Aguardando definir senha
- `ativo`: Operando normalmente
- `inativo`: Desativado

---

## 🔧 INTEGRAÇÃO COM O FRONTEND

### Arquivos que PRECISAM ser atualizados:

#### 1. `login.html`
**Mudanças necessárias**:

```javascript
// ANTES (b_veicular_auth.php):
fetch('https://floripa.in9automacao.com.br/b_veicular_auth.php', {
    body: JSON.stringify({
        acao: 'login',
        nome: username,
        senha: password
    })
})
if (result.sucesso) {
    sessionStorage.setItem('usuario_nome', result.usuario.nome);
    sessionStorage.setItem('usuario_tipo', result.usuario.tipo_usuario);
}

// DEPOIS (users-frotas-api.php):
fetch('https://floripa.in9automacao.com.br/users-frotas-api.php?action=login', {
    body: JSON.stringify({
        username: username,
        password: password
    })
})
if (result.success) {
    sessionStorage.setItem('usuario_nome', result.user.full_name);
    sessionStorage.setItem('usuario_tipo', result.user.user_type);
}
```

**Para definir senha**:
```javascript
// ANTES:
fetch('...b_veicular_auth.php', {
    body: JSON.stringify({
        acao: 'definir_senha',
        usuario_id: userId,
        nova_senha: password
    })
})

// DEPOIS:
fetch('...users-frotas-api.php?action=set_password', {
    body: JSON.stringify({
        user_id: userId,
        new_password: password
    })
})
```

#### 2. `auth.js`
**Já foi atualizado!** ✅
- Remove referência ao `login-frotas.php`
- Usa apenas `sessionStorage` para verificar autenticação
- Não faz chamadas de verificação ao servidor

---

## ✅ CHECKLIST DE DEPLOY

### Passo 1: Upload para cPanel
- [ ] Upload `create-table-users.php` para `/cpanel-api/`
- [ ] Upload `users-frotas-api.php` para `/cpanel-api/`

### Passo 2: Criar tabela no banco
- [ ] Acessar `https://floripa.in9automacao.com.br/create-table-users.php`
- [ ] Verificar mensagem de sucesso
- [ ] Anotar credenciais do admin (login: `admin`, senha: `admin123`)

### Passo 3: Testar API
```bash
# Testar login
curl -X POST https://floripa.in9automacao.com.br/users-frotas-api.php?action=login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123"}'

# Deve retornar:
# {"success":true,"token":"...","user":{...}}
```

### Passo 4: Atualizar Frontend
- [ ] Atualizar `login.html` conforme seção "Integração com Frontend"
- [ ] Upload `auth.js` atualizado (já pronto!)
- [ ] Upload `login.html` atualizado

### Passo 5: Testar Fluxo Completo
- [ ] Login com admin/admin123
- [ ] Cadastrar novo usuário SEM senha (via API)
- [ ] Tentar login com usuário novo (deve pedir criar senha)
- [ ] Criar senha
- [ ] Fazer login novamente
- [ ] Testar logout

---

## 📝 USUÁRIO PADRÃO

**Login**: `admin`
**Senha**: `admin123`
**Tipo**: admin

⚠️ **IMPORTANTE**: Alterar a senha do admin após primeiro login!

---

## 🔒 SEGURANÇA

✅ Senhas criptografadas com `bcrypt` (PHP `password_hash`)
✅ Prepared statements (proteção contra SQL injection)
✅ CORS headers configurados
✅ Validação de input em todos endpoints
✅ Não permite deletar usuário admin (ID 1)
✅ Senhas devem ter mínimo 4 caracteres

---

## 🚀 PRÓXIMOS PASSOS (OPCIONAL)

Depois que o sistema de login estiver funcionando:

1. **Criar tela de gestão de usuários** (`usuarios.html`)
   - Listar todos usuários
   - Cadastrar novos
   - Editar/Desativar
   - Resetar senhas

2. **Implementar permissões por módulo**
   - Controlar quem pode ver/editar cada área do sistema

3. **Adicionar auditoria**
   - Log de todas ações dos usuários
   - Tabela `FF_UserActivityLog`

4. **Melhorar segurança**
   - Limite de tentativas de login
   - Bloqueio após X tentativas incorretas
   - Tokens JWT com expiração
   - Sessões no banco com controle de expiração

---

## 📞 SUPORTE

Se encontrar algum problema:
1. Verificar logs do navegador (F12 → Console)
2. Verificar resposta da API no Network tab
3. Verificar se tabela foi criada: `SHOW TABLES LIKE 'FF_Users'`
4. Verificar se usuário admin existe: `SELECT * FROM FF_Users WHERE username='admin'`
