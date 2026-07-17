<style>
    .form-label.crm-required-label::after {
        color: #dc3545;
        content: " *";
        font-weight: 700;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        function hasVisibleRequiredMarker(label) {
            if (!label) {
                return true;
            }

            return label.classList.contains('crm-required-label')
                || label.querySelector('.required-star, .text-danger')
                || label.textContent.includes('*');
        }

        function fieldLabel(field) {
            if (field.id) {
                const explicitLabel = document.querySelector('label[for="' + CSS.escape(field.id) + '"]');
                if (explicitLabel) {
                    return explicitLabel;
                }
            }

            const wrapper = field.closest('.mb-3, .form-group, .col, [class*="col-"], .modal-body, form');
            return wrapper ? wrapper.querySelector('.form-label, label') : null;
        }

        function markRequiredLabels(root) {
            root.querySelectorAll('input[required], select[required], textarea[required]').forEach(function (field) {
                if (field.type === 'hidden' || field.disabled) {
                    return;
                }

                const label = fieldLabel(field);

                if (!hasVisibleRequiredMarker(label)) {
                    label.classList.add('crm-required-label');
                }
            });
        }

        markRequiredLabels(document);

        const observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (node.nodeType === Node.ELEMENT_NODE) {
                        markRequiredLabels(node);
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
