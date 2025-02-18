@php
$imagePath = public_path("images/ueb.png");
$imageData = base64_encode(file_get_contents($imagePath));
$imageSrc = 'data:image/png;base64,' . $imageData;
@endphp

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificado de Participación</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: rgb(255, 255, 255);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .certificado-wrapper {
            position: relative;
            width: 1000px;
            height: 650px;
            background-color: white;
            padding: 0;
            display: flex;
            justify-content: flex-start;
            align-items: flex-start;
        }

        .certificado-container {
            width: 660px;
            height: 580px;
            position: relative;
            background-image: url('{{ $imageSrc }}');

            background-position: center;
            background-repeat: no-repeat;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
            overflow: hidden;
        }

        .header {
            position: absolute;
            top: 91px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 9px;
            color: red;
            top: 13em;
        }

        .header h1 {
            margin: 0;
        }

        .certificado-titulo {
            position: absolute;
            top: 150px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 26px;
            font-weight: bold;
            color: #0A2E57;
        }

        .nombre {
            position: absolute;
            top: 180px;
            left: 0;
            right: 0;
            text-align: center;
            font-weight: bold;
            font-size: 22px;
            margin: 0;
        }

        .cedula {
            position: absolute;
            top: 220px;
            left: 0;
            right: 0;
            text-align: center;
            font-weight: bold;
            font-size: 22px;
            margin: 0;
        }

        .info {
            position: absolute;
            top: 280px;
            left: 58%;
            transform: translateX(-50%);
            width: 80%;
            font-size: 14px;
            margin: 0;
        }

        .date {
            position: absolute;
            bottom: 220px;
            right: 24.5px;
            font-size: 12px;
            margin: 0;
            top: 29em;
        }

        .qr {
            position: absolute;
            bottom: 80px;
            right: 100px;
            top: 31em;
        }

        .qr img {
            width: 100px;
        }

        .firma {
            position: absolute;
            bottom: 30px;
            left: 0;
            right: 0;
            text-align: center;
            top: 32em;
        }

        .firma p {
            margin: 5px 0;
            font-size: 10px;
        }

        .codigo {
            position: absolute;
            bottom: 20px;
            left: 45px;
            font-size: 12px;
            color: black;
            margin: 0;
            top: 49em;
        }

        .resolucion {
            position: absolute;
            bottom: 20px;
            left: 33.5%;
            transform: translateX(-50%);
            font-size: 12px;
            color: black;
            text-align: center;
            margin: 0;
            top: 50.1em;
        }

        .codproyecto {
            position: absolute;
            bottom: 20px;
            right: 100px;
            font-size: 12px;
            color: black;
            margin: 0;
            top: 50.1em;
        }
    </style>
</head>

<body>

    <div class="certificado-wrapper">
        <div class="certificado-container"></div>

        <div class="header">
            <h1>VICERRECTORADO DE INVESTIGACIÓN Y VINCULACIÓN</h1>
        </div>

        <div class="certificado-titulo">CERTIFICADO</div>

        <p class="nombre">A: {{ $estudiante }}</p>
        <p class="cedula">CI: {{ $cedula }}</p>

        <p class="info">
            Por su participación en el proyecto de Vinculación:
            <strong>{{ $proyecto }}</strong>,
            con una duración de <strong>96 horas</strong>
            @if(isset($nota_final))
            y una calificación de <strong>{{$nota_final}}</strong>.
            @endif
        </p>

        <p class="date">Guaranda, {{ $fecha }}</p>

        <div class="qr">
            <img src="data:image/svg+xml;base64,{{ $qrCode }}" alt="Código QR">
        </div>

        <div class="firma">
            <p>________________________________________</p>
            <p>DR. CARLOS RIBADENEIRA ZAPATA, PhD</p>
            <p>VICERRECTOR DE INVESTIGACIÓN Y VINCULACIÓN</p>
        </div>

        <p class="codigo">No. CERTIFICADO:<br>{{$certificado }}</p>
        <p class="resolucion">Resolución de Consejo Universitario: {{ $resolucion }}</p>
        <p class="codproyecto">CÓDIGO: {{ $codigo }}</p>
    </div>

</body>

</html>