<?php
/**
 * ============================================================================
 * EXEMPLO DE USO: Compilador Completo com Geração de Código MIPS
 * ----------------------------------------------------------------------------
 * Demonstra o uso completo do compilador, desde código-fonte até Assembly
 * ============================================================================
 */

require_once("IntegradorCompilador.php");

// ============================================================================
// EXEMPLO 1: Programa Simples com Declaração e Atribuição
// ============================================================================
echo "=== EXEMPLO 1: Declaração e Atribuição ===\n\n";

$codigo1 = "int x; x = 10;";

$compilador = new IntegradorCompilador();
$resultado = $compilador->compilar($codigo1);

if ($resultado['success']) {
    echo "✓ Compilação bem-sucedida!\n\n";
    echo "Código Assembly gerado:\n";
    echo "----------------------------------------\n";
    echo $resultado['codigo_assembly'];
    echo "\n----------------------------------------\n\n";
    
    // Salva em arquivo
    file_put_contents("exemplo1.asm", $resultado['codigo_assembly']);
    echo "✓ Código salvo em: exemplo1.asm\n\n";
} else {
    echo "✗ Erros encontrados:\n";
    foreach ($resultado['erros'] as $erro) {
        echo "  - $erro\n";
    }
}

// ============================================================================
// EXEMPLO 2: Operações Aritméticas
// ============================================================================
echo "\n=== EXEMPLO 2: Operações Aritméticas ===\n\n";

$codigo2 = "int a; int b; int resultado; a = 5; b = 3; resultado = a + b;";

$compilador2 = new IntegradorCompilador();
$resultado2 = $compilador2->compilar($codigo2);

if ($resultado2['success']) {
    echo "✓ Compilação bem-sucedida!\n\n";
    file_put_contents("exemplo2.asm", $resultado2['codigo_assembly']);
    echo "✓ Código salvo em: exemplo2.asm\n\n";
}

// ============================================================================
// EXEMPLO 3: Uso Direto do Gerador (sem parser completo)
// ============================================================================
echo "\n=== EXEMPLO 3: Uso Direto do Gerador ===\n\n";

// Simula tabela de símbolos
$tabelaSimbolos = [
    'x' => 'INT',
    'y' => 'INT',
    'soma' => 'INT'
];

$gerador = new GeradorCodigoMIPS($tabelaSimbolos);

// Gera variáveis globais
$gerador->gerarVariaveisGlobais();

// x = 10
$gerador->gerarAtribuicao('x', 10);

// y = 20
$gerador->gerarAtribuicao('y', 20);

// soma = x + y
$gerador->gerarAtribuicao('soma', ['+', 'x', 'y']);

// print(soma)
$gerador->gerarEscrita('soma');

// Finaliza
$gerador->gerarExit();

// Salva
$gerador->salvarArquivo("exemplo3.asm");

echo "✓ Código Assembly gerado e salvo em: exemplo3.asm\n\n";
echo "Código gerado:\n";
echo "----------------------------------------\n";
echo $gerador->getCodigoCompleto();
echo "\n----------------------------------------\n\n";

// ============================================================================
// EXEMPLO 4: Estrutura Condicional IF
// ============================================================================
echo "\n=== EXEMPLO 4: Estrutura IF ===\n\n";

$tabelaSimbolos4 = ['x' => 'INT', 'maior' => 'INT'];
$gerador4 = new GeradorCodigoMIPS($tabelaSimbolos4);

$gerador4->gerarVariaveisGlobais();
$gerador4->gerarAtribuicao('x', 15);
$gerador4->gerarAtribuicao('maior', 10);

// if (x > maior) { maior = x; }
$gerador4->gerarIf(
    ['>', 'x', 'maior'],
    function() use ($gerador4) {
        $gerador4->gerarAtribuicao('maior', 'x');
    }
);

$gerador4->gerarEscrita('maior');
$gerador4->gerarExit();

$gerador4->salvarArquivo("exemplo4_if.asm");
echo "✓ Código com IF salvo em: exemplo4_if.asm\n\n";

// ============================================================================
// EXEMPLO 5: Loop WHILE
// ============================================================================
echo "\n=== EXEMPLO 5: Loop WHILE ===\n\n";

$tabelaSimbolos5 = ['contador' => 'INT', 'limite' => 'INT'];
$gerador5 = new GeradorCodigoMIPS($tabelaSimbolos5);

$gerador5->gerarVariaveisGlobais();
$gerador5->gerarAtribuicao('contador', 0);
$gerador5->gerarAtribuicao('limite', 5);

// while (contador < limite) { contador = contador + 1; }
$gerador5->gerarWhile(
    ['<', 'contador', 'limite'],
    function() use ($gerador5) {
        $gerador5->gerarAtribuicao('contador', ['+', 'contador', 1]);
        $gerador5->gerarEscrita('contador');
    }
);

$gerador5->gerarExit();
$gerador5->salvarArquivo("exemplo5_while.asm");
echo "✓ Código com WHILE salvo em: exemplo5_while.asm\n\n";

// ============================================================================
// EXEMPLO 6: Entrada e Saída
// ============================================================================
echo "\n=== EXEMPLO 6: Entrada e Saída ===\n\n";

$tabelaSimbolos6 = ['numero' => 'INT', 'dobro' => 'INT'];
$gerador6 = new GeradorCodigoMIPS($tabelaSimbolos6);

$gerador6->gerarVariaveisGlobais();

// read(numero)
$gerador6->gerarLeitura('numero');

// dobro = numero * 2
$gerador6->gerarAtribuicao('dobro', ['*', 'numero', 2]);

// print(dobro)
$gerador6->gerarEscrita('dobro');

$gerador6->gerarExit();
$gerador6->salvarArquivo("exemplo6_io.asm");
echo "✓ Código com I/O salvo em: exemplo6_io.asm\n\n";

// ============================================================================
// INFORMAÇÕES FINAIS
// ============================================================================
echo "\n==============================================\n";
echo "📦 Arquivos .asm gerados com sucesso!\n";
echo "==============================================\n\n";
echo "Para executar no MARS:\n";
echo "1. Abra o MARS Simulator\n";
echo "2. File → Open → Selecione o arquivo .asm\n";
echo "3. Assemble (F3)\n";
echo "4. Run (F5)\n\n";
echo "Arquivos criados:\n";
echo "  • exemplo1.asm - Declaração e atribuição simples\n";
echo "  • exemplo2.asm - Operações aritméticas\n";
echo "  • exemplo3.asm - Múltiplas operações\n";
echo "  • exemplo4_if.asm - Estrutura condicional\n";
echo "  • exemplo5_while.asm - Loop while\n";
echo "  • exemplo6_io.asm - Entrada e saída\n";
echo "==============================================\n";
?>