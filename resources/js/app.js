import './bootstrap';

window.addEventListener('keydown', (event) => {
    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        const search = document.querySelector('[data-global-search]');
        search?.focus();
    }
});

window.serviceFieldsEditor = (initialFields = [], presets = {}) => {
    let sequence = 0;

    const uniqueId = () => {
        if (window.crypto?.randomUUID) {
            return window.crypto.randomUUID();
        }

        sequence += 1;

        return `service-field-${Date.now()}-${sequence}`;
    };

    const asBoolean = (value) => value === true || value === 1 || value === '1';

    const normalizeField = (field = {}) => ({
        client_id: field.client_id || uniqueId(),
        key: field.key || '',
        label: field.label || '',
        field_type: field.field_type || 'TEXT',
        unit: field.unit || '',
        required: asBoolean(field.required),
        affects_pricing: asBoolean(field.affects_pricing),
        options_text: field.options_text || '',
        default_value: field.default_value ?? '',
        active: field.active === undefined ? true : asBoolean(field.active),
    });

    return {
        fields: Array.isArray(initialFields) ? initialFields.map(normalizeField) : [],
        presets,

        hasField(key) {
            return this.fields.some((field) => field.key === key);
        },

        addPreset(presetKey) {
            const preset = this.presets[presetKey];

            if (!preset || this.hasField(preset.key)) {
                return;
            }

            this.fields.push(normalizeField(preset));
        },

        addCustomField() {
            this.fields.push(normalizeField());

            this.$nextTick(() => {
                const inputs = document.querySelectorAll('[data-service-field-label]');
                inputs[inputs.length - 1]?.focus();
            });
        },

        removeField(index) {
            this.fields.splice(index, 1);
        },

        moveField(index, direction) {
            const target = index + direction;

            if (target < 0 || target >= this.fields.length) {
                return;
            }

            const [field] = this.fields.splice(index, 1);
            this.fields.splice(target, 0, field);
        },
    };
};
