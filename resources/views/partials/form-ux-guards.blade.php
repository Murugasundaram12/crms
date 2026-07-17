<script>
    document.addEventListener('DOMContentLoaded', function () {
        const indianPhoneSelector = [
            'input[name="phone"]',
            'input[name="phone_number"]'
        ].join(',');

        function prepareIndianPhoneField(field) {
            if (field.readOnly || field.disabled) {
                return;
            }

            field.setAttribute('type', 'tel');
            field.setAttribute('inputmode', 'numeric');
            field.setAttribute('maxlength', '10');
            field.setAttribute('pattern', '[6-9][0-9]{9}');
            field.setAttribute('title', 'Enter a valid 10 digit Indian mobile number');

            field.value = String(field.value || '').replace(/\D/g, '').slice(0, 10);

            if (field.dataset.crmIndianPhoneBound === '1') {
                return;
            }

            field.dataset.crmIndianPhoneBound = '1';
            field.addEventListener('input', function () {
                field.value = field.value.replace(/\D/g, '').slice(0, 10);
            });
        }

        function prepareFormFields(root) {
            root.querySelectorAll(indianPhoneSelector).forEach(prepareIndianPhoneField);
        }

        prepareFormFields(document);

        const observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (node.nodeType === Node.ELEMENT_NODE) {
                        prepareFormFields(node);
                    }
                });
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    });
</script>
