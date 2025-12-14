# 📘 Manual do Gerador de Código Assembly MIPS

## 🎯 Visão Geral

Este gerador de código converte representações intermediárias (AST/tokens) em código Assembly MIPS32 executável no simulador MARS, seguindo os padrões descritos em **Organização Estruturada de Computadores** (Tanenbaum).

---

## 🏗️ Arquitetura

### Componentes Principais

```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│  Código Fonte   │ ──→ │ Analisadores     │ ──→ │ Gerador MIPS    │
│  (Linguagem)    │     │ (Léxico/Sintático│     │ (Assembly)      │
└─────────────────┘     │  /Semântico)     │     └─────────────────┘
                        └──────────────────┘              │
                                 │                        │
                                 ↓                        ↓
                        ┌──────────────────┐     ┌─────────────────┐
                        │ Tabela Símbolos  │     │  Arquivo .asm   │
                        │ AST / Tokens     │     │  (Executável)   │
                        └──────────────────┘     └─────────────────┘
```

---

## 📋 Convenções MIPS Utilizadas

### Registradores

| Registrador | Nome      | Uso                                    |
|-------------|-----------|----------------------------------------|
| `$zero`     | Constante | Sempre contém 0                        |
| `$t0-$t9`   | Temporários | Expressões temporárias (não preservados) |
| `$s0-$s7`   | Salvos    | Variáveis preservadas entre chamadas   |
| `$a0-$a3`   | Argumentos | Passagem de parâmetros                |
| `$v0-$v1`   | Valores   | Retorno de funções                     |
| `$sp`       | Stack Ptr | Ponteiro da pilha                      |
| `$fp`       | Frame Ptr | Ponteiro do frame                      |
| `$ra`       | Return    | Endereço de retorno                    |

### Syscalls Utilizadas

| Código | Função        | Argumentos       |
|--------|---------------|------------------|
| 1      | `print_int`   | `$a0` = inteiro  |
| 4      | `print_string`| `$a0` = endereço |
| 5      | `read_int`    | Retorna em `$v0` |
| 10     | `exit`        | Finaliza programa|

---

## 🔧 Uso Básico

### 1. Inicialização

```php
require_once("GeradorCodigoMIPS.php");

// Com tabela de símbolos existente
$tabelaSimbolos = [
    'x' => 'INT',
    'y' => 'INT'
];

$gerador = new GeradorCodigoMIPS($tabelaSimbolos);
```

### 2. Geração de Variáveis Globais

```php
$gerador->gerarVariaveisGlobais();
```

**Saída MIPS:**
```asm
.data
_var_x: .word 0  # INT
_var_y: .word 0  # INT
```

### 3. Atribuição Simples

```php
$gerador->gerarAtribuicao('x', 10);
```

**Saída MIPS:**
```asm
    # Atribuição: x = 10
    li $t0, 10  # Carrega constante 10
    sw $t0, _var_x  # x = 10
```

### 4. Operações Aritméticas

```php
// soma = a + b
$gerador->gerarAtribuicao('soma', ['+', 'a', 'b']);
```

**Saída MIPS:**
```asm
    # Operação: a + b
    lw $t0, _var_a  # Carrega a
    lw $t1, _var_b  # Carrega b
    add $t2, $t0, $t1  # +
    sw $t2, _var_soma  # soma = a + b
```

### 5. Estrutura Condicional IF

```php
$gerador->gerarIf(
    ['>', 'x', 10],  // Condição: x > 10
    function() use ($gerador) {
        // Bloco THEN
        $gerador->gerarAtribuicao('x', 0);
    },
    function() use ($gerador) {
        // Bloco ELSE (opcional)
        $gerador->gerarAtribuicao('x', 1);
    }
);
```

**Saída MIPS:**
```asm
    # IF: início
    lw $t0, _var_x
    li $t1, 10
    ble $t0, $t1, _else0  # Se <=, pula
    # Bloco THEN
    li $t2, 0
    sw $t2, _var_x
    j _endif0
_else0:  # Início ELSE
    li $t2, 1
    sw $t2, _var_x
_endif0:  # Fim IF-ELSE
```

### 6. Loop WHILE

```php
$gerador->gerarWhile(
    ['<', 'i', 10],  // Condição: i < 10
    function() use ($gerador) {
        // Corpo do loop
        $gerador->gerarAtribuicao('i', ['+', 'i', 1]);
    }
);
```

**Saída MIPS:**
```asm
_while0:  # Início do loop
    lw $t0, _var_i
    li $t1, 10
    bge $t0, $t1, _endwhile0  # Se >=, sai
    # Corpo do WHILE
    lw $t2, _var_i
    li $t3, 1
    add $t4, $t2, $t3
    sw $t4, _var_i
    j _while0  # Volta para condição
_endwhile0:  # Fim do loop
```

