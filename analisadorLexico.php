<?php
require_once("token.php");
require_once("lexico.php");

class AnalisadorLexico {
    private array $tokens = [];
    private array $erros = [];
    private string $entrada = "";
    private int $pos = 0;
    private int $ultimoTokenIndex = -1;

    public function analisar($entrada): void {
        $this->tokens = [];
        $this->erros = [];
        $this->entrada = (string)$entrada;
        $this->pos = 0;
        $this->ultimoTokenIndex = -1; // <<<<<< IMPORTANTÍSSIMO

        $estado = "q0";
        $lexema = "";
        $posTokenInicio = 0;

        // controle de linha/coluna
        $linha = 1;
        $col = 1;
        $linhaIniToken = 1;
        $colIniToken = 1;

        $transicoes = Lexico::getTransicoes();
        $finais = Lexico::getFinais();

        $len = strlen($this->entrada);

        while ($this->pos < $len) {
            $char = $this->entrada[$this->pos];

            if (isset($transicoes[$estado][$char])) {
                // marca início do token ao capturar o primeiro char do lexema
                if ($lexema === "") {
                    $posTokenInicio = $this->pos;
                    $linhaIniToken  = $linha;
                    $colIniToken    = $col;
                }

                $estado = $transicoes[$estado][$char];
                $lexema .= $char;

                // avança posição + atualiza linha/col
                $this->pos++;
                if ($char === "\n") {
                    $linha++;
                    $col = 1;
                } else {
                    $col++;
                }
            }
            elseif (isset($finais[$estado])) {
                // fecha token
                if ($finais[$estado] !== "WS" && $lexema !== "") {
                    $this->tokens[] = new Token($finais[$estado], $lexema, $posTokenInicio, $linhaIniToken, $colIniToken);
                }

                // reseta para reprocessar char atual
                $lexema = "";
                $estado = "q0";
            }
            else {
                $this->erros[] =
                    "Erro léxico: caractere inválido '" . $char . "' próximo de '" . $lexema .
                    "' na posição " . $this->pos . " (linha {$linha}, col {$col})";

                // reseta e consome char inválido
                $lexema = "";
                $estado = "q0";
                $this->pos++;

                if ($char === "\n") {
                    $linha++;
                    $col = 1;
                } else {
                    $col++;
                }
            }
        }

        // token pendente no final
        if (isset($finais[$estado]) && $finais[$estado] !== "WS" && $lexema !== "") {
            $this->tokens[] = new Token($finais[$estado], $lexema, $posTokenInicio, $linhaIniToken, $colIniToken);
        } elseif ($lexema !== "" && !isset($finais[$estado])) {
            $this->erros[] =
                "Erro léxico: token incompleto '" . $lexema . "' na posição " . $posTokenInicio .
                " (linha {$linhaIniToken}, col {$colIniToken})";
        }
    }

    public function getTokens() { return $this->tokens; }
    public function getErros()  { return $this->erros; }

    public function nextToken() {
        $this->ultimoTokenIndex++;
        if ($this->ultimoTokenIndex < count($this->tokens)) {
            return $this->tokens[$this->ultimoTokenIndex];
        }
        return null;
    }

    public function prevToken() {
        if ($this->ultimoTokenIndex > 0) {
            $this->ultimoTokenIndex--;
            return $this->tokens[$this->ultimoTokenIndex];
        }
        return null;
    }

    public function reset(): void {
        $this->ultimoTokenIndex = -1;
    }

    public function __toString(): string {
        $out = "";
        foreach ($this->tokens as $t) $out .= $t . "\n";
        return $out;
    }
}
?>
