<?php

namespace App\Http\Controllers;

use App\Models\Certificado;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Models\Proyecto;
use App\Models\User;
use App\Models\ProjectMember;
use App\Models\Estudiante;

class CertificadoController extends Controller
{

    public function obtenerProyectos()
    {
        $proyectos = Proyecto::select('id', 'nombre', 'codigo_proyecto', 'numero_resolucion')->get();

        return response()->json($proyectos);
    }

    /**
     * Obtener los participantes de un proyecto (estudiantes y docentes).
     */
    public function obtenerParticipantesProyecto($proyecto_id)
    {
        // Buscar el proyecto
        $proyecto = Proyecto::findOrFail($proyecto_id);

        // Obtener los miembros del proyecto con su información de usuario
        $miembros = $proyecto->miembros()->with('user')->get();

        // Filtrar por rol y asegurar que los IDs sean de la tabla 'estudiantes'
        $estudiantes = $miembros->where('role', 'estudiante')->map(function ($miembro) {
            $estudiante = Estudiante::where('correo', $miembro->user->email)->first();
            return [
                'id' => $estudiante ? $estudiante->id : null, // Usar el ID real del estudiante
                'nombre' => $miembro->user->name,
                'correo' => $miembro->user->email,
            ];
        })->filter(); // Elimina valores nulos en caso de no encontrar coincidencias

        $docentes = $miembros->where('role', 'profesor')->map(function ($miembro) {
            return [
                'id' => $miembro->user->id, // Para docentes, probablemente está correcto
                'nombre' => $miembro->user->name,
                'correo' => $miembro->user->email,
            ];
        });

        return response()->json([
            'proyecto' => $proyecto->nombre,
            'codigo' => $proyecto->codigo_proyecto,
            'resolucion' => $proyecto->numero_resolucion,
            'total_estudiantes' => $estudiantes->count(),
            'total_docentes' => $docentes->count(),
            'estudiantes' => $estudiantes->values(),
            'docentes' => $docentes->values(),
        ]);
    }



    /**
     * Generar certificado en PDF para un estudiante de un proyecto.
     */
    public function generarCertificado($proyecto_id, $estudiante_id)
    {
        // Obtener datos del proyecto y estudiante
        $proyecto = Proyecto::findOrFail($proyecto_id);

        // Buscar el estudiante por ID desde la tabla correcta
        $estudiante = Estudiante::where('id', $estudiante_id)->firstOrFail();

        // Generar contenido del código QR
        $qrData = "Universidad Estatal de Bolívar :: Certificado de Vinculación ::
                   Nombre: {$estudiante->nombre} {$estudiante->apellido} ::
                   Cédula: {$estudiante->cedula} ::
                   Código Proyecto: {$proyecto->codigo_proyecto} ::
                   Número Resolución: {$proyecto->numero_resolucion}";

        // Generar QR en base64
        $qrCode = base64_encode(QrCode::format('svg')->size(200)->generate($qrData));



        // Datos a enviar a la vista
        $data = [
            'proyecto' => $proyecto->nombre,
            'codigo' => $proyecto->codigo_proyecto,
            'resolucion' => $proyecto->numero_resolucion,
            'estudiante' => "{$estudiante->nombre} {$estudiante->apellido}",
            'cedula' => $estudiante->cedula, // Asegurar que la cédula está incluida
            'estudiante_email' => $estudiante->correo,
            'fecha' => now()->format('d-m-Y'),
            'qrCode' => $qrCode,
        ];

        // Cargar la vista y generar PDF
        $pdf = Pdf::loadView('certificados.certificado', $data)
            // Configura la orientación en horizontal
            ->setPaper('A4', 'landscape')
            ->setOption('margin-left', 0)
            ->setOption('margin-right', 0)
            ->setOption('margin-top', 0)
            ->setOption('margin-bottom', 0);


        return $pdf->stream("certificado_{$estudiante->cedula}.pdf");
    }

    // En CertificadoController.php (ejemplo)
    // public function getCertificadosPorDocente($userId)
    // {
    //     $certificados = \App\Models\Certificado::with('proyecto')
    //     ->where('user_id', $userId)
    //         ->get();
    //     return response()->json(['certificados' => $certificados], 200);
    // }
    public function getCertificadosPorDocente($userId)
    {
        $certificados = \App\Models\Certificado::with([
            'proyecto',
            'proyecto.miembros' => function ($query) {
                $query->where('role', 'estudiante')
                ->with('user.estudiante');
            }
        ])
            ->where('user_id', $userId)
            ->get();

        return response()->json(['certificados' => $certificados], 200);
    }




    public function getCertificadosPorEstudiante($userId)
    {
        $certificados = \App\Models\Certificado::with('proyecto')
        ->where('user_id', $userId)
            ->where('rol', 'estudiante')
            ->get();
        return response()->json(['certificados' => $certificados], 200);
    }

}
