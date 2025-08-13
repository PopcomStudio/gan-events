import * as $ from 'jquery';
import * as bootstrap from 'bootstrap/dist/js/bootstrap.bundle';

// create globals
global.$ = global.jQuery = $;
global.bootstrap = bootstrap;

// Import jQuery UI
require('jquery-ui/ui/core');
require('jquery-ui/ui/effect');
require('jquery-ui/ui/widgets/sortable');

// Tooltips
const tooltipTriggerList = document.querySelectorAll('[data-bs-tooltip="true"]')
const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl))

// Import stimulus
import * as stimulus from "./bootstrap"

// Custom JS
require('./js/form-file');

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.scss';