<?php
/**
 * =================================================================
 * CLASSE: Token
 * -----------------------------------------------------------------
 * Representa um token identificado pelo analisador léxico.
 *
 * ATRIBUTOS:
 * - $tipo : categoria do token (ID, IF, CONST, ATR, etc.)
 * - $lexema : a sequência de caracteres reconhecida
 * - $pos : posição no código-fonte
 *
 * IMPORTÂNCIA:
 * - É a unidade básica que o analisador léxico entrega ao parser.
 * - Facilita a comunicação entre os módulos do compilador.
 * - Permite rastreamento de erros através da posição.
 * =================================================================
 */
class Token {
    private $tipo;
    private $lexema;
    private $pos;

    /**
     * Construtor da classe Token
     * 
     * @param string $tipo - Categoria do token (IF, ID, CONST, etc.)
     * @param string $lexema - Sequência de caracteres original
     * @param int $pos - Posição no código-fonte
     */
    public function __construct($tipo, $lexema, $pos) {
        $this->tipo = $tipo;
        $this->lexema = $lexema;
        $this->pos = $pos;
    }

    /**
     * Retorna o tipo/categoria do token
     * @return string
     */
    public function getTipo() {
        return $this->tipo;
    }

    /**
     * Retorna o lexema (sequência original de caracteres)
     * @return string
     */
    public function getLexema() {
        return $this->lexema;
    }

    /**
     * Retorna a posição do token no código-fonte
     * @return int
     */
    public function getPos() {
        return $this->pos;
    }

    /**
     * Representação em string do token para debug/exibição
     * @return string
     */
    public function __toString() {
        return "[{$this->tipo}, '{$this->lexema}', pos={$this->pos}]";
    }
}
?>