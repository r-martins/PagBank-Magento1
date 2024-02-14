RMPagBank = Class.create({
    initialize: function (config) {
        this.config = config;
        this.addCardFieldsObserver(this);
        this.getInstallments();
    },

    addCardFieldsObserver: function(obj){
        try {
            let holderElem = $$('#ricardomartins_pagbank_cc_cc_owner').first();
            let numberElem = $$('#ricardomartins_pagbank_cc_cc_number').first();
            let expElem = $$('#ricardomartins_pagbank_cc_cc_exp').first();
            let cvcElem = $$('#ricardomartins_pagbank_cc_cc_cvc').first();

            Element.observe(numberElem,'change',function(e){obj.encryptCard();});
            Element.observe(numberElem,'change',function(e){obj.updateInstallments();});
            Element.observe(holderElem,'change',function(e){obj.encryptCard();});
            Element.observe(expElem,'change',function(e){obj.encryptCard();});
            Element.observe(cvcElem,'change',function(e){obj.encryptCard();});
        } catch(e) {
            console.error('Não foi possível adicionar observevação aos cartões. ' + e.message);
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
            card = PagSeguro.encryptCard({
                publicKey: this.config.publicKey,
                holder: holderName,
                number: ccNumber,
                expMonth: expMonth,
                expYear: expYear,
                securityCode: ccCvc,
                success: function (data) {
                    debugger;
                    console.log('Card encrypted successfully');
                },
                error: function (data) {
                    debugger;
                    console.error('Error encrypting card');
                    console.error(data);
                },
                complete: function (data) {
                    this.enablePlaceOrderButton();
                }
            });
        } catch (e) {
            alert("Erro ao criptografar o cartão.\nVerifique se os dados digitados estão corretos.");
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
            return false;
        }

        numberEncryptedInput.val(card.encryptedCard);
        return true;
    },
    disablePlaceOrderButton: function(){
        debugger;
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
                    if (response[i].interest_free === false)
                        additionalText = ' (Total R$ ' + totalAmount + ')';

                    option.text(text + additionalText);
                    select.append(option);
                }
            },
            error: (response)=>{
                alert('Error getting installments. Please try again.');
            }
        });
    }
});

