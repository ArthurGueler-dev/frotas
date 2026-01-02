<?php
/**
 * Interface simples para associar veículos às áreas
 *
 * Mostra todos os veículos e permite selecionar a área de cada um
 */

header('Content-Type: text/html; charset=utf-8');

// Conexão com banco
$host = '187.49.226.10';
$dbname = 'f137049_in9aut';
$username = 'f137049_tool';
$password = 'In9@1234qwer';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}

// Processar atualização se houver POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updates'])) {
    $updates = json_decode($_POST['updates'], true);
    $updated = 0;

    foreach ($updates as $licensePlate => $areaId) {
        $stmt = $pdo->prepare("UPDATE Vehicles SET area_id = ? WHERE LicensePlate = ?");
        $stmt->execute([$areaId ?: null, $licensePlate]);
        $updated++;
    }

    echo "<div style='background: #d4edda; color: #155724; padding: 15px; margin: 20px; border-radius: 5px;'>";
    echo "✅ <strong>$updated veículos atualizados com sucesso!</strong>";
    echo "</div>";
}

// Buscar áreas
$stmt = $pdo->query("SELECT id, name FROM areas WHERE is_active = 1 ORDER BY name");
$areas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar veículos
$stmt = $pdo->query("
    SELECT v.Id, v.LicensePlate, v.VehicleName, v.area_id, a.name as area_name
    FROM Vehicles v
    LEFT JOIN areas a ON v.area_id = a.id
    ORDER BY v.LicensePlate
");
$vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Associar Veículos às Áreas</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 8px 8px 0 0;
        }
        .header h1 { font-size: 28px; margin-bottom: 10px; }
        .header p { opacity: 0.9; font-size: 14px; }
        .content { padding: 30px; }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        .stat-card .number { font-size: 32px; font-weight: bold; color: #667eea; }
        .stat-card .label { font-size: 14px; color: #666; margin-top: 5px; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        thead {
            background: #f8f9fa;
            position: sticky;
            top: 0;
        }
        th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #dee2e6;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }
        tr:hover { background: #f8f9fa; }
        select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
        }
        select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        .actions {
            position: sticky;
            bottom: 0;
            background: white;
            padding: 20px 30px;
            border-top: 1px solid #dee2e6;
            text-align: right;
        }
        .changed { background: #fff3cd !important; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚗 Associar Veículos às Áreas</h1>
            <p>Selecione a área de cada veículo para habilitar relatórios regionais de quilometragem</p>
        </div>

        <div class="content">
            <div class="stats">
                <div class="stat-card">
                    <div class="number"><?= count($vehicles) ?></div>
                    <div class="label">Total de Veículos</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?php
                        $withArea = 0;
                        foreach ($vehicles as $v) {
                            if ($v['area_id']) $withArea++;
                        }
                        echo $withArea;
                    ?></div>
                    <div class="label">Com Área Definida</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?php
                        $withoutArea = 0;
                        foreach ($vehicles as $v) {
                            if (!$v['area_id']) $withoutArea++;
                        }
                        echo $withoutArea;
                    ?></div>
                    <div class="label">Sem Área Definida</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?= count($areas) ?></div>
                    <div class="label">Áreas Cadastradas</div>
                </div>
            </div>

            <!-- Barra de Pesquisa -->
            <div style="margin-bottom: 20px;">
                <input type="text"
                       id="searchInput"
                       placeholder="🔍 Pesquisar por placa ou nome do veículo..."
                       style="width: 100%; padding: 12px 20px; font-size: 16px; border: 2px solid #ddd; border-radius: 8px; transition: all 0.3s;"
                       onkeyup="filterTable()"
                       onfocus="this.style.borderColor='#667eea'; this.style.boxShadow='0 0 0 3px rgba(102, 126, 234, 0.1)'"
                       onblur="this.style.borderColor='#ddd'; this.style.boxShadow='none'">
                <div id="resultCount" style="margin-top: 10px; color: #666; font-size: 14px;"></div>
            </div>

            <form id="vehicleForm" method="POST">
                <input type="hidden" name="updates" id="updatesInput">

                <table id="vehiclesTable">
                    <thead>
                        <tr>
                            <th style="width: 120px;">Placa</th>
                            <th>Nome do Veículo</th>
                            <th style="width: 200px;">Área Atual</th>
                            <th style="width: 250px;">Nova Área</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vehicles as $vehicle): ?>
                        <tr data-plate="<?= htmlspecialchars($vehicle['LicensePlate']) ?>">
                            <td><strong><?= htmlspecialchars($vehicle['LicensePlate']) ?></strong></td>
                            <td><?= htmlspecialchars($vehicle['VehicleName'] ?: '-') ?></td>
                            <td>
                                <?php if ($vehicle['area_name']): ?>
                                    <span class="badge badge-success"><?= htmlspecialchars($vehicle['area_name']) ?></span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Não definida</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <select name="area_<?= htmlspecialchars($vehicle['LicensePlate']) ?>"
                                        data-original="<?= $vehicle['area_id'] ?>"
                                        onchange="markChanged(this)">
                                    <option value="">-- Selecione --</option>
                                    <?php foreach ($areas as $area): ?>
                                        <option value="<?= $area['id'] ?>"
                                                <?= $vehicle['area_id'] == $area['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($area['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </form>
        </div>

        <div class="actions">
            <button type="button" class="btn btn-primary" onclick="saveChanges()">
                💾 Salvar Alterações
            </button>
        </div>
    </div>

    <script>
        function markChanged(select) {
            const row = select.closest('tr');
            const original = select.dataset.original;
            const current = select.value;

            if (original != current) {
                row.classList.add('changed');
            } else {
                row.classList.remove('changed');
            }
        }

        function saveChanges() {
            const selects = document.querySelectorAll('select[name^="area_"]');
            const updates = {};
            let changedCount = 0;

            selects.forEach(select => {
                const plate = select.name.replace('area_', '');
                const original = select.dataset.original;
                const current = select.value;

                if (original != current) {
                    updates[plate] = current;
                    changedCount++;
                }
            });

            if (changedCount === 0) {
                alert('Nenhuma alteração foi feita.');
                return;
            }

            if (confirm(`Confirma a atualização de ${changedCount} veículo(s)?`)) {
                document.getElementById('updatesInput').value = JSON.stringify(updates);
                document.getElementById('vehicleForm').submit();
            }
        }

        function filterTable() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toUpperCase();
            const table = document.getElementById('vehiclesTable');
            const tbody = table.getElementsByTagName('tbody')[0];
            const rows = tbody.getElementsByTagName('tr');

            let visibleCount = 0;

            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const plate = row.cells[0].textContent || row.cells[0].innerText;
                const vehicleName = row.cells[1].textContent || row.cells[1].innerText;

                if (plate.toUpperCase().indexOf(filter) > -1 || vehicleName.toUpperCase().indexOf(filter) > -1) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            }

            // Atualizar contador de resultados
            const resultCount = document.getElementById('resultCount');
            if (filter === '') {
                resultCount.textContent = '';
            } else {
                resultCount.textContent = `📊 Mostrando ${visibleCount} de ${rows.length} veículos`;
            }
        }
    </script>
</body>
</html>
