import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
	static targets = ["url"];
	connect(){
		this.element.addEventListener('click',(event)=>{
			const options = {
			  private: true,
			};
			//window.open(this.urlTarget.value, '_blank', Object.entries(options).map(([k, v]) => `${k}=${v}`).join(','));
			window.open(this.urlTarget.value, '_blank');
		})
	}
}
