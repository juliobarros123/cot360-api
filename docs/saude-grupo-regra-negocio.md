# Saúde Grupo

## Objetivo

Este documento explica, de forma funcional e técnica, como a cotação de **Saúde Grupo** é construída no sistema.

A ideia é que:

- uma pessoa de negócio consiga entender a lógica;
- uma pessoa técnica consiga implementar ou validar a regra;
- alguém sem experiência prévia em seguros consiga acompanhar o raciocínio;
- a cotação possa ser conferida manualmente, sem depender do sistema.

Este documento descreve a regra hoje usada para o ramo **Saúde Grupo**, com base na lógica herdada do programa Java de referência e na adaptação feita no novo sistema.

---

## O que é uma cotação de Saúde Grupo

Uma cotação de Saúde Grupo é uma simulação do custo anual do seguro de saúde para um conjunto de pessoas ligadas a uma empresa.

Normalmente, este grupo pode incluir:

- trabalhadores da empresa;
- cônjuges desses trabalhadores;
- dependentes desses trabalhadores.

A cotação responde à pergunta:

> “Quanto custará, por ano, segurar este grupo de pessoas em cada plano de saúde disponível?”

No caso do BIC Seguros Saúde Grupo, o sistema calcula o valor para os pacotes:

- `BIC Pleno`
- `BIC Extra`
- `BIC Total`
- `BIC Integral`

---

## Conceitos básicos

### Titular

É a pessoa principal do seguro.

No contexto de Saúde Grupo, normalmente é:

- o trabalhador;
- o colaborador da empresa;
- o membro principal do grupo segurado.

No sistema e no PDF, o `Titular` é tratado como uma categoria própria.

### Cônjuge

É o marido ou a esposa do titular.

No Saúde Grupo:

- o cônjuge pode entrar no seguro;
- o cônjuge tem regras etárias iguais às do titular para adesão;
- o cônjuge é apresentado separadamente no PDF.

### Dependente

É uma pessoa dependente do titular, normalmente:

- filho;
- filha;
- outro descendente enquadrado na regra do produto.

No Saúde Grupo:

- dependente não é titular;
- dependente não é cônjuge;
- dependente tem regra etária própria.

### Subscrição

Subscrição significa que a pessoa:

- **pode entrar na cotação**;
- mas **precisa de análise adicional** antes da emissão final do seguro.

Em termos simples:

- não é exclusão;
- é um alerta de risco que exige avaliação.

### Exclusão

Exclusão significa que a pessoa:

- **não entra no cálculo do prémio**;
- **não pode ser considerada como segurada** nas condições normais da cotação.

---

## Estrutura da cotação

A cotação é construída a partir de quatro blocos:

1. lista de pessoas;
2. validação de elegibilidade;
3. aplicação da tarifa por pessoa e por plano;
4. consolidação dos totais e geração do PDF.

---

## Bloco 1: lista de pessoas

Cada pessoa usada na cotação deve ter, no mínimo:

- nome;
- data de nascimento;
- género;
- relação com o titular:
  - `Titular`
  - `Conjuge`
  - `Dependente`

No sistema, o Excel de entrada normalmente contém colunas como:

- `Nome`
- `DataNascimento`
- `Genero`
- `Relacao`
- `nif`

---

## Bloco 2: regras de elegibilidade

Antes de calcular o prémio, o sistema decide quem:

- entra normalmente;
- entra com subscrição;
- fica excluído.

Estas regras são fundamentais. A tarifa só deve ser aplicada às pessoas elegíveis.

### Regras para Titular e Cônjuge

#### Idade mínima

- `Titular` e `Conjuge` devem ter **16 anos ou mais**.

Se tiver menos de 16:

- fica excluído.

#### Idade máxima de adesão

- `Titular` e `Conjuge` podem entrar até **65 anos inclusive**.

Se tiver mais de 65:

- fica excluído.

#### Regra de subscrição

