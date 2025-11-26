
# 🧩 **Roteiro de Desenvolvimento — MVP JEBusiness Controle de Estoque e Vendas**

## 🏁 1. Setup inicial do projeto

| Etapa                                                      | Descrição                                                                                    | Tipo     | Estimate     |
| ---------------------------------------------------------- | -------------------------------------------------------------------------------------------- | -------- | ------------ |
| Configuração do repositório e pastas                       | Estruturar diretórios padrão (`/public`, `/app`, `/config`, `/views`, `/assets`, `/core`)    | backend  | `estimate:2` |
| Criação do `index.php` e roteamento básico                 | Definir router simples (query param ou regex) + renderização de views                        | backend  | `estimate:3` |
| Configuração do banco MySQL                                | Criar esquema inicial: `usuarios`, `produtos`, `vendas`, `clientes`, `estoque`, `financeiro` | backend  | `estimate:3` |
| Setup de ambiente local (.env + autoload + conexões PDO)   | Usar `vlucas/phpdotenv`, `PDO`, e `composer` para autoload                                   | backend  | `estimate:2` |
| Configuração do TailwindCSS e build frontend               | Integrar via CDN ou build leve com PostCSS                                                   | frontend | `estimate:2` |
| Criar templates base (header, footer, container principal) | Layout comum a todas as páginas                                                              | frontend | `estimate:1` |

---

## 🔐 2. Autenticação e Controle de Acesso

| Etapa                       | Descrição                                       | Tipo             | Estimate     |
| --------------------------- | ----------------------------------------------- | ---------------- | ------------ |
| Tela de Login e Logout      | Formulário, sessão, validação, redirecionamento | frontend/backend | `estimate:3` |
| Middleware de autenticação  | Bloqueio de rotas e validação de sessão         | backend          | `estimate:2` |
| Tela de Registro (opcional) | Cadastro inicial do administrador               | frontend/backend | `estimate:2` |

---

## 📦 3. Módulo de Produtos

| Etapa                       | Descrição                                       | Tipo             | Estimate     |
| --------------------------- | ----------------------------------------------- | ---------------- | ------------ |
| CRUD de produtos            | Listagem, criação, edição, exclusão             | fullstack        | `estimate:4` |
| Upload de imagem (opcional) | Integrar com upload local (`/uploads/products`) | backend          | `estimate:3` |
| Filtro e busca              | Implementar filtro por nome/código e categoria  | frontend/backend | `estimate:2` |

---

## 🧾 4. Módulo de Vendas

| Etapa                            | Descrição                                                         | Tipo             | Estimate     |
| -------------------------------- | ----------------------------------------------------------------- | ---------------- | ------------ |
| Tela de Nova Venda               | Seleção de produtos, quantidades, cálculo automático, totalização | fullstack        | `estimate:5` |
| Registro da venda no banco       | Inserir em `vendas` e `itens_venda`                               | backend          | `estimate:3` |
| Listagem de Vendas               | Histórico com data, cliente e valor total                         | frontend/backend | `estimate:2` |
| Emissão de comprovante (simples) | Impressão ou PDF via HTML                                         | frontend         | `estimate:2` |

---

## 🧍 5. Módulo de Clientes

| Etapa                                  | Descrição                             | Tipo             | Estimate     |
| -------------------------------------- | ------------------------------------- | ---------------- | ------------ |
| CRUD de Clientes                       | Cadastro, edição, listagem e exclusão | fullstack        | `estimate:3` |
| Busca rápida de cliente (autocomplete) | Campo de busca dinâmica               | frontend/backend | `estimate:2` |

---

## 📊 6. Dashboard e Indicadores

| Etapa             | Descrição                               | Tipo                         | Estimate     |
| ----------------- | --------------------------------------- | ---------------------------- | ------------ |
| Dashboard inicial | Resumo de vendas, produtos e clientes   | frontend                     | `estimate:3` |
| Gráficos simples  | Vendas do mês / Top produtos            | frontend (Chart.js opcional) | `estimate:3` |
| KPIs e totais     | Faturamento total, estoque crítico etc. | backend/frontend             | `estimate:2` |

---

## 💰 7. Financeiro (MVP simplificado)

| Etapa                       | Descrição                             | Tipo    | Estimate     |
| --------------------------- | ------------------------------------- | ------- | ------------ |
| Contas a receber            | Registro e status de pagamentos       | backend | `estimate:3` |
| Relatório financeiro básico | Somatório por período, exportação CSV | backend | `estimate:3` |

---

## ⚙️ 8. Configurações e Utilidades

| Etapa                              | Descrição                                       | Tipo             | Estimate     |
| ---------------------------------- | ----------------------------------------------- | ---------------- | ------------ |
| Tela de configurações gerais       | Nome da loja, logo, moeda, etc.                 | frontend/backend | `estimate:2` |
| Backup / Restore do banco (manual) | Exportação SQL simples                          | backend          | `estimate:4` |
| Logs de atividade (básico)         | Registrar ações importantes (vendas, exclusões) | backend          | `estimate:3` |

---

## 🧱 9. Refinamento e QA

| Etapa                       | Descrição                                     | Tipo             | Estimate     |
| --------------------------- | --------------------------------------------- | ---------------- | ------------ |
| Revisão de código e padrões | Ajustar nomes, identação e consistência       | backend/frontend | `estimate:2` |
| Testes básicos manuais      | Casos principais de uso (login, venda, CRUDs) | qa               | `estimate:2` |
| Documentação do projeto     | Atualizar README e docs/decisions.md          | docs             | `estimate:2` |

---

## 🔁 Total estimado de esforço (MVP)

**≈ 50–55 horas (estimates 45–60 dependendo da execução paralela)**
Ideal para **duas pessoas em 1–2 semanas** de trabalho leve a moderado.
