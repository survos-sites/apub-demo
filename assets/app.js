import './stimulus_bootstrap.js';
import * as bootstrap from 'bootstrap';

import '@tabler/core/dist/css/tabler.min.css';
import './styles/app.css';

// Tabler's navbar dropdowns use Bootstrap's own data-bs-toggle="dropdown" JS —
// @tabler/core ships only the CSS, not the interactive JS. Exposed on window to
// match the same pattern zm uses.
window.bootstrap = bootstrap;
