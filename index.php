<?php
/**
 * ============================================================================
 * INTERFACE WEB DO COMPILADOR
 * ----------------------------------------------------------------------------
 * APENAS EXIBIÇÃO - TODA LÓGICA ESTÁ NAS CLASSES
 * 
 * Responsabilidades:
 * - Receber entrada do usuário
 * - Chamar classes de análise
 * - Exibir resultados
 * ============================================================================
 */

require_once("analisadorLexico.php");
require_once("AnalisadorSintaticoSLR.php");

$codigoFonte = "";
$tokens = [];
$errosLexicos = [];
$resultadoSLR = null;
$parser = null;
$mensagem = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $codigoFonte = trim($_POST["codigo"] ?? "");

    if ($codigoFonte === "") {
        $mensagem = "Digite uma expressão para ser analisada.";
    } else {
        try {
            // ====== FASE 1: ANÁLISE LÉXICA ======
            $lexico = new AnalisadorLexico();
            $lexico->analisar($codigoFonte);
            $tokens = $lexico->getTokens();
            $errosLexicos = $lexico->getErros();

            // ====== FASE 2: ANÁLISE SINTÁTICA + SEMÂNTICA ======
            if (empty($errosLexicos)) {
                $parser = new AnalisadorSintaticoSLR();
                
                // Passa tokens do léxico - CLASSE FAZ TODO O TRABALHO
                $parser->setTokensDoLexico($tokens);
                
                // Executa análise - CLASSE INTEGRA AUTOMÁTICO COM SEMÂNTICO
                $resultadoSLR = $parser->analisar();
            }
        } catch (Exception $e) {
            $mensagem = "⚠ Erro: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Compilador Básico PHP - Analisador SLR</title>
<style>
body { 
    font-family: Arial, sans-serif; 
    margin: 20px; 
    color: #333;
    background: #f5f5f5;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    background: white;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

h1 { 
    color: #2c3e50;
    border-bottom: 3px solid #3498db;
    padding-bottom: 10px;
    margin-bottom: 20px;
}

h2 { 
    color: #34495e;
    margin-top: 30px;
    margin-bottom: 15px;
    border-left: 4px solid #3498db;
    padding-left: 10px;
}

.form-group {
    margin-bottom: 15px;
}

textarea { 
    width: 100%; 
    min-height: 150px;
    padding: 10px; 
    font-family: 'Courier New', monospace; 
    font-size: 14px;
    border: 2px solid #ddd;
    border-radius: 4px;
    box-sizing: border-box;
}

textarea:focus {
    outline: none;
    border-color: #3498db;
}

button { 
    background: #3498db;
    color: white;
    padding: 10px 30px; 
    margin-top: 10px; 
    cursor: pointer;
    border: none;
    border-radius: 4px;
    font-size: 16px;
    font-weight: bold;
    transition: background 0.3s;
}

button:hover {
    background: #2980b9;
}

pre { 
    background: #f8f9fa; 
    padding: 15px; 
    overflow-x: auto;
    border-left: 4px solid #3498db;
    border-radius: 4px;
    font-size: 13px;
}

table { 
    border-collapse: collapse; 
    width: 100%; 
    margin-top: 15px;
    font-size: 12px;
}

th, td { 
    border: 1px solid #ddd; 
    padding: 8px; 
    text-align: center; 
}

th { 
    background: #34495e; 
    color: white;
    font-weight: bold;
}

tr:nth-child(even) {
    background: #f9f9f9;
}

.msg { 
    padding: 15px;
    margin: 15px 0;
    border-radius: 4px;
    font-weight: bold;
}

.msg.error {
    background: #fee;
    color: #c33;
    border-left: 4px solid #c33;
}

.msg.success {
    background: #efe;
    color: #373;
    border-left: 4px solid #373;
}

.section {
    margin-top: 30px;
    padding: 20px;
    background: #fafafa;
    border-radius: 6px;
}

.table-wrapper {
    overflow-x: auto;
}

.tabela-simbolos {
    background: #fff;
    padding: 15px;
    border-radius: 4px;
    border: 2px solid #3498db;
}

.tabela-simbolos table {
    width: auto;
    min-width: 300px;
}

.tabela-simbolos th {
    background: #3498db;
}

.exemplo {
    background: #e8f4f8;
    padding: 10px;
    border-radius: 4px;
    margin-top: 10px;
    font-size: 13px;
}
</style>
</head>
<body>
<div class="container">
    <h1>🔧 Compilador Básico PHP - Analisador SLR</h1>

    <form method="post">
        <div class="form-group">
            <label for="codigo"><strong>Digite seu código:</strong></label>
            <textarea name="codigo" id="codigo" placeholder="Exemplo: int x; x = 10 + 5;"><?php echo htmlspecialchars($codigoFonte); ?></textarea>
            <div class="exemplo">
                <strong>💡 Exemplos de código válido:</strong><br>
                • <code>int x;</code><br>
                • <code>int idade; idade = 25;</code><br>
                • <code>char letra; bool ativo;</code>
            </div>
        </div>
        <button type="submit">▶ Analisar</button>
    </form>

    <?php if ($mensagem): ?>
        <div class="msg error"><?php echo $mensagem; ?></div>
    <?php endif; ?>

    <?php if ($codigoFonte !== ""): ?>
        <!-- ============================================ -->
        <!-- SEÇÃO 1: TOKENS RECONHECIDOS -->
        <!-- ============================================ -->
        <div class="section">
            <h2>📋 Tokens Reconhecidos</h2>
            <pre><?php 
                if (!empty($tokens)) {
                    foreach ($tokens as $token) {
                        echo $token . "\n";
                    }
                } else {
                    echo "Nenhum token reconhecido.";
                }
            ?></pre>
        </div>

        <!-- ============================================ -->
        <!-- SEÇÃO 2: ERROS LÉXICOS (SE HOUVER) -->
        <!-- ============================================ -->
        <?php if (!empty($errosLexicos)): ?>
            <div class="section">
                <h2>❌ Erros Léxicos</h2>
                <pre><?php echo implode("\n", $errosLexicos); ?></pre>
            </div>
        <?php endif; ?>

        <!-- ============================================ -->
        <!-- SEÇÃO 3: RESULTADO SINTÁTICO -->
        <!-- ============================================ -->
        <?php if ($resultadoSLR !== null): ?>
            <div class="section">
                <h2>🔍 Resultado Sintático (SLR)</h2>
                <div class="msg <?php echo $resultadoSLR['success'] ? 'success' : 'error'; ?>">
                    <?php echo ($resultadoSLR['success'] ? "✓ " : "✗ ") . $resultadoSLR['message']; ?>
                </div>
                <pre><?php
                    echo "Ações realizadas:\n";
                    foreach ($resultadoSLR['actions'] as $a) {
                        echo "  - $a\n";
                    }
                ?></pre>
            </div>
        <?php endif; ?>

        <!-- ============================================ -->
        <!-- SEÇÃO 4: TABELA DE SÍMBOLOS -->
        <!-- ============================================ -->
        <?php if ($parser !== null): ?>
            <div class="section tabela-simbolos">
                <h2>📊 Tabela de Símbolos (Semântico)</h2>
                <?php 
                    $tabela = $parser->getSemantico()->getTabelaSimbolos();
                    if (empty($tabela)) {
                        echo "<p><em>Nenhuma variável declarada.</em></p>";
                    } else {
                        echo "<table>";
                        echo "<tr><th>Variável</th><th>Tipo</th></tr>";
                        foreach ($tabela as $var => $tipo) {
                            echo "<tr><td><strong>{$var}</strong></td><td>{$tipo}</td></tr>";
                        }
                        echo "</table>";
                    }
                ?>
            </div>
        <?php endif; ?>

        <!-- ============================================ -->
        <!-- SEÇÃO 5: TABELA SLR (ACTION/GOTO) -->
        <!-- ============================================ -->
        <?php if ($parser !== null): ?>
            <div class="section">
                <h2>📑 Tabela SLR (ACTION / GOTO)</h2>
                <div class="table-wrapper">
                    <table>
                        <tr>
                            <th>Estado</th>
                            <?php 
                            $terminals = $parser->getTerminals();
                            $nonTerminals = $parser->getNonTerminals();
                            $ACTION = $parser->getActionTable();
                            $GOTO = $parser->getGotoTable();

                            foreach ($terminals as $t): ?>
                                <th><?php echo htmlspecialchars($t); ?></th>
                            <?php endforeach; ?>
                            <?php foreach ($nonTerminals as $nt): ?>
                                <th style="background: #9b59b6; color: white;"><?php echo htmlspecialchars($nt); ?></th>
                            <?php endforeach; ?>
                        </tr>
                        <?php foreach ($ACTION as $estado => $acoes): ?>
                            <tr>
                                <td><strong><?php echo $estado; ?></strong></td>
                                <?php foreach ($terminals as $t): ?>
                                    <td><?php
                                    if (isset($acoes[$t])) {
                                        $a = $acoes[$t];
                                        echo $a[0] === 'shift' ? "s{$a[1]}" :
                                             ($a[0] === 'reduce' ? "r{$a[1]}" :
                                             ($a[0] === 'accept' ? "acc" : "-"));
                                    } else {
                                        echo "-";
                                    }
                                    ?></td>
                                <?php endforeach; ?>
                                <?php foreach ($nonTerminals as $nt): ?>
                                    <td><?php echo $GOTO[$estado][$nt] ?? "-"; ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>

            <!-- ============================================ -->
            <!-- SEÇÃO 6: GRAMÁTICA -->
            <!-- ============================================ -->
            <div class="section">
                <h2>📖 Gramática</h2>
                <pre><?php
                    $gramatica = $parser->getGramatica();
                    foreach ($gramatica as $regra) {
                        echo $regra . "\n";
                    }
                ?></pre>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

</body>
</html>