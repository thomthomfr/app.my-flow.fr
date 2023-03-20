import { Controller } from 'stimulus';

export default class extends Controller {
    static targets = [
      'requestForm',
      'resetForm',
    ];

    submitRequestForm(event) {
        event.currentTarget.setAttribute("data-kt-indicator", "on");
        event.currentTarget.disabled = true;

        this.requestFormTarget.submit();
    }

    submitResetForm(event) {
        event.currentTarget.setAttribute("data-kt-indicator", "on");
        event.currentTarget.disabled = true;

        this.resetFormTarget.submit();
    }
}
