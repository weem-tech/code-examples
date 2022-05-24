<?php

namespace App\Command\Cron;

use App\Entity\Subscription\Feature;
use App\Entity\Subscription\FeatureSubscription;
use App\Entity\User\User;
use App\Service\Core\MailService;
use App\Service\Frontend\UserService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CheckFeaturesCommand
 * @package App\Command\Cron
 */
class CheckFeaturesCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'app:cron:check-features';

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var UserService
     */
    private $userService;

    /**
     * @var MailService
     */
    private $mailService;

    /**
     * CheckFeaturesCommand constructor.
     * @param string|null $name
     * @param EntityManagerInterface $em
     * @param UserService $userService
     * @param MailService $mailService
     */
    public function __construct(
        ?string $name = null,
        EntityManagerInterface $em,
        UserService $userService,
        MailService $mailService
    )
    {
        parent::__construct($name);

        $this->em = $em;
        $this->userService = $userService;
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
        $date = new DateTime('+2 days');
        $featureSubscriptions = $this->em->getRepository(FeatureSubscription::class)->findByExpiredDate($date);

        /** @var FeatureSubscription $featureSubscription */
        foreach ($featureSubscriptions as $featureSubscription) {
            /** @var User $user */
            $user = $featureSubscription->getUser();

            // remind choose feature plan
            $this->mailService->sendMail([
                'recipientEmail' => $user->getEmail(),
                'recipientName' => $user->getName(),
                'subject' => 'Feature subscription will be expired on ' . $featureSubscription->getExpiredDate()->format('d/m/y'),
                'template' => 'frontend/email/feature_reminder.twig',
                'params' => [
                    'name' => $user->getName(),
                    'shop' => $featureSubscription->getShop()->getUrl(),
                    'shopId' => $featureSubscription->getShop()->getId(),
                    'feature' => $featureSubscription->getFeature()->getName(),
                    'expiredDate' => $featureSubscription->getExpiredDate()
                ]
            ]);
        }

        $output->writeln('Command successfully executed!');
    }
}