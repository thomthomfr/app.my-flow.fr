import { Controller } from '@hotwired/stimulus';
import Select2Helper from "../../helpers/select2";

export default class extends Controller {
    static targets = ['select2', 'missionsContainer', 'newSubContractorInput'];

    connect() {
        this.initNewSubContractorInputEvent();
        if (this.hasSelect2Target) {
            this.initSelect2();
        }

        const that = this;
        this.missionsContainerTarget.addEventListener("DOMNodeInserted", function (e) {
            if (e.target.tagName === 'TR') {
                that.initSelect2();
                that.initNewSubContractorInputEvent();
            }
        });

        this.checkErrorMission();
    }

    initSelect2() {
        this.select2Targets.forEach((elem) => {
            Select2Helper.init(elem);
            $(elem).on('select2:select', function (e) {
                const price = e.params.data.element.dataset.price;
                elem.parentNode.parentNode.querySelector('.price-input').value = price;
            });
        });
    }

    initNewSubContractorInputEvent() {
        this.newSubContractorInputTargets.forEach((elem) => {
            elem.addEventListener('change', () => {
                const select = elem.parentNode.parentNode.querySelector('.select2-hidden-accessible');
                const job = select.options[select.selectedIndex].dataset.job;

                elem.parentNode.querySelector('.mt-2').value = job;
            });
        });
    }

    checkErrorMission(){
        var url_string = window.location.href;
        var url = new URL(url_string);
        var erreur = url.searchParams.get("erreur");

        if(erreur === '1'){
            var myModal = new bootstrap.Modal(document.getElementById('kt_modal_activate_panier'));
            myModal.show();
        }
    }
}
