<?php
/**
 * ============================================================================
 * CLASSE: GeradorCodigoMIPS
 * ----------------------------------------------------------------------------
 * Gerador de código Assembly MIPS32 compatível com MARS Simulator
 * 
 * ARQUITETURA:
 * - Target: MIPS32
 * - Simulator: MARS (MIPS Assembler and Runtime Simulator)
 * - Referência: Tanenbaum - Organização Estruturada de Computadores
 * 
 * CONVENÇÕES MIPS:
 * - $t0-$t9: registradores temporários
 * - $s0-$s7: registradores salvos (variáveis)
 * - $a0-$a3: argumentos de função
 * - $v0-$v1: valores de retorno
 * - $sp: stack pointer
 * - $fp: frame pointer
 * - $ra: return address
 * - $zero: constante 0
 * 
 * SEÇÕES:
 * - .data: variáveis globais e strings
 * - .text: código executável
 * ============================================================================
 */

class GeradorCodigoMIPS {
    
    private $codigoData = [];      // Seção .data
    private $codigoText = [];      // Seção .text
    private $tabelaSimbolos = [];  // Tabela de símbolos (var => offset)
    private $labelCounter = 0;     // Contador de labels únicos
    private $tempCounter = 0;      // Contador de temporários
    private $registradoresLivres = ['$t0', '$t1', '$t2', '$t3', '$t4', '$t5', '$t6', '$t7', '$t8', '$t9'];
    private $registradoresUsados = [];
    private $offsetStack = 0;      // Offset atual na pilha
    private $strings = [];         // Strings literais
    
    /**
     * Construtor
     * Inicializa o gerador com a tabela de símbolos do compilador
     * 
     * @param array $tabelaSimbolos - Tabela de símbolos do analisador semântico
     */
    public function __construct($tabelaSimbolos = []) {
        $this->tabelaSimbolos = $tabelaSimbolos;
        $this->inicializarSecaoData();
        $this->inicializarSecaoText();
    }
    
    /**
     * Inicializa a seção .data com strings de sistema
     */
    private function inicializarSecaoData() {
        $this->codigoData[] = ".data";
        $this->codigoData[] = "# Strings de sistema";
        $this->codigoData[] = "_newline: .asciiz \"\\n\"";
        $this->codigoData[] = "_space: .asciiz \" \"";
        $this->codigoData[] = "";
        $this->codigoData[] = "# Variáveis globais";
    }
    
    /**
     * Inicializa a seção .text com prólogo do programa
     */
    private function inicializarSecaoText() {
        $this->codigoText[] = ".text";
        $this->codigoText[] = ".globl main";
        $this->codigoText[] = "";
        $this->codigoText[] = "main:";
        $this->codigoText[] = "    # Prólogo do programa";
        $this->codigoText[] = "    addi \$sp, \$sp, -4   # Reserva espaço na pilha";
        $this->codigoText[] = "    sw \$fp, 0(\$sp)      # Salva frame pointer anterior";
        $this->codigoText[] = "    move \$fp, \$sp       # Atualiza frame pointer";
        $this->codigoText[] = "";
    }
    
    /**
     * Adiciona variáveis globais na seção .data
     */
    public function gerarVariaveisGlobais() {
        foreach ($this->tabelaSimbolos as $nome => $tipo) {
            $this->codigoData[] = "_var_{$nome}: .word 0  # {$tipo}";
        }
        $this->codigoData[] = "";
    }
    
    /**
     * Gera código para declaração de variável
     * 
     * @param string $nome - Nome da variável
     * @param string $tipo - Tipo da variável (INT, CHAR, BOOL)
     */
    public function gerarDeclaracao($nome, $tipo) {
        // Variáveis já alocadas na seção .data
        $this->adicionarComentario("Declaração: {$tipo} {$nome}");
    }
    
