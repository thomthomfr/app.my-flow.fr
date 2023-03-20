import tinymce from "tinymce";

export default class TinyMCEHelper {
    static init = (elem, opts = {}) => {
        const defaults = {
            selector: '#' + elem.getAttribute('id'),
            menubar: false,
            plugins: 'link',
            toolbar: ["styleselect fontselect fontsizeselect",
                "undo redo | cut copy paste | bold italic underline | alignleft aligncenter alignright alignjustify | link"],
        };
        const options = {...defaults, ...opts};

        tinymce.remove('#' + elem.getAttribute('id'));
        tinymce.init(options);
    }
}
