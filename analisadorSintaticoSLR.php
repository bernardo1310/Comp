<?php
require_once __DIR__ . "/analisadorSemantico.php";
require_once __DIR__ . "/GeradorCodigoAssembly.php";

/**
 * ============================================================================
 * AnalisadorSintaticoSLR (SLR(1)) - VERSÃO CORRIGIDA
 * ----------------------------------------------------------------------------
 * Correções principais:
 * 1) Agora a gramática aceita LISTA de comandos (CMDS), então isso funciona:
 *    int x;
 *    x = 10 + 2 * 3;
 *    if (x > 10) { x = 1; }
 *
 * 2) Expressões com precedência:
 *    EXPR (+) TERM (*) FACT
 *
 * 3) Condições suportadas:
 *    >  <  >=  <=  ==  !=
 * ============================================================================
 */
class AnalisadorSintaticoSLR {

    private Semantico $semantico;
    private GeradorCodigoAssembly $gerador;

    private array $tabela_action = [];
    private array $tabela_goto   = [];

    private array $tokensOriginais = [];
    private array $simbolosParser  = [];

    private array $pilha = [];
    private array $pilhaTokens = [];

    private int $indice = 0;
    private array $acoes  = [];

    public function __construct() {
        $this->semantico = new Semantico();
        $this->gerador   = new GeradorCodigoAssembly();
        $this->inicializarTabelas();
    }

    public function getSemantico(): Semantico {
        return $this->semantico;
    }

    public function getGerador(): GeradorCodigoAssembly {
        return $this->gerador;
    }

    public function getAssembly(): string {
        return $this->gerador->build();
    }

