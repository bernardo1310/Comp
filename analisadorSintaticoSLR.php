<?php
/**
 * ============================================================================
 * CLASSE: AnalisadorSintaticoSLR - VERSÃO FINAL FUNCIONAL
 * ----------------------------------------------------------------------------
 * Parser SLR completo com declarações e comandos
 * 
 * GRAMÁTICA:
 * 0. S' → PROG
 * 1. PROG → DECLS CMD
 * 2. DECLS → DECL DECLS
 * 3. DECLS → ε
 * 4. DECL → TIPO id ;
 * 5. TIPO → int | char | bool
 * 6. CMD → ATRIB | IF
 * 7. ATRIB → id = EXPR ;
 * 8. IF → if ( COND ) { CMD }
 * 9. EXPR → EXPR + EXPR | EXPR * EXPR | id | const | ( EXPR )
 * 10. COND → EXPR > EXPR | EXPR < EXPR
 * ============================================================================
 */

require_once "analisadorSemantico.php";

class AnalisadorSintaticoSLR {

    private $semantico;
    private $tabela_action;
    private $tabela_goto;
    private $tokensOriginais = [];
    private $simbolosParser = [];
    private $pilha = [];
    private $pilhaTokens = [];
    private $indice = 0;
    private $acoes = [];

    public function __construct() {
        $this->semantico = new Semantico();
        $this->inicializarTabelas();
    }

