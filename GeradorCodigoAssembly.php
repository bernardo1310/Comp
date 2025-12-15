<?php
/**
 * ============================================================================
 * GeradorCodigoAssembly (MIPS / MARS)
 * ----------------------------------------------------------------------------
 * Objetivo:
 *  - Gerar Assembly MIPS no estilo "esperado" por você:
 *      * addiu / addu / subu
 *      * mult + mflo (ao invés de mul)
 *      * acesso a variáveis via:
 *          lui $tX, %hi(label)
 *          ori $tX, $tX, %lo(label)
 *          lw/sw 0($tX)
 *      * IF usando slt + beq/bne + nop (delay slot)
 *
 * Suporta:
 *  - DECL:  int x; | char c; | bool b;
 *  - ATRIB: x = EXPR;
 *  - EXPR: id | const | EXPR + EXPR | EXPR * EXPR | EXPR - EXPR | EXPR / EXPR | ( EXPR )
 *  - IF: if ( EXPR <,>,<=,>=,==,!= EXPR ) { CMDS }
 * ============================================================================
 */

class GeradorCodigoAssembly {

    // -----------------------------
    // Buffers de saída
    // -----------------------------
    private array $data = [];
    private array $text = [];

    // -----------------------------
    // Tabela de variáveis
    // name => ['label'=>..., 'type'=>...]
    // -----------------------------
    private array $vars = [];

    // -----------------------------
    // Labels
    // -----------------------------
    private int $labelCount = 0;

    // -----------------------------
    // Temporários ($t0-$t9)
    // -----------------------------
    private array $freeTemps = [];
    private array $usedTemps = [];

    // -----------------------------
    // Pilha de expressões:
    // itens: ['reg'=>..., 'type'=>...]
    // -----------------------------
    private array $exprStack = [];

    // -----------------------------
    // Controle (IF):
    // itens: ['kind'=>'IF', 'end'=>label]
    // -----------------------------
    private array $ctrlStack = [];

    private bool $started = false;

    public function __construct() {
        $this->reset();
    }

    public function reset(): void {
        $this->data = [];
        $this->text = [];

        $this->vars = [];
        $this->labelCount = 0;

        $this->freeTemps = ['$t0','$t1','$t2','$t3','$t4','$t5','$t6','$t7','$t8','$t9'];
        $this->usedTemps = [];

        $this->exprStack = [];
        $this->ctrlStack = [];

        $this->started = false;
    }

    // =========================================================================
    // Programa
    // =========================================================================

    public function beginProgram(): void {
        if ($this->started) return;
        $this->started = true;

        $this->emitText(".text");
        $this->emitText(".globl main");
        $this->emitText("main:");
    }

    public function endProgram(): void {
        if (!$this->started) $this->beginProgram();

        // Fecha IFs pendentes (segurança)
        while (!empty($this->ctrlStack)) {
            $ctx = array_pop($this->ctrlStack);
            if (($ctx['kind'] ?? '') === 'IF') {
                $this->emitText(($ctx['end'] ?? 'L_if_end_missing') . ":");
            }
        }

        // exit
        $this->emitLoadImm('$v0', 10);
        $this->emitText("syscall");
    }

    public function build(): string {
        $out = [];

        if (!empty($this->data)) {
            $out[] = ".data";
            foreach ($this->data as $line) $out[] = $line;
            $out[] = "";
        }

        foreach ($this->text as $line) $out[] = $line;

        return implode("\n", $out) . "\n";
    }

    private function emitData(string $line): void {
        $this->data[] = $line;
    }

    private function emitText(string $line): void {
        if (!$this->started && $line !== ".text") {
            $this->beginProgram();
        }
        $this->text[] = $line;
    }

    // =========================================================================
    // Variáveis
    // =========================================================================

    public function declareVar(string $name, string $type): void {
        $type = strtoupper($type);

        if (isset($this->vars[$name])) return;

        $label = $this->sanitizeLabel("var_" . $name);
        $this->vars[$name] = ['label' => $label, 'type' => $type];

        switch ($type) {
            case 'CHAR':
                $this->emitData("{$label}: .byte 0");
                break;

            case 'BOOL':
            case 'INT':
            default:
                $this->emitData("{$label}: .word 0");
                break;
        }
    }

    private function varInfo(string $name): array {
        if (!isset($this->vars[$name])) {
            throw new Exception("Variável '$name' não declarada (geração de código).");
        }
        return $this->vars[$name];
    }

