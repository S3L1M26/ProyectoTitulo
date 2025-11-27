<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Mentor;
use App\Models\MentorReview;
use App\Models\User;

class MentorReviewsSeeder extends Seeder
{
    /**
     * Seed mentor reviews (incluye preguntas nuevas de intereses).
     */
    public function run(): void
    {
        $reviewsByMentor = [
            'mentor.completo@example.com' => [
                [
                    'user_email' => 'estudiante.test@example.com',
                    'rating' => 5,
                    'comment' => 'Excelente orientación, muy claro para definir mis siguientes pasos.',
                    'addressed_interests' => 'yes',
                    'interests_clarity' => 5,
                ],
                [
                    'user_email' => 'ana.frontend@example.com',
                    'rating' => 4,
                    'comment' => 'Me ayudó a priorizar qué aprender primero en frontend.',
                    'addressed_interests' => 'partial',
                    'interests_clarity' => 4,
                ],
            ],
            'pedro.senior@example.com' => [
                [
                    'user_email' => 'carlos.backend@example.com',
                    'rating' => 5,
                    'comment' => 'Explicó con ejemplos reales de backend y DevOps.',
                    'addressed_interests' => 'yes',
                    'interests_clarity' => 5,
                ],
                [
                    'user_email' => 'maria.data@example.com',
                    'rating' => 3,
                    'comment' => 'Buena charla, aunque no profundizamos tanto en datos.',
                    'addressed_interests' => 'partial',
                    'interests_clarity' => 3,
                ],
            ],
            'laura.ui@example.com' => [
                [
                    'user_email' => 'estudiante.incompleto@example.com',
                    'rating' => 4,
                    'comment' => 'Ahora entiendo mejor el día a día en UX/UI.',
                    'addressed_interests' => 'yes',
                    'interests_clarity' => 4,
                ],
            ],
            // Otros mentores quedan sin reseñas para probar el caso vacío
        ];

        foreach ($reviewsByMentor as $mentorEmail => $reviews) {
            /** @var \App\Models\Mentor|null $mentorProfile */
            $mentorProfile = Mentor::whereHas('user', function ($query) use ($mentorEmail) {
                $query->where('email', $mentorEmail);
            })->first();

            if (!$mentorProfile) {
                continue;
            }

            foreach ($reviews as $reviewData) {
                $studentUser = User::where('email', $reviewData['user_email'])->first();
                if (!$studentUser) {
                    continue;
                }

                MentorReview::updateOrCreate(
                    [
                        'mentor_id' => $mentorProfile->id,
                        'user_id' => $studentUser->id,
                    ],
                    [
                        'rating' => $reviewData['rating'],
                        'comment' => $reviewData['comment'],
                        'addressed_interests' => $reviewData['addressed_interests'] ?? null,
                        'interests_clarity' => $reviewData['interests_clarity'] ?? null,
                    ]
                );
            }

            // Recalcular promedio tras insertar reseñas
            $mentorProfile->updateAverageRating();
        }
    }
}
