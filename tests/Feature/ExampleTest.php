<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Astegni API is running',
                     'version' => '1.0.0'
                 ]);
    }

    public function test_the_hello_endpoint_returns_success(): void
    {
        $response = $this->get('/api/hello');

        $response->assertStatus(200)
                 ->assertJson([
                     "success" => true,
                     "application" => "Astegni API",
                     "version" => "1.0",
                     "message" => "Welcome to the Astegni Backend API"
                 ]);
    }

    public function test_the_health_endpoint_returns_success(): void
    {
        $response = $this->get('/api/health');

        $response->assertStatus(200)
                 ->assertJson([
                     "status" => "online",
                     "server" => "Laravel",
                     "api" => "Astegni",
                     "version" => "1.0"
                 ]);
    }

    public function test_the_grades_endpoint_returns_success(): void
    {
        $response = $this->get('/api/grades');
        $response->assertStatus(200);
        $response->assertJsonFragment(["name" => "Grade 9"]);
    }

    public function test_the_subjects_endpoint_returns_success(): void
    {
        $response = $this->get('/api/subjects');
        $response->assertStatus(200);
        $response->assertJsonFragment(["name" => "Mathematics"]);
    }

    public function test_the_tutors_endpoint_returns_success(): void
    {
        $response = $this->get('/api/tutors');
        $response->assertStatus(200);
        $response->assertJsonFragment(["name" => "Abebe Kebede"]);
    }
}