    // =========================================================================
    // Temporários
    // =========================================================================

    private function allocTemp(): string {
        if (empty($this->freeTemps)) {
            throw new Exception("Sem registradores temporários disponíveis (\$t0-\$t9).");
        }
        $reg = array_shift($this->freeTemps);
        $this->usedTemps[$reg] = true;
        return $reg;
    }

    private function freeTemp(string $reg): void {
        if (isset($this->usedTemps[$reg])) {
            unset($this->usedTemps[$reg]);
            array_unshift($this->freeTemps, $reg);
        }
    }

    // =========================================================================
    // Helpers (formato "esperado")
    // =========================================================================

    /**
     * Carrega imediato no padrão:
     *   addiu $reg, $zero, imm
     * Se não couber em 16 bits, usa lui/ori numérico.
     */
    private function emitLoadImm(string $reg, int $imm): void {
        if ($imm >= -32768 && $imm <= 32767) {
            $this->emitText("addiu {$reg}, \$zero, {$imm}");
            return;
        }

        $hi = ($imm >> 16) & 0xFFFF;
        $lo = $imm & 0xFFFF;

        $this->emitText("lui {$reg}, {$hi}");
        $this->emitText("ori {$reg}, {$reg}, {$lo}");
    }

    /**
     * Emite endereço de label no padrão:
     *   lui $reg, %hi(label)
     *   ori $reg, $reg, %lo(label)
     */
    private function emitAddrLabel(string $reg, string $label): void {
        $this->emitText("lui {$reg}, %hi({$label})");
        $this->emitText("ori {$reg}, {$reg}, %lo({$label})");
    }

    // =========================================================================
    // Expressões
    // =========================================================================

    public function pushConstFromLexeme(string $lex): void {
        $lex = trim($lex);

        if (!preg_match('/^-?\d+$/', $lex)) {
            $lex = preg_replace('/[^\d\-]/', '', $lex);
        }

        $value = ($lex !== '' && $lex !== '-') ? (int)$lex : 0;
        $this->pushConst($value);
    }

    public function pushConst(int $value): void {
        $r = $this->allocTemp();
        $this->emitLoadImm($r, $value);
        $this->exprStack[] = ['reg' => $r, 'type' => 'INT'];
    }

    public function pushVar(string $name): void {
        $info  = $this->varInfo($name);
        $label = $info['label'];
        $type  = strtoupper($info['type']);

        $rVal  = $this->allocTemp(); // valor
        $rAddr = $this->allocTemp(); // endereço

        $this->emitAddrLabel($rAddr, $label);

        if ($type === 'CHAR') {
            $this->emitText("lb {$rVal}, 0({$rAddr})");
        } else {
            $this->emitText("lw {$rVal}, 0({$rAddr})");
        }

        $this->freeTemp($rAddr);

        $this->exprStack[] = ['reg' => $rVal, 'type' => 'INT'];
    }

    private function pop2(string $ctx): array {
        if (count($this->exprStack) < 2) {
            throw new Exception("Stack de expressão insuficiente em {$ctx}.");
        }
        $rhs = array_pop($this->exprStack);
        $lhs = array_pop($this->exprStack);
        if (!$lhs || !$rhs) throw new Exception("Stack de expressão vazio em {$ctx}.");
        return [$lhs, $rhs];
    }

    public function exprAdd(): void {
        [$lhs, $rhs] = $this->pop2("ADD");

        $rd = $this->allocTemp();
        $this->emitText("addu {$rd}, {$lhs['reg']}, {$rhs['reg']}");

        $this->freeTemp($lhs['reg']);
        $this->freeTemp($rhs['reg']);

        $this->exprStack[] = ['reg' => $rd, 'type' => 'INT'];
    }

    public function exprSub(): void {
        [$lhs, $rhs] = $this->pop2("SUB");

        $rd = $this->allocTemp();
        $this->emitText("subu {$rd}, {$lhs['reg']}, {$rhs['reg']}");

        $this->freeTemp($lhs['reg']);
        $this->freeTemp($rhs['reg']);

        $this->exprStack[] = ['reg' => $rd, 'type' => 'INT'];
    }

