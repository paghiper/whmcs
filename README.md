# WHMCS - Módulo de boleto PagHiper 

Permite a emissão de boletos e integração do gateway da Paghiper ao seu WHMCS.
Este módulo implementa emissão de boletos com retorno automático.

* **Versão mais Recente:** 1.13
* **Requer WHMCS** versão mínima 5.0
* **Requisitos:** PHP >= 5.2.0, cURL ativado.
* **Compatibilidade:** WHMCS 7.1.2 ou Superior, PHP 7.x. Mod_rewrite opcional

## Informações do projeto

[![GitHub issues](https://img.shields.io/github/issues/paghiper/whmcs.svg)](https://github.com/paghiper/whmcs/issues)
[![GitHub forks](https://img.shields.io/github/forks/paghiper/whmcs.svg)](https://github.com/paghiper/whmcs/network)
[![GitHub stars](https://img.shields.io/github/stars/paghiper/whmcs.svg)](https://github.com/paghiper/whmcs/stargazers)
[![GitHub license](https://img.shields.io/github/license/paghiper/whmcs.svg)](https://github.com/paghiper/whmcs/blob/master/LICENSE)


# Como Instalar

1. [Crie sua conta](https://www.paghiper.com/abra-sua-conta/) na PagHiper.

2. Baixe o [módulo](https://github.com/paghiper/whmcs/archive/master.zip), extraia a pasta para raiz do seu WHMCS (seudominio.com.br/whmcs), fazendo com que o arquivo `paghiper.php` fique dentro da pasta `/whmcs/modules/gateways` 

3. Dentro da área administrativa do seu WHMCS, vá em: Setup > Payments > Payment Gateways (em inglês) ou Opções > Pagamentos > Portais para Pagamento

4. Após, va na aba “All Payment Gateways” ou "Todos os Portais de Pagamento" e procure pelo modulo de nome: “PagHiper Boleto” e clique em cima.

5. Será exibida uma pagina semelhante a que se encontra na figura abaixo. Basta configurar com suas credenciais, e pronto.

Se tiver dúvidas sobre esse processo, acesse nosso [guia de configuração de plugin](https://atendimento.paghiper.com/hc/pt-br/articles/360001296173-M%C3%B3dulo-PAGHIPER-para-WHMCS)


# Suporte

Para questões relacionadas a integração e plugin, acesse o [forum de suporte no Github](https://github.com/paghiper/whmcs/issues);
Para dúvidas comerciais e/ou sobre o funcionamento do serviço, visite a nossa [central de atendimento](https://atendimento.paghiper.com/hc/pt-br).


# Changelog

## Planejado para a próxima versão

* Reutilização de boletos ao invés de emitir um novo a cada acesso
* Emissão antecipada de boletos (automaticamente, no momento da criação da fatura via Cron)
* Disponibilização de linha digitável no painel e e-mails de cobrança/fatura

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

Copyright 2016 Serviços Online BR.

Licensed under the 3-Clause BSD License (the "License"); you may not use this file except in compliance with the License. You may obtain a copy of the License at

[https://opensource.org/licenses/BSD-3-Clause](https://opensource.org/licenses/BSD-3-Clause)

Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the License for the specific language governing permissions and limitations under the License.
