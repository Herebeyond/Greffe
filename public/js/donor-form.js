/**
 * Donor form: conditional field visibility.
 *
 * - Living donor fields shown only when donorType === 'living'
 * - Deceased donor fields shown only when donorType === 'deceased_encephalic' or 'deceased_cardiac_arrest'
 * - Transfusion detail fields (CGR/CPA/PFC) shown only when transfusion checkbox is checked
 */
document.addEventListener('DOMContentLoaded', function () {
    const donorTypeSelect = document.getElementById('donor_donorType');
    const livingFields = document.getElementById('living-donor-fields');
    const deceasedFields = document.getElementById('deceased-donor-fields');

    function toggleDonorTypeFields() {
        if (!donorTypeSelect) return;

        const value = donorTypeSelect.value;
        const isLiving = value === 'living';
        const isDeceased = value === 'deceased_encephalic' || value === 'deceased_cardiac_arrest';

        if (livingFields) {
            livingFields.style.display = isLiving ? '' : 'none';
        }
        if (deceasedFields) {
            deceasedFields.style.display = isDeceased ? '' : 'none';
        }
    }

    if (donorTypeSelect) {
        donorTypeSelect.addEventListener('change', toggleDonorTypeFields);
        toggleDonorTypeFields();
    }

    // Transfusion fields toggle
    const transfusionCheckbox = document.getElementById('donor_transfusion');
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
});
