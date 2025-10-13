<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WorkflowMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $titulo;
    public array $datos;     // pares [label => value]
    public ?string $actionUrl;
    public ?string $actionText;

    public function __construct(string $titulo, array $datos, ?string $actionUrl=null, ?string $actionText=null)
    {
        $this->titulo     = $titulo;
        $this->datos      = $datos;
        $this->actionUrl  = $actionUrl;
        $this->actionText = $actionText;
    }

    public function build()
    {
        return $this->subject($this->titulo)
            ->view('mail.workflow');
    }
}
