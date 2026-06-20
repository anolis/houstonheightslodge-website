<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

$pages = [
    'home' => [
        'label' => 'Home',
        'title' => 'Houston Heights Lodge #225',
        'description' => 'The Houston Heights Lodge #225, an Independent Order of Odd Fellows lodge in the Houston Heights neighborhood of Houston, TX.',
        'nav' => true,
    ],
    'about' => [
        'label' => 'About',
        'title' => 'About | Houston Heights Lodge #225',
        'description' => 'Learn about the Independent Order of Odd Fellows and Houston Heights Lodge #225.',
        'nav' => true,
    ],
    'scholarships' => [
        'label' => 'Scholarships',
        'title' => 'Scholarships | Houston Heights Lodge #225',
        'description' => 'Scholarship opportunities available through Houston Heights Odd Fellows Lodge #225.',
        'nav' => true,
    ],
    'building-history' => [
        'label' => 'Building History',
        'title' => 'Building History | Houston Heights Lodge #225',
        'description' => 'The history of the Houston Heights Odd Fellows Lodge #225 building, a historic landmark.',
        'nav' => true,
    ],
    'volunteering' => [
        'label' => 'Volunteering',
        'title' => 'Volunteering | Houston Heights Lodge #225',
        'description' => 'Volunteer with Houston Heights Lodge #225 and give back to the Houston Heights community.',
        'nav' => true,
    ],
    'rent' => [
        'label' => 'Rent The Lodge',
        'title' => 'Rent The Lodge | Houston Heights Lodge #225',
        'description' => 'Rent the Houston Heights Odd Fellows Lodge #225 for your next event, class, or private party.',
        'nav' => true,
    ],
    'memory-game' => [
        'label' => 'Memory Game',
        'title' => 'Memory Game | Houston Heights Lodge #225',
        'description' => 'Play the Houston Heights Lodge #225 memory game.',
        'nav' => true,
    ],
    'chili-cookoff' => ['label' => 'Chili Cookoff', 'title' => 'Chili Cookoff | Houston Heights Lodge #225'],
    'contact' => ['label' => 'Contact', 'title' => 'Contact | Houston Heights Lodge #225'],
    'events' => ['label' => 'Events', 'title' => 'Events | Houston Heights Lodge #225'],
    'members' => ['label' => 'Members Area', 'title' => 'Members Area | Houston Heights Lodge #225'],
    'sausagefest' => ['label' => 'Sausagefest', 'title' => 'Sausagefest | Houston Heights Lodge #225'],
    'sausagequest' => ['label' => 'Sausagequest', 'title' => 'Sausagequest | Houston Heights Lodge #225'],
    'sponsors' => ['label' => 'Sponsors', 'title' => 'Sponsors | Houston Heights Lodge #225'],
];

$navPages = fn () => collect($pages)->filter(fn ($item) => $item['nav'] ?? false)->all();
$downloadsPath = fn (?string $file = null) => base_path('downloads'.($file ? DIRECTORY_SEPARATOR.$file : ''));

$renderPage = function (string $page) use ($pages, $navPages) {
    abort_unless(isset($pages[$page]) && view()->exists('pages.'.$page), 404);

    $meta = $pages[$page];

    return view('page', [
        'page' => $page,
        'title' => $meta['title'] ?? Str::headline($page).' | Houston Heights Lodge #225',
        'description' => $meta['description'] ?? 'Houston Heights Lodge #225 information and updates.',
        'navPages' => $navPages(),
    ]);
};

Route::get('/', fn () => $renderPage('home'));
Route::get('/home', fn () => $renderPage('home'));

Route::get('/downloads', function () use ($navPages, $downloadsPath) {
    $apks = collect(glob($downloadsPath('*.apk')) ?: [])
        ->map(function ($path) {
            return [
                'name' => basename($path),
                'mb' => round(filesize($path) / 1048576, 1),
                'modified' => date('F j, Y', filemtime($path)),
                'url' => url('/downloads/'.rawurlencode(basename($path))),
            ];
        })
        ->sortBy('name')
        ->values()
        ->all();

    return view('downloads.index', [
        'page' => 'downloads',
        'title' => 'Downloads | Houston Heights Lodge #225',
        'description' => 'Downloads for Houston Heights Lodge #225.',
        'navPages' => $navPages(),
        'apks' => $apks,
    ]);
});

Route::get('/downloads/{filename}', function (string $filename) use ($downloadsPath) {
    abort_unless(preg_match('/^[A-Za-z0-9._-]+\.apk$/', $filename), 404);

    $path = $downloadsPath($filename);
    abort_unless(is_file($path), 404);

    return response()->download($path, $filename, [
        'Content-Type' => 'application/vnd.android.package-archive',
    ]);
});

Route::get('/{page}', fn (string $page) => $renderPage($page))->where('page', '[A-Za-z0-9-]+');
