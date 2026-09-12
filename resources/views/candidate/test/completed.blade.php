@extends('candidate.layouts.app')

@section('title', 'Test Completado')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10 col-xl-8">
            
            <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
                
                <!-- Success Header -->
                <div class="bg-ues-blue text-white text-center py-5 px-4 position-relative">
                    <div class="mb-4">
                        <div class="d-inline-flex align-items-center justify-content-center bg-white text-ues-blue rounded-circle shadow-lg" style="width: 80px; height: 80px;">
                            <i class="bi bi-check2-circle text-success" style="font-size: 3rem;"></i>
                        </div>
                    </div>
                    <h1 class="display-6 fw-bold mb-2">¡Test Completado!</h1>
                    <p class="lead mb-0 opacity-75">Has finalizado exitosamente el Test de Matrices Progresivas de Raven</p>
                </div>

                <!-- Content -->
                <div class="card-body p-4 p-md-5">

                    <!-- Mensaje Final -->
                    <div class="bg-light rounded-3 p-4 mb-5 border">
                        <h3 class="h6 fw-bold text-dark mb-3">
                            <i class="bi bi-info-circle-fill text-ues-blue me-2"></i> ¿Qué sigue?
                        </h3>
                        <p class="text-secondary mb-2">
                            Tus resultados han sido registrados exitosamente. Un administrador revisará tu evaluación 
                            y podrás obtener un reporte detallado de tu desempeño.
                        </p>
                        <p class="text-secondary mb-0">
                            Gracias por completar el Test de Matrices Progresivas de Raven.
                        </p>
                    </div>

                    <!-- Información de Contacto -->
                    <div class="text-center">
                        <p class="text-muted small mb-4">
                            Si tienes alguna pregunta sobre tus resultados, por favor contacta al administrador.
                        </p>
                        
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-secondary btn-lg px-5 shadow-sm">
                                <i class="bi bi-box-arrow-right me-2"></i> Cerrar Sesión
                            </button>
                        </form>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>
@endsection