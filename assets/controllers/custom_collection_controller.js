import { Controller } from 'stimulus';
const $ = require('jquery');
import 'jquery-datetimepicker'

export default class extends Controller {

  connect() {
    this.element.addEventListener('ux-collection:connect', this._onConnect);
    this.element.addEventListener('ux-collection:add', this._onAdd);
  }

  _onConnect(e) {
    const $this = $(e.target);

    $this.find('input[name*="endAt"], input[name*="startAt"]').datetimepicker({
      format: "d/m/Y H:i",
      step: 15
    })
  }

  _onAdd(e) {
    const $this = $(e.target);

    $this.find('input[name*="endAt"], input[name*="startAt"]').datetimepicker({
      format: "d/m/Y H:i",
      step: 15
    })
  }
}