    /**
     * Gera código para atribuição: var = expr
     * 
     * @param string $var - Nome da variável
     * @param mixed $expr - Expressão (pode ser valor, operação, etc.)
     */
    public function gerarAtribuicao($var, $expr) {
        $this->adicionarComentario("Atribuição: {$var} = {$expr}");
        
        // Avalia a expressão e coloca resultado em $t0
        $reg = $this->gerarExpressao($expr);
        
        // Armazena em memória
        $this->codigoText[] = "    sw {$reg}, _var_{$var}  # {$var} = {$expr}";
        
        $this->liberarRegistrador($reg);
    }
    
    /**
     * Gera código para expressão aritmética
     * 
     * @param mixed $expr - Expressão (número, variável, operação)
     * @return string - Registrador contendo o resultado
     */
    public function gerarExpressao($expr) {
        // Se é um número literal
        if (is_numeric($expr)) {
            $reg = $this->alocarRegistrador();
            $this->codigoText[] = "    li {$reg}, {$expr}  # Carrega constante {$expr}";
            return $reg;
        }
        
        // Se é uma variável
        if (is_string($expr) && isset($this->tabelaSimbolos[$expr])) {
            $reg = $this->alocarRegistrador();
            $this->codigoText[] = "    lw {$reg}, _var_{$expr}  # Carrega {$expr}";
            return $reg;
        }
        
        // Se é uma operação binária (array: [op, esq, dir])
        if (is_array($expr) && count($expr) === 3) {
            return $this->gerarOperacaoBinaria($expr[0], $expr[1], $expr[2]);
        }
        
        // Fallback: retorna $zero
        return '$zero';
    }
    
    /**
     * Gera código para operação binária
     * 
     * @param string $op - Operador (+, -, *, /)
     * @param mixed $esquerda - Operando esquerdo
     * @param mixed $direita - Operando direito
     * @return string - Registrador com resultado
     */
    public function gerarOperacaoBinaria($op, $esquerda, $direita) {
        $this->adicionarComentario("Operação: {$esquerda} {$op} {$direita}");
        
        // Avalia operandos
        $regEsq = $this->gerarExpressao($esquerda);
        $regDir = $this->gerarExpressao($direita);
        $regDest = $this->alocarRegistrador();
        
        switch ($op) {
            case '+':
                $this->codigoText[] = "    add {$regDest}, {$regEsq}, {$regDir}  # +";
                break;
            case '-':
                $this->codigoText[] = "    sub {$regDest}, {$regEsq}, {$regDir}  # -";
                break;
            case '*':
                $this->codigoText[] = "    mul {$regDest}, {$regEsq}, {$regDir}  # *";
                break;
            case '/':
                $this->codigoText[] = "    div {$regEsq}, {$regDir}  # /";
                $this->codigoText[] = "    mflo {$regDest}  # Resultado da divisão";
                break;
            default:
                $this->codigoText[] = "    # Operador desconhecido: {$op}";
        }
        
        $this->liberarRegistrador($regEsq);
        $this->liberarRegistrador($regDir);
        
        return $regDest;
    }
    
    /**
     * Gera código para estrutura IF
     * 
     * @param mixed $condicao - Condição (array: [op, esq, dir])
     * @param callable $blocoThen - Função que gera código do bloco THEN
     * @param callable|null $blocoElse - Função que gera código do bloco ELSE (opcional)
     */
    public function gerarIf($condicao, $blocoThen, $blocoElse = null) {
        $labelElse = $this->gerarLabel('else');
        $labelFim = $this->gerarLabel('endif');
        
        $this->adicionarComentario("IF: início");
        
        // Avalia condição
        $regCond = $this->gerarCondicao($condicao, $labelElse);
        
        // Bloco THEN
        $this->adicionarComentario("Bloco THEN");
        $blocoThen();
        
        if ($blocoElse !== null) {
            $this->codigoText[] = "    j {$labelFim}  # Pula ELSE";
            $this->codigoText[] = "{$labelElse}:  # Início ELSE";
            $blocoElse();
        } else {
            $this->codigoText[] = "{$labelElse}:  # Fim IF";
        }
        
        if ($blocoElse !== null) {
            $this->codigoText[] = "{$labelFim}:  # Fim IF-ELSE";
        }
        
        $this->adicionarComentario("IF: fim");
    }
    
