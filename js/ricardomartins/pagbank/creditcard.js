class RMPagBank {
    constructor(config, pagseguro_connect_3d_session) {
        console.log('PagBank: Inicializando módulo de cartão de crédito');
        this.config = config;
        this.formElementAndSubmit = false;
        this.addCardFieldsObserver();
        this.getInstallments();

        if (pagseguro_connect_3d_session !== '' && this.config.enabled_3ds) {
            document.getElementById('ricardomartins_pagbank_cc_cc_has_session').value = 1;
        }

        if (this.config.enabled_3ds && pagseguro_connect_3d_session !== '') {
            this.setUp3DS(pagseguro_connect_3d_session);
        }

        this.placeOrderEvent();
    }

    placeOrderEvent() {
        const methodForm = document.querySelectorAll('#payment_form_ricardomartins_pagbank_cc');
        if (!methodForm.length) {
            console.log('PagBank: Não há métodos de pagamento habilitados em exibição. Execução abortada.');
            return;
        }

        const mutationAttributesCallback = (mutationsList) => {
            const observedAttributes = ['class', 'disabled'];
            for (const mutation of mutationsList) {
                if (
                    mutation.type !== 'attributes' ||
                    !observedAttributes.includes(mutation.attributeName)
                ) {
                    return
                }

                if (mutation.target.hasAttribute('id')) {
                    let id = mutation.target.getAttribute('id');
                    let button = document.getElementById(id);
                    button.className = mutation.target.className;

                    if (mutation.target.hasAttribute('disabled') === false) {
                        button.removeAttribute('disabled');
                    } else {
                        button.setAttribute('disabled', '');
                    }
                }
            }
        }
        const observer = new MutationObserver(mutationAttributesCallback);

        let form = methodForm[0].closest('form');

        let buttons = [
            '#onestepcheckout-place-order-button',
            '.btn-checkout',
            '#payment-buttons-container .button'
        ];
        let configuredButton = this.config.placeorder_button;
        if (configuredButton) {
            console.log('PagBank: um botão de finalização foi configurado.', configuredButton);
            configuredButton = configuredButton.split(',');
            buttons.unshift(...configuredButton);

            // Remove duplicated buttons
            buttons = buttons.filter((value, index) => {
                return buttons.indexOf(value) === index;
            });
        }

        let eventAlreadyAttached = false;
        buttons.forEach((btn) => {
            let button = document.querySelector(btn);

            if (!button || eventAlreadyAttached) {
                return;
            }

            observer.observe(button, { attributes: true });

            let onclickEvent = button.getAttribute('onclick');
            button.removeAttribute('onclick');

            let newButton = button.cloneNode(true);
            button.parentNode.replaceChild(newButton, button);

            let validateAndPreventDefault = function (event) {
                let paymentMethod = document.querySelector('input[name="payment[method]"]:checked').value;
                if (paymentMethod !== 'ricardomartins_pagbank_cc') {
                    button.setAttribute('onclick', onclickEvent);
                    button.click();
                    return true;
                }
                event.preventDefault();
                event.stopImmediatePropagation();

                RMPagBankObj.cardActions().then((result) => {
                  if (RMPagBankObj.proceedCheckout) {
                    button.setAttribute("onclick", onclickEvent)
                    button.click()
                    return true
                  }
                })
                .catch((error) => {
                  console.error("Erro ao executar os eventos do cartão:", error)
                })
            }

            newButton.addEventListener('click', validateAndPreventDefault, false);
            form.addEventListener('submit', validateAndPreventDefault, false);

            eventAlreadyAttached = true;
        });

        if (!eventAlreadyAttached) {
            throw new Error('PagBank: Não foi possível adicionar o evento de clique ao botão de finalizar compra.');
        }
    }

    addCardFieldsObserver() {
        try {
            let numberElem = document.getElementById('ricardomartins_pagbank_cc_cc_number');
            if (!numberElem) throw new Error('Elemento não encontrado');
            numberElem.addEventListener('change', (e) => { RMPagBankObj.updateInstallments(); });
            numberElem.addEventListener('change', (e) => { RMPagBankObj.setBrand(); });
        } catch (e) {
            console.error('PagBank: Não foi possível adicionar observevação aos cartões. ' + e.message);
        }
    }

    async cardActions() {
        RMPagBankObj.proceedCheckout = false;
        if (RMPagBankObj.config.debug) {
            console.log('Iniciando criptografia do cartão');
        }

        let result = RMPagBankObj.encryptCard();

        if (RMPagBankObj.config.debug) {
            console.log('Criptografia do cartão finalizada', result);
        }

        if (RMPagBankObj.config.enabled_3ds) {
            if (RMPagBankObj.config.debug) {
                console.log('3DS iniciando...');
            }

            result = await RMPagBankObj.authenticate3DS();

            if (RMPagBankObj.config.debug) {
                console.log('3DS finalizado');
            }
        } else {
            RMPagBankObj.proceedCheckout = true;
        }

        this.enablePlaceOrderButton();
        return result;
    }

    setBrand() {
        let brandInput = document.getElementById('ricardomartins_pagbank_cc_cc_brand');
        let numberInput = document.getElementById('ricardomartins_pagbank_cc_cc_number');
        let urlPrefix = 'https://stc.pagseguro.uol.com.br/';
        if (this.config.stc_mirror) {
            urlPrefix = 'https://stcpagseguro.ricardomartins.net.br/';
        }
        let flagSrc = urlPrefix + 'public/img/payment-methods-flags/68x30/{brand}.png';
        let style = `background-image: url(${flagSrc});background-repeat: no-repeat;background-position: calc(100% - 5px) center;background-size: auto calc(100% - 6px);`;

        let ccNumber = numberInput.value.replace(/\s/g, '');

        let cardTypes = this.getCardTypes(ccNumber);
        if (cardTypes.length > 0) {
            style = style.replace(/{brand}/g, cardTypes[0].type);
            numberInput.style = style;
            brandInput.value = cardTypes[0].type;
            if (this.config.debug) {
                console.log("Bandeira armazenada com sucesso");
            }
        } else {
            numberInput.style = '';
            if (this.config.debug) {
                console.log("Bandeira não encontrada");
            }
        }
    }

    encryptCard() {
        if (RMPagBankObj.config.debug) {
            console.log('Encrypting card');
        }

        //inputs
        let holderInput = document.getElementById('ricardomartins_pagbank_cc_cc_owner');
        let numberInput = document.getElementById('ricardomartins_pagbank_cc_cc_number');
        let expInput = document.getElementById('ricardomartins_pagbank_cc_cc_exp');
        let cvcInput = document.getElementById('ricardomartins_pagbank_cc_cc_cvc');
        let numberEncryptedInput = document.getElementById('ricardomartins_pagbank_cc_cc_number_encrypted');

        //get input values
        holderInput = holderInput.value;
        numberInput = numberInput.value;
        cvcInput = cvcInput.value;
        expInput = expInput.value;

        if (holderInput === '' || numberInput === '' || cvcInput === '' || expInput === '') {
            return false;
        }

        let holderName = holderInput.trim().replace(/\s+/g, ' ');
        let ccNumber = numberInput.replace(/\s/g, '');
        let ccCvc = cvcInput.replace(/\s/g, '');
        let expMonth = expInput.split('/')[0].replace(/\s/g, '');
        let expYear = '20' + expInput.split('/')[1].slice(-2).replace(/\s/g, '');

        this.disablePlaceOrderButton();
        let card;
        try {
            const publicKey = this.config.publicKey;
            card = PagSeguro.encryptCard({
                publicKey: publicKey,
                holder: holderName,
                number: ccNumber,
                expMonth: expMonth,
                expYear: expYear,
                securityCode: ccCvc,
                success: function (data) {
                    if (RMPagBankObj.config.debug) {
                        console.log('Card encrypted successfully');
                    }
                },
                error: function (data) {
                    console.error('Error encrypting card.', data);
                }
            });
        } catch (e) {
            alert("Erro ao criptografar o cartão.\nVerifique se os dados digitados estão corretos.");
            if (RMPagBankObj.config.debug) {
                console.error('Erro ao criptografar o cartão.\nVerifique se os dados digitados estão corretos.', e);
            }
            return false;
        }

        if (card.hasErrors) {
            let errorCodes = [
                { code: 'INVALID_NUMBER', message: 'Número do cartão inválido' },
                { code: 'INVALID_SECURITY_CODE', message: 'CVV Inválido. Você deve passar um valor com 3, 4 ou mais dígitos.' },
                { code: 'INVALID_EXPIRATION_MONTH', message: 'Mês de expiração incorreto. Passe um valor entre 1 e 12.' },
                { code: 'INVALID_EXPIRATION_YEAR', message: 'Ano de expiração inválido.' },
                { code: 'INVALID_PUBLIC_KEY', message: 'Chave Pública inválida.' },
                { code: 'INVALID_HOLDER', message: 'Nome do titular do cartão inválido.' }
            ];
            let error = '';
            for (let i = 0; i < card.errors.length; i++) {
                //loop through error codes to find the message
                for (let j = 0; j < errorCodes.length; j++) {
                    if (errorCodes[j].code === card.errors[i].code) {
                        error += errorCodes[j].message + '\n';
                        break;
                    }
                }
            }
            alert('Erro ao criptografar cartão.\n' + error);

            if (RMPagBankObj.config.debug) {
                console.error('Erro ao criptografar cartão.\n' + error);
            }

            this.enablePlaceOrderButton();
            return false;
        }

        numberEncryptedInput.value = card.encryptedCard;
        return true;
    }

    updateInstallments() {
        let cardNumber = document.getElementById('ricardomartins_pagbank_cc_cc_number').value;
        let ccBin = cardNumber.replace(/\s/g, '').substring(0, 6);
        let ccBinInput = document.getElementById('ricardomartins_pagbank_cc_cc_bin');
        if (ccBin !== window.pb_cc_bin && ccBin.length === 6) {
            window.pb_cc_bin = ccBin;
            this.getInstallments();
            ccBinInput.value = ccBin;
        }
    }

    getInstallments() {
        let ccBin = typeof window.pb_cc_bin === 'undefined' || window.pb_cc_bin.replace(/[^0-9]/g, '').length < 6 ? '555566' : window.pb_cc_bin;
        fetch(this.config.installments_endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `cc_bin=${ccBin}`
        })
            .then(response => response.text())
            .then(response => {
                if (RMPagBankObj.config.debug) {
                    console.log('Installments response:', response);
                }

                response = JSON.parse(response);

                let select = document.getElementById('ricardomartins_pagbank_cc_cc_installments');
                select.innerHTML = '';

                for (let i = 0; i < response.length; i++) {
                    let installmentValue = parseInt(response[i].installment_value) / 100;
                    let installmentAmount = installmentValue.toFixed(2).toString().replace('.', ',');
                    let text = response[i].installments + 'x de R$ ' + installmentAmount;

                    let totalAmount = parseInt(response[i].amount.value) / 100;
                    totalAmount = totalAmount.toFixed(2).toString().replace('.', ',');

                    let additionalText = ' (sem juros)';
                    if (response[i].interest_free === false) {
                        additionalText = ' (Total R$ ' + totalAmount + ')';
                    }

                    let option = document.createElement('option');
                    option.value = response[i].installments;
                    option.text = text + additionalText;
                    select.appendChild(option);
                }
            })
            .catch(() => {
                alert('Error getting installments. Please try again.');
                if (RMPagBankObj.config.debug) {
                    console.error('Error getting installments. Please try again.');
                }
            });
    }

    setUp3DS(pagseguro_connect_3d_session) {
        PagSeguro.setUp({
            session: pagseguro_connect_3d_session,
            env: this.config.environment,
        });
    }

    async authenticate3DS() {
        let holderInput = document.getElementById('ricardomartins_pagbank_cc_cc_owner');
        let numberInput = document.getElementById('ricardomartins_pagbank_cc_cc_number');
        let expInput = document.getElementById('ricardomartins_pagbank_cc_cc_exp');
        let installmentsInput = document.getElementById('ricardomartins_pagbank_cc_cc_installments');
        let card3dsInput = document.getElementById('ricardomartins_pagbank_cc_cc_3ds_id');

        //get input values
        holderInput = holderInput.value;
        numberInput = numberInput.value;
        expInput = expInput.value;
        installmentsInput = installmentsInput.value;

        if (holderInput === '' || numberInput === '' || expInput === '') {
            return false;
        }

        this.disablePlaceOrderButton();
        this.enablePageLoader();

        const quote = await this.getQuoteData();

        let holderName, ccNumber, expMonth, expYear, installments;

        //replace trim and remove duplicated spaces from input values
        holderName = holderInput.trim().replace(/\s+/g, ' ');
        ccNumber = numberInput.replace(/\s/g, '');
        installments = installmentsInput.replace(/\s/g, '');
        expMonth = expInput.split('/')[0].replace(/\s/g, '');
        expYear = '20' + expInput.split('/')[1].slice(-2).replace(/\s/g, '');

        let email = quote.email ? quote.email : $$('input[name^="billing[email]').first().value;
        let name = quote.customerName && quote.customerName?.trim()?.length > 0 ? quote.customerName
            : $$('input[name^="billing[firstname]').first().value + ' '
            + $$('input[name^="billing[lastname]').first().value;
        //replace trim and remove duplicated spaces from input values
        name.replace(/[0-9]/g, '').replace(/[^\p{L} ]+/gu, '').replace(/\s+/g, ' ').trimStart().trimEnd()

        let phone = quote.phone.replace(/\D/g, '');
        if (!phone) {
            let tel = document.querySelector('input[name^="billing[telephone"]');
            let fax = document.querySelector('input[name^="billing[fax"]');
            phone = tel ? tel.value.replace(/\D/g, '') : fax ? fax.value.replace(/\D/g, '') : '';
        }
        // Safe access to street fields with fallback
        let streetElements = document.querySelectorAll('input[name^="billing[street"]');
        let street = quote.street ? quote.street : (streetElements[0] ? streetElements[0].value : '');
        let number = quote.number ? quote.number : (streetElements[1] ? streetElements[1].value : '');
        let complement = quote.complement ? quote.complement : quote.neighborhood;
        complement = complement ? complement : (streetElements[2] ? streetElements[2].value : '');
        complement = complement ? complement : 'n/d';
        
        let cityElement = document.querySelector('input[name^="billing[city"]');
        let city = quote.city ? quote.city : (cityElement ? cityElement.value : '');
        let regionCode = quote.regionCode ? quote.regionCode : null;
        
        let postcodeElement = document.querySelector('input[name^="billing[postcode"]');
        let postalCode = quote.postalCode ? quote.postalCode :
            (postcodeElement ? postcodeElement.value.replace(/\D/g, '') : '');

        if (regionCode === null || !isNaN(regionCode)) {
            let regionId = document.querySelector('select[name^="billing[region_id]"]');
            let selectedIndex = regionId.selectedIndex;
            let region = regionId.options[selectedIndex].text;
            regionCode = this.getRegionCode(region);
        }

        let amount = Math.round(quote.totalAmount * 100);
        if (installments > 1) {
            let installmentText = document.getElementById('ricardomartins_pagbank_cc_cc_installments').selectedOptions[0].text;
            let totalValuePattern = /\(Total R\$ ([\d\.,]+)\)/;
            let match = installmentText.match(totalValuePattern);

            if (match) {
                let totalValue = match[1];
                totalValue = totalValue.replace(',', '.');
                totalValue = parseInt(parseFloat(totalValue.toString()).toFixed(2) * 100);
                amount = totalValue;
            }
        }

        street = this.sanitizeAddress(street)
        number = this.sanitizeAddress(number)
        complement = this.sanitizeAddress(complement)
        city = this.sanitizeAddress(city)

        const request = {
            data: {
                customer: {
                    name: name,
                    email: email,
                    phones: [
                        {
                            country: '55',
                            area: phone.substring(0, 2),
                            number: phone.substring(2),
                            type: 'MOBILE'
                        }
                    ]
                },
                paymentMethod: {
                    type: 'CREDIT_CARD',
                    installments: installments,
                    card: {
                        number: ccNumber,
                        expMonth: expMonth,
                        expYear: expYear,
                        holder: {
                            name: holderName
                        }
                    }
                },
                amount: {
                    value: amount,
                    currency: 'BRL'
                },
                billingAddress: {
                    street: street,
                    number: number,
                    complement: complement,
                    regionCode: regionCode,
                    country: 'BRA',
                    city: city,
                    postalCode: postalCode
                },
                dataOnly: false
            }
        }

        await PagSeguro.authenticate3DS(request).then(result => {
            switch (result.status) {
                case 'CHANGE_PAYMENT_METHOD':
                    // The user must change the payment method used
                    alert('Pagamento negado pelo PagBank. Escolha outro método de pagamento ou cartão.');
                    this.enablePlaceOrderButton();
                    this.disablePageLoader();
                    return false;
                case 'AUTH_FLOW_COMPLETED':
                    //O processo de autenticação foi realizado com sucesso, dessa forma foi gerado um id do 3DS que poderá ter o resultado igual a Autenticado ou Não Autenticado.
                    if (result.authenticationStatus === 'AUTHENTICATED') {
                        //O cliente foi autenticado com sucesso, dessa forma o pagamento foi autorizado.
                        card3dsInput.value = result.id;
                        console.debug('PagBank: 3DS Autenticado ou Sem desafio');
                        this.enablePlaceOrderButton();
                        this.disablePageLoader();
                        RMPagBankObj.proceedCheckout = true;
                        return true;
                    }
                    alert('Autenticação 3D falhou. Tente novamente.');
                    this.enablePlaceOrderButton();
                    this.disablePageLoader();
                    return false;
                case 'AUTH_NOT_SUPPORTED':
                    //A autenticação 3DS não ocorreu, isso pode ter ocorrido por falhas na comunicação com emissor ou bandeira, ou algum controle que não possibilitou a geração do 3DS id, essa transação não terá um retorno de status de autenticação e seguirá como uma transação sem 3DS.
                    //O cliente pode seguir adiante sem 3Ds (exceto débito)
                    if (this.config.cc_3ds_allow_continue) {
                        console.debug('PagBank: 3DS não suportado pelo cartão. Continuando sem 3DS.');
                        RMPagBankObj.proceedCheckout = true;
                        this.enablePlaceOrderButton();
                        this.disablePageLoader();
                        return true;
                    }
                    alert('Seu cartão não suporta autenticação 3D. Escolha outro método de pagamento ou cartão.');
                    this.enablePlaceOrderButton();
                    this.disablePageLoader();
                    return false;
                case 'REQUIRE_CHALLENGE':
                    //É um status intermediário que é retornando em casos que o banco emissor solicita desafios, é importante para identificar que o desafio deve ser exibido.
                    console.debug('PagBank: REQUIRE_CHALLENGE - O desafio está sendo exibido pelo banco.');
                    this.enablePlaceOrderButton();
                    this.disablePageLoader();
                    break;
            }
        }).catch((err) => {
            if (err instanceof PagSeguro.PagSeguroError) {
                console.error(err);
                console.debug('PagBank: ' + err.detail);
                let errMsgs = err.detail.errorMessages.map(error => RMPagBankObj.pagBankParseErrorMessage(error)).join('\n');
                alert('Falha na requisição de autenticação 3D.\n' + errMsgs);
                this.enablePlaceOrderButton();
                this.disablePageLoader();
                return false;
            }
        });
    }

    pagBankParseErrorMessage(errorMessage) {
        const codes = {
            '40001': 'Parâmetro obrigatório',
            '40002': 'Parâmetro inválido',
            '40003': 'Parâmetro desconhecido ou não esperado',
            '40004': 'Limite de uso da API excedido',
            '40005': 'Método não permitido',
        };

        const descriptions = {
            "must match the regex: ^\\p{L}+['.-]?(?:\\s+\\p{L}+['.-]?)+$": 'parece inválido ou fora do padrão permitido',
            'cannot be blank': 'não pode estar em branco',
            'size must be between 8 and 9': 'deve ter entre 8 e 9 caracteres',
            'must be numeric': 'deve ser numérico',
            'must be greater than or equal to 100': 'deve ser maior ou igual a 100',
            'must be between 1 and 24': 'deve ser entre 1 e 24',
            'only ISO 3166-1 alpha-3 values are accepted': 'deve ser um código ISO 3166-1 alpha-3',
            'either paymentMethod.card.id or paymentMethod.card.encrypted should be informed': 'deve ser informado o cartão de crédito criptografado ou o id do cartão',
            'must be an integer number': 'deve ser um número inteiro',
            'card holder name must contain a first and last name': 'o nome do titular do cartão deve conter um primeiro e último nome',
            'must be a well-formed email address': 'deve ser um endereço de e-mail válido',
        };

        const parameters = {
            'amount.value': 'valor do pedido',
            'customer.name': 'nome do cliente',
            'customer.phones[0].number': 'número de telefone do cliente',
            'customer.phones[0].area': 'DDD do telefone do cliente',
            'billingAddress.complement': 'complemento/bairro do endereço de cobrança',
            'paymentMethod.installments': 'parcelas',
            'billingAddress.country': 'país de cobrança',
            'paymentMethod.card': 'cartão de crédito',
            'paymentMethod.card.encrypted': 'cartão de crédito criptografado',
            'customer.email': 'e-mail',
        };

        // Get the code, description, and parameterName from the errorMessage object
        const { code, description, parameterName } = errorMessage;

        // Look up the translations
        const codeTranslation = codes[code] || code;
        const descriptionTranslation = descriptions[description] || description;
        const parameterTranslation = parameters[parameterName] || parameterName;

        // Concatenate the translations into a single string
        return `${codeTranslation}: ${parameterTranslation} - ${descriptionTranslation}`;
    }

    disablePlaceOrderButton() {
        if (RMPagBankObj.config.placeorder_button) {
            let placeOrderButton = document.querySelector(RMPagBankObj.config.placeorder_button);
            if (placeOrderButton) {
                let loaderDiv = document.createElement('div');
                loaderDiv.id = 'pagbank-loader';
                placeOrderButton.parentNode.insertBefore(loaderDiv, placeOrderButton.nextSibling);

                loaderDiv.style.background = `#000000a1 url('${RMPagBankObj.config.loader_url}') no-repeat center`;
                loaderDiv.style.height = placeOrderButton.offsetHeight + 'px';
                loaderDiv.style.width = placeOrderButton.offsetWidth + 'px';
                loaderDiv.style.left = placeOrderButton.offsetLeft + 'px';
                loaderDiv.style.zIndex = 99;
                loaderDiv.style.opacity = .5;
                loaderDiv.style.position = 'absolute';
                loaderDiv.style.top = placeOrderButton.offsetTop + 'px';
                return;
            }

            if (RMPagBankObj.config.debug) {
                console.error('PagBank: Botão configurado não encontrado (' + RMPagBankObj.config.placeorder_button + '). Verifique as configurações do módulo.');
            }
        }
    }

    enablePlaceOrderButton() {
        let element = document.getElementById('pagbank-loader');
        if (!element) {
            return;
        }

        if (RMPagBankObj.config.placeorder_button && document.querySelector(RMPagBankObj.config.placeorder_button)) {
            element.remove();
        }
    }

    enablePageLoader() {
        let overlay = document.createElement("div");
        overlay.id = 'pagbank-page-loader-overlay';

        let spinnerContainer = document.createElement("div");
        spinnerContainer.id = 'pagbank-page-loader-container';
        let spinner = document.createElement("div");
        spinner.id = 'pagbank-page-loader';
        let spinnerText = document.createElement("p");
        spinnerText.innerHTML = 'Aguarde...';
        spinnerContainer.appendChild(spinner);
        spinnerContainer.appendChild(spinnerText);
        overlay.appendChild(spinnerContainer);
        document.body.appendChild(overlay);
    }

    disablePageLoader() {
        let element = document.getElementById("pagbank-page-loader-overlay");
        if (element) {
            element.remove();
        }
    }

    getQuoteData() {
        let endpoint = this.config.quotedata_endpoint;
        return new Promise((resolve, reject) => {
            fetch(endpoint)
                .then(response => response.json())
                .then(data => resolve(data))
                .catch(error => reject(error));
        });
    }

    getCardTypes(cardNumber) {
       const typesPagBank = [
            {
                title: 'MasterCard',
                type: 'mastercard',
                pattern: '^(?:5[1-5][0-9]{2}|222[1-9]|22[3-9][0-9]|2[3-6][0-9]{2}|27[01][0-9]|2720)[0-9]{12}$',
                gaps: [4, 8, 12],
                lengths: [16],
                code: {
                    name: 'CVC',
                    size: 3
                }
            },
            {
                title: 'Visa',
                type: 'visa',
                pattern: '^4\\d*$',
                gaps: [4, 8, 12],
                lengths: [16, 18, 19],
                code: {
                    name: 'CVV',
                    size: 3
                }
            },
            {
                title: 'American Express',
                type: 'amex',
                pattern: '^3([47]\\d*)?$',
                isAmex: true,
                gaps: [4, 10],
                lengths: [15],
                code: {
                    name: 'CID',
                    size: 4
                }
            },
            {
                title: 'Diners',
                type: 'dinnersclub',
                pattern: '^(3(0[0-5]|095|6|[8-9]))\\d*$',
                gaps: [4, 10],
                lengths: [14, 16, 17, 18, 19],
                code: {
                    name: 'CVV',
                    size: 3
                }
            },
            {
                title: 'Elo',
                type: 'elo',
                pattern: '^((451416)|(509091)|(636368)|(636297)|(504175)|(438935)|(40117[8-9])|(45763[1-2])|' +
                    '(457393)|(431274)|(50990[0-2])|(5099[7-9][0-9])|(50996[4-9])|(509[1-8][0-9][0-9])|' +
                    '(5090(0[0-2]|0[4-9]|1[2-9]|[24589][0-9]|3[1-9]|6[0-46-9]|7[0-24-9]))|' +
                    '(5067(0[0-24-8]|1[0-24-9]|2[014-9]|3[0-379]|4[0-9]|5[0-3]|6[0-5]|7[0-8]))|' +
                    '(6504(0[5-9]|1[0-9]|2[0-9]|3[0-9]))|' +
                    '(6504(8[5-9]|9[0-9])|6505(0[0-9]|1[0-9]|2[0-9]|3[0-8]))|' +
                    '(6505(4[1-9]|5[0-9]|6[0-9]|7[0-9]|8[0-9]|9[0-8]))|' +
                    '(6507(0[0-9]|1[0-8]))|(65072[0-7])|(6509(0[1-9]|1[0-9]|20))|' +
                    '(6516(5[2-9]|6[0-9]|7[0-9]))|(6550(0[0-9]|1[0-9]))|' +
                    '(6550(2[1-9]|3[0-9]|4[0-9]|5[0-8])))\\d*$',
                gaps: [4, 8, 12],
                lengths: [16],
                code: {
                    name: 'CVC',
                    size: 3
                }
            },
            {
                title: 'Hipercard',
                type: 'hipercard',
                pattern: '^((606282)|(637095)|(637568)|(637599)|(637609)|(637612))\\d*$',
                gaps: [4, 8, 12],
                lengths: [13, 16],
                code: {
                    name: 'CVC',
                    size: 3
                }
            },
            {
                title: 'Aura',
                type: 'aura',
                pattern: '^5078\\d*$',
                gaps: [4, 8, 12],
                lengths: [19],
                code: {
                    name: 'CVC',
                    size: 3
                }
            }];

        //remove spaces
        cardNumber = cardNumber.replace(/\s/g, '');
        let result = [];

        if (!cardNumber) {
            return result;
        }

        for (let i = 0; i < typesPagBank.length; i++) {
            let value = typesPagBank[i];
            if (new RegExp(value.pattern).test(cardNumber)) {
                result.push(JSON.parse(JSON.stringify(value)));
            }
        }

        return result.slice(-1);
    }

    getRegionCode(region) {
        const regionCodes = {
            "AC": "ACRE",
            "AL": "ALAGOAS",
            "AP": "AMAPA",
            "AM": "AMAZONAS",
            "BA": "BAHIA",
            "CE": "CEARA",
            "DF": "DISTRITO FEDERAL",
            "ES": "ESPIRITO SANTO",
            "GO": "GOIAS",
            "MA": "MARANHÃO",
            "MT": "MATO GROSSO",
            "MS": "MATO GROSSO DO SUL",
            "MG": "MINAS GERAIS",
            "PA": "PARÁ",
            "PB": "PARAÍBA",
            "PR": "PARANÁ",
            "PE": "PERNAMBUCO",
            "PI": "PIAUÍ",
            "RJ": "RIO DE JANEIRO",
            "RN": "RIO GRANDE DO NORTE",
            "RS": "RIO GRANDE DO SUL",
            "RO": "RONDÔNIA",
            "RR": "RORAIMA",
            "SC": "SANTA CATARINA",
            "SP": "SÃO PAULO",
            "SE": "SERGIPE",
            "TO": "TOCANTINS"
        }

        region = region.toUpperCase();
        return Object.keys(regionCodes).find(key => regionCodes[key] === region);
    }

    sanitizeAddress(value) {
        return value ? value.replace(/\s+/g, ' ').trim() : '';
    }
}

window.RMPagBank = RMPagBank