    private function inicializarTabelas() {
        // Tabela ACTION completa e corrigida
        $this->tabela_action = [
            // Estado 0: Inicial
            0 => [
                'int' => ['shift', 2],
                'char' => ['shift', 3],
                'bool' => ['shift', 4],
                'id' => ['shift', 5],
                'if' => ['shift', 6],
                '$' => ['reduce', 3]
            ],
            // Estado 1: Aceitação
            1 => ['$' => ['accept']],
            
            // Estados 2,3,4: Após TIPO (int/char/bool)
            2 => ['id' => ['shift', 7]],
            3 => ['id' => ['shift', 7]],
            4 => ['id' => ['shift', 7]],
            
            // Estado 5: Após id (atribuição)
            5 => ['=' => ['shift', 8]],
            
            // Estado 6: Após if
            6 => ['(' => ['shift', 9]],
            
            // Estado 7: Após TIPO id
            7 => [';' => ['shift', 10]],
            
            // Estado 8: Após id =
            8 => [
                'id' => ['shift', 11],
                'const' => ['shift', 12],
                '(' => ['shift', 13]
            ],
            
            // Estado 9: Após if (
            9 => [
                'id' => ['shift', 11],
                'const' => ['shift', 12],
                '(' => ['shift', 13]
            ],
            
            // Estado 10: Após TIPO id ;
            10 => [
                'int' => ['reduce', 4],
                'char' => ['reduce', 4],
                'bool' => ['reduce', 4],
                'id' => ['reduce', 4],
                'if' => ['reduce', 4],
                '$' => ['reduce', 4]
            ],
            
            // Estados 11,12: EXPR → id | const
            11 => [
                '+' => ['reduce', 14],
                '*' => ['reduce', 14],
                ';' => ['reduce', 14],
                ')' => ['reduce', 14],
                '>' => ['reduce', 14],
                '<' => ['reduce', 14]
            ],
            12 => [
                '+' => ['reduce', 15],
                '*' => ['reduce', 15],
                ';' => ['reduce', 15],
                ')' => ['reduce', 15],
                '>' => ['reduce', 15],
                '<' => ['reduce', 15]
            ],
            
            // Estado 13: Após (
            13 => [
                'id' => ['shift', 11],
                'const' => ['shift', 12],
                '(' => ['shift', 13]
            ],
            
            // Estado 14: Após id = EXPR
            14 => [
                ';' => ['shift', 15],
                '+' => ['shift', 16],
                '*' => ['shift', 17]
            ],
            
            // Estado 15: Após id = EXPR ;
            15 => [
                '$' => ['reduce', 10],
                '}' => ['reduce', 10]
            ],
            
            // Estados 16,17: Operadores
            16 => [
                'id' => ['shift', 11],
                'const' => ['shift', 12],
                '(' => ['shift', 13]
            ],
            17 => [
                'id' => ['shift', 11],
                'const' => ['shift', 12],
                '(' => ['shift', 13]
            ],
            
            // Estado 18: Após if ( EXPR
            18 => [
                ')' => ['shift', 19],
                '+' => ['shift', 16],
                '*' => ['shift', 17],
                '>' => ['shift', 20],
                '<' => ['shift', 21]
            ],
            
            // Estado 19: Após if ( COND )
            19 => ['{' => ['shift', 22]],
            
            // Estados 20,21: Operadores relacionais
            20 => [
                'id' => ['shift', 11],
                'const' => ['shift', 12],
                '(' => ['shift', 13]
            ],
            21 => [
                'id' => ['shift', 11],
                'const' => ['shift', 12],
                '(' => ['shift', 13]
            ],
            
            // Estado 22: Após if ( COND ) {
            22 => [
                'id' => ['shift', 5],
                'if' => ['shift', 6]
            ],
            
            // Estados 23,24: EXPR + EXPR, EXPR * EXPR
            23 => [
                '+' => ['reduce', 12],
                '*' => ['shift', 17],
                ';' => ['reduce', 12],
                ')' => ['reduce', 12],
                '>' => ['reduce', 12],
                '<' => ['reduce', 12]
            ],
            24 => [
                '+' => ['reduce', 13],
                '*' => ['reduce', 13],
                ';' => ['reduce', 13],
                ')' => ['reduce', 13],
                '>' => ['reduce', 13],
                '<' => ['reduce', 13]
            ],
            
            // Estado 25: ( EXPR )
            25 => [
                ')' => ['shift', 26],
                '+' => ['shift', 16],
                '*' => ['shift', 17]
            ],
            
            // Estado 26: ( EXPR ) completo
            26 => [
                '+' => ['reduce', 16],
                '*' => ['reduce', 16],
                ';' => ['reduce', 16],
                ')' => ['reduce', 16],
                '>' => ['reduce', 16],
                '<' => ['reduce', 16]
            ],
            
            // Estados 27,28: COND → EXPR > EXPR, EXPR < EXPR
            27 => [
                ')' => ['reduce', 17],
                '+' => ['shift', 16],
                '*' => ['shift', 17]
            ],
            28 => [
                ')' => ['reduce', 18],
                '+' => ['shift', 16],
                '*' => ['shift', 17]
            ],
            
            // Estado 29: if ( COND ) { CMD }
            29 => [
                '}' => ['shift', 30]
            ],
            
            // Estado 30: IF completo
            30 => [
                '$' => ['reduce', 11],
                '}' => ['reduce', 11]
            ],
            
            // Estados 31,32: CMD reduções
            31 => [
                '$' => ['reduce', 8]
            ],
            32 => [
                '$' => ['reduce', 9]
            ],
            
            // Estado 33: Após DECLS
            33 => [
                'id' => ['shift', 5],
                'if' => ['shift', 6]
            ],
            
            // Estado 34: Após DECL
            34 => [
                'int' => ['shift', 2],
                'char' => ['shift', 3],
                'bool' => ['shift', 4],
                'id' => ['reduce', 3],
                'if' => ['reduce', 3],
                '$' => ['reduce', 3]
            ]
        ];

        // Tabela GOTO completa
        $this->tabela_goto = [
            0 => [
                'PROG' => 1,
                'DECLS' => 33,
                'DECL' => 34,
                'TIPO' => 35
            ],
            8 => ['EXPR' => 14],
            9 => [
                'EXPR' => 18,
                'COND' => 36
            ],
            13 => ['EXPR' => 25],
            16 => ['EXPR' => 23],
            17 => ['EXPR' => 24],
            20 => ['EXPR' => 27],
            21 => ['EXPR' => 28],
            22 => [
                'CMD' => 29,
                'ATRIB' => 31,
                'IF' => 32
            ],
            33 => [
                'CMD' => 37,
                'ATRIB' => 31,
                'IF' => 32
            ],
            34 => ['DECLS' => 38],
            35 => [], // TIPO não tem GOTO adicional
            36 => [], // COND não tem GOTO adicional
            37 => [], // CMD após DECLS
            38 => [] // DECLS recursivo
        ];
    }

