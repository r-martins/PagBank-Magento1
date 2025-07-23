class RMPagBank {
  constructor(config, pagseguro_connect_3d_session) {
    console.log("PagBank: Inicializando módulo de cartão de crédito")
    this.config = config
    this.formElementAndSubmit = false
    this.proceedCheckout = false
    this.PagSeguro = window.PagSeguro // Declare PagSeguro variable

    this.addCardFieldsObserver()
    this.getInstallments()

    if (pagseguro_connect_3d_session !== "" && this.config.enabled_3ds) {
      document.getElementById("ricardomartins_pagbank_cc_cc_has_session").value = 1
    }

    if (this.config.enabled_3ds && pagseguro_connect_3d_session !== "") {
      this.setUp3DS(pagseguro_connect_3d_session)
    }

    this.placeOrderEvent()
  }

  placeOrderEvent() {
    const methodForm = document.querySelectorAll("#payment_form_ricardomartins_pagbank_cc")
    if (!methodForm.length) {
      console.log("PagBank: Não há métodos de pagamento habilitados em exibição. Execução abortada.")
      return
    }

    const mutationAttributesCallback = (mutationsList) => {
      const observedAttributes = ["class", "disabled"]
      for (const mutation of mutationsList) {
        if (mutation.type !== "attributes" || !observedAttributes.includes(mutation.attributeName)) {
          return
        }

        if (mutation.target.hasAttribute("id")) {
          const id = mutation.target.getAttribute("id")
          const button = document.getElementById(id)
          button.classList.value = mutation.target.classList.value

          if (mutation.target.hasAttribute("disabled") === false) {
            button.removeAttribute("disabled")
          }

          if (mutation.target.hasAttribute("disabled")) {
            button.setAttribute("disabled", "")
          }
        }
      }
    }

    const observer = new MutationObserver(mutationAttributesCallback)
    const form = methodForm[0].closest("form")
    let buttons = ["#onestepcheckout-place-order-button", ".btn-checkout", "#payment-buttons-container .button"]
    let configuredButton = this.config.placeorder_button

    if (configuredButton) {
      console.log("PagBank: um botão de finalização foi configurado.", configuredButton)
      configuredButton = configuredButton.split(",")
      buttons.unshift(...configuredButton)
      // Remove duplicated buttons
      buttons = buttons.filter((value, index) => {
        return buttons.indexOf(value) === index
      })
    }

    let eventAlreadyAttached = false

    buttons.forEach((btn) => {
      const button = document.querySelector(btn)
      if (typeof button === "undefined" || !button || eventAlreadyAttached) {
        return
      }

      observer.observe(button, { attributes: true })
      const onclickEvent = button.getAttribute("onclick")
      button.removeAttribute("onclick")

      const newButton = button.cloneNode(true)
      button.parentNode.replaceChild(newButton, button)

      const validateAndPreventDefault = (event) => {
        const paymentMethodInput = document.querySelector('input[name="payment[method]"]:checked')
        if (!paymentMethodInput || paymentMethodInput.value !== "ricardomartins_pagbank_cc") {
          button.setAttribute("onclick", onclickEvent)
          button.click()
          return true
        }

        event.preventDefault()
        event.stopImmediatePropagation()

        this.cardActions()
          .then((result) => {
            if (this.proceedCheckout) {
              button.setAttribute("onclick", onclickEvent)
              button.click()
              return true
            }
          })
          .catch((error) => {
            console.error("Erro ao executar os eventos do cartão:", error)
          })
      }

      newButton.addEventListener("click", validateAndPreventDefault, false)
      form.addEventListener("submit", validateAndPreventDefault, false)
      eventAlreadyAttached = true
    })

    if (!eventAlreadyAttached) {
      throw new Error("PagBank: Não foi possível adicionar o evento de clique ao botão de finalizar compra.")
    }
  }

  addCardFieldsObserver() {
    try {
      const numberElem = document.querySelector("#ricardomartins_pagbank_cc_cc_number")
      if (numberElem) {
        numberElem.addEventListener("change", (e) => {
          this.updateInstallments()
        })
        numberElem.addEventListener("change", (e) => {
          this.setBrand()
        })
      }
    } catch (e) {
      console.error("PagBank: Não foi possível adicionar observação aos cartões. " + e.message)
    }
  }

  async cardActions() {
    this.proceedCheckout = false

    if (this.config.debug) {
      console.log("Iniciando criptografia do cartão")
    }

    let result = this.encryptCard()

    if (this.config.debug) {
      console.log("Criptografia do cartão finalizada", result)
    }

    if (this.config.enabled_3ds) {
      if (this.config.debug) {
        console.log("3DS iniciando...")
      }
      result = await this.authenticate3DS()
      if (this.config.debug) {
        console.log("3DS finalizado")
      }
    } else {
      this.proceedCheckout = true
    }

    this.enablePlaceOrderButton()
    return result
  }

  setBrand() {
    const brandInput = document.getElementById("ricardomartins_pagbank_cc_cc_brand")
    const numberInput = document.getElementById("ricardomartins_pagbank_cc_cc_number")
    let urlPrefix = "https://stc.pagseguro.uol.com.br/"

    if (this.config.stc_mirror) {
      urlPrefix = "https://stcpagseguro.ricardomartins.net.br/"
    }

    const flagSrc = urlPrefix + "public/img/payment-methods-flags/68x30/{brand}.png"
    const style =
      "background-image: url(" +
      flagSrc +
      ");" +
      "background-repeat: no-repeat;" +
      "background-position: calc(100% - 5px) center;" +
      "background-size: auto calc(100% - 6px);"

    let ccNumber = numberInput.value
    ccNumber = ccNumber.replace(/\s/g, "")
    const cardTypes = this.getCardTypes(ccNumber)

    if (cardTypes.length > 0) {
      const finalStyle = style.replace(/{brand}/g, cardTypes[0].type)
      numberInput.style.cssText = finalStyle
      brandInput.value = cardTypes[0].type

      if (this.config.debug) {
        console.log("Bandeira armazenada com sucesso")
      }
    } else {
      numberInput.style.cssText = ""
      if (this.config.debug) {
        console.log("Bandeira não encontrada")
      }
    }
  }

  encryptCard() {
    if (this.config.debug) {
      console.log("Encrypting card")
    }

    // inputs
    const holderInput = document.getElementById("ricardomartins_pagbank_cc_cc_owner")
    const numberInput = document.getElementById("ricardomartins_pagbank_cc_cc_number")
    const expInput = document.getElementById("ricardomartins_pagbank_cc_cc_exp")
    const cvcInput = document.getElementById("ricardomartins_pagbank_cc_cc_cvc")
    const numberEncryptedInput = document.getElementById("ricardomartins_pagbank_cc_cc_number_encrypted")

    // get input values
    const holderValue = holderInput.value
    const numberValue = numberInput.value
    const cvcValue = cvcInput.value
    const expValue = expInput.value

    if (holderValue === "" || numberValue === "" || cvcValue === "" || expValue === "") {
      return false
    }

    // replace trim and remove duplicated spaces from input values
    const holderName = holderValue.trim().replace(/\s+/g, " ")
    const ccNumber = numberValue.replace(/\s/g, "")
    const ccCvc = cvcValue.replace(/\s/g, "")
    const expMonth = expValue.split("/")[0].replace(/\s/g, "")
    const expYear = "20" + expValue.split("/")[1].slice(-2).replace(/\s/g, "")

    this.disablePlaceOrderButton()

    try {
      const publicKey = this.config.publicKey
      const card = this.PagSeguro.encryptCard({
        publicKey: publicKey,
        holder: holderName,
        number: ccNumber,
        expMonth: expMonth,
        expYear: expYear,
        securityCode: ccCvc,
        success: function (data) {
          if (this.config.debug) {
            console.log("Card encrypted successfully")
          }
        },
        error: (data) => {
          console.error("Error encrypting card.", data)
        },
      })

      if (card.hasErrors) {
        const errorCodes = [
          { code: "INVALID_NUMBER", message: "Número do cartão inválido" },
          {
            code: "INVALID_SECURITY_CODE",
            message: "CVV Inválido. Você deve passar um valor com 3, 4 ou mais dígitos.",
          },
          {
            code: "INVALID_EXPIRATION_MONTH",
            message: "Mês de expiração incorreto. Passe um valor entre 1 e 12.",
          },
          { code: "INVALID_EXPIRATION_YEAR", message: "Ano de expiração inválido." },
          { code: "INVALID_PUBLIC_KEY", message: "Chave Pública inválida." },
          { code: "INVALID_HOLDER", message: "Nome do titular do cartão inválido." },
        ]

        // extract error message
        let error = ""
        for (let i = 0; i < card.errors.length; i++) {
          // loop through error codes to find the message
          for (let j = 0; j < errorCodes.length; j++) {
            if (errorCodes[j].code === card.errors[i].code) {
              error += errorCodes[j].message + "\n"
              break
            }
          }
        }

        alert("Erro ao criptografar cartão.\n" + error)
        if (this.config.debug) {
          console.error("Erro ao criptografar cartão.\n" + error)
        }
        this.enablePlaceOrderButton()
        return false
      }

      numberEncryptedInput.value = card.encryptedCard
      return true
    } catch (e) {
      alert("Erro ao criptografar o cartão.\nVerifique se os dados digitados estão corretos.")
      if (this.config.debug) {
        console.error("Erro ao criptografar o cartão.\nVerifique se os dados digitados estão corretos.", e)
      }
      return false
    }
  }

  updateInstallments() {
    const cardNumber = document.getElementById("ricardomartins_pagbank_cc_cc_number").value
    const ccBin = cardNumber.replace(/\s/g, "").substring(0, 6)
    const ccBinInput = document.getElementById("ricardomartins_pagbank_cc_cc_bin")

    if (ccBin !== window.pb_cc_bin && ccBin.length === 6) {
      window.pb_cc_bin = ccBin
      this.getInstallments()
      ccBinInput.value = ccBin
    }
  }

  getInstallments() {
    const ccBin =
      typeof window.pb_cc_bin === "undefined" || window.pb_cc_bin.replace(/[^0-9]/g, "").length < 6
        ? "555566"
        : window.pb_cc_bin

    const formData = new FormData()
    formData.append("cc_bin", ccBin)

    fetch(this.config.installments_endpoint, {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        if (this.config.debug) {
          console.log("Installments response:", data)
        }

        const select = document.getElementById("ricardomartins_pagbank_cc_cc_installments")
        select.innerHTML = ""

        for (let i = 0; i < data.length; i++) {
          const installmentValue = Number.parseInt(data[i].installment_value) / 100
          const installmentAmount = installmentValue.toFixed(2).toString().replace(".", ",")
          const text = data[i].installments + "x de R$ " + installmentAmount
          const totalAmount = Number.parseInt(data[i].amount.value) / 100
          const totalAmountFormatted = totalAmount.toFixed(2).toString().replace(".", ",")

          let additionalText = " (sem juros)"
          if (data[i].interest_free === false) {
            additionalText = " (Total R$ " + totalAmountFormatted + ")"
          }

          const option = document.createElement("option")
          option.value = data[i].installments
          option.text = text + additionalText
          select.appendChild(option)
        }
      })
      .catch((error) => {
        alert("Error getting installments. Please try again.")
        if (this.config.debug) {
          console.error("Error getting installments. Please try again.", error)
        }
      })
  }

  setUp3DS(pagseguro_connect_3d_session) {
    // region 3ds authentication method
    this.PagSeguro.setUp({
      session: pagseguro_connect_3d_session,
      env: this.config.environment,
    })
  }

  async authenticate3DS() {
    // inputs
    const holderInput = document.getElementById("ricardomartins_pagbank_cc_cc_owner")
    const numberInput = document.getElementById("ricardomartins_pagbank_cc_cc_number")
    const expInput = document.getElementById("ricardomartins_pagbank_cc_cc_exp")
    const installmentsInput = document.getElementById("ricardomartins_pagbank_cc_cc_installments")
    const card3dsInput = document.getElementById("ricardomartins_pagbank_cc_cc_3ds_id")

    // get input values
    const holderValue = holderInput.value
    const numberValue = numberInput.value
    const expValue = expInput.value
    const installmentsValue = installmentsInput.value

    if (holderValue === "" || numberValue === "" || expValue === "") {
      return false
    }

    this.disablePlaceOrderButton()
    this.enablePageLoader()

    const quote = await this.getQuoteData()

    // replace trim and remove duplicated spaces from input values
    const holderName = holderValue.trim().replace(/\s+/g, " ")
    const ccNumber = numberValue.replace(/\s/g, "")
    const installments = installmentsValue.replace(/\s/g, "")
    const expMonth = expValue.split("/")[0].replace(/\s/g, "")
    const expYear = "20" + expValue.split("/")[1].slice(-2).replace(/\s/g, "")

    const email = quote.email ? quote.email : document.querySelector('input[name^="billing[email]"]').value
    let name =
      quote.customerName && quote.customerName?.trim()?.length > 0
        ? quote.customerName
        : document.querySelector('input[name^="billing[firstname]"]').value +
          " " +
          document.querySelector('input[name^="billing[lastname]"]').value

    // replace trim and remove duplicated spaces from input values
    name = name
      .replace(/[0-9]/g, "")
      .replace(/[^\p{L} ]+/gu, "")
      .replace(/\s+/g, " ")
      .trimStart()
      .trimEnd()

    let phone = quote.phone ? quote.phone.replace(/\D/g, "") : ""
    if (!phone) {
      const phoneInput =
        document.querySelector('input[name^="billing[telephone]"]') ||
        document.querySelector('input[name^="billing[fax]"]')
      phone = phoneInput ? phoneInput.value.replace(/\D/g, "") : ""
    }

    const street = quote.street ? quote.street : document.querySelector('input[name^="billing[street]"]').value
    const number = quote.number ? quote.number : document.querySelectorAll('input[name^="billing[street]"]')[1].value
    let complement = quote.complement ? quote.complement : quote.neighborhood
    complement = complement ? complement : document.querySelectorAll('input[name^="billing[street]"]')[2].value
    complement = complement ? complement : "n/d"

    const city = quote.city ? quote.city : document.querySelector('input[name^="billing[city]"]').value
    let regionCode = quote.regionCode ? quote.regionCode : null
    const postalCode = quote.postalCode
      ? quote.postalCode
      : document.querySelector('input[name^="billing[postcode]"]').value.replace(/\D/g, "")

    if (regionCode === null || !isNaN(regionCode)) {
      const regionSelect = document.querySelector('select[name^="billing[region_id]"]')
      const selectedIndex = regionSelect.selectedIndex
      const region = regionSelect.options[selectedIndex].text
      regionCode = this.getRegionCode(region)
    }

    let amount = Math.round(quote.totalAmount * 100)
    if (installments > 1) {
      const installmentText = document.getElementById("ricardomartins_pagbank_cc_cc_installments").selectedOptions[0]
        .text
      const totalValuePattern = /$$Total R\$ ([\d.,]+)$$/
      const match = installmentText.match(totalValuePattern)
      if (match) {
        let totalValue = match[1]
        totalValue = totalValue.replace(",", ".")
        totalValue = Number.parseInt(Number.parseFloat(totalValue.toString()).toFixed(2) * 100)
        amount = totalValue
      }
    }

    const sanitizedStreet = this.sanitizeAddress(street)
    const sanitizedNumber = this.sanitizeAddress(number)
    const sanitizedComplement = this.sanitizeAddress(complement)
    const sanitizedCity = this.sanitizeAddress(city)

    const request = {
      data: {
        customer: {
          name: name,
          email: email,
          phones: [
            {
              country: "55",
              area: phone.substring(0, 2),
              number: phone.substring(2),
              type: "MOBILE",
            },
          ],
        },
        paymentMethod: {
          type: "CREDIT_CARD",
          installments: installments,
          card: {
            number: ccNumber,
            expMonth: expMonth,
            expYear: expYear,
            holder: {
              name: holderName,
            },
          },
        },
        amount: {
          value: amount,
          currency: "BRL",
        },
        billingAddress: {
          street: sanitizedStreet,
          number: sanitizedNumber,
          complement: sanitizedComplement,
          regionCode: regionCode,
          country: "BRA",
          city: sanitizedCity,
          postalCode: postalCode,
        },
        dataOnly: false,
      },
    }

    try {
      const result = await this.PagSeguro.authenticate3DS(request)

      switch (result.status) {
        case "CHANGE_PAYMENT_METHOD":
          alert("Pagamento negado pelo PagBank. Escolha outro método de pagamento ou cartão.")
          this.enablePlaceOrderButton()
          this.disablePageLoader()
          return false

        case "AUTH_FLOW_COMPLETED":
          if (result.authenticationStatus === "AUTHENTICATED") {
            card3dsInput.value = result.id
            console.debug("PagBank: 3DS Autenticado ou Sem desafio")
            this.enablePlaceOrderButton()
            this.disablePageLoader()
            this.proceedCheckout = true
            return true
          }
          alert("Autenticação 3D falhou. Tente novamente.")
          this.enablePlaceOrderButton()
          this.disablePageLoader()
          return false

        case "AUTH_NOT_SUPPORTED":
          if (this.config.cc_3ds_allow_continue) {
            console.debug("PagBank: 3DS não suportado pelo cartão. Continuando sem 3DS.")
            this.proceedCheckout = true
            this.enablePlaceOrderButton()
            this.disablePageLoader()
            return true
          }
          alert("Seu cartão não suporta autenticação 3D. Escolha outro método de pagamento ou cartão.")
          this.enablePlaceOrderButton()
          this.disablePageLoader()
          return false

        case "REQUIRE_CHALLENGE":
          console.debug("PagBank: REQUIRE_CHALLENGE - O desafio está sendo exibido pelo banco.")
          this.enablePlaceOrderButton()
          this.disablePageLoader()
          break
      }
    } catch (err) {
      if (err instanceof this.PagSeguro.PagSeguroError) {
        console.error(err)
        console.debug("PagBank: " + err.detail)
        const errMsgs = err.detail.errorMessages.map((error) => this.pagBankParseErrorMessage(error)).join("\n")
        alert("Falha na requisição de autenticação 3D.\n" + errMsgs)
        this.enablePlaceOrderButton()
        this.disablePageLoader()
        return false
      }
    }
  }

  pagBankParseErrorMessage(errorMessage) {
    const codes = {
      40001: "Parâmetro obrigatório",
      40002: "Parâmetro inválido",
      40003: "Parâmetro desconhecido ou não esperado",
      40004: "Limite de uso da API excedido",
      40005: "Método não permitido",
    }

    const descriptions = {
      "must match the regex: ^\\p{L}+['.-]?(?:\\s+\\p{L}+['.-]?)+$": "parece inválido ou fora do padrão permitido",
      "cannot be blank": "não pode estar em branco",
      "size must be between 8 and 9": "deve ter entre 8 e 9 caracteres",
      "must be numeric": "deve ser numérico",
      "must be greater than or equal to 100": "deve ser maior ou igual a 100",
      "must be between 1 and 24": "deve ser entre 1 e 24",
      "only ISO 3166-1 alpha-3 values are accepted": "deve ser um código ISO 3166-1 alpha-3",
      "either paymentMethod.card.id or paymentMethod.card.encrypted should be informed":
        "deve ser informado o cartão de crédito criptografado ou o id do cartão",
      "must be an integer number": "deve ser um número inteiro",
      "card holder name must contain a first and last name":
        "o nome do titular do cartão deve conter um primeiro e último nome",
      "must be a well-formed email address": "deve ser um endereço de e-mail válido",
    }

    const parameters = {
      "amount.value": "valor do pedido",
      "customer.name": "nome do cliente",
      "customer.phones[0].number": "número de telefone do cliente",
      "customer.phones[0].area": "DDD do telefone do cliente",
      "billingAddress.complement": "complemento/bairro do endereço de cobrança",
      "paymentMethod.installments": "parcelas",
      "billingAddress.country": "país de cobrança",
      "paymentMethod.card": "cartão de crédito",
      "paymentMethod.card.encrypted": "cartão de crédito criptografado",
      "customer.email": "e-mail",
    }

    const { code, description, parameterName } = errorMessage
    const codeTranslation = codes[code] || code
    const descriptionTranslation = descriptions[description] || description
    const parameterTranslation = parameters[parameterName] || parameterName

    return `${codeTranslation}: ${parameterTranslation} - ${descriptionTranslation}`
  }

  disablePlaceOrderButton() {
    if (this.config.placeorder_button) {
      const placeOrderButton = document.querySelector(this.config.placeorder_button)
      if (placeOrderButton) {
        const loader = document.createElement("div")
        loader.id = "pagbank-loader"
        placeOrderButton.parentNode.insertBefore(loader, placeOrderButton.nextSibling)

        const loaderElement = document.getElementById("pagbank-loader")
        const buttonRect = placeOrderButton.getBoundingClientRect()

        loaderElement.style.cssText = `
                    background: #000000a1 url('${this.config.loader_url}') no-repeat center;
                    height: ${buttonRect.height}px;
                    width: ${buttonRect.width}px;
                    left: ${placeOrderButton.offsetLeft}px;
                    z-index: 99;
                    opacity: 0.5;
                    position: absolute;
                    top: ${placeOrderButton.offsetTop}px;
                `
        return
      }
      if (this.config.debug) {
        console.error(
          "PagBank: Botão configurado não encontrado (" +
            this.config.placeorder_button +
            "). Verifique as configurações do módulo.",
        )
      }
    }
  }

  enablePlaceOrderButton() {
    const element = document.getElementById("pagbank-loader")
    if (element) {
      element.remove()
    }
  }

  enablePageLoader() {
    const overlay = document.createElement("div")
    overlay.id = "pagbank-page-loader-overlay"

    const spinnerContainer = document.createElement("div")
    spinnerContainer.id = "pagbank-page-loader-container"

    const spinner = document.createElement("div")
    spinner.id = "pagbank-page-loader"

    const spinnerText = document.createElement("p")
    spinnerText.innerHTML = "Aguarde..."

    spinnerContainer.appendChild(spinner)
    spinnerContainer.appendChild(spinnerText)
    overlay.appendChild(spinnerContainer)
    document.body.appendChild(overlay)
  }

  disablePageLoader() {
    const element = document.getElementById("pagbank-page-loader-overlay")
    if (element) {
      element.remove()
    }
  }

  async getQuoteData() {
    const endpoint = this.config.quotedata_endpoint

    try {
      const response = await fetch(endpoint, {
        method: "GET",
      })
      return await response.json()
    } catch (error) {
      throw error
    }
  }

  getCardTypes(cardNumber) {
    const typesPagBank = [
      {
        title: "MasterCard",
        type: "mastercard",
        pattern: "^(?:5[1-5][0-9]{2}|222[1-9]|22[3-9][0-9]|2[3-6][0-9]{2}|27[01][0-9]|2720)[0-9]{12}$",
        gaps: [4, 8, 12],
        lengths: [16],
        code: {
          name: "CVC",
          size: 3,
        },
      },
      {
        title: "Visa",
        type: "visa",
        pattern: "^4\\d*$",
        gaps: [4, 8, 12],
        lengths: [16, 18, 19],
        code: {
          name: "CVV",
          size: 3,
        },
      },
      {
        title: "American Express",
        type: "amex",
        pattern: "^3([47]\\d*)?$",
        isAmex: true,
        gaps: [4, 10],
        lengths: [15],
        code: {
          name: "CID",
          size: 4,
        },
      },
      {
        title: "Diners",
        type: "dinnersclub",
        pattern: "^(3(0[0-5]|095|6|[8-9]))\\d*$",
        gaps: [4, 10],
        lengths: [14, 16, 17, 18, 19],
        code: {
          name: "CVV",
          size: 3,
        },
      },
      {
        title: "Elo",
        type: "elo",
        pattern:
          "^((451416)|(509091)|(636368)|(636297)|(504175)|(438935)|(40117[8-9])|(45763[1-2])|" +
          "(457393)|(431274)|(50990[0-2])|(5099[7-9][0-9])|(50996[4-9])|(509[1-8][0-9][0-9])|" +
          "(5090(0[0-2]|0[4-9]|1[2-9]|[24589][0-9]|3[1-9]|6[0-46-9]|7[0-24-9]))|" +
          "(5067(0[0-24-8]|1[0-24-9]|2[014-9]|3[0-379]|4[0-9]|5[0-3]|6[0-5]|7[0-8]))|" +
          "(6504(0[5-9]|1[0-9]|2[0-9]|3[0-9]))|" +
          "(6504(8[5-9]|9[0-9])|6505(0[0-9]|1[0-9]|2[0-9]|3[0-8]))|" +
          "(6505(4[1-9]|5[0-9]|6[0-9]|7[0-9]|8[0-9]|9[0-8]))|" +
          "(6507(0[0-9]|1[0-8]))|(65072[0-7])|(6509(0[1-9]|1[0-9]|20))|" +
          "(6516(5[2-9]|6[0-9]|7[0-9]))|(6550(0[0-9]|1[0-9]))|" +
          "(6550(2[1-9]|3[0-9]|4[0-9]|5[0-8])))\\d*$",
        gaps: [4, 8, 12],
        lengths: [16],
        code: {
          name: "CVC",
          size: 3,
        },
      },
      {
        title: "Hipercard",
        type: "hipercard",
        pattern: "^((606282)|(637095)|(637568)|(637599)|(637609)|(637612))\\d*$",
        gaps: [4, 8, 12],
        lengths: [13, 16],
        code: {
          name: "CVC",
          size: 3,
        },
      },
      {
        title: "Aura",
        type: "aura",
        pattern: "^5078\\d*$",
        gaps: [4, 8, 12],
        lengths: [19],
        code: {
          name: "CVC",
          size: 3,
        },
      },
    ]

    // remove spaces
    cardNumber = cardNumber.replace(/\s/g, "")
    const result = []

    if (!cardNumber) {
      return result
    }

    for (let i = 0; i < typesPagBank.length; i++) {
      const value = typesPagBank[i]
      if (new RegExp(value.pattern).test(cardNumber)) {
        result.push(JSON.parse(JSON.stringify(value)))
      }
    }

    return result.slice(-1)
  }

  getRegionCode(region) {
    const regionCodes = {
      AC: "ACRE",
      AL: "ALAGOAS",
      AP: "AMAPA",
      AM: "AMAZONAS",
      BA: "BAHIA",
      CE: "CEARA",
      DF: "DISTRITO FEDERAL",
      ES: "ESPIRITO SANTO",
      GO: "GOIAS",
      MA: "MARANHÃO",
      MT: "MATO GROSSO",
      MS: "MATO GROSSO DO SUL",
      MG: "MINAS GERAIS",
      PA: "PARÁ",
      PB: "PARAÍBA",
      PR: "PARANÁ",
      PE: "PERNAMBUCO",
      PI: "PIAUÍ",
      RJ: "RIO DE JANEIRO",
      RN: "RIO GRANDE DO NORTE",
      RS: "RIO GRANDE DO SUL",
      RO: "RONDÔNIA",
      RR: "RORAIMA",
      SC: "SANTA CATARINA",
      SP: "SÃO PAULO",
      SE: "SERGIPE",
      TO: "TOCANTINS",
    }

    region = region.toUpperCase()
    return Object.keys(regionCodes).find((key) => regionCodes[key] === region)
  }

  sanitizeAddress(value) {
    return value ? value.replace(/\s+/g, " ").trimStart().trimEnd() : ""
  }
}

// Para manter compatibilidade com o código existente
window.RMPagBank = RMPagBank
