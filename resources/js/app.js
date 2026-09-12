import Alpine from 'alpinejs';
import { clubShell } from './club';
import { calendarPage } from './calendar';
import { bookingForm } from './booking-form';
import { reportsPage } from './reports';
import { auditTable } from './audit';

window.Alpine = Alpine;
Alpine.data('clubShell', clubShell);
Alpine.data('calendarPage', calendarPage);
Alpine.data('bookingForm', bookingForm);
Alpine.data('reportsPage', reportsPage);
Alpine.data('auditTable', auditTable);
Alpine.start();
