<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Mentoria;
use App\Models\User;
use App\Models\SolicitudMentoria;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MentoriaModelTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test que el modelo Mentoria puede ser creado.
     */
    public function test_mentoria_puede_ser_creada(): void
    {
        $aprendiz = User::factory()->create(['role' => 'student']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        $solicitud = SolicitudMentoria::factory()->create([
            'estudiante_id' => $aprendiz->id,
            'mentor_id' => $mentor->id,
        ]);

        $mentoria = Mentoria::factory()->create([
            'solicitud_id' => $solicitud->id,
            'aprendiz_id' => $aprendiz->id,
            'mentor_id' => $mentor->id,
        ]);

        $this->assertInstanceOf(Mentoria::class, $mentoria);
        $this->assertDatabaseHas('mentorias', [
            'id' => $mentoria->id,
            'aprendiz_id' => $aprendiz->id,
            'mentor_id' => $mentor->id,
        ]);
    }

    /**
     * Test relación belongsTo con aprendiz.
     */
    public function test_mentoria_pertenece_a_aprendiz(): void
    {
        $mentoria = Mentoria::factory()->create();

        $this->assertInstanceOf(User::class, $mentoria->aprendiz);
        $this->assertEquals('student', $mentoria->aprendiz->role);
    }

    /**
     * Test relación belongsTo con mentor.
     */
    public function test_mentoria_pertenece_a_mentor(): void
    {
        $mentoria = Mentoria::factory()->create();

        $this->assertInstanceOf(User::class, $mentoria->mentor);
        $this->assertEquals('mentor', $mentoria->mentor->role);
    }

    /**
     * Test relación belongsTo con solicitud.
     */
    public function test_mentoria_pertenece_a_solicitud(): void
    {
        $mentoria = Mentoria::factory()->create();

        $this->assertInstanceOf(SolicitudMentoria::class, $mentoria->solicitud);
    }

    /**
     * Test scope confirmadas.
     */
    public function test_scope_confirmadas_filtra_correctamente(): void
    {
        Mentoria::factory()->confirmada()->count(2)->create();
        Mentoria::factory()->completada()->create();
        Mentoria::factory()->cancelada()->create();

        $confirmadas = Mentoria::confirmadas()->get();

        $this->assertCount(2, $confirmadas);
        $confirmadas->each(fn($m) => $this->assertEquals('confirmada', $m->estado));
    }

    /**
     * Test método completar().
     */
    public function test_puede_completar_mentoria(): void
    {
        $mentoria = Mentoria::factory()->confirmada()->create();

        $resultado = $mentoria->completar('Notas del mentor', 'Notas del aprendiz');

        $this->assertTrue($resultado);
        $this->assertEquals('completada', $mentoria->fresh()->estado);
        $this->assertEquals('Notas del mentor', $mentoria->fresh()->notas_mentor);
        $this->assertEquals('Notas del aprendiz', $mentoria->fresh()->notas_aprendiz);
    }

    /**
     * Test método cancelar().
     */
    public function test_puede_cancelar_mentoria(): void
    {
        $mentoria = Mentoria::factory()->confirmada()->create();

        $resultado = $mentoria->cancelar();

        $this->assertTrue($resultado);
        $this->assertEquals('cancelada', $mentoria->fresh()->estado);
    }

    /**
     * Test método puedeUnirse().
     */
    public function test_usuario_autorizado_puede_unirse(): void
    {
        $aprendiz = User::factory()->create(['role' => 'student']);
        $mentor = User::factory()->create(['role' => 'mentor']);
        
        $mentoria = Mentoria::factory()->create([
            'aprendiz_id' => $aprendiz->id,
            'mentor_id' => $mentor->id,
            'estado' => 'confirmada',
            'enlace_reunion' => 'https://zoom.us/j/123456789',
        ]);

        $this->assertTrue($mentoria->puedeUnirse($aprendiz));
        $this->assertTrue($mentoria->puedeUnirse($mentor));
    }

    /**
     * Test usuario no autorizado no puede unirse.
     */
    public function test_usuario_no_autorizado_no_puede_unirse(): void
    {
        $otroUsuario = User::factory()->create();
        $mentoria = Mentoria::factory()->create();

        $this->assertFalse($mentoria->puedeUnirse($otroUsuario));
    }
}
