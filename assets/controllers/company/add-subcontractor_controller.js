import { Controller } from '@hotwired/stimulus';
import Select2Helper from "../../helpers/select2";

export default class extends Controller {
    static targets = ['select2'];

    connect() {
        if (this.hasSelect2Target) {
            this.initSelect2();
        }
    }

    initSelect2() {
        this.select2Targets.forEach((elem) => {
            Select2Helper.init(elem);
        });
    }
}
