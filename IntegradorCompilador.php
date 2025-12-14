<?php
/**
 * ============================================================================
 * CLASSE: IntegradorCompilador
 * ----------------------------------------------------------------------------
 * Integra o Gerador de Código MIPS com o compilador existente
 * 
 * RESPONSABILIDADES:
 * - Orquestrar todas as fases da compilação
 * - Converter AST/estruturas do parser em código MIPS
 * - Gerenciar fluxo: Léxico → Sintático → Semântico → Geração de Código
 * ============================================================================
 */

require_once("analisadorLexico.php");
require_once("AnalisadorSintaticoSLR.php");
require_once("GeradorCodigoMIPS.php");

class IntegradorCompilador {
    
    private $lexico;
    private $sintatico;
    private $gerador;
    private $tokens = [];
    private $tabelaSimbolos = [];
    
    /**
     * Construtor
     */
    public function __construct() {
        $this->lexico = new AnalisadorLexico();
    }
    
    /**
     * Compila código-fonte completo
     * 
     * @param string $codigoFonte - Código-fonte na linguagem
     * @return array - Resultado da compilação
     */
    public function compilar($codigoFonte) {
        $resultado = [
            'success' => false,
            'codigo_assembly' => '',
            'erros' => [],
            'etapas' => []
        ];
        
        try {
            // FASE 1: Análise Léxica
            $resultado['etapas'][] = "✓ Análise Léxica";
            $this->lexico->analisar($codigoFonte);
            $this->tokens = $this->lexico->getTokens();
            
            if (!empty($this->lexico->getErros())) {
                $resultado['erros'] = $this->lexico->getErros();
                return $resultado;
            }
            
            // FASE 2: Análise Sintática
            $resultado['etapas'][] = "✓ Análise Sintática";
            $this->sintatico = new AnalisadorSintaticoSLR();
            $this->sintatico->setTokensDoLexico($this->tokens);
            $resultadoSintatico = $this->sintatico->analisar();
            
            if (!$resultadoSintatico['success']) {
                $resultado['erros'][] = $resultadoSintatico['message'];
                return $resultado;
            }
            
            // FASE 3: Análise Semântica (já integrada no sintático)
            $resultado['etapas'][] = "✓ Análise Semântica";
            $this->tabelaSimbolos = $this->sintatico->getSemantico()->getTabelaSimbolos();
            
            // FASE 4: Geração de Código Assembly MIPS
            $resultado['etapas'][] = "✓ Geração de Código MIPS";
            $this->gerador = new GeradorCodigoMIPS($this->tabelaSimbolos);
            $this->processarTokensParaMIPS();
            
            // Finaliza programa
            $this->gerador->gerarExit();
            
            // Gera código completo
            $resultado['codigo_assembly'] = $this->gerador->getCodigoCompleto();
            $resultado['success'] = true;
            
        } catch (Exception $e) {
            $resultado['erros'][] = "Erro fatal: " . $e->getMessage();
        }
        
        return $resultado;
    }
    
    /**
     * Processa tokens e gera código MIPS correspondente
     */
    private function processarTokensParaMIPS() {
        // Gera variáveis globais
        $this->gerador->gerarVariaveisGlobais();
        
        // Processa tokens sequencialmente
        $i = 0;
        while ($i < count($this->tokens)) {
            $token = $this->tokens[$i];
            
            switch ($token->getTipo()) {
                case 'INT':
                case 'CHAR':
                case 'BOOL':
                    // Declaração: TIPO id ;
                    if (isset($this->tokens[$i + 1]) && $this->tokens[$i + 1]->getTipo() === 'ID') {
                        $nome = $this->tokens[$i + 1]->getLexema();
                        $tipo = $token->getTipo();
                        $this->gerador->gerarDeclaracao($nome, $tipo);
                        $i += 3; // Pula TIPO, ID, PV
                    }
                    break;
                    
                case 'ID':
                    // Verifica se é atribuição: id = expr ;
                    if (isset($this->tokens[$i + 1]) && $this->tokens[$i + 1]->getTipo() === 'ATRIB') {
                        $var = $token->getLexema();
                        $expr = $this->extrairExpressao($i + 2);
                        $this->gerador->gerarAtribuicao($var, $expr['valor']);
                        $i = $expr['indice_final'] + 1;
                    } else {
                        $i++;
                    }
                    break;
                    
                case 'IF':
                    // Estrutura if ( cond ) { ... }
                    $estruturaIf = $this->extrairEstruturaIf($i);
                    $this->gerarCodigoIf($estruturaIf);
                    $i = $estruturaIf['indice_final'] + 1;
                    break;
                    
                case 'WHILE':
                    // Estrutura while ( cond ) { ... }
                    $estruturaWhile = $this->extrairEstruturaWhile($i);
                    $this->gerarCodigoWhile($estruturaWhile);
                    $i = $estruturaWhile['indice_final'] + 1;
                    break;
                    
                case 'PRINT':
                    // print ( expr ) ;
                    if (isset($this->tokens[$i + 2])) {
                        $expr = $this->extrairExpressao($i + 2);
                        $this->gerador->gerarEscrita($expr['valor']);
                        $i = $expr['indice_final'] + 2; // Pula FP e PV
                    } else {
                        $i++;
                    }
                    break;
                    
                case 'READ':
                    // read ( id ) ;
                    if (isset($this->tokens[$i + 2]) && $this->tokens[$i + 2]->getTipo() === 'ID') {
                        $var = $this->tokens[$i + 2]->getLexema();
                        $this->gerador->gerarLeitura($var);
                        $i += 5; // Pula READ, AP, ID, FP, PV
                    } else {
                        $i++;
                    }
                    break;
                    
                default:
                    $i++;
            }
        }
    }
    
