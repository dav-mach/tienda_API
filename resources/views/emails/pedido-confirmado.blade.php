<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Confirmación de compra</title>
</head>
<body>
    <h1>¡Gracias por tu compra, {{ $pedido->nombre_cliente }}!</h1>

    <p>Tu pedido <strong>#{{ $pedido->id }}</strong> fue confirmado.</p>

    <h2>Resumen</h2>
    <ul>
        <li>Subtotal: ${{ number_format($pedido->subtotal, 2) }}</li>
        <li>Impuestos: ${{ number_format($pedido->impuestos, 2) }}</li>
        <li>Envío: ${{ number_format($pedido->costo_envio, 2) }}</li>
        <li><strong>Total: ${{ number_format($pedido->total, 2) }}</strong></li>
    </ul>

    <p>Se enviará a: {{ $pedido->direccion_envio }}, {{ $pedido->ciudad }} ({{ $pedido->codigo_postal }}).</p>
</body>
</html>
