# Dashboard BI — Grupo Holística
## Guia Completo de Implantação na Hostinger

---

## 📁 ESTRUTURA DE ARQUIVOS

```
/dashboard/
├── .htaccess           ← Segurança e roteamento
├── config.php          ← Configurações gerais (DB, Google Sheets)
├── auth.php            ← Controle de sessão e autenticação
├── install.php         ← Instalador do banco de dados (apague após instalar!)
├── index.php           ← Dashboard principal
├── login.php           ← Tela de login
├── register.php        ← Cadastro de usuários
├── logout.php          ← Logout
└── api/
    ├── _helper.php     ← Helper de leitura CSV
    ├── vendas.php      ← API de faturamento
    ├── formulas.php    ← API de orçamentos
    ├── trafego_pago.php← API de marketing
    └── vendedores.php  ← API de vendedores
```

---

## 🚀 PASSO A PASSO: DEPLOY NA HOSTINGER

### 1. Upload dos arquivos
- Acesse o hPanel da Hostinger
- Abra o **Gerenciador de Arquivos**
- Navegue até `public_html/dashboard/`
- Faça upload de TODOS os arquivos mantendo a estrutura de pastas

### 2. Configurar o Google Sheets

#### 2a. Publique sua planilha como CSV:
1. Abra sua planilha no Google Sheets
2. Vá em **Arquivo → Compartilhar → Publicar na web**
3. Selecione **"Planilha inteira"** e formato **"CSV"**
4. Clique em **Publicar** e confirme
5. Copie o ID da planilha da URL:
   - URL exemplo: `https://docs.google.com/spreadsheets/d/`**`1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgVE2upms`**`/edit`
   - O ID é a parte destacada entre `/d/` e `/edit`

#### 2b. Obtenha o GID de cada aba:
1. Clique em cada aba da planilha
2. Na URL, copie o número após `#gid=`
   - Exemplo: `...#gid=`**`1234567890`**

#### 2c. Edite o arquivo `config.php`:
```php
define('SHEET_ID', 'SEU_ID_AQUI');       // ← Cole o ID da planilha
define('GID_VENDAS',     '0');            // ← GID da aba "vendas"
define('GID_FORMULAS',   '123456');       // ← GID da aba "formulas"
define('GID_TRAFEGO',    '234567');       // ← GID da aba "trafego_pago"
define('GID_VENDEDORES', '345678');       // ← GID da aba "vendedores"
```

#### ⚠️ Nomes das colunas obrigatórios na planilha:

**Aba "vendas":**
| data | loja | produto | unidades_vendidas | valor | tipo_produto |

**Aba "formulas":**
| mes | loja | orcamentos_efetuados | rejeitados | valor_orcado | valor_aprovado |

**Aba "trafego_pago":**
| data | campanha | valor_gasto | impressoes | cliques | cpm |

**Aba "vendedores":**
| data | loja | vendedor | aprovados | rejeitados |

### 3. Instalar o banco de dados
1. Acesse: `https://grupoholistica.com.br/dashboard/install.php`
2. O sistema criará as tabelas automaticamente
3. Um usuário admin será criado:
   - Email: `admin@grupoholistica.com.br`
   - Senha: `Admin@2024!`
4. **⚠️ APAGUE o arquivo `install.php` imediatamente após!**

---

## 🔐 DADOS DE ACESSO AO BANCO

- **Host:** localhost
- **Banco:** u800341527_Dashboard
- **Usuário:** u800341527_admin
- **Senha:** WhitzZ@10912

Configure no hPanel → MySQL → Gerenciar Banco de Dados

---

## 👥 CONTROLE DE ACESSO

| Setor | Acesso | Lojas |
|-------|--------|-------|
| Diretoria | Total — vê tudo | Todas |
| Marketing | Total — vê tudo (incl. Marketing) | Todas |
| Coordenador de Loja | Restrito | Apenas a própria loja |
| Gerente | Restrito | Apenas a própria loja |

### Domínios permitidos para cadastro:
- @farmaciaholistica.com.br
- @grupoholistica.com.br
- @holtecpharma.com.br

---

## 📊 FUNCIONALIDADES

### 💰 Faturamento
- Faturamento total, ticket médio, unidades vendidas
- Gráfico de evolução por mês
- Ranking e participação por loja
- Filtros: loja, mês, ano

### 📋 Orçamentos
- Total orçado, aprovados, rejeitados
- Taxa de conversão por loja
- Evolução mensal
- Valor orçado vs aprovado

### 🏪 Lojas
- Comparativo completo entre lojas
- Faturamento + conversão de orçamentos
- Participação percentual

### 👥 Vendedores
- Ranking por aprovados e conversão
- Filtros por loja e período

### 📦 Produtos
- Curva ABC automática
- Top 10 por faturamento e unidades
- Ranking completo

### 📣 Marketing (acesso total apenas)
- Gasto, cliques, impressões, CPM, CPC, CTR
- Evolução temporal
- Comparação por campanha Meta Ads

---

## 🔧 CONFIGURAÇÕES AVANÇADAS

### Cache de dados
Por padrão, os dados do Google Sheets são cacheados por **5 minutos**.
Para alterar, edite em `api/_helper.php`:
```php
$data = fetchSheetCSV($url, 'vendas', 300); // 300 = 5 minutos em segundos
```

### Timeout da sessão
Padrão: 1 hora. Edite em `config.php`:
```php
define('SESSION_TIMEOUT', 3600); // segundos
```

### Adicionar nova loja
Edite o array `$lojas` em `register.php`:
```php
$lojas = ['BOQUEIRÃO', 'CAIÇARA', ..., 'NOVA_LOJA'];
```

---

## ❓ SOLUÇÃO DE PROBLEMAS

**Tela em branco:** Verifique erros PHP no hPanel → Logs de erro

**"Não foi possível acessar o Google Sheets":**
- Confirme que a planilha está publicada na web
- Verifique o SHEET_ID e os GIDs
- Teste a URL: `https://docs.google.com/spreadsheets/d/SEU_ID/export?format=csv&gid=0`

**Login não funciona:**
- Execute novamente o `install.php`
- Verifique credenciais do MySQL no `config.php`

**Erro 403 em arquivos da API:**
- Verifique se o `.htaccess` foi enviado corretamente
- No hPanel, ative `mod_rewrite` se disponível

---

## 📞 SUPORTE TÉCNICO

Sistema desenvolvido para uso interno do Grupo Holística.
Versão: 1.0.0