    /**
     * Gera código para estrutura WHILE
     * 
     * @param mixed $condicao - Condição de permanência
     * @param callable $blocoCorpo - Função que gera código do corpo do loop
     */
    public function gerarWhile($condicao, $blocoCorpo) {
        $labelInicio = $this->gerarLabel('while');
        $labelFim = $this->gerarLabel('endwhile');
        
        $this->adicionarComentario("WHILE: início");
        $this->codigoText[] = "{$labelInicio}:  # Início do loop";
        
        // Avalia condição
        $this->gerarCondicao($condicao, $labelFim);
        
        // Corpo do loop
        $this->adicionarComentario("Corpo do WHILE");
        $blocoCorpo();
        
        $this->codigoText[] = "    j {$labelInicio}  # Volta para condição";
        $this->codigoText[] = "{$labelFim}:  # Fim do loop";
        
        $this->adicionarComentario("WHILE: fim");
    }
    
    /**
     * Gera código para estrutura FOR
     * 
     * @param callable $inicializacao - Código de inicialização
     * @param mixed $condicao - Condição de permanência
     * @param callable $incremento - Código de incremento
     * @param callable $blocoCorpo - Função que gera código do corpo
     */
    public function gerarFor($inicializacao, $condicao, $incremento, $blocoCorpo) {
        $this->adicionarComentario("FOR: início");
        
        // Inicialização
        $this->adicionarComentario("Inicialização");
        $inicializacao();
        
        $labelInicio = $this->gerarLabel('for');
        $labelFim = $this->gerarLabel('endfor');
        
        $this->codigoText[] = "{$labelInicio}:  # Início do loop";
        
        // Condição
        $this->gerarCondicao($condicao, $labelFim);
        
        // Corpo
        $this->adicionarComentario("Corpo do FOR");
        $blocoCorpo();
        
        // Incremento
        $this->adicionarComentario("Incremento");
        $incremento();
        
        $this->codigoText[] = "    j {$labelInicio}  # Volta para condição";
        $this->codigoText[] = "{$labelFim}:  # Fim do loop";
        
        $this->adicionarComentario("FOR: fim");
    }
    
    /**
     * Gera código para avaliação de condição
     * 
     * @param mixed $condicao - Condição (array: [op, esq, dir])
     * @param string $labelFalso - Label para onde pular se falso
     * @return string - Registrador usado
     */
    private function gerarCondicao($condicao, $labelFalso) {
        if (!is_array($condicao) || count($condicao) !== 3) {
            return '$zero';
        }
        
        list($op, $esquerda, $direita) = $condicao;
        
        $regEsq = $this->gerarExpressao($esquerda);
        $regDir = $this->gerarExpressao($direita);
        
        switch ($op) {
            case '>':
                $this->codigoText[] = "    ble {$regEsq}, {$regDir}, {$labelFalso}  # Se <=, pula";
                break;
            case '<':
                $this->codigoText[] = "    bge {$regEsq}, {$regDir}, {$labelFalso}  # Se >=, pula";
                break;
            case '==':
                $this->codigoText[] = "    bne {$regEsq}, {$regDir}, {$labelFalso}  # Se !=, pula";
                break;
            case '!=':
                $this->codigoText[] = "    beq {$regEsq}, {$regDir}, {$labelFalso}  # Se ==, pula";
                break;
            case '>=':
                $this->codigoText[] = "    blt {$regEsq}, {$regDir}, {$labelFalso}  # Se <, pula";
                break;
            case '<=':
                $this->codigoText[] = "    bgt {$regEsq}, {$regDir}, {$labelFalso}  # Se >, pula";
                break;
            default:
                $this->codigoText[] = "    # Operador de comparação desconhecido: {$op}";
        }
        
        $this->liberarRegistrador($regEsq);
        $this->liberarRegistrador($regDir);
        
        return '$zero';
    }
    
