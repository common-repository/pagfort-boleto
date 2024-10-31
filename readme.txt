=== Pagfort Boleto Bancário para WooCommerce ===
Contributors: desenvolvimento@pagfort.com.br
Donate link: https://www.protestonacional.com.br/cliente/index.php/informacao/donate
Tags: boleto, payment bank slip, boleto bancário, woocommerce, pagfort, spc protesto nacional
Requires at least: 5.0
Tested up to: 5.4.2
Stable tag: 1.0.1
Requires PHP: 7.0
License: GPLv2 or later

Adicione o Pagfort Boleto como meio de pagamento para receber por boleto bancário em sua loja WooCommerce.

== Description ==

= Como o Pagfort Boleto funciona =

1. Faça cobranças via boleto bancário através da API do SPC Protesto Nacional integrada com sua loja.

2. Quando o cliente finaliza a compra, ele recebe o link para impressão do boleto.

3. Os valores entram em nossa conta operacional antes de fazer o repasse para a sua conta.

4. As transferências dos valores serão creditados em sua conta em D+1 (um dia) após o pagamento ser confirmado na rede bancária.

5. Os boletos que forem emitidos utilizando o plugin Pagfort Boleto e não forem pagos, não serão cobrados pelo cancelamento e baixa do mesmo.

= Quem é o SPC Protesto Nacional? =

Somos uma empresa consolidada no mercado a mais de 10 anos atuando no ramo de emissão de boletos, gerenciamento de dívidas, restritivos de crédito e cobrança especializada e assessoria de cobrança, economizando tempo e dinheiro, com isso diminuindo sua inadimplência.

