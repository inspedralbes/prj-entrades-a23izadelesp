<?php

namespace Database\Seeders;

use App\Models\AppSession;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Usuario de prueba
        User::factory()->create([
            'name' => 'Izan de la Cruz',
            'email' => 'test@queuely.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        // Evento de Cine
        $movie = Event::create([
            'title' => 'Interstellar',
            'description' => 'Un grupo de exploradores viaja a través de un agujero de gusano en el espacio.',
            'type' => 'movie',
            'image' => null,
        ]);

        // Sesiones de Cine (grid 10x15)
        $gridLayout = [];
        for ($r = 0; $r < 10; $r++) {
            $row = [];
            for ($c = 0; $c < 15; $c++) {
                $row[] = ($c === 7) ? 0 : 1; // Pasillo en columna 7
            }
            $gridLayout[] = $row;
        }

        AppSession::create([
            'event_id' => $movie->id,
            'date' => '2026-04-01',
            'time' => '18:00',
            'price' => 9.50,
            'venue_config' => ['type' => 'grid', 'rows' => 10, 'cols' => 15, 'layout' => $gridLayout],
        ]);

        AppSession::create([
            'event_id' => $movie->id,
            'date' => '2026-04-01',
            'time' => '21:00',
            'price' => 11.00,
            'venue_config' => ['type' => 'grid', 'rows' => 10, 'cols' => 15, 'layout' => $gridLayout],
        ]);

        // Evento de Concierto
        $concert = Event::create([
            'title' => 'Bad Bunny - Most Wanted Tour',
            'description' => 'El artista más escuchado del mundo en una noche inolvidable.',
            'type' => 'concert',
            'image' => null,
        ]);

        // Sesiones de Concierto (zonas)
        AppSession::create([
            'event_id' => $concert->id,
            'date' => '2026-04-15',
            'time' => '21:30',
            'price' => null,
            'venue_config' => [
                'type' => 'zones',
                'zones' => [
                    ['id' => 'pista', 'name' => 'Pista', 'capacity' => 500, 'price' => 45.00, 'color' => '#10B981'],
                    ['id' => 'grada_izq', 'name' => 'Grada Izquierda', 'capacity' => 200, 'price' => 60.00, 'color' => '#F59E0B'],
                    ['id' => 'grada_der', 'name' => 'Grada Derecha', 'capacity' => 200, 'price' => 60.00, 'color' => '#F59E0B'],
                    ['id' => 'vip', 'name' => 'VIP', 'capacity' => 50, 'price' => 120.00, 'color' => '#EF4444'],
                ],
            ],
        ]);

        // Segundo evento de cine
        $movie2 = Event::create([
            'title' => 'Dune: Part Three',
            'description' => 'La épica conclusión de la saga de Frank Herbert.',
            'type' => 'movie',
            'image' => null,
        ]);

        AppSession::create([
            'event_id' => $movie2->id,
            'date' => '2026-04-02',
            'time' => '19:30',
            'price' => 10.00,
            'venue_config' => ['type' => 'grid', 'rows' => 10, 'cols' => 15, 'layout' => $gridLayout],
        ]);

        // Segundo evento de concierto
        $concert2 = Event::create([
            'title' => 'Rosalma - Motomami World Tour',
            'description' => 'La artista española que conquistó el mundo.',
            'type' => 'concert',
            'image' => null,
        ]);

        AppSession::create([
            'event_id' => $concert2->id,
            'date' => '2026-04-20',
            'time' => '22:00',
            'price' => null,
            'venue_config' => [
                'type' => 'zones',
                'zones' => [
                    ['id' => 'pista', 'name' => 'Pista', 'capacity' => 400, 'price' => 55.00, 'color' => '#10B981'],
                    ['id' => 'preferente', 'name' => 'Preferente', 'capacity' => 150, 'price' => 80.00, 'color' => '#F59E0B'],
                    ['id' => 'vip', 'name' => 'VIP', 'capacity' => 30, 'price' => 150.00, 'color' => '#EF4444'],
                ],
            ],
        ]);
    }
}