### 7. Entrada e Saída

```php
// Leitura
$gerador->gerarLeitura('numero');

// Escrita
$gerador->gerarEscrita('resultado');
```

**Saída MIPS:**
```asm
    # READ: numero
    li $v0, 5  # Syscall read_int
    syscall
    sw $v0, _var_numero  # Armazena em numero

    # PRINT: resultado
    lw $t0, _var_resultado
    move $a0, $t0  # Prepara argumento
    li $v0, 1  # Syscall print_int
    syscall
```

### 8. Finalização

```php
$gerador->gerarExit();
$codigo = $gerador->getCodigoCompleto();
$gerador->salvarArquivo("programa.asm");
```

---

## 📊 Operadores Suportados

### Aritméticos
- `+` (add)
- `-` (sub)
- `*` (mul)
- `/` (div/mflo)

### Relacionais
- `>` (bgt)
- `<` (blt)
- `==` (beq)
- `!=` (bne)
- `>=` (bge)
- `<=` (ble)

---

## 🎓 Integração com Compilador Existente

### Fluxo Completo

```php
require_once("IntegradorCompilador.php");

$codigo = "int x; x = 10 + 5;";

$compilador = new IntegradorCompilador();
$resultado = $compilador->compilar($codigo);

if ($resultado['success']) {
    echo $resultado['codigo_assembly'];
    file_put_contents("saida.asm", $resultado['codigo_assembly']);
}
```

### Etapas Automáticas

1. **Análise Léxica**: Tokenização
2. **Análise Sintática**: Validação estrutural
3. **Análise Semântica**: Verificação de tipos
4. **Geração de Código**: Tradução para MIPS

---

## 🧪 Testando no MARS

### Passo a Passo

1. **Abra o MARS Simulator**
   ```
   java -jar Mars.jar
   ```

2. **Carregue o arquivo .asm**
   - File → Open
   - Selecione o arquivo gerado

3. **Monte o código (Assemble)**
   - Clique em "Assemble" ou pressione `F3`

4. **Execute o programa**
   - Clique em "Run" ou pressione `F5`

5. **Observe saída**
   - Console MARS mostrará resultados de `print`

---

## 🔍 Estrutura do Código Gerado

### Seção .data (Variáveis Globais)

```asm
.data
_newline: .asciiz "\n"
_space: .asciiz " "

# Variáveis globais
_var_x: .word 0  # INT
_var_y: .word 0  # INT
```

### Seção .text (Código Executável)

```asm
.text
.globl main

main:
    # Prólogo
    addi $sp, $sp, -4
    sw $fp, 0($sp)
    move $fp, $sp

    # Código do programa
    # ...

    # Epílogo
    lw $fp, 0($sp)
    addi $sp, $sp, 4
    li $v0, 10
    syscall
```

---

## ⚙️ Otimizações Futuras

### 1. Alocação Inteligente de Registradores
- Análise de vida útil de variáveis
- Minimização de acessos à memória

### 2. Otimização de Expressões
- Constant folding
- Eliminação de subexpressões comuns

### 3. Otimização de Código
- Eliminação de código morto
- Redução de saltos redundantes


## 🐛 Solução de Problemas

### Erro: "Invalid instruction"
**Causa**: Instrução não suportada pelo MARS  
**Solução**: Verifique a documentação MARS para instruções compatíveis

### Erro: "Address out of range"
**Causa**: Acesso inválido à memória  
**Solução**: Verifique inicialização de variáveis na seção .data

### Programa não termina
**Causa**: Falta de syscall exit  
**Solução**: Sempre chame `gerarExit()` ao final

---

## ✅ Checklist de Validação

- [ ] Código assembly válido no MARS
- [ ] Todas as variáveis inicializadas em .data
- [ ] Prólogo e epílogo corretos
- [ ] Syscalls com argumentos adequados
- [ ] Labels únicos e bem formatados
- [ ] Programa finaliza com exit (syscall 10)

---

## 📝 Exemplo Completo

```php
$tabela = ['x' => 'INT', 'y' => 'INT', 'soma' => 'INT'];
$gerador = new GeradorCodigoMIPS($tabela);

$gerador->gerarVariaveisGlobais();
$gerador->gerarLeitura('x');
$gerador->gerarLeitura('y');
$gerador->gerarAtribuicao('soma', ['+', 'x', 'y']);
$gerador->gerarEscrita('soma');
$gerador->gerarExit();

$gerador->salvarArquivo("calculadora.asm");
```

**Resultado**: Programa que lê dois números, soma e imprime o resultado.

---

🎉 **Gerador de Código MIPS - Versão 1.0**  
Desenvolvido seguindo padrões acadêmicos e boas práticas de compiladores.