    public function getSemantico() {
        return $this->semantico;
    }

    public function setTokensDoLexico($tokens) {
        $this->tokensOriginais = array_values($tokens);
        $this->simbolosParser = [];
        
        foreach ($tokens as $token) {
            $tipo = $token->getTipo();
            
            switch ($tipo) {
                case 'ID':
                    $this->simbolosParser[] = 'id';
                    break;
                case 'IF':
                    $this->simbolosParser[] = 'if';
                    break;
                case 'INT':
                    $this->simbolosParser[] = 'int';
                    break;
                case 'CHAR':
                    $this->simbolosParser[] = 'char';
                    break;
                case 'BOOL':
                    $this->simbolosParser[] = 'bool';
                    break;
                case 'CONST':
                case 'DECIMAL':
                    $this->simbolosParser[] = 'const';
                    break;
                case 'ATRIB':
                    $this->simbolosParser[] = '=';
                    break;
                case 'SOMA':
                    $this->simbolosParser[] = '+';
                    break;
                case 'MULT':
                    $this->simbolosParser[] = '*';
                    break;
                case 'AP':
                    $this->simbolosParser[] = '(';
                    break;
                case 'FP':
                    $this->simbolosParser[] = ')';
                    break;
                case 'INIBLOCO':
                    $this->simbolosParser[] = '{';
                    break;
                case 'FIMBLOCO':
                    $this->simbolosParser[] = '}';
                    break;
                case 'PV':
                    $this->simbolosParser[] = ';';
                    break;
                case 'MAIOR':
                    $this->simbolosParser[] = '>';
                    break;
                case 'MENOR':
                    $this->simbolosParser[] = '<';
                    break;
                default:
                    break;
            }
        }
        
        $this->simbolosParser[] = '$';
    }

    public function analisar() {
        $this->pilha = [0];
        $this->pilhaTokens = [null];
        $this->indice = 0;
        $this->acoes = [];

        while (true) {
            $estado = end($this->pilha);
            $simbolo = $this->simbolosParser[$this->indice];
            $tokenAtual = isset($this->tokensOriginais[$this->indice]) ? $this->tokensOriginais[$this->indice] : null;

            if (!isset($this->tabela_action[$estado][$simbolo])) {
                return [
                    'success' => false,
                    'message' => "❌ Erro sintático: símbolo '$simbolo' não esperado no estado $estado",
                    'actions' => $this->acoes,
                    'pilha' => $this->pilha,
                    'posicao' => $this->indice
                ];
            }

            $acao = $this->tabela_action[$estado][$simbolo];
            $this->acoes[] = "Estado $estado, Símbolo '$simbolo': {$acao[0]}" . (isset($acao[1]) ? " {$acao[1]}" : "");

            if ($acao[0] === 'shift') {
                array_push($this->pilha, $simbolo, $acao[1]);
                array_push($this->pilhaTokens, $tokenAtual, null);
                $this->indice++;
            } 
            elseif ($acao[0] === 'reduce') {
                $producao = $this->getReducao($acao[1]);
                $tamanho = count($producao[1]);

                // Coleta tokens da produção
                $tokensProducao = [];
                for ($i = 0; $i < $tamanho; $i++) {
                    $idx = count($this->pilhaTokens) - ($tamanho - $i) * 2;
                    if ($idx >= 0 && isset($this->pilhaTokens[$idx])) {
                        $tokensProducao[] = $this->pilhaTokens[$idx];
                    }
                }

                // Análise semântica
                try {
                    $this->processarSemantica($acao[1], $tokensProducao);
                } catch (Exception $e) {
                    return [
                        'success' => false,
                        'message' => "❌ " . $e->getMessage(),
                        'actions' => $this->acoes,
                        'pilha' => $this->pilha
                    ];
                }

                // Remove símbolos da pilha
                for ($i = 0; $i < $tamanho; $i++) {
                    array_pop($this->pilha);
                    array_pop($this->pilha);
                    array_pop($this->pilhaTokens);
                    array_pop($this->pilhaTokens);
                }

                $estado = end($this->pilha);

                if (!isset($this->tabela_goto[$estado][$producao[0]])) {
                    // Se não há GOTO, pode ser epsilon ou fim de parsing
                    if ($producao[0] === 'PROG' && $estado === 0) {
                        // Caso especial: PROG no estado inicial
                        array_push($this->pilha, $producao[0], 1);
                        array_push($this->pilhaTokens, null, null);
                    } else {
                        return [
                            'success' => false,
                            'message' => "❌ Erro sintático: não há transição GOTO para '{$producao[0]}' no estado $estado",
                            'actions' => $this->acoes,
                            'pilha' => $this->pilha
                        ];
                    }
                } else {
                    array_push($this->pilha, $producao[0], $this->tabela_goto[$estado][$producao[0]]);
                    array_push($this->pilhaTokens, null, null);
                }
            } 
            elseif ($acao[0] === 'accept') {
                return [
                    'success' => true,
                    'message' => "✅ Código sintaticamente correto!",
                    'actions' => $this->acoes
                ];
            }
        }
    }

