// RMPagBank - Módulo de Cartão de Crédito convertido para jQuery e JavaScript puro
function RMPagBank(config, pagseguro_connect_3d_session) {
  this.config = config
  this.formElementAndSubmit = false
  this.proceedCheckout = false
  this.$_ = window.jQuery // Declare the $ variable

  this.initialize(config, pagseguro_connect_3d_session)
}

RMPagBank.prototype = {
  initialize: function (config, pagseguro_connect_3d_session) {
    console.log("PagBank: Inicializando módulo de cartão de crédito")

    this.addCardFieldsObserver()
    this.getInstallments()

    if (pagseguro_connect_3d_session !== "" && this.config.enabled_3ds) {
      document.getElementById("ricardomartins_pagbank_cc_cc_has_session").value = 1
    }

    if (this.config.enabled_3ds && pagseguro_connect_3d_session !== "") {
      this.setUp3DS(pagseguro_connect_3d_session)
    }

    this.placeOrderEvent()
  },

  placeOrderEvent: function () {
    const methodForm = this.$_("#payment_form_ricardomartins_pagbank_cc")

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

          if (!mutation.target.hasAttribute("disabled")) {
            button.removeAttribute("disabled")
          } else {
            button.setAttribute("disabled", "")
          }
        }
      }
    }

    const observer = new MutationObserver(mutationAttributesCallback)
    const form = methodForm.closest("form")

    let buttons = ["#onestepcheckout-place-order-button", ".btn-checkout", "#payment-buttons-container .button"]
    const configuredButton = this.config.placeorder_button

    if (configuredButton) {
      console.log("PagBank: um botão de finalização foi configurado.", configuredButton)
      const configuredButtons = configuredButton.split(",")
      buttons.unshift(...configuredButtons)
      // Remove duplicated buttons
      buttons = [...new Set(buttons)]
    }

    let eventAlreadyAttached = false
    

    buttons.forEach((btnSelector) => {
      const button = this.$_(btnSelector).first()[0]

      if (!button || eventAlreadyAttached) {
        return
      }

      observer.observe(button, { attributes: true })

      const onclickEvent = button.getAttribute("onclick")
      button.removeAttribute("onclick")

      const newButton = button.cloneNode(true)
      button.parentNode.replaceChild(newButton, button)

      const validateAndPreventDefault = (event) => {
        const paymentMethod = document.querySelector('input[name="payment[method]"]:checked')

        if (!paymentMethod || paymentMethod.value !== "ricardomartins_pagbank_cc") {
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
      form[0].addEventListener("submit", validateAndPreventDefault, false)
      eventAlreadyAttached = true
    })

    if (!eventAlreadyAttached) {
      throw new Error("PagBank: Não foi possível adicionar o evento de clique ao botão de finalizar compra.")
    }
  },

  addCardFieldsObserver: function () {
    try {
      const numberElem = this.$_("#ricardomartins_pagbank_cc_cc_number")
      

      numberElem.on("change", () => {
        this.updateInstallments()
        this.setBrand()
      })
    } catch (e) {
      console.error("PagBank: Não foi possível adicionar observação aos cartões. " + e.message)
    }
  },

  cardActions: async function () {
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
  },

  setBrand: function () {
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

    const ccNumber = numberInput.value.replace(/\s/g, "")
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
  },

  encryptCard: function () {
    if (this.config.debug) {
      console.log("Encrypting card")
    }

    // Get input elements
    const holderInput = document.getElementById("ricardomartins_pagbank_cc_cc_owner")
    const numberInput = document.getElementById("ricardomartins_pagbank_cc_cc_number")
    const expInput = document.getElementById("ricardomartins_pagbank_cc_cc_exp")
    const cvcInput = document.getElementById("ricardomartins_pagbank_cc_cc_cvc")
    const numberEncryptedInput = document.getElementById("ricardomartins_pagbank_cc_cc_number_encrypted")

    // Get input values
    const holderValue = holderInput.value
    const numberValue = numberInput.value
    const cvcValue = cvcInput.value
    const expValue = expInput.value

    if (!holderValue || !numberValue || !cvcValue || !expValue) {
      return false
    }

    let card;

    // Process input values
    const holderName = holderValue.trim().replace(/\s+/g, " ")
    const ccNumber = numberValue.replace(/\s/g, "")
    const ccCvc = cvcValue.replace(/\s/g, "")
    const expMonth = expValue.split("/")[0].replace(/\s/g, "")
    const expYear = "20" + expValue.split("/")[1].slice(-2).replace(/\s/g, "")

    this.disablePlaceOrderButton()

    try {
      const publicKey = this.config.publicKey
      card = PagSeguro.encryptCard({
        publicKey: publicKey,
        holder: holderName,
        number: ccNumber,
        expMonth: expMonth,
        expYear: expYear,
        securityCode: ccCvc,
        success: (data) => {
          if (RMPagBankObj.config.debug) {
            console.log("Card encrypted successfully")
          }
        },
        error: (data) => {
          console.error("Error encrypting card.", data)
        },
      })
    } catch (e) {
      alert("Erro ao criptografar o cartão.\nVerifique se os dados digitados estão corretos.")
      if (this.config.debug) {
        console.error("Erro ao criptografar o cartão.\nVerifique se os dados digitados estão corretos.", e)
      }
      return false
    }

    if (PagSeguro.hasErrors) {
      const errorCodes = [
        { code: "INVALID_NUMBER", message: "Número do cartão inválido" },
        { code: "INVALID_SECURITY_CODE", message: "CVV Inválido. Você deve passar um valor com 3, 4 ou mais dígitos." },
        { code: "INVALID_EXPIRATION_MONTH", message: "Mês de expiração incorreto. Passe um valor entre 1 e 12." },
        { code: "INVALID_EXPIRATION_YEAR", message: "Ano de expiração inválido." },
        { code: "INVALID_PUBLIC_KEY", message: "Chave Pública inválida." },
        { code: "INVALID_HOLDER", message: "Nome do titular do cartão inválido." },
      ]

      let error = ""
      for (let i = 0; i < PagSeguro.errors.length; i++) {
        for (let j = 0; j < errorCodes.length; j++) {
          if (errorCodes[j].code === PagSeguro.errors[i].code) {
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
  },

  updateInstallments: function () {
    const cardNumber = document.getElementById("ricardomartins_pagbank_cc_cc_number").value
    const ccBin = cardNumber.replace(/\s/g, "").substring(0, 6)
    const ccBinInput = document.getElementById("ricardomartins_pagbank_cc_cc_bin")

    if (ccBin !== window.pb_cc_bin && ccBin.length === 6) {
      window.pb_cc_bin = ccBin
      this.getInstallments()
      ccBinInput.value = ccBin
    }
  },

  getInstallments: function () {
    const ccBin =
      typeof window.pb_cc_bin === "undefined" || window.pb_cc_bin.replace(/[^0-9]/g, "").length < 6
        ? "555566"
        : window.pb_cc_bin
    

    this.$_.ajax({
      url: this.config.installments_endpoint,
      method: "POST",
      data: {
        cc_bin: ccBin,
      },
      success: (response) => {

        if (typeof response === "string") {
            try {
                response = JSON.parse(response);
            } catch (e) {
                console.error("Erro ao parsear JSON de parcelas:", e);
                alert("Erro ao processar resposta de parcelas.");
                return;
            }
        }

        if (this.config.debug) {
          console.log("Installments response:", response)
        }

        const select = document.getElementById("ricardomartins_pagbank_cc_cc_installments")
        select.innerHTML = ""

        response.forEach((item) => {
          const installmentValue = Number.parseInt(item.installment_value) / 100
          const installmentAmount = installmentValue.toFixed(2).toString().replace(".", ",")
          const text = item.installments + "x de R$ " + installmentAmount
          const totalAmount = (Number.parseInt(item.amount.value) / 100).toFixed(2).toString().replace(".", ",")

          const additionalText = item.interest_free === false ? " (Total R$ " + totalAmount + ")" : " (sem juros)"

          const option = document.createElement("option")
          option.value = item.installments
          option.text = text + additionalText
          select.appendChild(option)
        })
      },
      error: () => {
        alert("Error getting installments. Please try again.")
        if (this.config.debug) {
          console.error("Error getting installments. Please try again.")
        }
      },
    })
  },

  setUp3DS: function (pagseguro_connect_3d_session) {
    PagSeguro.setUp({
      session: pagseguro_connect_3d_session,
      env: this.config.environment,
    })
  },

  authenticate3DS: async function () {
    // Get input elements
    const holderInput = document.getElementById("ricardomartins_pagbank_cc_cc_owner")
    const numberInput = document.getElementById("ricardomartins_pagbank_cc_cc_number")
    const expInput = document.getElementById("ricardomartins_pagbank_cc_cc_exp")
    const installmentsInput = document.getElementById("ricardomartins_pagbank_cc_cc_installments")
    const card3dsInput = document.getElementById("ricardomartins_pagbank_cc_cc_3ds_id")

    // Get input values
    const holderValue = holderInput.value
    const numberValue = numberInput.value
    const expValue = expInput.value
    const installmentsValue = installmentsInput.value

    if (!holderValue || !numberValue || !expValue) {
      return false
    }

    this.disablePlaceOrderButton()
    this.enablePageLoader()

    const quote = await this.getQuoteData()

    // Process input values
    const holderName = holderValue.trim().replace(/\s+/g, " ")
    const ccNumber = numberValue.replace(/\s/g, "")
    const installments = installmentsValue.replace(/\s/g, "")
    const expMonth = expValue.split("/")[0].replace(/\s/g, "")
    const expYear = "20" + expValue.split("/")[1].slice(-2).replace(/\s/g, "")

    const email = quote.email ? quote.email : this.$_('input[name^="billing[email]"]').first().val()
    let name =
      quote.customerName && quote.customerName.trim().length > 0
        ? quote.customerName
        : this.$_('input[name^="billing[firstname]"]').first().val() +
          " " +
          this.$_('input[name^="billing[lastname]"]').first().val()

    // Clean name
    name = name
      .replace(/[0-9]/g, "")
      .replace(/[^\p{L} ]+/gu, "")
      .replace(/\s+/g, " ")
      .trim()

    let phone = quote.phone ? quote.phone.replace(/\D/g, "") : ""
    if (!phone) {
      const phoneInput =
        this.$_('input[name^="billing[telephone]"]').first()[0] || this.$_('input[name^="billing[fax]"]').first()[0]
      phone = phoneInput ? phoneInput.value.replace(/\D/g, "") : ""
    }

    const street = quote.street ? quote.street : this.$_('input[name^="billing[street]"]').first().val()
    const number = quote.number ? quote.number : this.$_('input[name^="billing[street]"]').eq(1).val()
    let complement = quote.complement ? quote.complement : quote.neighborhood
    complement = complement ? complement : this.$_('input[name^="billing[street]"]').eq(2).val()
    complement = complement ? complement : "n/d"

    const city = quote.city ? quote.city : this.$_('input[name^="billing[city]"]').first().val()
    let regionCode = quote.regionCode ? quote.regionCode : null
    const postalCode = quote.postalCode
      ? quote.postalCode
      : this.$_('input[name^="billing[postcode]"]').first().val().replace(/\D/g, "")

    if (regionCode === null || !isNaN(regionCode)) {
      const regionSelect = this.$_('select[name^="billing[region_id]"]').first()[0]
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
        let totalValue = match[1].replace(",", ".")
        totalValue = Number.parseInt(Number.parseFloat(totalValue).toFixed(2) * 100)
        amount = totalValue
      }
    }

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
          street: this.sanitizeAddress(street),
          number: this.sanitizeAddress(number),
          complement: this.sanitizeAddress(complement),
          regionCode: regionCode,
          country: "BRA",
          city: this.sanitizeAddress(city),
          postalCode: postalCode,
        },
        dataOnly: false,
      },
    }
    

    await PagSeguro.authenticate3DS(request)
      .then((result) => {
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
      })
      .catch((err) => {
        if (err instanceof PagSeguro.PagSeguroError) {
          console.error(err)
          console.debug("PagBank: " + err.detail)
          const errMsgs = err.detail.errorMessages.map((error) => this.pagBankParseErrorMessage(error)).join("\n")
          alert("Falha na requisição de autenticação 3D.\n" + errMsgs)
          this.enablePlaceOrderButton()
          this.disablePageLoader()
          return false
        }
      })
  },

  pagBankParseErrorMessage: (errorMessage) => {
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
  },

  disablePlaceOrderButton: function () {
    if (this.config.placeorder_button) {
      const placeOrderButton = this.$_(this.config.placeorder_button).first()

      if (placeOrderButton.length) {
        const buttonElement = placeOrderButton[0]
        const loader = this.$_('<div id="pagbank-loader"></div>')

        placeOrderButton.after(loader)

        loader.css({
          background: "#000000a1 url('" + this.config.loader_url + "') no-repeat center",
          height: placeOrderButton.css("height"),
          width: placeOrderButton.css("width"),
          left: buttonElement.offsetLeft + "px",
          "z-index": 99,
          opacity: 0.5,
          position: "absolute",
          top: buttonElement.offsetTop + "px",
        })
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
  },

  enablePlaceOrderButton: function () {
    const element = this.$_("#pagbank-loader")
    if (element.length) {
      element.remove()
    }
  },

  enablePageLoader: function () {
    const overlay = this.$_('<div id="pagbank-page-loader-overlay"></div>')
    const spinnerContainer = this.$_('<div id="pagbank-page-loader-container"></div>')
    const spinner = this.$_('<div id="pagbank-page-loader"></div>')
    const spinnerText = this.$_("<p>Aguarde...</p>")

    spinnerContainer.append(spinner, spinnerText)
    overlay.append(spinnerContainer)
    this.$_("body").append(overlay)
  },

  disablePageLoader: function () {
    this.$_("#pagbank-page-loader-overlay").remove()
  },

  getQuoteData: async function () {
    const endpoint = this.config.quotedata_endpoint

    return new Promise((resolve, reject) => {
      this.$_.ajax({
        url: endpoint,
        method: "GET",
        success: (response) => {
          resolve(response)
        },
        error: (error) => {
          reject(error)
        },
      })
    })
  },

  getCardTypes: (cardNumber) => {
    const typesPagBank = [
      {
        title: "MasterCard",
        type: "mastercard",
        pattern: "^(?:5[1-5][0-9]{2}|222[1-9]|22[3-9][0-9]|2[3-6][0-9]{2}|27[01][0-9]|2720)[0-9]{12}$",
        gaps: [4, 8, 12],
        lengths: [16],
        code: { name: "CVC", size: 3 },
      },
      {
        title: "Visa",
        type: "visa",
        pattern: "^4\\d*$",
        gaps: [4, 8, 12],
        lengths: [16, 18, 19],
        code: { name: "CVV", size: 3 },
      },
      {
        title: "American Express",
        type: "amex",
        pattern: "^3([47]\\d*)?$",
        isAmex: true,
        gaps: [4, 10],
        lengths: [15],
        code: { name: "CID", size: 4 },
      },
      {
        title: "Diners",
        type: "dinnersclub",
        pattern: "^(3(0[0-5]|095|6|[8-9]))\\d*$",
        gaps: [4, 10],
        lengths: [14, 16, 17, 18, 19],
        code: { name: "CVV", size: 3 },
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
        code: { name: "CVC", size: 3 },
      },
      {
        title: "Hipercard",
        type: "hipercard",
        pattern: "^((606282)|(637095)|(637568)|(637599)|(637609)|(637612))\\d*$",
        gaps: [4, 8, 12],
        lengths: [13, 16],
        code: { name: "CVC", size: 3 },
      },
      {
        title: "Aura",
        type: "aura",
        pattern: "^5078\\d*$",
        gaps: [4, 8, 12],
        lengths: [19],
        code: { name: "CVC", size: 3 },
      },
    ]

    // Remove spaces
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
  },

  getRegionCode: (region) => {
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
  },

  sanitizeAddress: (value) => (value ? value.replace(/\s+/g, " ").trim() : ""),
}