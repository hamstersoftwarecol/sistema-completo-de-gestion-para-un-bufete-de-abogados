import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';
import interactionPlugin from '@fullcalendar/interaction';
import esLocale from '@fullcalendar/core/locales/es';

/**
 * Inicializa el calendario judicial.
 */
window.initLegalCalendar = (el, { eventsUrl, onDateClick, filters }) => {
    const calendar = new Calendar(el, {
        plugins: [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin],
        locale: esLocale,
        initialView: window.innerWidth < 768 ? 'listWeek' : 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek',
        },
        height: 'auto',
        nowIndicator: true,
        dayMaxEvents: 3,
        eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
        slotMinTime: '07:00:00',
        slotMaxTime: '20:00:00',
        events: (info, success, failure) => {
            window.axios.get(eventsUrl, { params: { start: info.startStr, end: info.endStr, ...filters() } })
                .then(({ data }) => success(data))
                .catch(failure);
        },
        dateClick: (info) => onDateClick(info.dateStr, info.allDay),
        eventDidMount: (info) => {
            const p = info.event.extendedProps;
            info.el.title = [p.kind + ' — ' + p.status, p.client, p.location, p.lawyer].filter(Boolean).join('\n');
        },
    });

    calendar.render();

    return calendar;
};
