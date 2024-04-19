# For Developers (Para desenvolvedores)

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