    private function inicializarTabelas(): void {
        $this->tabela_action = [
    0 => [
        'id' => ['reduce', 3],
        'if' => ['reduce', 3],
        'int' => ['shift', 2],
        'char' => ['shift', 1],
        'bool' => ['shift', 4],
        '$' => ['reduce', 3],
    ],
    1 => [
        'id' => ['reduce', 6],
    ],
    2 => [
        'id' => ['reduce', 5],
    ],
    3 => [
        '$' => ['accept'],
    ],
    4 => [
        'id' => ['reduce', 7],
    ],
    5 => [
        'id' => ['reduce', 3],
        'if' => ['reduce', 3],
        'int' => ['shift', 2],
        'char' => ['shift', 1],
        'bool' => ['shift', 4],
        '$' => ['reduce', 3],
    ],
    6 => [
        'id' => ['shift', 9],
    ],
    7 => [
        'id' => ['shift', 12],
        'if' => ['shift', 11],
        '}' => ['reduce', 9],
        '$' => ['reduce', 9],
    ],
    8 => [
        'id' => ['reduce', 2],
        'if' => ['reduce', 2],
        '$' => ['reduce', 2],
    ],
    9 => [
        ';' => ['shift', 16],
    ],
    10 => [
        'id' => ['shift', 12],
        'if' => ['shift', 11],
        '}' => ['reduce', 9],
        '$' => ['reduce', 9],
    ],
    11 => [
        '(' => ['shift', 18],
    ],
    12 => [
        '=' => ['shift', 19],
    ],
    13 => [
        'id' => ['reduce', 10],
        'if' => ['reduce', 10],
        '}' => ['reduce', 10],
        '$' => ['reduce', 10],
    ],
    14 => [
        'id' => ['reduce', 11],
        'if' => ['reduce', 11],
        '}' => ['reduce', 11],
        '$' => ['reduce', 11],
    ],
    15 => [
        '$' => ['reduce', 1],
    ],
    16 => [
        'id' => ['reduce', 4],
        'if' => ['reduce', 4],
        'int' => ['reduce', 4],
        'char' => ['reduce', 4],
        'bool' => ['reduce', 4],
        '$' => ['reduce', 4],
    ],
    17 => [
        '}' => ['reduce', 8],
        '$' => ['reduce', 8],
    ],
    18 => [
        'id' => ['shift', 22],
        '(' => ['shift', 26],
        'const' => ['shift', 20],
    ],
    19 => [
        'id' => ['shift', 22],
        '(' => ['shift', 26],
        'const' => ['shift', 20],
    ],
    20 => [
        ')' => ['reduce', 25],
        '+' => ['reduce', 25],
        '*' => ['reduce', 25],
        ';' => ['reduce', 25],
        '>' => ['reduce', 25],
        '<' => ['reduce', 25],
        '>=' => ['reduce', 25],
        '<=' => ['reduce', 25],
        '==' => ['reduce', 25],
        '!=' => ['reduce', 25],
    ],
    21 => [
        ')' => ['shift', 28],
    ],
    22 => [
        ')' => ['reduce', 24],
        '+' => ['reduce', 24],
        '*' => ['reduce', 24],
        ';' => ['reduce', 24],
        '>' => ['reduce', 24],
        '<' => ['reduce', 24],
        '>=' => ['reduce', 24],
        '<=' => ['reduce', 24],
        '==' => ['reduce', 24],
        '!=' => ['reduce', 24],
    ],
    23 => [
        ')' => ['reduce', 23],
        '+' => ['reduce', 23],
        '*' => ['reduce', 23],
        ';' => ['reduce', 23],
        '>' => ['reduce', 23],
        '<' => ['reduce', 23],
        '>=' => ['reduce', 23],
        '<=' => ['reduce', 23],
        '==' => ['reduce', 23],
        '!=' => ['reduce', 23],
    ],
    24 => [
        '+' => ['shift', 31],
        '>' => ['shift', 35],
        '<' => ['shift', 32],
        '>=' => ['shift', 34],
        '<=' => ['shift', 33],
        '==' => ['shift', 30],
        '!=' => ['shift', 29],
    ],
    25 => [
        ')' => ['reduce', 21],
        '+' => ['reduce', 21],
        '*' => ['shift', 36],
        ';' => ['reduce', 21],
        '>' => ['reduce', 21],
        '<' => ['reduce', 21],
        '>=' => ['reduce', 21],
        '<=' => ['reduce', 21],
        '==' => ['reduce', 21],
        '!=' => ['reduce', 21],
    ],
    26 => [
        'id' => ['shift', 22],
        '(' => ['shift', 26],
        'const' => ['shift', 20],
    ],
    27 => [
        '+' => ['shift', 31],
        ';' => ['shift', 38],
    ],
    28 => [
        '{' => ['shift', 39],
    ],
    29 => [
        'id' => ['shift', 22],
        '(' => ['shift', 26],
        'const' => ['shift', 20],
    ],
    30 => [
        'id' => ['shift', 22],
        '(' => ['shift', 26],
        'const' => ['shift', 20],
    ],
    31 => [
        'id' => ['shift', 22],
        '(' => ['shift', 26],
        'const' => ['shift', 20],
    ],
    32 => [
        'id' => ['shift', 22],
        '(' => ['shift', 26],
        'const' => ['shift', 20],
    ],
    33 => [
        'id' => ['shift', 22],
        '(' => ['shift', 26],
        'const' => ['shift', 20],
    ],
    34 => [
        'id' => ['shift', 22],
        '(' => ['shift', 26],
        'const' => ['shift', 20],
    ],
    35 => [
        'id' => ['shift', 22],
        '(' => ['shift', 26],
        'const' => ['shift', 20],
    ],
    36 => [
        'id' => ['shift', 22],
        '(' => ['shift', 26],
        'const' => ['shift', 20],
    ],
    37 => [
        ')' => ['shift', 48],
        '+' => ['shift', 31],
    ],
    38 => [
        'id' => ['reduce', 12],
        'if' => ['reduce', 12],
        '}' => ['reduce', 12],
        '$' => ['reduce', 12],
    ],
    39 => [
        'id' => ['shift', 12],
        'if' => ['shift', 11],
        '}' => ['reduce', 9],
        '$' => ['reduce', 9],
    ],
    40 => [
        ')' => ['reduce', 19],
        '+' => ['shift', 31],
    ],
    41 => [
        ')' => ['reduce', 18],
        '+' => ['shift', 31],
    ],
    42 => [
        ')' => ['reduce', 20],
        '+' => ['reduce', 20],
        '*' => ['shift', 36],
        ';' => ['reduce', 20],
        '>' => ['reduce', 20],
        '<' => ['reduce', 20],
        '>=' => ['reduce', 20],
        '<=' => ['reduce', 20],
        '==' => ['reduce', 20],
        '!=' => ['reduce', 20],
    ],
    43 => [
        ')' => ['reduce', 15],
        '+' => ['shift', 31],
    ],
    44 => [
        ')' => ['reduce', 17],
        '+' => ['shift', 31],
    ],
    45 => [
        ')' => ['reduce', 16],
        '+' => ['shift', 31],
    ],
    46 => [
        ')' => ['reduce', 14],
        '+' => ['shift', 31],
    ],
    47 => [
        ')' => ['reduce', 22],
        '+' => ['reduce', 22],
        '*' => ['reduce', 22],
        ';' => ['reduce', 22],
        '>' => ['reduce', 22],
        '<' => ['reduce', 22],
        '>=' => ['reduce', 22],
        '<=' => ['reduce', 22],
        '==' => ['reduce', 22],
        '!=' => ['reduce', 22],
    ],
    48 => [
        ')' => ['reduce', 26],
        '+' => ['reduce', 26],
        '*' => ['reduce', 26],
        ';' => ['reduce', 26],
        '>' => ['reduce', 26],
        '<' => ['reduce', 26],
        '>=' => ['reduce', 26],
        '<=' => ['reduce', 26],
        '==' => ['reduce', 26],
        '!=' => ['reduce', 26],
    ],
    49 => [
        '}' => ['shift', 50],
    ],
    50 => [
        'id' => ['reduce', 13],
        'if' => ['reduce', 13],
        '}' => ['reduce', 13],
        '$' => ['reduce', 13],
    ],
];

        $this->tabela_goto = [
    0 => [
        'PROG' => 3,
        'DECLS' => 7,
        'DECL' => 5,
        'TIPO' => 6,
    ],
    5 => [
        'DECLS' => 8,
        'DECL' => 5,
        'TIPO' => 6,
    ],
    7 => [
        'CMDS' => 15,
        'CMD' => 10,
        'ATRIB' => 13,
        'IF' => 14,
    ],
    10 => [
        'CMDS' => 17,
        'CMD' => 10,
        'ATRIB' => 13,
        'IF' => 14,
    ],
    18 => [
        'EXPR' => 24,
        'TERM' => 25,
        'FACT' => 23,
        'COND' => 21,
    ],
    19 => [
        'EXPR' => 27,
        'TERM' => 25,
        'FACT' => 23,
    ],
    26 => [
        'EXPR' => 37,
        'TERM' => 25,
        'FACT' => 23,
    ],
    29 => [
        'EXPR' => 40,
        'TERM' => 25,
        'FACT' => 23,
    ],
    30 => [
        'EXPR' => 41,
        'TERM' => 25,
        'FACT' => 23,
    ],
    31 => [
        'TERM' => 42,
        'FACT' => 23,
    ],
    32 => [
        'EXPR' => 43,
        'TERM' => 25,
        'FACT' => 23,
    ],
    33 => [
        'EXPR' => 44,
        'TERM' => 25,
        'FACT' => 23,
    ],
    34 => [
        'EXPR' => 45,
        'TERM' => 25,
        'FACT' => 23,
    ],
    35 => [
        'EXPR' => 46,
        'TERM' => 25,
        'FACT' => 23,
    ],
    36 => [
        'FACT' => 47,
    ],
    39 => [
        'CMDS' => 49,
        'CMD' => 10,
        'ATRIB' => 13,
        'IF' => 14,
    ],
];
    }

