jQuery(document).ready(function($) {

    //region Encrypt card method
    /**
     * Encrypts the card and sets the encrypted card in the hidden input
     * @returns {boolean}
     */
    let encryptCard = function () {
        //inputs
        let holderInput = $('#ricardomartins_pagbank_cc_cc_owner');
        let numberInput = $('#ricardomartins_pagbank_cc_cc_number');
        let expInput = $('#ricardomartins_pagbank_cc_cc_exp');
        let cvcInput = $('#ricardomartins_pagbank_cc_cc_cvc');
        let numberEncryptedInput = $('#ricardomartins_pagbank_cc_cc_number_encrypted');

        let holderName, card, ccNumber, ccCvc, expMonth, expYear;

        //replace trim and remove duplicated spaces from input values
        holderName = holderInput.val().trim().replace(/\s+/g, ' ');
        ccNumber = numberInput.val().replace(/\s/g, '');
        ccCvc = cvcInput.val().replace(/\s/g, '');
        expMonth = expInput.val().split('/')[0].replace(/\s/g, '');
        expYear = '20' + expInput.val().split('/')[1].slice(-2).replace(/\s/g, '');

        try {
            card = PagSeguro.encryptCard({
                publicKey: checkoutConfig.ricardomartins_pagbank.publicKey,
                holder: holderName,
                number: ccNumber,
                expMonth: expMonth,
                expYear: expYear,
                securityCode: ccCvc,
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
    }
    //endregion

    /* MAGENTO CHECKOUT METHODS SAVING */

    // if (typeof FireCheckout !== "undefined") {
    //     // FireCheckout customization
    //     FireCheckout.prototype._save = FireCheckout.prototype.save;
    //     FireCheckout.prototype.save = function () {
    //         if (payment.currentMethod == 'ricardomartins_pagbank_cc') {
    //             if (encryptCard() === false){
    //                 return false;
    //             }
    //
    //         }
    //         this._save();
    //     }
    // } else {
    //     // default magento checkout
    // }

    // Default Magento Checkout
    Payment.prototype._save = Payment.prototype.save;
    Payment.prototype.save = function () {
        let validator = new Validation(this.form);
        if (this.validate() && validator.validate()) {
            if (payment.currentMethod == 'ricardomartins_pagbank_cc') {
                if (encryptCard() === false) {
                    return false;
                }
            }
            this._save();
        }
    };

});

jQuery(document).on('keyup change paste', '#ricardomartins_pagbank_cc_cc_number', (e)=>{
    let cardNumber = jQuery(e.target).val();
    let ccBin = cardNumber.replace(/\s/g, '').substring(0, 6);
    let ccBinInput = jQuery('#ricardomartins_pagbank_cc_cc_bin');
    if (ccBin !== window.ps_cc_bin && ccBin.length === 6) {
        window.ps_cc_bin = ccBin;
        jQuery(document.body).trigger('update_installments');
        ccBinInput.val(ccBin);
    }
});

jQuery(document.body).on('update_installments', ()=>{
    //if success, update the installments select with the response
    //if error, show error message
    let ccBin = typeof window.ps_cc_bin === 'undefined' || window.ps_cc_bin.replace(/[^0-9]/g, '').length < 6 ? '555566' : window.ps_cc_bin;

    let total = quoteBaseGrandTotal;

    //convert to cents
    let orderTotal = parseFloat(total).toFixed(2) * 100;
    if (orderTotal < 100){
        return;
    }

    jQuery.ajax({
        url: checkoutConfig.ricardomartins_pagbank.installmentsEndpoint,
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
});
