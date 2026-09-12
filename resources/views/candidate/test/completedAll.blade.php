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
                    @if($result)
                        <!-- Resultados -->
                        <div class="mb-5">
                            <h2 class="h4 fw-bold text-dark mb-4 text-center">
                                <i class="bi bi-clipboard2-data-fill text-ues-blue me-2"></i> Resumen de Resultados
                            </h2>

                            <!-- Puntaje Total -->
                            <div class="bg-ues-blue text-white rounded-4 p-4 p-md-5 mb-4 text-center shadow">
                                <p class="text-uppercase small fw-bold opacity-75 tracking-wider mb-2">Puntaje Total</p>
                                <div class="display-1 fw-bold mb-2">{{ $result->total_score }}</div>
                                <p class="h4 opacity-75 mb-0">de 60 puntos</p>
                            </div>

                            <!-- Estadísticas principales -->
                            <div class="row g-3 mb-4 text-center">
                                <!-- Percentil -->
                                <div class="col-6 col-md-3">
                                    <div class="bg-light rounded-3 p-3 border h-100">
                                        <div class="bg-ues-blue text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 45px; height: 45px;">
                                            <i class="bi bi-bar-chart-fill fs-5"></i>
                                        </div>
                                        <p class="text-muted small fw-bold text-uppercase mb-1">Percentil</p>
                                        <p class="h3 fw-bold text-ues-blue mb-0">{{ $result->percentile }}</p>
                                    </div>
                                </div>
                                
                                <!-- Rango -->
                                <div class="col-6 col-md-3">
                                    <div class="bg-light rounded-3 p-3 border h-100">
                                        <div class="bg-ues-blue text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 45px; height: 45px;">
                                            <i class="bi bi-award-fill fs-5"></i>
                                        </div>
                                        <p class="text-muted small fw-bold text-uppercase mb-1">Rango</p>
                                        <p class="h4 fw-bold text-ues-blue mb-0">{{ $result->diagnostic_range }}</p>
                                    </div>
                                </div>
                                
                                <!-- Tiempo -->
                                <div class="col-6 col-md-3">
                                    <div class="bg-light rounded-3 p-3 border h-100">
                                        <div class="bg-ues-blue text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 45px; height: 45px;">
                                            <i class="bi bi-stopwatch-fill fs-5"></i>
                                        </div>
                                        <p class="text-muted small fw-bold text-uppercase mb-1">Tiempo Total</p>
                                        <p class="h4 fw-bold text-ues-blue mb-0">{{ gmdate('i:s', $result->total_time_seconds) }}</p>
                                    </div>
                                </div>
                                
                                <!-- Estado -->
                                <div class="col-6 col-md-3">
                                    <div class="bg-light rounded-3 p-3 border h-100">
                                        <div class="{{ $result->is_valid ? 'bg-success' : 'bg-danger' }} text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 45px; height: 45px;">
                                            <i class="bi {{ $result->is_valid ? 'bi-check-lg' : 'bi-x-lg' }} fs-4"></i>
                                        </div>
                                        <p class="text-muted small fw-bold text-uppercase mb-1">Estado</p>
                                        <p class="h5 fw-bold {{ $result->is_valid ? 'text-success' : 'text-danger' }} mb-0">
                                            {{ $result->is_valid ? 'Válido' : 'Inválido' }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Clasificación -->
                            <div class="alert bg-light rounded-3 p-4 mb-5 border-4 border-ues-blue mb-4 shadow-sm">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-person-lines-fill text-ues-blue fs-2 me-3"></i>
                                    <div>
                                        <h3 class="h6 fw-bold text-ues-blue mb-1">Clasificación Diagnóstica</h3>
                                        <p class="text-dark fs-5 fw-bold mb-0">
                                            {{ $result->diagnostic_label }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Puntajes por Serie -->
                            <div class="mb-4">
                                <h3 class="h6 fw-bold text-dark mb-3">
                                    <i class="bi bi-grid-3x3-gap-fill text-ues-blue me-2"></i> Puntajes por Serie
                                </h3>
                                <div class="row row-cols-2 row-cols-md-5 g-3">
                                    @foreach(['A', 'B', 'C', 'D', 'E'] as $serie)
                                        <div class="col">
                                            <div class="bg-light rounded-3 p-3 text-center border h-100">
                                                <div class="bg-ues-gold text-dark rounded-circle d-inline-flex align-items-center justify-content-center fw-bold mb-2" style="width: 35px; height: 35px;">
                                                    {{ $serie }}
                                                </div>
                                                <p class="text-muted small mb-1">Serie {{ $serie }}</p>
                                                <p class="h3 fw-bold text-dark mb-0">{{ $result->{'series_' . strtolower($serie) . '_score'} }}</p>
                                                <span class="text-muted small">/12</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Advertencia si es inválido -->
                            @if(!$result->is_valid)
                                <div class="alert alert-warning border-start border-4 border-warning shadow-sm mb-4">
                                    <div class="d-flex">
                                        <i class="bi bi-exclamation-triangle-fill text-warning fs-4 me-3"></i>
                                        <div>
                                            <strong class="fw-bold">Nota:</strong> {{ $result->validity_notes }}
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif

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