- `Titular` e `Conjuge` com idade **superior a 60 anos** entram com subscrição.

Na prática:

- `61`, `62`, `63`, `64` e `65` anos:
  - entram no cálculo;
  - mas são marcados como “para subscrição”.

### Regras para Dependente

- `Dependente` pode entrar até **25 anos inclusive**.

Se tiver mais de 25:

- fica excluído.

Dependente não usa a regra de subscrição etária usada para titular/cônjuge.

### Resumo das regras

| Relação | Regra | Resultado |
|---|---|---|
| Titular | idade < 16 | Excluído |
| Titular | 16 a 60 | Incluído |
| Titular | 61 a 65 | Incluído + Subscrição |
| Titular | > 65 | Excluído |
| Conjuge | idade < 16 | Excluído |
| Conjuge | 16 a 60 | Incluído |
| Conjuge | 61 a 65 | Incluído + Subscrição |
| Conjuge | > 65 | Excluído |
| Dependente | 0 a 25 | Incluído |
| Dependente | > 25 | Excluído |

---

## Como calcular a idade

A idade é calculada com base na:

- data de nascimento da pessoa;
- data de referência da cotação.

Exemplo:

- data da cotação: `28/04/2026`
- nascimento: `28/04/1961`
- idade: `65`

Exemplo:

- data da cotação: `28/04/2026`
- nascimento: `27/04/1960`
- idade: `66`

---

## Bloco 3: aplicação da tarifa

Depois de filtrar quem entra ou não entra, o sistema calcula o prémio anual por pessoa.

### O que é a tarifa

A tarifa é a tabela base de preço do seguro.

Ela depende de:

- plano escolhido;
- idade da pessoa;
- género;
- tipo de pessoa:
  - `Adult`
  - `Dependent`

No contexto de Saúde Grupo:

- `Titular` e `Conjuge` usam tarifa de `Adult`;
- `Dependente` usa tarifa de `Dependent`.

### O que existe na base de tarifas

Para cada plano, a tabela tem:

- idade mínima;
- idade máxima;
- género;
- tipo;
- valor base da tarifa (`premium_value`).

Exemplo conceitual:

| Plano | Faixa etária | Género | Tipo | Tarifa base |
|---|---|---|---|---|
| BIC Pleno | 41-45 | Male | Adult | 67.551,77 |
| BIC Pleno | 41-45 | Female | Adult | 96.715,04 |
| BIC Pleno | 11-15 | Male | Dependent | 34.945,24 |

### Como encontrar a tarifa correta

Para cada pessoa:

1. identificar a relação:
   - `Titular` ou `Conjuge` => `Adult`
   - `Dependente` => `Dependent`
2. calcular a idade;
3. identificar o género;
4. procurar, em cada plano, a linha cuja faixa etária contém aquela idade;
5. usar a tarifa base correspondente.

---

## Bloco 4: fórmula do prémio anual

Depois de encontrar a tarifa base, o sistema aplica a fórmula comercial.

### Fórmula usada

Para o ramo Saúde Grupo, o prémio anual por pessoa é calculado como:

```text
Prémio Anual por Pessoa = Tarifa Base × Fator de Margem × Fator do Grupo
```

Onde:

- `Tarifa Base` = valor da tabela por idade/género/tipo/plano;
- `Fator de Margem` = `1,25`;
- `Fator do Grupo` depende da quantidade de adultos elegíveis do grupo;
- o resultado final é arredondado para inteiro.

### Fator de margem

Hoje o sistema usa:

```text
Fator de Margem = 1,25
```

Isto equivale a acrescentar 25% sobre a tarifa base.

### Fator do grupo

O fator do grupo depende do número de pessoas adultas elegíveis no grupo, isto é:

- titulares incluídos;
- cônjuges incluídos.

Dependentes não são usados para definir o fator do grupo.

Tabela:

| Quantidade de adultos elegíveis | Fator do grupo |
|---|---:|
| 2 a 19 | 1,625 |
| 20 a 49 | 1,100 |
| 50 a 99 | 1,000 |
| 100 a 249 | 0,900 |
| 250 a 999 | 0,825 |
| 1000 ou mais | 0,800 |

### Regra de arredondamento

Depois de aplicar a fórmula, o sistema arredonda o valor para inteiro.

Exemplo:

```text
123.456,78 => 123.457
```

---

## Como fazer a cotação manualmente

Esta é a parte mais importante para validação humana.

### Passo 1: listar todas as pessoas

Exemplo:

| Nome | Relação | Idade | Género |
|---|---|---:|---|
| João | Titular | 61 | Male |
| Maria | Conjuge | 62 | Female |
| Ana | Dependente | 25 | Female |
| Pedro | Dependente | 8 | Male |

### Passo 2: aplicar elegibilidade

Verificar:

- João, 61, Titular => entra + subscrição
- Maria, 62, Conjuge => entra + subscrição
- Ana, 25, Dependente => entra
- Pedro, 8, Dependente => entra

Neste exemplo:

- incluídos: 4
- excluídos: 0
- subscrição: 2

### Passo 3: contar adultos elegíveis

Adultos elegíveis:

- João
- Maria

Total de adultos elegíveis = `2`

Logo:

```text
Fator do Grupo = 1,625
```

### Passo 4: buscar a tarifa base em cada plano

Exemplo ilustrativo:

| Pessoa | Plano | Tarifa base |
|---|---|---:|
| João | BIC Pleno | 91.080,01 |
| Maria | BIC Pleno | 91.080,01 |
| Ana | BIC Pleno | 61.914,57 |
| Pedro | BIC Pleno | 45.606,10 |

### Passo 5: aplicar a fórmula por pessoa

Fórmula:

```text
Prémio Anual = Tarifa Base × 1,25 × 1,625
```

Como:

```text
1,25 × 1,625 = 2,03125
```

Então:

```text
Prémio Anual = Tarifa Base × 2,03125
```

Exemplo João:

```text
91.080,01 × 2,03125 = 184.996,27
Arredondado = 184.996
```

Exemplo Pedro:

```text
45.606,10 × 2,03125 = 92.637,39
Arredondado = 92.637
```

### Passo 6: somar por categoria

Depois de calcular cada pessoa de um plano:

- somar titulares;
- somar cônjuges;
- somar dependentes.

Exemplo:

| População | BIC Pleno |
|---|---:|
| Titular | 184.996 |
| Conjuge | 184.996 |
| Dependente | 218.430 |
| Total | 588.422 |

### Passo 7: calcular “por pessoa”

Depois de obter o total anual por categoria:

```text
Valor por pessoa da categoria = Total da categoria / Quantidade da categoria
```

E para o total geral:

```text
Valor médio por pessoa = Total geral / Total de pessoas incluídas
```

---

## Como ler o PDF

O PDF hoje apresenta três blocos principais de cálculo.

### 1. Número e tipo de Pessoas Seguras

Mostra quantas pessoas elegíveis ficaram em cada categoria:

- Titular
- Conjuge
- Dependente
- Total

Importante:

- pessoas excluídas não entram aqui;
- pessoas para subscrição entram aqui, porque continuam na cotação.

### 2. Prémio Total Anual em Kwanza

Mostra, para cada plano:

- o total anual de titulares;
- o total anual de cônjuges;
- o total anual de dependentes;
- o total geral do grupo.

### 3. Prémio Total Anual em Kwanza por Pessoa Segura

Mostra:

- média anual por titular;
- média anual por cônjuge;
- média anual por dependente;
- média anual do grupo todo.

---

## Secções operacionais do PDF

Além dos valores, o PDF também mostra:

### Pessoas Excluídas

Lista:

- nome;
- relação;
- idade;
- motivo da exclusão.

