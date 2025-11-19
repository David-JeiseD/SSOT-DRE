<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Constancia de Pago - {{ $expediente->numero_expediente }}</title>
    <style>
        /* Estilos CSS para el PDF */
        body { font-family: 'Helvetica', sans-serif; font-size: 11px; line-height: 1.5; color: #333; }
        .footer { text-align: center; }
        .header h1, .header h2, .header h3 { margin: 0; padding: 0; }
        .logo { width: 80px; height: auto; position: absolute; top: 0px; left: 0px; }
        .info-box { position: absolute; top: 20px; right: 40px; text-align: left; }
        .title { text-align: center; font-weight: bold; margin-top: 20px; margin-bottom: 20px; font-size: 14px; text-decoration: underline; }
        .content-text { text-align: justify; margin-bottom: 15px; }
        .hacen-constar { text-align: center; font-weight: bold; margin: 15px 0; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 9px; }
        th, td { border: 1px solid #999; padding: 4px; text-align: center; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .signature { margin-top: 50px; text-align: left; }
        .footer-notes { font-size: 8px; text-align: left; margin-top: 60px; }
        .date-right { text-align: right; margin: 40px 0; }
    </style>
</head>
<body>

    {{-- Encabezado con Logo y Números de Documento --}}
    <div class="header" style="text-align: center; position: relative;">
        {{-- Nota: dompdf requiere una ruta absoluta a la imagen o una imagen en base64 --}}
        <img src="{{ storage_path('app/public/images/logo_escudo.png') }}" class="logo">
        <h3>MINISTERIO DE EDUCACIÓN</h3>
        <h3>GOBIERNO REGIONAL DE HUÁNUCO</h3>
        <h3>DIRECCIÓN REGIONAL DE EDUCACIÓN</h3>
    </div>
    
    <div class="info-box">
        <strong>CONSTANCIA N°</strong> {{ $expediente->constancia->numero_constancia }}<br>
        <strong>EXPEDIENTE N°</strong> {{ $expediente->numero_expediente }}
    </div>

    <h2 class="title">CONSTANCIA DE PAGO</h2>

    <div class="content-text" style="text-align: center;">
        EL DIRECTOR REGIONAL DE EDUCACIÓN, EL DIRECTOR DE GESTIÓN ADMINISTRATIVA, EL ÁREA DE TESORERÍA A TRAVÉS DE LA OFICINA DE CONSTANCIA DE PAGOS Y DESCUENTOS DE HABERES QUIENES SUSCRIBEN:
    </div>

    <div class="hacen-constar">HACEN CONSTAR</div>

    <div class="content-text">
        Que el Sr. <strong>{{ $usuario->name }}</strong>, Con DNI N° <strong>{{ $usuario->dni }}</strong> 
        y C.M. <strong>{{ $usuario->codigomodular }}</strong> percibió el pago de sus remuneraciones descontados sus aportes de Ley, según como constan en las planillas de pagos y descuentos de haberes existentes en esta oficina.
    </div>

    {{-- La tabla de datos --}}
    <table>
        <thead>
            <tr>
                @foreach($columnas as $columna)
                    <th>{{ $columna->nombre_display }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($tabla as $fila)
                <tr>
                    @foreach($columnas as $columna)
                        <td>{{ $fila['datos'][$columna->id] ?? '' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $columnas->count() }}">No hay datos para mostrar.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="content-text">
        Se expide la presente constancia de pagos y descuentos de haberes, que consta de <strong>{{ count($tabla) }}</strong> meses, de acuerdo con lo solicitado por parte del interesado, para los fines que estime conveniente.
    </div>

    <div class="date-right">
        Huánuco, {{ \Carbon\Carbon::now()->isoFormat('DD [de] MMMM [de] YYYY') }}
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