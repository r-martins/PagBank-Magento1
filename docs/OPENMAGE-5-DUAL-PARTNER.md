# OpenMage / Magento 1 — dual PagBank / Vindi (5.0+)

Versão **5.0.0+**. Espelho do playbook Woo `WOO-5-DUAL-PARTNER.md`, adaptado ao Magento 1 / OpenMage / Maho.

## Detecção

| Connect Key | Partner | Host |
|-------------|---------|------|
| `CON…` / `CONSANDBOX…` | PagBank | `ws…/pspro/v7/connect/ws/` (**40** chars) |
| `CONVD…` / `CONVDSANDBOX…` | Vindi | `api…/v1/` — **40** chars prod + sandbox (igual PagBank; legado mais curto OK) |

Nunca chame a API Vindi nativa com a Connect Key. Tokenização no browser: `GET /tokenize/config` → `payment_profiles` (chave pública).

**Modelo C:** keys `CONVD…` enviam o contrato canônico PB (centavos, `CREDIT_CARD` / `CREDIT_CARD_3DS`, `gateway_token`). Keys `CON…` continuam no v7.

## Matriz de recursos

| Recurso | PagBank | Vindi |
|---------|---------|-------|
| PIX / boleto | ✓ | ✓ (`api/v1`) |
| Cartão | `encryptCard` | public `payment_profiles` → `gateway_token` |
| 3DS | SDK PagBank | setup → DDC → enroll → challenge → validate (proxies `pagbank/ajax/vindi*`) |
| Tipo no pedido (3DS) | `CREDIT_CARD` + `authentication_method` | `CREDIT_CARD_3DS` + `three_ds` |
| Soft descriptor | ✓ | omitido |
| Checkout hospedado / split / recorrência | n/a neste módulo | n/a |
| Debug upstream | — | `POST orders?debug=1` automático |

## Meta de pedidos (`additional_data`)

Helper: `RicardoMartins_PagBank_Helper_PaymentData`.

| Operação | Comportamento |
|----------|----------------|
| **Write** PagBank | `pagbank_*` (+ aliases legados `pagbank_order_id`, `charge_id`) |
| **Write** Vindi | `pb_*` (+ mesmos aliases legados para admin) |
| **Read** | Prefixo do partner do pedido; fallback no outro |

Novos pedidos gravam `partner` e `connect_key_fp`.

## Classes Partner

- `Model/Partner/Detector.php` — prefixo da key
- `Model/Partner/Capabilities.php` — `usesGatewayToken`, `has3ds` (probe `payment_methods`)
- `Model/Partner/Cutoff.php` — **5/nov/2026** (CTA apenas)
- `Model/Partner/Branding.php` — labels

## Corte / migração

Notice no admin quando a key é PagBank. Sem hard-kill no módulo. URL: https://pbintegracoes.com/migrar

Após trocar para `CONVD…`, consultas/refunds de charges PagBank antigas podem falhar (mesma key exigida pelo pedido). UI de pedidos antigos continua legível via `additional_data`.

## Teste local (Docker)

O PHP do OpenMage roda no container `openmagephp`. Hosts `*.test` → `127.0.0.1` no Mac **quebram** o cURL de dentro do container.

Em `docker-compose.yml` do OpenMage:

```yaml
services:
  openmage-php:
    extra_hosts:
      - "api.pbintegracoes.test:host-gateway"
      - "v7.test:host-gateway"
```

Somente dev, na **cópia local** de `ConnectInterface.php` (não commitar):

- `WS_CONNECT_BASE_URI` → ex. `https://v7.test/pspro/v7/connect/` ou `http://ws-pagseguro/pspro/v7/connect/`
- `API_V1_URI` → ex. `https://api.pbintegracoes.test/v1/`
- `CURL_SSL_VERIFY` → `false` se usar cert `*.test`

Não há override no admin nem via `define()` global — reduz risco de outro módulo ou config alterar o destino das requisições.

## QA rápido

- [ ] CC PagBank (encrypt + 3DS) regressão
- [ ] CC Vindi sem 3DS (`gateway_token`)
- [ ] CC Vindi com 3DS (`CREDIT_CARD_3DS`)
- [ ] PIX e boleto + webhook
- [ ] Parcelas `fees/calculate` com `CONVD`
- [ ] Troca CON→CONVD: novos pedidos na v1; info de pedidos antigos no admin
- [ ] Notice de cutoff só com key PagBank
