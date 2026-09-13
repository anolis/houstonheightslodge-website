<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    public function test_public_pages_render(): void
    {
        foreach (['/', '/home', '/about', '/scholarships', '/building-history', '/volunteering', '/rent', '/memory-game'] as $path) {
            $this->get($path)
                ->assertOk()
                ->assertSee('Houston Heights Lodge #225');
        }
    }

    public function test_unknown_page_returns_not_found(): void
    {
        $this->get('/definitely-not-a-lodge-page')->assertNotFound();
    }

    public function test_national_night_out_page_and_lowercase_alias(): void
    {
        $this->get('/NNO2026')
            ->assertOk()
            ->assertSee('National Night Out 2026')
            ->assertSee('Tuesday, October 6, 2026')
            ->assertSee('5:30 PM &ndash; 8:00 PM', false)
            ->assertSee('115 E. 14th Street')
            ->assertSee('Enjoy free ice cream and win a door prize!')
            ->assertSee('res/img/nno-2026.png', false)
            ->assertSee('res/img/nno-logo-2026.jpg', false)
            ->assertSee('<meta property="og:image" content="http://localhost/res/img/nno-2026.png">', false);

        $this->get('/nno2026')->assertStatus(301)->assertRedirect('/NNO2026');
        $this->get('/')->assertSee('href="http://localhost/NNO2026"', false)
            ->assertSee('Sausage Fest 2026');
    }

    public function test_homepage_features_sausage_fest_2026(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Saturday, September 26th at the Heights Odd Fellows Lodge!')
            ->assertSee('Everyone is invited to join us!')
            ->assertSee('Carnivore sausage sampler plate')
            ->assertSee('View the Facebook event')
            ->assertSee('href="https://www.facebook.com/share/1JyvvFkbj1/"', false)
            ->assertSee('res/img/sausagefest-2026.webp', false)
            ->assertSee('res/img/sausagefest-2026.png', false)
            ->assertSee('<meta property="og:image" content="http://localhost/res/img/sausagefest-2026.png">', false);
    }

    public function test_members_page_redirects_to_the_private_portal(): void
    {
        $this->get('/members')->assertRedirect('https://secret.houstonheightslodge225.com/');
    }

    public function test_downloads_page_renders_when_no_apks_are_present(): void
    {
        $this->get('/downloads')->assertOk()->assertSee('Downloads');
    }

    public function test_download_route_rejects_non_apk_names(): void
    {
        $this->get('/downloads/config.php')->assertNotFound();
        $this->get('/downloads/../.env')->assertNotFound();
    }

    public function test_health_endpoint_is_available(): void
    {
        $this->get('/up')->assertOk();
    }
}
