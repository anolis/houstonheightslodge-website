<section class="lodge-calendar" aria-labelledby="calendar-title">
    <div class="lodge-calendar__heading">
        <div>
            <h2 id="calendar-title">What’s happening at the lodge</h2>
            <p>Join us at an upcoming event. All times are Houston time.</p>
        </div>
        <a href="https://www.facebook.com/OddFellowsLodge225/events/" target="_blank" rel="noopener noreferrer">Follow on Facebook</a>
    </div>
    <p data-calendar-status role="status">Loading events…</p>
    <div data-lodge-calendar data-feed-url="{{ url('/events/feed') }}"></div>
    <noscript><p><a href="https://www.facebook.com/OddFellowsLodge225/events/">View upcoming events on Facebook</a>.</p></noscript>
    <dialog class="lodge-calendar__dialog" data-calendar-dialog aria-labelledby="calendar-event-title">
        <button type="button" data-close-event aria-label="Close event details">Close &times;</button>
        <h3 id="calendar-event-title" data-event-title></h3>
        <p data-event-time></p>
        <p data-event-location></p>
        <img data-event-cover alt="" hidden>
        <p data-event-description></p>
        <a data-event-link target="_blank" rel="noopener noreferrer">View event &amp; RSVP on Facebook</a>
    </dialog>
</section>
@push('scripts')
    @vite('resources/js/calendar.js')
@endpush
