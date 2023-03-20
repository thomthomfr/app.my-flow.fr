require('select2/dist/js/select2.min');

export default class Select2Helper {
    static init = (elem, options = {}) => {
        $.fn.select2.defaults.set("theme", "bootstrap5");
        $.fn.select2.defaults.set("width", "100%");
        $.fn.select2.defaults.set("selectionCssClass", ":all:");

        $(elem).select2(options);
        $(elem).on('select2:select', function() {
            let event = new Event('change', { bubbles: true })
            elem.dispatchEvent(event)
        })
    }
}
