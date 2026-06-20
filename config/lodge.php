<?php

return [
    // Self-links to the lodge's own web properties. Defaults are production;
    // override per-environment via .env (LODGE_SITE_URL / LODGE_PORTAL_URL / LODGE_FORUM_URL).
    "site_url"   => env("LODGE_SITE_URL",   "https://www.houstonheightslodge225.com"),
    "portal_url" => env("LODGE_PORTAL_URL", "https://secret.houstonheightslodge225.com"),
    "forum_url"  => env("LODGE_FORUM_URL",  "https://forum.houstonheightslodge225.com"),
];
