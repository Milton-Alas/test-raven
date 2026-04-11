@extends('candidate.layouts.app')

@section('title', 'Bienvenida al Test')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10 col-xl-8">
            
            <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
                
                @if(session('error'))
                    <div class="alert alert-danger border-0 rounded-0 m-0">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
                    </div>
                @endif
                @if(session('success'))
                    <div class="alert alert-success border-0 rounded-0 m-0">
                        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                    </div>
                @endif

                <div class="card-header bg-ues-blue text-white text-center py-4 border-0">
                    <h1 class="h2 fw-bold mb-1">Test de Matrices Progresivas de Raven</h1>
                    <p class="mb-0 opacity-75">Escala General para Adultos</p>
                </div>

                <div class="card-body p-4 p-md-5">
                    
                    <div class="bg-light rounded-3 p-4 mb-4 border-start border-4 border-ues-gold shadow-sm">
                        <h3 class="h6 fw-bold text-ues-blue mb-3">
                            <i class="bi bi-person-badge-fill me-2"></i> Información del Candidato
                        </h3>
                        <div class="row g-3 small">
                            <div class="col-md-6">
                                <span class="text-muted">Nombre:</span>
                                <span class="fw-bold d-block text-dark">{{ $candidate->name }}</span>
                            </div>
                            <div class="col-md-6">
                                <span class="text-muted">Edad:</span>
                                <span class="fw-bold d-block text-dark">{{ $candidate->age }} años</span>
                            </div>
                            @if($candidate->occupation)
                            <div class="col-md-6">
                                <span class="text-muted">Ocupación:</span>
                                <span class="fw-bold d-block text-dark">{{ $candidate->occupation }}</span>
                            </div>
                            @endif
                            @if($candidate->education_level)
                            <div class="col-md-6">
                                <span class="text-muted">Educación:</span>
                                <span class="fw-bold d-block text-dark">{{ ucfirst($candidate->education_level) }}</span>
                            </div>
                            @endif
                        </div>
                    </div>

                    <div class="mb-5">
                        <h3 class="h4 fw-bold text-ues-blue mb-4 border-bottom pb-2">Instrucciones del Test</h3>
                        
                        <p class="text-secondary mb-4">
                            Esta prueba mide su capacidad de razonamiento abstracto y resolución de problemas mediante figuras geométricas.
                        </p>

                        <div class="row g-4">
                            <div class="col-12">
                                <div class="p-3 rounded-3 border-start border-4 border-primary" style="background-color: #f0f7ff;">
                                    <h4 class="h6 fw-bold text-primary mb-2"><i class="bi bi-info-circle-fill me-2"></i> ¿Cómo funciona?</h4>
                                    <ul class="mb-0 small text-dark">
                                        <li>Verá una serie de figuras con una parte faltante.</li>
                                        <li>Elija una de las 6 opciones que complete correctamente el patrón.</li>
                                        <li>Consta de <strong>60 preguntas</strong> en 5 series (A a la E).</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="p-3 rounded-3 border-start border-4 border-danger" style="background-color: #fff5f5;">
                                    <h4 class="h6 fw-bold text-danger mb-2"><i class="bi bi-exclamation-octagon-fill me-2"></i> Importante</h4>
                                    <ul class="mb-0 small text-dark">
                                        <li>Tiempo límite: <strong>45 minutos</strong>.</li>
                                        <li>Una vez seleccionada una respuesta, <strong>no podrá regresar</strong>.</li>
                                        <li><strong>Solo puede realizar el test UNA vez.</strong></li>
                                        <li>No cierre el navegador ni actualice la página.</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="p-3 rounded-3 border-start border-4 border-success" style="background-color: #f6fff9;">
                                    <h4 class="h6 fw-bold text-success mb-2"><i class="bi bi-lightbulb-fill me-2"></i> Recomendaciones</h4>
                                    <ul class="mb-0 small text-dark">
                                        <li>Trabaje en un lugar tranquilo y sin distracciones.</li>
                                        <li>Lea cuidadosamente cada matriz antes de responder.</li>
                                        <li>Confíe en su primera impresión lógica.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('candidate.test.start') }}" class="text-center">
                        @csrf
                        <button type="submit" class="btn btn-ues-rojo btn-lg px-5 py-3 fw-bold shadow-lg transform-hover">
                            <i class="bi bi-play-fill me-2"></i> INICIAR TEST AHORA
                        </button>
                        <p class="text-muted mt-3 small">
                            Al iniciar, confirma que ha comprendido las instrucciones.
                        </p>
                    </form>

                </div> <div class="card-footer bg-white border-top py-3 text-center">
                    <span class="text-muted small">Sistema de Evaluación Psicométrica<br>
                        Universidad de El Salvador © 2026</span>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection