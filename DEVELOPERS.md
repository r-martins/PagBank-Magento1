# For Developers (Para desenvolvedores)

## Dual partner (5.0+)

Ver [`docs/OPENMAGE-5-DUAL-PARTNER.md`](docs/OPENMAGE-5-DUAL-PARTNER.md).

### Testando com Docker / WS local

URLs e SSL **não** são configuráveis no admin (evita override acidental ou por outro código).

Para apontar ao ambiente local, altere **somente na sua cópia de trabalho** (não commitar):

`app/code/community/RicardoMartins/PagBank/Api/Connect/ConnectInterface.php`

- `WS_CONNECT_BASE_URI` — v7 PagBank (`CON…`), ex.: `https://v7.test/pspro/v7/connect/` ou `http://ws-pagseguro/pspro/v7/connect/`
- `API_V1_URI` — v1 Vindi (`CONVD…`), ex.: `https://api.pbintegracoes.test/v1/`
- `CURL_SSL_VERIFY` — `false` apenas para certs locais `*.test`

No `docker-compose.yml` do OpenMage, o PHP precisa enxergar o host:

```yaml
extra_hosts:
  - "api.pbintegracoes.test:host-gateway"
  - "v7.test:host-gateway"
```

## PHPStan
Usamos ele para garantir compatibilidade com versões antigas do PHP.

Para instalar o PHPStan, execute o seguinte comando:
```bash
composer require --dev phpstan/phpstan
```

Para rodar o PHPStan, execute o seguinte comando na raiz do módulo clonado:
```bash
./vendor/bin/phpstan analyse
```

Verifique se há métodos não encontrados ou incompatíveis com versões antigas do PHP (7.1 e acima).

Ex: "Function str_contains not found."

## modman
Usamos ele para instalar o módulo em um ambiente Magento.

No entanto, sempre que adicionamos, movemos ou removemos um arquivo do módulo, precisamos atualizar o arquivo `modman` da raiz.

Fazemos isso com o comando
```bash
for i in `find ./*/* -type f ! -name ".DS_Store" | grep -v \.git | grep -v "^.$" | grep -v "modman" | grep -v "vendor" | sed 's/\.\///'`; do echo ${i} ${i}; done > modman
```
