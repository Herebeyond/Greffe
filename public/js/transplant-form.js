/**
 * Transplant form: conditional field visibility.
 *
 * - Graft end fields shown only when "greffon fonctionnel" is unchecked
 * - Last dialysis date shown only when "dialyse" is checked
 * - Transfusion detail fields (CGR/CPA/PFC) shown only when "transfusion" is checked (deceased donor)
 * - Donor type change triggers page reload to get correct donor sub-form
 */
document.addEventListener('DOMContentLoaded', function () {
    // Graft functional toggle
    const graftCheckbox = document.getElementById('transplant_isGraftFunctional');
    const graftEndFields = document.getElementById('graft-end-fields');

    function toggleGraftEndFields() {
        if (graftCheckbox && graftEndFields) {
            graftEndFields.style.display = graftCheckbox.checked ? 'none' : '';
        }
    }

    if (graftCheckbox) {
        graftCheckbox.addEventListener('change', toggleGraftEndFields);
        toggleGraftEndFields();
    }

    // Dialysis date toggle
    const dialysisCheckbox = document.getElementById('transplant_dialysis');
    const dialysisDateField = document.getElementById('dialysis-date-field');

    function toggleDialysisDate() {
        if (dialysisCheckbox && dialysisDateField) {
            dialysisDateField.style.display = dialysisCheckbox.checked ? '' : 'none';
        }
    }

    if (dialysisCheckbox) {
        dialysisCheckbox.addEventListener('change', toggleDialysisDate);
        toggleDialysisDate();
    }

    // Transfusion fields toggle (deceased donor form)
    const transfusionCheckbox = document.getElementById('donor_data_transfusion');
    const transfusionFields = document.getElementById('transfusion-fields');

    function toggleTransfusionFields() {
        if (transfusionCheckbox && transfusionFields) {
            transfusionFields.style.display = transfusionCheckbox.checked ? '' : 'none';
        }
    }

    if (transfusionCheckbox) {
        transfusionCheckbox.addEventListener('change', toggleTransfusionFields);
        toggleTransfusionFields();
    }

    // Donor type change -> reload page to update donor sub-form
    const donorTypeSelect = document.getElementById('transplant_donorType');
    if (donorTypeSelect) {
        donorTypeSelect.addEventListener('change', function () {
            // Submit the form to reload with proper donor sub-form
            const form = donorTypeSelect.closest('form');
            if (form) {
                form.submit();
            }
        });
    }
});