    public function exprMul(): void {
        [$lhs, $rhs] = $this->pop2("MUL");

        $rd = $this->allocTemp();

        $this->emitText("mult {$lhs['reg']}, {$rhs['reg']}");
        $this->emitText("mflo {$rd}");

        $this->freeTemp($lhs['reg']);
        $this->freeTemp($rhs['reg']);

        $this->exprStack[] = ['reg' => $rd, 'type' => 'INT'];
    }

    public function exprDiv(): void {
        [$lhs, $rhs] = $this->pop2("DIV");

        $rd = $this->allocTemp();

        $this->emitText("div {$lhs['reg']}, {$rhs['reg']}");
        $this->emitText("mflo {$rd}");

        $this->freeTemp($lhs['reg']);
        $this->freeTemp($rhs['reg']);

        $this->exprStack[] = ['reg' => $rd, 'type' => 'INT'];
    }

    // =========================================================================
    // Atribuição
    // =========================================================================

    public function assignFromTopExpr(string $name): void {
        if (empty($this->exprStack)) {
            throw new Exception("Não há valor no stack de expressão para atribuição.");
        }

        $v = array_pop($this->exprStack);

        $info  = $this->varInfo($name);
        $label = $info['label'];
        $type  = strtoupper($info['type']);

        $rAddr = $this->allocTemp();
        $this->emitAddrLabel($rAddr, $label);

        if ($type === 'CHAR') {
            $this->emitText("sb {$v['reg']}, 0({$rAddr})");
        } else {
            $this->emitText("sw {$v['reg']}, 0({$rAddr})");
        }

        $this->freeTemp($rAddr);
        $this->freeTemp($v['reg']);
    }

    // =========================================================================
    // IF / COND
    // =========================================================================

    /**
     * Gera desvio quando condição for FALSA.
     * Usa slt + beq/bne (bem padrão e fica igual ao seu esperado).
     */
    public function beginIf(string $op): void {
        [$lhs, $rhs] = $this->pop2("COND/IF");
        $end = $this->newLabel("L_if_end");

        $t = $this->allocTemp(); // temp para slt quando necessário

        switch ($op) {
            case '>':
                // true se rhs < lhs
                $this->emitText("slt {$t}, {$rhs['reg']}, {$lhs['reg']}");
                $this->emitText("beq {$t}, \$zero, {$end}");
                break;

            case '<':
                // true se lhs < rhs
                $this->emitText("slt {$t}, {$lhs['reg']}, {$rhs['reg']}");
                $this->emitText("beq {$t}, \$zero, {$end}");
                break;

            case '>=':
                // false se lhs < rhs
                $this->emitText("slt {$t}, {$lhs['reg']}, {$rhs['reg']}");
                $this->emitText("bne {$t}, \$zero, {$end}");
                break;

            case '<=':
                // false se rhs < lhs
                $this->emitText("slt {$t}, {$rhs['reg']}, {$lhs['reg']}");
                $this->emitText("bne {$t}, \$zero, {$end}");
                break;

            case '==':
                $this->emitText("bne {$lhs['reg']}, {$rhs['reg']}, {$end}");
                break;

            case '!=':
                $this->emitText("beq {$lhs['reg']}, {$rhs['reg']}, {$end}");
                break;

            default:
                $this->freeTemp($t);
                throw new Exception("Operador de condição não suportado: {$op}");
        }

        $this->emitText("nop");

        $this->freeTemp($t);
        $this->freeTemp($lhs['reg']);
        $this->freeTemp($rhs['reg']);

        $this->ctrlStack[] = ['kind' => 'IF', 'end' => $end];
    }

    public function endIf(): void {
        if (empty($this->ctrlStack)) {
            throw new Exception("endIf chamado sem IF ativo.");
        }
        $ctx = array_pop($this->ctrlStack);
        if (($ctx['kind'] ?? '') !== 'IF') {
            throw new Exception("endIf chamado sem IF ativo.");
        }
        $this->emitText(($ctx['end'] ?? 'L_if_end_missing') . ":");
    }

    // =========================================================================
    // Utilidades
    // =========================================================================

    private function newLabel(string $prefix): string {
        $this->labelCount++;
        return "{$prefix}_{$this->labelCount}";
    }

    private function sanitizeLabel(string $s): string {
        return preg_replace('/[^a-zA-Z0-9_]/', '_', $s);
    }

    // Debug
    public function getExprStackSize(): int { return count($this->exprStack); }
    public function getVars(): array { return $this->vars; }
}