### Pessoas para Subscrição

Lista:

- nome;
- relação;
- idade;
- motivo da subscrição.

Isto é importante porque:

- o utilizador entende por que o número de pessoas do PDF pode ser menor que o número do Excel;
- a equipa comercial sabe que algumas pessoas ainda exigem análise.

---

## Regras práticas sobre as relações

### Quando usar Titular

Usar `Titular` quando a pessoa é:

- o colaborador principal;
- a pessoa base da adesão;
- o trabalhador da empresa.

### Quando usar Conjuge

Usar `Conjuge` quando a pessoa é:

- marido;
- esposa;
- cônjuge do titular.

### Quando usar Dependente

Usar `Dependente` quando a pessoa é:

- filho;
- filha;
- descendente enquadrado pela regra do seguro.

Se a relação vier errada no Excel, a cotação também ficará errada.

---

## Exemplo completo validado

No teste de referência com 11 pessoas:

- 4 foram excluídas;
- 3 ficaram para subscrição;
- 7 entraram no cálculo.

Resultado final:

| População | Nº |
|---|---:|
| Titular | 3 |
| Conjuge | 2 |
| Dependente | 2 |
| Total | 7 |

Excluídos:

- Titular com 15 anos
- Titular com 66 anos
- Conjuge com 66 anos
- Dependente com 26 anos

Subscrição:

- Titular com 65 anos
- Titular com 61 anos
- Conjuge com 62 anos

---

## Ordem correta de processamento

Para evitar erro de cálculo, a ordem deve ser sempre esta:

1. ler pessoas;
2. normalizar relação;
3. calcular idade;
4. aplicar elegibilidade;
5. separar excluídos;
6. marcar subscrição;
7. contar adultos elegíveis;
8. determinar fator do grupo;
9. buscar tarifa base por pessoa/plano;
10. aplicar fórmula;
11. arredondar;
12. somar por categoria;
13. calcular médias por pessoa;
14. gerar PDF.

Se esta ordem for alterada, os resultados deixam de ser fiáveis.

---

## Erros mais comuns

### 1. Contar excluídos na população

Isto infla:

- o total de pessoas;
- o fator do grupo;
- o prémio final.

### 2. Tratar Conjuge como Dependente

Isto aplica a tarifa errada e a regra etária errada.

### 3. Tratar Dependente maior de 25 como elegível

Isto gera prémio indevido.

### 4. Usar dependentes para definir o fator do grupo

No Saúde Grupo, o fator do grupo deve ser definido pelos adultos elegíveis.

### 5. Dividir médias pelo total errado

Na tabela “por pessoa”:

- média de Titular divide por número de titulares;
- média de Conjuge divide por número de cônjuges;
- média de Dependente divide por número de dependentes;
- média Total divide por número total de pessoas incluídas.

---

## Interpretação de negócio

Em linguagem simples:

- o Excel traz as pessoas;
- o sistema decide quem pode ou não pode entrar;
- quem é mais velho mas ainda admissível pode exigir subscrição;
- o preço por pessoa depende da idade, género, tipo e plano;
- depois aplica-se uma carga comercial e um fator do tamanho do grupo;
- no fim, o sistema mostra totais e médias.

---

## Conclusão

A cotação de Saúde Grupo não é apenas uma soma de tarifas.

Ela depende de:

- classificação correta da pessoa;
- idade correta;
- regras de elegibilidade;
- regras de subscrição;
- escolha correta da tarifa base;
- fator comercial;
- fator do tamanho do grupo;
- consolidação correta no PDF.

Se qualquer uma destas peças estiver errada, o resultado final também estará errado.

Por isso, para validar a cotação manualmente, deve-se sempre:

1. conferir quem entra;
2. conferir quem fica excluído;
3. conferir quem vai para subscrição;
4. conferir a tarifa base aplicada;
5. conferir a fórmula;
6. conferir as somas finais.

