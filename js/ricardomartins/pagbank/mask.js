function mask() {
    const masks = {
        date(value, previousValue) {
            if (!isNaN(value) && parseInt(value) > 1 && value.length === 1 && previousValue.length === 0) {
                value = '0' + value;
            }

            return value
                .replace(/\D+/g, '')
                .replace(/(\d{2})(\d)/, '$1/$2')
                .replace(/(\/\d{2})\d+?$/, '$1');
        },
        name(value, previousValue) {
            return value
                .replace(/[0-9]/g, '')
                .replace(/[^\p{L} ]+/gu, '')
                .replace(/\s+/g, ' ')
                .toUpperCase();
        },
        document(value, previousValue) {
            let raw = value.replace(/[.\/-]/g, '').replace(/[^A-Za-z0-9]/g, '').toUpperCase();
            const isCpf = raw.length <= 11 && /^[0-9]*$/.test(raw);

            if (isCpf) {
                return raw
                    .replace(/(\d{3})(\d)/, '$1.$2')
                    .replace(/(\d{3})(\d)/, '$1.$2')
                    .replace(/(\d{3})(\d{1,2})/, '$1-$2')
                    .replace(/(-\d{2})\d+?$/, '$1');
            }

            raw = raw.slice(0, 14);
            return raw
                .replace(/([A-Z0-9]{2})([A-Z0-9])/, '$1.$2')
                .replace(/([A-Z0-9]{3})([A-Z0-9])/, '$1.$2')
                .replace(/([A-Z0-9]{3})([A-Z0-9])/, '$1/$2')
                .replace(/([A-Z0-9]{4})([A-Z0-9])/, '$1-$2')
                .replace(/(-[A-Z0-9]{2})[A-Z0-9]+?$/, '$1');
        },
        creditCard (value, previousValue) {
            return value
                .replace(/\D+/g, '')
                .replace(/(\d{4})(\d)/, '$1 $2')
                .replace(/(\d{4})(\d)/, '$1 $2')
                .replace(/(\d{4})(\d)/, '$1 $2')
                .replace(/(\d{4})\d+?$/, '$1');
        },
        cvv(value, previousValue) {
            return value.replace(/\D+/g, '').replace(/(\d{4})\d+?$/, '$1');
        }
    };

    document.querySelectorAll('input[data-js]').forEach($input => {
        const field = $input.dataset.js;
        if (!field || !masks[field]) return;

        let previousValue = $input.value;
        const applyMask = (e) => {
            let value = e.target.value;
            if(e.type == 'focusout') {
                value = value.trimStart().trimEnd();
            }
            e.target.value = masks[field](value, previousValue);
            previousValue = value;
        };

        $input.addEventListener('input', applyMask, false);
        $input.addEventListener('focusout', applyMask, false);
        $input.addEventListener('focusin', applyMask, false);
    });
}

document.addEventListener("DOMContentLoaded", function() {
    mask();
});

MutationObserver = window.MutationObserver || window.WebKitMutationObserver;
const observerPage = new MutationObserver(function(mutationList, observer) {
    mutationList.forEach((mutation) => {
        let id = mutation.target.id;
        if (typeof id === 'string' &&  id.includes("ricardomartins_pagbank")) {
            mask();
        }
    });
});
const observeConfig = {
    subtree: true,
    childList: true,
    attributeFilter: ["disabled"],
    attributes: true
}
observerPage.observe(document, observeConfig);
