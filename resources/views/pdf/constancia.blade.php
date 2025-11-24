<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Constancia de Pago - {{ $expediente->numero_expediente }}</title>
    <style>
        /* Estilos CSS para el PDF */
        body { font-family: 'Helvetica', sans-serif; font-size: 10px; line-height: 1.4; color: #333; }
        .header-table { width: 100%; border-spacing: 0; border: none; margin-bottom: 20px; }
        .header-table td { border: none; vertical-align: middle; }
        .logo { width: 140px; height: auto; }
        .header-text { text-align: center; }
        .header-text h3 { margin: 2px 0; font-size: 12px; }
        .info-box { text-align: left; font-size: 11px; }
        .title { text-align: center; font-weight: bold; margin-bottom: 20px; font-size: 14px; text-decoration: underline; }
        .content-text { text-align: justify; margin-bottom: 15px; }
        .hacen-constar { text-align: center; font-weight: bold; margin: 15px 0; font-size: 12px; }
        .data-table { width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 8px; }
        .data-table th, .data-table td { border: 1px solid #999; padding: 4px; text-align: center; vertical-align: middle; }
        .data-table th { background-color: #f2f2f2; font-weight: bold; }
        .signature { margin-top: 50px; text-align: center; } /* Centrado para mejor estética */
        .footer-notes { font-size: 7px; text-align: left; margin-top: 60px; border-top: 1px solid #ccc; padding-top: 10px;}
        .date-right { text-align: right; margin: 40px 0; }
        .text-center { text-align: center !important; }
    </style>
</head>
<body>

    {{-- 🔥 ENCABEZADO CORREGIDO CON UNA TABLA INVISIBLE 🔥 --}}
    <table class="header-table">
        <tr>
            <td style="width: 25%;">
                <img src="{{ storage_path('app/public/images/ambos.png') }}" class="logo">
            </td>
            <td style="width: 50%;" class="header-text">
                <h3>GOBIERNO REGIONAL DE HUÁNUCO</h3>
                <h3>DIRECCIÓN REGIONAL DE EDUCACIÓN</h3>
            </td>
            <td style="width: 25%;" class="info-box">
                <strong>CONSTANCIA N° {{ $expediente->constancia->numero_constancia }}</strong><br>
                <strong>EXPEDIENTE N° {{ $expediente->numero_expediente }}</strong>
            </td>
        </tr>
    </table>

    <h2 class="title">CONSTANCIA DE PAGO</h2>

    <div class="content-text" style="text-align: center;">
        EL DIRECTOR REGIONAL DE EDUCACIÓN, EL DIRECTOR DE GESTIÓN ADMINISTRATIVA, EL ÁREA DE TESORERÍA A TRAVÉS DE LA OFICINA DE CONSTANCIA DE PAGOS Y DESCUENTOS DE HABERES QUIENES SUSCRIBEN:
    </div>

    <div class="hacen-constar">HACEN CONSTAR</div>

    <div class="content-text">
        Que el(la) Sr(a). <strong>{{ $usuario->name }}</strong>, con DNI N° <strong>{{ $usuario->dni }}</strong> 
        y C.M. <strong>{{ $usuario->codigomodular }}</strong> percibió el pago de sus remuneraciones descontados sus aportes de Ley, según como constan en las planillas de pagos y descuentos de haberes existentes en esta oficina.
    </div>

    {{-- 🔥 TABLA DE DATOS CORREGIDA CON LÓGICA PARA CELDAS COMBINADAS 🔥 --}}
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;">N°</th>
                @foreach($columnas as $columna)
                    <th>{{ $columna->nombre_display }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @php
                // Obtenemos los IDs de las columnas clave una sola vez
                $idMeses = $columnas->firstWhere('nombre_normalizado', 'meses')->id ?? null;
                $idAnio = $columnas->firstWhere('nombre_normalizado', 'ano')->id ?? null;
                $idObservacion = $columnas->firstWhere('nombre_normalizado', 'observacion')->id ?? null;
            @endphp
            @forelse($tabla as $fila)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    
                    @php
                        // Verificamos si es una fila de "solo observación"
                        $datosReales = array_filter($fila['datos']); // Filtra valores vacíos o nulos
                        $esFilaObservacion = isset($datosReales[$idObservacion]) && count($datosReales) <= 3;
                    @endphp

                    @if($esFilaObservacion)
                        {{-- Fila especial para Observaciones --}}
                        <td>{{ $fila['datos'][$idMeses] ?? '' }}</td>
                        <td>{{ $fila['datos'][$idAnio] ?? '' }}</td>
                        {{-- La celda de observación ocupa el resto de las columnas --}}
                        <td colspan="{{ $columnas->count() - 2 }}" class="text-center">
                            <strong>{{ $fila['datos'][$idObservacion] }}</strong>
                        </td>
                    @else
                        {{-- Fila normal con todos los datos --}}
                        @foreach($columnas as $columna)
                            <td>{{ $fila['datos'][$columna->id] ?? '' }}</td>
                        @endforeach
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $columnas->count() + 1 }}">No hay datos para mostrar.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="content-text">
        Se expide la presente constancia de pagos y descuentos de haberes, que consta de <strong>{{ count($tabla) }}</strong> meses, de acuerdo con lo solicitado por parte del interesado, para los fines que estime conveniente.
    </div>

    <div class="date-right">
        Huánuco, {{ \Carbon\Carbon::now()->locale('es')->isoFormat('DD [de] MMMM [de] YYYY') }}
    </div>

    <div class="signature">
        _____________________________________<br>
        <strong>{{ $generadoPor->name }}</strong><br>
        Encargado(a) en constancia de pago
    </div>

    <div class="footer-notes">
        <strong>NOTA: SISTEMA MONETARIO:</strong> SOL DE ORO, HASTA DICIEMBRE 1985<br>
        INTIS MILLON’ DE ENERO 1986 HASTA DICIEMBRE DE 1990<br>
        NUEVO SOL, DE ENERO 1991 HASTA DICIEMBRE DEL 2016<br>
        SOL, DE ENERO DEL 2017 HASTA LA FECHA<br><br>
        <strong>Nota:</strong> INVALIDA la presente constancia cualquier enmendadura borrón y/o añadidura.<br>
        Esquina Jr. Abtao y Jr. Progreso Nº 462 – Huánuco (062) 512810 Fax : (062) 514856
    </div>

</body>
</html>