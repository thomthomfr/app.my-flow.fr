import { Controller } from '@hotwired/stimulus';
import DatatablesHelper from "../../helpers/datatables";


export default class extends Controller {
  static targets = ['datatable', 'cancelCampaignForm', 'cancelCampainModal', 'cancelMissionForm', 'cancelMissionModal', 'initialTime', 'estimatedIncome', 'addTimeModal', 'productName', 'product', 'missionInitialTimeForm', 'activated'];

  connect() {
    this.table = DatatablesHelper.init(this.datatableTarget);
    this.displayDetail();
    this.displayHistorique();
      this.count = 0;
  }

  searchTable(event) {
    this.table.search(event.currentTarget.value).draw();
  }

  displayDetail(){
    var table = $('#table_order_view').DataTable();

    $('#table_order_view tbody').on('click', 'td.details-control', function () {
      var tr = $(this).closest('tr');
      var row = table.row( tr );

      if ( row.child.isShown() ) {
        row.child.hide();
        tr.removeClass('shown');
      }
      else {
        row.child(tr.attr('data-mission-information')).show();
        tr.addClass('shown');
      }
    });
  }

  displayHistorique(){
    var table = $('#table_order_historique').DataTable({ordering:false});

    $('#table_order_historique tbody').on('click', 'td.details-control', function () {
      var tr = $(this).closest('tr');
      var row = table.row( tr );

      if ( row.child.isShown() ) {
        row.child.hide();
        tr.removeClass('shown');
      }
      else {
        row.child(tr.attr('data-mission-historique')).show();
        tr.addClass('shown');
      }
    });
  }

    async changeQuantity(event){
      let url = event.currentTarget.dataset.url;
      let id = event.currentTarget.dataset.inputId;
      let campaign = event.currentTarget.dataset.campaignId;
      const input = document.getElementById(id);

      if(event.currentTarget.dataset.direction === 'down') {
          input.value = Number(input.value) - Number(1);
      }else{
          input.value = Number(input.value) + Number(1);
      }
          return fetch(url+'?quantity='+input.value, {
              method: 'GET',
              headers: {
                  'Accept':'application/json',
              },
          }).then((res) => {
              return res.json();
          }).then((data) => {
              document.getElementById('campaign-'+campaign+'-total-cost').innerHTML = data.total;
          });

  }

  openCancelCampaignModal(event){
      this.cancelCampaignFormTarget.action = event.currentTarget.dataset.url;
      var myModal = new bootstrap.Modal(document.getElementById('cancelCampaignModal'));
      myModal.show();
  }

    openCancelMissionModal(event){
        this.cancelMissionFormTarget.action = event.currentTarget.dataset.url2;
        var myModal = new bootstrap.Modal(document.getElementById('cancelMissionModal'));
        myModal.show();
    }

    addTime(event) {
        this.missionInitialTimeFormTarget.action = event.currentTarget.dataset.url;
        this.productNameTarget.innerHTML = event.currentTarget.dataset.name;
        document.getElementById('productName').innerHTML = event.currentTarget.dataset.name;

        const that = this;
        const tauxHoraire = event.currentTarget.dataset.tauxHoraire;

        this.initialTimeTarget.addEventListener('keyup', (e) => {
            if (tauxHoraire !== undefined) {
                that.estimatedIncomeTarget.value = Number(e.target.value / 420 * tauxHoraire).toFixed(2);
            }
        });

        this.estimatedIncomeTarget.addEventListener('keyup', (e) => {
            if (tauxHoraire !== undefined) {
                that.initialTimeTarget.value = Number(e.target.value * 420 / tauxHoraire).toFixed(2);
            }
        });

        $(this.addTimeModalTarget).modal('show');
    }

    activatedModal(event) {
        this.productNameTarget.innerHTML = event.currentTarget.dataset.name;

        $(this.activatedTarget).modal('show');
    }

    async changeDelais(event){
        let url = event.currentTarget.dataset.url;
        let id = event.currentTarget.dataset.inputId;
        let resumer = event.currentTarget.dataset.inputIdResumer;
        let input = document.getElementById(id);

        if (resumer !== undefined){
            input = document.getElementById(resumer);
        }else{
            input = document.getElementById(id);
        }
        return fetch(url+'?delais='+input.value, {
            method: 'GET',
            headers: {
                'Accept':'application/json',
            },
        }).then((res) => {
            return res.json();
        }).then((data) => {
            window.location = data.redirect;
        });
    }

    async changeIncome(event){
        let url = event.currentTarget.dataset.url;
        let id = event.currentTarget.dataset.inputId;
        const input = document.getElementById(id);

        return fetch(url+'?income='+input.value, {
            method: 'GET',
            headers: {
                'Accept':'application/json',
            },
        }).then((res) => {
            return res.json();
        }).then((data) => {
            return false;
        });

    }
}
