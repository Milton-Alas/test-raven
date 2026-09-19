<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Informe Raven - {{ $result->candidate->name }}</title>
    <style>
        @page {
            margin: 25px 35px;
        }

        .university-header {
            width: 100%;
            height: 90px;
            border-bottom: 2px solid #0047AB;
            margin-bottom: 20px;
            border-collapse: collapse;
        }

        .university-header td {
            width: 33.33%;
            height: 90px;
            vertical-align: middle;
        }

        .header-spacer {
            text-align: left;
        }

        .university-info {
            text-align: center;
        }

        .logo-container {
            text-align: right;
        }

        .logo {
            width: 80px;
            height: auto;
        }

        .university-name {
            font-size: 16px;
            font-weight: bold;
            color: #0047AB;
            white-space: nowrap;
        }

        .university-subtitle {
            font-size: 10px;
            margin-top: 4px;
        }
        /*
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        */
        body { 
            font-family: DejaVu Sans, Arial, sans-serif; 
            font-size: 10pt; 
            line-height: 1.4; 
            color: #333333;
        }
        
        .page { 
            width: 190mm;
            margin: 0 auto;
            padding: 0;
        }
        
        /* Header con colores UES */
        .header { 
            text-align: center; 
            margin-bottom: 20px; 
            border-bottom: 3px solid #0047AB; 
            padding-bottom: 15px;
        }
        
        .header h1 { 
            color: #0047AB; 
            font-size: 18pt; 
            margin-bottom: 5px; 
            font-weight: bold;
        }
        
        .header h2 { 
            color: #666666; 
            font-size: 12pt; 
            font-weight: normal; 
            margin-top: 5px;
        }
        
        .section { 
            margin-bottom: 20px;
        }
        
        .section-title { 
            background-color: #0047AB;
            color: white; 
            padding: 8px 12px;
            font-weight: bold; 
            margin-bottom: 10px;
            font-size: 11pt;
            page-break-after: avoid;
        }
        
        /* Grid de información */
        .info-table { 
            width: 100%; 
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        
        .info-table tr {
            border-bottom: 1px solid #E0E0E0;
        }
        
        .info-label { 
            width: 40%; 
            padding: 8px 10px; 
            background-color: #E8F0FF;
            font-weight: bold; 
            color: #0047AB;
            border-right: 2px solid #0047AB;
        }
        
        .info-value { 
            padding: 8px 10px; 
            background: white;
        }

        .info-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .info-row {
            display: table-row;
            border-bottom: 1px solid #E0E0E0;
        }

        .info-row .info-label,
        .info-row .info-value {
            display: table-cell;
            vertical-align: middle;
        }
        
        /* Tabla de puntajes */
        .scores-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 10px 0; 
            page-break-inside: avoid;
        }
        
        .scores-table th { 
            background-color: #0047AB;
            color: white; 
            padding: 10px 5px; 
            text-align: center; 
            font-size: 10pt;
            border: 1px solid #0047AB;
        }
        
        .scores-table td { 
            padding: 10px 5px; 
            text-align: center; 
            border: 1px solid #CCCCCC;
            background: #F8FBFF;
        }
        
        .scores-table td:last-child {
            background: #E8F0FF;
            font-weight: bold;
            color: #0047AB;
            font-size: 11pt;
        }
        
        /* Caja de diagnóstico */
        .diagnostic-box { 
            background-color: #F8FBFF;
            border: 3px solid #0047AB; 
            padding: 20px;
            text-align: center; 
            margin: 15px 0;
            page-break-inside: avoid;
        }
        
        .diagnostic-label-text {
            color: #666666;
            font-size: 10pt;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .percentile { 
            font-size: 42pt; 
            font-weight: bold; 
            color: #0047AB;
            line-height: 1;
            margin: 8px 0;
        }
        
        .percentile-unit {
            font-size: 16pt;
            color: #666666;
        }
        
        .range-badge { 
            display: inline-block; 
            background-color: #E60000;
            color: white; 
            padding: 6px 16px; 
            border-radius: 15px; 
            font-weight: bold; 
            margin: 10px 0;
            font-size: 11pt;
        }
        
        .classification { 
            font-size: 14pt; 
            font-weight: bold; 
            color: #0047AB;
            margin-top: 8px;
        }
        
        /* Caja de interpretación */
        .interpretation { 
            background-color: #F8FBFF; 
            padding: 15px; 
            border-left: 4px solid #0047AB; 
            margin-top: 10px;
            line-height: 1.5;
            color: #333333;
            page-break-inside: avoid;
        }
        
        /* Sección de validez */
        .validity-section {
            background-color: #FFF9E6;
            border-left: 4px solid #FFCC00;
            padding: 12px;
            margin: 15px 0;
            page-break-inside: avoid;
        }
        
        .validity-title {
            color: #B8860B;
            font-weight: bold;
            font-size: 11pt;
            margin-bottom: 6px;
        }
        
        .validity-notes {
            color: #333333;
            line-height: 1.5;
        }
        
        /* Badge de estado */
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 9pt;
            font-weight: bold;
        }
        
        .status-valid {
            background-color: #D4EDDA;
            color: #155724;
        }
        
        .status-invalid {
            background-color: #F8D7DA;
            color: #721C24;
        }
        
        /* Footer */
        .footer { 
            margin-top: 30px; 
            text-align: center; 
            font-size: 8pt; 
            color: #666666;
            border-top: 2px solid #0047AB; 
            padding-top: 10px;
            page-break-inside: avoid;
        }
        
        .footer p {
            margin: 3px 0;
        }
    </style>