    public function setTokensDoLexico(array $tokens): void {
        $this->tokensOriginais = array_values($tokens ?? []);
        $this->simbolosParser  = [];

        foreach ($this->tokensOriginais as $token) {
            $tipo = $token->getTipo();

            switch ($tipo) {
                case 'ID':       $this->simbolosParser[] = 'id';    break;
                case 'IF':       $this->simbolosParser[] = 'if';    break;
                case 'INT':      $this->simbolosParser[] = 'int';   break;
                case 'CHAR':     $this->simbolosParser[] = 'char';  break;
                case 'BOOL':     $this->simbolosParser[] = 'bool';  break;

                case 'CONST':
                case 'DECIMAL':  $this->simbolosParser[] = 'const'; break;

                case 'ATRIB':    $this->simbolosParser[] = '=';     break;
                case 'SOMA':     $this->simbolosParser[] = '+';     break;
                case 'MULT':     $this->simbolosParser[] = '*';     break;

                case 'AP':       $this->simbolosParser[] = '(';     break;
                case 'FP':       $this->simbolosParser[] = ')';     break;

                case 'INIBLOCO': $this->simbolosParser[] = '{';     break;
                case 'FIMBLOCO': $this->simbolosParser[] = '}';     break;

                case 'PV':       $this->simbolosParser[] = ';';     break;

                case 'MAIOR':      $this->simbolosParser[] = '>';   break;
                case 'MENOR':      $this->simbolosParser[] = '<';   break;
                case 'MAIORIGUAL': $this->simbolosParser[] = '>=';  break;
                case 'MENORIGUAL': $this->simbolosParser[] = '<=';  break;
                case 'IGUAL':      $this->simbolosParser[] = '==';  break;
                case 'DIFERENTE':  $this->simbolosParser[] = '!=';  break;

                default:
                    // tokens fora da gramática atual são ignorados
                    break;
            }
        }

        $this->simbolosParser[] = '$';
    }

