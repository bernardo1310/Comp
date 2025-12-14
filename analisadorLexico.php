<?php
require_once("token.php");
require_once("lexico.php");

/**
 * ============================================================================
 * CLASSE: AnalisadorLexico
 * ----------------------------------------------------------------------------
 * Realiza a análise léxica da cadeia de entrada utilizando o AFD definido em Lexico.
 *
 * - Percorre a string símbolo a símbolo.
 * - Utiliza transições do AFD para identificar tokens.
 * - Armazena tokens produzidos e erros léxicos.
 * ============================================================================
 */
class AnalisadorLexico {
    private $tokens = [];
    private $erros = [];
    private $entrada = "";
    private $pos = 0;
    private $ultimoTokenIndex = -1;

    public function analisar($entrada) {
        $this->tokens = [];
        $this->erros = [];
        $this->entrada = $entrada;
        $this->pos = 0;
        $estado = "q0";
        $lexema = "";
        $posTokenInicio = 0;

        $transicoes = Lexico::getTransicoes();
        $finais = Lexico::getFinais();

        while ($this->pos < strlen($entrada)) {
            $char = $entrada[$this->pos];

            // Verifica se existe transição para o caractere atual
            if (isset($transicoes[$estado][$char])) {
                $estado = $transicoes[$estado][$char];
                if ($lexema === "") {
                    $posTokenInicio = $this->pos;
                }
                $lexema .= $char;
                $this->pos++;
            } 
            // Se não há transição, verifica se o estado atual é final
            elseif (isset($finais[$estado])) {
                // Reconhece o token acumulado
                if ($finais[$estado] !== "WS" && $lexema !== "") {
                    $this->tokens[] = new Token($finais[$estado], $lexema, $posTokenInicio);
                }
                // Reseta para processar o caractere atual novamente
                $lexema = "";
                $estado = "q0";
                // NÃO incrementa $this->pos, reprocessa o caractere atual
            } 
            // Erro léxico: não há transição e não é estado final
            else {
                $this->erros[] = "Erro léxico: caractere inválido '" . $char . "' próximo de '" . $lexema . "' na posição " . $this->pos;
                $lexema = "";
                $estado = "q0";
                $this->pos++;
            }
        }

        // Verifica se há token pendente ao final da entrada
        if (isset($finais[$estado]) && $finais[$estado] !== "WS" && $lexema !== "") {
            $this->tokens[] = new Token($finais[$estado], $lexema, $posTokenInicio);
        } elseif ($lexema !== "" && !isset($finais[$estado])) {
            $this->erros[] = "Erro léxico: token incompleto '" . $lexema . "' na posição " . $posTokenInicio;
        }
    }

    public function getTokens() {
        return $this->tokens;
    }

    public function getErros() {
        return $this->erros;
    }

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

    public function reset() {
        $this->ultimoTokenIndex = -1;
    }

    public function __toString() {
        $out = "";
        foreach ($this->tokens as $t) {
            $out .= $t . "\n";
        }
        return $out;
    }
}
?>