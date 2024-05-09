function mask() {
    const masks = {
        date (value) {
            return value
                .replace(/\D+/g, '')
                .replace(/(\d{2})(\d)/, '$1/$2')
                .replace(/(\/\d{2})\d+?$/, '$1')
        },
        document (value) {
            value = value.replace(/\D+/g, '');
            if (value.length > 11) {
                return value
                    .replace(/(\d{2})(\d)/, '$1.$2')
                    .replace(/(\d{3})(\d)/, '$1.$2')
                    .replace(/(\d{3})(\d)/, '$1/$2')
                    .replace(/(\d{4})(\d)/, '$1-$2')
                    .replace(/(-\d{2})\d+?$/, '$1')
            }
            return value
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d{1,2})/, '$1-$2')
                .replace(/(-\d{2})\d+?$/, '$1')
        },
        creditCard (value) {
            return value
                .replace(/\D+/g, '')
                .replace(/(\d{4})(\d)/, '$1 $2')
                .replace(/(\d{4})(\d)/, '$1 $2')
                .replace(/(\d{4})(\d)/, '$1 $2')
                .replace(/(\d{4})\d+?$/, '$1')
        },
        cvv (value) {
            return value
                .replace(/\D+/g, '')
                .replace(/(\d{4})\d+?$/, '$1')
        }
    }

    document.querySelectorAll('input').forEach($input => {
        const field = $input.dataset.js

        if (field === undefined || masks[field] === undefined) return

        $input.addEventListener('input', e => {
            e.target.value = masks[field](e.target.value)
        }, false)
    })
}

document.observe("dom:loaded", function () {
    mask();
});

MutationObserver = window.MutationObserver || window.WebKitMutationObserver;
const observerPage = new MutationObserver(function(mutationList, observer) {
    mutationList.forEach((mutation) => {
        let id = mutation.target.id;
        if (id.includes("ricardomartins_pagbank")) {
            mask();
        }
    });
});
observerPage.observe(document, {
    subtree: true,
    attributeFilter: ["disabled"],
    attributes: true
});