</head>
<body>

    <table class="university-header">
    <tr>
            <td class="header-spacer"></td>

            <td class="university-info">
                <div class="university-name">
                    UNIVERSIDAD DE EL SALVADOR
                </div>

                <div class="university-subtitle">
                    Facultad Multidisciplinaria de Occidente
                </div>

                <div class="university-subtitle">
                    Sistema de Evaluación Psicométrica
                </div>
            </td>

            <td class="logo-container">
                <img
                    class="logo"
                    src="{{ public_path('logo/ues1.png') }}"
                    alt="Logo UES"
                >
            </td>
        </tr>
    </table>
    @php
        $candidate = $result->candidate;
        $session = $result->testSession;
        $interpretation = \App\Models\DiagnosticRange::query()
            ->where('range_number', $result->diagnostic_range)
            ->value('interpretation');
    @endphp

    <div class="page">
        <div class="header">
            <!--
            @if(file_exists(public_path('logo/ues1.png')))
                <img src="{{ public_path('logo/ues1.png') }}" class="logo" alt="Logo">
            @endif
            -->
            <h1>Informe de Resultados</h1>
            <h2>Test de Matrices Progresivas de Raven</h2>
            <p style="font-size: 10pt; margin: 5px 0 0 0; color: #0047AB;">Escala General para Adultos</p>
        </div>

        <!-- Datos del Evaluado -->
        <div class="section">
            <div class="section-title">Datos del Evaluado</div>
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Nombre:</div>
                    <div class="info-value">{{ $candidate->name ?? 'N/D' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Edad:</div>
                    <div class="info-value">{{ $candidate->age ?? 'N/D' }} años</div>
                </div>
                <div class="info-row">
                    <div class="info-label">DUI/NIT:</div>
                    <div class="info-value">{{ $candidate->dui_nit ?? 'N/D' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Correo:</div>
                    <div class="info-value">{{ $candidate->email ?? 'N/D' }}</div>
                </div>
                @if(!empty($candidate?->occupation))
                <div class="info-row">
                    <div class="info-label">Ocupación:</div>
                    <div class="info-value">{{ $candidate->occupation }}</div>
                </div>
                @endif
                <div class="info-row">
                    <div class="info-label">Nivel Educativo:</div>
                    <div class="info-value">{{ !empty($candidate?->education_level) ? ucfirst($candidate->education_level) : 'N/D' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Fecha de Evaluación:</div>
                    <div class="info-value">{{ optional($session?->started_at)->format('d/m/Y H:i') ?? 'N/D' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Tiempo Total:</div>
                    <div class="info-value">{{ $result->total_time_formatted ?? 'N/D' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Estado del Test:</div>
                    <div class="info-value">
                        <span class="status-badge {{ $result->is_valid ? 'status-valid' : 'status-invalid' }}">
                            {{ $result->is_valid ? 'Valido' : 'Invalido' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Puntajes por Serie -->
        <div class="section">
            <div class="section-title">Puntajes por Serie</div>
            <table class="scores-table">
                <thead>
                    <tr>
                        <th>Serie A</th>
                        <th>Serie B</th>
                        <th>Serie C</th>
                        <th>Serie D</th>
                        <th>Serie E</th>
                        <th>TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ $result->series_a_score ?? 0 }}/12</td>
                        <td>{{ $result->series_b_score ?? 0 }}/12</td>
                        <td>{{ $result->series_c_score ?? 0 }}/12</td>
                        <td>{{ $result->series_d_score ?? 0 }}/12</td>
                        <td>{{ $result->series_e_score ?? 0 }}/12</td>
                        <td><strong>{{ $result->total_score ?? 0 }}/60</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Diagnóstico -->
        <div class="section">
            <div class="section-title">Diagnostico</div>
            <div class="diagnostic-box">
                <div class="diagnostic-label-text">Percentil</div>
                <div class="percentile">
                    {{ $result->percentile ?? 'N/D' }}
                    <span class="percentile-unit">%</span>
                </div>
                <div class="range-badge">Rango {{ $result->diagnostic_range_roman ?: 'N/D' }}</div>
                <div class="classification">{{ $result->diagnostic_label ?? 'N/D' }}</div>
            </div>
        </div>

        <!-- Interpretación -->
        @if(!empty($interpretation))
        <div class="section">
            <div class="section-title">Interpretacion</div>
            <div class="interpretation">
                {{ $interpretation }}
            </div>
        </div>
        @endif

        <!-- Notas de Validez -->
        @if(!$result->is_valid && !empty($result->validity_notes))
        <div class="section">
            <div class="validity-section">
                <div class="validity-title">Observaciones sobre la Validez</div>
                <div class="validity-notes">{{ $result->validity_notes }}</div>
            </div>
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <p><strong>Informe generado el {{ now()->format('d/m/Y H:i') }}</strong></p>
            <p>Normas de Montevideo | Test de Matrices Progresivas de Raven - Escala General</p>
            <p style="font-size: 8pt; margin-top: 8px; color: #999;">
                Este informe es confidencial y debe ser interpretado por un profesional calificado.
            </p>
        </div>
    </div>
</body>
</html>
