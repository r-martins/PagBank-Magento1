RMPagBank = Class.create({
    initialize: function (config, pagseguro_connect_3d_session) {
        this.config = config;
        this.addCardFieldsObserver(this);
        this.getInstallments();

        if (pagseguro_connect_3d_session !== '' && this.config.enabled_3ds) {
            jQuery('#ricardomartins_pagbank_cc_cc_has_session').val(1);
        }

        if (this.config.enabled_3ds && pagseguro_connect_3d_session !== '') {
            this.setUp3DS(pagseguro_connect_3d_session);
        }
    },

    addCardFieldsObserver: function(obj){
        try {
            let holderElem = $$('#ricardomartins_pagbank_cc_cc_owner').first();
            let numberElem = $$('#ricardomartins_pagbank_cc_cc_number').first();
            let expElem = $$('#ricardomartins_pagbank_cc_cc_exp').first();
            let cvcElem = $$('#ricardomartins_pagbank_cc_cc_cvc').first();

            Element.observe(numberElem,'change',function(e){obj.encryptCard();});
            Element.observe(numberElem,'change',function(e){obj.updateInstallments();});
            Element.observe(numberElem,'change',function(e){obj.setBrand();});
            Element.observe(holderElem,'change',function(e){obj.encryptCard();});
            Element.observe(expElem,'change',function(e){obj.encryptCard();});
            Element.observe(cvcElem,'change',function(e){obj.encryptCard();});
        } catch(e) {
            console.error('Não foi possível adicionar observevação aos cartões. ' + e.message);
        }

    },
    setBrand: function () {
        let brandInput = jQuery('#ricardomartins_pagbank_cc_cc_brand');
        let flag = this.config.flag_size;
        let numberInput = jQuery('#ricardomartins_pagbank_cc_cc_number');
        let urlPrefix = 'https://stc.pagseguro.uol.com.br/';
        if(this.config.stc_mirror){
            urlPrefix = 'https://stcpagseguro.ricardomartins.net.br/';
        }
        let src = urlPrefix + 'public/img/payment-methods-flags/{flag}/{brand}.png';
        let style = '';

        src = src.replace('{flag}', flag);

        if (flag !== '') {
            style =  'background-image: url(' + src + ');' +
                'background-repeat: no-repeat;' +
                'background-position: calc(100% - 5px) center;' +
                'background-size: auto calc(100% - 6px);';
        }

        let ccNumber = numberInput.val();
        ccNumber = ccNumber.replace(/\s/g, '');

        let cardTypes = RMPagBankObj.getCardTypes(ccNumber);
        if (cardTypes.length > 0) {
            style = style.replace(/{brand}/g, cardTypes[0].type);
            numberInput.attr('style', style);
            brandInput.val(cardTypes[0].type);
            console.log("Bandeira armazenada com sucesso");
        } else {
            numberInput.attr('style', '');
            console.log("Bandeira não encontrada");
        }
    },
    encryptCard: function () {
        //inputs
        let holderInput = jQuery('#ricardomartins_pagbank_cc_cc_owner');
        let numberInput = jQuery('#ricardomartins_pagbank_cc_cc_number');
        let expInput = jQuery('#ricardomartins_pagbank_cc_cc_exp');
        let cvcInput = jQuery('#ricardomartins_pagbank_cc_cc_cvc');
        let numberEncryptedInput = jQuery('#ricardomartins_pagbank_cc_cc_number_encrypted');

        //get input values
        holderInput = holderInput.val();
        numberInput = numberInput.val();
        cvcInput = cvcInput.val();
        expInput = expInput.val();

        if (holderInput === '' || numberInput === '' || cvcInput === '' || expInput === '') {
            return false;
        }

        let holderName, card, ccNumber, ccCvc, expMonth, expYear;

        //replace trim and remove duplicated spaces from input values
        holderName = holderInput.trim().replace(/\s+/g, ' ');
        ccNumber = numberInput.replace(/\s/g, '');
        ccCvc = cvcInput.replace(/\s/g, '');
        expMonth = expInput.split('/')[0].replace(/\s/g, '');
        expYear = '20' + expInput.split('/')[1].slice(-2).replace(/\s/g, '');

        this.disablePlaceOrderButton();
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
                    console.log('Card encrypted successfully');
                },
                error: function (data) {
                    console.error('Error encrypting card');
                    console.error(data);
                }
            });
        } catch (e) {
            alert("Erro ao criptografar o cartão.\nVerifique se os dados digitados estão corretos.");
            if(RMPagBankObj.config.debug){
                console.error('Erro ao criptografar o cartão.\nVerifique se os dados digitados estão corretos.', e);
            }
            return false;
        }

        if (card.hasErrors) {
            let errorCodes = [
                {code: 'INVALID_NUMBER', message: 'Número do cartão inválido'},
                {
                    code: 'INVALID_SECURITY_CODE',
                    message: 'CVV Inválido. Você deve passar um valor com 3, 4 ou mais dígitos.'
                },
                {
                    code: 'INVALID_EXPIRATION_MONTH',
                    message: 'Mês de expiração incorreto. Passe um valor entre 1 e 12.'
                },
                {code: 'INVALID_EXPIRATION_YEAR', message: 'Ano de expiração inválido.'},
                {code: 'INVALID_PUBLIC_KEY', message: 'Chave Pública inválida.'},
                {code: 'INVALID_HOLDER', message: 'Nome do titular do cartão inválido.'},
            ]
            //extract error message
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

            if(RMPagBankObj.config.debug){
                console.error('Erro ao criptografar cartão.\n' + error);
            }
            return false;
        }

        numberEncryptedInput.val(card.encryptedCard);

        if (this.config.enabled_3ds) {
            this.authenticate3DS();
        }

        this.enablePlaceOrderButton();
        return true;
    },
    disablePlaceOrderButton: function(){
        if (RMPagBankObj.config.placeorder_button) {
            if(typeof $$(RMPagBankObj.config.placeorder_button).first() != 'undefined'){
                $$(RMPagBankObj.config.placeorder_button).first().up().insert({
                    'after': new Element('div',{
                        'id': 'pagbank-loader'
                    })
                });

                $$('#pagbank-loader').first().setStyle({
                    'background': '#000000a1 url(\'' + RMPagBankObj.config.loader_url + '\') no-repeat center',
                    'height': $$(RMPagBankObj.config.placeorder_button).first().getStyle('height'),
                    'width': $$(RMPagBankObj.config.placeorder_button).first().getStyle('width'),
                    'left': document.querySelector(RMPagBankObj.config.placeorder_button).offsetLeft + 'px',
                    'z-index': 99,
                    'opacity': .5,
                    'position': 'absolute',
                    'top': document.querySelector(RMPagBankObj.config.placeorder_button).offsetTop + 'px'
                });
                return;
            }

            if(RMPagBankObj.config.debug){
                console.error('PagBank: Botão configurado não encontrado (' + RMPagBankObj.config.placeorder_button + '). Verifique as configurações do módulo.');
            }
        }
    },
    enablePlaceOrderButton: function(){
        if(RMPagBankObj.config.placeorder_button && typeof $$(RMPagBankObj.config.placeorder_button).first() != 'undefined'){
            $$('#pagbank-loader').first().remove();
        }
    },
    updateInstallments: function() {
        let cardNumber = jQuery('#ricardomartins_pagbank_cc_cc_number').val();
        let ccBin = cardNumber.replace(/\s/g, '').substring(0, 6);
        let ccBinInput = jQuery('#ricardomartins_pagbank_cc_cc_bin');
        if (ccBin !== window.pb_cc_bin && ccBin.length === 6) {
            window.pb_cc_bin = ccBin;
            this.getInstallments();
            ccBinInput.val(ccBin);
        }
    },
    getInstallments: function() {
        //if success, update the installments select with the response
        //if error, show error message
        let ccBin = typeof window.pb_cc_bin === 'undefined' || window.pb_cc_bin.replace(/[^0-9]/g, '').length < 6 ? '555566' : window.pb_cc_bin;

        jQuery.ajax({
            url: this.config.installments_endpoint,
            method: 'POST',
            data: {
                cc_bin: ccBin
            },
            success: (response)=>{
                if(RMPagBankObj.config.debug){
                    console.log('Installments response:', response);
                }

                response = JSON.parse(response);

                let select = jQuery('#ricardomartins_pagbank_cc_cc_installments');
                select.empty();

                for (let i = 0; i < response.length; i++) {
                    let option = jQuery('<option></option>');
                    option.attr('value', response[i].installments);

                    let installmentValue = parseInt(response[i].installment_value) / 100;
                    let installmentAmount = installmentValue.toFixed(2).toString().replace('.', ',');
                    let text = response[i].installments + 'x de R$ ' + installmentAmount;

                    let totalAmount = parseInt(response[i].amount.value) / 100;
                    totalAmount = totalAmount.toFixed(2).toString().replace('.', ',');

                    let additionalText = ' (sem juros)';
                    if (response[i].interest_free === false) {
                        additionalText = ' (Total R$ ' + totalAmount + ')';
                    }

                    option.text(text + additionalText);
                    select.append(option);
                }
            },
            error: (response)=>{
                alert('Error getting installments. Please try again.');
                if(RMPagBankObj.config.debug){
                    console.error('Error getting installments. Please try again.', response);
                }
            }
        });
    },
    setUp3DS: function(pagseguro_connect_3d_session) {
        //region 3ds authentication method
        PagSeguro.setUp({
            session: pagseguro_connect_3d_session,
            env: this.config.environment,
        });
    },
    authenticate3DS: async function() {
        //inputs
        let holderInput = jQuery('#ricardomartins_pagbank_cc_cc_owner');
        let numberInput = jQuery('#ricardomartins_pagbank_cc_cc_number');
        let expInput = jQuery('#ricardomartins_pagbank_cc_cc_exp');
        let installmentsInput = jQuery('#ricardomartins_pagbank_cc_cc_installments');
        let card3dsInput = jQuery('#ricardomartins_pagbank_cc_cc_3ds_id');

        //get input values
        holderInput = holderInput.val();
        numberInput = numberInput.val();
        expInput = expInput.val();
        installmentsInput = installmentsInput.val();

        if (holderInput === '' || numberInput === '' || expInput === '') {
            return false;
        }

        this.disablePlaceOrderButton();

        const quote = await this.getQuoteData();

        let holderName, ccNumber, expMonth, expYear, installments;

        //replace trim and remove duplicated spaces from input values
        holderName = holderInput.trim().replace(/\s+/g, ' ');
        ccNumber = numberInput.replace(/\s/g, '');
        installments = installmentsInput.replace(/\s/g, '');
        expMonth = expInput.split('/')[0].replace(/\s/g, '');
        expYear = '20' + expInput.split('/')[1].slice(-2).replace(/\s/g, '');

        const request = {
            data: {
                customer: {
                    name: quote.customerName,
                    email: quote.email,
                    phones: [
                        {
                            country: '55',
                            area: quote.phone.substring(0, 2),
                            number: quote.phone.substring(2),
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
                    value: quote.totalAmount * 100,
                    currency: 'BRL'
                },
                billingAddress: {
                    street: quote.street,
                    number: quote.number,
                    complement: quote.complement,
                    regionCode: quote.regionCode,
                    country: 'BRA',
                    city: quote.city,
                    postalCode: quote.postalCode
                },
                dataOnly: false
            }
        }

        PagSeguro.authenticate3DS(request).then( result => {
            switch (result.status) {
                case 'CHANGE_PAYMENT_METHOD':
                    // The user must change the payment method used
                    alert('Pagamento negado pelo PagBank. Escolha outro método de pagamento ou cartão.');
                    return false;
                case 'AUTH_FLOW_COMPLETED':
                    //O processo de autenticação foi realizado com sucesso, dessa forma foi gerado um id do 3DS que poderá ter o resultado igual a Autenticado ou Não Autenticado.
                    if (result.authenticationStatus === 'AUTHENTICATED') {
                        //O cliente foi autenticado com sucesso, dessa forma o pagamento foi autorizado.
                        card3dsInput.val(result.id);
                        console.debug('PagBank: 3DS Autenticado ou Sem desafio');
                        this.enablePlaceOrderButton();
                        return true;
                    }
                    alert('Autenticação 3D falhou. Tente novamente.');
                    return false;
                case 'AUTH_NOT_SUPPORTED':
                    //A autenticação 3DS não ocorreu, isso pode ter ocorrido por falhas na comunicação com emissor ou bandeira, ou algum controle que não possibilitou a geração do 3DS id, essa transação não terá um retorno de status de autenticação e seguirá como uma transação sem 3DS.
                    //O cliente pode seguir adiante sem 3Ds (exceto débito)
                    if (this.config.cc_3ds_allow_continue) {
                        console.debug('PagBank: 3DS não suportado pelo cartão. Continuando sem 3DS.');
                        this.enablePlaceOrderButton();
                        return true;
                    }
                    alert('Seu cartão não suporta autenticação 3D. Escolha outro método de pagamento ou cartão.');
                    return false;
                case 'REQUIRE_CHALLENGE':
                    //É um status intermediário que é retornando em casos que o banco emissor solicita desafios, é importante para identificar que o desafio deve ser exibido.
                    console.debug('PagBank: REQUIRE_CHALLENGE - O desafio está sendo exibido pelo banco.');
                    this.enablePlaceOrderButton();
                    break;
            }
        }).catch((err) => {
            if(err instanceof PagSeguro.PagSeguroError ) {
                console.error(err);
                console.debug('PagBank: ' + err.detail);
                let errMsgs = err.detail.errorMessages.map(error => pagBankParseErrorMessage(error)).join('\n');
                alert('Falha na requisição de autenticação 3D.\n' + errMsgs);
                return false;
            }
        });
    },
    getQuoteData: async function() {
        return await jQuery.ajax({
            url: this.config.quotedata_endpoint,
            method: 'GET'
        });
    },
    getCardTypes: function (cardNumber) {
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

        if (jQuery.isEmptyObject(cardNumber)) {
            return result;
        }

        for (let i = 0; i < typesPagBank.length; i++) {
            let value = typesPagBank[i];
            if (new RegExp(value.pattern).test(cardNumber)) {
                result.push(jQuery.extend(true, {}, value));
            }
        }

        return result.slice(-1);
    }
});

