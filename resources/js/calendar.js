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
    const showDetails = (event) => {
        dialog.querySelector('[data-event-title]').textContent = event.title;
        dialog.querySelector('[data-event-time]').textContent = formatDate.format(new Date(event.start))
            + (event.end ? ` – ${formatDate.format(new Date(event.end))}` : '');
        dialog.querySelector('[data-event-location]').textContent = event.extendedProps?.location || '';
        dialog.querySelector('[data-event-description]').textContent = event.extendedProps?.description || '';
        dialog.querySelector('[data-event-link]').href = event.url;
        const cover = dialog.querySelector('[data-event-cover]');
        cover.hidden = !event.extendedProps?.cover;
        if (event.extendedProps?.cover) cover.src = event.extendedProps.cover;
        else cover.removeAttribute('src');
        dialog.showModal();
    };
    const loadEvents = async () => {
        try {
            const response = await fetch(element.dataset.feedUrl, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('Event feed unavailable');
            const feed = await response.json();
            if (!Array.isArray(feed.events)) throw new Error('Invalid event feed');
            const now = Date.now();
            const upcoming = feed.events
                .filter(event => Date.parse(event.end || event.start) >= now)
                .sort((a, b) => Date.parse(a.start) - Date.parse(b.start));
            element.replaceChildren();
            for (const event of upcoming) {
                const card = document.createElement('article');
                card.className = 'lodge-event-card';
                if (event.extendedProps?.cover) {
                    const image = document.createElement('img');
                    image.src = event.extendedProps.cover;
                    image.alt = '';
                    image.loading = 'lazy';
                    card.append(image);
                }
                const body = document.createElement('div');
                body.className = 'lodge-event-card__body';
                const title = document.createElement('h3');
                title.textContent = event.title;
                const time = document.createElement('time');
                time.dateTime = event.start;
                time.textContent = formatDate.format(new Date(event.start));
                const location = document.createElement('p');
                location.textContent = event.extendedProps?.location || '';
                const details = document.createElement('button');
                details.type = 'button';
                details.textContent = 'View event details';
                details.setAttribute('aria-label', `View details for ${event.title}`);
                details.addEventListener('click', () => showDetails(event));
                body.append(title, time, location, details);
                card.append(body);
                element.append(card);
            }
            const stale = now - Date.parse(feed.updatedAt) > 24 * 60 * 60 * 1000;
            status.textContent = stale
                ? 'Showing our last saved events. Check Facebook for the latest changes.'
                : upcoming.length ? '' : 'No upcoming events announced yet. Check back soon!';
        } catch {
            status.textContent = 'Events are temporarily unavailable. Please use the Facebook link above.';
        }
    };
    loadEvents();
}
