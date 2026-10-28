/**
 * PIX success-page status polling (PB-1083).
 * Same behaviour as Woo pix-instructions.php: poll every 10s for up to 10 minutes.
 */
document.addEventListener('DOMContentLoaded', function () {
  var root = document.getElementById('pagbank-pix-success');
  if (!root) {
    return;
  }

  var confirmedEl = root.querySelector('.pix-payment-confirmed');
  var paymentEl = root.querySelector('.pix-payment');
  if (confirmedEl && confirmedEl.style.display !== 'none' && paymentEl && paymentEl.style.display === 'none') {
    return;
  }

  var orderId = root.getAttribute('data-order-id');
  var statusUrl = root.getAttribute('data-status-url');
  if (!orderId || !statusUrl) {
    return;
  }

  var pollInterval = parseInt(root.getAttribute('data-poll-interval'), 10) || 10000;
  var pollTimeout = parseInt(root.getAttribute('data-poll-timeout'), 10) || 600000;
  var separator = statusUrl.indexOf('?') === -1 ? '?' : '&';
  var url = statusUrl + separator + 'order_id=' + encodeURIComponent(orderId);

  function showConfirmed() {
    if (paymentEl) {
      paymentEl.style.display = 'none';
    }
    if (confirmedEl) {
      confirmedEl.style.display = 'block';
    }
  }

  function isPaid(payload) {
    if (!payload || payload.success === false) {
      return false;
    }
    if (payload.paid === true || payload.paid === 1 || payload.paid === '1') {
      return true;
    }
    var status = payload.data;
    return status === 'processing' || status === 'complete' || status === 'completed';
  }

  function poll() {
    var xhr = new XMLHttpRequest();
    xhr.open('GET', url, true);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) {
        return;
      }
      if (xhr.status < 200 || xhr.status >= 300) {
        return;
      }
      var payload;
      try {
        payload = JSON.parse(xhr.responseText);
      } catch (e) {
        return;
      }
      if (isPaid(payload)) {
        clearInterval(intervalId);
        showConfirmed();
      }
    };
    xhr.send();
  }

  var intervalId = setInterval(poll, pollInterval);
  setTimeout(function () {
    clearInterval(intervalId);
  }, pollTimeout);

  // First check shortly after load (webhook may already have run).
  setTimeout(poll, 1500);
});
