import { Controller } from 'stimulus';

export default class extends Controller {
  static targets = ['form','scrollChat','filesInput','filesNameSpan'];

  connect() {
    this.scrollAuto();
    // this.initValidation();

    this.filesInputTarget.addEventListener('change', () => {
        this.filesNameSpanTarget.innerHTML = null;
        let content = '<p>';
        for (let i = 0; i < this.filesInputTarget.files.length; i++) {
            content = content + `
                  <i class="fas fa-paperclip"></i> ${this.filesInputTarget.files[i].name}<br>
            `;
        }
        content = content + '</p>';
        this.filesNameSpanTarget.innerHTML = content;
    });
  }

  scrollAuto(){
    let divElement = this.scrollChatTarget;
    divElement.scroll({
      top: divElement.scrollHeight,
      behavior: 'smooth'
    });
  }

  initValidation() {
    // validation du formulaire
    this.validator = FormValidation.formValidation(
      this.formTarget,
      {
        fields: {
          'message[content]': {
            validators: {
              notEmpty: {
                message: 'Ce champs est requis'
              },
            }
          },
        },
        plugins: {
          trigger: new FormValidation.plugins.Trigger(),
          bootstrap: new FormValidation.plugins.Bootstrap5()
        }
      }
    ).on('core.field.invalid', function(event) {
      let id = document.getElementsByName(event)[0].getAttribute('id');
      $($('#'+id).parents().get(0)).find('.invalid-feedback').remove();
    });
  }

  submitForm(event) {
    let that = this;

    // On ne soumet le formulaire que s'il passe la validation
    this.validator.validate().then(function (status) {
      if (status == 'valid') {
        // On active l'animation sur le bouton de submit et on le disabled
        // pour éviter les double click
        event.currentTarget.setAttribute("data-kt-indicator", "on");
        event.currentTarget.disabled = true;

        // On envoie le formulaire
        that.formTarget.submit();
      }
    });
  }
}
