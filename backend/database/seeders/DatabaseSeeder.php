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
        User::updateOrCreate(['email' => 'test@queuely.com'], [
            'name' => 'Izan de la Cruz',
            'email' => 'test@queuely.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        Event::query()->delete();

        $gridLayout = $this->buildGridLayout(12, 16, [7, 8]);

        $this->createMovieEvent(
            title: 'Interstellar',
            description: 'En un futuro cercano marcado por la escasez de recursos y el colapso agrícola, un antiguo piloto de pruebas recibe una misión imposible: cruzar un agujero de gusano para encontrar un nuevo hogar para la humanidad. A medida que avanza la expedición, los límites entre ciencia, tiempo y memoria familiar se difuminan en una aventura emocional de gran escala. Interstellar combina exploración espacial, decisiones morales y un viaje íntimo sobre el amor, la pérdida y la esperanza.',
            image: 'https://images.unsplash.com/photo-1462331940025-496dfbfc7564',
            sessions: [
                ['date' => '2026-05-10', 'time' => '18:00', 'price' => 9.90],
                ['date' => '2026-05-10', 'time' => '21:30', 'price' => 11.50],
                ['date' => '2026-05-11', 'time' => '20:00', 'price' => 10.50],
            ],
            gridLayout: $gridLayout,
        );

        $this->createMovieEvent(
            title: 'Dune: Part Three',
            description: 'La guerra por Arrakis entra en su fase decisiva. Paul Atreides debe equilibrar su destino político con las consecuencias personales de convertirse en símbolo de una revolución imparable. Entre alianzas frágiles, traiciones cortesanas y una lucha feroz por el control de la especia, la película expande su universo con batallas monumentales y un conflicto profundamente humano sobre liderazgo, fe y sacrificio.',
            image: 'https://images.unsplash.com/photo-1477209472048-9d96fefb4e8f',
            sessions: [
                ['date' => '2026-05-12', 'time' => '19:00', 'price' => 10.00],
                ['date' => '2026-05-12', 'time' => '22:15', 'price' => 11.80],
                ['date' => '2026-05-13', 'time' => '18:30', 'price' => 9.80],
            ],
            gridLayout: $gridLayout,
        );

        $this->createMovieEvent(
            title: 'La Ciudad de Cristal',
            description: 'Un thriller de ciencia ficción ambientado en una metrópolis donde cada recuerdo puede comprarse, editarse o borrarse. Una inspectora de delitos neurodigitales investiga una serie de desapariciones vinculadas a una empresa que promete reescribir el pasado de sus clientes. Con una atmósfera noir futurista, la historia mezcla acción, misterio y dilemas éticos sobre identidad, verdad y libertad personal.',
            image: 'https://images.unsplash.com/photo-1519608487953-e999c86e7455',
            sessions: [
                ['date' => '2026-05-14', 'time' => '17:45', 'price' => 8.90],
                ['date' => '2026-05-14', 'time' => '20:30', 'price' => 10.40],
                ['date' => '2026-05-15', 'time' => '22:00', 'price' => 10.90],
            ],
            gridLayout: $gridLayout,
        );

        $this->createConcertEvent(
            title: 'Bad Bunny - Most Wanted Tour',
            description: 'Una producción de gran formato con visuales inmersivos, banda en directo y un setlist que recorre sus mayores éxitos y colaboraciones más icónicas. El show fusiona trap, reggaetón y sonidos caribeños en una experiencia energética pensada para estadios. Luces, escenografía móvil y narrativa audiovisual convierten cada bloque en un viaje sonoro que mantiene la intensidad de principio a fin.',
            image: 'https://images.unsplash.com/photo-1501386761578-eac5c94b800a',
            sessions: [
                ['date' => '2026-06-02', 'time' => '21:30'],
                ['date' => '2026-06-03', 'time' => '21:30'],
            ],
            zones: [
                ['id' => 'pista', 'name' => 'Pista General', 'capacity' => 900, 'price' => 49.00, 'color' => '#10B981'],
                ['id' => 'graderio', 'name' => 'Graderío', 'capacity' => 650, 'price' => 69.00, 'color' => '#F59E0B'],
                ['id' => 'front_stage', 'name' => 'Front Stage', 'capacity' => 220, 'price' => 99.00, 'color' => '#6366F1'],
                ['id' => 'vip', 'name' => 'VIP Experience', 'capacity' => 90, 'price' => 159.00, 'color' => '#EF4444'],
            ],
        );

        $this->createConcertEvent(
            title: 'Rosalía - Motomami World Tour',
            description: 'Una puesta en escena vanguardista donde baile, percusión y electrónica se combinan con flamenco contemporáneo. El espectáculo alterna momentos íntimos con bloques de gran intensidad coreográfica, creando un ritmo narrativo que evoluciona durante toda la noche. Diseño de iluminación cinemático, arreglos en directo y dirección artística detallista elevan cada canción a una pieza escénica propia.',
            image: 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819',
            sessions: [
                ['date' => '2026-06-10', 'time' => '22:00'],
            ],
            zones: [
                ['id' => 'pista', 'name' => 'Pista', 'capacity' => 700, 'price' => 55.00, 'color' => '#10B981'],
                ['id' => 'preferente', 'name' => 'Preferente', 'capacity' => 320, 'price' => 84.00, 'color' => '#F59E0B'],
                ['id' => 'palco', 'name' => 'Palco', 'capacity' => 110, 'price' => 129.00, 'color' => '#8B5CF6'],
                ['id' => 'vip', 'name' => 'VIP', 'capacity' => 60, 'price' => 179.00, 'color' => '#EF4444'],
            ],
        );

        $this->createConcertEvent(
            title: 'Arctic Lights Festival - Noche Headliners',
            description: 'Un evento especial que reúne a varias bandas internacionales de indie y electrónica en un mismo cartel. Con dos escenarios sincronizados y cambios de set continuos, la noche ofrece un formato dinámico para disfrutar tanto de grandes himnos como de propuestas emergentes. Sonido envolvente, pantallas de gran formato y una dirección visual inspirada en auroras polares completan una experiencia festival premium.',
            image: 'https://images.unsplash.com/photo-1459749411175-04bf5292ceea',
            sessions: [
                ['date' => '2026-06-21', 'time' => '20:30'],
            ],
            zones: [
                ['id' => 'general', 'name' => 'General', 'capacity' => 1200, 'price' => 39.00, 'color' => '#10B981'],
                ['id' => 'front', 'name' => 'Front Zone', 'capacity' => 260, 'price' => 72.00, 'color' => '#F59E0B'],
                ['id' => 'lounge', 'name' => 'Lounge', 'capacity' => 140, 'price' => 115.00, 'color' => '#3B82F6'],
                ['id' => 'vip', 'name' => 'VIP Backstage', 'capacity' => 45, 'price' => 210.00, 'color' => '#EF4444'],
            ],
        );
    }

    private function buildGridLayout(int $rows, int $cols, array $aisleColumns = []): array
    {
        $layout = [];

        for ($row = 0; $row < $rows; $row++) {
            $rowLayout = [];

            for ($col = 0; $col < $cols; $col++) {
                $rowLayout[] = in_array($col, $aisleColumns, true) ? 0 : 1;
            }

            $layout[] = $rowLayout;
        }

        return $layout;
    }

    private function createMovieEvent(string $title, string $description, ?string $image, array $sessions, array $gridLayout): void
    {
        $event = Event::create([
            'title' => $title,
            'description' => $description,
            'type' => 'movie',
            'image' => $image,
        ]);

        foreach ($sessions as $session) {
            AppSession::create([
                'event_id' => $event->id,
                'date' => $session['date'],
                'time' => $session['time'],
                'price' => $session['price'],
                'venue_config' => [
                    'type' => 'grid',
                    'rows' => count($gridLayout),
                    'cols' => count($gridLayout[0]),
                    'layout' => $gridLayout,
                ],
            ]);
        }
    }

    private function createConcertEvent(string $title, string $description, ?string $image, array $sessions, array $zones): void
    {
        $event = Event::create([
            'title' => $title,
            'description' => $description,
            'type' => 'concert',
            'image' => $image,
        ]);

        foreach ($sessions as $session) {
            AppSession::create([
                'event_id' => $event->id,
                'date' => $session['date'],
                'time' => $session['time'],
                'price' => null,
                'venue_config' => [
                    'type' => 'zones',
                    'zones' => $zones,
                ],
            ]);
        }
    }
}
