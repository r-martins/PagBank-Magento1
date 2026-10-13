class RMPagBank {
    constructor(config, pagseguro_connect_3d_session) {
        console.log('PB Integrações: Inicializando módulo de cartão de crédito');
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
        console.log('PB Integrações: placeOrderEvent() chamado');
        const methodForm = document.querySelectorAll('#payment_form_ricardomartins_pagbank_cc');
        if (!methodForm.length) {
            // Form not found yet, try again after a short delay (max 5 attempts)
            if (!this._placeOrderRetries) {
                this._placeOrderRetries = 0;
            }
            if (this._placeOrderRetries < 5) {
                this._placeOrderRetries++;
                console.log(`PB Integrações: Formulário não encontrado. Tentativa ${this._placeOrderRetries}/5...`);
                setTimeout(() => {
                    this.placeOrderEvent();
                }, 500);
                return;
            }
            console.log('PB Integrações: Não há métodos de pagamento habilitados em exibição. Execução abortada.');
            return;
        }
        console.log('PB Integrações: Formulário encontrado. Procurando botões...');

        // Removed mutationAttributesCallback and observer - they were causing performance issues
        // by doing expensive DOM operations (set Element.className) on every mutation.
        // Not needed since we're no longer cloning buttons.

        let form = methodForm[0].closest('form');
        if (!form) {
            console.warn('PB Integrações: Formulário não encontrado para anexar eventos do botão.');
            return;
        }

        let buttons = [
            '#onestepcheckout-place-order-button',
            '.btn-checkout',
            '#payment-buttons-container .button'
        ];
        let configuredButton = this.config.placeorder_button;
        if (configuredButton) {
            console.log('PB Integrações: um botão de finalização foi configurado.', configuredButton);
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
                console.log(`PB Integrações: Botão não encontrado com seletor: ${btnSelector}`);
                return;
            }
            
            console.log(`PB Integrações: Botão encontrado com seletor: ${btnSelector}`);
            
            // Check if events are already attached to this button instance
            // Use a data attribute to track if this specific button instance has listeners
            const buttonTimestamp = button.getAttribute('data-pagbank-button-timestamp');
            const storedTimestamp = this._lastButtonTimestamp || null;
            
            console.log(`PB Integrações: Timestamp do botão: ${buttonTimestamp}, Timestamp armazenado: ${storedTimestamp}, Listeners anexados: ${button.dataset.pagbankButtonListenersAttached}`);
            
            // Check if listeners are already attached to THIS button instance
            // Verify by checking timestamp AND handler reference
            if (storedTimestamp !== null && 
                buttonTimestamp && 
                buttonTimestamp === storedTimestamp && 
                button.dataset.pagbankButtonListenersAttached === 'true' &&
                button._pagbankClickHandler) {
                console.log('PB Integrações: Eventos do botão já anexados a esta instância. Pulando para evitar duplicação.');
                return;
            }
            
            // If stored timestamp is null (forced reset), remove old timestamp and listeners from button
            if (storedTimestamp === null && buttonTimestamp) {
                console.log('PB Integrações: Removendo timestamp antigo do botão para forçar reaplicação.');
                button.removeAttribute('data-pagbank-button-timestamp');
                delete button.dataset.pagbankButtonListenersAttached;
                
                // Also remove old listeners if they exist
                if (button._pagbankClickHandler) {
                    console.log('PB Integrações: Removendo listeners antigos do botão (reset forçado).');
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
                    console.log('PB Integrações: Evento já processado, ignorando...');
                    return;
                }
                
                // Get current button reference (may have changed after DOM updates)
                let currentButton = document.querySelector(storedBtnSelector);
                
                // If this is a programmatic click (after restoring onclick), let it proceed normally
                if (event.isTrusted === false && currentButton && currentButton.dataset.pagbankRestoringOnclick === 'true') {
                    console.log('PB Integrações: Click programático após restaurar onclick, permitindo execução normal.');
                    return true; // Let the onclick handler execute normally
                }
                
                console.log('PB Integrações: Botão de finalizar compra clicado ou formulário submetido.');
                console.log('PB Integrações: Event type:', event.type, 'Target:', event.target, 'Current target:', event.currentTarget);
                
                // Mark as processed to prevent duplicate execution
                event._pagbankProcessed = true;
                
                // Re-get button reference if not found
                if (!currentButton) {
                    console.warn('PB Integrações: Botão não encontrado no DOM. Tentando continuar...');
                    // Try to get button from event target
                    if (event.target && (event.target.matches && event.target.matches(storedBtnSelector))) {
                        currentButton = event.target;
                        console.log('PB Integrações: Botão encontrado via event.target');
                    } else if (event.currentTarget && event.currentTarget.matches && event.currentTarget.matches(storedBtnSelector)) {
                        currentButton = event.currentTarget;
                        console.log('PB Integrações: Botão encontrado via event.currentTarget');
                    } else {
                        currentButton = button; // Fallback to stored reference
                    }
                }
                
                let paymentMethod = document.querySelector('input[name="payment[method]"]:checked');
                if (!paymentMethod || paymentMethod.value !== 'ricardomartins_pagbank_cc') {
                    console.log('PB Integrações: Método de pagamento não é PagBank. Continuando checkout normal.');
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
                
                console.log('PB Integrações: Método PagBank detectado. Prevenindo submit padrão.');
                event.preventDefault();
                event.stopImmediatePropagation();
                event.stopPropagation();

                console.debug('PB Integrações: Iniciando cardActions...');
                RMPagBankObj.cardActions().then((result) => {
                  console.debug('PB Integrações: cardActions concluído. proceedCheckout:', RMPagBankObj.proceedCheckout);
                  if (RMPagBankObj.proceedCheckout) {
                    console.debug('PB Integrações: Prosseguindo com checkout...');
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
                        console.debug('PB Integrações: Sem onclick inline. Disparando clique programático para acionar handlers do OSC...');
                        
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
                    console.warn('PB Integrações: proceedCheckout é false. Checkout não prosseguirá.');
                  }
                })
                .catch((error) => {
                  console.error("PB Integrações: Erro ao executar os eventos do cartão:", error);
                });
                
                return false;
            }

            // Always remove old listeners before adding new ones (prevent duplication)
            if (button._pagbankClickHandler) {
                console.debug('PB Integrações: Removendo listeners antigos do botão antes de adicionar novos.');
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
            
            console.debug(`PB Integrações: Eventos do botão de finalizar compra anexados com sucesso ao botão: ${btnSelector}`);
            console.debug(`PB Integrações: Timestamp do botão definido como: ${timestamp}`);
            console.debug(`PB Integrações: Event listeners anexados em ambas as fases (capture e bubble)`);
            console.debug(`PB Integrações: Botão verificado - ID: ${button.id || 'sem ID'}, Classes: ${button.className || 'sem classes'}`);
            
            // Test click handler by checking if button still exists and has listeners
            setTimeout(() => {
                const testButton = document.querySelector(btnSelector);
                if (testButton) {
                    if (testButton.dataset.pagbankButtonListenersAttached === 'true') {
                        console.debug(`PB Integrações: Botão ainda presente no DOM após anexação: ${btnSelector}`);
                        // Verify listeners are actually attached by checking if button has our data attribute
                        console.debug(`PB Integrações: Verificação - Botão tem timestamp: ${testButton.getAttribute('data-pagbank-button-timestamp') || 'não'}`);
                    } else {
                        console.warn(`PB Integrações: Botão encontrado mas sem flag de listeners anexados: ${btnSelector}`);
                        console.warn(`PB Integrações: Botão foi substituído após anexação! Reaplicando...`);
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
                    console.warn(`PB Integrações: Botão não encontrado no DOM após anexação: ${btnSelector}`);
                }
            }, 500);

            eventAlreadyAttached = true;
        });

        if (!eventAlreadyAttached) {
            console.warn('PB Integrações: Nenhum botão de finalizar compra encontrado. Verifique a configuração placeorder_button.');
            console.warn('PB Integrações: Botões procurados:', buttons);
        } else {
            console.log('PB Integrações: placeOrderEvent() concluído com sucesso.');
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
                    console.warn('PB Integrações: Campo de número do cartão não encontrado após múltiplas tentativas.');
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
            console.debug('PB Integrações: Observers de cartão anexados com sucesso.');
        } catch (e) {
            console.error('PB Integrações: Não foi possível adicionar observevação aos cartões. ' + e.message);
        }
    }

    async cardActions() {
        RMPagBankObj.proceedCheckout = false;
        console.log('PB Integrações: Iniciando criptografia/tokenização do cartão');

        let result;
        if (this.config.is_vindi) {
            result = await RMPagBankObj.tokenizeVindiCard();
            if (result && this.config.enabled_3ds) {
                console.log('PB Integrações: 3DS Vindi iniciando...');
                result = await RMPagBankObj.runVindi3ds();
                console.log('PB Integrações: 3DS Vindi finalizado');
            } else if (result) {
                RMPagBankObj.proceedCheckout = true;
            }
        } else {
            result = RMPagBankObj.encryptCard();
            console.debug('PB Integrações: encryptCard() retornou:', result);

            if (RMPagBankObj.config.enabled_3ds) {
                console.log('PB Integrações: 3DS iniciando...');
                result = await RMPagBankObj.authenticate3DS();
                console.log('PB Integrações: 3DS finalizado');
            } else {
                RMPagBankObj.proceedCheckout = true;
                console.debug('PB Integrações: 3DS desabilitado. proceedCheckout definido como true.');
            }
        }

        this.enablePlaceOrderButton();
        console.debug('PB Integrações: cardActions() concluído. proceedCheckout:', RMPagBankObj.proceedCheckout);
        return result;
    }

    setBrand() {
        let brandInput = document.getElementById('ricardomartins_pagbank_cc_cc_brand');
        let numberInput = document.getElementById('ricardomartins_pagbank_cc_cc_number');
        let urlPrefix = 'https://stc.pagseguro.uol.com.br/';
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

    /**
     * Vindi payment_company_code from PAN (Elo/Hipercard before Visa/Master).
     */
    detectVindiPaymentCompanyCode(ccNumber) {
        const n = String(ccNumber || '').replace(/\D/g, '');
        if (/^(606282|3841)/.test(n)) return 'hipercard';
        if (/^(4011|4312|4389|4514|4576|5041|5067|5090|6277|6362|6363|6504|6505|6507|6509|6516|6550)/.test(n)) return 'elo';
        if (/^3[47]/.test(n)) return 'american_express';
        if (/^3(?:0[0-5]|[68])/.test(n)) return 'diners_club';
        if (/^6(?:011|5)/.test(n)) return 'discover';
        if (/^5[1-5]/.test(n) || /^2(2[2-9]|[3-6]|7[01])/.test(n)) return 'mastercard';
        if (/^4/.test(n)) return 'visa';
        return 'mastercard';
    }

    async tokenizeVindiCard() {
        const holderInput = document.getElementById('ricardomartins_pagbank_cc_cc_owner');
        const numberInput = document.getElementById('ricardomartins_pagbank_cc_cc_number');
        const expInput = document.getElementById('ricardomartins_pagbank_cc_cc_exp');
        const cvcInput = document.getElementById('ricardomartins_pagbank_cc_cc_cvc');
        const numberEncryptedInput = document.getElementById('ricardomartins_pagbank_cc_cc_number_encrypted');
        const companyInput = document.getElementById('ricardomartins_pagbank_cc_payment_company_code');

        if (!holderInput || !numberInput || !expInput || !cvcInput || !numberEncryptedInput) {
            return false;
        }
        if (!holderInput.value || !numberInput.value || !cvcInput.value || !expInput.value) {
            return false;
        }

        const holderName = holderInput.value.trim().replace(/\s+/g, ' ');
        const ccNumber = numberInput.value.replace(/\s/g, '');
        const ccCvc = cvcInput.value.replace(/\s/g, '');
        const expiryParts = expInput.value.split('/');
        let expMonth = (expiryParts[0] || '').replace(/\D/g, '');
        let expYear = (expiryParts[1] || '').replace(/\D/g, '').slice(-2);
        expMonth = ('0' + expMonth).slice(-2);
        if (!/^(0[1-9]|1[0-2])$/.test(expMonth) || !/^\d{2}$/.test(expYear)) {
            alert('Validade do cartão inválida. Use o formato MM/AA.');
            return false;
        }
        const cardExpiration = expMonth + '/20' + expYear;
        const company = this.detectVindiPaymentCompanyCode(ccNumber);
        const publicKey = this.config.publicKey;
        const profilesUrl = this.config.vindi_payment_profiles_url;
        if (!publicKey || !profilesUrl) {
            alert('Configuração de tokenização Vindi indisponível. Recarregue a página ou verifique a Connect Key.');
            return false;
        }

        this.disablePlaceOrderButton();
        try {
            const auth = btoa(String(publicKey) + ':');
            const resp = await fetch(profilesUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Basic ' + auth
                },
                body: JSON.stringify({
                    holder_name: holderName,
                    card_expiration: cardExpiration,
                    card_number: ccNumber,
                    card_cvv: ccCvc,
                    payment_method_code: 'credit_card',
                    payment_company_code: company
                })
            });
            const body = await resp.json().catch(function () { return {}; });
            if (!resp.ok) {
                let detail = '';
                if (body && Array.isArray(body.errors)) {
                    detail = body.errors.map(function (e) {
                        return (e.parameter ? e.parameter + ': ' : '') + (e.message || '');
                    }).filter(Boolean).join('\n');
                }
                alert('Erro ao tokenizar o cartão.' + (detail ? '\n' + detail : '\nVerifique os dados e tente novamente.'));
                return false;
            }
            const token = body.payment_profile && (body.payment_profile.gateway_token || body.gateway_token);
            if (!token) {
                alert('Erro ao tokenizar o cartão (token ausente).');
                return false;
            }
            numberEncryptedInput.value = token;
            if (companyInput) {
                companyInput.value = company;
            }
            window.ps_vindi_payment_company = company;
            return true;
        } catch (e) {
            console.error(e);
            alert('Erro ao tokenizar o cartão.\nVerifique se os dados digitados estão corretos.');
            return false;
        }
    }

    async vindiAjaxJson(url, payload) {
        const resp = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify(payload || {})
        });
        const json = await resp.json().catch(function () { return {}; });
        if (!resp.ok || json.success === false) {
            throw new Error(json.message || 'Falha na requisição 3DS Vindi');
        }
        return json.data || json;
    }

    async runVindi3ds() {
        try {
            const quoteEndpoint = this.config.quotedata_endpoint;
            const quoteResp = await fetch(quoteEndpoint, { credentials: 'same-origin' });
            const checkout = await quoteResp.json();
            const gatewayToken = document.getElementById('ricardomartins_pagbank_cc_cc_number_encrypted').value;
            const company = (document.getElementById('ricardomartins_pagbank_cc_payment_company_code') || {}).value
                || window.ps_vindi_payment_company
                || 'mastercard';
            const installmentsEl = document.getElementById('ricardomartins_pagbank_cc_cc_installments');
            const installments = installmentsEl ? parseInt(installmentsEl.value, 10) || 1 : 1;
            const amountCents = Math.round(parseFloat(checkout.totalAmount || 0) * 100);

            const profileData = await this.vindiAjaxJson(this.config.vindi_payment_profile_endpoint, {
                gateway_token: gatewayToken,
                payment_company_code: company,
                checkout: checkout
            });
            const profileId = profileData.payment_profile_id;
            const profileInput = document.getElementById('ricardomartins_pagbank_cc_payment_profile_id');
            if (profileInput) {
                profileInput.value = profileId;
            }

            const setupData = await this.vindiAjaxJson(this.config.vindi_3ds_setup_endpoint, {
                payment_profile_id: profileId
            });
            const setup = setupData.setup || setupData;
            await this.vindiRunDdc(setup);

            const enrollData = await this.vindiAjaxJson(this.config.vindi_3ds_enroll_endpoint, {
                session_id: setup.session_id,
                amount: amountCents,
                installments: installments,
                device_info: {
                    screen_height: window.screen.height,
                    screen_width: window.screen.width,
                    color_depth: window.screen.colorDepth,
                    timezone_offset: new Date().getTimezoneOffset(),
                    language: navigator.language || 'pt-BR',
                    java_enabled: false,
                    user_agent: navigator.userAgent
                },
                checkout: checkout
            });
            const enroll = enrollData.enroll || enrollData;
            let authResult = enroll;
            if (enroll && enroll.status === 'AUTHENTICATION_REQUIRED' && enroll.step_up_url) {
                authResult = await this.vindiRunChallenge(enroll);
                const again = await this.tokenizeVindiCard();
                if (!again) {
                    return false;
                }
            }

            const payloadInput = document.getElementById('ricardomartins_pagbank_cc_cc_3ds_payload');
            if (payloadInput && authResult) {
                const threeDs = {
                    status: authResult.status || 'AUTHENTICATION_SUCCESSFUL',
                    liability_shift: !!authResult.liability_shift,
                    consumer_authentication_information: authResult.consumer_authentication_information
                        || authResult.authentication_information
                        || {}
                };
                payloadInput.value = JSON.stringify(threeDs);
            }
            const idInput = document.getElementById('ricardomartins_pagbank_cc_cc_3ds_id');
            if (idInput) {
                idInput.value = (authResult && (authResult.authentication_transaction_id || authResult.id)) || 'vindi_3ds';
            }
            RMPagBankObj.proceedCheckout = true;
            return true;
        } catch (e) {
            console.error(e);
            alert('Falha na autenticação 3D Secure.\n' + (e.message || 'Tente novamente.'));
            RMPagBankObj.proceedCheckout = false;
            return false;
        }
    }

    vindiRunDdc(setup) {
        const self = this;
        return new Promise(function (resolve) {
            const mpiOrigin = self.config.vindi_mpi_origin || 'https://mpi.vindi.com.br';
            let iframe = document.getElementById('rm-pagbank-vindi-ddc-iframe');
            if (iframe) {
                iframe.remove();
            }
            iframe = document.createElement('iframe');
            iframe.id = 'rm-pagbank-vindi-ddc-iframe';
            iframe.name = 'rm-pagbank-vindi-ddc-iframe';
            iframe.style.cssText = 'width:0;height:0;border:0;position:absolute;left:-9999px;';
            document.body.appendChild(iframe);

            let form = document.getElementById('rm-pagbank-vindi-ddc-form');
            if (form) {
                form.remove();
            }
            form = document.createElement('form');
            form.id = 'rm-pagbank-vindi-ddc-form';
            form.method = 'POST';
            form.target = 'rm-pagbank-vindi-ddc-iframe';
            form.action = setup.device_data_collection_url || (mpiOrigin + '/V1/Cruise/Collect');
            form.style.display = 'none';
            const jwt = document.createElement('input');
            jwt.type = 'hidden';
            jwt.name = 'JWT';
            jwt.value = setup.access_token || setup.device_data_collection_jwt || '';
            form.appendChild(jwt);
            document.body.appendChild(form);
            form.submit();
            setTimeout(resolve, 3000);
        });
    }

    vindiRunChallenge(enroll) {
        const self = this;
        return new Promise(function (resolve, reject) {
            const onMsg = function (event) {
                const data = event.data || {};
                if (!data || data.source !== 'rm-pagbank-vindi-3ds') {
                    return;
                }
                window.removeEventListener('message', onMsg);
                const wrap = document.getElementById('rm-pagbank-vindi-challenge-wrap');
                if (wrap) {
                    wrap.remove();
                }
                self.vindiAjaxJson(self.config.vindi_3ds_validate_endpoint, {
                    authentication_transaction_id: data.TransactionId || enroll.authentication_transaction_id,
                    card_type: window.ps_vindi_payment_company || 'visa'
                }).then(function (validateData) {
                    resolve((validateData && validateData.validate) || validateData || enroll);
                }).catch(reject);
            };
            window.addEventListener('message', onMsg);

            let wrap = document.getElementById('rm-pagbank-vindi-challenge-wrap');
            if (wrap) {
                wrap.remove();
            }
            wrap = document.createElement('div');
            wrap.id = 'rm-pagbank-vindi-challenge-wrap';
            wrap.style.cssText = 'position:fixed;z-index:99999;inset:0;background:rgba(0,0,0,.45);display:flex;align-items:center;justify-content:center;';
            const iframe = document.createElement('iframe');
            iframe.id = 'rm-pagbank-vindi-stepup-iframe';
            iframe.name = 'rm-pagbank-vindi-stepup-iframe';
            iframe.style.cssText = 'width:420px;height:520px;max-width:95vw;max-height:90vh;background:#fff;border:0;border-radius:4px;';
            wrap.appendChild(iframe);
            document.body.appendChild(wrap);

            const form = document.createElement('form');
            form.method = 'POST';
            form.target = 'rm-pagbank-vindi-stepup-iframe';
            form.action = enroll.step_up_url;
            form.style.display = 'none';
            const jwt = document.createElement('input');
            jwt.type = 'hidden';
            jwt.name = 'JWT';
            jwt.value = enroll.step_up_token || enroll.access_token || '';
            form.appendChild(jwt);
            document.body.appendChild(form);
            form.submit();
            form.remove();
        });
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
                        console.debug('PB Integrações: BIN detectado:', ccBin, '- Buscando parcelas...');
                    }
                    this.getInstallments();
                }
            } catch (e) {
                console.error('PB Integrações: Erro ao atualizar parcelas:', e);
            }
        }, 300);
    }

    getInstallments() {
        // Prevent multiple simultaneous calls
        if (this._gettingInstallments) {
            console.log('PB Integrações: getInstallments() já em execução, ignorando chamada duplicada.');
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
                        console.debug('PB Integrações: 3DS Autenticado ou Sem desafio');
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
                        console.debug('PB Integrações: 3DS não suportado pelo cartão. Continuando sem 3DS.');
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
                    console.debug('PB Integrações: REQUIRE_CHALLENGE - O desafio está sendo exibido pelo banco.');
                    this.enablePlaceOrderButton();
                    this.disablePageLoader();
                    break;
            }
        }).catch((err) => {
            if (err instanceof PagSeguro.PagSeguroError) {
                console.error(err);
                console.debug('PB Integrações: ' + err.detail);
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
                console.error('PB Integrações: Botão configurado não encontrado (' + RMPagBankObj.config.placeorder_button + '). Verifique as configurações do módulo.');
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
                console.log('PB Integrações: Formulário recarregado detectado. Reaplicando observers...');
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
                            console.debug(`PB Integrações: Timestamp removido do botão: ${selector}`);
                        });
                    });
                    
                    RMPagBankObj.addCardFieldsObserver();
                    // Don't call getInstallments() here - it will be called automatically
                    // when the user types the card number via updateInstallments()
                    // Calling it here causes infinite loops when the block is reloaded
                    
                    // Also reapply place order button events
                    // Use a small delay to ensure button is in DOM
                    setTimeout(() => {
                        console.debug('PB Integrações: Chamando placeOrderEvent() após delay...');
                        RMPagBankObj.placeOrderEvent();
                    }, 200);
                    console.log('PB Integrações: Observers reaplicados com sucesso.');
                } catch (e) {
                    console.error('PB Integrações: Erro ao reaplicar observers:', e);
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
                                    console.debug(`PB Integrações: Novo botão detectado: ${selector}`);
                                    shouldReapply = true;
                                }
                                // Also check children
                                if (node.querySelectorAll) {
                                    const matchingButtons = node.querySelectorAll(selector);
                                    if (matchingButtons.length > 0) {
                                        console.debug(`PB Integrações: Botões encontrados dentro do nó adicionado: ${selector}`);
                                        shouldReapply = true;
                                    }
                                }
                            });
                        }
                    });
                }
            });
            
            if (shouldReapply) {
                console.debug('PB Integrações: Botão substituído detectado. Reaplicando eventos...');
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
        
        console.log('PB Integrações: Observer de botões configurado.');
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
                    console.error('PB Integrações: Erro ao parsear window.pagbankConfig:', e);
                }
            } else {
                config = window.pagbankConfig;
            }
            if (config) {
                console.debug('PB Integrações: Config obtida de window.pagbankConfig');
            }
        }
        
        // Try to get from data attribute on form (fallback)
        if (!config) {
            const configAttr = formElement.getAttribute('data-pagbank-config');
            if (configAttr) {
                try {
                    config = JSON.parse(configAttr);
                    console.debug('PB Integrações: Config obtida de data-attribute');
                } catch (e) {
                    console.error('PB Integrações: Erro ao parsear config do data-attribute:', e);
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
            console.log('PB Integrações: Config ainda não disponível ou inválida, tentando novamente...', config);
            setTimeout(tryAutoInitialize, 100);
            return;
        }
        
        // Initialize
        try {
            isInitializing = true;
            console.debug('PB Integrações: Auto-inicializando com config:', config);
            RMPagBankObj = new RMPagBank(config, pagseguro_connect_3d_session);
            console.debug('PB Integrações: Auto-inicializado com sucesso!');
            lastFormElement = formElement;
            isInitializing = false;
        } catch (e) {
            console.error('PB Integrações: Erro na auto-inicialização:', e);
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
                            console.debug('PB Integrações: Formulário removido do DOM. Aguardando recarregamento...');
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
                            console.debug('PB Integrações: Formulário detectado no DOM');
                            // Check if this is a replacement (form already existed and was removed)
                            if ((formWasReplaced || (lastFormElement && lastFormElement !== formElement)) && typeof RMPagBankObj !== "undefined") {
                                console.debug('PB Integrações: Formulário substituído detectado. Reaplicando observers...');
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
                    console.debug('PB Integrações: Conteúdo do formulário modificado. Reaplicando observers...');
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
                            console.debug('PB Integrações: Campos do formulário substituídos. Reaplicando observers...');
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
            console.log('PB Integrações: Método de pagamento selecionado');
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
                                console.debug('PB Integrações: Possível recarregamento via AJAX detectado. Reaplicando observers...');
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