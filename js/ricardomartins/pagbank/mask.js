function mask() {
    const masks = {
        date (value) {
            return value
                .replace(/\D+/g, '')
                .replace(/(\d{2})(\d)/, '$1/$2')
                .replace(/(\/\d{2})\d+?$/, '$1')
        },
        document (value) {
            if (value.length > 14) {
                return value
                    .replace(/\D+/g, '')
                    .replace(/(\d{2})(\d)/, '$1.$2')
                    .replace(/(\d{3})(\d)/, '$1.$2')
                    .replace(/(\d{3})(\d)/, '$1/$2')
                    .replace(/(\d{4})(\d)/, '$1-$2')
                    .replace(/(-\d{2})\d+?$/, '$1')
            }
            return value
                .replace(/\D+/g, '')
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
const observer = new MutationObserver(function(mutations, observer) {
    mask();
});
observer.observe(document, {
    subtree: true,
    attributes: true
});