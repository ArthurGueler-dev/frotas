<?php
/**
 * Script para criar tabelas de Perfis e Permissões
 *
 * Tabelas criadas:
 * - FF_Perfis: Perfis de acesso (Admin, Operador, Visualizador)
 * - FF_Perfil_Permissoes: Permissões por página para cada perfil
 * - Altera FF_Users para adicionar coluna perfil_id
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$host = '187.49.226.10';
$dbname = 'f137049_in9aut';
$username = 'f137049_tool';
$password = 'In9@1234qwer';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $resultado = array('success' => true, 'etapas' => array());

    // 1. Criar tabela FF_Perfis
    $sql = "CREATE TABLE IF NOT EXISTS FF_Perfis (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(50) NOT NULL UNIQUE,
        descricao VARCHAR(255),
        ativo TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    $pdo->exec($sql);
    $resultado['etapas'][] = 'Tabela FF_Perfis criada/verificada';

    // 2. Criar tabela FF_Perfil_Permissoes
    $sql = "CREATE TABLE IF NOT EXISTS FF_Perfil_Permissoes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        perfil_id INT NOT NULL,
        pagina VARCHAR(50) NOT NULL,
        pode_acessar TINYINT(1) DEFAULT 0,
        pode_editar TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (perfil_id) REFERENCES FF_Perfis(id) ON DELETE CASCADE,
        UNIQUE KEY unique_perfil_pagina (perfil_id, pagina)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    $pdo->exec($sql);
    $resultado['etapas'][] = 'Tabela FF_Perfil_Permissoes criada/verificada';

    // 3. Adicionar coluna perfil_id em FF_Users (se não existir)
    $stmt = $pdo->query("SHOW COLUMNS FROM FF_Users LIKE 'perfil_id'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE FF_Users ADD COLUMN perfil_id INT DEFAULT NULL");
        $pdo->exec("ALTER TABLE FF_Users ADD FOREIGN KEY (perfil_id) REFERENCES FF_Perfis(id) ON DELETE SET NULL");
        $resultado['etapas'][] = 'Coluna perfil_id adicionada em FF_Users';
    } else {
        $resultado['etapas'][] = 'Coluna perfil_id já existe em FF_Users';
    }

    // 4. Inserir perfis padrão (se não existirem)
    $perfis = array(
        array('nome' => 'Administrador', 'descricao' => 'Acesso total a todas as funcionalidades do sistema'),
        array('nome' => 'Operador', 'descricao' => 'Acesso operacional: veículos, manutenção, rotas'),
        array('nome' => 'Visualizador', 'descricao' => 'Apenas visualização de dados, sem edição')
    );

    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM FF_Perfis WHERE nome = ?");
    $stmtInsert = $pdo->prepare("INSERT INTO FF_Perfis (nome, descricao) VALUES (?, ?)");

    foreach ($perfis as $perfil) {
        $stmtCheck->execute(array($perfil['nome']));
        if ($stmtCheck->fetchColumn() == 0) {
            $stmtInsert->execute(array($perfil['nome'], $perfil['descricao']));
            $resultado['etapas'][] = "Perfil '{$perfil['nome']}' criado";
        }
    }

    // 5. Definir páginas do sistema
    $paginas = array(
        'dashboard' => 'Dashboard',
        'veiculos' => 'Veículos',
        'motoristas' => 'Motoristas',
        'manutencao' => 'Manutenção',
        'lancar-os' => 'Lançar OS',
        'planos-manutencao' => 'Planos de Manutenção',
        'pecas' => 'Peças',
        'servicos' => 'Serviços',
        'modelos' => 'Modelos',
        'rotas' => 'Rotas',
        'otimizador' => 'Otimizador de Rotas',
        'relatorios' => 'Relatórios',
        'usuarios' => 'Usuários',
        'configuracoes' => 'Configurações'
    );

    // 6. Inserir permissões padrão para cada perfil
    $stmtPerfil = $pdo->query("SELECT id, nome FROM FF_Perfis");
    $perfisCriados = $stmtPerfil->fetchAll(PDO::FETCH_ASSOC);

    $stmtCheckPerm = $pdo->prepare("SELECT COUNT(*) FROM FF_Perfil_Permissoes WHERE perfil_id = ? AND pagina = ?");
    $stmtInsertPerm = $pdo->prepare("INSERT INTO FF_Perfil_Permissoes (perfil_id, pagina, pode_acessar, pode_editar) VALUES (?, ?, ?, ?)");

    foreach ($perfisCriados as $perfil) {
        foreach ($paginas as $paginaKey => $paginaNome) {
            $stmtCheckPerm->execute(array($perfil['id'], $paginaKey));
            if ($stmtCheckPerm->fetchColumn() == 0) {
                // Definir permissões baseado no perfil
                $podeAcessar = 0;
                $podeEditar = 0;

                if ($perfil['nome'] === 'Administrador') {
                    $podeAcessar = 1;
                    $podeEditar = 1;
                } elseif ($perfil['nome'] === 'Operador') {
                    // Operador: acesso a tudo exceto usuários e configurações
                    $paginasOperador = array('dashboard', 'veiculos', 'motoristas', 'manutencao', 'lancar-os',
                                              'planos-manutencao', 'pecas', 'servicos', 'modelos', 'rotas', 'otimizador', 'relatorios');
                    $podeAcessar = in_array($paginaKey, $paginasOperador) ? 1 : 0;
                    $podeEditar = in_array($paginaKey, $paginasOperador) ? 1 : 0;
                } elseif ($perfil['nome'] === 'Visualizador') {
                    // Visualizador: apenas visualização (sem edição)
                    $paginasVisualizador = array('dashboard', 'veiculos', 'motoristas', 'manutencao', 'relatorios');
                    $podeAcessar = in_array($paginaKey, $paginasVisualizador) ? 1 : 0;
                    $podeEditar = 0;
                }

                $stmtInsertPerm->execute(array($perfil['id'], $paginaKey, $podeAcessar, $podeEditar));
            }
        }
        $resultado['etapas'][] = "Permissões configuradas para perfil '{$perfil['nome']}'";
    }

    // 7. Atribuir perfil Administrador ao usuário admin (id=1)
    $pdo->exec("UPDATE FF_Users SET perfil_id = (SELECT id FROM FF_Perfis WHERE nome = 'Administrador' LIMIT 1) WHERE id = 1");
    $resultado['etapas'][] = 'Perfil Administrador atribuído ao usuário admin';

    // Listar perfis criados
    $stmt = $pdo->query("SELECT p.*,
                         (SELECT COUNT(*) FROM FF_Perfil_Permissoes pp WHERE pp.perfil_id = p.id AND pp.pode_acessar = 1) as total_paginas
                         FROM FF_Perfis p ORDER BY p.id");
    $resultado['perfis'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Listar páginas do sistema
    $resultado['paginas_sistema'] = $paginas;

    echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
?>
