//adds copy text from .pix-code to clipboard function on .copy-btn click
document.addEventListener("DOMContentLoaded", function () {
  // Seleciona todos os botões com classe copy-btn
  const copyButtons = document.querySelector(".copy-btn");
  copyButtons.addEventListener("click", function () {
    // Pega o valor do input/textarea com classe payment-code
    const copyTextElement = document.querySelector(".payment-code");
    if (!copyTextElement) return;

    const copyText = copyTextElement.value;
    copyToClipboard(copyText, function () {
      // Mostra a mensagem '.copied' com efeito fadeIn/fadeOut
      const copiedMsg = document.querySelector(".copied");
      if (!copiedMsg) return;

      // Fade in (simples)
      copiedMsg.style.opacity = 0;
      copiedMsg.style.display = "block";

      let opacity = 0;
      const fadeInInterval = setInterval(() => {
        if (opacity >= 1) {
          clearInterval(fadeInInterval);

          // Delay de 3 segundos antes de iniciar o fade out
          setTimeout(() => {
            // Fade out
            let fadeOutOpacity = 1;
            const fadeOutInterval = setInterval(() => {
              fadeOutOpacity -= 0.05;
              if (fadeOutOpacity <= 0) {
                clearInterval(fadeOutInterval);
                copiedMsg.style.display = "none";
              }
              copiedMsg.style.opacity = fadeOutOpacity;
            }, 25);
          }, 3000);
        } else {
          opacity += 0.05;
          copiedMsg.style.opacity = opacity;
        }
      }, 25);
    });
  });
});

async function copyToClipboard(textToCopy, successCallback) {
    // Navigator clipboard api needs a secure context (https)
    if (navigator.clipboard && window.isSecureContext) {
        await navigator.clipboard.writeText(textToCopy)
            .then(successCallback);
    } else {
        // Use the 'out of viewport hidden text area' trick
        const textArea = document.createElement("textarea");
        textArea.value = textToCopy;

        // Move textarea out of the viewport so it's not visible
        textArea.style.position = "absolute";
        textArea.style.left = "-999999px";

        document.body.prepend(textArea);
        textArea.select();

        try {
            document.execCommand('copy');
        } catch (error) {
            console.error(error);
        } finally {
            textArea.remove();
            successCallback();
        }
    }
}
