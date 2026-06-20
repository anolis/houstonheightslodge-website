<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? "Houston Heights Lodge #225" }}</title>
    <meta name="description" content="{{ $description ?? "The home page of Houston Heights Lodge #225" }}">
    <meta name="keywords" content="IOOF OddFellows">
    <meta property="og:image" content="{{ asset("res/img/links_FLT.png") }}">
    <meta property="og:url" content="{{ url($page === "home" ? "/" : "/" . $page) }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $title ?? "Houston Heights Lodge #225" }}">
    <meta property="og:description" content="{{ $description ?? "The home page of Houston Heights Lodge #225" }}">
    <meta property="fb:app_id" content="965766727408385">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

    <link rel="apple-touch-icon" sizes="16x16" href="{{ asset("res/img/favicon/favicon-16x16.png") }}">
    <link rel="apple-touch-icon" sizes="32x32" href="{{ asset("res/img/favicon/favicon-32x32.png") }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset("res/img/favicon/apple-touch-icon.png") }}">
    <link rel="manifest" href="{{ asset("res/img/favicon/manifest.json") }}">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="theme-color" content="#ffffff">

    <script type="application/ld+json">
    {
      "@@context": "https://schema.org",
      "@@type": "Organization",
      "name": "Houston Heights Lodge #225",
      "alternateName": "Houston Heights Odd Fellows Lodge #225",
      "url": "{{ config('lodge.site_url') }}",
      "logo": "{{ config('lodge.site_url') }}/res/img/links_FLT.png",
      "email": "marilybbrooks@gmail.com",
      "address": {
        "@@type": "PostalAddress",
        "streetAddress": "115 E. 14th St.",
        "addressLocality": "Houston",
        "addressRegion": "TX",
        "postalCode": "77008",
        "addressCountry": "US"
      },
      "sameAs": [
        "https://www.facebook.com/OddFellowsLodge225"
      ]
    }
    </script>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link href="{{ asset("res/libs/lightbox/css/lightbox.css") }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset("res/css/main.css") }}">
    @stack("head")
</head>
<body>
<nav class="navbar navbar-expand-md navbar-dark sticky-top" id="mainNav">
    <div class="container-fluid">
        <a class="navbar-brand" href="{{ url("/home") }}" data-home-url="{{ url("/home") }}" data-downloads-url="{{ url("/downloads") }}">Houston Heights Lodge #225</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu" aria-controls="navMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="navMenu">
            <ul class="navbar-nav">
                @foreach ($navPages as $slug => $item)
                    <li class="nav-item">
                        <a class="{{ $page === $slug ? "nav-link active" : "nav-link" }}" href="{{ url("/" . $slug) }}" aria-current="{{ $page === $slug ? "page" : "false" }}">{{ $item["label"] }}</a>
                    </li>
                @endforeach
                <li class="nav-item">
                    <a class="nav-link" href="{{ config('lodge.portal_url') }}/" target="_blank" rel="noopener">Members</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<main class="container content" id="contentContainer" tabindex="-1" role="main">
    @yield("content")
</main>

<footer id="siteFooter">
    <div class="container">
        <div class="row">
            <div class="col-12 col-md-4 mb-3">
                <h6>Houston Heights Lodge #225</h6>
                <p>Independent Order of Odd Fellows</p>
                <p>115 E. 14th St.<br>Houston, TX 77008</p>
            </div>
            <div class="col-12 col-md-4 mb-3">
                <h6>Contact</h6>
                <p>
                    <a href="mailto:marilybbrooks@gmail.com">Email Us</a><br>
                    <a href="https://www.facebook.com/OddFellowsLodge225" target="_blank" rel="noopener">Facebook</a>
                </p>
            </div>
            <div class="col-12 col-md-4 mb-3">
                <h6>Meet &amp; Greet</h6>
                <p>First Tuesday of each month<br>7:30 PM - open to the public</p>
            </div>
        </div>
        <div class="row">
            <div class="col-12 text-center">
                <small>&copy; <span id="footerYear">{{ date("Y") }}</span> Houston Heights Lodge #225 &middot; Independent Order of Odd Fellows</small>
            </div>
        </div>
    </div>
</footer>

<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset("res/libs/lightbox/js/lightbox.js") }}"></script>
<script src="{{ asset("res/js/main.js") }}"></script>
<script>
(function () {
    var brand = document.querySelector(".navbar-brand");
    if (!brand) return;

    var taps = 0;
    var timer = null;
    var homeUrl = brand.getAttribute("data-home-url") || brand.getAttribute("href") || "/home";
    var downloadsUrl = brand.getAttribute("data-downloads-url") || "/downloads";

    brand.addEventListener("click", function (event) {
        event.preventDefault();
        taps++;

        if (timer) {
            clearTimeout(timer);
        }

        if (taps >= 5) {
            taps = 0;
            window.location.href = downloadsUrl;
            return;
        }

        timer = setTimeout(function () {
            taps = 0;
            window.location.href = homeUrl;
        }, 450);
    });
})();
</script>
<script async src="https://www.googletagmanager.com/gtag/js?id=UA-162033247-1"></script>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag("js", new Date());
gtag("config", "UA-162033247-1", { page_path: window.location.pathname });
</script>
@stack("scripts")
</body>
</html>
