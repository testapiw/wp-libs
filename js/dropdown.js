!(function(){
    window.wplibs = window.wplibs || {};
    window.wplibs.getDropdown = () => {
        return {
            data() {
                return {
                    valueMap: {},
                }
            },
            props: {
                list: { type: Array, default: () => [] },
                modelValue: { type: String, default: null },
                labelKey: { type: String, default: 'value' },
                valueKey: { type: String, default: 'id' },
                placeholder: { type: String, default: 'Select an option' },
                usebadge: { type: Boolean, default: false }
            },
            emits: ['update:modelValue'],
            computed: {
                selectedLabel() {
                    const item = this.valueMap[ this.modelValue ];
                    return item ? (typeof item === 'object' ? item[this.labelKey] : item) : null;
                }
            },
            created() {
                this.indexList();
            },
            methods: {
                indexList() {
                    this.valueMap = {};
                    for (const item of this.list) {
                        const key = typeof item === 'object' ? item[this.valueKey] : item;
                        this.valueMap[key] = item;
                    }
                },
                getItemLabel(item) {
                    return typeof item === 'object' ? item[this.labelKey] : item;
                },
                getItemValue(item) {
                    return typeof item === 'object' ? item[this.valueKey] : item;
                },
                getItemKey(item) {
                    return this.getItemValue(item);
                },
                selectItem(item) {
                    this.$emit('update:modelValue', this.getItemValue(item));
                    this.$emit('status-changed', this.getItemValue(item)); 
                },
                getColor(item) {
                    return typeof item === 'object' && item && item.color ? `badge me-2 bg-${item.color}` : ''; // Assuming 'color' is a property of each item
                },
                getButtonClass() {
                    if (!this.usebadge) return;

                    const item = this.valueMap[ this.modelValue ];
                    if (item && item.color) {
                        return `badge bg-${item.color}`;
                    } else {
                        return 'badge bg-primary';
                    }
                }
            },
            template: '#dropdown-template'
        }
    }
})();