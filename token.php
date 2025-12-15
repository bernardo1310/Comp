<?php
class Token {
    private string $tipo;
    private string $lexema;
    private int $pos;
    private int $linha;
    private int $col;

    public function __construct($tipo, $lexema, $pos, $linha = 1, $col = 1) {
        $this->tipo   = (string)$tipo;
        $this->lexema = (string)$lexema;
        $this->pos    = (int)$pos;
        $this->linha  = (int)$linha;
        $this->col    = (int)$col;
    }

    public function getTipo()   { return $this->tipo; }
    public function getLexema() { return $this->lexema; }
    public function getPos()    { return $this->pos; }

    // NOVO
    public function getLinha()  { return $this->linha; }
    public function getCol()    { return $this->col; }

    public function __toString() {
        return "[{$this->tipo}, '{$this->lexema}', pos={$this->pos}, linha={$this->linha}, col={$this->col}]";
    }
}
?>