    public function analisar(): array {
        $this->pilha = [0];
        $this->pilhaTokens = [null];
        $this->indice = 0;
        $this->acoes  = [];

        $this->gerador->reset();
        $this->gerador->beginProgram();

        while (true) {
            $estado  = end($this->pilha);
            $simbolo = $this->simbolosParser[$this->indice] ?? '$';
            $tokenAtual = $this->tokensOriginais[$this->indice] ?? null;

            if (!isset($this->tabela_action[$estado][$simbolo])) {
                return [
                    'success' => false,
                    'message' => "Erro sintático: símbolo '$simbolo' não esperado no estado $estado",
                    'actions' => $this->acoes,
                    'pilha'   => $this->pilha,
                    'posicao' => $this->indice,
                ];
            }

            $acao = $this->tabela_action[$estado][$simbolo];
            $this->acoes[] = "Estado $estado, Símbolo '$simbolo': {$acao[0]}" . (isset($acao[1]) ? " {$acao[1]}" : "");

            if ($acao[0] === 'shift') {
                array_push($this->pilha, $simbolo, $acao[1]);
                array_push($this->pilhaTokens, $tokenAtual, null);
                $this->indice++;
                continue;
            }

            if ($acao[0] === 'reduce') {
                $num = (int)$acao[1];
                $producao = $this->getReducao($num);

                if ($producao === null) {
                    return [
                        'success' => false,
                        'message' => "Redução inválida: r{$num}",
                        'actions' => $this->acoes,
                        'pilha'   => $this->pilha,
                    ];
                }

                [$lhs, $rhs] = $producao;
                $tamanho = count($rhs);

                // pega os tokens do RHS (1 token a cada 2 posições na pilhaTokens)
                $tokensProducao = [];
                for ($i = 0; $i < $tamanho; $i++) {
                    $idx = count($this->pilhaTokens) - ($tamanho - $i) * 2;
                    $tokensProducao[] = ($idx >= 0 && array_key_exists($idx, $this->pilhaTokens))
                        ? $this->pilhaTokens[$idx]
                        : null;
                }

                try {
                    $this->processarSemantica($num, $tokensProducao);
                    $this->processarGeracao($num, $tokensProducao);
                } catch (Exception $e) {
                    return [
                        'success' => false,
                        'message' => "" . $e->getMessage(),
                        'actions' => $this->acoes,
                        'pilha'   => $this->pilha,
                    ];
                }

                for ($i = 0; $i < $tamanho; $i++) {
                    array_pop($this->pilha);
                    array_pop($this->pilha);
                    array_pop($this->pilhaTokens);
                    array_pop($this->pilhaTokens);
                }

                $estadoTopo = end($this->pilha);

                if (!isset($this->tabela_goto[$estadoTopo][$lhs])) {
                    return [
                        'success' => false,
                        'message' => "Erro sintático: não há GOTO para '$lhs' no estado $estadoTopo",
                        'actions' => $this->acoes,
                        'pilha'   => $this->pilha,
                    ];
                }

                $novoEstado = $this->tabela_goto[$estadoTopo][$lhs];
                $tokenSint = $this->sintetizarToken($num, $tokensProducao);

                array_push($this->pilha, $lhs, $novoEstado);
                array_push($this->pilhaTokens, $tokenSint, null);

                continue;
            }

            if ($acao[0] === 'accept') {
                $this->gerador->endProgram();
                return [
                    'success' => true,
                    'message' => "Código sintaticamente correto!",
                    'actions' => $this->acoes,
                ];
            }
        }
    }