    /**
     * Gera código para leitura (read/scanf)
     * 
     * @param string $var - Nome da variável
     */
    public function gerarLeitura($var) {
        $this->adicionarComentario("READ: {$var}");
        $this->codigoText[] = "    li \$v0, 5  # Syscall read_int";
        $this->codigoText[] = "    syscall";
        $this->codigoText[] = "    sw \$v0, _var_{$var}  # Armazena em {$var}";
    }
    
    /**
     * Gera código para escrita (print/printf)
     * 
     * @param mixed $expr - Expressão a imprimir
     */
    public function gerarEscrita($expr) {
        $this->adicionarComentario("PRINT: {$expr}");
        
        $reg = $this->gerarExpressao($expr);
        
        $this->codigoText[] = "    move \$a0, {$reg}  # Prepara argumento";
        $this->codigoText[] = "    li \$v0, 1  # Syscall print_int";
        $this->codigoText[] = "    syscall";
        
        // Imprime newline
        $this->codigoText[] = "    la \$a0, _newline";
        $this->codigoText[] = "    li \$v0, 4  # Syscall print_string";
        $this->codigoText[] = "    syscall";
        
        $this->liberarRegistrador($reg);
    }
    
    /**
     * Gera código de finalização do programa
     */
    public function gerarExit() {
        $this->codigoText[] = "";
        $this->codigoText[] = "    # Epílogo do programa";
        $this->codigoText[] = "    lw \$fp, 0(\$sp)  # Restaura frame pointer";
        $this->codigoText[] = "    addi \$sp, \$sp, 4  # Libera pilha";
        $this->codigoText[] = "    li \$v0, 10  # Syscall exit";
        $this->codigoText[] = "    syscall";
    }
    
    /**
     * Gera label único
     * 
     * @param string $prefixo - Prefixo do label
     * @return string - Label único
     */
    private function gerarLabel($prefixo = 'L') {
        return "_{$prefixo}" . $this->labelCounter++;
    }
    
    /**
     * Aloca um registrador temporário livre
     * 
     * @return string - Nome do registrador alocado
     */
    private function alocarRegistrador() {
        if (count($this->registradoresLivres) > 0) {
            $reg = array_shift($this->registradoresLivres);
            $this->registradoresUsados[] = $reg;
            return $reg;
        }
        
        // Se não há registradores livres, usa spillage (simplificado)
        return '$t9';
    }
    
    /**
     * Libera um registrador temporário
     * 
     * @param string $reg - Nome do registrador
     */
    private function liberarRegistrador($reg) {
        if (in_array($reg, $this->registradoresUsados)) {
            $key = array_search($reg, $this->registradoresUsados);
            unset($this->registradoresUsados[$key]);
            $this->registradoresLivres[] = $reg;
        }
    }
    
    /**
     * Adiciona comentário ao código
     * 
     * @param string $comentario - Texto do comentário
     */
    private function adicionarComentario($comentario) {
        $this->codigoText[] = "    # {$comentario}";
    }
    
    /**
     * Retorna o código Assembly completo gerado
     * 
     * @return string - Código Assembly MIPS
     */
    public function getCodigoCompleto() {
        $codigo = implode("\n", $this->codigoData);
        $codigo .= "\n\n";
        $codigo .= implode("\n", $this->codigoText);
        return $codigo;
    }
    
    /**
     * Salva código Assembly em arquivo
     * 
     * @param string $nomeArquivo - Nome do arquivo .asm
     */
    public function salvarArquivo($nomeArquivo) {
        file_put_contents($nomeArquivo, $this->getCodigoCompleto());
    }
}
?>