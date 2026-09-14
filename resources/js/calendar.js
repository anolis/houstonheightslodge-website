import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import listPlugin from '@fullcalendar/list';
import luxonPlugin from '@fullcalendar/luxon3';
import '../css/calendar.css';

const element = document.querySelector('[data-lodge-calendar]');
if (element) {
    const section = element.closest('section');
    const status = section.querySelector('[data-calendar-status]');
    const dialog = section.querySelector('[data-calendar-dialog]');
    const formatDate = new Intl.DateTimeFormat('en-US', {
        timeZone: 'America/Chicago', weekday: 'long', month: 'long', day: 'numeric',
        year: 'numeric', hour: 'numeric', minute: '2-digit', timeZoneName: 'short',
    });
    section.querySelector('[data-close-event]').addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            const box = dialog.getBoundingClientRect();
            if (event.clientX < box.left || event.clientX > box.right || event.clientY < box.top || event.clientY > box.bottom) dialog.close();
        }
    });
    const narrow = window.matchMedia('(max-width: 640px)');
    const calendar = new Calendar(element, {
        plugins: [dayGridPlugin, listPlugin, luxonPlugin],
        initialView: narrow.matches ? 'listMonth' : 'dayGridMonth',
        timeZone: 'America/Chicago',
        height: 'auto',
        dayMaxEvents: 3,
        headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,listMonth' },
        buttonIcons: false,
        buttonText: { prev: 'Previous', next: 'Next', today: 'Today', month: 'Month', list: 'List' },
        noEventsContent: 'No events scheduled for this month.',
        events: async (_range, success, failure) => {
            try {
                const response = await fetch(element.dataset.feedUrl, { headers: { Accept: 'application/json' } });
                if (!response.ok) throw new Error('Event feed unavailable');
                const feed = await response.json();
                if (!Array.isArray(feed.events)) throw new Error('Invalid event feed');
                success(feed.events);
                const stale = Date.now() - Date.parse(feed.updatedAt) > 24 * 60 * 60 * 1000;
                status.textContent = stale
                    ? 'Showing our last saved events. Check Facebook for the latest changes.'
                    : 'Events update automatically from Facebook.';
            } catch (error) {
                status.textContent = 'Events are temporarily unavailable. Please use the Facebook link above.';
                failure(error);
            }
        },
        eventClick: ({ event, jsEvent }) => {
            jsEvent.preventDefault();
            dialog.querySelector('[data-event-title]').textContent = event.title;
            dialog.querySelector('[data-event-time]').textContent = formatDate.format(event.start)
                + (event.end ? ` – ${formatDate.format(event.end)}` : '');
            dialog.querySelector('[data-event-location]').textContent = event.extendedProps.location || '';
            dialog.querySelector('[data-event-description]').textContent = event.extendedProps.description || '';
            dialog.querySelector('[data-event-link]').href = event.url;
            const cover = dialog.querySelector('[data-event-cover]');
            cover.hidden = !event.extendedProps.cover;
            if (event.extendedProps.cover) cover.src = event.extendedProps.cover;
            else cover.removeAttribute('src');
            dialog.showModal();
        },
    });
    calendar.render();
    narrow.addEventListener('change', ({ matches }) => calendar.changeView(matches ? 'listMonth' : 'dayGridMonth'));
}