    private function sintetizarToken(int $r, array $tokens) {
        // Precisamos manter o token do TIPO para o reduce de DECL.
        switch ($r) {
            case 5: // TIPO -> int
            case 6: // TIPO -> char
            case 7: // TIPO -> bool
                return $tokens[0] ?? null;
            default:
                return null;
        }
    }

    private function processarSemantica(int $r, array $tokens): void {
        switch ($r) {
            case 4: { // DECL -> TIPO id ;
                $tipoToken = $tokens[0] ?? null;
                $idToken   = $tokens[1] ?? null;
                if ($tipoToken && $idToken) {
                    $tipo = strtoupper($tipoToken->getTipo());
                    $var  = $idToken->getLexema();
                    $this->semantico->instalaVariavel($var, $tipo);
                }
                break;
            }

            case 12: { // ATRIB -> id = EXPR ;
                $idToken = $tokens[0] ?? null;
                if ($idToken) {
                    $var = $idToken->getLexema();
                    $this->semantico->verificaVariavelExistente($var);
                }
                break;
            }
        }
    }

    private function processarGeracao(int $r, array $tokens): void {
        switch ($r) {

            // DECL -> TIPO id ;
            case 4: {
                $tipoToken = $tokens[0] ?? null;
                $idToken   = $tokens[1] ?? null;
                if ($tipoToken && $idToken) {
                    $tipo = strtoupper($tipoToken->getTipo());
                    $var  = $idToken->getLexema();
                    $this->gerador->declareVar($var, $tipo);
                }
                break;
            }

            // FACT -> id
            case 24: {
                $idToken = $tokens[0] ?? null;
                if ($idToken) {
                    $this->gerador->pushVar($idToken->getLexema());
                }
                break;
            }

            // FACT -> const
            case 25: {
                $cToken = $tokens[0] ?? null;
                if ($cToken) {
                    $this->gerador->pushConstFromLexeme($cToken->getLexema());
                }
                break;
            }

            // TERM -> TERM * FACT
            case 22:
                $this->gerador->exprMul();
                break;

            // EXPR -> EXPR + TERM
            case 20:
                $this->gerador->exprAdd();
                break;

            // ATRIB -> id = EXPR ;
            case 12: {
                $idToken = $tokens[0] ?? null;
                if ($idToken) {
                    $this->gerador->assignFromTopExpr($idToken->getLexema());
                }
                break;
            }

            // COND -> EXPR op EXPR
            case 14: $this->gerador->beginIf('>');  break;
            case 15: $this->gerador->beginIf('<');  break;
            case 16: $this->gerador->beginIf('>='); break;
            case 17: $this->gerador->beginIf('<='); break;
            case 18: $this->gerador->beginIf('=='); break;
            case 19: $this->gerador->beginIf('!='); break;

            // IF -> if ( COND ) { CMDS }
            case 13:
                $this->gerador->endIf();
                break;
        }
    }

