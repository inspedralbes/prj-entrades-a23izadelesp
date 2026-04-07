<?php

namespace App\Services;

use App\Models\Booking;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;

class QrService
{
    public function generate(Booking $booking): string
    {
        $qrData = $this->buildQrData($booking);
        
        $qrCode = new QrCode(
            data: $qrData,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 300,
            margin: 10,
            foregroundColor: new Color(0, 0, 0),
            backgroundColor: new Color(255, 255, 255)
        );
        
        $writer = new PngWriter();
        $result = $writer->write($qrCode);
        
        return 'data:image/png;base64,' . base64_encode($result->getString());
    }

    public function generateWithLabel(Booking $booking): string
    {
        $qrData = $this->buildQrData($booking);
        $event = $booking->session->event;
        $label = Label::create($event->title);
        
        $qrCode = new QrCode(
            data: $qrData,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 300,
            margin: 10,
            foregroundColor: new Color(0, 0, 0),
            backgroundColor: new Color(255, 255, 255),
            label: $label
        );
        
        $writer = new PngWriter();
        $result = $writer->write($qrCode);
        
        return 'data:image/png;base64,' . base64_encode($result->getString());
    }

    private function buildQrData(Booking $booking): string
    {
        $booking->load(['session.event', 'tickets.seat', 'tickets.zone']);
        
        $event = $booking->session->event;
        $session = $booking->session;
        
        $data = [
            'booking_id' => $booking->id,
            'event' => $event->title,
            'session' => $session->date . ' ' . $session->time,
            'total' => $booking->total,
        ];
        
        $seats = [];
        $zones = [];
        
        foreach ($booking->tickets as $ticket) {
            if ($ticket->seat) {
                $seats[] = "F{$ticket->seat->row}-{$ticket->seat->number}";
            }
            if ($ticket->zone) {
                if (!isset($zones[$ticket->zone->name])) {
                    $zones[$ticket->zone->name] = 0;
                }
                $zones[$ticket->zone->name]++;
            }
        }
        
        if (!empty($seats)) {
            $data['seats'] = implode(', ', $seats);
        }
        
        if (!empty($zones)) {
            $zoneStr = [];
            foreach ($zones as $name => $count) {
                $zoneStr[] = "{$name}x{$count}";
            }
            $data['zones'] = implode(', ', $zoneStr);
        }
        
        return json_encode($data);
    }
}