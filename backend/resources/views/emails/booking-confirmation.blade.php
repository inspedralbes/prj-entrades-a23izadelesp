<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmació de Reserva</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', Arial, sans-serif; background: #F3F4F6; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; border: 2px solid #000000; box-shadow: 4px 4px 0 0 #000000; }
        .header { background: #10B981; padding: 20px; border-bottom: 2px solid #000000; text-align: center; }
        .header h1 { color: #000000; font-size: 24px; }
        .content { padding: 20px; }
        .qr-section { text-align: center; margin: 20px 0; }
        .qr-box { display: inline-block; padding: 20px; background: white; border: 2px solid #000000; box-shadow: 4px 4px 0 0 #000000; }
        .qr-box img { max-width: 200px; }
        .receipt { background: #F3F4F6; border: 1px dashed #000000; padding: 15px; margin-top: 20px; }
        .receipt-line { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dotted #000000; }
        .receipt-line:last-child { border-bottom: none; font-weight: bold; }
        .footer { background: #F3F4F6; padding: 15px; text-align: center; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>QueueLy - Confirmació</h1>
        </div>
        <div class="content">
            <p>Gràcies per la teva compra!</p>
            <p>A continuació trobes el detall de la teva reserva:</p>
            
            <div class="receipt">
                <div class="receipt-line">
                    <span>Esdeveniment:</span>
                    <strong>{{ $booking->session->event->title }}</strong>
                </div>
                <div class="receipt-line">
                    <span>Data:</span>
                    <strong>{{ $booking->session->date }}</strong>
                </div>
                <div class="receipt-line">
                    <span>Hora:</span>
                    <strong>{{ $booking->session->time }}</strong>
                </div>
                <div class="receipt-line">
                    <span>Lloc:</span>
                    <strong>{{ $booking->session->event->venue }}</strong>
                </div>
                
                @php
                $seats = [];
                $zones = [];
                foreach ($booking->tickets as $ticket) {
                    if ($ticket->row !== null) $seats[] = "Fila {$ticket->row}, Seient {$ticket->col}";
                    if ($ticket->zone) {
                        if (!isset($zones[$ticket->zone->name])) $zones[$ticket->zone->name] = 0;
                        $zones[$ticket->zone->name]++;
                    }
                }
                @endphp
                
                @if(count($seats) > 0)
                <div class="receipt-line">
                    <span>Seients:</span>
                    <strong>{{ implode(', ', $seats) }}</strong>
                </div>
                @endif
                
                @if(count($zones) > 0)
                @foreach($zones as $name => $count)
                <div class="receipt-line">
                    <span>Zona {{ $name }}:</span>
                    <strong>{{ $count }} entrades</strong>
                </div>
                @endforeach
                @endif
                
                <div class="receipt-line">
                    <span>Total:</span>
                    <strong>{{ $booking->total }}€</strong>
                </div>
            </div>
            
            <div class="qr-section">
                <div class="qr-box">
                    <img src="{{ $qrImage }}" alt="QR Code" />
                </div>
                <p style="margin-top: 10px; font-size: 12px; color: #666;">Presenta aquest codi a l'entrada</p>
            </div>
        </div>
        <div class="footer">
            <p>QueueLy - Entrades Online</p>
            <p>Si tens qualsevol dubte, contacta'ns a support@queuely.app</p>
        </div>
    </div>
</body>
</html>