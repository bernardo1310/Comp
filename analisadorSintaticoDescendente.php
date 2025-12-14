<?php
require_once("analisadorLexico.php");
/**
 * ============================================================================
 * CLASSE: AnalisadorSintaticoDescendente
 * ----------------------------------------------------------------------------
 * Implementa um analisador sintático descendente recursivo (LL).
 * Cada não-terminal da gramática é um método.
 * Utiliza lookahead de 1 token e sincronização para erro.
 * ============================================================================
 */
class AnalisadorSintaticoDescendente {
    private $lexico;
    private $tokenAtual;
    private $erros = [];

    public function __construct(AnalisadorLexico $lexico) {
        $this->lexico = $lexico;
        $this->lexico->reset();
        $this->tokenAtual = $this->lexico->nextToken();
    }

    public function analisar() {
        $this->erros = [];
        $this->programa();
        if ($this->tokenAtual !== null) {
            $this->adicionarErro("Tokens extras além do esperado: " . $this->tokenAtual);
        }
        return empty($this->erros);
    }

    private function consumir($tipo) {
        if ($this->tokenAtual === null) {
            $this->adicionarErro("Fim inesperado da entrada, esperado: $tipo");
            return;
        }
        if ($this->tokenAtual->getTipo() === $tipo) {
            $this->tokenAtual = $this->lexico->nextToken();
        } else {
            $this->adicionarErro("Esperado token $tipo, encontrado " . $this->tokenAtual->getTipo());
            $this->tokenAtual = $this->lexico->nextToken();
        }
    }

    private function adicionarErro($msg) {
        $this->erros[] = $msg;
    }

    private function programa() {
        // Programa -> Declaracoes Comandos
        $this->declaracoes();
        $this->comandos();
    }

    private function declaracoes() {
        // Declaracoes -> var ID ; Declaracoes | ε
        if ($this->tokenAtual !== null && $this->tokenAtual->getTipo() === "VAR") {
            $this->consumir("VAR");
            $this->consumir("ID");
            $this->consumir("PV");
            $this->declaracoes();
        }
    }

    private function comandos() {
        // Comandos -> Comando Comandos | ε
        while ($this->tokenAtual !== null && in_array($this->tokenAtual->getTipo(),["ID","IF","WHILE","FOR","READ","PRINT","INIBLOCO"])) {
            $this->comando();
        }
    }

    private function comando() {
        if ($this->tokenAtual === null) {
            $this->adicionarErro("Fim inesperado na análise de comando");
            return;
        }
        switch ($this->tokenAtual->getTipo()) {
            case "ID":
                $this->atribuicao();
                break;
            case "IF":
                $this->condicional();
                break;
            case "WHILE":
            case "FOR":
                $this->laco();
                break;
            case "READ":
                $this->entrada();
                break;
            case "PRINT":
                $this->saida();
                break;
            case "INIBLOCO":
                $this->bloco();
                break;
            default:
                $this->adicionarErro("Token inesperado em comando: " . $this->tokenAtual->getTipo());
                $this->tokenAtual = $this->lexico->nextToken();
                break;
        }
    }

    private function atribuicao() {
        $this->consumir("ID");
        $this->consumir("ATRIBUICAO");
        $this->expressao();
        $this->consumir("PV");
    }

    private function condicional() {
        $this->consumir("IF");
        $this->consumir("AP");
        $this->expressao();
        $this->consumir("FP");
        $this->comando();
        if ($this->tokenAtual !== null && $this->tokenAtual->getTipo() === "ELSE") {
            $this->consumir("ELSE");
            $this->comando();
        }
    }

    private function laco() {
        if ($this->tokenAtual->getTipo() === "WHILE") {
            $this->consumir("WHILE");
            $this->consumir("AP");
            $this->expressao();
            $this->consumir("FP");
            $this->comando();
        } elseif ($this->tokenAtual->getTipo() === "FOR") {
            $this->consumir("FOR");
            $this->consumir("AP");
            $this->atribuicao();
            $this->expressao();
            $this->consumir("PV");
            $this->atribuicao();
            $this->consumir("FP");
            $this->comando();
        }
    }

    private function entrada() {
        $this->consumir("READ");
        $this->consumir("AP");
        $this->consumir("ID");
        $this->consumir("FP");
        $this->consumir("PV");
    }

    private function saida() {
        $this->consumir("PRINT");
        $this->consumir("AP");
        $this->expressao();
        $this->consumir("FP");
        $this->consumir("PV");
    }

    private function bloco() {
        $this->consumir("INIBLOCO");
        $this->comandos();
        $this->consumir("FIMBLOCO");
    }

    private function expressao() {
        $this->termo();
        while ($this->tokenAtual !== null && in_array($this->tokenAtual->getTipo(), ["SOMA", "SUBTRACAO"])) {
            $this->consumir($this->tokenAtual->getTipo());
            $this->termo();
        }
    }

    private function termo() {
        $this->fator();
        while ($this->tokenAtual !== null && in_array($this->tokenAtual->getTipo(), ["MULTIPLICACAO", "DIVISAO"])) {
            $this->consumir($this->tokenAtual->getTipo());
            $this->fator();
        }
    }

    private function fator() {
        if ($this->tokenAtual === null) {
            $this->adicionarErro("Fim inesperado durante análise de fator");
            return;
        }
        switch ($this->tokenAtual->getTipo()) {
            case "ID":
                $this->consumir("ID");
                break;
            case "CONST":
                $this->consumir("CONST");
                break;
            case "AP":
                $this->consumir("AP");
                $this->expressao();
                $this->consumir("FP");
                break;
            default:
                $this->adicionarErro("Token inesperado em fator: " . $this->tokenAtual->getTipo());
                $this->tokenAtual = $this->lexico->nextToken();
                break;
        }
    }

    public function getErros() {
        return $this->erros;
    }
}
?>