    private function getReducao(int $n): ?array {
        // IDs de redução BATEM com a tabela SLR desta versão.
        $map = [
            1  => ['PROG',  ['DECLS', 'CMDS']],
            2  => ['DECLS', ['DECL', 'DECLS']],
            3  => ['DECLS', []],
            4  => ['DECL',  ['TIPO', 'id', ';']],
            5  => ['TIPO',  ['int']],
            6  => ['TIPO',  ['char']],
            7  => ['TIPO',  ['bool']],

            8  => ['CMDS',  ['CMD', 'CMDS']],
            9  => ['CMDS',  []],
            10 => ['CMD',   ['ATRIB']],
            11 => ['CMD',   ['IF']],

            12 => ['ATRIB', ['id', '=', 'EXPR', ';']],
            13 => ['IF',    ['if', '(', 'COND', ')', '{', 'CMDS', '}']],

            14 => ['COND',  ['EXPR', '>',  'EXPR']],
            15 => ['COND',  ['EXPR', '<',  'EXPR']],
            16 => ['COND',  ['EXPR', '>=', 'EXPR']],
            17 => ['COND',  ['EXPR', '<=', 'EXPR']],
            18 => ['COND',  ['EXPR', '==', 'EXPR']],
            19 => ['COND',  ['EXPR', '!=', 'EXPR']],

            20 => ['EXPR',  ['EXPR', '+', 'TERM']],
            21 => ['EXPR',  ['TERM']],

            22 => ['TERM',  ['TERM', '*', 'FACT']],
            23 => ['TERM',  ['FACT']],

            24 => ['FACT',  ['id']],
            25 => ['FACT',  ['const']],
            26 => ['FACT',  ['(', 'EXPR', ')']],
        ];

        return $map[$n] ?? null;
    }

    // ===== getters usados no index =====

    public function getTerminals(): array {
        return ['id','if','int','char','bool','(',')','const','+','*','=','{','}',';','>','<','>=','<=','==','!=','$'];
    }

    public function getNonTerminals(): array {
        return ['PROG','DECLS','DECL','TIPO','CMDS','CMD','ATRIB','IF','EXPR','TERM','FACT','COND'];
    }

    public function getActionTable(): array { return $this->tabela_action; }
    public function getGotoTable(): array { return $this->tabela_goto; }
    public function getGramatica(): array {
        return [
            "1.  PROG  → DECLS CMDS",
            "2.  DECLS → DECL DECLS",
            "3.  DECLS → ε",
            "4.  DECL  → TIPO id ;",
            "5.  TIPO  → int",
            "6.  TIPO  → char",
            "7.  TIPO  → bool",
            "8.  CMDS  → CMD CMDS",
            "9.  CMDS  → ε",
            "10. CMD   → ATRIB",
            "11. CMD   → IF",
            "12. ATRIB → id = EXPR ;",
            "13. IF    → if ( COND ) { CMDS }",
            "14. COND  → EXPR >  EXPR",
            "15. COND  → EXPR <  EXPR",
            "16. COND  → EXPR >= EXPR",
            "17. COND  → EXPR <= EXPR",
            "18. COND  → EXPR == EXPR",
            "19. COND  → EXPR != EXPR",
            "20. EXPR  → EXPR + TERM",
            "21. EXPR  → TERM",
            "22. TERM  → TERM * FACT",
            "23. TERM  → FACT",
            "24. FACT  → id",
            "25. FACT  → const",
            "26. FACT  → ( EXPR )",
        ];
    }
    public function getAcoes(): array { return $this->acoes; }
}
