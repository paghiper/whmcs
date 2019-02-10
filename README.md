# WHMCS - Módulo de boleto PagHiper 

Permite a emissão de boletos e integração do gateway da Paghiper ao seu WHMCS.
Este módulo implementa emissão de boletos com retorno automático.

* **Versão mais Recente:** 2.0.1.1
* **Requer WHMCS** versão mínima 5.0
* **Requisitos:** PHP >= 5.2.0, cURL e JSON ativado.
* **Compatibilidade:** WHMCS 7.7.0, PHP 7.x. Mod_rewrite opcional


# Como Instalar

1. Crie sua conta na PagHiper [clique aqui para saber como](https://github.com/paghiper/whmcs/wiki/Como-criar-seu-cadastro-na-PagHiper).

2. Baixe o arquivo [paghiper.php](https://github.com/paghiper/whmcs/tree/master/modules/gateways), coloque o arquivo na pasta gateways dentro de modules/gateways, para que o Gateway fique disponível dentro do seu WHMCS. 

3. Dentro da área administrativa do seu WHMCS, vá em: Setup > Payments > Payment Gateways (em inglês) ou Opções > Pagamentos > Portais para Pagamento

4. Após, va na aba “All Payment Gateways” ou "Todos os Portais de Pagamento" e procure pelo modulo de nome: “PagHiper Boleto” e clique em cima.

5. Será exibida uma pagina semelhante a que se encontra na figura abaixo. Basta configurar com suas credenciais, e pronto.

Se tiver dúvidas sobre esse processo, acesse nosso [guia de configuração de plugin](https://github.com/paghiper/whmcs/wiki/Configurando-o-plugin-no-seu-WHMCS)


# Suporte

Para questões relacionadas a integração e plugin, acesse o [forum de suporte no Github](https://github.com/paghiper/whmcs/issues);
Para dúvidas comerciais e/ou sobre o funcionamento do serviço, visite a nossa [central de atendimento](https://www.paghiper.com/atendimento/).

# Changelog

## Disponíveis em alguns dias:

* Emissão antecipada de boletos (automaticamente, no momento da criação da fatura via Cron)
* Disponibilização de linha digitável no painel e e-mails de cobrança/fatura

## 2.0.1.1 - 2019/02/09

`Bugs resolvidos`

* Boletos sendo emitidos para todas as faturas, independente do método de pagamento
* Erro 500 causado pelo invoicepdf.tpl (Adicionada tag de fechamento do php)

## 2.0.1 - 2019/02/08

`Melhorias`

* Agora é possível configurar a nova data de vencimento para reemissão de boletos vencidos
* Possibilidade de restrição na emissão de novos boletos ou emitir para todas as faturas por padrão
* Novas telas de status
* Novo hook para criação do boleto junto com a criação da fatura
* Boleto anexo no e-mail da fatura (veja como ativar aqui)
* Seleção automática de usuário para uso na API Local do WHMCS (necessário para algumas operações internas)
* Novos pontos de log
* Seleção automática de usuário admin para uso na localAPI

`Bugs`

* Melhorada a lógica de busca de boletos emitidos (para faturas vencidas)
* Novo método de formação de URL de retorno

## 2.0 - 2019/02/01

`Novas funcionalidades`

* Uso da nova API 2.0
* Re-utilização de boletos
* Juros/multas automáticos na emissão
* Desconto por pagto. antecipadotbladmin
* Conciliação (acréscimo ou decréscimo de valores) na compensação dos boletos
* Acesso direto ao boleto bancário (por link no e-mail, sem a necessidade de log-in)

`Melhorias`

* Adicionados endpoints para emissão do boleto bancário na emissão da fatura
* Possibilidade de integração do boleto nos e-mails de notificação (boleto PDF na fatura e linha digitável no corpo do e-mail)
* Adicionado novo status "Reservado", para pré-confirmação de pagto.
* Atualização do ícone usado no checkout
* Uso de classes bootstrap para melhor visual no checkout

`Bugs e correções`

* Log de notificação inválida estava armazenando array vazio, impossibilitando debug
* Uso do total da fatura (ao invés do sub-total) - Isso corrige problemas relacionadas a aplicação de taxas, créditos e descontos
* Compatibilidade com WHMCS 7.5
* Erro ao emitir/visualizar boletos através de uma sub-conta

## 1.2.1b - 2019/01/26

`Melhorias e novidades`

* Possibilidade de usar campos separados para CPF e CNPJ

`Bugs e correções`

* Coluna de valores estava sendo criada como FLOAT, causando erro nas queries em algumas versões do MySQL. Boletos são reutilizados corretamente agora.

## 1.2.1 - 2019/01/23

`Melhorias e novidades`

* Atualização de ícone de boleto bancário
* Uso de classes bootstrap para melhor visual no checkout
* Maior clareza nas instruções e remoção de passos desnecessários
* Remoção da "integração avançada"
* Preparação para implementação de linha digitável e boleto anexo no e-mail da fatura

## 1.2 - 2019/01/20

`Melhorias e novidades`

* Compatibilidade com WHMCS 7.5
* Implementação da nova API
* Armazenamento de boletos em tabela, para consulta e re-utilização
* Juros, Multas e desconto por pagto. antecipado
* Conciliação na baixa (para evitar multas como crédito)

`Bugs e correções`

* Uso do total da fatura para cálculo (ao invés do subtotal), para aplicação de descontos e créditos
* Problema nas queries de atualização da tabela mod_paghiper
* Erro ao emitir/visualizar boletos através de uma sub-conta

## 1.121 - 2017/09/30

`Bugs e correções`

* Corrigido erro na formação da URL para recebimento de retorno

## 1.12 - 2017/05/03

`Melhorias e novidades`

* Informa a versão do módulo
* Tabela criada para armazenar retornos da PagHiper.

`Bugs e correções`

* Cálculo de taxas no checkout transparente não era efetuado
* URL do sistema não era retornada caso o WHMCS estivesse instalado em subdiretório
* Caracteres nos nomes de campos personalizados vinham sem HTML entities

## 1.11 - 2017/04/20

`Bugs e correções`

* Problemas relacionados ao checkout transparente
* Omissão de CPF/CNPJ

## 1.1 - 2017/04/13

`Melhorias e novidades`

* Otimização geral e limpeza de código
* Suporte a Checkout transparente
* Integração avançada (campos recebidos podem ser salvos em uma tabela)
* Opção para abrir boleto por link direto
* Envia o nome do cliente em caso de CPF, razão social em caso de CNPJ

`Bugs e correções`

* Loga eventuais problemas com o checkout transparente para debug
* Trata o campo de CPF/CNPJ para se adequar ao formato exigido pela PagHiper
* Usa o nome de usuário admin por padrão, caso não seja informado


## 1.0 - 

* Lançamento inicial

# Licença

Copyright 2016-2019 Serviços Online BR.

Licensed under the 3-Clause BSD License (the "License"); you may not use this file except in compliance with the License. You may obtain a copy of the License at

[https://opensource.org/licenses/BSD-3-Clause](https://opensource.org/licenses/BSD-3-Clause)

Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the License for the specific language governing permissions and limitations under the License.
