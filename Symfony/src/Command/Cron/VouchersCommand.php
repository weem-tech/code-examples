<?php

namespace App\Command\Cron;

use App\Entity\Voucher\Voucher;
use App\Service\Core\MailService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class VouchersCommand
 * @package App\Command\Cron
 */
class VouchersCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'app:cron:vouchers';

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var MailService
     */
    private $mailService;


    /**
     * VouchersCommand constructor.
     * @param string|null $name
     * @param EntityManagerInterface $em
     * @param MailService $userService
     */
    public function __construct(
        ?string $name = null,
        EntityManagerInterface $em,
        MailService $mailService
    )
    {
        parent::__construct($name);

        $this->em = $em;
        $this->mailService = $mailService;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $vouchers = $this->em->getRepository(Voucher::class)->findPendingToActivate();
        foreach ($vouchers as $voucher) {
            $voucher->setActive(true);
            $this->em->persist($voucher);

            $this->mailService->sendMail([
                'recipientEmail' => $voucher->getUser()->getEmail(),
                'recipientName' => $voucher->getUser()->getName(),
                'subject' => 'Your voucher campaign has been activated',
                'template' => 'frontend/email/voucher_activation.twig',
                'params' => [
                    'name' => $voucher->getUser()->getName(),
                    'voucher' => $voucher,
                    'status' => 'active'
                ]
            ]);
        }


        $expired_vouchers = $this->em->getRepository(Voucher::class)->findActiveToFinish();
        foreach ($expired_vouchers as $voucher) {
            $voucher->setActive(false);
            $this->em->persist($voucher);

            $this->mailService->sendMail([
                'recipientEmail' => $voucher->getUser()->getEmail(),
                'recipientName' => $voucher->getUser()->getName(),
                'subject' => 'Your voucher campaign has been expired!',
                'template' => 'frontend/email/voucher_expired.twig',
                'params' => [
                    'name' => $voucher->getUser()->getName(),
                    'voucher' => $voucher,
                    'status' => 'finish'
                ]
            ]);
        }

        $this->em->flush();
        $output->writeln('Command successfully executed!');
    }
}