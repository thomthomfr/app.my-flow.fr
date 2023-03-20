import { Controller } from 'stimulus';

export default class extends Controller {
    static targets = ['form'];

    submitForm(event) {
        event.currentTarget.setAttribute("data-kt-indicator", "on");
        event.currentTarget.disabled = true;

        this.formTarget.submit();
    }
}
