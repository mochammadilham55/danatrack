<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Mengakses halaman utama ('/') harus langsung diarahkan ke
     * halaman login, bukan menampilkan halaman welcome bawaan Laravel.
     */
    public function test_root_url_redirects_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }
}
