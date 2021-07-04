<?php

namespace Alura\Leilao\Tests\Domain;

use Alura\Leilao\Dao\Leilao as LeilaoDao;
use Alura\Leilao\Model\Leilao;
use Alura\Leilao\Service\Encerrador;
use Alura\Leilao\Service\EnviadorEmail;
use DateTimeImmutable;
use DomainException;
use PHPUnit\Framework\TestCase;

class EncerradorTest extends TestCase
{
    private Encerrador $encerrador;
    /** @var MockObject */
    private $enviadorEmail;
    private Leilao $leilaoFiat147;
    private Leilao $leilaoVariant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->leilaoFiat147 = new Leilao(
            'Fiat 147 0 Km',
            new DateTimeImmutable('8 days ago')
        );

        $this->leilaoVariant = new Leilao(
            'this->leilaoVariant 1972 0 Km',
            new DateTimeImmutable('10 days ago')
        );

        $leilaoDao = $this->createMock(LeilaoDao::class);

        $leilaoDao->method('recuperarNaoFinalizados')->willReturn([$this->leilaoFiat147, $this->leilaoVariant]);
        $leilaoDao->method('recuperarFinalizados')->willReturn([$this->leilaoFiat147, $this->leilaoVariant]);

        $leilaoDao->expects($this->exactly(2))->method('atualiza')->withConsecutive([$this->leilaoFiat147], [$this->leilaoVariant]);

        $this->enviadorEmail = $this->createMock(EnviadorEmail::class);

        $this->encerrador = new Encerrador($leilaoDao, $this->enviadorEmail);
    }

    public function testLeiloesComMaisDeUmaSemanaDevemSerEncerrados() {
        $this->encerrador->encerra();

        $leiloes = [$this->leilaoFiat147, $this->leilaoVariant];

        self::assertCount(2, $leiloes);
        self::assertTrue($leiloes[0]->estaFinalizado());
        self::assertTrue($leiloes[1]->estaFinalizado());
    }

    public function testDeveContinuarOProcessamentoAoEncontrarErroAoEnviarEmail()
    {
        $this->enviadorEmail->expects($this->exactly(2))
            ->method('notificarTerminoLeilao')
            ->willThrowException(new DomainException('Erro ao enviar e-mail'));

        $this->encerrador->encerra();
    }

    public function testSoDeveEnviarLeilaoAposFinalizado()
    {
        $this->enviadorEmail->expects($this->exactly(2))
            ->method('notificarTerminoLeilao')
            ->willReturnCallback(function (Leilao $leilao) {
                static::assertTrue($leilao->estaFinalizado());
            });

        $this->encerrador->encerra();
    }
}
