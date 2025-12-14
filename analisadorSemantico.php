<?php
/**
 * ============================================================================
 * CLASSE: Semantico - VERSÃO CORRIGIDA
 * ----------------------------------------------------------------------------
 * Realiza análise semântica com gerenciamento simplificado
 * 
 * RESPONSABILIDADES:
 * - Gerenciar tabela de símbolos (variáveis)
 * - Verificar declaração antes do uso
 * - Evitar redeclarações
 * ============================================================================
 */
class Semantico {

    private $tabelaSimbolos = [];

    /**
     * Verifica se variável existe na tabela
     * 
     * @param string $var - Nome da variável
     * @throws Exception se variável não foi declarada
     */
    public function verificaVariavelExistente($var){
        if (!array_key_exists($var, $this->tabelaSimbolos)) {
            throw new Exception("Erro Semântico: Variável '{$var}' não declarada antes do uso.");
        }
    }

    /**
     * Verifica compatibilidade de tipos
     * 
     * @param string $var - Nome da variável
     * @param string $tipoValor - Tipo do valor sendo atribuído
     * @throws Exception se houver incompatibilidade de tipos
     */
    public function verificaTipo($var, $tipoValor){
        if (!isset($this->tabelaSimbolos[$var])) {
            throw new Exception("Erro Semântico: Variável '{$var}' não declarada.");
        }

        if ($this->tabelaSimbolos[$var] !== $tipoValor) {
            throw new Exception("Erro Semântico: Tipo incompatível para '{$var}'. Esperado: {$this->tabelaSimbolos[$var]}, Recebido: {$tipoValor}");
        }
    }

    /**
     * Instala variável na tabela de símbolos
     * 
     * @param string $var - Nome da variável
     * @param string $tipo - Tipo da variável (INT, CHAR, BOOL)
     * @return bool true se instalado com sucesso
     * @throws Exception se variável já existe ou parâmetros inválidos
     */
    public function instalaVariavel($var, $tipo){
        if ($var === "" || $tipo === "") {
            throw new Exception("Erro Semântico: Variável ou tipo não definidos.");
        }

        if (isset($this->tabelaSimbolos[$var])) {
            throw new Exception("Erro Semântico: Variável '{$var}' já foi declarada.");
        }

        $this->tabelaSimbolos[$var] = $tipo;
        return true;
    }

    /**
     * Retorna a tabela de símbolos completa
     * 
     * @return array Tabela de símbolos (nome => tipo)
     */
    public function getTabelaSimbolos(){
        return $this->tabelaSimbolos;
    }

    /**
     * Limpa a tabela de símbolos (útil para novos testes)
     */
    public function limparTabela(){
        $this->tabelaSimbolos = [];
    }
}
?>