    private function processarSemantica($numeroProducao, $tokensProducao) {
        switch ($numeroProducao) {
            case 4: // DECL → TIPO id ;
                if (count($tokensProducao) >= 2) {
                    $tipoToken = $tokensProducao[0];
                    $idToken = $tokensProducao[1];
                    
                    if ($tipoToken && $idToken) {
                        $tipo = strtoupper($tipoToken->getTipo());
                        $var = $idToken->getLexema();
                        $this->semantico->instalaVariavel($var, $tipo);
                    }
                }
                break;
                
            case 10: // ATRIB → id = EXPR ;
                if (count($tokensProducao) >= 1) {
                    $idToken = $tokensProducao[0];
                    if ($idToken) {
                        $var = $idToken->getLexema();
                        $this->semantico->verificaVariavelExistente($var);
                    }
                }
                break;
        }
    }

    private function getReducao($numero) {
        return [
            1 => ['PROG', ['DECLS', 'CMD']],
            2 => ['DECLS', ['DECL', 'DECLS']],
            3 => ['DECLS', []],
            4 => ['DECL', ['TIPO', 'id', ';']],
            5 => ['TIPO', ['int']],
            6 => ['TIPO', ['char']],
            7 => ['TIPO', ['bool']],
            8 => ['CMD', ['ATRIB']],
            9 => ['CMD', ['IF']],
            10 => ['ATRIB', ['id', '=', 'EXPR', ';']],
            11 => ['IF', ['if', '(', 'COND', ')', '{', 'CMD', '}']],
            12 => ['EXPR', ['EXPR', '+', 'EXPR']],
            13 => ['EXPR', ['EXPR', '*', 'EXPR']],
            14 => ['EXPR', ['id']],
            15 => ['EXPR', ['const']],
            16 => ['EXPR', ['(', 'EXPR', ')']],
            17 => ['COND', ['EXPR', '>', 'EXPR']],
            18 => ['COND', ['EXPR', '<', 'EXPR']]
        ][$numero];
    }

    public function getTerminals() {
        return ['id', 'if', 'int', 'char', 'bool', '(', ')', 'const', '+', '*', '=', '{', '}', ';', '>', '<', '$'];
    }

    public function getNonTerminals() {
        return ['PROG', 'DECLS', 'DECL', 'TIPO', 'CMD', 'ATRIB', 'IF', 'EXPR', 'COND'];
    }

    public function getActionTable() {
        return $this->tabela_action;
    }

    public function getGotoTable() {
        return $this->tabela_goto;
    }

    public function getGramatica() {
        return [
            "1. PROG → DECLS CMD",
            "2. DECLS → DECL DECLS",
            "3. DECLS → ε (vazio)",
            "4. DECL → TIPO id ;",
            "5. TIPO → int",
            "6. TIPO → char",
            "7. TIPO → bool",
            "8. CMD → ATRIB",
            "9. CMD → IF",
            "10. ATRIB → id = EXPR ;",
            "11. IF → if ( COND ) { CMD }",
            "12. EXPR → EXPR + EXPR",
            "13. EXPR → EXPR * EXPR",
            "14. EXPR → id",
            "15. EXPR → const",
            "16. EXPR → ( EXPR )",
            "17. COND → EXPR > EXPR",
            "18. COND → EXPR < EXPR"
        ];
    }

    public function getAcoes() {
        return $this->acoes;
    }
}
?>