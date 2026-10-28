/**
 * PIX success-page status polling (PB-1083).
 * Same behaviour as Woo / GHL: poll every 10s for up to 10 minutes,
 * then switch the waiting badge to the green confirmed state.
 */
document.addEventListener('DOMContentLoaded', function () {
  var root = document.getElementById('pagbank-pix-success');
  if (!root) {
    return;
  }

  var detailsEl = root.querySelector('.pix-payment-details');
  var statusEl = root.querySelector('.pix-payment-status');
  var statusTextEl = root.querySelector('.pix-status-text');
  var labelWaiting = root.getAttribute('data-label-waiting') || 'Awaiting payment...';
  var labelConfirmed = root.getAttribute('data-label-confirmed') || 'Payment confirmed';

  if (statusEl && statusEl.classList.contains('is-confirmed')) {
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
    if (detailsEl) {
      detailsEl.style.display = 'none';
    }
    if (statusEl) {
      statusEl.classList.remove('is-waiting');
      statusEl.classList.add('is-confirmed');
    }
    if (statusTextEl) {
      statusTextEl.textContent = labelConfirmed;
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
