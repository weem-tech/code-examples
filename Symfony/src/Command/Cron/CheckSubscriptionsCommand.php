<?php

namespace App\Command\Cron;

use App\Entity\Subscription\Subscription;
use App\Service\Core\MailService;
use App\Service\Frontend\SubscriptionService;
use App\Service\Frontend\UserService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CheckSubscriptionsCommand
 * @package App\Command\Cron
 */
class CheckSubscriptionsCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'app:cron:check-subscriptions';

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var UserService
     */
    private $userService;

    /**
     * @var SubscriptionService
     */
    private $subscriptionService;

    /**
     * @var MailService
     */
    private $mailService;

    /**
     * CheckSubscriptionsCommand constructor.
     * @param string|null $name
     * @param EntityManagerInterface $em
     * @param UserService $userService
     * @param SubscriptionService $subscriptionService
     * @param MailService $mailService
     */
    public function __construct(
        ?string $name = null,
        EntityManagerInterface $em,
        UserService $userService,
        SubscriptionService $subscriptionService,
        MailService $mailService
    )
    {
        parent::__construct($name);

        $this->em = $em;
        $this->userService = $userService;
        $this->subscriptionService = $subscriptionService;
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
        $subscriptions = $this->em->getRepository(Subscription::class)->findByExpiresDate($date);

        foreach ($subscriptions as $subscription) {
            $user = $subscription->getUser();
            $nextPlan = $user->getSubscriptionPlan();
            $currentSubscription = $this->em->getRepository(Subscription::class)->getUserCurrentSubscription($user->getId());
            if ($nextPlan) {
                if ($nextPlan->getLevel() < $currentSubscription->getPlan()->getLevel()) {
                    $this->mailService->sendMail([
                        'recipientEmail' => $user->getEmail(),
                        'recipientName' => $user->getName(),
                        'subject' => 'Your plan will be downgraded on ' . $currentSubscription->getExpiresDate()->format('d/m/y'),
                        'template' => 'frontend/email/subscription_downgrade_reminder.twig',
                        'params' => [
                            'name' => $user->getName(),
                            'expiresDate' => $currentSubscription->getExpiresDate(),
                            'planName' => $nextPlan->getName()
                        ]
                    ]);
                }
            } else {
                // remind choose next subscription plan
                $this->mailService->sendMail([
                    'recipientEmail' => $user->getEmail(),
                    'recipientName' => $user->getName(),
                    'subject' => 'Subscription will be expired on ' . $currentSubscription->getExpiresDate()->format('d/m/y'),
                    'template' => 'frontend/email/subscription_reminder.twig',
                    'params' => [
                        'name' => $user->getName(),
                        'expiresDate' => $currentSubscription->getExpiresDate()
                    ]
                ]);
            }
        }

        $output->writeln('Command successfully executed!');
    }
}