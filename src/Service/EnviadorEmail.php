<?php

namespace Alura\Leilao\Service;

use Alura\Leilao\Model\Leilao;
use DomainException;

class EnviadorEmail
{
    public function notificarTerminoLeilao(Leilao $leilao): void
    {
        $sucesso = mail(
            'usuario@email.com',
            'Leilao Finalizado',
            "O leilão para {$leilao->recuperarDescricao()} foi finalizado",
        );

        if (!$sucesso) {
            throw new DomainException('Erro ao enviar o e-mail');
        }
    }
}