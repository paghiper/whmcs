# WHMCS - Módulo de boleto PagHiper 

Permite a emissão de boletos e integração do gateway da Paghiper ao seu WHMCS.
Este módulo implementa emissão de boletos com retorno automático.

* **Versão mais Recente:** 1.1
* **Requer WHMCS** versão mínima 5.0
* **Requisitos:** PHP >= 5.2.0, cURL ativado.
* **Compatibilidade:** WHMCS 7.1.2, PHP 7.x. Mod_rewrite opcional


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
