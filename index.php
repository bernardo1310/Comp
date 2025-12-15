<?php
/**
 * ============================================================================
 * INTERFACE WEB DO COMPILADOR
 * ----------------------------------------------------------------------------
 * Responsabilidades:
 * - Receber entrada do usuário
 * - Chamar léxico e sintático/semântico
 * - Exibir resultados sem warnings/notices
 * ============================================================================
 */

require_once __DIR__ . "/analisadorLexico.php";
require_once __DIR__ . "/AnalisadorSintaticoSLR.php";

$codigoFonte   = "";
$tokens        = [];
$errosLexicos  = [];
$resultadoSLR  = null;
$parser        = null;
$mensagem      = "";

function h($s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

if (($_SERVER["REQUEST_METHOD"] ?? "") === "POST") {
    $codigoFonte = trim($_POST["codigo"] ?? "");

    if ($codigoFonte === "") {
        $mensagem = "Digite um código para ser analisado.";
    } else {
        try {
            // ====== FASE 1: LÉXICO ======
            $lexico = new AnalisadorLexico();
            $lexico->analisar($codigoFonte);

            $tokens       = $lexico->getTokens() ?? [];
            $errosLexicos = $lexico->getErros() ?? [];

            // ====== FASE 2: SINTÁTICO + SEMÂNTICO + GERAÇÃO ======
            if (empty($errosLexicos)) {
                $parser = new AnalisadorSintaticoSLR();
                $parser->setTokensDoLexico($tokens);
                $resultadoSLR = $parser->analisar();
            }
        } catch (Throwable $e) {
            $mensagem = "Erro: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Compilador PHP - SLR + Assembly MIPS</title>
<style>
body { font-family: Arial, sans-serif; margin: 20px; color:#333; background:#f5f5f5; }
.container { max-width:1200px; margin:0 auto; background:#fff; padding:30px; border-radius:8px; box-shadow:0 2px 10px rgba(0,0,0,0.1); }
h1 { color:#2c3e50; border-bottom:3px solid #3498db; padding-bottom:10px; margin-bottom:20px; }
h2 { color:#34495e; margin-top:30px; margin-bottom:15px; border-left:4px solid #3498db; padding-left:10px; }
.form-group { margin-bottom:15px; }
textarea { width:100%; min-height:160px; padding:10px; font-family:'Courier New', monospace; font-size:14px; border:2px solid #ddd; border-radius:4px; box-sizing:border-box; }
textarea:focus { outline:none; border-color:#3498db; }
button { background:#3498db; color:#fff; padding:10px 30px; margin-top:10px; cursor:pointer; border:none; border-radius:4px; font-size:16px; font-weight:bold; }
button:hover { background:#2980b9; }
pre { background:#f8f9fa; padding:15px; overflow-x:auto; border-left:4px solid #3498db; border-radius:4px; font-size:13px; white-space:pre; }
table { border-collapse:collapse; width:100%; margin-top:15px; font-size:12px; }
th, td { border:1px solid #ddd; padding:8px; text-align:center; }
th { background:#34495e; color:#fff; font-weight:bold; }
tr:nth-child(even){ background:#f9f9f9; }
.msg { padding:15px; margin:15px 0; border-radius:4px; font-weight:bold; }
.msg.error { background:#fee; color:#c33; border-left:4px solid #c33; }
.msg.success { background:#efe; color:#373; border-left:4px solid #373; }
.section { margin-top:30px; padding:20px; background:#fafafa; border-radius:6px; }
.table-wrapper { overflow-x:auto; }
.exemplo { background:#e8f4f8; padding:10px; border-radius:4px; margin-top:10px; font-size:13px; }
.badge { display:inline-block; padding:2px 8px; border-radius:999px; font-size:12px; margin-left:8px; background:#eee; }
.badge.ok { background:#e8ffe8; color:#2d7a2d; }
.badge.err { background:#ffe8e8; color:#a11; }
</style>
</head>
<body>
<div class="container">
    <h1>Compilador PHP - SLR + Gerador Assembly (MIPS/MARS)</h1>

    <form method="post">
        <div class="form-group">
            <label for="codigo"><strong>Digite seu código:</strong></label>
            <textarea name="codigo" id="codigo" placeholder="Ex: int x; x = 10 + 5;"><?php echo h($codigoFonte); ?></textarea>
            <div class="exemplo">
                <strong>Exemplos:</strong><br>
                • <code>int idade;</code><br>
                • <code>int idade; idade = 25;</code><br>
                • <code>int x; x = (10 + 2) * 3;</code><br>
                • <code>int x; if (x &gt; 0) { x = 1; }</code>
            </div>
        </div>
        <button type="submit">▶ Analisar</button>
    </form>

    <?php if ($mensagem): ?>
        <div class="msg error"><?php echo h($mensagem); ?></div>
    <?php endif; ?>

    <?php if ($codigoFonte !== ""): ?>

        <!-- ========================= TOKENS ========================= -->
        <div class="section">
            <h2>Tokens Reconhecidos</h2>
            <pre><?php
                if (!empty($tokens)) {
                    foreach ($tokens as $t) echo h($t) . "\n";
                } else {
                    echo "Nenhum token reconhecido.";
                }
            ?></pre>
        </div>

        <!-- ========================= ERROS LÉXICOS ========================= -->
        <?php if (!empty($errosLexicos)): ?>
            <div class="section">
                <h2>Erros Léxicos <span class="badge err">falhou</span></h2>
                <pre><?php echo h(implode("\n", $errosLexicos)); ?></pre>
            </div>
        <?php endif; ?>

        <!-- ========================= RESULTADO SLR ========================= -->
        <?php if ($resultadoSLR !== null): ?>
            <div class="section">
                <h2>Resultado Sintático (SLR)</h2>
                <div class="msg <?php echo !empty($resultadoSLR['success']) ? 'success' : 'error'; ?>">
                    <?php echo (!empty($resultadoSLR['success']) ? "✓ " : "✗ ") . h($resultadoSLR['message'] ?? ''); ?>
                </div>
                <pre><?php
                    echo "Ações realizadas:\n";
                    foreach (($resultadoSLR['actions'] ?? []) as $a) {
                        echo "  - " . h($a) . "\n";
                    }
                ?></pre>
            </div>
        <?php endif; ?>

        <!-- ========================= TABELA DE SÍMBOLOS ========================= -->
        <?php if ($parser !== null): ?>
            <div class="section">
                <h2>Tabela de Símbolos (Semântico)</h2>
                <?php
                    $tabela = $parser->getSemantico()->getTabelaSimbolos() ?? [];
                    if (empty($tabela)) {
                        echo "<p><em>Nenhuma variável declarada.</em></p>";
                    } else {
                        echo "<div class='table-wrapper'><table>";
                        echo "<tr><th>Variável</th><th>Tipo</th></tr>";
                        foreach ($tabela as $var => $tipo) {
                            echo "<tr><td><strong>" . h($var) . "</strong></td><td>" . h($tipo) . "</td></tr>";
                        }
                        echo "</table></div>";
                    }
                ?>
            </div>
        <?php endif; ?>

        <!-- ========================= ASSEMBLY GERADO ========================= -->
        <?php if ($parser !== null && !empty($resultadoSLR['success'])): ?>
            <div class="section">
                <h2>Código Gerado em Assembly (MIPS / MARS) <span class="badge ok">ok</span></h2>
                <pre><?php echo h($parser->getAssembly()); ?></pre>
            </div>
        <?php endif; ?>

        <!-- ========================= TABELA SLR ========================= -->
        <?php if ($parser !== null): ?>
            <div class="section">
                <h2>Tabela SLR (ACTION / GOTO)</h2>
                <div class="table-wrapper">
                    <table>
                        <tr>
                            <th>Estado</th>
                            <?php
                                $terminals    = $parser->getTerminals();
                                $nonTerminals = $parser->getNonTerminals();
                                $ACTION       = $parser->getActionTable();
                                $GOTO         = $parser->getGotoTable();

                                foreach ($terminals as $t) {
                                    echo "<th>" . h($t) . "</th>";
                                }
                                foreach ($nonTerminals as $nt) {
                                    echo '<th style="background:#9b59b6;color:white;">' . h($nt) . "</th>";
                                }
                            ?>
                        </tr>

                        <?php foreach ($ACTION as $estado => $acoesEstado): ?>
                            <tr>
                                <td><strong><?php echo h($estado); ?></strong></td>

                                <?php foreach ($terminals as $t): ?>
                                    <td><?php
                                        if (isset($acoesEstado[$t])) {
                                            $a = $acoesEstado[$t];
                                            echo $a[0] === 'shift' ? "s{$a[1]}" :
                                                 ($a[0] === 'reduce' ? "r{$a[1]}" :
                                                 ($a[0] === 'accept' ? "acc" : "-"));
                                        } else {
                                            echo "-";
                                        }
                                    ?></td>
                                <?php endforeach; ?>

                                <?php foreach ($nonTerminals as $nt): ?>
                                    <td><?php
                                        echo (isset($GOTO[$estado]) && isset($GOTO[$estado][$nt]))
                                            ? h($GOTO[$estado][$nt])
                                            : "-";
                                    ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>

            <!-- ========================= GRAMÁTICA ========================= -->
            <div class="section">
                <h2>Gramática</h2>
                <pre><?php
                    $gramatica = $parser->getGramatica() ?? [];
                    foreach ($gramatica as $regra) echo h($regra) . "\n";
                ?></pre>
            </div>
        <?php endif; ?>

    <?php endif; ?>
</div>
</body>
</html>
