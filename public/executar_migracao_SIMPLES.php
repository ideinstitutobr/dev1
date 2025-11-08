<?php
/**
 * Script SIMPLIFICADO de Migração
 * Execute este arquivo para migrar de lojas para unidades
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/classes/Database.php';

echo "<h1>Migração: Lojas para Unidades</h1>";
echo "<hr>";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    echo "<h2>Executando comandos SQL...</h2>";

    // Array de comandos SQL
    $comandos = [
        "1. Adicionar coluna unidade_id" =>
            "ALTER TABLE checklists ADD COLUMN unidade_id INT NULL AFTER id",

        "2. Remover foreign key antiga" =>
            "ALTER TABLE checklists DROP FOREIGN KEY IF EXISTS checklists_ibfk_1",

        "3. Remover coluna loja_id" =>
            "ALTER TABLE checklists DROP COLUMN IF EXISTS loja_id",

        "4. Tornar unidade_id obrigatório" =>
            "ALTER TABLE checklists MODIFY COLUMN unidade_id INT NOT NULL",

        "5. Adicionar foreign key" =>
            "ALTER TABLE checklists ADD CONSTRAINT fk_checklist_unidade
             FOREIGN KEY (unidade_id) REFERENCES unidades(id)
             ON DELETE RESTRICT ON UPDATE CASCADE",

        "6. Criar índice" =>
            "CREATE INDEX idx_unidade_id ON checklists(unidade_id)"
    ];

    foreach ($comandos as $descricao => $sql) {
        echo "<p><strong>$descricao:</strong> ";

        try {
            $pdo->exec($sql);
            echo "<span style='color: green;'>✅ OK</span></p>";
        } catch (PDOException $e) {
            $erro = $e->getMessage();

            // Ignorar erros comuns de "já existe"
            if (strpos($erro, 'Duplicate') !== false ||
                strpos($erro, 'check that it exists') !== false ||
                strpos($erro, "Can't DROP") !== false) {
                echo "<span style='color: orange;'>⚠️ Já existe (OK)</span></p>";
            } else {
                echo "<span style='color: red;'>❌ ERRO: " . htmlspecialchars($erro) . "</span></p>";
            }
        }
    }

    echo "<hr>";
    echo "<h2 style='color: green;'>✅ Migração Concluída!</h2>";
    echo "<p><a href='checklist/novo.php'>Testar: Criar Nova Avaliação</a></p>";
    echo "<p><a href='checklist/'>Ver Checklists</a></p>";

} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Erro Fatal</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