Quer saber mais sobre nós? [Acesse nosso site.](https://www.protestonacional.com.br/)

[Pagfort](https://pagfort.com.br/) é um método de pagamento brasileiro desenvolvido pelo SPC Protesto Nacional.

= Compatibilidade =

Compatível com versões atuais do [WooCommerce](http://wordpress.org/plugins/woocommerce/).

Este plugin requer o [Brazilian Market on WooCommerce (Antigo WooCommerce Extra Checkout Fields for Brazil)](http://wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/), para enviar dados do cliente como CPF ou CNPJ, além dos campos número e bairro do endereço (obrigatório o uso deste plugin);

Necessário a utilização da **API REST do WooCommerce** para o retorno automático do status de pagamento.

Possuir o SSL instalado e configurado em sua loja.

Ter instalado no seu servidor a extensão do [php-curl](https://www.php.net/manual/pt_BR/book.curl.php) e [php-xml](https://www.php.net/manual/pt_BR/dom.setup.php).

= Instalação =

Confira o nosso guia de instalação e configuração do Pagfort Boleto na aba [Instalação](http://wordpress.org/plugins/pagfort-boleto/installation/).


= Dúvidas? =

Você pode esclarecer suas dúvidas usando:

* A nossa sessão de [FAQ](http://wordpress.org/plugins/pagfort-boleto/faq/).
* Utilizando o nosso [fórum no Github](https://github.com/spcprotestonacional/pagfort-boleto).
* Criando um tópico no [fórum de ajuda do WordPress](http://wordpress.org/support/plugin/pagfort-boleto).

= Colaborar =

Você pode contribuir com código-fonte em nossa página no [GitHub](https://github.com/spcprotestonacional/pagfort-boleto).


== Installation ==

= Instalação do plugin: =

* Envie os arquivos do plugin para a pasta wp-content/plugins, ou instale usando o instalador de plugins do WordPress.

* Caso já tenha instalado e ativado os outros plugins requeridos, ative o plugin do Pagfort Boleto.

= Requerimentos: =

* Possuir uma conta no [SPC Protesto Nacional](https://www.protestonacional.com.br/);
* Ter instalado e ativado o [WooCommerce](http://wordpress.org/plugins/woocommerce/);
* Ter instalado e ativado o [Brazilian Market on WooCommerce (Antigo WooCommerce Extra Checkout Fields for Brazil)](http://wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/);

* Certificado *(SSL)* instalado. 

* PHP 7.0 ou superior.

= Configurações do Plugin Pagfort: =

Com o plugin instalado acesse o admin do WordPress e entre em "Usuários" > "Adicionar novo" e crie um usuário para o Pagfort. (Ex: teste@pagfort.com)

Após a criação do usuário precisamos geras as chaves de API, acesse o admin do WordPress e entre em "WooCommerce" > "Configurações" > "Avançado" > "API REST".

Clique no botão "Adicionar chave", preencha a descrição para identificar de qual usuário pertence as chaves;

Selecione o usuário criado anteriormente;

Selecione a permissão de leitura;

**Importante!**

<blockquote>Clique no botão "Gerar uma chave de API" e não feche a página, é necessário completar as Configurações no site da API antes, caso contrário será preciso criar novamente uma chave da API Rest no WooCommerce.</blockquote>

Após a criação das chaves de API, no menu do admin, vá até "Configurações" > "Links Permanentes" e em "Configurações comuns", marque qualquer outra opção que não seja a padrão e salve as alterações para que as novas URL's sejam atualizadas.

Após completada a configuração, acesse o admin do WordPress e entre em "WooCommerce" > "Configurações" > "Pagamentos", procure pelo plugin Pagfort e clique no botão "Gerenciar", preencha as credenciais de acesso ao ambiente de testes ou de produção da API do SPC Protesto Nacional que lhe foram fornecidas, e salve as informações.

Após salvar as informações, faça um "Teste de conexão" para verificar a comunicação com a API.

Em caso de falhas em ser avisado automaticamente do pagamento, considere a seguinte modificação do seu arquivo .htaccess na raiz do site:

- De -> RewriteRule . /index.php [L]  para -> RewriteRule ^index\.php$ - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization},L]

- De -> RewriteRule ^index\.php$ - [L] para -> RewriteRule ^index\.php$ - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization},L]

= Configurações no site do SPC Protesto Nacional =

Entre em sua conta no site do SPC Protesto Nacional e navegue até o menu "Minha Conta", na aba "WooCommerce", preencha as seguintes informações:

 Endereço da sua loja (Ex: http://www.minhaloja.com.br);
 
 As chaves da API REST que foram criadas, volte na sua loja copie sua *"Consumer Key"* e a *"Consumer Secret"* e cole nos respectivos campos e salve as configurações.

Isso é necessário para que a API retorne o aviso para sua loja, sinalizando que o boleto foi pago e o "Status do pedido" seja alterado para "Processando".

= Configurações adicionais do plugin: = 

- **Dias de vencimento (padrão 5 dias):** Após a emissão do boleto o cliente tem o prazo de 5 dias para o pagamento, caso não tenha sido pago, o administrador deve fazer o cancelamento manualmente do pedido, automaticamente o boleto também será cancelado na API do SPC Protesto Nacional.
- **Desconto para o pagamento:** Permite configurar um desconto padrão para as vendas realizadas com o Pagfort Boleto, máximo permitido de 99.99%.
- **Permitir cobrança:** O administrador pode optar por fazer a cobrança deste boleto do seu cliente. [Saiba mais sobre as formas de cobrança](https://protestonacional.com.br/index.php/informacao/servico#como-funciona-cobranca)

= Configurações do Plugin WooCommerce Extra Checkout Fields for Brazil =

Com o WooCommerce Extra Checkout Fields for Brazil instalado e ativado você deve ir até "WooCommerce > Campos do Checkout" e configurar a opção "Exibir Tipo de Pessoa" como "Pessoa Física e Pessoa Jurídica", marcar as opções "Exibir RG" e "Exibir Inscrição Estadual".

Pronto, sua loja já pode receber pagamentos pelo Pagfort.

== Frequently Asked Questions ==

= Qual é a licença do plugin? =

Este plugin esta licenciado como GPL.

= O que eu preciso para utilizar este plugin? =

* Ter instalado uma versão atual do plugin WooCommerce.
* Ter instalado uma versão atual do plugin WooCommerce Extra Checkout Fields for Brazil.
* Possuir uma conta no SPC Protesto Nacional.
* Certificado digital instalado (SSL).


= Quais são os meios de pagamento que o plugin aceita? =

Somente boleto bancário no momento.

= Como enviar as informações de "CPF", "CNPJ", "Número" e "Bairro", para o Pagfort Boleto? =

Utilize o plugin [Brazilian Market on WooCommerce (Antigo WooCommerce Extra Checkout Fields for Brazil)](http://wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/).

= Pagfort Boleto recebe pagamentos de quais países? =

Apenas do Brasil.

= O boleto foi pago e ficou com o status de "processando" e não como "concluído" nos pedidos do WooCommerce, como isso funciona? =

O status do pedido encontra-se "processando" no momento que é confirmado o pagamento do boleto, jamais deve ser alterado automaticamente para "concluído", o status deve mudar para "concluído" somente após ter sido entregue.

= Ocorreu algum erro durante a finalização da compra =

Ao tentar finalizar a compra ocorra algum erro, por favor, tente novamente ou entre em contato para obter ajuda.

É possível ativar a opção de **Log de depuração** nas configurações do plugin.
Com o log é possível saber qual o problema está ocorrendo com o plugin e sua instalação ou utilização.

Você pode abrir um [tópico no fórum do plugin](https://wordpress.org/support/plugin/pagfort-boleto#postform) com o link do log (utilize o [pastebin.com](http://pastebin.com),  [gist.github.com](http://gist.github.com) ou qualquer outro editor on-line de sua preferência para salvar o conteúdo do log).

= O status do pedido não é alterado automaticamente? =

Sim, o status é alterado automaticamente para "Processando" quando o boleto for pago na API do SPC Protesto Nacional. Você deve alterar manualmente o status para "Concluído".

= Outras dúvidas sobre funcionamento do plugin? =

Para outras dúvidas, [abra um tópico no fórum do plugin](https://wordpress.org/support/plugin/pagfort-boleto#postform) com o link do seu arquivo de log.

== Screenshots ==

1. Configurações da API Rest.
2. Criação da chave da API Rest.
3. Configuração dos campos do checkout.
4. Configuração do plugin.
5. Pedido finalizado no ambiente de teste.
6. Pedido finalizado no ambiente de produção.
7. Configuração do site da API.

== Changelog ==

= 1.0.0 - 2019/09/20 = 

- Versão inicial de lançamento

== Upgrade Notice ==

 = 1.0.0 = 

* Inicial
