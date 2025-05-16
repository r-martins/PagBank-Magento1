[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207.1-8892BF.svg?style=flat-square)](https://php.net/)
[![GitHub release](https://img.shields.io/github/v/release/r-martins/PagBank-Magento1)](https://github.com/r-martins/PagBank-Magento1)
![GitHub last commit (branch)](https://img.shields.io/github/last-commit/r-martins/PagBank-Magento1/develop)
# Módulo PagBank(PagSeguro) Connect para Magento1
![Módulo PagBank para Magento 2](https://i.ibb.co/Z2Bbnz6/Banner-Magento1-Pag-Bank.jpg?1=1)
## Nova Geração - Novas APIs, Novos Recursos, Mais Estabilidade
Aceite mais de 30 cartões de crédito, PIX e boleto em sua loja [Magento 1.x](http://bit.ly/m1download) ou [OpenMage LTS](https://www.openmage.org/), usando o meio de pagamento mais aceito pelos brasileiros.

Chega de reprovações e chargebacks! Nosso novo módulo tem suporte a PIX transparente e 3D Secure, alcançando até 99% de aprovação no cartão de crédito sem necessidade de antifraude.

Agora usando as Novas APIs do PagBank.

A integração mais usada do Brasil, agora com mais recursos, mais estabilidade e ainda mais segurança.

# Recursos

- Aceite pagamentos com Cartão de Crédito, PIX ou Boleto de forma transparente (sem sair da loja)
- [Autenticação 3D Secure](https://pagsegurotransparente.zendesk.com/hc/pt-br/articles/22375922278157) para evitar chargebacks e aumentar aprovação
- PIX e Boleto com validades configuráveis
- Atualização automática do status do pedido
- Pagamento em 1x ou parcelado, com várias configurações de parcelamento
- Identificador do nome da loja na fatura
- Descontos nas taxa oficiais do PagBank (ou use suas taxas)
- Suporte a Sandbox
- Link direto para a transação disponível no admin
- Suporte a todos os tipos de produtos
- Suporte a multi-loja
- Desenvolvido nos padrões Magento por desenvolvedores certificados pela Adobe 🏆
- Compatível com os principais checkouts do mercado (OneStepCheckout Inovarti, IWD, FireCheckout, etc)

<details>
  <summary>Veja alguns Screenshots (clique aqui para expandir)</summary>
  <img src="https://i.imgur.com/TNU3sgW.png" alt="Cartão de crédito PagBank(PagSeguro) no front end" title="Cartão de crédito PagBank(PagSeguro) no front end"/>
  <img src="https://i.imgur.com/qpIkGT8.png" alt="PIX - Tela de Sucesso" title="PIX - Tela de Sucesso"/>
  <img src="https://i.imgur.com/fjirExl.png" alt="Configurações de cartão de crédito" title="Configurações de cartão de crédito"/>
  <img alt="Admin - Tela do pedido" src="https://i.imgur.com/rfx75Rt.png" title="Admin - Tela do pedido"/>
  <img alt="Autenticação 3D Secure" src="https://i.imgur.com/IrhEgMN.png" title="Autenticação 3D Secure"/>
  <img alt="Compatibilidade com OpenMage e os principais checkouts do mercado" src="https://i.imgur.com/gdUCw42.png" title="Compatibilidade com OpenMage e os principais checkouts do mercado"/>
</details>

# Pré-requisitos
- OpenMage LTS (recomendado) ou Magento 1.7.x a 1.9.x
- PHP 7.1 ou superior (recomendado 7.4 ou superior) / PHP 5.6 é suportado, mas não recomendado
- Extensão PHP [mbstring habilitado](https://pt.stackoverflow.com/a/200646/331)

# Instalação

Escolha uma das formas de instalação abaixo:

## Instale com Composer
- Certifique-se que você [configurou seu Magento para ter suporte a composer](https://www.magenteiro.com/blog/magento-1/magento-e-composer-como-gerenciar-melhor-seus-modulos-e-pacotes/).
- Digite o comando `composer require ricardomartins/pagbank-magento1` na raiz da sua instalação Magento.
- Limpe o cache do Magento

## Instalação manual
- Baixe a versão mais recente do módulo [aqui](https://github.com/r-martins/PagBank-Magento1/archive/refs/heads/master.zip)
- Extraia o arquivo baixado e copie as pastas para a raiz da sua instalação Magento
- - As pastas `app`, `lib` e `skin`, `js`, etc devem ser copiadas para a raiz da instalação mesclando-se com as pastas existentes e não substituindo-as
- Limpe o cache do Magento
- ou...

## Instalação com modman
Você pode usar [modman](https://github.com/colinmollenhour/modman) para instalar o módulo. Para isso, siga os passos abaixo:
- Digite o comando `modman clone https://github.com/r-martins/PagBank-Magento1.git` na raiz da sua instalação Magento.
- Limpe o cache do Magento

# Configuração do Módulo PagBank(PagSeguro) Connect
- Navegue até Lojas &gt; Configurações &gt; Vendas &gt; Formas de Pagamento &gt; **PagBank - Ricardo Martins**.
  - Clique em "Obter Connect Key" ou "Obter Connect Key para Testes" e siga as instruções para obter sua Connect Key e preenche-la no campo indicado logo abaixo.
  - Salve as configurações e você está pronto para vender.
- Se desejar, configure opções de parcelamento, e validade do boleto e código pix de acordo com suas necessidades.
- Em Clientes &gt; Configurações &gt; Opções de Nome e Endereço, configure o campo "Número de Linhas p/ Endereço" para 4.


## Criptografia do cartão no checkout ℹ️
Dependendo do checkout que estiver utilizando ou de customizações no tema, você também precisará [configurar o "Identificador CSS do botão de Finalizar Compra"](https://pagsegurotransparente.zendesk.com/hc/pt-br/articles/360039820711-Configurando-o-bot%C3%A3o-de-Finalizar-Compra).

Isso é necessário para que o módulo possa criptografar os dados do cartão de crédito antes de enviá-los ao PagBank e também para o correto funcionamento do recurso de 3D Secure (quando ativado).


# Site Oficial do Módulo
https://pbintegracoes.com/

Disponível também para [Magento 2.x](https://pbintegracoes.com/magento2.html) e [WooCommerce](https://pbintegracoes.com/woocommerce.html).

# Cursos Magento
Em [Magenteiro.com/cursos](https://www.magenteiro.com/cursos?___store=default&utm_source=github-m1&utm_medium=readme&utm_campaign=readme.md) você encontra uma dezena de cursos pagos e gratuitos sobre Magento, inclusive o famoso curso gratuito [Sua loja com PagSeguro e OneStepCheckout](https://www.magenteiro.com/sua-loja-com-pagseguro-e-onestepcheckout?___store=default&utm_source=github-m1&utm_medium=readme&utm_campaign=readme.md).

Veja tamém o [canal Magenteiro no YouTube](https://youtube.com/@Magenteiro).

# Central de ajuda e suporte
https://pagsegurotransparente.zendesk.com/hc/pt-br/



# Autores
- Ligia Salzano (@ligiasalzano) - [Adobe Certified Professional - 3x](https://www.credly.com/users/ligia-salzano) 🏆🏆🏆
- Ricardo Martins (@magenteiro) - [Adobe Certified Professional](https://www.credly.com/badges/8a2af83e-60c6-447a-b8e5-9154dd97751b) 🏆

&ast; Estes são os autores da versão inicial. Novos autores e colaboradores não certificados podem vir a contribuir com futuras versões e podem ser encontrados [aqui](https://github.com/r-martins/PagBank-Magento1/graphs/contributors).

# Perguntas Frequentes (FAQ)

## Como funcionam os descontos nas taxas?

Ao usar nossas integrações no modelo de recebimento em 14 ou 30 dias, ao invés de pagar 4,99% ou 3,99%, você pagará cerca de 0,60% a menos e estará isento da taxa de R$0,40 por transação.

Taxas menores são aplicadas para transações parceladas, PIX e Boleto.

Consulte mais sobre as taxas atualizadas no [nosso site](https://pbintegracoes.com/).

## Eu tenho uma taxa ou condição negociada menor que estas. O que faço?

Ao usar nossa integração, nossas taxas e condições serão aplicadas ao invés das suas. Isto é, nas transações realizadas com nosso plugin.

É importante notar que taxas negociadas no mundo físico (moderninhas) não são aplicadas no mundo online.

Se mesmo assim você possuir uma taxa ou condição melhor, e se compromete a faturar mais de R$20 mil / mês (pedidos aprovados usando nossa integração), podemos incluir sua loja em uma aplicação especial. Basta selecionar o modelo "Minhas taxas" quando obter sua Connect Key.


## Tenho outra pergunta não listada aqui

Consulte nossa [Central de ajuda](https://pagsegurotransparente.zendesk.com/hc/pt-br/) e [entre em contato](https://pagsegurotransparente.zendesk.com/hc/pt-br/requests/new) conosco se não encontrar sua dúvida respondida por lá.

A maioria das dúvidas estão respondidas lá. As outras são respondidas em até 2 dias após entrar em contato.

## O plugin atualiza os status automaticamente?

Sim.

E quando há uma transação no PagBank, um link para ela é exibida na página do pedido. Assim você pode confirmar novamente o status do mesmo.

Certifique-se de [configurar seu Firewall (ou Cloudflare)](https://pagsegurotransparente.zendesk.com/hc/pt-br/articles/115002699823-Usu%C3%A1rios-Cloudflare-e-CDN-s) para permitir conexões de entrada em sualoja.com/pagbank/notification/ para evitar problemas.

## Como posso testar usando a Sandbox?

Basta clicar no botão 'Obter Connect Key para Testes' localizado nas configurações do módulo e seguir as instruções.

Depois é só informar sua Connect Key de testes no campo "Chave Connect".

Utilize estes [cartões de teste](https://pagsegurotransparente.zendesk.com/hc/pt-br/articles/22375426666253) de acordo com as configurações e cenário que deseja testar.

Note que há diferenças entre cartões de teste e cartões para testar compras com 3D Secure habilitado.

## Este é um plugin oficial?

Não. Este é um plugin desenvolvido por Ligia Salzano, Ricardo Martins (e equipe/colaboradores), assim como outros para Magento e WooCommerce desenvolvidos no passado.

Apesar da parceria entre o desenvolvedor e o PagBank que concede descontos e benefícios, este NÃO é um produto oficial.

PagSeguro e PagBank são marcas do UOL.


## Posso modificar e comercializar este plugin?

O plugin é licenciado sob GPL v3. Você pode modificar e distribuir, contanto que suas melhorias e correções sejam contribuidas de volta com o projeto.

Você deve fazer isso através de Pull Requests ao [repositório oficial no github](https://github.com/r-martins/PagBank-Magento1).

# Garantia

Conhecido como "software livre", este plugin é distribuido sem garantias de qualquer tipo.

O desenvolvedor ou PagBank não se responsabilizam por quaisquer danos causados pelo uso (ou mal uso) deste plugin.

Esta é uma iniciativa pessoal, sem vínculo com PagBank. PagBank é uma marca do UOL.

Este não é um produto oficial do PagBank.

Ao usar este plugin você concorda com os [Termos de Uso e Política de Privacidade](https://pbintegracoes.com/terms).

# Links úteis

- [Site Oficial das Integrações PagBank por Ricardo Martins](https://pbintegracoes.com/)
- [Central de Ajuda](https://pagsegurotransparente.zendesk.com/hc/pt-br/)
- [Termos de Uso e Política de Privacidade](https://pbintegracoes.com/terms)
- [Vídeos no Youtube](https://www.youtube.com/@Magenteiro/search?query=pagseguro)
- [Artigos](https://www.magenteiro.com/blog/tag/pagseguro)
