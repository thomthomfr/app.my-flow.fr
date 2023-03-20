export default class FormsHelper {
    static validate = (form, fields) => {
        return FormValidation.formValidation(
            form,
            {
                fields: fields,
                plugins: {
                    trigger: new FormValidation.plugins.Trigger(),
                    bootstrap: new FormValidation.plugins.Bootstrap5()
                }
            }
        ).on('core.field.invalid', function(event) {
            let id = document.getElementsByName(event)[0].getAttribute('id');
            $($('#'+id).parents().get(0)).find('.invalid-feedback').remove();
        }).on('core.element.validated', function(e) {
            // e.element presents the field element
            // e.valid indicates the field is valid or not
            if (e.valid) {
                // Remove has-success from the container
                const groupEle = FormValidation.utils.closest(e.element, '.form-group');
                if (groupEle) {
                    FormValidation.utils.classSet(groupEle, {
                        'has-success': false,
                    });
                }

                // Remove is-valid from the element
                FormValidation.utils.classSet(e.element, {
                    'is-valid': false,
                });
            }
        });
    }
}
