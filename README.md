# Módulo Braintree Brasil para Magento2
![](https://raw.githubusercontent.com/wiki/paypal/PayPal-PHP-SDK/images/homepage.jpg)

Página oficial do módulo Braintree com as soluções utilizadas no mercado Brasileiro para Magento 2.

## Descrição

Este módulo contém os principais produtos PayPal para o mercado Brasileiro:
- **Checkout com Cartão de Crédito**: O cliente paga somente utilizando o seu cartão de crédito, sem a necessidade de ter uma conta PayPal.
- **Checkout com Cartão de Débito**: O cliente paga somente utilizando o seu cartão de Débito, sem a necessidade de ter uma conta PayPal.
- **Smart Payment Button (Novo Express Checkout)**: O cliente paga com a sua conta PayPal ou cria uma no momento da compra.

## Requisitos

O servidor precisa ter suporte à TLS 1.2 ou superior e HTTPS 1.1;

O servidor precisa ter suporte à PHP 8.1 ou superior.

**A solução de Braintree só irá funcionar caso tenha sido aprovado pelo PayPal.**

## Compatibilidade

Versões Magento Open Source 2.4.4 até 2.4.6 e Commerce Cloud 2.4.4 até 2.4.6;

## Instalação

Este módulo está disponível através do Composer, você não precisa mais especificar o repositório.

Para instalar, adicione as seguintes linhas ao seu composer.json:

```
...
"require":{
    ...
    "br-paypaldev/module-braintree-brasil":"^2.0"
 }
```
Ou simplesmente digite  o comando abaixo:
```
composer require br-paypaldev/module-braintree-brasil --no-update

```

Em seguida, digite os seguintes comandos da sua raiz do Magento:

```
$ composer update br-paypaldev/module-braintree-brasil

$ ./bin/magento setup:upgrade
$ ./bin/magento cache:clean
$ ./bin/magento setup:di:compile
```

Quando a loja está no modo Produção, é necessário gerar novamente os arquivos estáticos:

```
$ ./bin/magento setup:static-content:deploy
```

Caso tenha problemas com as permissões de pasta ao acessar a loja, será necessário renovar as permissões das pastas:

```
$ chmod 777 -R var/ pub/ generated/
```

Para visualizar os modulos ativos:
```
$ ./bin/magento module:status
```
Você verá o Braintree Brasil na lista de ativos.

## Configuração

Para que o PayPal Braintree funcione corretamente deverá realizar algumas configurações padrão no magento.

### - Checkout como convidado

Validar se clientes não registrados podem realizar a compra:

Admin >> Lojas >> Configuração >> Vendas >> Finalizar Compra >> Opções de compra >> Permitir Compras por Visitantes > SIM

### - Endereço padrão BR do Magento

Após a instalação do módulo, validar a configuração de linhas de endereço do cliente, deixando em 4 (quatro) linhas em:

 Admin >> Lojas >> Configuração >> Clientes >> Configuração de cliente >> Opções de endereço.


### - CPF/CNPJ - TaxVat
O campo CPF/CNPJ é obrigatório, para habilitá-lo siga os passos abaixo dentro do painel administrativo do Magento:

**Habilitar o VAT Number no Front-end:**
-Admin >> Lojas >> Atributos >> Clientes >> Tax /VAT Number (Habilitar como "Required")

**Habilitar como obrigatório o Tax/VAT Number no endereço do Cliente:**
-Admin >> Lojas >> Configuração >> Clientes >> Configuração de cliente >> Opções de endereço -> Nome e opção de endereço -> Mostrar Tax/VAT (Habilitar como "Required")


### - Configuração de Credenciais

O Braintree não está disponível para uso sem vetting. Portanto, assim que seu e-commerce/negócio passar por este processo, o engenheiro responsável irá prover instruções de como gerar as credenciais.

## Atualização

Para atualizar o módulo rode os comandos abaixo no Composer:

```
    $ composer update br-paypaldev/module-braintree-brasil
    $ ./bin/magento setup:upgrade
    $ ./bin/magento setup:di:compile
```

## Dúvidas/Suporte

Caso a sua dúvida não tenha sido respondida aqui, entre em contato com o PayPal pelo número 0800 047 4482.

E caso necessite de algum suporte técnico e/ou acredita ter encontrado algum problema com este módulo acesse o nosso [portal de suporte técnico](https://www.paypal-support.com/s/?language=pt_BR) e abra um ticket detalhando o seu problema na seção "Fale Conosco".

## Changelog

Para visulizar as últimas atualizações acesse o [**CHANGELOG.md**](CHANGELOG.md).
