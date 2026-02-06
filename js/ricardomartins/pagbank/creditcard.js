class RMPagBank {
    constructor(config, pagseguro_connect_3d_session) {
        console.log('PagBank: Inicializando módulo de cartão de crédito');
        this.config = config;
        this.formElementAndSubmit = false;
        this.addCardFieldsObserver();
        this.getInstallments();

        if (pagseguro_connect_3d_session !== '' && this.config.enabled_3ds) {
            const sessionInput = document.getElementById('ricardomartins_pagbank_cc_cc_has_session');
            if (sessionInput) {
                sessionInput.value = 1;
            }
        }

        if (this.config.enabled_3ds && pagseguro_connect_3d_session !== '') {
            this.setUp3DS(pagseguro_connect_3d_session);
        }

        this.placeOrderEvent();
    }

    placeOrderEvent() {
        console.log('PagBank: placeOrderEvent() chamado');
        const methodForm = document.querySelectorAll('#payment_form_ricardomartins_pagbank_cc');
        if (!methodForm.length) {
            // Form not found yet, try again after a short delay (max 5 attempts)
            if (!this._placeOrderRetries) {
                this._placeOrderRetries = 0;
            }
            if (this._placeOrderRetries < 5) {
                this._placeOrderRetries++;
                console.log(`PagBank: Formulário não encontrado. Tentativa ${this._placeOrderRetries}/5...`);
                setTimeout(() => {
                    this.placeOrderEvent();
                }, 500);
                return;
            }
            console.log('PagBank: Não há métodos de pagamento habilitados em exibição. Execução abortada.');
            return;
        }
        console.log('PagBank: Formulário encontrado. Procurando botões...');

        // Removed mutationAttributesCallback and observer - they were causing performance issues
        // by doing expensive DOM operations (set Element.className) on every mutation.
        // Not needed since we're no longer cloning buttons.

        let form = methodForm[0].closest('form');
        if (!form) {
            console.warn('PagBank: Formulário não encontrado para anexar eventos do botão.');
            return;
        }

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
        buttons.forEach((btnSelector) => {
            let button = document.querySelector(btnSelector);

            if (!button) {
                console.log(`PagBank: Botão não encontrado com seletor: ${btnSelector}`);
                return;
            }
            
            console.log(`PagBank: Botão encontrado com seletor: ${btnSelector}`);
            
            // Check if events are already attached to this button instance
            // Use a data attribute to track if this specific button instance has listeners
            const buttonTimestamp = button.getAttribute('data-pagbank-button-timestamp');
            const storedTimestamp = this._lastButtonTimestamp || null;
            
            console.log(`PagBank: Timestamp do botão: ${buttonTimestamp}, Timestamp armazenado: ${storedTimestamp}, Listeners anexados: ${button.dataset.pagbankButtonListenersAttached}`);
            
            // Check if listeners are already attached to THIS button instance
            // Verify by checking timestamp AND handler reference
            if (storedTimestamp !== null && 
                buttonTimestamp && 
                buttonTimestamp === storedTimestamp && 
                button.dataset.pagbankButtonListenersAttached === 'true' &&
                button._pagbankClickHandler) {
                console.log('PagBank: Eventos do botão já anexados a esta instância. Pulando para evitar duplicação.');
                return;
            }
            
            // If stored timestamp is null (forced reset), remove old timestamp and listeners from button
            if (storedTimestamp === null && buttonTimestamp) {
                console.log('PagBank: Removendo timestamp antigo do botão para forçar reaplicação.');
                button.removeAttribute('data-pagbank-button-timestamp');
                delete button.dataset.pagbankButtonListenersAttached;
                
                // Also remove old listeners if they exist
                if (button._pagbankClickHandler) {
                    console.log('PagBank: Removendo listeners antigos do botão (reset forçado).');
                    button.removeEventListener('click', button._pagbankClickHandler, true);
                    button.removeEventListener('click', button._pagbankClickHandler, false);
                    delete button._pagbankClickHandler;
                }
                if (form._pagbankSubmitHandler) {
                    form.removeEventListener('submit', form._pagbankSubmitHandler, true);
                    form.removeEventListener('submit', form._pagbankSubmitHandler, false);
                    delete form._pagbankSubmitHandler;
                }
            }
            
            // Generate or update timestamp for this button instance
            const timestamp = Date.now().toString();
            button.setAttribute('data-pagbank-button-timestamp', timestamp);
            this._lastButtonTimestamp = timestamp;

            let onclickEvent = button.getAttribute('onclick');
            // Store onclick but don't remove it yet - we'll wrap it
            const storedOnclickEvent = onclickEvent;
            const storedBtnSelector = btnSelector; // Store selector for later use
            
            // Don't clone the button - attach listeners directly to the existing button
            // Cloning removes event listeners and the button might be replaced anyway

            let validateAndPreventDefault = function (event) {
                // Prevent duplicate execution
                if (event._pagbankProcessed) {
                    console.log('PagBank: Evento já processado, ignorando...');
                    return;
                }
                
                // Get current button reference (may have changed after DOM updates)
                let currentButton = document.querySelector(storedBtnSelector);
                
                // If this is a programmatic click (after restoring onclick), let it proceed normally
                if (event.isTrusted === false && currentButton && currentButton.dataset.pagbankRestoringOnclick === 'true') {
                    console.log('PagBank: Click programático após restaurar onclick, permitindo execução normal.');
                    return true; // Let the onclick handler execute normally
                }
                
                console.log('PagBank: Botão de finalizar compra clicado ou formulário submetido.');
                console.log('PagBank: Event type:', event.type, 'Target:', event.target, 'Current target:', event.currentTarget);
                
                // Mark as processed to prevent duplicate execution
                event._pagbankProcessed = true;
                
                // Re-get button reference if not found
                if (!currentButton) {
                    console.warn('PagBank: Botão não encontrado no DOM. Tentando continuar...');
                    // Try to get button from event target
                    if (event.target && (event.target.matches && event.target.matches(storedBtnSelector))) {
                        currentButton = event.target;
                        console.log('PagBank: Botão encontrado via event.target');
                    } else if (event.currentTarget && event.currentTarget.matches && event.currentTarget.matches(storedBtnSelector)) {
                        currentButton = event.currentTarget;
                        console.log('PagBank: Botão encontrado via event.currentTarget');
                    } else {
                        currentButton = button; // Fallback to stored reference
                    }
                }
                
                let paymentMethod = document.querySelector('input[name="payment[method]"]:checked');
                if (!paymentMethod || paymentMethod.value !== 'ricardomartins_pagbank_cc') {
                    console.log('PagBank: Método de pagamento não é PagBank. Continuando checkout normal.');
                    // Restore original onclick and execute it
                    if (storedOnclickEvent) {
                        // Mark that we're restoring onclick to prevent our handler from intercepting
                        currentButton.dataset.pagbankRestoringOnclick = 'true';
                        currentButton.setAttribute('onclick', storedOnclickEvent);
                        setTimeout(() => {
                            currentButton.click();
                            // Remove flag after click
                            setTimeout(() => {
                                delete currentButton.dataset.pagbankRestoringOnclick;
                            }, 100);
                        }, 10);
                    }
                    return true;
                }
                
                console.log('PagBank: Método PagBank detectado. Prevenindo submit padrão.');
                event.preventDefault();
                event.stopImmediatePropagation();
                event.stopPropagation();

                console.debug('PagBank: Iniciando cardActions...');
                RMPagBankObj.cardActions().then((result) => {
                  console.debug('PagBank: cardActions concluído. proceedCheckout:', RMPagBankObj.proceedCheckout);
                  if (RMPagBankObj.proceedCheckout) {
                    console.debug('PagBank: Prosseguindo com checkout...');
                    if (storedOnclickEvent) {
                        // Restore onclick attribute and trigger click (safer than eval)
                        // This properly handles onclick with "return false;" statements
                        // Mark that we're restoring onclick to prevent our handler from intercepting
                        currentButton.dataset.pagbankRestoringOnclick = 'true';
                        currentButton.setAttribute("onclick", storedOnclickEvent);
                        // Use a small delay to ensure onclick is restored before clicking
                        setTimeout(() => {
                            currentButton.click();
                            // Remove flag after click
                            setTimeout(() => {
                                delete currentButton.dataset.pagbankRestoringOnclick;
                            }, 100);
                        }, 10);
                    } else {
                        // If no onclick, the OSC likely uses event listeners.
                        // Mark that we're allowing the click to proceed normally (prevents our handler from intercepting)
                        console.debug('PagBank: Sem onclick inline. Disparando clique programático para acionar handlers do OSC...');
                        
                        // Mark that we're restoring onclick to prevent our handler from intercepting
                        // (even though there's no onclick, this flag prevents our handler from running)
                        currentButton.dataset.pagbankRestoringOnclick = 'true';
                        
                        // Trigger programmatic click - our handler will see the flag and let it pass through
                        setTimeout(() => {
                            currentButton.click();
                            // Remove flag after click
                            setTimeout(() => {
                                delete currentButton.dataset.pagbankRestoringOnclick;
                            }, 100);
                        }, 10);
                    }
                    return true;
                  } else {
                    console.warn('PagBank: proceedCheckout é false. Checkout não prosseguirá.');
                  }
                })
                .catch((error) => {
                  console.error("PagBank: Erro ao executar os eventos do cartão:", error);
                });
                
                return false;
            }

            // Always remove old listeners before adding new ones (prevent duplication)
            if (button._pagbankClickHandler) {
                console.debug('PagBank: Removendo listeners antigos do botão antes de adicionar novos.');
                button.removeEventListener('click', button._pagbankClickHandler, true);
                button.removeEventListener('click', button._pagbankClickHandler, false);
            }
            if (form._pagbankSubmitHandler) {
                form.removeEventListener('submit', form._pagbankSubmitHandler, true);
                form.removeEventListener('submit', form._pagbankSubmitHandler, false);
            }
            
            // Wrap the original onclick if it exists
            if (onclickEvent) {
                // Remove the inline onclick to prevent it from running before our handler
                button.removeAttribute('onclick');
                // Store it so we can call it later if needed
                button._pagbankOriginalOnclick = onclickEvent;
            }
            
            // Store handler reference for cleanup (AFTER defining the function)
            button._pagbankClickHandler = validateAndPreventDefault;
            form._pagbankSubmitHandler = validateAndPreventDefault;
            
            // Use ONLY capture phase to avoid duplication (capture runs before bubble)
            // This ensures our handler runs first without needing both phases
            button.addEventListener('click', validateAndPreventDefault, true);
            form.addEventListener('submit', validateAndPreventDefault, true);
            
            // Removed event delegation to prevent duplication - reapplyObservers will handle button replacement
            
            // Mark as attached
            button.dataset.pagbankButtonListenersAttached = 'true';
            button.dataset.pagbankSelector = btnSelector; // Store selector for verification
            
            // Verify listeners are attached
            const hasListeners = button.onclick !== null || 
                                 (button.getEventListeners && button.getEventListeners('click')?.length > 0);
            
            console.debug(`PagBank: Eventos do botão de finalizar compra anexados com sucesso ao botão: ${btnSelector}`);
            console.debug(`PagBank: Timestamp do botão definido como: ${timestamp}`);
            console.debug(`PagBank: Event listeners anexados em ambas as fases (capture e bubble)`);
            console.debug(`PagBank: Botão verificado - ID: ${button.id || 'sem ID'}, Classes: ${button.className || 'sem classes'}`);
            
            // Test click handler by checking if button still exists and has listeners
            setTimeout(() => {
                const testButton = document.querySelector(btnSelector);
                if (testButton) {
                    if (testButton.dataset.pagbankButtonListenersAttached === 'true') {
                        console.debug(`PagBank: Botão ainda presente no DOM após anexação: ${btnSelector}`);
                        // Verify listeners are actually attached by checking if button has our data attribute
                        console.debug(`PagBank: Verificação - Botão tem timestamp: ${testButton.getAttribute('data-pagbank-button-timestamp') || 'não'}`);
                    } else {
                        console.warn(`PagBank: Botão encontrado mas sem flag de listeners anexados: ${btnSelector}`);
                        console.warn(`PagBank: Botão foi substituído após anexação! Reaplicando...`);
                        // Button was replaced, reapply
                        if (typeof RMPagBankObj !== "undefined" && RMPagBankObj) {
                            RMPagBankObj._lastButtonTimestamp = null;
                            RMPagBankObj._placeOrderRetries = 0;
                            setTimeout(() => {
                                RMPagBankObj.placeOrderEvent();
                            }, 100);
                        }
                    }
                } else {
                    console.warn(`PagBank: Botão não encontrado no DOM após anexação: ${btnSelector}`);
                }
            }, 500);

            eventAlreadyAttached = true;
        });

        if (!eventAlreadyAttached) {
            console.warn('PagBank: Nenhum botão de finalizar compra encontrado. Verifique a configuração placeorder_button.');
            console.warn('PagBank: Botões procurados:', buttons);
        } else {
            console.log('PagBank: placeOrderEvent() concluído com sucesso.');
        }
    }

    addCardFieldsObserver() {
        try {
            let numberElem = document.getElementById('ricardomartins_pagbank_cc_cc_number');
            if (!numberElem) {
                // Element not found yet, try again after a short delay (max 10 attempts)
                if (!this._cardObserverRetries) {
                    this._cardObserverRetries = 0;
                }
                if (this._cardObserverRetries < 10) {
                    this._cardObserverRetries++;
                    setTimeout(() => {
                        this.addCardFieldsObserver();
                    }, 200);
                } else {
                    console.warn('PagBank: Campo de número do cartão não encontrado após múltiplas tentativas.');
                }
                return;
            }
            
            // Check if listeners are already attached to THIS specific element instance
            // We use a timestamp to detect if element was replaced
            const currentTimestamp = numberElem.getAttribute('data-pagbank-timestamp');
            const storedTimestamp = this._lastElementTimestamp || null;
            
            // If timestamp matches and listeners are marked as attached, skip
            if (currentTimestamp && currentTimestamp === storedTimestamp && numberElem.dataset.pagbankListenersAttached === 'true') {
                // Same element instance, listeners already attached
                return;
            }
            
            // Generate or update timestamp for this element instance
            const timestamp = Date.now().toString();
            numberElem.setAttribute('data-pagbank-timestamp', timestamp);
            this._lastElementTimestamp = timestamp;
            
            // Remove the flag first to allow reattachment if needed
            delete numberElem.dataset.pagbankListenersAttached;
            
            // Attach listeners to the element
            const updateInstallmentsHandler = (e) => { 
                if (RMPagBankObj && typeof RMPagBankObj.updateInstallments === 'function') {
                    RMPagBankObj.updateInstallments(); 
                }
            };
            
            const setBrandHandler = (e) => { 
                if (RMPagBankObj && typeof RMPagBankObj.setBrand === 'function') {
                    RMPagBankObj.setBrand(); 
                }
            };
            
            numberElem.addEventListener('change', updateInstallmentsHandler);
            numberElem.addEventListener('change', setBrandHandler);
            numberElem.addEventListener('input', updateInstallmentsHandler);
            numberElem.addEventListener('input', setBrandHandler);
            
            // Store handlers for potential cleanup (though not strictly necessary)
            numberElem._pagbankHandlers = {
                updateInstallments: updateInstallmentsHandler,
                setBrand: setBrandHandler
            };
            
            // Mark as attached
            numberElem.dataset.pagbankListenersAttached = 'true';
            console.debug('PagBank: Observers de cartão anexados com sucesso.');
        } catch (e) {
            console.error('PagBank: Não foi possível adicionar observevação aos cartões. ' + e.message);
        }
    }

    async cardActions() {
        RMPagBankObj.proceedCheckout = false;
        console.log('PagBank: Iniciando criptografia do cartão');

        let result = RMPagBankObj.encryptCard();
        console.debug('PagBank: encryptCard() retornou:', result);

        if (RMPagBankObj.config.enabled_3ds) {
            console.log('PagBank: 3DS iniciando...');

            result = await RMPagBankObj.authenticate3DS();

            console.log('PagBank: 3DS finalizado');
        } else {
            RMPagBankObj.proceedCheckout = true;
            console.debug('PagBank: 3DS desabilitado. proceedCheckout definido como true.');
        }

        this.enablePlaceOrderButton();
        console.debug('PagBank: cardActions() concluído. proceedCheckout:', RMPagBankObj.proceedCheckout);
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
                console.debug("Bandeira armazenada com sucesso");
            }
        } else {
            numberInput.style = '';
            if (this.config.debug) {
                console.debug("Bandeira não encontrada");
            }
        }
    }

    encryptCard() {
        if (RMPagBankObj.config.debug) {
            console.debug('Encrypting card');
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
                        console.debug('Card encrypted successfully');
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
        // Clear any pending timeout
        if (this._updateInstallmentsTimeout) {
            clearTimeout(this._updateInstallmentsTimeout);
        }
        
        // Debounce: wait 300ms before processing to avoid excessive calls
        this._updateInstallmentsTimeout = setTimeout(() => {
            try {
                let numberInput = document.getElementById('ricardomartins_pagbank_cc_cc_number');
                if (!numberInput) {
                    return;
                }
                
                let cardNumber = numberInput.value;
                let ccBin = cardNumber.replace(/\s/g, '').substring(0, 6);
                let ccBinInput = document.getElementById('ricardomartins_pagbank_cc_cc_bin');
                
                if (!ccBinInput) {
                    return;
                }
                
                // Only update if BIN changed and we have at least 6 digits
                if (ccBin !== window.pb_cc_bin && ccBin.length === 6) {
                    window.pb_cc_bin = ccBin;
                    ccBinInput.value = ccBin;
                    if (this.config.debug) {
                        console.debug('PagBank: BIN detectado:', ccBin, '- Buscando parcelas...');
                    }
                    this.getInstallments();
                }
            } catch (e) {
                console.error('PagBank: Erro ao atualizar parcelas:', e);
            }
        }, 300);
    }

    getInstallments() {
        // Prevent multiple simultaneous calls
        if (this._gettingInstallments) {
            console.log('PagBank: getInstallments() já em execução, ignorando chamada duplicada.');
            return;
        }
        
        let select = document.getElementById('ricardomartins_pagbank_cc_cc_installments');
        if (!select) {
            // Element not found yet, try again after a short delay
            setTimeout(() => {
                this.getInstallments();
            }, 200);
            return;
        }
        
        // Set flag to prevent concurrent calls
        this._gettingInstallments = true;
        
        let ccBin = typeof window.pb_cc_bin === 'undefined' || window.pb_cc_bin.replace(/[^0-9]/g, '').length < 6 ? '555566' : window.pb_cc_bin;
        fetch(this.config.installments_endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `cc_bin=${ccBin}`
        })
            .then(response => response.text())
            .then(response => {
                if (RMPagBankObj.config.debug) {
                    console.debug('Installments response:', response);
                }

                response = JSON.parse(response);

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
                
                // Clear flag after successful completion
                this._gettingInstallments = false;
            })
            .catch(() => {
                alert('Error getting installments. Please try again.');
                if (RMPagBankObj.config.debug) {
                    console.error('Error getting installments. Please try again.');
                }
                // Clear flag on error
                this._gettingInstallments = false;
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
            phone = tel && tel.value ? tel.value.replace(/\D/g, '') : '';
            if (!phone && fax && fax.value) {
                phone = fax.value.replace(/\D/g, '');
            }
        }
        let street = quote.street ? quote.street : document.querySelector('input[name^="billing[street"]').value;
        let number = quote.number ? quote.number : document.querySelectorAll('input[name^="billing[street"]')[1].value;

        // Get all street fields to mimic PHP logic
        let streetFields = document.querySelectorAll('input[name^="billing[street"]');
        let addressLinesNotEmpty = Array.from(streetFields).map(field => field.value).filter(value => value.trim() !== '');

        // Complement only if there are more than 3 address lines (same as PHP logic)
        let complement = quote.complement ? quote.complement : null;
        if (!complement && addressLinesNotEmpty.length > 3 && streetFields[2]) {
            complement = streetFields[2].value;
        }
        // If still no complement, use neighborhood as fallback
        if (!complement) {
            complement = quote.neighborhood ? quote.neighborhood : 'n/d';
        }
        // Final fallback to 'n/d' if still empty
        complement = complement || 'n/d';

        let city = quote.city ? quote.city : document.querySelector('input[name^="billing[city"]').value;
        let regionCode = quote.regionCode ? quote.regionCode : null;

        let postalCode = quote.postalCode ? quote.postalCode :
            document.querySelector('input[name^="billing[postcode"]').value.replace(/\D/g, '');

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
                totalValue = Math.round(Number(totalValue) * 100);
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

window.RMPagBank = RMPagBank;

// Auto-initialization: Monitor DOM for PagBank form and initialize automatically
(function() {
    const FORM_ID = 'payment_form_ricardomartins_pagbank_cc';
    let initializationAttempts = 0;
    const MAX_ATTEMPTS = 100; // Try for ~10 seconds (100 * 100ms)
    let isInitializing = false;
    let lastFormElement = null;
    
    const reapplyObservers = function() {
        // Reapply observers if RMPagBankObj exists and form is present
        if (typeof RMPagBankObj !== "undefined" && RMPagBankObj) {
            const formElement = document.getElementById(FORM_ID);
            if (formElement) {
                console.log('PagBank: Formulário recarregado detectado. Reaplicando observers...');
                try {
                    // Reset the timestamps to force reattachment
                    RMPagBankObj._lastElementTimestamp = null;
                    RMPagBankObj._lastButtonTimestamp = null; // Reset button timestamp too!
                    RMPagBankObj._cardObserverRetries = 0;
                    RMPagBankObj._placeOrderRetries = 0; // Reset place order retries
                    
                    // Remove old timestamps from any existing buttons to force reattachment
                    const buttonSelectors = [
                        '#onestepcheckout-place-order-button',
                        '.btn-checkout',
                        '#payment-buttons-container .button',
                    ];
                    // Also get configured buttons
                    let configuredButton = RMPagBankObj.config.placeorder_button;
                    if (configuredButton) {
                        configuredButton.split(',').forEach(btn => {
                            buttonSelectors.push(btn.trim());
                        });
                    }
                    // Remove duplicates
                    const uniqueSelectors = [...new Set(buttonSelectors)];
                    
                    uniqueSelectors.forEach(selector => {
                        const buttons = document.querySelectorAll(selector);
                        buttons.forEach(btn => {
                            btn.removeAttribute('data-pagbank-button-timestamp');
                            delete btn.dataset.pagbankButtonListenersAttached;
                            console.debug(`PagBank: Timestamp removido do botão: ${selector}`);
                        });
                    });
                    
                    RMPagBankObj.addCardFieldsObserver();
                    // Don't call getInstallments() here - it will be called automatically
                    // when the user types the card number via updateInstallments()
                    // Calling it here causes infinite loops when the block is reloaded
                    
                    // Also reapply place order button events
                    // Use a small delay to ensure button is in DOM
                    setTimeout(() => {
                        console.debug('PagBank: Chamando placeOrderEvent() após delay...');
                        RMPagBankObj.placeOrderEvent();
                    }, 200);
                    console.log('PagBank: Observers reaplicados com sucesso.');
                } catch (e) {
                    console.error('PagBank: Erro ao reaplicar observers:', e);
                }
            }
        }
    };
    
    // Monitor buttons for replacement
    let buttonObserver = null;
    const setupButtonObserver = function() {
        if (typeof MutationObserver === 'undefined') {
            return;
        }
        
        // Get button selectors
        const buttonSelectors = [
            '#onestepcheckout-place-order-button',
            '.btn-checkout',
            '#payment-buttons-container .button'
        ];
        
        if (typeof RMPagBankObj !== "undefined" && RMPagBankObj && RMPagBankObj.config) {
            let configuredButton = RMPagBankObj.config.placeorder_button;
            if (configuredButton) {
                configuredButton.split(',').forEach(btn => {
                    buttonSelectors.push(btn.trim());
                });
            }
        }
        
        // Find common parent for all buttons (usually the checkout form or container)
        const checkoutForm = document.querySelector('form#checkout-form, form#co-checkout-form, #checkout, .checkout');
        if (!checkoutForm) {
            setTimeout(setupButtonObserver, 500);
            return;
        }
        
        if (buttonObserver) {
            buttonObserver.disconnect();
        }
        
        buttonObserver = new MutationObserver(function(mutations) {
            let shouldReapply = false;
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1) { // Element node
                            // Check if any added node matches our button selectors
                            buttonSelectors.forEach(selector => {
                                if (node.matches && node.matches(selector)) {
                                    console.debug(`PagBank: Novo botão detectado: ${selector}`);
                                    shouldReapply = true;
                                }
                                // Also check children
                                if (node.querySelectorAll) {
                                    const matchingButtons = node.querySelectorAll(selector);
                                    if (matchingButtons.length > 0) {
                                        console.debug(`PagBank: Botões encontrados dentro do nó adicionado: ${selector}`);
                                        shouldReapply = true;
                                    }
                                }
                            });
                        }
                    });
                }
            });
            
            if (shouldReapply) {
                console.debug('PagBank: Botão substituído detectado. Reaplicando eventos...');
                setTimeout(() => {
                    if (typeof RMPagBankObj !== "undefined" && RMPagBankObj) {
                        RMPagBankObj._lastButtonTimestamp = null;
                        RMPagBankObj._placeOrderRetries = 0;
                        RMPagBankObj.placeOrderEvent();
                    }
                }, 100);
            }
        });
        
        buttonObserver.observe(checkoutForm, {
            childList: true,
            subtree: true
        });
        
        console.log('PagBank: Observer de botões configurado.');
    };
    
    // Setup button observer when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setupButtonObserver);
    } else {
        setTimeout(setupButtonObserver, 500);
    }
    
    const tryAutoInitialize = function(forceReinit = false) {
        // Don't initialize if already initialized or initializing (unless forced)
        if ((typeof RMPagBankObj !== "undefined" && !forceReinit) || isInitializing) {
            // Check if form was replaced (different element instance)
            const formElement = document.getElementById(FORM_ID);
            if (formElement && formElement !== lastFormElement && typeof RMPagBankObj !== "undefined") {
                // Form was replaced, reapply observers
                lastFormElement = formElement;
                reapplyObservers();
            }
            return;
        }
        
        initializationAttempts++;
        
        if (initializationAttempts > MAX_ATTEMPTS) {
            return;
        }
        
        // Check if form exists
        const formElement = document.getElementById(FORM_ID);
        if (!formElement) {
            setTimeout(tryAutoInitialize, 100);
            return;
        }
        
        // Check if form is visible (not display:none)
        const formStyle = window.getComputedStyle(formElement);
        if (formStyle.display === 'none') {
            setTimeout(tryAutoInitialize, 100);
            return;
        }
        
        // Try to get config from global variable or data attribute
        let config = null;
        let pagseguro_connect_3d_session = '';
        
        // Try to get from global variable (set by template)
        if (typeof window.pagbankConfig !== "undefined" && window.pagbankConfig) {
            // If it's a string, parse it; if it's already an object, use it directly
            if (typeof window.pagbankConfig === 'string') {
                try {
                    config = JSON.parse(window.pagbankConfig);
                } catch (e) {
                    console.error('PagBank: Erro ao parsear window.pagbankConfig:', e);
                }
            } else {
                config = window.pagbankConfig;
            }
            if (config) {
                console.debug('PagBank: Config obtida de window.pagbankConfig');
            }
        }
        
        // Try to get from data attribute on form (fallback)
        if (!config) {
            const configAttr = formElement.getAttribute('data-pagbank-config');
            if (configAttr) {
                try {
                    config = JSON.parse(configAttr);
                    console.debug('PagBank: Config obtida de data-attribute');
                } catch (e) {
                    console.error('PagBank: Erro ao parsear config do data-attribute:', e);
                }
            }
        }
        
        // Try to get 3D session from global variable
        if (typeof window.pagbank3dSession !== "undefined") {
            pagseguro_connect_3d_session = window.pagbank3dSession;
        }
        
        // Try to get 3D session from data attribute (fallback)
        if (!pagseguro_connect_3d_session) {
            const sessionAttr = formElement.getAttribute('data-pagbank-3d-session');
            if (sessionAttr) {
                pagseguro_connect_3d_session = sessionAttr;
            }
        }
        
        if (!config || typeof config !== 'object') {
            // Config not available yet or invalid, try again
            console.log('PagBank: Config ainda não disponível ou inválida, tentando novamente...', config);
            setTimeout(tryAutoInitialize, 100);
            return;
        }
        
        // Initialize
        try {
            isInitializing = true;
            console.debug('PagBank: Auto-inicializando com config:', config);
            RMPagBankObj = new RMPagBank(config, pagseguro_connect_3d_session);
            console.debug('PagBank: Auto-inicializado com sucesso!');
            lastFormElement = formElement;
            isInitializing = false;
        } catch (e) {
            console.error('PagBank: Erro na auto-inicialização:', e);
            isInitializing = false;
            setTimeout(() => tryAutoInitialize(forceReinit), 500);
        }
    };
    
    // Start monitoring immediately
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(tryAutoInitialize, 100);
        });
    } else {
        setTimeout(tryAutoInitialize, 100);
    }
    
    // Monitor form content changes to detect when it's reloaded
    let formContentObserver = null;
    
    // Also use MutationObserver to detect when form is added or replaced
    if (typeof MutationObserver !== 'undefined') {
        const observer = new MutationObserver(function(mutations) {
            let formWasReplaced = false;
            
            mutations.forEach(function(mutation) {
                // Check for removed nodes first (form was removed/replaced)
                mutation.removedNodes.forEach(function(node) {
                    if (node.nodeType === 1) {
                        const removedForm = node.id === FORM_ID 
                            ? node 
                            : (node.querySelector && node.querySelector('#' + FORM_ID));
                        
                        if (removedForm && removedForm === lastFormElement) {
                            console.debug('PagBank: Formulário removido do DOM. Aguardando recarregamento...');
                            formWasReplaced = true;
                            lastFormElement = null;
                            if (formContentObserver) {
                                formContentObserver.disconnect();
                            }
                        }
                    }
                });
                
                // Check for added nodes
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) {
                        const formElement = node.id === FORM_ID 
                            ? node 
                            : (node.querySelector && node.querySelector('#' + FORM_ID));
                        
                        if (formElement) {
                            console.debug('PagBank: Formulário detectado no DOM');
                            // Check if this is a replacement (form already existed and was removed)
                            if ((formWasReplaced || (lastFormElement && lastFormElement !== formElement)) && typeof RMPagBankObj !== "undefined") {
                                console.debug('PagBank: Formulário substituído detectado. Reaplicando observers...');
                                lastFormElement = formElement;
                                setTimeout(reapplyObservers, 200);
                                // Start observing the new form
                                if (formContentObserver) {
                                    formContentObserver.observe(formElement, {
                                        childList: true,
                                        subtree: true,
                                        attributes: true,
                                        attributeFilter: ['style', 'class']
                                    });
                                }
                            } else if (!lastFormElement) {
                                // New form, initialize
                                initializationAttempts = 0;
                                setTimeout(() => tryAutoInitialize(false), 200);
                            }
                        }
                    }
                });
            });
        });
        
        const targetNode = document.body || document.documentElement;
        if (targetNode) {
            observer.observe(targetNode, {
                childList: true,
                subtree: true
            });
        }
        
        // Create form content observer
        formContentObserver = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                // Check if form content was modified
                if (mutation.type === 'childList' && mutation.target.id === FORM_ID) {
                    console.debug('PagBank: Conteúdo do formulário modificado. Reaplicando observers...');
                    setTimeout(reapplyObservers, 100);
                }
                // Check if form attributes changed (like style display)
                if (mutation.type === 'attributes' && mutation.target.id === FORM_ID) {
                    const formStyle = window.getComputedStyle(mutation.target);
                    if (formStyle.display !== 'none' && typeof RMPagBankObj !== "undefined") {
                        // Form became visible, ensure observers are attached
                        setTimeout(reapplyObservers, 100);
                    }
                }
                // Check if child elements were added/removed (form fields replaced)
                if (mutation.type === 'childList' && mutation.target.closest && mutation.target.closest('#' + FORM_ID)) {
                    const formElement = document.getElementById(FORM_ID);
                    if (formElement && typeof RMPagBankObj !== "undefined") {
                        // Check if number input was replaced
                        const numberElem = document.getElementById('ricardomartins_pagbank_cc_cc_number');
                        if (numberElem && !numberElem.dataset.pagbankListenersAttached) {
                            console.debug('PagBank: Campos do formulário substituídos. Reaplicando observers...');
                            setTimeout(reapplyObservers, 100);
                        }
                    }
                }
            });
        });
        
        // Start observing form when it appears
        const startFormObserver = function() {
            const formElement = document.getElementById(FORM_ID);
            if (formElement && formContentObserver) {
                formContentObserver.observe(formElement, {
                    childList: true,
                    subtree: true,
                    attributes: true,
                    attributeFilter: ['style', 'class']
                });
            }
        };
        
        // Try to start observer immediately and after form appears
        startFormObserver();
        setTimeout(startFormObserver, 500);
        setTimeout(startFormObserver, 1000);
    }
    
    // Listen for payment method changes
    document.addEventListener('change', function(e) {
        if (e.target && e.target.name === 'payment[method]' && e.target.value === 'ricardomartins_pagbank_cc') {
            console.log('PagBank: Método de pagamento selecionado');
            initializationAttempts = 0;
            lastFormElement = null; // Reset to detect new form instance
            setTimeout(() => tryAutoInitialize(false), 300);
        }
    }, true);
    
    // Also listen for AJAX completion events that might reload the form
    const originalFetch = window.fetch;
    if (originalFetch) {
        window.fetch = function(...args) {
            return originalFetch.apply(this, args).then(response => {
                // Check if this might be a checkout AJAX request
                const url = args[0];
                if (typeof url === 'string' && (url.includes('checkout') || url.includes('payment'))) {
                    setTimeout(() => {
                        const formElement = document.getElementById(FORM_ID);
                        if (formElement) {
                            if (formElement !== lastFormElement && typeof RMPagBankObj !== "undefined") {
                                console.debug('PagBank: Possível recarregamento via AJAX detectado. Reaplicando observers...');
                                lastFormElement = formElement;
                                reapplyObservers();
                            }
                            // Restart form content observer if needed
                            if (formContentObserver) {
                                formContentObserver.disconnect();
                                formContentObserver.observe(formElement, {
                                    childList: true,
                                    subtree: true,
                                    attributes: true,
                                    attributeFilter: ['style', 'class']
                                });
                            }
                        }
                    }, 500);
                }
                return response;
            });
        };
    }
})();