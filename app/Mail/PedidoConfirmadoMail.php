<?php

namespace App\Mail;

use App\Models\Pedido;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email de confirmación de compra. Se envía cuando un pedido se confirma
 * (ver CheckoutService::confirmar()).
 *
 * En los tests NO se manda de verdad: se usa Mail::fake() para verificar
 * que "se habría enviado", sin depender de un servidor de correo real
 * (requisito 5: mocking de un servicio externo).
 */
class PedidoConfirmadoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Pedido $pedido)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Confirmación de tu compra #{$this->pedido->id}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.pedido-confirmado',
            with: [
                'pedido' => $this->pedido,
            ],
        );
    }
}