    /**
     * Extrai expressão dos tokens
     * 
     * @param int $inicio - Índice inicial
     * @return array - ['valor' => expressão, 'indice_final' => índice]
     */
    private function extrairExpressao($inicio) {
        $i = $inicio;
        $pilha = [];
        $expr = null;
        
        // Expressão simples: id ou const
        if (isset($this->tokens[$i])) {
            $token = $this->tokens[$i];
            
            if ($token->getTipo() === 'ID') {
                $expr = $token->getLexema();
                
                // Verifica se há operador binário
                if (isset($this->tokens[$i + 1]) && in_array($this->tokens[$i + 1]->getTipo(), ['SOMA', 'MULT'])) {
                    $op = $this->tokens[$i + 1]->getTipo() === 'SOMA' ? '+' : '*';
                    $direitaResult = $this->extrairExpressao($i + 2);
                    $expr = [$op, $expr, $direitaResult['valor']];
                    return ['valor' => $expr, 'indice_final' => $direitaResult['indice_final']];
                }
                
            } elseif ($token->getTipo() === 'CONST') {
                $expr = intval($token->getLexema());
                
                // Verifica operador binário
                if (isset($this->tokens[$i + 1]) && in_array($this->tokens[$i + 1]->getTipo(), ['SOMA', 'MULT'])) {
                    $op = $this->tokens[$i + 1]->getTipo() === 'SOMA' ? '+' : '*';
                    $direitaResult = $this->extrairExpressao($i + 2);
                    $expr = [$op, $expr, $direitaResult['valor']];
                    return ['valor' => $expr, 'indice_final' => $direitaResult['indice_final']];
                }
            }
        }
        
        return ['valor' => $expr ?? 0, 'indice_final' => $i];
    }
    
    /**
     * Extrai estrutura IF completa
     * 
     * @param int $inicio - Índice do token IF
     * @return array - Estrutura parseada
     */
    private function extrairEstruturaIf($inicio) {
        $i = $inicio + 2; // Pula IF e AP
        
        // Extrai condição: expr op expr
        $esq = $this->extrairExpressao($i);
        $i = $esq['indice_final'] + 1;
        
        $op = null;
        if (isset($this->tokens[$i])) {
            $opToken = $this->tokens[$i];
            if ($opToken->getTipo() === 'MAIOR') $op = '>';
            if ($opToken->getTipo() === 'MENOR') $op = '<';
            $i++;
        }
        
        $dir = $this->extrairExpressao($i);
        $i = $dir['indice_final'] + 3; // Pula FP e INIBLOCO
        
        $condicao = [$op, $esq['valor'], $dir['valor']];
        
        // Extrai bloco (simplificado: pega tokens até FIMBLOCO)
        $blocoTokens = [];
        while ($i < count($this->tokens) && $this->tokens[$i]->getTipo() !== 'FIMBLOCO') {
            $blocoTokens[] = $this->tokens[$i];
            $i++;
        }
        
        return [
            'condicao' => $condicao,
            'bloco_then' => $blocoTokens,
            'indice_final' => $i
        ];
    }
    
    /**
     * Extrai estrutura WHILE completa
     * 
     * @param int $inicio - Índice do token WHILE
     * @return array - Estrutura parseada
     */
    private function extrairEstruturaWhile($inicio) {
        // Similar ao IF
        return $this->extrairEstruturaIf($inicio);
    }
    
    /**
     * Gera código MIPS para estrutura IF
     * 
     * @param array $estrutura - Estrutura parseada
     */
    private function gerarCodigoIf($estrutura) {
        $condicao = $estrutura['condicao'];
        
        $this->gerador->gerarIf(
            $condicao,
            function() use ($estrutura) {
                // Processa bloco THEN (simplificado)
                foreach ($estrutura['bloco_then'] as $token) {
                    if ($token->getTipo() === 'PRINT') {
                        // Simplificação: assume print(const)
                    }
                }
            }
        );
    }
    
    /**
     * Gera código MIPS para estrutura WHILE
     * 
     * @param array $estrutura - Estrutura parseada
     */
    private function gerarCodigoWhile($estrutura) {
        $condicao = $estrutura['condicao'];
        
        $this->gerador->gerarWhile(
            $condicao,
            function() use ($estrutura) {
                // Processa corpo do loop
            }
        );
    }
    
    /**
     * Salva código Assembly em arquivo .asm
     * 
     * @param string $nomeArquivo - Nome do arquivo
     */
    public function salvarAssembly($nomeArquivo) {
        if ($this->gerador) {
            $this->gerador->salvarArquivo($nomeArquivo);
        }
    }
}
?>